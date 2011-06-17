<?php
/**
 * Emulates the email sending process for testing purposes
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
 * @package       cake.libs.email
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Debug Transport class, useful for emulate the email sending process and inspect the resulted
 * email message before actually send it during development
 *
 * @package Cake.Network.Email
 */
class DebugTransport extends AbstractTransport {

/**
 * Send mail
 *
 * @params object $email CakeEmail
 * @return boolean
 */
	public function send(CakeEmail $email) {
		$headers = $email->getHeaders(array(
			'from' => true,
			'sender' => true,
			'replyTo' => true,
			'readReceipt' => true,
			'returnPath' => true,
			'to' => true,
			'cc' => true,
			'bcc' => true,
			'subject' => true
		));
		$headers = $this->_headersToString($headers);
		return $headers . "\n\n" . implode((array)$email->message(), "\n");
	}

}
