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
 * @package       cake.libs.email
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Mail class
 *
 * @package       cake.libs.email
 */
class MailTransport extends AbstractTransport {

/**
 * Send mail
 *
 * @params object $email CakeEmail
 * @return boolean
 */
	public function send(CakeEmail $email) {
		$eol = Configure::read('Email.Mail.EOL');
		if (!$eol) {
			$eol = PHP_EOL;
		}
		$header = $this->_headersToString($email->getHeaders(true, true, false), $eol);
		$message = implode($eol, $email->getMessage());
		$to = key($email->getTo());
		if (ini_get('safe_mode')) {
			return @mail($to, $email->getSubject(), $message, $header);
		}
		return @mail($to, $email->getSubject(), $message, $header, (string)Configure::read('Email.Mail.AdditionalParameters'));
	}

}
