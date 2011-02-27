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
 * What mailer should EmailComponent identify itself as
 *
 * @var string
 */
	public $xMailer = 'CakePHP Email Component';

/**
 * The list of paths to search if an attachment isnt absolute
 *
 * @var array
 */
	public $filePaths = array();

/**
 * Temporary store of message header lines
 *
 * @var array
 */
	protected $_header = array();

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
 * Sets headers for the message
 *
 * @param array Associative array containing headers to be set.
 * @return void
 */
	public function header($headers) {
		foreach ($headers as $header => $value) {
			$this->_header[] = sprintf('%s: %s', trim($header), trim($value));
		}
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
