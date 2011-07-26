<?php
/**
 * Cake E-Mail
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Network.Email
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Validation', 'Utility');
App::uses('Multibyte', 'I18n');
App::uses('AbstractTransport', 'Network/Email');
App::uses('String', 'Utility');
App::uses('View', 'View');
App::import('I18n', 'Multibyte');

/**
 * Cake e-mail class.
 *
 * This class is used for handling Internet Message Format based
 * based on the standard outlined in http://www.rfc-editor.org/rfc/rfc2822.txt
 *
 * @package       Cake.Network.Email
 */
class CakeEmail {
/**
 * Default X-Mailer
 *
 * @constant EMAIL_CLIENT
 */
	const EMAIL_CLIENT = 'CakePHP Email';

/**
 * Line length - no should more - RFC 2822 - 2.1.1
 *
 * @constant LINE_LENGTH_SHOULD
 */
	const LINE_LENGTH_SHOULD = 78;

/**
 * Line length - no must more - RFC 2822 - 2.1.1
 *
 * @constant LINE_LENGTH_MUST
 */
	const LINE_LENGTH_MUST = 998;

/**
 * Type of message - HTML
 *
 * @constant MESSAGE_HTML
 */
	const MESSAGE_HTML = 'html';

/**
 * Type of message - TEXT
 *
 * @constant MESSAGE_TEXT
 */
	const MESSAGE_TEXT = 'text';

/**
 * Recipient of the email
 *
 * @var array
 */
	protected $_to = array();

/**
 * The mail which the email is sent from
 *
 * @var array
 */
	protected $_from = array();

/**
 * The sender email
 *
 * @var array();
 */
	protected $_sender = array();

/**
 * The email the recipient will reply to
 *
 * @var array
 */
	protected $_replyTo = array();

/**
 * The read receipt email
 *
 * @var array
 */
	protected $_readReceipt = array();

/**
 * The mail that will be used in case of any errors like
 * - Remote mailserver down
 * - Remote user has exceeded his quota
 * - Unknown user
 *
 * @var array
 */
	protected $_returnPath = array();

/**
 * Carbon Copy
 *
 * List of email's that should receive a copy of the email.
 * The Recipient WILL be able to see this list
 *
 * @var array
 */
	protected $_cc = array();

/**
 * Blind Carbon Copy
 *
 * List of email's that should receive a copy of the email.
 * The Recipient WILL NOT be able to see this list
 *
 * @var array
 */
	protected $_bcc = array();

/**
 * Message ID
 *
 * @var mixed True to generate, False to ignore, String with value
 */
	protected $_messageId = true;

/**
 * The subject of the email
 *
 * @var string
 */
	protected $_subject = '';

/**
 * Associative array of a user defined headers
 * Keys will be prefixed 'X-' as per RFC2822 Section 4.7.5
 *
 * @var array
 */
	protected $_headers = array();

/**
 * Layout for the View
 *
 * @var string
 */
	protected $_layout = 'default';

/**
 * Template for the view
 *
 * @var string
 */
	protected $_template = '';

/**
 * View for render
 *
 * @var string
 */
	protected $_viewRender = 'View';

/**
 * Vars to sent to render
 *
 * @var array
 */
	protected $_viewVars = array();

/**
 * Helpers to be used in the render
 *
 * @var array
 */
	protected $_helpers = array();

/**
 * Text message
 *
 * @var string
 */
	protected $_textMessage = '';

/**
 * Html message
 *
 * @var string
 */
	protected $_htmlMessage = '';

/**
 * Final message to send
 *
 * @var array
 */
	protected $_message = array();

/**
 * Available formats to be sent.
 *
 * @var array
 */
	protected $_emailFormatAvailable = array('text', 'html', 'both');

/**
 * What format should the email be sent in
 *
 * @var string
 */
	protected $_emailFormat = 'text';

/**
 * What method should the email be sent
 *
 * @var string
 */
	protected $_transportName = 'Mail';

/**
 * Instance of transport class
 *
 * @var object
 */
	protected $_transportClass = null;

/**
 * charset the email is sent in
 *
 * @var string
 */
	public $charset = 'utf-8';

/**
 * List of files that should be attached to the email.
 *
 * Only absolute paths
 *
 * @var array
 */
	protected $_attachments = array();

/**
 * If set, boundary to use for multipart mime messages
 *
 * @var string
 */
	protected $_boundary = null;

/**
 * Configuration to transport
 *
 * @var mixed
 */
	protected $_config = 'default';

/**
 * Constructor
 *
 */
	public function __construct() {
		$charset = Configure::read('App.encoding');
		if ($charset !== null) {
			$this->charset = $charset;
		}
	}

/**
 * From
 *
 * @param mixed $email
 * @param string $name
 * @return mixed
 * @throws SocketException
 */
	public function from($email = null, $name = null) {
		if ($email === null) {
			return $this->_from;
		}
		return $this->_setEmailSingle('_from', $email, $name, __d('cake', 'From requires only 1 email address.'));
	}

/**
 * Sender
 *
 * @param mixed $email
 * @param string $name
 * @return mixed
 * @throws SocketException
 */
	public function sender($email = null, $name = null) {
		if ($email === null) {
			return $this->_sender;
		}
		return $this->_setEmailSingle('_sender', $email, $name, __d('cake', 'Sender requires only 1 email address.'));
	}

/**
 * Reply-To
 *
 * @param mixed $email
 * @param string $name
 * @return mixed
 * @throws SocketException
 */
	public function replyTo($email = null, $name = null) {
		if ($email === null) {
			return $this->_replyTo;
		}
		return $this->_setEmailSingle('_replyTo', $email, $name, __d('cake', 'Reply-To requires only 1 email address.'));
	}

/**
 * Read Receipt (Disposition-Notification-To header)
 *
 * @param mixed $email
 * @param string $name
 * @return mixed
 * @throws SocketException
 */
	public function readReceipt($email = null, $name = null) {
		if ($email === null) {
			return $this->_readReceipt;
		}
		return $this->_setEmailSingle('_readReceipt', $email, $name, __d('cake', 'Disposition-Notification-To requires only 1 email address.'));
	}

/**
 * Return Path
 *
 * @param mixed $email
 * @param string $name
 * @return mixed
 * @throws SocketException
 */
	public function returnPath($email = null, $name = null) {
		if ($email === null) {
			return $this->_returnPath;
		}
		return $this->_setEmailSingle('_returnPath', $email, $name, __d('cake', 'Return-Path requires only 1 email address.'));
	}

/**
 * To
 *
 * @param mixed $email Null to get, String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return mixed
 */
	public function to($email = null, $name = null) {
		if ($email === null) {
			return $this->_to;
		}
		return $this->_setEmail('_to', $email, $name);
	}

/**
 * Add To
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return object $this
 */
	public function addTo($email, $name = null) {
		return $this->_addEmail('_to', $email, $name);
	}

/**
 * Cc
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return mixed
 */
	public function cc($email = null, $name = null) {
		if ($email === null) {
			return $this->_cc;
		}
		return $this->_setEmail('_cc', $email, $name);
	}

/**
 * Add Cc
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return object $this
 */
	public function addCc($email, $name = null) {
		return $this->_addEmail('_cc', $email, $name);
	}

/**
 * Bcc
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return mixed
 */
	public function bcc($email = null, $name = null) {
		if ($email === null) {
			return $this->_bcc;
		}
		return $this->_setEmail('_bcc', $email, $name);
	}

/**
 * Add Bcc
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return object $this
 */
	public function addBcc($email, $name = null) {
		return $this->_addEmail('_bcc', $email, $name);
	}

/**
 * Set email
 *
 * @param string $varName
 * @param mixed $email
 * @param mixed $name
 * @return object $this
 * @throws SocketException
 */
	protected function _setEmail($varName, $email, $name) {
		if (!is_array($email)) {
			if (!Validation::email($email)) {
				throw new SocketException(__d('cake', 'Invalid email: "%s"', $email));
			}
			if ($name === null) {
				$name = $email;
			}
			$this->{$varName} = array($email => $name);
			return $this;
		}
		$list = array();
		foreach ($email as $key => $value) {
			if (is_int($key)) {
				$key = $value;
			}
			if (!Validation::email($key)) {
				throw new SocketException(__d('cake', 'Invalid email: "%s"', $key));
			}
			$list[$key] = $value;
		}
		$this->{$varName} = $list;
		return $this;
	}

/**
 * Set only 1 email
 *
 * @param string $varName
 * @param mixed $email
 * @param string $name
 * @param string $throwMessage
 * @return object $this
 * @throws SocketExpceiton
 */
	protected function _setEmailSingle($varName, $email, $name, $throwMessage) {
		$current = $this->{$varName};
		$this->_setEmail($varName, $email, $name);
		if (count($this->{$varName}) !== 1) {
			$this->{$varName} = $current;
			throw new SocketException($throwMessage);
		}
		return $this;
	}

/**
 * Add email
 *
 * @param string $varName
 * @param mixed $email
 * @param mixed $name
 * @return object $this
 */
	protected function _addEmail($varName, $email, $name) {
		if (!is_array($email)) {
			if (!Validation::email($email)) {
				throw new SocketException(__d('cake', 'Invalid email: "%s"', $email));
			}
			if ($name === null) {
				$name = $email;
			}
			$this->{$varName}[$email] = $name;
			return $this;
		}
		$list = array();
		foreach ($email as $key => $value) {
			if (is_int($key)) {
				$key = $value;
			}
			if (!Validation::email($key)) {
				throw new SocketException(__d('cake', 'Invalid email: "%s"', $key));
			}
			$list[$key] = $value;
		}
		$this->{$varName} = array_merge($this->{$varName}, $list);
		return $this;
	}

/**
 * Set Subject
 *
 * @param string $subject
 * @return mixed
 */
	public function subject($subject = null) {
		if ($subject === null) {
			return $this->_subject;
		}
		$this->_subject = $this->_encode((string)$subject);
		return $this;
	}

/**
 * Sets headers for the message
 *
 * @param array Associative array containing headers to be set.
 * @return object $this
 * @throws SocketException
 */
	public function setHeaders($headers) {
		if (!is_array($headers)) {
			throw new SocketException(__d('cake', '$headers should be an array.'));
		}
		$this->_headers = $headers;
		return $this;
	}

/**
 * Add header for the message
 *
 * @param array $headers
 * @return mixed $this
 * @throws SocketException
 */
	public function addHeaders($headers) {
		if (!is_array($headers)) {
			throw new SocketException(__d('cake', '$headers should be an array.'));
		}
		$this->_headers = array_merge($this->_headers, $headers);
		return $this;
	}

/**
 * Get list of headers
 *
 * ### Includes:
 *
 * - `from`
 * - `replyTo`
 * - `readReceipt`
 * - `returnPath`
 * - `to`
 * - `cc`
 * - `bcc`
 * - `subject`
 *
 * @param array $include
 * @return array
 */
	public function getHeaders($include = array()) {
		$defaults = array(
			'from' => false,
			'sender' => false,
			'replyTo' => false,
			'readReceipt' => false,
			'returnPath' => false,
			'to' => false,
			'cc' => false,
			'bcc' => false,
			'subject' => false
		);
		$include += $defaults;

		$headers = array();
		$relation = array(
			'from' => 'From',
			'replyTo' => 'Reply-To',
			'readReceipt' => 'Disposition-Notification-To',
			'returnPath' => 'Return-Path'
		);
		foreach ($relation as $var => $header) {
			if ($include[$var]) {
				$var = '_' . $var;
				$headers[$header] = current($this->_formatAddress($this->{$var}));
			}
		}
		if ($include['sender']) {
			if (key($this->_sender) === key($this->_from)) {
				$headers['Sender'] = '';
			} else {
				$headers['Sender'] = current($this->_formatAddress($this->_sender));
			}
		}

		foreach (array('to', 'cc', 'bcc') as $var) {
			if ($include[$var]) {
				$classVar = '_' . $var;
				$headers[ucfirst($var)] = implode(', ', $this->_formatAddress($this->{$classVar}));
			}
		}

		$headers += $this->_headers;
		if (!isset($headers['X-Mailer'])) {
			$headers['X-Mailer'] = self::EMAIL_CLIENT;
		}
		if (!isset($headers['Date'])) {
			$headers['Date'] = date(DATE_RFC2822);
		}
		if ($this->_messageId !== false) {
			if ($this->_messageId === true) {
				$headers['Message-ID'] = '<' . String::UUID() . '@' . env('HTTP_HOST') . '>';
			} else {
				$headers['Message-ID'] = $this->_messageId;
			}
		}

		if ($include['subject']) {
			$headers['Subject'] = $this->_subject;
		}

		$headers['MIME-Version'] = '1.0';
		if (!empty($this->_attachments)) {
			$headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->_boundary . '"';
			$headers[] = 'This part of the E-mail should never be seen. If';
			$headers[] = 'you are reading this, consider upgrading your e-mail';
			$headers[] = 'client to a MIME-compatible client.';
		} elseif ($this->_emailFormat === 'text') {
			$headers['Content-Type'] = 'text/plain; charset=' . $this->charset;
		} elseif ($this->_emailFormat === 'html') {
			$headers['Content-Type'] = 'text/html; charset=' . $this->charset;
		} elseif ($this->_emailFormat === 'both') {
			$headers['Content-Type'] = 'multipart/alternative; boundary="alt-' . $this->_boundary . '"';
		}
		$headers['Content-Transfer-Encoding'] = '7bit';

		return $headers;
	}

/**
 * Format addresses
 *
 * @param array $address
 * @return array
 */
	protected function _formatAddress($address) {
		$return = array();
		foreach ($address as $email => $alias) {
			if ($email === $alias) {
				$return[] = $email;
			} else {
				$return[] = sprintf('%s <%s>', $this->_encode($alias), $email);
			}
		}
		return $return;
	}

/**
 * Template and layout
 *
 * @param mixed $template Template name or null to not use
 * @param mixed $layout Layout name or null to not use
 * @return mixed
 */
	public function template($template = false, $layout = false) {
		if ($template === false) {
			return array(
				'template' => $this->_template,
				'layout' => $this->_layout
			);
		}
		$this->_template = $template;
		if ($layout !== false) {
			$this->_layout = $layout;
		}
		return $this;
	}

/**
 * View class for render
 *
 * @param string $viewClass
 * @return mixed
 */
	public function viewRender($viewClass = null) {
		if ($viewClass === null) {
			return $this->_viewRender;
		}
		$this->_viewRender = $viewClass;
		return $this;
	}

/**
 * Variables to be set on render
 *
 * @param array $viewVars
 * @return mixed
 */
	public function viewVars($viewVars = null) {
		if ($viewVars === null) {
			return $this->_viewVars;
		}
		$this->_viewVars = array_merge($this->_viewVars, (array)$viewVars);
		return $this;
	}

/**
 * Helpers to be used in render
 *
 * @param array $helpers
 * @return mixed
 */
	public function helpers($helpers = null) {
		if ($helpers === null) {
			return $this->_helpers;
		}
		$this->_helpers = (array)$helpers;
		return $this;
	}

/**
 * Email format
 *
 * @param string $format
 * @return mixed
 * @throws SocketException
 */
	public function emailFormat($format = null) {
		if ($format === null) {
			return $this->_emailFormat;
		}
		if (!in_array($format, $this->_emailFormatAvailable)) {
			throw new SocketException(__d('cake', 'Format not available.'));
		}
		$this->_emailFormat = $format;
		return $this;
	}

/**
 * Transport name
 *
 * @param string $name
 * @return mixed
 */
	public function transport($name = null) {
		if ($name === null) {
			return $this->_transportName;
		}
		$this->_transportName = (string)$name;
		$this->_transportClass = null;
		return $this;
	}

/**
 * Return the transport class
 *
 * @return object
 * @throws SocketException
 */
	public function transportClass() {
		if ($this->_transportClass) {
			return $this->_transportClass;
		}
		list($plugin, $transportClassname) = pluginSplit($this->_transportName, true);
		$transportClassname .= 'Transport';
		App::uses($transportClassname, $plugin . 'Network/Email');
		if (!class_exists($transportClassname)) {
			throw new SocketException(__d('cake', 'Class "%s" not found.', $transportClassname));
		} elseif (!method_exists($transportClassname, 'send')) {
			throw new SocketException(__d('cake', 'The "%s" do not have send method.', $transportClassname));
		}

		return $this->_transportClass = new $transportClassname();
	}

/**
 * Message-ID
 *
 * @param mixed $message True to generate a new Message-ID, False to ignore (not send in email), String to set as Message-ID
 * @return mixed
 * @throws SocketException
 */
	public function messageId($message = null) {
		if ($message === null) {
			return $this->_messageId;
		}
		if (is_bool($message)) {
			$this->_messageId = $message;
		} else {
			if (!preg_match('/^\<.+@.+\>$/', $message)) {
				throw new SocketException(__d('cake', 'Invalid format to Message-ID. The text should be something like "<uuid@server.com>"'));
			}
			$this->_messageId = $message;
		}
		return $this;
	}

/**
 * Attachments
 *
 * @param mixed $attachments String with the filename or array with filenames
 * @return mixed
 * @throws SocketException
 */
	public function attachments($attachments = null) {
		if ($attachments === null) {
			return $this->_attachments;
		}
		$attach = array();
		foreach ((array)$attachments as $name => $fileInfo) {
			if (!is_array($fileInfo)) {
				$fileInfo = array('file' => $fileInfo);
			}
			if (!isset($fileInfo['file'])) {
				throw new SocketException(__d('cake', 'File not specified.'));
			}
			$fileInfo['file'] = realpath($fileInfo['file']);
			if ($fileInfo['file'] === false || !file_exists($fileInfo['file'])) {
				throw new SocketException(__d('cake', 'File not found: "%s"', $fileInfo['file']));
			}
			if (is_int($name)) {
				$name = basename($fileInfo['file']);
			}
			if (!isset($fileInfo['mimetype'])) {
				$fileInfo['mimetype'] = 'application/octet-stream';
			}
			$attach[$name] = $fileInfo;
		}
		$this->_attachments = $attach;
		return $this;
	}

/**
 * Add attachments
 *
 * @param mixed $attachments String with the filename or array with filenames
 * @return object $this
 * @throws SocketException
 */
	public function addAttachments($attachments) {
		$current = $this->_attachments;
		$this->attachments($attachments);
		$this->_attachments = array_merge($current, $this->_attachments);
		return $this;
	}

/**
 * Get generated message (used by transport classes)
 *
 * @param mixed $type Use MESSAGE_* constants or null to return the full message as array
 * @return mixed String if have type, array if type is null
 */
	public function message($type = null) {
		switch ($type) {
			case self::MESSAGE_HTML:
				return $this->_htmlMessage;
			case self::MESSAGE_TEXT:
				return $this->_textMessage;
		}
		return $this->_message;
	}

/**
 * Configuration to use when send email
 *
 * @param mixed $config String with configuration name (from email.php), array with config or null to return current config
 * @return mixed
 */
	public function config($config = null) {
		if ($config === null) {
			return $this->_config;
		}

		if (is_array($config)) {
			$this->_config = $config;
		} else {
			$this->_config = (string)$config;
		}

		if ($this->_transportClass) {
			$this->_transportClass->config($this->_config);
		}

		return $this;
	}

/**
 * Send an email using the specified content, template and layout
 *
 * @return boolean Success
 * @throws SocketException
 */
	public function send($content = null) {
		if (is_string($this->_config)) {
			if (!config('email')) {
				throw new SocketException(__d('cake', '%s not found.', APP . 'Config' . DS . 'email.php'));
			}
			$configs = new EmailConfig();
			if (!isset($configs->{$this->_config})) {
				throw new SocketException(__d('cake', 'Unknown email configuration "%s".', $this->_config));
			}
			$config = $configs->{$this->_config};
			if (isset($config['transport'])) {
				$this->transport($config['transport']);
			}
		} else {
			$config = $this->_config;
		}

		if (empty($this->_from)) {
			if (!empty($config['from'])) {
				$this->from($config['from']);
			} else {
				throw new SocketException(__d('cake', 'From is not specified.'));
			}
		}
		if (empty($this->_to) && empty($this->_cc) && empty($this->_bcc)) {
			throw new SocketException(__d('cake', 'You need specify one destination on to, cc or bcc.'));
		}

		if (is_array($content)) {
			$content = implode("\n", $content) . "\n";
		}

		$this->_textMessage = $this->_htmlMessage = '';
		if ($content !== null) {
			if ($this->_emailFormat === 'text') {
				$this->_textMessage = $content;
			} elseif ($this->_emailFormat === 'html') {
				$this->_htmlMessage = $content;
			} elseif ($this->_emailFormat === 'both') {
				$this->_textMessage = $this->_htmlMessage = $content;
			}
		}

		$this->_createBoundary();

		$message = $this->_wrap($content);
		if (empty($this->_template)) {
			$message = $this->_formatMessage($message);
		} else {
			$message = $this->_render($message);
		}
		$message[] = '';
		$this->_message = $message;

		if (!empty($this->_attachments)) {
			$this->_attachFiles();

			$this->_message[] = '';
			$this->_message[] = '--' . $this->_boundary . '--';
			$this->_message[] = '';
		}

		$transport = $this->transportClass();
		$transport->config($config);

		return $transport->send($this);
	}

/**
 * Static method to fast create an instance of CakeEmail
 *
 * @param mixed $to Address to send (see CakeEmail::to()). If null, will try to use 'to' from transport config
 * @param mixed $subject String of subject or null to use 'subject' from transport config
 * @param mixed $message String with message or array with variables to be used in render
 * @param mixed $transportConfig String to use config from EmailConfig or array with configs
 * @param boolean $send Send the email or just return the instance pre-configured
 * @return object Instance of CakeEmail
 */
	public static function deliver($to = null, $subject = null, $message = null, $transportConfig = 'fast', $send = true) {
		$class = __CLASS__;
		$instance = new $class();

		if (is_string($transportConfig)) {
			if (!config('email')) {
				throw new SocketException(__d('cake', '%s not found.', APP . 'Config' . DS . 'email.php'));
			}
			$configs = new EmailConfig();
			if (!isset($configs->{$transportConfig})) {
				throw new SocketException(__d('cake', 'Unknown email configuration "%s".', $transportConfig));
			}
			$transportConfig = $configs->{$transportConfig};
		}
		self::_applyConfig($instance, $transportConfig);

		if ($to !== null) {
			$instance->to($to);
		}
		if ($subject !== null) {
			$instance->subject($subject);
		}
		if (is_array($message)) {
			$instance->viewVars($message);
			$message = null;
		} elseif ($message === null && isset($config['message'])) {
			$message = $config['message'];
		}

		if ($send === true) {
			$instance->send($message);
		}

		return $instance;
	}

/**
 * Apply the config to an instance
 *
 * @param object $obj CakeEmail
 * @param array $config
 * @return void
 */
	protected static function _applyConfig(CakeEmail $obj, $config) {
		$simpleMethods = array(
			'from', 'sender', 'to', 'replyTo', 'readReceipt', 'returnPath', 'cc', 'bcc',
			'messageId', 'subject', 'viewRender', 'viewVars', 'attachments',
			'transport', 'emailFormat'
		);
		foreach ($simpleMethods as $method) {
			if (isset($config[$method])) {
				$obj->$method($config[$method]);
				unset($config[$method]);
			}
		}
		if (isset($config['headers'])) {
			$obj->setHeaders($config['headers']);
			unset($config['headers']);
		}
		if (array_key_exists('template', $config)) {
			$layout = false;
			if (array_key_exists('layout', $config)) {
				$layout = $config['layout'];
				unset($config['layout']);
			}
			$obj->template($config['template'], $layout);
			unset($config['template']);
		}
		$obj->config($config);
	}

/**
 * Reset all EmailComponent internal variables to be able to send out a new email.
 *
 * @return object $this
 */
	public function reset() {
		$this->_to = array();
		$this->_from = array();
		$this->_sender = array();
		$this->_replyTo = array();
		$this->_readReceipt = array();
		$this->_returnPath = array();
		$this->_cc = array();
		$this->_bcc = array();
		$this->_messageId = true;
		$this->_subject = '';
		$this->_headers = array();
		$this->_layout = 'default';
		$this->_template = '';
		$this->_viewRender = 'View';
		$this->_viewVars = array();
		$this->_helpers = array();
		$this->_textMessage = '';
		$this->_htmlMessage = '';
		$this->_message = '';
		$this->_emailFormat = 'text';
		$this->_transportName = 'Mail';
		$this->_transportClass = null;
		$this->_attachments = array();
		$this->_config = 'default';
		return $this;
	}

/**
 * Encode the specified string using the current charset
 *
 * @param string $text String to encode
 * @return string Encoded string
 */
	protected function _encode($text) {
		$internalEncoding = function_exists('mb_internal_encoding');
		if ($internalEncoding) {
			$restore = mb_internal_encoding();
			mb_internal_encoding($this->charset);
		}
		$return = mb_encode_mimeheader($text, $this->charset, 'B');
		if ($internalEncoding) {
			mb_internal_encoding($restore);
		}
		return $return;
	}

/**
 * Wrap the message to follow the RFC 2822 - 2.1.1
 *
 * @param string $message Message to wrap
 * @return array Wrapped message
 */
	protected function _wrap($message) {
		$message = str_replace(array("\r\n", "\r"), "\n", $message);
		$lines = explode("\n", $message);
		$formatted = array();

		foreach ($lines as $line) {
			if (empty($line)) {
				$formatted[] = '';
				continue;
			}
			if ($line[0] === '.') {
				$line = '.' . $line;
			}
			if (!preg_match('/\<[a-z]/i', $line)) {
				$formatted = array_merge($formatted, explode("\n", wordwrap($line, self::LINE_LENGTH_SHOULD, "\n")));
				continue;
			}

			$tagOpen = false;
			$tmpLine = $tag = '';
			$tmpLineLength = 0;
			for ($i = 0, $count = strlen($line); $i < $count; $i++) {
				$char = $line[$i];
				if ($tagOpen) {
					$tag .= $char;
					if ($char === '>') {
						$tagLength = strlen($tag);
						if ($tagLength + $tmpLineLength < self::LINE_LENGTH_SHOULD) {
							$tmpLine .= $tag;
							$tmpLineLength += $tagLength;
						} else {
							if ($tmpLineLength > 0) {
								$formatted[] = trim($tmpLine);
								$tmpLine = '';
								$tmpLineLength = 0;
							}
							if ($tagLength > self::LINE_LENGTH_SHOULD) {
								$formatted[] = $tag;
							} else {
								$tmpLine = $tag;
								$tmpLineLength = $tagLength;
							}
						}
						$tag = '';
						$tagOpen = false;
					}
					continue;
				}
				if ($char === '<') {
					$tagOpen = true;
					$tag = '<';
					continue;
				}
				if ($char === ' ' && $tmpLineLength >= self::LINE_LENGTH_SHOULD) {
					$formatted[] = $tmpLine;
					$tmpLineLength = 0;
					continue;
				}
				$tmpLine .= $char;
				$tmpLineLength++;
				if ($tmpLineLength === self::LINE_LENGTH_SHOULD) {
					$nextChar = $line[$i + 1];
					if ($nextChar === ' ' || $nextChar === '<') {
						$formatted[] = trim($tmpLine);
						$tmpLine = '';
						$tmpLineLength = 0;
						if ($nextChar === ' ') {
							$i++;
						}
					} else {
						$lastSpace = strrpos($tmpLine, ' ');
						if ($lastSpace === false) {
							continue;
						}
						$formatted[] = trim(substr($tmpLine, 0, $lastSpace));
						$tmpLine = substr($tmpLine, $lastSpace + 1);
						$tmpLineLength = strlen($tmpLine);
					}
				}
			}
			if (!empty($tmpLine)) {
				$formatted[] = $tmpLine;
			}
		}
		$formatted[] = '';
		return $formatted;
	}

/**
 * Create unique boundary identifier
 *
 * @return void
 */
	protected function _createBoundary() {
		if (!empty($this->_attachments) || $this->_emailFormat === 'both') {
			$this->_boundary = md5(uniqid(time()));
		}
	}

/**
 * Attach files by adding file contents inside boundaries.
 *
 * @return void
 */
	protected function _attachFiles() {
		foreach ($this->_attachments as $filename => $fileInfo) {
			$handle = fopen($fileInfo['file'], 'rb');
			$data = fread($handle, filesize($fileInfo['file']));
			$data = chunk_split(base64_encode($data)) ;
			fclose($handle);

			$this->_message[] = '--' . $this->_boundary;
			$this->_message[] = 'Content-Type: ' . $fileInfo['mimetype'];
			$this->_message[] = 'Content-Transfer-Encoding: base64';
			if (empty($fileInfo['contentId'])) {
				$this->_message[] = 'Content-Disposition: attachment; filename="' . $filename . '"';
			} else {
				$this->_message[] = 'Content-ID: <' . $fileInfo['contentId'] . '>';
				$this->_message[] = 'Content-Disposition: inline; filename="' . $filename . '"';
			}
			$this->_message[] = '';
			$this->_message[] = $data;
			$this->_message[] = '';
		}
	}

/**
 * Format the message by seeing if it has attachments.
 *
 * @param array $message Message to format
 * @return array
 */
	protected function _formatMessage($message) {
		if (!empty($this->_attachments)) {
			$prefix = array('--' . $this->_boundary);
			if ($this->_emailFormat === 'text') {
				$prefix[] = 'Content-Type: text/plain; charset=' . $this->charset;
			} elseif ($this->_emailFormat === 'html') {
				$prefix[] = 'Content-Type: text/html; charset=' . $this->charset;
			} elseif ($this->_emailFormat === 'both') {
				$prefix[] = 'Content-Type: multipart/alternative; boundary="alt-' . $this->_boundary . '"';
			}
			$prefix[] = 'Content-Transfer-Encoding: 7bit';
			$prefix[] = '';
			$message = array_merge($prefix, $message);
		}
		return $message;
	}

/**
 * Render the contents using the current layout and template.
 *
 * @param string $content Content to render
 * @return array Email ready to be sent
 * @access private
 */
	protected function _render($content) {
		$viewClass = $this->_viewRender;

		if ($viewClass !== 'View') {
			list($plugin, $viewClass) = pluginSplit($viewClass, true);
			$viewClass .= 'View';
			App::uses($viewClass, $plugin . 'View');
		}

		$View = new $viewClass(null);
		$View->viewVars = $this->_viewVars;
		$View->helpers = $this->_helpers;
		$msg = array();

		list($templatePlugin, $template) = pluginSplit($this->_template, true);
		list($layoutPlugin, $layout) = pluginSplit($this->_layout, true);
		if (!empty($templatePlugin)) {
			$View->plugin = rtrim($templatePlugin, '.');
		} elseif (!empty($layoutPlugin)) {
			$View->plugin = rtrim($layoutPlugin, '.');
		}

		$content = implode("\n", $content);

		if ($this->_emailFormat === 'both') {
			$originalContent = $content;
			if (!empty($this->_attachments)) {
				$msg[] = '--' . $this->_boundary;
				$msg[] = 'Content-Type: multipart/alternative; boundary="alt-' . $this->_boundary . '"';
				$msg[] = '';
			}
			$msg[] = '--alt-' . $this->_boundary;
			$msg[] = 'Content-Type: text/plain; charset=' . $this->charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$View->viewPath = $View->layoutPath = 'Emails' . DS . 'text';
			$View->viewVars['content'] = $originalContent;
			$this->_textMessage = str_replace(array("\r\n", "\r"), "\n", $View->render($template, $layout));
			$content = explode("\n", $this->_textMessage);
			$msg = array_merge($msg, $content);

			$msg[] = '';
			$msg[] = '--alt-' . $this->_boundary;
			$msg[] = 'Content-Type: text/html; charset=' . $this->charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$View->viewPath = $View->layoutPath = 'Emails' . DS . 'html';
			$View->viewVars['content'] = $originalContent;
			$View->hasRendered = false;
			$this->_htmlMessage = str_replace(array("\r\n", "\r"), "\n", $View->render($template, $layout));
			$content = explode("\n", $this->_htmlMessage);
			$msg = array_merge($msg, $content);

			$msg[] = '';
			$msg[] = '--alt-' . $this->_boundary . '--';
			$msg[] = '';

			return $msg;
		}

		if (!empty($this->_attachments)) {
			if ($this->_emailFormat === 'html') {
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

		$View->viewPath = $View->layoutPath = 'Emails' . DS . $this->_emailFormat;
		$View->viewVars['content'] = $content;
		$rendered = $View->render($template, $layout);
		$content = explode("\n", $rendered);

		if ($this->_emailFormat === 'html') {
			$this->_htmlMessage = $rendered;
		} else {
			$this->_textMessage = $rendered;
		}

		return array_merge($msg, $content);
	}

}
