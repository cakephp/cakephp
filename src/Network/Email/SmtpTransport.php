<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network\Email;

use Cake\Network\Exception\SocketException;
use Cake\Network\Socket;

/**
 * Send mail using SMTP protocol
 */
class SmtpTransport extends AbstractTransport
{

    /**
     * Default config for this class
     *
     * @var array
     */
    protected $_defaultConfig = [
        'host' => 'localhost',
        'port' => 25,
        'timeout' => 30,
        'username' => null,
        'password' => null,
        'client' => null,
        'tls' => false,
        'keepAlive' => false
    ];

    /**
     * Socket to SMTP server
     *
     * @var \Cake\Network\Socket
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
    protected $_lastResponse = [];

    /**
     * Destructor
     *
     * Tries to disconnect to ensure that the connection is being
     * terminated properly before the socket gets closed.
     */
    public function __destruct()
    {
        try {
            $this->disconnect();
        } catch (\Exception $e) {
// avoid fatal error on script termination
        }
    }

    /**
     * Connect to the SMTP server.
     *
     * This method tries to connect only in case there is no open
     * connection available already.
     *
     * @return void
     */
    public function connect()
    {
        if (!$this->connected()) {
            $this->_connect();
            $this->_auth();
        }
    }

    /**
     * Check whether an open connection to the SMTP server is available.
     *
     * @return bool
     */
    public function connected()
    {
        return $this->_socket !== null && $this->_socket->connected;
    }

    /**
     * Disconnect from the SMTP server.
     *
     * This method tries to disconnect only in case there is an open
     * connection available.
     *
     * @return void
     */
    public function disconnect()
    {
        if ($this->connected()) {
            $this->_disconnect();
        }
    }

    /**
     * Returns the response of the last sent SMTP command.
     *
     * A response consists of one or more lines containing a response
     * code and an optional response message text:
     * ```
     * [
     *     [
     *         'code' => '250',
     *         'message' => 'mail.example.com'
     *     ],
     *     [
     *         'code' => '250',
     *         'message' => 'PIPELINING'
     *     ],
     *     [
     *         'code' => '250',
     *         'message' => '8BITMIME'
     *     ],
     *     // etc...
     * ]
     * ```
     *
     * @return array
     */
    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    /**
     * Send mail
     *
     * @param \Cake\Network\Email\Email $email Email instance
     * @return array
     * @throws \Cake\Network\Exception\SocketException
     */
    public function send(Email $email)
    {
        if (!$this->connected()) {
            $this->_connect();
            $this->_auth();
        } else {
            $this->_smtpSend('RSET');
        }

        $this->_sendRcpt($email);
        $this->_sendData($email);

        if (!$this->_config['keepAlive']) {
            $this->_disconnect();
        }

        return $this->_content;
    }

    /**
     * Parses and stores the response lines in `'code' => 'message'` format.
     *
     * @param array $responseLines Response lines to parse.
     * @return void
     */
    protected function _bufferResponseLines(array $responseLines)
    {
        $response = [];
        foreach ($responseLines as $responseLine) {
            if (preg_match('/^(\d{3})(?:[ -]+(.*))?$/', $responseLine, $match)) {
                $response[] = [
                    'code' => $match[1],
                    'message' => isset($match[2]) ? $match[2] : null
                ];
            }
        }
        $this->_lastResponse = array_merge($this->_lastResponse, $response);
    }

    /**
     * Connect to SMTP Server
     *
     * @return void
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _connect()
    {
        $this->_generateSocket();
        if (!$this->_socket->connect()) {
            throw new SocketException('Unable to connect to SMTP server.');
        }
        $this->_smtpSend(null, '220');

        $config = $this->_config;

        if (isset($config['client'])) {
            $host = $config['client'];
        } elseif ($httpHost = env('HTTP_HOST')) {
            list($host) = explode(':', $httpHost);
        } else {
            $host = 'localhost';
        }

        try {
            $this->_smtpSend("EHLO {$host}", '250');
            if ($config['tls']) {
                $this->_smtpSend("STARTTLS", '220');
                $this->_socket->enableCrypto('tls');
                $this->_smtpSend("EHLO {$host}", '250');
            }
        } catch (SocketException $e) {
            if ($config['tls']) {
                throw new SocketException('SMTP server did not accept the connection or trying to connect to non TLS SMTP server using TLS.');
            }
            try {
                $this->_smtpSend("HELO {$host}", '250');
            } catch (SocketException $e2) {
                throw new SocketException('SMTP server did not accept the connection.');
            }
        }
    }

    /**
     * Send authentication
     *
     * @return void
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _auth()
    {
        if (isset($this->_config['username']) && isset($this->_config['password'])) {
            $replyCode = $this->_smtpSend('AUTH LOGIN', '334|500|502|504');
            if ($replyCode == '334') {
                try {
                    $this->_smtpSend(base64_encode($this->_config['username']), '334');
                } catch (SocketException $e) {
                    throw new SocketException('SMTP server did not accept the username.');
                }
                try {
                    $this->_smtpSend(base64_encode($this->_config['password']), '235');
                } catch (SocketException $e) {
                    throw new SocketException('SMTP server did not accept the password.');
                }
            } elseif ($replyCode == '504') {
                throw new SocketException('SMTP authentication method not allowed, check if SMTP server requires TLS.');
            } else {
                throw new SocketException('AUTH command not recognized or not implemented, SMTP server may not require authentication.');
            }
        }
    }

    /**
     * Prepares the `MAIL FROM` SMTP command.
     *
     * @param string $email The email address to send with the command.
     * @return string
     */
    protected function _prepareFromCmd($email)
    {
        return 'MAIL FROM:<' . $email . '>';
    }

