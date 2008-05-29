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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
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
class EmailComponent extends Object{
/**
 * Recipient of the email
 *
 * @var string
 * @access public
 */
	var $to = null;
/**
 * The mail which the email is sent from
 *
 * @var string
 * @access public
 */
	var $from = null;
/**
 * The email the recipient will reply to
 *
 * @var string
 * @access public
 */
	var $replyTo = null;
/**
 * The read receipt email
 *
 * @var string
 * @access public
 */
	var $readReceipt = null;
/**
 * The mail that will be used in case of any errors like
 * - Remote mailserver down
 * - Remote user has exceeded his quota
 * - Unknown user
 *
 * @var string
 * @access public
 */
	var $return = null;
/**
 * Carbon Copy
 *
 * List of email's that should receive a copy of the email.
 * The Recipient WILL be able to see this list
 *
 * @var array
 * @access public
 */
	var $cc = array();
/**
 * Blind Carbon Copy
 *
 * List of email's that should receive a copy of the email.
 * The Recipient WILL NOT be able to see this list
 *
 * @var array
 * @access public
 */
	var $bcc = array();
/**
 * The subject of the email
 *
 * @var string
 * @access public
 */
	var $subject = null;
/**
 * Associative array of a user defined headers
 * Keys will be prefixed 'X-' as per RFC822 Section 4.7.5
 *
 * @var array
 * @access public
 */
	var $headers = array();
/**
 * List of additional headers
 *
 * These will NOT be used if you are using safemode and mail()
 *
 * @var string
 * @access public
 */
	var $additionalParams = null;
/**
 * Layout for the View
 *
 * @var string
 * @access public
 */
	var $layout = 'default';
/**
 * Template for the view
 *
 * @var string
 * @access public
 */
	var $template = null;
/**
 * What format should the email be sent in
 *
 * Supported formats:
 * - text
 * - html
 * - both
 *
 * @var string
 * @access public
 */
	var $sendAs = 'text';
/**
 * What method should the email be sent by
 *
 * Supported methods:
 * - mail
 * - smtp
 * - debug
 *
 * @var string
 * @access public
 */
	var $delivery = 'mail';
/**
 * charset the email is sent in
 *
 * @var string
 * @access public
 */
	var $charset = 'utf-8';
/**
 * List of files that should be attached to the email.
 *
 * Can be both absolute and relative paths
 *
 * @var array
 * @access public
 */
	var $attachments = array();
/**
 * What mailer should EmailComponent identify itself as
 *
 * @var string
 * @access public
 */
	var $xMailer = 'CakePHP Email Component';
/**
 * The list of paths to search if an attachment isnt absolute
 *
 * @var array
 * @access public
 */
	var $filePaths = array();
/**
 * List of options to use for smtp mail method
 *
 * Options is:
 * - port
 * - host
 * - timeout
 * - username
 * - password
 *
 * @var array
 * @access public
 */
	var $smtpOptions = array('port'=> 25,
							 'host' => 'localhost',
							 'timeout' => 30);
/**
 * Placeholder for any errors that might happen with the
 * smtp mail methods
 *
 * @var string
 * @access public
 */
	var $smtpError = null;
/**
 * If set to true, the mail method will be auto-set to 'debug'
 *
 * @var string
 * @access protected
 */
	var $_debug = false;
/**
 * Enter description here...
 *
 * @var string
 * @access protected
 */
	var $_error = false;
/**
 * New lines char
 *
 * @var string
 * @access protected
 */
	var $_newLine = "\r\n";
/**
 * Enter description here...
 *
 * @var integer
 * @access protected
 */
	var $_lineLength = 70;
/**
 * Enter description here...
 *
 * @var string
 * @access private
 */
	var $__header = null;
/**
 * Enter description here...
 *
 * @var string
 * @access private
 */
	var $__boundary = null;
/**
 * Enter description here...
 *
 * @var string
 * @access private
 */
	var $__message = null;
/**
 * Variable that holds SMTP connection
 *
 * @var resource
 * @access private
 */
	var $__smtpConnection = null;
/**
 * Startup component
 *
 * @param object $controller Instantiating controller
 * @access public
 */
	function startup(&$controller) {
		$this->Controller =& $controller;
		if (Configure::read('App.encoding') !== null) {
			$this->charset = Configure::read('App.encoding');
		}
	}
/**
 * Send an email using the specified content, template and layout
 *
 * @param mixed $content Either an array of text lines, or a string with contents
 * @param string $template Template to use when sending email
 * @param string $layout Layout to use to enclose email body
 * @return boolean Success
 * @access public
 */
	function send($content = null, $template = null, $layout = null) {
		$this->__createHeader();

		if ($template) {
			$this->template = $template;
		}

		if ($layout) {
			$this->layout = $layout;
		}

		if (is_array($content)) {
			$message = null;
			foreach ($content as $key => $value) {
				$message .= $value . $this->_newLine;
			}
		} else {
			$message = $content;
		}

		if ($template === null && $this->template === null) {
			$this->__formatMessage($message);
		} else {
			$message = $this->__wrap($message);
			$this->__message = $this->__renderTemplate($message);
		}

		if (!empty($this->attachments)) {
			$this->__attachFiles();
		}

		if (!is_null($this->__boundary)) {
			$this->__message .= $this->_newLine .'--' . $this->__boundary . '--' . $this->_newLine . $this->_newLine;
		}

		if ($this->_debug) {
			return $this->__debug();
		}
		$__method = '__'.$this->delivery;
		return $this->$__method();
	}
/**
 * Reset all EmailComponent internal variables to be able to send out a new email.
 *
 * @access public
 */
	function reset() {
		$this->template = null;
		$this->to = null;
		$this->from = null;
		$this->replyTo = null;
		$this->return = null;
		$this->cc = array();
		$this->bcc = array();
		$this->subject = null;
		$this->additionalParams = null;
		$this->__header = null;
		$this->__boundary = null;
		$this->__message = null;
	}
/**
 * Render the contents using the current layout and template.
 *
 * @param string $content Content to render
 * @return string Email ready to be sent
 * @access private
 */
	function __renderTemplate($content) {
		$viewClass = $this->Controller->view;

		if ($viewClass != 'View') {
			if (strpos($viewClass, '.') !== false) {
				list($plugin, $viewClass) = explode('.', $viewClass);
			}
			$viewClass = $viewClass . 'View';
			App::import('View', $this->Controller->view);
		}
		$View = new $viewClass($this->Controller, false);
		$View->layout = $this->layout;
		$msg = null;

		if ($this->sendAs === 'both') {
			$htmlContent = $content;
			if (!empty($this->attachments)) {
				$msg .= '--' . $this->__boundary . $this->_newLine;
				$msg .= 'Content-Type: multipart/alternative; boundary="alt-' . $this->__boundary . '"' . $this->_newLine . $this->_newLine;
			}
			$msg .= '--alt-' . $this->__boundary . $this->_newLine;
			$msg .= 'Content-Type: text/plain; charset=' . $this->charset . $this->_newLine;
			$msg .= 'Content-Transfer-Encoding: 7bit' . $this->_newLine . $this->_newLine;

			$content = $View->element('email' . DS . 'text' . DS . $this->template, array('content' => $content), true);
			$View->layoutPath = 'email' . DS . 'text';
			$msg .= $View->renderLayout($content) . $this->_newLine;

			$msg .= $this->_newLine. '--alt-' . $this->__boundary . $this->_newLine;
			$msg .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
			$msg .= 'Content-Transfer-Encoding: 7bit' . $this->_newLine . $this->_newLine;

			$content = $View->element('email' . DS . 'html' . DS . $this->template, array('content' => $htmlContent), true);
			$View->layoutPath = 'email' . DS . 'html';
			$msg .= $View->renderLayout($content) . $this->_newLine . $this->_newLine;
			$msg .= '--alt-' . $this->__boundary . '--' . $this->_newLine . $this->_newLine;
			return $msg;

		}

		if (!empty($this->attachments)) {
			if ($this->sendAs === 'html') {
				$msg .= $this->_newLine. '--' . $this->__boundary . $this->_newLine;
				$msg .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
				$msg .= 'Content-Transfer-Encoding: 7bit' . $this->_newLine . $this->_newLine;
			} else {
				$msg .= '--' . $this->__boundary . $this->_newLine;
				$msg .= 'Content-Type: text/plain; charset=' . $this->charset . $this->_newLine;
				$msg .= 'Content-Transfer-Encoding: 7bit' . $this->_newLine . $this->_newLine;
			}
		}

		$content = $View->element('email' . DS . $this->sendAs . DS . $this->template, array('content' => $content), true);
		$View->layoutPath = 'email' . DS . $this->sendAs;
		$msg .= $View->renderLayout($content) . $this->_newLine;
		return $msg;
	}
/**
 * Create unique boundary identifier
 *
 * @access private
 */
	function __createBoundary() {
		$this->__boundary = md5(uniqid(time()));
	}
/**
 * Create emails headers including (but not limited to) from email address, reply to,
 * bcc and cc.
 *
 * @access private
 */
	function __createHeader() {
		$this->__header = '';
		if ($this->delivery == 'smtp') {
			$this->__header = 'To: ' . $this->__formatAddress($this->to) . $this->_newLine;
		}
		$this->__header .= 'From: ' . $this->__formatAddress($this->from) . $this->_newLine;

		if (!empty($this->replyTo)) {
			$this->__header .= 'Reply-To: ' . $this->__formatAddress($this->replyTo) . $this->_newLine;
		}
		if (!empty($this->return)) {
			$this->__header .= 'Return-Path: ' . $this->__formatAddress($this->return) . $this->_newLine;
		}
		if (!empty($this->readReceipt)) {
			$this->__header .= 'Disposition-Notification-To: ' . $this->__formatAddress($this->readReceipt) . $this->_newLine;
		}
		$addresses = null;

		if (!empty($this->cc)) {
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
		if ($this->delivery == 'smtp') {
			$this->__header .= 'Subject: ' . $this->__encode($this->subject) . $this->_newLine;
		}
		$this->__header .= 'X-Mailer: ' . $this->xMailer . $this->_newLine;

		if (!empty($this->headers)) {
			foreach ($this->headers as $key => $val) {
				$this->__header .= 'X-'.$key.': '.$val . $this->_newLine;
			}
		}

		if (!empty($this->attachments)) {
			$this->__createBoundary();
			$this->__header .= 'MIME-Version: 1.0' . $this->_newLine;
			$this->__header .= 'Content-Type: multipart/mixed; boundary="' . $this->__boundary . '"' . $this->_newLine;
			$this->__header .= 'This part of the E-mail should never be seen. If' . $this->_newLine;
			$this->__header .= 'you are reading this, consider upgrading your e-mail' . $this->_newLine;
			$this->__header .= 'client to a MIME-compatible client.' . $this->_newLine;
		} elseif ($this->sendAs === 'text') {
			$this->__header .= 'Content-Type: text/plain; charset=' . $this->charset . $this->_newLine;
		} elseif ($this->sendAs === 'html') {
			$this->__header .= 'Content-Type: text/html; charset=' . $this->charset . $this->_newLine;
		} elseif ($this->sendAs === 'both') {
			$this->__header .= 'Content-Type: multipart/alternative; boundary="alt-' . $this->__boundary . '"' . $this->_newLine . $this->_newLine;
		}

		$this->__header .= 'Content-Transfer-Encoding: 7bit';
	}
/**
 * Format the message by seeing if it has attachments.
 *
 * @param string $message Message to format
 * @access private
 */
	function __formatMessage($message) {
		$this->__message = '';
		if (!empty($this->attachments)) {
			$this->__message .= '--' . $this->__boundary . $this->_newLine;
			$this->__message .= 'Content-Type: text/plain; charset=' . $this->charset . $this->_newLine;
			$this->__message .= 'Content-Transfer-Encoding: 7bit' . $this->_newLine . $this->_newLine;
		}
		$message = $this->__wrap($message);
		$this->__message .= $message . $this->_newLine;
	}
/**
 * Attach files by adding file contents inside boundaries.
 *
 * @access private
 */
	function __attachFiles() {
		$files = array();
		foreach ($this->attachments as $attachment) {
			$file = $this->__findFiles($attachment);
			if (!empty($file)) {
				$files[] = $file;
			}
		}

		foreach ($files as $file) {
			$handle = fopen($file, 'rb');
			$data = fread($handle, filesize($file));
			$data = chunk_split(base64_encode($data)) ;
			fclose($handle);

			$this->__message .= '--' . $this->__boundary . $this->_newLine;
			$this->__message .= 'Content-Type: application/octet-stream' . $this->_newLine;
			$this->__message .= 'Content-Transfer-Encoding: base64' . $this->_newLine;
			$this->__message .= 'Content-Disposition: attachment; filename="' . basename($file) . '"' . $this->_newLine . $this->_newLine;
			$this->__message .= $data . $this->_newLine . $this->_newLine;
		}
	}
/**
 * Find the specified attachment in the list of file paths
 *
 * @param string $attachment Attachment file name to find
 * @return string Path to located file
 * @access private
 */
	function __findFiles($attachment) {
		if (file_exists($attachment)) {
			return $attachment;
		}
		foreach ($this->filePaths as $path) {
			if (file_exists($path . DS . $attachment)) {
				$file = $path . DS . $attachment;
				return $file;
			}
		}
		return null;
	}
/**
 * Wrap the message using EmailComponet::$_lineLength
 *
 * @param string $message Message to wrap
 * @return string Wrapped message
 * @access private
 */
	function __wrap($message) {
		$message = $this->__strip($message, true);
		$message = str_replace(array("\r\n","\r"), "\n", $message);
		$lines = explode("\n", $message);
		$formatted = null;

		foreach ($lines as $line) {
			if(substr($line, 0, 1) == '.') {
				$line = '.' . $line;
			}
			$formatted .= wordwrap($line, $this->_lineLength, $this->_newLine, true);
			$formatted .= $this->_newLine;
		}
		return $formatted;
	}
/**
 * Encode the specified string using the current charset
 *
 * @param string $subject String to encode
 * @return string Encoded string
 * @access private
 */
	function __encode($subject) {
		$subject = $this->__strip($subject);

		if (low($this->charset) !== 'iso-8859-15') {
			$start = "=?" . $this->charset . "?B?";
			$end = "?=";
			$spacer = $end . "\n " . $start;

			$length = 75 - strlen($start) - strlen($end);
			$length = $length - ($length % 4);

			$subject = base64_encode($subject);
			$subject = chunk_split($subject, $length, $spacer);
			$spacer = preg_quote($spacer);
			$subject = preg_replace("/" . $spacer . "$/", "", $subject);
			$subject = $start . $subject . $end;
		}
		return $subject;
	}
/**
 * Format a string as an email address
 *
 * @param string $string String representing an email address
 * @return string Email address suitable for email headers or smtp pipe
 * @access private
 */
	function __formatAddress($string, $smtp = false) {
		if (strpos($string, '<') !== false) {
			$value = explode('<', $string);
			if ($smtp) {
				$string = '<' . $value[1];
			} else {
				$string = $this->__encode($value[0]) . ' <' . $value[1];
			}
		}
		return $this->__strip($string);
	}
/**
 * Remove certain elements (such as bcc:, to:, %0a) from given value
 *
 * @param string $value Value to strip
 * @param boolean $message Set to true to indicate main message content
 * @return string Stripped value
 * @access private
 */
	function __strip($value, $message = false) {
		$search = array(
			'/%0a/i', '/%0d/i', '/Content-Type\:/i', '/charset\=/i', '/mime-version\:/i',
			'/multipart\/mixed/i', '/bcc\:.*/i','/to\:.*/i','/cc\:.*/i', '/Content-Transfer-Encoding\:/i',
			'/\\r/i', '/\\n/i'
		);

		if ($message === true) {
			$search = array_slice($search, 0, -2);
		}

		foreach ($search as $key) {
			while (preg_match($key, $value)) {
				$value = preg_replace($key, '', $value);
			}
		}
		return preg_replace($search, '', $value);
	}
/**
 * Wrapper for PHP mail function used for sending out emails
 *
 * @return bool Success
 * @access private
 */
	function __mail() {
		if (ini_get('safe_mode')) {
			return @mail($this->to, $this->__encode($this->subject), $this->__message, $this->__header);
		}
		return @mail($this->to, $this->__encode($this->subject), $this->__message, $this->__header, $this->additionalParams);
	}
/**
 * Sends out email via SMTP
 *
 * @return bool Success
 * @access private
 */
	function __smtp() {
		App::import('Core', array('Socket'));
		
		$this->__smtpConnection =& new CakeSocket(array_merge(array('protocol'=>'smtp'), $this->smtpOptions));
		
		if (!$this->__smtpConnection->connect()) {
			$this->smtpError = $this->__smtpConnection->lastError();
			return false;
		} elseif (!$this->__smtpSend(null, '220')) {
			return false;
		}
		
		if (!$this->__smtpSend('HELO cake', '250')) {
			return false;
		}

		if (isset($this->smtpOptions['username']) && isset($this->smtpOptions['password'])) {
			if (!$this->__smtpSend('AUTH LOGIN', '334')) {
				return false;
			}
			if (!$this->__smtpSend(base64_encode($this->smtpOptions['username']), '334')) {
				return false;
			}
			if (!$this->__smtpSend(base64_encode($this->smtpOptions['password']), '235')) {
				return false;
			}
		}

		if (!$this->__smtpSend('MAIL FROM: ' . $this->__formatAddress($this->from, true))) {
			return false;
		}

		if (!$this->__smtpSend('RCPT TO: ' . $this->__formatAddress($this->to, true))) {
			return false;
		}

		foreach ($this->cc as $cc) {
			if (!$this->__smtpSend('RCPT TO: ' . $this->__formatAddress($cc, true))) {
				return false;
			}
		}
		foreach ($this->bcc as $bcc) {
			if (!$this->__smtpSend('RCPT TO: ' . $this->__formatAddress($bcc, true))) {
				return false;
			}
		}

		if (!$this->__smtpSend('DATA', '354')) {
			return false;
		}

		if (!$this->__smtpSend($this->__header . "\r\n\r\n" . $this->__message . "\r\n\r\n\r\n.")) {
			return false;
		}
		$this->__smtpSend('QUIT', false);
		
		$this->__smtpConnection->disconnect();
		return true;
	}
/**
 * Private method for sending data to SMTP connection
 *
 * @param string $data data to be sent to SMTP server
 * @param mixed $checkCode code to check for in server response, false to skip
 * @return bool Success
 * @access private
 */
	function __smtpSend($data, $checkCode = '250') {
		if (!is_null($data)) {
			$this->__smtpConnection->write($data . "\r\n");
		}
		if ($checkCode !== false) {
			$response = '';

			while ($str = $this->__smtpConnection->read()) {
				$response .= $str;

				if ($str[3] == ' ') {
					break;
				}
			}
			
			if (stristr($response, $checkCode) === false) {
				$this->smtpError = $response;
				return false;
			}
		}
		return true;
	}
/**
 * Set as controller flash message a debug message showing current settings in component
 *
 * @return boolean Success
 * @access private
 */
	function __debug() {
		$nl = $this->_newLine;
		$fm = '<pre>';

		if ($this->delivery == 'smtp') {
			$fm .= sprintf('%s %s%s', 'Host:', $this->smtpOptions['host'], $nl);
			$fm .= sprintf('%s %s%s', 'Port:', $this->smtpOptions['port'], $nl);
			$fm .= sprintf('%s %s%s', 'Timeout:', $this->smtpOptions['timeout'], $nl);
		}
		$fm .= sprintf('%s %s%s', 'To:', $this->to, $nl);
		$fm .= sprintf('%s %s%s', 'From:', $this->from, $nl);
		$fm .= sprintf('%s %s%s', 'Subject:', $this->subject, $nl);
		$fm .= sprintf('%s%3$s%3$s%s', 'Header:', $this->__header, $nl);
		$fm .= sprintf('%s%3$s%3$s%s', 'Parameters:', $this->additionalParams, $nl);
		$fm .= sprintf('%s%3$s%3$s%s', 'Message:', $this->__message, $nl);
		$fm .= '</pre>';

		$this->Controller->Session->setFlash($fm, 'default', null, 'email');
		return true;
	}
}
?>