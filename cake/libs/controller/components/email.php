<?php
/**
 * Short description for file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.controller.components
 * @since         CakePHP(tm) v 1.2.0.3467
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'Multibyte');

/**
 * EmailComponent
 *
 * This component is used for handling Internet Message Format based
 * based on the standard outlined in http://www.rfc-editor.org/rfc/rfc2822.txt
 *
 * @package       cake
 * @subpackage    cake.cake.libs.controller.components
 * @link http://book.cakephp.org/view/1283/Email
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
 * Keys will be prefixed 'X-' as per RFC2822 Section 4.7.5
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
 * as per RFC2822 Section 2.1.1
 *
 * @var integer
 * @access public
 */
	var $lineLength = 70;

/**
 * @deprecated see lineLength
 */
	var $_lineLength = null;

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
 * - client
 *
 * @var array
 * @access public
 * @link http://book.cakephp.org/view/1290/Sending-A-Message-Using-SMTP
 */
	var $smtpOptions = array(
		'port'=> 25, 'host' => 'localhost', 'timeout' => 30
	);

/**
 * Placeholder for any errors that might happen with the
 * smtp mail methods
 *
 * @var string
 * @access public
 */
	var $smtpError = null;

/**
 * Contains the rendered plain text message if one was sent.
 *
 * @var string
 * @access public
 */
	var $textMessage = null;

/**
 * Contains the rendered HTML message if one was sent.
 *
 * @var string
 * @access public
 */
	var $htmlMessage = null;

/**
 * Whether to generate a Message-ID header for the
 * e-mail. True to generate a Message-ID, False to let
 * it be handled by sendmail (or similar) or a string
 * to completely override the Message-ID.
 *
 * @var mixed
 * @access public
 */
	var $messageId = true;

/**
 * Temporary store of message header lines
 *
 * @var array
 * @access private
 */
	var $__header = array();

/**
 * If set, boundary to use for multipart mime messages
 *
 * @var string
 * @access private
 */
	var $__boundary = null;

/**
 * Temporary store of message lines
 *
 * @var array
 * @access private
 */
	var $__message = array();

/**
 * Variable that holds SMTP connection
 *
 * @var resource
 * @access private
 */
	var $__smtpConnection = null;

/**
 * Initialize component
 *
 * @param object $controller Instantiating controller
 * @access public
 */
	function initialize(&$controller, $settings = array()) {
		$this->Controller =& $controller;
		if (Configure::read('App.encoding') !== null) {
			$this->charset = Configure::read('App.encoding');
		}
		$this->_set($settings);
	}

/**
 * Startup component
 *
 * @param object $controller Instantiating controller
 * @access public
 */
	function startup(&$controller) {}

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
		$this->_createHeader();

		if ($template) {
			$this->template = $template;
		}

		if ($layout) {
			$this->layout = $layout;
		}

		if (is_array($content)) {
			$content = implode("\n", $content) . "\n";
		}

		$this->htmlMessage = $this->textMessage = null;
		if ($content) {
			if ($this->sendAs === 'html') {
				$this->htmlMessage = $content;
			} elseif ($this->sendAs === 'text') {
				$this->textMessage = $content;
			} else {
				$this->htmlMessage = $this->textMessage = $content;
			}
		}

		$message = $this->_wrap($content);

		if ($this->template === null) {
			$message = $this->_formatMessage($message);
		} else {
			$message = $this->_render($message);
		}

		$message[] = '';
		$this->__message = $message;

		if (!empty($this->attachments)) {
			$this->_attachFiles();
		}

		if (!is_null($this->__boundary)) {
			$this->__message[] = '';
			$this->__message[] = '--' . $this->__boundary . '--';
			$this->__message[] = '';
		}


		$_method = '_' . $this->delivery;
		$sent = $this->$_method();

		$this->__header = array();
		$this->__message = array();

		return $sent;
	}

