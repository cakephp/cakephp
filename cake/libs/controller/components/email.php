<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.controller.components
 * @since			CakePHP(tm) v 1.2.0.3467
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components
 *
 */
class EmailComponent extends Object {
/**
 * Recipient of the email
 *
 * @access public
 * @var string
 */
	var $to = null;
/**
 * The mail which the email is sent from
 *
 * @access public
 * @var string
 */
	var $from = null;
/**
 * The email the recipient will reply to
 *
 * @access public
 * @var string
 */
	var $replyTo = null;
/**
 * The mail that will be used in case of any errors like
 * - Remote mailserver down
 * - Remote user has exceeded his quota
 * - Unknown user
 *
 * @access public
 * @var string
 */
	var $return = null;
/**
 * Carbon Copy
 *
 * List of email's that should receive a copy of the email.
 * The Recipient WILL be able to see this list
 *
 * @access public
 * @var string|array
 */
	var $cc = array();
/**
 * Blind Carbon Copy
 *
 * List of email's that should receive a copy of the email.
 * The Recipient WILL NOT be able to see this list
 *
 * @access public
 * @var string|array
 */
	var $bcc = array();
/**
 * The subject of the email
 *
 * @access public
 * @var string
 */
	var $subject = null;
/**
 * List of extra / custom headers
 *
 * @access public
 * @var array
 */
	var $headers = array();
/**
 * List of additional headers
 *
 * These will NOT be used if you are using safemode and mail()
 *
 * @access public
 * @var array
 */
	var $additionalParams = null;
/**
 * Layout for the View
 *
 * @access public
 * @var string
 */
	var $layout = 'default';
/**
 * Template for the view
 *
 * @access public
 * @var string
 */
	var $template = null;
/**
 * What format should the email be sent in
 * Supported formats:
 *  - text
 *  - html
 *  - both
 *
 * @access public
 * @var string
 */
	var $sendAs = 'text';
/**
 * What method should the email be sent by
 * Supported methods:
 *  - mail
 *  - smtp
 *  - debug
 *
 * @access public
 * @var string
 */
	var $delivery = 'mail';
/**
 * What charset should the email be sent in
 *
 * @example UTF-8
 * @access public
 * @var string
 */
	var $charset = 'ISO-8859-15';
/**
 * List of files that should be attached to the email.
 * Can be both absolute and relative paths
 *
 * @access public
 * @var array
 */
	var $attachments = array();
/**
 * The list of paths to search if an attachment isnt absolute
 *
 * @access public
 * @var array
 */
	var $filePaths = array();
/**
 * What mailer should EmailComponent identify itself as
 *
 * @access public
 * @var string
 */
	var $xMailer = 'CakePHP Email Component';
/**
 * Placeholder for any errors that might happen with the
 * smtp mail methods
 *
 * @access public
 * @var string
 */
	var $smtpError = null;
/**
 * List of options to use for smtp mail method
 * Options is:
 *  - port
 *  - host
 *  - timeout
 *
 * @access public
 * @var array
 */
	var $smtpOptions = array(
		'port'=> 25,
		'host' => 'localhost',
		'timeout' => 30);
/**
 * If set to true, the mail method will be auto-set to 'debug'
 *
 * @access protected
 * @var boolean
 */
	var $_debug = false;
/**
 * ???
 * @deprecated Isnt used anywhere
 * @access protected
 * @var boolean
 */
	var $_error = false;
/**
 * Placeholder for the newline (\n)
 *
 * @access protected
 * @var string
 */
	var $_newLine = "\n";
/**
 * Placeholder for the maximum allowed line length
 *
 * @access protected
 * @var integer
 */
	var $_lineLength = 70;
/**
 * Placeholder for header data
 *
 * @access private
 * @var string
 */
	var $__header = null;
/**
 * Placeholder for the boundary seperator.
 *
 * Used to split the message up into different parts eg. for
 * HTML and Text email or file attachments
 *
 * @access private
 * @var string
 */
	var $__boundary = null;
/**
 * The email message.
 *
 * Can contain cleartext and bass64 encoded data (File attachments)
 *
 * @access private
 * @var string
 */
	var $__message = null;
/**
 * Placeholder for the SMTP socket connection
 *
 * @access private
 * @var string
 */
	var $__smtpConnection = null;
/**
 * Placeholder for the default object variables
 *
 * @access private
 * @var array
 */
	var $__defaults = array();
/**
 * Called from the controller to start up the component
 *
 * @access public
 * @param AppController $controller
 */
	function startup(&$controller) {
		$this->__defaults = get_object_vars($this);
		$this->Controller = & $controller;
	}
/**
 * Reset the EmailComponent settings to default, with the
 * possibilty to inject some changes.
 *
 * @access public
 * @param array $settings
 * @param array $exempt
 */
	function reset($settings = array(), $exempt = array()) {
		foreach ($this->__defaults as $key => $value) {
			if(array_key_exists($key,$exempt)) {
				continue;
			}
			if(array_key_exists($key,$settings)) {
				$this->{$key} = $settings[$key];
			} else {
				$this->{$key} = $value;
			}
		}
	}
/**
 * Send Email(s)
 *
 * @access public
 * @param mixed $content
 * @param mixed $template
 * @param mixed $layout
 * @return boolean true on success, false on failure
 */
	function send($content = null, $template = null, $layout = null) {
		$this->__createHeader();
		$this->subject = $this->__encode($this->subject);

		if (!empty($template)) {
			$this->template = $template;
		}

		if (!empty($layout)) {
			$this->layout = $layout;
		}

		if ($template === null && $this->template === null) {
			if (is_array($content)) {
				$message = "";
				foreach ($content as $value) {
					$message .= $value . $this->_newLine;
				}
			} else {
				$message = $content;
			}
			$this->__formatMessage($message);
		} else {
			$this->__message = $this->__renderTemplate($content);
		}

		if (!empty($this->attachments)) {
			$this->__message .= $this->_newLine . $this->_newLine;
			$this->__attachFiles();
		}

		if ($this->_debug) {
			$this->delivery = 'debug';
		}

		$__method = '__'.$this->delivery;
		if(method_exists($this,$__method)) {
			return call_user_func(array($this,$__method));
		}

		user_error('Invalid mailer defined. (mail,smtp,debug)',E_USER_ERROR);
		return false;
	}
/**
 * Rendering the cake template, using the current View class
 *
 * @uses View to render the templates
 * @see http://manual.cakephp.org/chapter/views
 * @access private
 * @param string $content
 * @return string The rendered view
 */
	function __renderTemplate($content) {
		$View = new View($this->Controller);
		$View->layout = $this->layout;
		$content = $this->__strip($content);

		if ($this->sendAs === 'both') {
			$htmlContent = $content;
			$msg = '--' . $this->__createBoundary() . $this->_newLine;
			$msg .= 'Content-Type: text/plain; charset=' . $this->charset . $this->_newLine;
			$msg .= 'Content-Transfer-Encoding: 8bit' . $this->_newLine . $this->_newLine;
			$content = $View->renderElement('email' . DS . 'text' . DS . $this->template, array('content' => $content), true);
			$View->layoutPath = 'email' . DS . 'text';
			$msg .= $View->renderLayout($content) . $this->_newLine;

			$msg .= $this->_newLine. '--' . $this->__createBoundary() . $this->_newLine;
			$msg .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
			$msg .= 'Content-Transfer-Encoding: 8bit' . $this->_newLine;
			$content = $View->renderElement('email' . DS . 'html' . DS . $this->template, array('content' => $htmlContent), true);
			$View->layoutPath = 'email' . DS . 'html';
			return $msg . $View->renderLayout($content);
		} else {
			$msg = "";
			if(!empty($this->attachments)) {
				$msg .= $this->_newLine. '--' . $this->__createBoundary() . $this->_newLine;
				$msg .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
				$msg .= 'Content-Transfer-Encoding: 8bit' . $this->_newLine . $this->_newLine;
			}
			$content = $View->renderElement('email' . DS . $this->sendAs . DS . $this->template, array('content' => $content), true);
			$View->layoutPath = 'email' . DS . $this->sendAs;
			return $msg . $View->renderLayout($content);
		}
	}
/**
 * Creates a unique boundary used to seperate
 * - Header
 * - Html / Text content
 * - attached files
 *
 * @access private
 * @return string
 */
	function __createBoundary() {
		if(empty($this->__boundary)) {
			$this->__boundary = '==Multipart_Boundary_x'.md5(uniqid(time())).'x';
		}
		return $this->__boundary;
	}
/**
 * Create the mail headers
 *
 * - From
 * - Reply-To (Optimal)
 * - Return-Path (Optimal)
 * - Carbon Copy [CC] (Optimal)
 * - Blind Carbon Copy [BCC] (Optimal)
 * - Additional headers (Optimal)
 *
 * @see http://www.faqs.org/rfcs/rfc822.html (4.1)
 * @access private
 */
	function __createHeader() {
		$this->__header .= 'From: ' . $this->__formatAddress($this->from) . $this->_newLine;

		if (!empty($this->replyTo)) {
			$this->__header .= 'Reply-To: ' . $this->__formatAddress($this->replyTo) . $this->_newLine;
		}

		if (!empty($this->return)) {
			$this->__header .= 'Return-Path: ' . $this->__formatAddress($this->return) . $this->_newLine;
		}

		$addresses = null;
		if (!empty($this->cc)) {
			if(!is_array($this->cc)) {
				$this->cc = array($this->cc);
			}
			foreach ($this->cc as $cc) {
				$addresses .= ', ' . $this->__formatAddress($cc);
			}
			$this->__header .= 'cc: ' . substr($addresses, 2) . $this->_newLine;
		}

		$addresses = null;
		if (!empty($this->bcc)) {
			foreach ($this->bcc as $bcc) {
				$addresses .= ', ' . $this->__formatAddress($bcc);
			}
			$this->__header .= 'Bcc: ' . substr($addresses, 2) . $this->_newLine;
		}

		if (!empty($this->headers)) {
			foreach ($this->headers as $key => $val) {
				$this->__header .= 'X-'.$key.': '.$val . $this->_newLine;
			}
		}

		$this->__header .= 'X-Mailer: ' . $this->xMailer . $this->_newLine;

		if (!empty($this->attachments) && $this->sendAs === 'text') {
			$this->__header .= 'MIME-Version: 1.0' . $this->_newLine;
			$this->__header .= 'Content-Type: multipart/mixed; boundary="' . $this->__createBoundary() . '"' . $this->_newLine;
		} elseif (!empty($this->attachments) && $this->sendAs === 'html') {
			$this->__header .= 'MIME-Version: 1.0' . $this->_newLine;
			$this->__header .= 'Content-Type: multipart/related; boundary="' . $this->__createBoundary() . '"' . $this->_newLine;
		} elseif ($this->sendAs === 'html') {
			$this->__header .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
			$this->__header .= 'Content-Transfer-Encoding: 8bit' . $this->_newLine;
		} elseif ($this->sendAs === 'both') {
			$this->__createBoundary();
			$this->__header .= 'MIME-Version: 1.0' . $this->_newLine;
			$this->__header .= 'Content-Type: multipart/alternative; boundary="' . $this->__createBoundary() . '"' . $this->_newLine;
		}
	}
/**
 * Format the mail message.
 *
 * Used only if you havent specified a view template and layout.
 * Adds a Text and Html version of the specified content from send()'s $content
 *
 * @see send()
 * @access private
 * @param string $message
 */
	function __formatMessage($message) {
		$message = $this->__wrap($message);

		if ($this->sendAs === 'both') {
			$this->__message = '--' . $this->__createBoundary() . $this->_newLine;
			$this->__message .= 'Content-Type: text/plain; charset=' . $this->charset . $this->_newLine;
			$this->__message .= 'Content-Transfer-Encoding: 8bit' . $this->_newLine;
			$this->__message .= 'If you are seeing this is because you may need to change your'.$this->_newLine;
			$this->__message .= 'preferred message format from HTML to plain text.'.$this->_newLine.$this->_newLine;
			$this->__message .= strip_tags($message) . $this->_newLine;

			$this->__message .= $this->__createBoundary() . $this->_newLine;
			$this->__message .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
			$this->__message .= 'Content-Transfer-Encoding: 8bit' . $this->_newLine;
			$this->__message .= $message . $this->_newLine;
			$this->__message .= $this->_newLine . $this->_newLine;
		} else {
			$this->__message .= $message . $this->_newLine;
		}
	}
/**
 * attach files to the mail message.
 *
 * For each element in the attachments list it will:
 *      - Check if the attachment exists (eg. absolute path)
 *      - Check if the attachment exists within any of the filePaths
 * If none of the two scenarios above is a success, the attachment will be ignored!
 *
 * When the absolute path for the attachment has been found, it will attemp to
 * guess the mimetype for the file (application/pdf, test/javascript ect).
 * If it cannot find the mime-type, an E_USER_ERROR is raised and the operation is aborted
 *
 * Finally it will attach the file to the message
 *
 * @see __findFiles()
 * @access private
 */
	function __attachFiles() {
		foreach ($this->attachments as $attachment) {
			$files[] = $this->__findFiles($attachment);
		}

		foreach ($files as $file) {
			$handle = fopen($file, 'rb');
			$data = fread($handle, filesize($file));
			$data = chunk_split(base64_encode($data)) ;
			$filetype = trim(mime_content_type($file));

			if(empty($filetype)) {
				user_error('Unable to get mimetype for e-mail attachment', E_USER_ERROR);
				break;
			}

			$this->__message .= '--' . $this->__createBoundary() . $this->_newLine;
			$this->__message .= 'Content-Type: ' . $filetype . '; name="' . basename($file) . '"' . $this->_newLine;
			$this->__message .= 'Content-Transfer-Encoding: base64' . $this->_newLine;
			$this->__message .= 'Content-Disposition: attachment; filename="' . basename($file) . '"' . $this->_newLine . $this->_newLine;
			$this->__message .= $data . $this->_newLine . $this->_newLine;
		}
	}
/**
 * Attempt to locate the absolute path of an attachment.
 *
 * @see __attachFiles()
 * @access private
 * @param string $attachment
 * @return string|null
 */
	function __findFiles($attachment) {
		if(is_file($attachment)) {
			return $attachment;
		}

		foreach ($this->filePaths as $path) {
			if (file_exists($path . DS . $attachment)) {
				$file = $path . DS . $attachment;
				return $file;
			}
		}
	}
/**
 * Wrap a string so a line cannot be no longer than _lineLength
 *
 * @see _lineLength
 * @see __formatMessage()
 * @access private
 * @param string $message
 * @return string
 */
	function __wrap($message) {
		$message = $this->__strip($message, true);
		$message = str_replace(array('\r','\n'), '\n', $message);
		$words = explode('\n', $message);
		$formated = null;

		foreach ($words as $word) {
			$formated .= wordwrap($word, $this->_lineLength, "\n", true);
			$formated .= "\n";
		}
		return $formated;
	}
/**
 * base64_encode a string if charset isnt ISO-8859-15 to fit the RFC
 *
 * @example Subject: =?UTF-8?B?RmFrdHVyYSBmb3IgaW5ka8O4Yg==?=
 * @see http://www.faqs.org/rfcs/rfc822.html
 * @access private
 * @param string $subject
 * @return string
 */
	function __encode($string) {
		$string = $this->__strip($string);

		if (low($this->charset) !== 'iso-8859-15') {
			$start = "=?" . $this->charset . "?B?";
			$end = "?=";
			$spacer = $end . "\n " . $start;

			$length = 75 - strlen($start) - strlen($end);
			$length = $length - ($length % 4);

			$string = base64_encode($string);
			$string = chunk_split($string, $length, $spacer);
			$spacer = preg_quote($spacer);
			$string = preg_replace("/" . $spacer . "$/", "", $string);
			$string = $start . $string . $end;
		}
		return $string;
	}
/**
 * Format a string to fit the RFC
 *
 * @example John Doe <john@cakephp.org>
 * @see http://www.faqs.org/rfcs/rfc822.html (A.2)
 * @access private
 * @param string $string
 * @return string
 */
	function __formatAddress($string) {
		if (strpos($string, '<') !== false) {
			$value = explode('<', $string);
			$string = $this->__encode($value[0]) . ' <' . $value[1];
		}
		return $this->__strip($string);
	}
/**
 * Strip a string from anything that could be related
 *
 * @access private
 * @param string $value
 * @param boolean $message
 * @return string
 */
	function __strip($value, $message = false) {
		$search = array('/%0a/i', '/%0d/i', '/Content-Type\:/i',
		'/charset\=/i', '/mime-version\:/i', '/multipart\/mixed/i',
		'/bcc\:/i','/to\:/i','/cc\:/i', '/\\r/i', '/\\n/i');

		if(false === $message) {
			$search = array_slice($search,2);
		}
		return preg_replace($search, '', $value);
	}
/**
 * Default delivery method for EmailComponent
 *
 * @see http://www.php.net/manual/en/function.mail.php
 * @access private
 * @return boolean true on success, false on error
 */
	function __mail() {
		if (ini_get('safe_mode')) {
			return @mail($this->to, $this->subject, $this->__message, $this->__header);
		}
		return @mail($this->to, $this->subject, $this->__message, $this->__header, $this->additionalParams);
	}
/**
 * Optimal SMTP delivery method
 *
 * @access private
 * @return boolean true on success, false on error
 */
	function __smtp() {
		$response = $this->__smtpConnect($this->smtpOptions);

		if ($response['status'] == false) {
			$this->smtpError = "{$response['errno']}: {$response['errstr']}";
			return false;
		}

		$this->__sendData("HELO cake\r\n", false);

		if (!$this->__sendData("MAIL FROM: {$this->from}\r\n")) {
			return false;
		}

		if (!$this->__sendData("RCPT TO: {$this->to}\r\n")) {
			return false;
		}

		$this->__sendData("DATA\r\n{$this->__header}\r\n{$this->__message}\r\n\r\n\r\n.\r\n", false);
		$this->__sendData("QUIT\r\n", false);
		return true;
	}

/**
 * Enter description here...
 *
 * @param unknown_type $options
 * @return unknown
 */
	function __smtpConnect($options) {
		$status = true;
		$this->__smtpConnection = @fsockopen($options['host'], $options['port'], $errno, $errstr, $options['timeout']);

		if ($this->__smtpConnection == false) {
			$status = false;
		}

		return array('status' => $status,
			'errno' => $errno,
			'errstr' => $errstr);
	}
/**
 * Enter description here...
 *
 * @return unknown
 */
	function __getSmtpResponse() {
		$response = @fgets($this->__smtpConnection, 512);
		return $response;
	}
/**
 * Enter description here...
 *
 * @param unknown_type $data
 * @param unknown_type $check
 * @return unknown
 */
	function __sendData($data, $check = true) {
		@fwrite($this->__smtpConnection, $data);
		$response = $this->__getSmtpResponse();

		if ($check == true && !stristr($response, '250')) {
			$this->smtpError = $response;
			return false;
		}
		return true;
	}
/**
 * Enter description here...
 *
 * @return unknown
 */
	function __debug() {
		$fm = '<pre>';

		if ($this->delivery == 'smtp') {
			$fm .= sprintf('%s %s', 'Host:', $this->smtpOptions['host']);
			$fm .= sprintf('%s %s', 'Port:', $this->smtpOptions['port']);
			$fm .= sprintf('%s %s', 'Timeout:', $this->smtpOptions['timeout']);
		}

		$fm .= sprintf('%s %s', 'To:', $this->to);
		$fm .= sprintf('%s %s', 'From:', $this->from);
		$fm .= sprintf('%s %s', 'Subject:', $this->subject);
		$fm .= sprintf('%s\n\n%s', 'Header:', $this->__header);
		$fm .= sprintf('%s\n\n%s', 'Parameters:', $this->additionalParams);
		$fm .= sprintf('%s\n\n%s', 'Message:', $this->__message);
		$fm .= '</pre>';

		$this->Controller->Session->setFlash($fm, 'default', null, 'email');
		return true;
	}
}
?>