<?php
/**
 * Email Component
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.libs.controller.components
 * @since         CakePHP(tm) v 1.2.0.3467
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Component', 'Controller');
App::uses('Multibyte', 'I18n');

/**
 * EmailComponent
 *
 * This component is used for handling Internet Message Format based
 * based on the standard outlined in http://www.rfc-editor.org/rfc/rfc2822.txt
 *
 * @package       cake.libs.controller.components
 * @link http://book.cakephp.org/view/1283/Email
 * @deprecated
 * @see CakeEmail Lib
 */
class EmailComponent extends Component {

/**
 * Recipient of the email
 *
 * @var string
 * @access public
 */
	public $to = null;

/**
 * The mail which the email is sent from
 *
 * @var string
 * @access public
 */
	public $from = null;

/**
 * The email the recipient will reply to
 *
 * @var string
 * @access public
 */
	public $replyTo = null;

/**
 * The read receipt email
 *
 * @var string
 * @access public
 */
	public $readReceipt = null;

/**
 * The mail that will be used in case of any errors like
 * - Remote mailserver down
 * - Remote user has exceeded his quota
 * - Unknown user
 *
 * @var string
 * @access public
 */
	public $return = null;

/**
 * Carbon Copy
 *
 * List of email's that should receive a copy of the email.
 * The Recipient WILL be able to see this list
 *
 * @var array
 * @access public
 */
	public $cc = array();

/**
 * Blind Carbon Copy
 *
 * List of email's that should receive a copy of the email.
 * The Recipient WILL NOT be able to see this list
 *
 * @var array
 * @access public
 */
	public $bcc = array();

/**
 * The date to put in the Date: header.  This should be a date
 * conformant with the RFC2822 standard.  Leave null, to have
 * today's date generated.
 *
 * @var string
 */
	var $date = null;

/**
 * The subject of the email
 *
 * @var string
 * @access public
 */
	public $subject = null;

/**
 * Associative array of a user defined headers
 * Keys will be prefixed 'X-' as per RFC2822 Section 4.7.5
 *
 * @var array
 * @access public
 */
	public $headers = array();

/**
 * List of additional headers
 *
 * These will NOT be used if you are using safemode and mail()
 *
 * @var string
 * @access public
 */
	public $additionalParams = null;

/**
 * Layout for the View
 *
 * @var string
 * @access public
 */
	public $layout = 'default';

/**
 * Template for the view
 *
 * @var string
 * @access public
 */
	public $template = null;

/**
 * as per RFC2822 Section 2.1.1
 *
 * @var integer
 * @access public
 */
	public $lineLength = 70;

/**
 * Line feed character(s) to be used when sending using mail() function
 * By default PHP_EOL is used.
 * RFC2822 requires it to be CRLF but some Unix
 * mail transfer agents replace LF by CRLF automatically
 * (which leads to doubling CR if CRLF is used).
 *
 * @var string
 * @access public
 */
	var $lineFeed = PHP_EOL;

/**
 * @deprecated see lineLength
 */
	protected $_lineLength = null;

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
	public $sendAs = 'text';

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
	public $delivery = 'mail';

/**
 * charset the email is sent in
 *
 * @var string
 * @access public
 */
	public $charset = 'utf-8';

/**
 * List of files that should be attached to the email.
 *
 * Can be both absolute and relative paths
 *
 * @var array
 * @access public
 */
	public $attachments = array();

/**
 * What mailer should EmailComponent identify itself as
 *
 * @var string
 * @access public
 */
	public $xMailer = 'CakePHP Email Component';

/**
 * The list of paths to search if an attachment isnt absolute
 *
 * @var array
 * @access public
 */
	public $filePaths = array();

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
	public $smtpOptions = array();

/**
 * Contains the rendered plain text message if one was sent.
 *
 * @var string
 * @access public
 */
	public $textMessage = null;

/**
 * Contains the rendered HTML message if one was sent.
 *
 * @var string
 * @access public
 */
	public $htmlMessage = null;

/**
 * Whether to generate a Message-ID header for the
 * e-mail. True to generate a Message-ID, False to let
 * it be handled by sendmail (or similar) or a string
 * to completely override the Message-ID.
 *
 * If you are sending Email from a shell, be sure to set this value.  As you
 * could encounter delivery issues if you do not.
 *
 * @var mixed
 * @access public
 */
	public $messageId = true;

/**
 * Temporary store of message header lines
 *
 * @var array
 * @access protected
 */
	protected $_header = array();

/**
 * If set, boundary to use for multipart mime messages
 *
 * @var string
 * @access protected
 */
	protected $_boundary = null;

/**
 * Temporary store of message lines
 *
 * @var array
 * @access protected
 */
	protected $_message = array();

/**
 * Constructor
 *
 * @param ComponentCollection $collection A ComponentCollection this component can use to lazy load its components
 * @param array $settings Array of configuration settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$this->Controller = $collection->getController();
		parent::__construct($collection, $settings);
	}

/**
 * Initialize component
 *
 * @param object $controller Instantiating controller
 */
	public function initialize($controller) {
		if (Configure::read('App.encoding') !== null) {
			$this->charset = Configure::read('App.encoding');
		}
	}

/**
 * Startup component
 *
 * @param object $controller Instantiating controller
 */
	public function startup($controller) {}

/**
 * Send an email using the specified content, template and layout
 *
 * @param mixed $content Either an array of text lines, or a string with contents
 *  If you are rendering a template this variable will be sent to the templates as `$content`
 * @param string $template Template to use when sending email
 * @param string $layout Layout to use to enclose email body
 * @return boolean Success
 */
	public function send($content = null, $template = null, $layout = null) {
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

		if ($this->sendAs === 'text') {
			$message = $this->_wrap($content);
		} else {
			$message = $this->_wrap($content, 998);
		}

		if ($this->template === null) {
			$message = $this->_formatMessage($message);
		} else {
			$message = $this->_render($message);
		}

		$message[] = '';
		$this->_message = $message;

		if (!empty($this->attachments)) {
			$this->_attachFiles();
		}

		if (!is_null($this->_boundary)) {
			$this->_message[] = '';
			$this->_message[] = '--' . $this->_boundary . '--';
			$this->_message[] = '';
		}


		$_method = '_' . $this->delivery;
		//$sent = $this->$_method();
		$sent = true;

		$this->_header = array();
		$this->_message = array();

		return $sent;
	}

/**
 * Reset all EmailComponent internal variables to be able to send out a new email.
 *
 * @link http://book.cakephp.org/view/1285/Sending-Multiple-Emails-in-a-loop
 */
	public function reset() {
		$this->template = null;
		$this->to = array();
		$this->from = null;
		$this->replyTo = null;
		$this->return = null;
		$this->cc = array();
		$this->bcc = array();
		$this->subject = null;
		$this->additionalParams = null;
		$this->date = null;
		$this->attachments = array();
		$this->htmlMessage = null;
		$this->textMessage = null;
		$this->messageId = true;
		$this->_header = array();
		$this->_boundary = null;
		$this->_message = array();
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
			list($plugin, $viewClass) = pluginSplit($viewClass, true);
			$viewClass = $viewClass . 'View';
			App::uses($viewClass, $plugin . 'View');
		}

