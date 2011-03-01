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
 * @package       cake.libs
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', array('Validation', 'Multibyte'));

/**
 * Cake e-mail class.
 *
 * This class is used for handling Internet Message Format based
 * based on the standard outlined in http://www.rfc-editor.org/rfc/rfc2822.txt
 *
 * @package       cake.libs
 */
class CakeEmail {
/**
 * What mailer should EmailComponent identify itself as
 *
 * @constant EMAIL_CLIENT
 */
	const EMAIL_CLIENT = 'CakePHP Email Component';

/**
 * Recipient of the email
 *
 * @var string
 */
	protected $_to = array();

/**
 * The mail which the email is sent from
 *
 * @var string
 */
	protected $_from = array();

/**
 * The email the recipient will reply to
 *
 * @var string
 */
	protected $_replyTo = array();

/**
 * The read receipt email
 *
 * @var string
 */
	protected $_readReceipt = array();

/**
 * The mail that will be used in case of any errors like
 * - Remote mailserver down
 * - Remote user has exceeded his quota
 * - Unknown user
 *
 * @var string
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
 * as per RFC2822 Section 2.1.1
 *
 * @var integer
 */
	public $lineLength = 70;

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
	protected $_transportName = 'mail';

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
 * Set From
 *
 * @param mixed $email
 * @param string $name
 * @return void
 * @thrown SocketException
 */
	public function setFrom($email, $name = null) {
		$this->_setEmail1('_from', $email, $name, __('From requires only 1 email address.'));
	}

/**
 * Get the From information
 *
 * @return array Key is email, Value is name. If Key is equal of Value, the name is not specified
 */
	public function getFrom() {
		return $this->_from;
	}

/**
 * Set Reply-To
 *
 * @param mixed $email
 * @param string $name
 * @return void
 * @thrown SocketException
 */
	public function setReplyTo($email, $name = null) {
		$this->_setEmail1('_replyTo', $email, $name, __('Reply-To requires only 1 email address.'));
	}

/**
 * Get the ReplyTo information
 *
 * @return array Key is email, Value is name. If Key is equal of Value, the name is not specified
 */
	public function getReplyTo() {
		return $this->_replyTo;
	}

/**
 * Set Read Receipt (Disposition-Notification-To header)
 *
 * @param mixed $email
 * @param string $name
 * @return void
 * @thrown SocketException
 */
	public function setReadReceipt($email, $name = null) {
		$this->_setEmail1('_readReceipt', $email, $name, __('Disposition-Notification-To requires only 1 email address.'));
	}

/**
 * Get the Read Receipt (Disposition-Notification-To header) information
 *
 * @return array Key is email, Value is name. If Key is equal of Value, the name is not specified
 */
	public function getReadReceipt() {
		return $this->_readReceipt;
	}

/**
 * Set Return Path
 *
 * @param mixed $email
 * @param string $name
 * @return void
 * @thrown SocketException
 */
	public function setReturnPath($email, $name = null) {
		$this->_setEmail1('_returnPath', $email, $name, __('Return-Path requires only 1 email address.'));
	}

/**
 * Get the Return Path information
 *
 * @return array Key is email, Value is name. If Key is equal of Value, the name is not specified
 */
	public function getReturnPath() {
		return $this->_returnPath;
	}

/**
 * Set To
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return void
 */
	public function setTo($email, $name = null) {
		$this->_setEmail('_to', $email, $name);
	}

/**
 * Add To
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return void
 */
	public function addTo($email, $name = null) {
		$this->_addEmail('_to', $email, $name);
	}

/**
 * Get To
 *
 * @return array
 */
	public function getTo() {
		return $this->_to;
	}

/**
 * Set Cc
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return void
 */
	public function setCc($email, $name = null) {
		$this->_setEmail('_cc', $email, $name);
	}

/**
 * Add Cc
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return void
 */
	public function addCc($email, $name = null) {
		$this->_addEmail('_cc', $email, $name);
	}

/**
 * Get Cc
 *
 * @return array
 */
	public function getCc() {
		return $this->_cc;
	}

/**
 * Set Bcc
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return void
 */
	public function setBcc($email, $name = null) {
		$this->_setEmail('_bcc', $email, $name);
	}

/**
 * Add Bcc
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return void
 */
	public function addBcc($email, $name = null) {
		$this->_addEmail('_bcc', $email, $name);
	}

/**
 * Get Bcc
 *
 * @return array
 */
	public function getBcc() {
		return $this->_bcc;
	}

/**
 * Set email
 *
 * @param string $varName
 * @param mixed $email
 * @param mixed $name
 * @return void
 * @thrown SocketException
 */
	protected function _setEmail($varName, $email, $name) {
		if (!is_array($email)) {
			if (!Validation::email($email)) {
				throw new SocketException(__('Invalid email: "%s"', $email));
			}
			if ($name === null) {
				$name = $email;
			}
			$this->{$varName} = array($email => $name);
			return;
		}
		$list = array();
		foreach ($email as $key => $value) {
			if (is_int($key)) {
				$key = $value;
			}
			if (!Validation::email($key)) {
				throw new SocketException(__('Invalid email: "%s"', $key));
			}
			$list[$key] = $value;
		}
		$this->{$varName} = $list;
	}

/**
 * Set only 1 email
 *
 * @param string $varName
 * @param mixed $email
 * @param string $name
 * @param string $throwMessage
 * @return void
 * @thrown SocketExpceiton
 */
	protected function _setEmail1($varName, $email, $name, $throwMessage) {
		$current = $this->{$varName};
		$this->_setEmail($varName, $email, $name);
		if (count($this->{$varName}) !== 1) {
			$this->{$varName} = $current;
			throw new SocketException($throwMessage);
		}
	}

/**
 * Add email
 *
 * @param string $varName
 * @param mixed $email
 * @param mixed $name
 * @return void
 */
	protected function _addEmail($varName, $email, $name) {
		if (!is_array($email)) {
			if (!Validation::email($email)) {
				throw new SocketException(__('Invalid email: "%s"', $email));
			}
			if ($name === null) {
				$name = $email;
			}
			$this->{$varName}[$email] = $name;
			return;
		}
		$list = array();
		foreach ($email as $key => $value) {
			if (is_int($key)) {
				$key = $value;
			}
			if (!Validation::email($key)) {
				throw new SocketException(__('Invalid email: "%s"', $key));
			}
			$list[$key] = $value;
		}
		$this->{$varName} = array_merge($this->{$varName}, $list);
	}

/**
 * Set Subject
 *
 * @param string $subject
 * @return void
 */
	public function setSubject($subject) {
		$this->_subject = (string)$subject;
	}

/**
 * Get Subject
 *
 * @return string
 */
	public function getSubject() {
		return $this->_subject;
	}

/**
 * Sets headers for the message
 *
 * @param array Associative array containing headers to be set.
 * @return void
 * @thrown SocketException
 */
	public function setHeaders($headers) {
		if (!is_array($headers)) {
			throw new SocketException(__('$headers should be an array.'));
		}
		$this->_headers = $headers;
	}

/**
 * Add header for the message
 *
 * @param array $headers
 * @return void
 * @thrown SocketException
 */
	public function addHeaders($headers) {
		if (!is_array($headers)) {
			throw new SocketException(__('$headers should be an array.'));
		}
		$this->_headers = array_merge($this->_headers, $headers);
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
			'replyTo' => false,
			'readReceipt' => false,
			'returnPath' => false,
			'to' => false,
			'cc' => false,
			'bcc' => false,
			'subject' => false
		);
		$include += $defaults;

