<?php
/**
 * Cake E-Mail
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
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
 * @var boolean|string True to generate, False to ignore, String with value
 */
	protected $_messageId = true;

/**
 * Domain for messageId generation.
 * Needs to be manually set for CLI mailing as env('HTTP_HOST') is empty
 *
 * @var string
 */
	protected $_domain = null;

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
 * Theme for the View
 *
 * @var array
 */
	protected $_theme = null;

/**
 * Helpers to be used in the render
 *
 * @var array
 */
	protected $_helpers = array('Html');

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
 * @var AbstractTransport
 */
	protected $_transportClass = null;

/**
 * Charset the email body is sent in
 *
 * @var string
 */
	public $charset = 'utf-8';

/**
 * Charset the email header is sent in
 * If null, the $charset property will be used as default
 *
 * @var string
 */
	public $headerCharset = null;

/**
 * The application wide charset, used to encode headers and body
 *
 * @var string
 */
	protected $_appCharset = null;

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
 * @var string|array
 */
	protected $_config = array();

/**
 * 8Bit character sets
 *
 * @var array
 */
	protected $_charset8bit = array('UTF-8', 'SHIFT_JIS');

/**
 * Define Content-Type charset name
 *
 * @var array
 */
	protected $_contentTypeCharset = array(
		'ISO-2022-JP-MS' => 'ISO-2022-JP'
	);

/**
 * Constructor
 * @param array|string $config Array of configs, or string to load configs from email.php
 *
 */
	public function __construct($config = null) {
		$this->_appCharset = Configure::read('App.encoding');
		if ($this->_appCharset !== null) {
			$this->charset = $this->_appCharset;
		}
		$this->_domain = env('HTTP_HOST');
		if (empty($this->_domain)) {
			$this->_domain = php_uname('n');
		}

		if ($config) {
			$this->config($config);
		}
		if (empty($this->headerCharset)) {
			$this->headerCharset = $this->charset;
		}
	}

/**
 * From
 *
 * @param string|array $email
 * @param string $name
 * @return array|CakeEmail
 * @throws SocketException
 */
	public function from($email = null, $name = null) {
		if ($email === null) {
			return $this->_from;
		}
		return $this->_setEmailSingle('_from', $email, $name, __d('cake_dev', 'From requires only 1 email address.'));
	}

/**
 * Sender
 *
 * @param string|array $email
 * @param string $name
 * @return array|CakeEmail
 * @throws SocketException
 */
	public function sender($email = null, $name = null) {
		if ($email === null) {
			return $this->_sender;
		}
		return $this->_setEmailSingle('_sender', $email, $name, __d('cake_dev', 'Sender requires only 1 email address.'));
	}

/**
 * Reply-To
 *
 * @param string|array $email
 * @param string $name
 * @return array|CakeEmail
 * @throws SocketException
 */
	public function replyTo($email = null, $name = null) {
		if ($email === null) {
			return $this->_replyTo;
		}
		return $this->_setEmailSingle('_replyTo', $email, $name, __d('cake_dev', 'Reply-To requires only 1 email address.'));
	}

/**
 * Read Receipt (Disposition-Notification-To header)
 *
 * @param string|array $email
 * @param string $name
 * @return array|CakeEmail
 * @throws SocketException
 */
	public function readReceipt($email = null, $name = null) {
		if ($email === null) {
			return $this->_readReceipt;
		}
		return $this->_setEmailSingle('_readReceipt', $email, $name, __d('cake_dev', 'Disposition-Notification-To requires only 1 email address.'));
	}

/**
 * Return Path
 *
 * @param string|array $email
 * @param string $name
 * @return array|CakeEmail
 * @throws SocketException
 */
	public function returnPath($email = null, $name = null) {
		if ($email === null) {
			return $this->_returnPath;
		}
		return $this->_setEmailSingle('_returnPath', $email, $name, __d('cake_dev', 'Return-Path requires only 1 email address.'));
	}

/**
 * To
 *
 * @param string|array $email Null to get, String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return array|CakeEmail
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
 * @param string|array $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return CakeEmail $this
 */
	public function addTo($email, $name = null) {
		return $this->_addEmail('_to', $email, $name);
	}

/**
 * Cc
 *
 * @param string|array $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return array|CakeEmail
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
 * @param string|array $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return CakeEmail $this
 */
	public function addCc($email, $name = null) {
		return $this->_addEmail('_cc', $email, $name);
	}