		$View = new $viewClass($this->Controller);
		$View->layout = $this->layout;
		$msg = array();

		$content = implode("\n", $content);

		if ($this->sendAs === 'both') {
			$htmlContent = $content;
			if (!empty($this->attachments)) {
				$msg[] = '--' . $this->_boundary;
				$msg[] = 'Content-Type: multipart/alternative; boundary="alt-' . $this->_boundary . '"';
				$msg[] = '';
			}
			$msg[] = '--alt-' . $this->_boundary;
			$msg[] = 'Content-Type: text/plain; charset=' . $this->charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$content = $View->element('email' . DS . 'text' . DS . $this->template, array('content' => $content), true);
			$View->layoutPath = 'email' . DS . 'text';
			$content = explode("\n", $this->textMessage = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($content)));

			$msg = array_merge($msg, $content);

			$msg[] = '';
			$msg[] = '--alt-' . $this->_boundary;
			$msg[] = 'Content-Type: text/html; charset=' . $this->charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$htmlContent = $View->element('email' . DS . 'html' . DS . $this->template, array('content' => $htmlContent), true);
			$View->layoutPath = 'email' . DS . 'html';
			$htmlContent = explode("\n", $this->htmlMessage = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($htmlContent)));
			$msg = array_merge($msg, $htmlContent);
			$msg[] = '';
			$msg[] = '--alt-' . $this->_boundary . '--';
			$msg[] = '';

			ClassRegistry::removeObject('view');
			return $msg;
		}

		if (!empty($this->attachments)) {
			if ($this->sendAs === 'html') {
				$msg[] = '';
				$msg[] = '--' . $this->_boundary;
				$msg[] = 'Content-Type: text/html; charset=' . $this->charset;
				$msg[] = 'Content-Transfer-Encoding: 7bit';
				$msg[] = '';
			} else {
				$msg[] = '--' . $this->_boundary;
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
		ClassRegistry::removeObject('view');

		return $msg;
	}

/**
 * Create unique boundary identifier
 *
 * @access private
 */
	function _createboundary() {
		$this->_boundary = md5(uniqid(time()));
	}

/**
 * Sets headers for the message
 *
 * @access public
 * @param array Associative array containing headers to be set.
 */
	function header($headers) {
		foreach ($headers as $header => $value) {
			$this->_header[] = sprintf('%s: %s', trim($header), trim($value));
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
			$headers['To'] = implode(', ', array_map(array($this, '_formatAddress'), (array)$this->to));
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
			$headers['Cc'] = implode(', ', array_map(array($this, '_formatAddress'), (array)$this->cc));
		}

		if (!empty($this->bcc) && $this->delivery != 'smtp') {
			$headers['Bcc'] = implode(', ', array_map(array($this, '_formatAddress'), (array)$this->bcc));
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

		$date = $this->date;
		if ($date == false) {
			$date = date(DATE_RFC2822);
		}
		$headers['Date'] = $date;

		$headers['X-Mailer'] = $this->xMailer;

		if (!empty($this->headers)) {
			foreach ($this->headers as $key => $val) {
				$headers['X-' . $key] = $val;
			}
		}

		if (!empty($this->attachments)) {
			$this->_createBoundary();
			$headers['MIME-Version'] = '1.0';
			$headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->_boundary . '"';
			$headers[] = 'This part of the E-mail should never be seen. If';
			$headers[] = 'you are reading this, consider upgrading your e-mail';
			$headers[] = 'client to a MIME-compatible client.';
		} elseif ($this->sendAs === 'text') {
			$headers['Content-Type'] = 'text/plain; charset=' . $this->charset;
		} elseif ($this->sendAs === 'html') {
			$headers['Content-Type'] = 'text/html; charset=' . $this->charset;
		} elseif ($this->sendAs === 'both') {
			$headers['Content-Type'] = 'multipart/alternative; boundary="alt-' . $this->_boundary . '"';
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
			$prefix = array('--' . $this->_boundary);
			if ($this->sendAs === 'text') {
				$prefix[] = 'Content-Type: text/plain; charset=' . $this->charset;
			} elseif ($this->sendAs === 'html') {
				$prefix[] = 'Content-Type: text/html; charset=' . $this->charset;
			} elseif ($this->sendAs === 'both') {
				$prefix[] = 'Content-Type: multipart/alternative; boundary="alt-' . $this->_boundary . '"';
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

			$this->_message[] = '--' . $this->_boundary;
			$this->_message[] = 'Content-Type: application/octet-stream';
			$this->_message[] = 'Content-Transfer-Encoding: base64';
			$this->_message[] = 'Content-Disposition: attachment; filename="' . basename($filename) . '"';
			$this->_message[] = '';
			$this->_message[] = $data;
			$this->_message[] = '';
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
 * @param integer $lineLength Max length of line
 * @return array Wrapped message
 * @access protected
 */
	function _wrap($message, $lineLength = null) {
		$message = $this->_strip($message, true);
		$message = str_replace(array("\r\n","\r"), "\n", $message);
		$lines = explode("\n", $message);
		$formatted = array();

		if ($this->_lineLength !== null) {
			trigger_error(__d('cake_dev', '_lineLength cannot be accessed please use lineLength'), E_USER_WARNING);
			$this->lineLength = $this->_lineLength;
		}

		if (!$lineLength) {
			$lineLength = $this->lineLength;
		}

		foreach ($lines as $line) {
			if (substr($line, 0, 1) == '.') {
				$line = '.' . $line;
			}
			$formatted = array_merge($formatted, explode("\n", wordwrap($line, $lineLength, "\n", true)));
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
		$internalEncoding = function_exists('mb_internal_encoding');
		if ($internalEncoding) {
			$restore = mb_internal_encoding();
			mb_internal_encoding($this->charset);
		}
		$return = mb_encode_mimeheader($subject, $this->charset, 'B', $nl);
		if ($internalEncoding) {
			mb_internal_encoding($restore);
		}
		return $return;
	}

/**
 * Format a string as an email address
 *
 * @param string $string String representing an email address
 * @return string Email address suitable for email headers or smtp pipe
 * @access private
 */
	function _formatAddress($string, $smtp = false) {
		$hasAlias = preg_match('/((.*))?\s?<(.+)>/', $string, $matches);
		if ($smtp && $hasAlias) {
			return $this->_strip('<' .  $matches[3] . '>');
		} elseif ($smtp) {
			return $this->_strip('<' . $string . '>');
		}

		if ($hasAlias && !empty($matches[2])) {
			return $this->_encode($matches[2]) . $this->_strip(' <' . $matches[3] . '>');
		}
		return $this->_strip($string);
	}

/**
 * Remove certain elements (such as bcc:, to:, %0a) from given value.
 * Helps prevent header injection / mainipulation on user content.
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

}