/**
 * Reset all EmailComponent internal variables to be able to send out a new email.
 *
 * @access public
 * @link http://book.cakephp.org/view/1285/Sending-Multiple-Emails-in-a-loop
 */
	function reset() {
		$this->template = null;
		$this->to = array();
		$this->from = null;
		$this->replyTo = null;
		$this->return = null;
		$this->cc = array();
		$this->bcc = array();
		$this->subject = null;
		$this->additionalParams = null;
		$this->smtpError = null;
		$this->attachments = array();
		$this->htmlMessage = null;
		$this->textMessage = null;
		$this->messageId = true;
		$this->__header = array();
		$this->__boundary = null;
		$this->__message = array();
	}

/**
 * Render the contents using the current layout and template.
 *
 * @param string $content Content to render
 * @return array Email ready to be sent
 * @access private
 */
	function _render($content) {
		$viewClass = $this->Controller->view;

		if ($viewClass != 'View') {
			list($plugin, $viewClass) = pluginSplit($viewClass);
			$viewClass = $viewClass . 'View';
			App::import('View', $this->Controller->view);
		}

		$View = new $viewClass($this->Controller, false);
		$View->layout = $this->layout;
		$msg = array();

		$content = implode("\n", $content);

		if ($this->sendAs === 'both') {
			$htmlContent = $content;
			if (!empty($this->attachments)) {
				$msg[] = '--' . $this->__boundary;
				$msg[] = 'Content-Type: multipart/alternative; boundary="alt-' . $this->__boundary . '"';
				$msg[] = '';
			}
			$msg[] = '--alt-' . $this->__boundary;
			$msg[] = 'Content-Type: text/plain; charset=' . $this->charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$content = $View->element('email' . DS . 'text' . DS . $this->template, array('content' => $content), true);
			$View->layoutPath = 'email' . DS . 'text';
			$content = explode("\n", $this->textMessage = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($content)));

			$msg = array_merge($msg, $content);

			$msg[] = '';
			$msg[] = '--alt-' . $this->__boundary;
			$msg[] = 'Content-Type: text/html; charset=' . $this->charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$htmlContent = $View->element('email' . DS . 'html' . DS . $this->template, array('content' => $htmlContent), true);
			$View->layoutPath = 'email' . DS . 'html';
			$htmlContent = explode("\n", $this->htmlMessage = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($htmlContent)));
			$msg = array_merge($msg, $htmlContent);
			$msg[] = '';
			$msg[] = '--alt-' . $this->__boundary . '--';
			$msg[] = '';

			return $msg;
		}

		if (!empty($this->attachments)) {
			if ($this->sendAs === 'html') {
				$msg[] = '';
				$msg[] = '--' . $this->__boundary;
				$msg[] = 'Content-Type: text/html; charset=' . $this->charset;
				$msg[] = 'Content-Transfer-Encoding: 7bit';
				$msg[] = '';
			} else {
				$msg[] = '--' . $this->__boundary;
				$msg[] = 'Content-Type: text/plain; charset=' . $this->charset;
				$msg[] = 'Content-Transfer-Encoding: 7bit';
				$msg[] = '';
			}
		}

		$content = $View->element('email' . DS . $this->sendAs . DS . $this->template, array('content' => $content), true);
		$View->layoutPath = 'email' . DS . $this->sendAs;
		$content = explode("\n", $rendered = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($content)));

		if ($this->sendAs === 'html') {
			$this->htmlMessage = $rendered;
		} else {
			$this->textMessage = $rendered;
		}

		$msg = array_merge($msg, $content);

		return $msg;
	}

/**
 * Create unique boundary identifier
 *
 * @access private
 */
	function _createboundary() {
		$this->__boundary = md5(uniqid(time()));
	}

/**
 * Sets headers for the message
 *
 * @access public
 * @param array Associative array containing headers to be set.
 */
	function header($headers) {
		foreach ($headers as $header => $value) {
			$this->__header[] = sprintf('%s: %s', trim($header), trim($value));
		}
	}