/**
 * Bcc
 *
 * @param string|array $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return array|CakeEmail
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
 * @param string|array $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return CakeEmail $this
 */
	public function addBcc($email, $name = null) {
		return $this->_addEmail('_bcc', $email, $name);
	}

/**
 * Charset setter/getter
 *
 * @param string $charset
 * @return string $this->charset
 */
	public function charset($charset = null) {
		if ($charset === null) {
			return $this->charset;
		}
		$this->charset = $charset;
		if (empty($this->headerCharset)) {
			$this->headerCharset = $charset;
		}
		return $this->charset;
	}

/**
 * HeaderCharset setter/getter
 *
 * @param string $charset
 * @return string $this->charset
 */
	public function headerCharset($charset = null) {
		if ($charset === null) {
			return $this->headerCharset;
		}
		return $this->headerCharset = $charset;
	}

/**
 * Set email
 *
 * @param string $varName
 * @param string|array $email
 * @param string $name
 * @return CakeEmail $this
 * @throws SocketException
 */
	protected function _setEmail($varName, $email, $name) {
		if (!is_array($email)) {
			if (!Validation::email($email)) {
				throw new SocketException(__d('cake_dev', 'Invalid email: "%s"', $email));
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
				throw new SocketException(__d('cake_dev', 'Invalid email: "%s"', $key));
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
 * @param string|array $email
 * @param string $name
 * @param string $throwMessage
 * @return CakeEmail $this
 * @throws SocketException
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
 * @param string|array $email
 * @param string $name
 * @return CakeEmail $this
 * @throws SocketException
 */
	protected function _addEmail($varName, $email, $name) {
		if (!is_array($email)) {
			if (!Validation::email($email)) {
				throw new SocketException(__d('cake_dev', 'Invalid email: "%s"', $email));
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
				throw new SocketException(__d('cake_dev', 'Invalid email: "%s"', $key));
			}
			$list[$key] = $value;
		}
		$this->{$varName} = array_merge($this->{$varName}, $list);
		return $this;
	}

/**
 * Get/Set Subject.
 *
 * @param string $subject
 * @return string|CakeEmail
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
 * @param array $headers Associative array containing headers to be set.
 * @return CakeEmail $this
 * @throws SocketException
 */
	public function setHeaders($headers) {
		if (!is_array($headers)) {
			throw new SocketException(__d('cake_dev', '$headers should be an array.'));
		}
		$this->_headers = $headers;
		return $this;
	}

/**
 * Add header for the message
 *
 * @param array $headers
 * @return object $this
 * @throws SocketException
 */
	public function addHeaders($headers) {
		if (!is_array($headers)) {
			throw new SocketException(__d('cake_dev', '$headers should be an array.'));
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
		if ($include == array_values($include)) {
			$include = array_fill_keys($include, true);
		}
		$defaults = array_fill_keys(array('from', 'sender', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc', 'subject'), false);
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
				$headers['Message-ID'] = '<' . str_replace('-', '', String::UUID()) . '@' . $this->_domain . '>';
			} else {
				$headers['Message-ID'] = $this->_messageId;
			}
		}

		if ($include['subject']) {
			$headers['Subject'] = $this->_subject;
		}

		$headers['MIME-Version'] = '1.0';
		if (!empty($this->_attachments) || $this->_emailFormat === 'both') {
			$headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->_boundary . '"';
		} elseif ($this->_emailFormat === 'text') {
			$headers['Content-Type'] = 'text/plain; charset=' . $this->_getContentTypeCharset();
		} elseif ($this->_emailFormat === 'html') {
			$headers['Content-Type'] = 'text/html; charset=' . $this->_getContentTypeCharset();
		}
		$headers['Content-Transfer-Encoding'] = $this->_getContentTransferEncoding();

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
				if (strpos($alias, ',') !== false) {
					$alias = '"' . $alias . '"';
				}
				$return[] = sprintf('%s <%s>', $this->_encode($alias), $email);
			}
		}
		return $return;
	}

/**
 * Template and layout
 *
 * @param boolean|string $template Template name or null to not use
 * @param boolean|string $layout Layout name or null to not use
 * @return array|CakeEmail
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
 * @return string|CakeEmail
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
 * @return array|CakeEmail
 */
	public function viewVars($viewVars = null) {
		if ($viewVars === null) {
			return $this->_viewVars;
		}
		$this->_viewVars = array_merge($this->_viewVars, (array)$viewVars);
		return $this;
	}

/**
 * Theme to use when rendering
 *
 * @param string $theme
 * @return string|CakeEmail
 */
	public function theme($theme = null) {
		if ($theme === null) {
			return $this->_theme;
		}
		$this->_theme = $theme;
		return $this;
	}

/**
 * Helpers to be used in render
 *
 * @param array $helpers
 * @return array|CakeEmail
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
 * @return string|CakeEmail
 * @throws SocketException
 */
	public function emailFormat($format = null) {
		if ($format === null) {
			return $this->_emailFormat;
		}
		if (!in_array($format, $this->_emailFormatAvailable)) {
			throw new SocketException(__d('cake_dev', 'Format not available.'));
		}
		$this->_emailFormat = $format;
		return $this;
	}

/**
 * Transport name
 *
 * @param string $name
 * @return string|CakeEmail
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
 * @return CakeEmail
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
			throw new SocketException(__d('cake_dev', 'Class "%s" not found.', $transportClassname));
		} elseif (!method_exists($transportClassname, 'send')) {
			throw new SocketException(__d('cake_dev', 'The "%s" do not have send method.', $transportClassname));
		}

		return $this->_transportClass = new $transportClassname();
	}

/**
 * Message-ID
 *
 * @param boolean|string $message True to generate a new Message-ID, False to ignore (not send in email), String to set as Message-ID
 * @return boolean|string|CakeEmail
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
				throw new SocketException(__d('cake_dev', 'Invalid format for Message-ID. The text should be something like "<uuid@server.com>"'));
			}
			$this->_messageId = $message;
		}
		return $this;
	}

/**
 * Domain as top level (the part after @)
 *
 * @param string $domain Manually set the domain for CLI mailing
 * @return string|CakeEmail
 */
	public function domain($domain = null) {
		if ($domain === null) {
			return $this->_domain;
		}
		$this->_domain = $domain;
		return $this;
	}

/**
 * Add attachments to the email message
 *
 * Attachments can be defined in a few forms depending on how much control you need:
 *
 * Attach a single file:
 *
 * {{{
 * $email->attachments('path/to/file');
 * }}}
 *
 * Attach a file with a different filename:
 *
 * {{{
 * $email->attachments(array('custom_name.txt' => 'path/to/file.txt'));
 * }}}
 *
 * Attach a file and specify additional properties:
 *
 * {{{
 * $email->attachments(array('custom_name.png' => array(
 *		'file' => 'path/to/file',
 *		'mimetype' => 'image/png',
 *		'contentId' => 'abc123'
 * ));
 * }}}
 *
 * The `contentId` key allows you to specify an inline attachment. In your email text, you
 * can use `<img src="cid:abc123" />` to display the image inline.
 *
 * @param string|array $attachments String with the filename or array with filenames
 * @return array|CakeEmail Either the array of attachments when getting or $this when setting.
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
				throw new SocketException(__d('cake_dev', 'File not specified.'));
			}
			$fileInfo['file'] = realpath($fileInfo['file']);
			if ($fileInfo['file'] === false || !file_exists($fileInfo['file'])) {
				throw new SocketException(__d('cake_dev', 'File not found: "%s"', $fileInfo['file']));
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
 * @param string|array $attachments String with the filename or array with filenames
 * @return CakeEmail $this
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
 * @param string $type Use MESSAGE_* constants or null to return the full message as array
 * @return string|array String if have type, array if type is null
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
 * @param string|array $config String with configuration name (from email.php), array with config or null to return current config
 * @return string|array|CakeEmail
 */
	public function config($config = null) {
		if ($config === null) {
			return $this->_config;
		}
		if (!is_array($config)) {
			$config = (string)$config;
		}

		$this->_applyConfig($config);
		return $this;
	}

/**
 * Send an email using the specified content, template and layout
 * 
 * @param string|array $content String with message or array with messages
 * @return array
 * @throws SocketException
 */
	public function send($content = null) {
		if (empty($this->_from)) {
			throw new SocketException(__d('cake_dev', 'From is not specified.'));
		}
		if (empty($this->_to) && empty($this->_cc) && empty($this->_bcc)) {
			throw new SocketException(__d('cake_dev', 'You need to specify at least one destination for to, cc or bcc.'));
		}

		if (is_array($content)) {
			$content = implode("\n", $content) . "\n";
		}

		$this->_textMessage = $this->_htmlMessage = '';
		$this->_createBoundary();
		$this->_message = $this->_render($this->_wrap($content));

		$contents = $this->transportClass()->send($this);
		if (!empty($this->_config['log'])) {
			$level = LOG_DEBUG;
			if ($this->_config['log'] !== true) {
				$level = $this->_config['log'];
			}
			CakeLog::write($level, PHP_EOL . $contents['headers'] . PHP_EOL . $contents['message']);
		}
		return $contents;
	}

/**
 * Static method to fast create an instance of CakeEmail
 *
 * @param string|array $to Address to send (see CakeEmail::to()). If null, will try to use 'to' from transport config
 * @param string $subject String of subject or null to use 'subject' from transport config
 * @param string|array $message String with message or array with variables to be used in render
 * @param string|array $transportConfig String to use config from EmailConfig or array with configs
 * @param boolean $send Send the email or just return the instance pre-configured
 * @return CakeEmail Instance of CakeEmail
 * @throws SocketException
 */
	public static function deliver($to = null, $subject = null, $message = null, $transportConfig = 'fast', $send = true) {
		$class = __CLASS__;
		$instance = new $class($transportConfig);
		if ($to !== null) {
			$instance->to($to);
		}
		if ($subject !== null) {
			$instance->subject($subject);
		}
		if (is_array($message)) {
			$instance->viewVars($message);
			$message = null;
		} elseif ($message === null && array_key_exists('message', $config = $instance->config())) {
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
 * @param CakeEmail $obj CakeEmail
 * @param array $config
 * @return void
 * @throws ConfigureException When configuration file cannot be found, or is missing
 *   the named config.
 */
	protected function _applyConfig($config) {
		if (is_string($config)) {
			if (!class_exists('EmailConfig') && !config('email')) {
				throw new ConfigureException(__d('cake_dev', '%s not found.', APP . 'Config' . DS . 'email.php'));
			}
			$configs = new EmailConfig();
			if (!isset($configs->{$config})) {
				throw new ConfigureException(__d('cake_dev', 'Unknown email configuration "%s".', $config));
			}
			$config = $configs->{$config};
		}
		$this->_config += $config;
		if (!empty($config['charset'])) {
			$this->charset = $config['charset'];
		}
		if (!empty($config['headerCharset'])) {
			$this->headerCharset = $config['headerCharset'];
		}
		if (empty($this->headerCharset)) {
			$this->headerCharset = $this->charset;
		}
		$simpleMethods = array(
			'from', 'sender', 'to', 'replyTo', 'readReceipt', 'returnPath', 'cc', 'bcc',
			'messageId', 'domain', 'subject', 'viewRender', 'viewVars', 'attachments',
			'transport', 'emailFormat', 'theme', 'helpers'
		);
		foreach ($simpleMethods as $method) {
			if (isset($config[$method])) {
				$this->$method($config[$method]);
				unset($config[$method]);
			}
		}
		if (isset($config['headers'])) {
			$this->setHeaders($config['headers']);
			unset($config['headers']);
		}
		if (array_key_exists('template', $config)) {
			$layout = false;
			if (array_key_exists('layout', $config)) {
				$layout = $config['layout'];
				unset($config['layout']);
			}
			$this->template($config['template'], $layout);
			unset($config['template']);
		}
		$this->transportClass()->config($config);
	}

/**
 * Reset all EmailComponent internal variables to be able to send out a new email.
 *
 * @return CakeEmail $this
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
		$this->_theme = null;
		$this->_helpers = array('Html');
		$this->_textMessage = '';
		$this->_htmlMessage = '';
		$this->_message = '';
		$this->_emailFormat = 'text';
		$this->_transportName = 'Mail';
		$this->_transportClass = null;
		$this->charset = 'utf-8';
		$this->headerCharset = null;
		$this->_attachments = array();
		$this->_config = array();
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
			mb_internal_encoding($this->_appCharset);
		}
		if (empty($this->headerCharset)) {
			$this->headerCharset = $this->charset;
		}
		$return = mb_encode_mimeheader($text, $this->headerCharset, 'B');
		if ($internalEncoding) {
			mb_internal_encoding($restore);
		}
		return $return;
	}

/**
 * Translates a string for one charset to another if the App.encoding value
 * differs and the mb_convert_encoding function exists
 *
 * @param string $text The text to be converted
 * @param string $charset the target encoding
 * @return string
 */
	protected function _encodeString($text, $charset) {
		if ($this->_appCharset === $charset || !function_exists('mb_convert_encoding')) {
			return $text;
		}
		return mb_convert_encoding($text, $charset, $this->_appCharset);
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
 * Attach non-embedded files by adding file contents inside boundaries.
 *
 * @param string $boundary Boundary to use. If null, will default to $this->_boundary 
 * @return array An array of lines to add to the message
 */
	protected function _attachFiles($boundary = null) {
		if ($boundary === null) {
			$boundary = $this->_boundary;
		}

		$msg = array();
		foreach ($this->_attachments as $filename => $fileInfo) {
			if (!empty($fileInfo['contentId'])) {
				continue;
			}
			$data = $this->_readFile($fileInfo['file']);

			$msg[] = '--' . $boundary;
			$msg[] = 'Content-Type: ' . $fileInfo['mimetype'];
			$msg[] = 'Content-Transfer-Encoding: base64';
			$msg[] = 'Content-Disposition: attachment; filename="' . $filename . '"';
			$msg[] = '';
			$msg[] = $data;
			$msg[] = '';
		}
		return $msg;
	}

/**
 * Read the file contents and return a base64 version of the file contents.
 *
 * @param string $file The file to read.
 * @return string File contents in base64 encoding
 */
	protected function _readFile($file) {
		$handle = fopen($file, 'rb');
		$data = fread($handle, filesize($file));
		$data = chunk_split(base64_encode($data));
		fclose($handle);
		return $data;
	}

/**
 * Attach inline/embedded files to the message.
 *
 * @param string $boundary Boundary to use. If null, will default to $this->_boundary 
 * @return array An array of lines to add to the message
 */
	protected function _attachInlineFiles($boundary = null) {
		if ($boundary === null) {
			$boundary = $this->_boundary;
		}

		$msg = array();
		foreach ($this->_attachments as $filename => $fileInfo) {
			if (empty($fileInfo['contentId'])) {
				continue;
			}
			$data = $this->_readFile($fileInfo['file']);

			$msg[] = '--' . $boundary;
			$msg[] = 'Content-Type: ' . $fileInfo['mimetype'];
			$msg[] = 'Content-Transfer-Encoding: base64';
			$msg[] = 'Content-ID: <' . $fileInfo['contentId'] . '>';
			$msg[] = 'Content-Disposition: inline; filename="' . $filename . '"';
			$msg[] = '';
			$msg[] = $data;
			$msg[] = '';
		}
		return $msg;
	}

/**
 * Render the body of the email.
 *
 * @param string $content Content to render
 * @return array Email body ready to be sent
 */
	protected function _render($content) {
		$content = implode("\n", $content);
		$rendered = $this->_renderTemplates($content);

		$msg = array();

		$contentIds = array_filter((array)Hash::extract($this->_attachments, '{s}.contentId'));
		$hasInlineAttachments = count($contentIds) > 0;
		$hasAttachments = !empty($this->_attachments);
		$hasMultipleTypes = count($rendered) > 1;

		$boundary = $relBoundary = $textBoundary = $this->_boundary;

		if ($hasInlineAttachments) {
			$msg[] = '--' . $boundary;
			$msg[] = 'Content-Type: multipart/related; boundary="rel-' . $boundary . '"';
			$msg[] = '';
			$relBoundary = $textBoundary = 'rel-' . $boundary;
		}

		if ($hasMultipleTypes) {
			$msg[] = '--' . $relBoundary;
			$msg[] = 'Content-Type: multipart/alternative; boundary="alt-' . $boundary . '"';
			$msg[] = '';
			$textBoundary = 'alt-' . $boundary;
		}

		if (isset($rendered['text'])) {
			if ($textBoundary !== $boundary || $hasAttachments) {
				$msg[] = '--' . $textBoundary;
				$msg[] = 'Content-Type: text/plain; charset=' . $this->_getContentTypeCharset();
				$msg[] = 'Content-Transfer-Encoding: ' . $this->_getContentTransferEncoding();
				$msg[] = '';
			}
			$this->_textMessage = $rendered['text'];
			$content = explode("\n", $this->_textMessage);
			$msg = array_merge($msg, $content);
			$msg[] = '';
		}

		if (isset($rendered['html'])) {
			if ($textBoundary !== $boundary || $hasAttachments) {
				$msg[] = '--' . $textBoundary;
				$msg[] = 'Content-Type: text/html; charset=' . $this->_getContentTypeCharset();
				$msg[] = 'Content-Transfer-Encoding: ' . $this->_getContentTransferEncoding();
				$msg[] = '';
			}
			$this->_htmlMessage = $rendered['html'];
			$content = explode("\n", $this->_htmlMessage);
			$msg = array_merge($msg, $content);
			$msg[] = '';
		}

		if ($hasMultipleTypes) {
			$msg[] = '--' . $textBoundary . '--';
			$msg[] = '';
		}

		if ($hasInlineAttachments) {
			$attachments = $this->_attachInlineFiles($relBoundary);
			$msg = array_merge($msg, $attachments);
			$msg[] = '';
			$msg[] = '--' . $relBoundary . '--';
			$msg[] = '';
		}

		if ($hasAttachments) {
			$attachments = $this->_attachFiles($boundary);
			$msg = array_merge($msg, $attachments);
		}
		if ($hasAttachments || $hasMultipleTypes) {
			$msg[] = '';
			$msg[] = '--' . $boundary . '--';
			$msg[] = '';
		}
		return $msg;
	}

/**
 * Gets the text body types that are in this email message
 *
 * @return array Array of types.  Valid types are 'text' and 'html'
 */
	protected function _getTypes() {
		$types = array($this->_emailFormat);
		if ($this->_emailFormat == 'both') {
			$types = array('html', 'text');
		}
		return $types;
	}

/**
 * Build and set all the view properties needed to render the templated emails.
 * If there is no template set, the $content will be returned in a hash
 * of the text content types for the email.
 *
 * @param string $content The content passed in from send() in most cases.
 * @return array The rendered content with html and text keys.
 */
	protected function _renderTemplates($content) {
		$types = $this->_getTypes();
		$rendered = array();
		if (empty($this->_template)) {
			foreach ($types as $type) {
				$rendered[$type] = $this->_encodeString($content, $this->charset);
			}
			return $rendered;
		}
		$viewClass = $this->_viewRender;
		if ($viewClass !== 'View') {
			list($plugin, $viewClass) = pluginSplit($viewClass, true);
			$viewClass .= 'View';
			App::uses($viewClass, $plugin . 'View');
		}

		$View = new $viewClass(null);
		$View->viewVars = $this->_viewVars;
		$View->helpers = $this->_helpers;

		list($templatePlugin, $template) = pluginSplit($this->_template);
		list($layoutPlugin, $layout) = pluginSplit($this->_layout);
		if ($templatePlugin) {
			$View->plugin = $templatePlugin;
		} elseif ($layoutPlugin) {
			$View->plugin = $layoutPlugin;
		}
		if ($this->_theme) {
			$View->theme = $this->_theme;
		}

		foreach ($types as $type) {
			$View->set('content', $content);
			$View->hasRendered = false;
			$View->viewPath = $View->layoutPath = 'Emails' . DS . $type;

			$render = $View->render($template, $layout);
			$render = str_replace(array("\r\n", "\r"), "\n", $render);
			$rendered[$type] = $this->_encodeString($render, $this->charset);
		}
		return $rendered;
	}

/**
 * Return the Content-Transfer Encoding value based on the set charset
 *
 * @return void
 */
	protected function _getContentTransferEncoding() {
		$charset = strtoupper($this->charset);
		if (in_array($charset, $this->_charset8bit)) {
			return '8bit';
		}
		return '7bit';
	}

/**
 * Return charset value for Content-Type.
 *
 * Checks fallback/compatibility types which include workarounds
 * for legacy japanese character sets.
 *
 * @return string
 */
	protected function _getContentTypeCharset() {
		$charset = strtoupper($this->charset);
		if (array_key_exists($charset, $this->_contentTypeCharset)) {
			return strtoupper($this->_contentTypeCharset[$charset]);
		} else {
			return strtoupper($this->charset);
		}
	}

}
