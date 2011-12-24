<?php
/**
 * Send mail using mail() function
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

/**
 * Send mail using mail() function
 *
 * @package       Cake.Network.Email
 */
class MailTransport extends AbstractTransport {

/**
 * Send mail
 *
 * @param CakeEmail $email CakeEmail
 * @return array
 */
	public function send(CakeEmail $email) {
		$eol = PHP_EOL;
		if (isset($this->_config['eol'])) {
			$eol = $this->_config['eol'];
		}
		$headers = $email->getHeaders(array('from', 'sender', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'bcc'));
		$to = $headers['To'];
		unset($headers['To']);
		$headers = $this->_headersToString($headers, $eol);
		$message = implode($eol, $email->message());
		if (ini_get('safe_mode') || !isset($this->_config['additionalParameters'])) {
			if (!@mail($to, $email->subject(), $message, $headers)) {
				throw new SocketException(__d('cake_dev', 'Could not send email.'));
			}
		} elseif (!@mail($to, $email->subject(), $message, $headers, $this->_config['additionalParameters'])) {
			throw new SocketException(__d('cake_dev', 'Could not send email.'));
		}
		return array('headers' => $headers, 'message' => $message);
	}

}