		$headers = $this->_headers;
		if (!isset($headers['X-Mailer'])) {
			$headers['X-Mailer'] = Configure::read('Email.XMailer');
			if (empty($headers['X-Mailer'])) {
				$headers['X-Mailer'] = self::EMAIL_CLIENT;
			}
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

		foreach (array('to', 'cc', 'bcc') as $var) {
			if ($include[$var]) {
				$classVar = '_' . $var;
				$headers[ucfirst($var)] = implode(', ', $this->_formatAddress($this->{$classVar}));
			}
		}

		if ($include['subject']) {
			$headers['Subject'] = $this->_subject;
		}

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
 * Set the layout and template
 *
 * @param string $layout
 * @param string $template
 * @return void
 */
	public function setLayout($layout, $template = null) {
		$this->_layout = (string)$layout;
		if ($template !== null) {
			$this->_template = (string)$template;
		}
	}

/**
 * Set the email format
 *
 * @param string $format
 * @return void
 * @thrown SocketException
 */
	public function setEmailFormat($format) {
		if (!in_array($format, $this->_emailFormatAvailable)) {
			throw new SocketException(__('Format not available.'));
		}
		$this->_emailFormat = $format;
	}

/**
 * Set transport name
 *
 * @param string $name
 * @return void
 */
	public function setTransport($name) {
		$this->_transportName = (string)$name;
	}

/**
 * Set Message-ID
 *
 * @param mixed $message True to generate a new Message-ID, False to ignore (not send in email), String to set as Message-ID
 * @return void
 * @thrown SocketException
 */
	public function setMessageID($message) {
		if (is_bool($message)) {
			$this->_messageId = $message;
		} else {
			if (!preg_match('/^\<.+@.+\>$/', $message)) {
				throw new SocketException(__('Invalid format to Message-ID. The text should be something like "<uuid@server.com>"'));
			}
			$this->_messageId = $message;
		}
	}

/**
 * Set attachments
 *
 * @param mixed $attachments String with the filename or array with filenames
 * @return void
 * @thrown SocketException
 */
	public function setAttachments($attachments) {
		$attachments = (array)$attachments;
		foreach ($attachments as &$attach) {
			$path = realpath($attach);
			if ($path === false) {
				throw new SocketException(__('File not found: "%s"', $attach));
			}
			$attach = $path;
		}
		$this->_attachments = $attachments;
	}

/**
 * Add attachments
 *
 * @param mixed $attachments String with the filename or array with filenames
 * @return void
 * @thrown SocketException
 */
	public function addAttachments($attachments) {
		$current = $this->_attachments;
		$this->setAttachments($attachments);
		$this->_attachments = array_unique(array_merge($current, $this->_attachments));
	}

/**
 * Get attachments
 *
 * @return array
 */
	public function getAttachments() {
		return $this->_attachments;
	}


/**
 * Send an email using the specified content, template and layout
 *
 * @return boolean Success
 */
	public function send() {
	}

/**
 * Reset all EmailComponent internal variables to be able to send out a new email.
 *
 * @return void
 */
	public function reset() {
		$this->_to = array();
		$this->_from = array();
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
		$this->_emailFormat = 'text';
		$this->_transportName = 'mail';
		$this->_attachments = array();
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

}