/**
 * Create emails headers including (but not limited to) from email address, reply to,
 * bcc and cc.
 *
 * @access private
 */
	function _createHeader() {
        $headers = array();

		if ($this->delivery == 'smtp') {
			if (is_array($this->to)) {
				$headers['To'] = implode(', ', array_map(array($this, '_formatAddress'), $this->to));
			} else {
				$headers['To'] = $this->_formatAddress($this->to);
			}
		}
		$headers['From'] = $this->_formatAddress($this->from);

		if (!empty($this->replyTo)) {
			$headers['Reply-To'] = $this->_formatAddress($this->replyTo);
		}
		if (!empty($this->return)) {
			$headers['Return-Path'] = $this->_formatAddress($this->return);
		}
		if (!empty($this->readReceipt)) {
			$headers['Disposition-Notification-To'] = $this->_formatAddress($this->readReceipt);
		}

		if (!empty($this->cc)) {
			$headers['cc'] = implode(', ', array_map(array($this, '_formatAddress'), $this->cc));
		}

		if (!empty($this->bcc) && $this->delivery != 'smtp') {
			$headers['Bcc'] = implode(', ', array_map(array($this, '_formatAddress'), $this->bcc));
		}
		if ($this->delivery == 'smtp') {
			$headers['Subject'] = $this->_encode($this->subject);
		}

		if ($this->messageId !== false) {
			if ($this->messageId === true) {
				$headers['Message-ID'] = '<' . String::UUID() . '@' . env('HTTP_HOST') . '>';
			} else {
				$headers['Message-ID'] = $this->messageId;
			}
		}

		$headers['X-Mailer'] = $this->xMailer;

		if (!empty($this->headers)) {
			foreach ($this->headers as $key => $val) {
				$headers['X-' . $key] = $val;
			}
		}

		if (!empty($this->attachments)) {
			$this->_createBoundary();
			$headers['MIME-Version'] = '1.0';
			$headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->__boundary . '"';
			$headers[] = 'This part of the E-mail should never be seen. If';
			$headers[] = 'you are reading this, consider upgrading your e-mail';
			$headers[] = 'client to a MIME-compatible client.';
		} elseif ($this->sendAs === 'text') {
			$headers['Content-Type'] = 'text/plain; charset=' . $this->charset;
		} elseif ($this->sendAs === 'html') {
			$headers['Content-Type'] = 'text/html; charset=' . $this->charset;
		} elseif ($this->sendAs === 'both') {
			$headers['Content-Type'] = 'multipart/alternative; boundary="alt-' . $this->__boundary . '"';
		}

		$headers['Content-Transfer-Encoding'] = '7bit';

        $this->header($headers);
	}

/**
 * Format the message by seeing if it has attachments.
 *
 * @param string $message Message to format
 * @access private
 */
	function _formatMessage($message) {
		if (!empty($this->attachments)) {
			$prefix = array('--' . $this->__boundary);
			if ($this->sendAs === 'text') {
				$prefix[] = 'Content-Type: text/plain; charset=' . $this->charset;
			} elseif ($this->sendAs === 'html') {
				$prefix[] = 'Content-Type: text/html; charset=' . $this->charset;
			} elseif ($this->sendAs === 'both') {
				$prefix[] = 'Content-Type: multipart/alternative; boundary="alt-' . $this->__boundary . '"';
			}
			$prefix[] = 'Content-Transfer-Encoding: 7bit';
			$prefix[] = '';
			$message = array_merge($prefix, $message);
		}
		return $message;
	}

/**
 * Attach files by adding file contents inside boundaries.
 *
 * @access private
 * @TODO: modify to use the core File class?
 */
	function _attachFiles() {
		$files = array();
		foreach ($this->attachments as $filename => $attachment) {
			$file = $this->_findFiles($attachment);
			if (!empty($file)) {
				if (is_int($filename)) {
					$filename = basename($file);
				}
				$files[$filename] = $file;
			}
		}

		foreach ($files as $filename => $file) {
			$handle = fopen($file, 'rb');
			$data = fread($handle, filesize($file));
			$data = chunk_split(base64_encode($data)) ;
			fclose($handle);

			$this->__message[] = '--' . $this->__boundary;
			$this->__message[] = 'Content-Type: application/octet-stream';
			$this->__message[] = 'Content-Transfer-Encoding: base64';
			$this->__message[] = 'Content-Disposition: attachment; filename="' . basename($filename) . '"';
			$this->__message[] = '';
			$this->__message[] = $data;
			$this->__message[] = '';
		}
	}

