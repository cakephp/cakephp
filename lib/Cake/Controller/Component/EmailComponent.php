<?php
/**
 * Email Component
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
 * @package       Cake.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.3467
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Component', 'Controller');
App::uses('Multibyte', 'I18n');
App::uses('CakeEmail', 'Network/Email');

/**
 * EmailComponent
 *
 * This component is used for handling Internet Message Format based
 * based on the standard outlined in http://www.rfc-editor.org/rfc/rfc2822.txt
 *
 * @package       Cake.Controller.Component
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/email.html
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/email.html
 * @deprecated Use Network/CakeEmail
 */
class EmailComponent extends Component {

/**
 * Recipient of the email
 *
 * @var string
 */
	public $to = null;

/**
 * The mail which the email is sent from
 *
 * @var string
 */
	public $from = null;

/**
 * The email the recipient will reply to
 *
 * @var string
 */
	public $replyTo = null;

/**
 * The read receipt email
 *
 * @var string
 */
	public $readReceipt = null;

/**
 * The mail that will be used in case of any errors like
 * - Remote mailserver down
 * - Remote user has exceeded his quota
 * - Unknown user
 *
 * @var string
 */
	public $return = null;

/**
 * Carbon Copy
 *
 * List of email's that should receive a copy of the email.
 * The Recipient WILL be able to see this list
 *
 * @var array
 */
	public $cc = array();

/**
 * Blind Carbon Copy
 *
 * List of email's that should receive a copy of the email.
 * The Recipient WILL NOT be able to see this list
 *
 * @var array
 */
	public $bcc = array();

/**
 * The date to put in the Date: header. This should be a date
 * conforming with the RFC2822 standard. Leave null, to have
 * today's date generated.
 *
 * @var string
 */
	public $date = null;

/**
 * The subject of the email
 *
 * @var string
 */
	public $subject = null;

/**
 * Associative array of a user defined headers
 * Keys will be prefixed 'X-' as per RFC2822 Section 4.7.5
 *
 * @var array
 */
	public $headers = array();

/**
 * List of additional headers
 *
 * These will NOT be used if you are using safemode and mail()
 *
 * @var string
 */
	public $additionalParams = null;

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
 * What method should the email be sent by
 *
 * Supported methods:
 * - mail
 * - smtp
 * - debug
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
 * What mailer should EmailComponent identify itself as
 *
 * @var string
 */
	public $xMailer = 'CakePHP Email Component';

/**
 * The list of paths to search if an attachment isn't absolute
 *
 * @var array
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
 */
	public $smtpOptions = array();

/**
 * Contains the rendered plain text message if one was sent.
 *
 * @var string
 */
	public $textMessage = null;

/**
 * Contains the rendered HTML message if one was sent.
 *
 * @var string
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
 */
	public $messageId = true;

/**
 * Controller reference
 *
 * @var Controller
 */
	protected $_controller = null;

/**
 * Constructor
 *
 * @param ComponentCollection $collection A ComponentCollection this component can use to lazy load its components
 * @param array $settings Array of configuration settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$this->_controller = $collection->getController();
		parent::__construct($collection, $settings);
	}

/**
 * Initialize component
 *
 * @param Controller $controller Instantiating controller
 * @return void
 */
	public function initialize(Controller $controller) {
		if (Configure::read('App.encoding') !== null) {
			$this->charset = Configure::read('App.encoding');
		}
	}

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
		$lib = new CakeEmail();
		$lib->charset = $this->charset;

		$lib->from($this->_formatAddresses((array)$this->from));
		if (!empty($this->to)) {
			$lib->to($this->_formatAddresses((array)$this->to));
		}
		if (!empty($this->cc)) {
			$lib->cc($this->_formatAddresses((array)$this->cc));
		}
		if (!empty($this->bcc)) {
			$lib->bcc($this->_formatAddresses((array)$this->bcc));
		}
		if (!empty($this->replyTo)) {
			$lib->replyTo($this->_formatAddresses((array)$this->replyTo));
		}
		if (!empty($this->return)) {
			$lib->returnPath($this->_formatAddresses((array)$this->return));
		}
		if (!empty($readReceipt)) {
			$lib->readReceipt($this->_formatAddresses((array)$this->readReceipt));
		}

		$lib->subject($this->subject)->messageID($this->messageId);
		$lib->helpers($this->_controller->helpers);

		$headers = array('X-Mailer' => $this->xMailer);
		foreach ($this->headers as $key => $value) {
			$headers['X-' . $key] = $value;
		}
		if ($this->date != false) {
			$headers['Date'] = $this->date;
		}
		$lib->setHeaders($headers);

		if ($template) {
			$this->template = $template;
		}
		if ($layout) {
			$this->layout = $layout;
		}
		$lib->template($this->template, $this->layout)->viewVars($this->_controller->viewVars)->emailFormat($this->sendAs);

		if (!empty($this->attachments)) {
			$lib->attachments($this->_formatAttachFiles());
		}

		$lib->transport(ucfirst($this->delivery));
		if ($this->delivery === 'mail') {
			$lib->config(array('eol' => $this->lineFeed, 'additionalParameters' => $this->additionalParams));
		} elseif ($this->delivery === 'smtp') {
			$lib->config($this->smtpOptions);
		} else {
			$lib->config(array());
		}

		$sent = $lib->send($content);

		$this->htmlMessage = $lib->message(CakeEmail::MESSAGE_HTML);
		if (empty($this->htmlMessage)) {
			$this->htmlMessage = null;
		}
		$this->textMessage = $lib->message(CakeEmail::MESSAGE_TEXT);
		if (empty($this->textMessage)) {
			$this->textMessage = null;
		}

		$this->_header = array();
		$this->_message = array();

		return $sent;
	}

/**
 * Reset all EmailComponent internal variables to be able to send out a new email.
 *
 * @return void
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
		$this->delivery = 'mail';
	}

/**
 * Format the attach array
 *
 * @return array
 */
	protected function _formatAttachFiles() {
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
		return $files;
	}

/**
 * Find the specified attachment in the list of file paths
 *
 * @param string $attachment Attachment file name to find
 * @return string Path to located file
 */
	protected function _findFiles($attachment) {
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
 * Format addresses to be an array with email as key and alias as value
 *
 * @param array $addresses
 * @return array
 */
	protected function _formatAddresses($addresses) {
		$formatted = array();
		foreach ($addresses as $address) {
			if (preg_match('/((.*))?\s?<(.+)>/', $address, $matches) && !empty($matches[2])) {
				$formatted[$this->_strip($matches[3])] = $matches[2];
			} else {
				$address = $this->_strip($address);
				$formatted[$address] = $address;
			}
		}
		return $formatted;
	}

/**
 * Remove certain elements (such as bcc:, to:, %0a) from given value.
 * Helps prevent header injection / manipulation on user content.
 *
 * @param string $value Value to strip
 * @param boolean $message Set to true to indicate main message content
 * @return string Stripped value
 */
	protected function _strip($value, $message = false) {
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