    /**
     * Prepares the `RCPT TO` SMTP command.
     *
     * @param string $email The email address to send with the command.
     * @return string
     */
    protected function _prepareRcptCmd($email)
    {
        return 'RCPT TO:<' . $email . '>';
    }

    /**
     * Prepares the `from` email address.
     *
     * @param \Cake\Network\Email\Email $email Email instance
     * @return array
     */
    protected function _prepareFromAddress($email)
    {
        $from = $email->returnPath();
        if (empty($from)) {
            $from = $email->from();
        }
        return $from;
    }

    /**
     * Prepares the recipient email addresses.
     *
     * @param \Cake\Network\Email\Email $email Email instance
     * @return array
     */
    protected function _prepareRecipientAddresses($email)
    {
        $to = $email->to();
        $cc = $email->cc();
        $bcc = $email->bcc();
        return array_merge(array_keys($to), array_keys($cc), array_keys($bcc));
    }

    /**
     * Prepares the message headers.
     *
     * @param \Cake\Network\Email\Email $email Email instance
     * @return array
     */
    protected function _prepareMessageHeaders($email)
    {
        return $email->getHeaders(['from', 'sender', 'replyTo', 'readReceipt', 'to', 'cc', 'subject', 'returnPath']);
    }

    /**
     * Prepares the message body.
     *
     * @param \Cake\Network\Email\Email $email Email instance
     * @return string
     */
    protected function _prepareMessage($email)
    {
        $lines = $email->message();
        $messages = [];
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
     * @return void
     * @param \Cake\Network\Email\Email $email Cake Email
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _sendRcpt($email)
    {
        $from = $this->_prepareFromAddress($email);
        $this->_smtpSend($this->_prepareFromCmd(key($from)));

        $emails = $this->_prepareRecipientAddresses($email);
        foreach ($emails as $mail) {
            $this->_smtpSend($this->_prepareRcptCmd($mail));
        }
    }

    /**
     * Send Data
     *
     * @param \Cake\Network\Email\Email $email Email instance
     * @return void
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _sendData($email)
    {
        $this->_smtpSend('DATA', '354');

        $headers = $this->_headersToString($this->_prepareMessageHeaders($email));
        $message = $this->_prepareMessage($email);

        $this->_smtpSend($headers . "\r\n\r\n" . $message . "\r\n\r\n\r\n.");
        $this->_content = ['headers' => $headers, 'message' => $message];
    }

    /**
     * Disconnect
     *
     * @return void
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _disconnect()
    {
        $this->_smtpSend('QUIT', false);
        $this->_socket->disconnect();
    }

    /**
     * Helper method to generate socket
     *
     * @return void
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _generateSocket()
    {
        $this->_socket = new Socket($this->_config);
    }

    /**
     * Protected method for sending data to SMTP connection
     *
     * @param string $data data to be sent to SMTP server
     * @param string|bool $checkCode code to check for in server response, false to skip
     * @return string|null The matched code, or null if nothing matched
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _smtpSend($data, $checkCode = '250')
    {
        $this->_lastResponse = [];

        if ($data !== null) {
            $this->_socket->write($data . "\r\n");
        }

        $timeout = $this->_config['timeout'];

        while ($checkCode !== false) {
            $response = '';
            $startTime = time();
            while (substr($response, -2) !== "\r\n" && ((time() - $startTime) < $timeout)) {
                $response .= $this->_socket->read();
            }
            if (substr($response, -2) !== "\r\n") {
                throw new SocketException('SMTP timeout.');
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
            throw new SocketException(sprintf('SMTP Error: %s', $response));
        }
    }
}
