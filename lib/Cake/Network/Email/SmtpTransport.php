<?php
/**
 * Send mail using SMTP protocol
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Network.Email
 * @since         CakePHP(tm) v 2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('AbstractTransport', 'Network/Email');
App::uses('CakeSocket', 'Network');

/**
 * Send mail using SMTP protocol
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
 * Content of email to return
 *
 * @var string
 */
	protected $_content;

/**
 * The response of the last sent SMTP command.
 *
 * @var array
 */
	protected $_lastResponse = array();

/**
 * Returns the response of the last sent SMTP command.
 *
 * A response consists of one or more lines containing a response
 * code and an optional response message text:
 * ```
 * array(
 *     array(
 *         'code' => '250',
 *         'message' => 'mail.example.com'
 *     ),
 *     array(
 *         'code' => '250',
 *         'message' => 'PIPELINING'
 *     ),
 *     array(
 *         'code' => '250',
 *         'message' => '8BITMIME'
 *     ),
 *     // etc...
 * )
 * ```
 *
 * @return array
 */
	public function getLastResponse() {
		return $this->_lastResponse;
	}

/**
 * Send mail
 *
 * @param CakeEmail $email CakeEmail
 * @return array
 * @throws SocketException
 */
	public function send(CakeEmail $email) {
		$this->_connect();
		$this->_auth();
		$this->_sendRcpt($email);
		$this->_sendData($email);
		$this->_disconnect();

		return $this->_content;
	}

/**
 * Set the configuration
 *
 * @param array $config Configuration options.
 * @return array Returns configs
 */
	public function config($config = null) {
		if ($config === null) {
			return $this->_config;
		}
		$default = array(
			'host' => 'localhost',
			'port' => 25,
			'timeout' => 30,
			'username' => null,
			'password' => null,
			'client' => null,
			'tls' => false,
			'ssl_allow_self_signed' => false
		);
		$this->_config = array_merge($default, $this->_config, $config);
		return $this->_config;
	}

/**
 * Parses and stores the reponse lines in `'code' => 'message'` format.
 *
 * @param array $responseLines Response lines to parse.
 * @return void
 */
	protected function _bufferResponseLines(array $responseLines) {
		$response = array();
		foreach ($responseLines as $responseLine) {
			if (preg_match('/^(\d{3})(?:[ -]+(.*))?$/', $responseLine, $match)) {
				$response[] = array(
					'code' => $match[1],
					'message' => isset($match[2]) ? $match[2] : null
				);
			}
		}
		$this->_lastResponse = array_merge($this->_lastResponse, $response);
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
			if ($this->_config['tls']) {
				$this->_smtpSend("STARTTLS", '220');
				$this->_socket->enableCrypto('tls');
				$this->_smtpSend("EHLO {$host}", '250');
			}
		} catch (SocketException $e) {
			if ($this->_config['tls']) {
				throw new SocketException(__d('cake_dev', 'SMTP server did not accept the connection or trying to connect to non TLS SMTP server using TLS.'));
			}
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
			$replyCode = $this->_smtpSend('AUTH LOGIN', '334|500|502|504');
			if ($replyCode == '334') {
				try {
					$this->_smtpSend(base64_encode($this->_config['username']), '334');
				} catch (SocketException $e) {
					throw new SocketException(__d('cake_dev', 'SMTP server did not accept the username.'));
				}
				try {
					$this->_smtpSend(base64_encode($this->_config['password']), '235');
				} catch (SocketException $e) {
					throw new SocketException(__d('cake_dev', 'SMTP server did not accept the password.'));
				}
			} elseif ($replyCode == '504') {
				throw new SocketException(__d('cake_dev', 'SMTP authentication method not allowed, check if SMTP server requires TLS.'));
			} else {
				throw new SocketException(__d('cake_dev', 'AUTH command not recognized or not implemented, SMTP server may not require authentication.'));
			}
		}
	}

/**
 * Prepares the `MAIL FROM` SMTP command.
 *
 * @param string $email The email address to send with the command.
 * @return string
 */
	protected function _prepareFromCmd($email) {
		return 'MAIL FROM:<' . $email . '>';
	}

/**
 * Prepares the `RCPT TO` SMTP command.
 *
 * @param string $email The email address to send with the command.
 * @return string
 */
	protected function _prepareRcptCmd($email) {
		return 'RCPT TO:<' . $email . '>';
	}

/**
 * Prepares the `from` email address.
 *
 * @param CakeEmail $email CakeEmail
 * @return array
 */
	protected function _prepareFromAddress($email) {
		$from = $email->returnPath();
		if (empty($from)) {
			$from = $email->from();
		}
		return $from;
	}

/**
 * Prepares the recipient email addresses.
 *
 * @param CakeEmail $email CakeEmail
 * @return array
 */
	protected function _prepareRecipientAddresses(CakeEmail $email) {
		$to = $email->to();
		$cc = $email->cc();
		$bcc = $email->bcc();
		return array_merge(array_keys($to), array_keys($cc), array_keys($bcc));
	}

/**
 * Prepares the message headers.
 *
 * @param CakeEmail $email CakeEmail
 * @return array
 */
	protected function _prepareMessageHeaders(CakeEmail $email) {
		return $email->getHeaders(array('from', 'sender', 'replyTo', 'readReceipt', 'to', 'cc', 'subject'));
	}

/**
 * Prepares the message body.
 *
 * @param CakeEmail $email CakeEmail
 * @return string
 */
	protected function _prepareMessage(CakeEmail $email) {
		$lines = $email->message();
		$messages = array();
		foreach ($lines as $line) {
			if ((!empty($line)) && ($line[0] === '.')) {
				$messages[] = '.' . $line;
			} else {
				$messages[] = $line;
			}
		}
		return implode("\r\n", $messages);
	}

/**
 * Send emails
 *
 * @param CakeEmail $email CakeEmail
 * @return void
 * @throws SocketException
 */
	protected function _sendRcpt(CakeEmail $email) {
		$from = $this->_prepareFromAddress($email);
		$this->_smtpSend($this->_prepareFromCmd(key($from)));

		$emails = $this->_prepareRecipientAddresses($email);
		foreach ($emails as $email) {
			$this->_smtpSend($this->_prepareRcptCmd($email));
		}
	}

/**
 * Send Data
 *
 * @param CakeEmail $email CakeEmail
 * @return void
 * @throws SocketException
 */
	protected function _sendData(CakeEmail $email) {
		$this->_smtpSend('DATA', '354');

		$headers = $this->_headersToString($this->_prepareMessageHeaders($email));
		$message = $this->_prepareMessage($email);

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
 * @param string|null $data Data to be sent to SMTP server
 * @param string|bool $checkCode Code to check for in server response, false to skip
 * @return string|null The matched code, or null if nothing matched
 * @throws SocketException
 */
	protected function _smtpSend($data, $checkCode = '250') {
		$this->_lastResponse = array();

		if ($data !== null) {
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
			$responseLines = explode("\r\n", rtrim($response, "\r\n"));
			$response = end($responseLines);

			$this->_bufferResponseLines($responseLines);

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
