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
App::import('Lib', 'email/AbstractTransport');

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
 * View for render
 *
 * @var string
 */
	protected $_viewRender = 'View';

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
 * @thrown SocketException
 */
	public function from($email = null, $name = null) {
		if ($email === null) {
			return $this->_from;
		}
		$this->_setEmailSingle('_from', $email, $name, __('From requires only 1 email address.'));
	}

/**
 * Reply-To
 *
 * @param mixed $email
 * @param string $name
 * @return mixed
 * @thrown SocketException
 */
	public function replyTo($email = null, $name = null) {
		if ($email === null) {
			return $this->_replyTo;
		}
		$this->_setEmailSingle('_replyTo', $email, $name, __('Reply-To requires only 1 email address.'));
	}

/**
 * Read Receipt (Disposition-Notification-To header)
 *
 * @param mixed $email
 * @param string $name
 * @return mixed
 * @thrown SocketException
 */
	public function readReceipt($email = null, $name = null) {
		if ($email === null) {
			return $this->_readReceipt;
		}
		$this->_setEmailSingle('_readReceipt', $email, $name, __('Disposition-Notification-To requires only 1 email address.'));
	}

/**
 * Return Path
 *
 * @param mixed $email
 * @param string $name
 * @return mixed
 * @thrown SocketException
 */
	public function returnPath($email = null, $name = null) {
		if ($email === null) {
			return $this->_returnPath;
		}
		$this->_setEmailSingle('_returnPath', $email, $name, __('Return-Path requires only 1 email address.'));
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
 * Bcc
 *
 * @param mixed $email String with email, Array with email as key, name as value or email as value (without name)
 * @param string $name
 * @return void
 */
	public function bcc($email = null, $name = null) {
		if ($email === null) {
			return $this->_bcc;
		}
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
	protected function _setEmailSingle($varName, $email, $name, $throwMessage) {
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
 * @return mixed
 */
	public function subject($subject = null) {
		if ($subject === null) {
			return $this->_subject;
		}
		$this->_subject = (string)$subject;
	}

/**
 * Sets eaders for the message
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

		if (!empty($this->_attachments)) {
			$this->_createBoundary();
			$headers['MIME-Version'] = '1.0';
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
 * Layout and template
 *
 * @param string $layout
 * @param string $template
 * @return mixed
 */
	public function layout($layout = null, $template = null) {
		if ($layout === null) {
			return array(
				'layout' => $this->_layout,
				'template' => $this->_template
			);
		}
		$this->_layout = (string)$layout;
		if ($template !== null) {
			$this->_template = (string)$template;
		}
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
	}

/**
 * Email format
 *
 * @param string $format
 * @return mixed
 * @thrown SocketException
 */
	public function emailFormat($format = null) {
		if ($format === null) {
			return $this->_emailFormat;
		}
		if (!in_array($format, $this->_emailFormatAvailable)) {
			throw new SocketException(__('Format not available.'));
		}
		$this->_emailFormat = $format;
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
	}

/**
 * Message-ID
 *
 * @param mixed $message True to generate a new Message-ID, False to ignore (not send in email), String to set as Message-ID
 * @return mixed
 * @thrown SocketException
 */
	public function messageID($message = null) {
		if ($message === null) {
			return $this->_messageId;
		}
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
 * Attachments
 *
 * @param mixed $attachments String with the filename or array with filenames
 * @return mixed
 * @thrown SocketException
 */
	public function attachments($attachments = null) {
		if ($attachments === null) {
			return $this->_attachments;
		}
		$attach = array();
		foreach ((array)$attachments as $name => $file) {
			$path = realpath($file);
			if ($path === false) {
				throw new SocketException(__('File not found: "%s"', $attach));
			}
			if (is_int($name)) {
				$name = basename($path);
			}
			$attach[$name] = $path;
		}
		$this->_attachments = $attach;
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
		$this->attachments($attachments);
		$this->_attachments = array_merge($current, $this->_attachments);
	}

/**
 * Get generated message (used by transport classes)
 *
 * @return array
 */
	public function message() {
		return $this->_message;
	}

/**
 * Configuration to use when send email
 *
 * @param mixed $config String with configuration name (from email.php), array with config or null to return current config
 * @return string
 */
	public function config($config = null) {
		if (!empty($config)) {
			if (is_array($config)) {
				$this->_config = $config;
			} else {
				$this->_config = (string)$config;
			}
		}
		return $this->_config;
	}

/**
 * Send an email using the specified content, template and layout
 *
 * @return boolean Success
 * @thrown SocketExpcetion
 */
	public function send($content = null) {
		if (is_string($this->_config)) {
			if (!config('email')) {
				throw new SocketException(__('%s not found.', APP . DS . 'email.php'));
			}
			$configs = new EMAIL_CONFIG();
			if (!isset($configs->{$this->_config})) {
				throw new SocketException(__('Unknown email configuration "%s".', $this->_config));
			}
			$config = $configs->{$this->_config};
		} else {
			$config = $this->_config;
		}

		if (empty($this->_from)) {
			if (!empty($config['from'])) {
				$this->to($config['from']);
			} else {
				throw new SocketException(__('From is not specified.'));
			}
		}
		if (empty($this->_to) && empty($this->_cc) && empty($this->_bcc)) {
			throw new SocketExpcetion(__('You need specify one destination on to, cc or bcc.'));
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
		}

		if (!is_null($this->_boundary)) {
			$this->_message[] = '';
			$this->_message[] = '--' . $this->_boundary . '--';
			$this->_message[] = '';
		}

		$transportClassname = Inflector::camelize($this->_transportName) . 'Transport';
		if (!App::import('Lib', 'email/' . $transportClassname)) {
			throw new SocketException(__('Class "%s" not found.', $transportClassname));
		} elseif (!method_exists($transportClassname, 'send')) {
			throw new SocketException(__('The "%s" do not have send method.', $transportClassname));
		}

		$transport = new $transportClassname($config);
		return $transport->send($this);
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
		$this->_viewRender = 'View';
		$this->_textMessage = '';
		$this->_htmlMessage = '';
		$this->_message = '';
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
	protected function _createboundary() {
		$this->_boundary = md5(uniqid(time()));
	}

/**
 * Attach files by adding file contents inside boundaries.
 *
 * @return void
 */
	protected function _attachFiles() {
		foreach ($this->_attachments as $filename => $file) {
			$handle = fopen($file, 'rb');
			$data = fread($handle, filesize($file));
			$data = chunk_split(base64_encode($data)) ;
			fclose($handle);

			$this->_message[] = '--' . $this->_boundary;
			$this->_message[] = 'Content-Type: application/octet-stream';
			$this->_message[] = 'Content-Transfer-Encoding: base64';
			$this->_message[] = 'Content-Disposition: attachment; filename="' . $filename . '"';
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
			list($plugin, $viewClass) = pluginSplit($viewClass);
			$viewClass = $viewClass . 'View';
			App::import('View', $this->_viewRender);
		}

		$View = new $viewClass(null);
		$View->layout = $this->_layout;
		$msg = array();

		$content = implode("\n", $content);

		if ($this->_emailFormat === 'both') {
			$htmlContent = $content;
			if (!empty($this->_attachments)) {
				$msg[] = '--' . $this->_boundary;
				$msg[] = 'Content-Type: multipart/alternative; boundary="alt-' . $this->_boundary . '"';
				$msg[] = '';
			}
			$msg[] = '--alt-' . $this->_boundary;
			$msg[] = 'Content-Type: text/plain; charset=' . $this->charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$content = $View->element('email' . DS . 'text' . DS . $this->_template, array('content' => $content), true);
			$View->layoutPath = 'email' . DS . 'text';
			$content = explode("\n", $this->_textMessage = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($content)));

			$msg = array_merge($msg, $content);

			$msg[] = '';
			$msg[] = '--alt-' . $this->_boundary;
			$msg[] = 'Content-Type: text/html; charset=' . $this->charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$htmlContent = $View->element('email' . DS . 'html' . DS . $this->_template, array('content' => $htmlContent), true);
			$View->layoutPath = 'email' . DS . 'html';
			$htmlContent = explode("\n", $this->_htmlMessage = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($htmlContent)));
			$msg = array_merge($msg, $htmlContent);
			$msg[] = '';
			$msg[] = '--alt-' . $this->_boundary . '--';
			$msg[] = '';

			ClassRegistry::removeObject('view');
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

		$content = $View->element('email' . DS . $this->_emailFormat . DS . $this->_template, array('content' => $content), true);
		$View->layoutPath = 'email' . DS . $this->_emailFormat;
		$content = explode("\n", $rendered = str_replace(array("\r\n", "\r"), "\n", $View->renderLayout($content)));

		if ($this->_emailFormat === 'html') {
			$this->_htmlMessage = $rendered;
		} else {
			$this->_textMessage = $rendered;
		}

		$msg = array_merge($msg, $content);
		ClassRegistry::removeObject('view');

		return $msg;
	}

}
