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
 * The subject of the email
 *
 * @var string
 */
	protected $_subject = null;

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
	public $layout = 'default';

/**
 * Template for the view
 *
 * @var string
 */
	public $template = null;

/**
 * as per RFC2822 Section 2.1.1
 *
 * @var integer
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
 */
	public $lineFeed = PHP_EOL;

/**
 * What format should the email be sent in
 *
 * Supported formats:
 * - text
 * - html
 * - both
 *
 * @var string
 */
	public $sendAs = 'text';

/**
 * What method should the email be sent
 *
 * @var string
 */
	public $delivery = 'mail';

/**
 * charset the email is sent in
 *
 * @var string
 */
	public $charset = 'utf-8';

/**
 * List of files that should be attached to the email.
 *
 * Can be both absolute and relative paths
 *
 * @var array
 */
	public $attachments = array();

/**
 * The list of paths to search if an attachment isnt absolute
 *
 * @var array
 */
	public $filePaths = array();

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
 */
	protected function _setEmail($varName, $email, $name) {
		if (!is_array($email)) {
			if ($name === null) {
				$this->{$varName} = array($email => $email);
			} else {
				$this->{$varName} = array($email => $name);
			}
			return;
		}
		$list = array();
		foreach ($email as $key => $value) {
			if (is_int($key)) {
				$list[$value] = $value;
			} else {
				$list[$key] = $value;
			}
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
			if ($name === null) {
				$this->{$varName}[$email] = $email;
			} else {
				$this->{$varName}[$email] = $name;
			}
			return;
		}
		$list = array();
		foreach ($email as $key => $value) {
			if (is_int($key)) {
				$list[$value] = $value;
			} else {
				$list[$key] = $value;
			}
		}
		$this->{$varName} = array_merge($this->{$varName}, $list);
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
 * @param boolean $includeToAndCc
 * @param boolean $includeBcc
 * @param boolean $includeSubject
 * @return array
 */
	public function getHeaders($includeToAndCc = false, $includeBcc = false, $includeSubject = false) {
		if (!isset($this->_headers['X-Mailer'])) {
			$this->_headers['X-Mailer'] = Configure::read('Email.XMailer');
			if (empty($this->_headers['X-Mailer'])) {
				$this->_headers['X-Mailer'] = self::EMAIL_CLIENT;
			}
		}
		if (!isset($this->_headers['Date'])) {
			$this->_headers['Date'] = date(DATE_RFC2822);
		}
		if ($includeSubject) {
			$this->_headers['Subject'] = $this->_subject;
		}
		return $this->_headers;
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
	}

}