/**
 * Find the specified attachment in the list of file paths
 *
 * @param string $attachment Attachment file name to find
 * @return string Path to located file
 * @access private
 */
	function _findFiles($attachment) {
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
 * Wrap the message using EmailComponent::$lineLength
 *
 * @param string $message Message to wrap
 * @return array Wrapped message
 * @access private
 */
	function _wrap($message) {
		$message = $this->_strip($message, true);
		$message = str_replace(array("\r\n","\r"), "\n", $message);
		$lines = explode("\n", $message);
		$formatted = array();

		if ($this->_lineLength !== null) {
			trigger_error(__('_lineLength cannot be accessed please use lineLength', true), E_USER_WARNING);
			$this->lineLength = $this->_lineLength;
		}

		foreach ($lines as $line) {
			if (substr($line, 0, 1) == '.') {
				$line = '.' . $line;
			}
			$formatted = array_merge($formatted, explode("\n", wordwrap($line, $this->lineLength, "\n", true)));
		}
		$formatted[] = '';
		return $formatted;
	}

/**
 * Encode the specified string using the current charset
 *
 * @param string $subject String to encode
 * @return string Encoded string
 * @access private
 */
	function _encode($subject) {
		$subject = $this->_strip($subject);

		$nl = "\r\n";
		if ($this->delivery == 'mail') {
			$nl = '';
		}
		return mb_encode_mimeheader($subject, $this->charset, 'B', $nl);
	}

/**
 * Format a string as an email address
 *
 * @param string $string String representing an email address
 * @return string Email address suitable for email headers or smtp pipe
 * @access private
 */
	function _formatAddress($string, $smtp = false) {
		if (strpos($string, '<') !== false) {
			$value = explode('<', $string);
			if ($smtp) {
				$string = '<' . $value[1];
			} else {
				$string = $this->_encode($value[0]) . ' <' . $value[1];
			}
		}
		return $this->_strip($string);
	}

/**
 * Remove certain elements (such as bcc:, to:, %0a) from given value
 *
 * @param string $value Value to strip
 * @param boolean $message Set to true to indicate main message content
 * @return string Stripped value
 * @access private
 */
	function _strip($value, $message = false) {
		$search  = '%0a|%0d|Content-(?:Type|Transfer-Encoding)\:';
		$search .= '|charset\=|mime-version\:|multipart/mixed|(?:[^a-z]to|b?cc)\:.*';

		if ($message !== true) {
			$search .= '|\r|\n';
		}
		$search = '#(?:' . $search . ')#i';
		while (preg_match($search, $value)) {
			$value = preg_replace($search, '', $value);
		}
		return $value;
	}

/**
 * Wrapper for PHP mail function used for sending out emails
 *
 * @return bool Success
 * @access private
 */
	function _mail() {
		$header = implode("\n", $this->__header);
		$message = implode("\n", $this->__message);
		if (is_array($this->to)) {
			$to = implode(', ', array_map(array($this, '_formatAddress'), $this->to));
		} else {
			$to = $this->to;
		}
		if (ini_get('safe_mode')) {
			return @mail($to, $this->_encode($this->subject), $message, $header);
		}
		return @mail($to, $this->_encode($this->subject), $message, $header, $this->additionalParams);
	}

/**
 * Sends out email via SMTP
 *
 * @return bool Success
 * @access private
 */
	function _smtp() {
		App::import('Core', array('CakeSocket'));

		$this->__smtpConnection =& new CakeSocket(array_merge(array('protocol'=>'smtp'), $this->smtpOptions));

		if (!$this->__smtpConnection->connect()) {
			$this->smtpError = $this->__smtpConnection->lastError();
			return false;
		} elseif (!$this->_smtpSend(null, '220')) {
			return false;
		}

		$httpHost = env('HTTP_HOST');

		if (isset($this->smtpOptions['client'])) {
			$host = $this->smtpOptions['client'];
		} elseif (!empty($httpHost)) {
			$host = $httpHost;
		} else {
			$host = 'localhost';
		}

		if (!$this->_smtpSend("HELO {$host}", '250')) {
			return false;
		}

		if (isset($this->smtpOptions['username']) && isset($this->smtpOptions['password'])) {
			$authRequired = $this->_smtpSend('AUTH LOGIN', '334|503');
			if ($authRequired == '334') {
				if (!$this->_smtpSend(base64_encode($this->smtpOptions['username']), '334')) {
					return false;
				}
				if (!$this->_smtpSend(base64_encode($this->smtpOptions['password']), '235')) {
					return false;
				}
			} elseif ($authRequired != '503') {
				return false;
			}
		}

		if (!$this->_smtpSend('MAIL FROM: ' . $this->_formatAddress($this->from, true))) {
			return false;
		}

		if (!is_array($this->to)) {
			$tos = array($this->to);
		} else {
			$tos = $this->to;
		}
		foreach ($tos as $to) {
			if (!$this->_smtpSend('RCPT TO: ' . $this->_formatAddress($to, true))) {
				return false;
			}
		}

		foreach ($this->cc as $cc) {
			if (!$this->_smtpSend('RCPT TO: ' . $this->_formatAddress($cc, true))) {
				return false;
			}
		}
		foreach ($this->bcc as $bcc) {
			if (!$this->_smtpSend('RCPT TO: ' . $this->_formatAddress($bcc, true))) {
				return false;
			}
		}

		if (!$this->_smtpSend('DATA', '354')) {
			return false;
		}

		$header = implode("\r\n", $this->__header);
		$message = implode("\r\n", $this->__message);
		if (!$this->_smtpSend($header . "\r\n\r\n" . $message . "\r\n\r\n\r\n.")) {
			return false;
		}
		$this->_smtpSend('QUIT', false);

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
	function _smtpSend($data, $checkCode = '250') {
		if (!is_null($data)) {
			$this->__smtpConnection->write($data . "\r\n");
		}
		if ($checkCode !== false) {
			$response = $this->__smtpConnection->read();

			if (preg_match('/^(' . $checkCode . ')/', $response, $code)) {
				return $code[0];
			}
			$this->smtpError = $response;
			return false;
		}
		return true;
	}

/**
 * Set as controller flash message a debug message showing current settings in component
 *
 * @return boolean Success
 * @access private
 */
	function _debug() {
		$nl = "\n";
		$header = implode($nl, $this->__header);
		$message = implode($nl, $this->__message);
		$fm = '<pre>';

		if (is_array($this->to)) {
			$to = implode(', ', array_map(array($this, '_formatAddress'), $this->to));
		} else {
			$to = $this->to;
		}
		if ($this->delivery == 'smtp') {
			$fm .= sprintf('%s %s%s', 'Host:', $this->smtpOptions['host'], $nl);
			$fm .= sprintf('%s %s%s', 'Port:', $this->smtpOptions['port'], $nl);
			$fm .= sprintf('%s %s%s', 'Timeout:', $this->smtpOptions['timeout'], $nl);
		}
		$fm .= sprintf('%s %s%s', 'To:', $to, $nl);
		$fm .= sprintf('%s %s%s', 'From:', $this->from, $nl);
		$fm .= sprintf('%s %s%s', 'Subject:', $this->_encode($this->subject), $nl);
		$fm .= sprintf('%s%3$s%3$s%s', 'Header:', $header, $nl);
		$fm .= sprintf('%s%3$s%3$s%s', 'Parameters:', $this->additionalParams, $nl);
		$fm .= sprintf('%s%3$s%3$s%s', 'Message:', $message, $nl);
		$fm .= '</pre>';

		if (isset($this->Controller->Session)) {
			$this->Controller->Session->setFlash($fm, 'default', null, 'email');
			return true;
		}
		return $fm;
	}

}
?>