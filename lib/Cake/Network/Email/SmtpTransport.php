<?php
/**
 * Send mail using SMTP protocol
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
App::uses('CakeSocket', 'Network');

/**
 * SendEmail class
 *
 * @package       Cake.Network.Email
 */
class SmtpTransport extends AbstractTransport {

/**
 * Socket to SMTP server
 *
 * @var CakeSocket
 */
	protected $_socket;

/**
 * CakeEmail
 *
 * @var CakeEmail
 */
	protected $_cakeEmail;

/**
 * Content of email to return
 *
 * @var string
 */
	protected $_content;

/**
 * Send mail
 *
 * @param CakeEmail $email CakeEmail
 * @return array
 * @throws SocketException
 */
	public function send(CakeEmail $email) {
		$this->_cakeEmail = $email;

		$this->_connect();
		$this->_auth();
		$this->_sendRcpt();
		$this->_sendData();
		$this->_disconnect();

		return $this->_content;
	}

/**
 * Set the configuration
 *
 * @param array $config
 * @return void
 */
	public function config($config = array()) {
		$default = array(
			'host' => 'localhost',
			'port' => 25,
			'timeout' => 30,
			'username' => null,
			'password' => null,
			'client' => null
		);
		$this->_config = $config + $default;
	}

/**
 * Connect to SMTP Server
 *
 * @return void
 * @throws SocketException
 */
	protected function _connect() {
		$this->_generateSocket();
		if (!$this->_socket->connect()) {
			throw new SocketException(__d('cake_dev', 'Unable to connect to SMTP server.'));
		}
		$this->_smtpSend(null, '220');

		if (isset($this->_config['client'])) {
			$host = $this->_config['client'];
		} elseif ($httpHost = env('HTTP_HOST')) {
			list($host) = explode(':', $httpHost);
		} else {
			$host = 'localhost';
		}

		try {
			$this->_smtpSend("EHLO {$host}", '250');
		} catch (SocketException $e) {
			try {
				$this->_smtpSend("HELO {$host}", '250');
			} catch (SocketException $e2) {
				throw new SocketException(__d('cake_dev', 'SMTP server did not accept the connection.'));
			}
		}
	}

/**
 * Send authentication
 *
 * @return void
 * @throws SocketException
 */
	protected function _auth() {
		if (isset($this->_config['username']) && isset($this->_config['password'])) {
			$authRequired = $this->_smtpSend('AUTH LOGIN', '334|503');
			if ($authRequired == '334') {
				if (!$this->_smtpSend(base64_encode($this->_config['username']), '334')) {
					throw new SocketException(__d('cake_dev', 'SMTP server did not accept the username.'));
				}
				if (!$this->_smtpSend(base64_encode($this->_config['password']), '235')) {
					throw new SocketException(__d('cake_dev', 'SMTP server did not accept the password.'));
				}
			} elseif ($authRequired != '503') {
				throw new SocketException(__d('cake_dev', 'SMTP does not require authentication.'));
			}
		}
	}

/**
 * Send emails
 *
 * @return void
 * @throws SocketException
 */
	protected function _sendRcpt() {
		$from = $this->_cakeEmail->from();
		$this->_smtpSend('MAIL FROM:<' . key($from) . '>');

		$to = $this->_cakeEmail->to();
		$cc = $this->_cakeEmail->cc();
		$bcc = $this->_cakeEmail->bcc();
		$emails = array_merge(array_keys($to), array_keys($cc), array_keys($bcc));
		foreach ($emails as $email) {
			$this->_smtpSend('RCPT TO:<' . $email . '>');
		}
	}

/**
 * Send Data
 *
 * @return void
 * @throws SocketException
 */
	protected function _sendData() {
		$this->_smtpSend('DATA', '354');

		$headers = $this->_cakeEmail->getHeaders(array('from', 'sender', 'replyTo', 'readReceipt', 'returnPath', 'to', 'cc', 'subject'));
		$headers = $this->_headersToString($headers);
		$message = implode("\r\n", $this->_cakeEmail->message());
		$this->_smtpSend($headers . "\r\n\r\n" . $message . "\r\n\r\n\r\n.");
		$this->_content = array('headers' => $headers, 'message' => $message);
	}

/**
 * Disconnect
 *
 * @return void
 * @throws SocketException
 */
	protected function _disconnect() {
		$this->_smtpSend('QUIT', false);
		$this->_socket->disconnect();
	}

/**
 * Helper method to generate socket
 *
 * @return void
 * @throws SocketException
 */
	protected function _generateSocket() {
		$this->_socket = new CakeSocket($this->_config);
	}

/**
 * Protected method for sending data to SMTP connection
 *
 * @param string $data data to be sent to SMTP server
 * @param mixed $checkCode code to check for in server response, false to skip
 * @return void
 * @throws SocketException
 */
	protected function _smtpSend($data, $checkCode = '250') {
		if (!is_null($data)) {
			$this->_socket->write($data . "\r\n");
		}
		while ($checkCode !== false) {
			$response = '';
			$startTime = time();
			while (substr($response, -2) !== "\r\n" && ((time() - $startTime) < $this->_config['timeout'])) {
				$response .= $this->_socket->read();
			}
			if (substr($response, -2) !== "\r\n") {
				throw new SocketException(__d('cake_dev', 'SMTP timeout.'));
			}
			$response = end(explode("\r\n", rtrim($response, "\r\n")));

			if (preg_match('/^(' . $checkCode . ')(.)/', $response, $code)) {
				if ($code[2] === '-') {
					continue;
				}
				return $code[1];
			}
			throw new SocketException(__d('cake_dev', 'SMTP Error: %s', $response));
		}
	}

}
