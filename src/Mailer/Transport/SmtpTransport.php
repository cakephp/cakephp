<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer\Transport;

use Cake\Core\Exception\CakeException;
use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;
use Cake\Network\Exception\SocketException;
use Cake\Network\Socket;
use Exception;
use function Cake\Core\env;

/**
 * Send mail using SMTP protocol
 */
class SmtpTransport extends AbstractTransport
{
    public const AUTH_PLAIN = 'PLAIN';
    public const AUTH_LOGIN = 'LOGIN';
    public const AUTH_XOAUTH2 = 'XOAUTH2';

    public const SUPPORTED_AUTH_TYPES = [
        self::AUTH_PLAIN,
        self::AUTH_LOGIN,
        self::AUTH_XOAUTH2,
    ];

    /**
     * Default config for this class
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'host' => 'localhost',
        'port' => 25,
        'timeout' => 30,
        'username' => null,
        'password' => null,
        'client' => null,
        'tls' => false,
        'keepAlive' => false,
        'authType' => null,
    ];

    /**
     * Socket to SMTP server
     *
     * @var \Cake\Network\Socket
     */
    protected Socket $_socket;

    /**
     * Content of email to return
     *
     * @var array<string, string>
     */
    protected array $_content = [];

    /**
     * The response of the last sent SMTP command.
     *
     * @var array
     */
    protected array $_lastResponse = [];

    /**
     * Authentication type.
     *
     * @var string|null
     */
    protected ?string $authType = null;

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
        } catch (Exception) {
            // avoid fatal error on script termination
        }
    }

    /**
     * Unserialize handler.
     *
     * Ensure that the socket property isn't reinitialized in a broken state.
     *
     * @return void
     */
    public function __wakeup(): void
    {
        unset($this->_socket);
    }

    /**
     * Connect to the SMTP server.
     *
     * This method tries to connect only in case there is no open
     * connection available already.
     *
     * @return void
     */
    public function connect(): void
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
    public function connected(): bool
    {
        return isset($this->_socket) && $this->_socket->isConnected();
    }

    /**
     * Disconnect from the SMTP server.
     *
     * This method tries to disconnect only in case there is an open
     * connection available.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if (!$this->connected()) {
            return;
        }

        $this->_disconnect();
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
    public function getLastResponse(): array
    {
        return $this->_lastResponse;
    }

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Message $message Message instance
     * @return array{headers: string, message: string}
     * @throws \Cake\Network\Exception\SocketException
     */
    public function send(Message $message): array
    {
        $this->checkRecipient($message);

        if (!$this->connected()) {
            $this->_connect();
            $this->_auth();
        } else {
            $this->_smtpSend('RSET');
        }

        $this->_sendRcpt($message);
        $this->_sendData($message);

        if (!$this->_config['keepAlive']) {
            $this->_disconnect();
        }

        /** @var array{headers: string, message: string} */
        return $this->_content;
    }

    /**
     * Parses and stores the response lines in `'code' => 'message'` format.
     *
     * @param list<string> $responseLines Response lines to parse.
     * @return void
     */
    protected function _bufferResponseLines(array $responseLines): void
    {
        $response = [];
        foreach ($responseLines as $responseLine) {
            if (preg_match('/^(\d{3})(?:[ -]+(.*))?$/', $responseLine, $match)) {
                $response[] = [
                    'code' => $match[1],
                    'message' => $match[2] ?? null,
                ];
            }
        }
        $this->_lastResponse = array_merge($this->_lastResponse, $response);
    }

    /**
     * Parses the last response line and extract the preferred authentication type.
     *
     * @return void
     */
    protected function _parseAuthType(): void
    {
        $authType = $this->getConfig('authType');
        if ($authType !== null) {
            if (!in_array($authType, self::SUPPORTED_AUTH_TYPES)) {
                throw new CakeException(
                    'Unsupported auth type. Available types are: ' . implode(', ', self::SUPPORTED_AUTH_TYPES)
                );
            }

            $this->authType = $authType;

            return;
        }

        if (!isset($this->_config['username'], $this->_config['password'])) {
            return;
        }

        $auth = '';
        foreach ($this->_lastResponse as $line) {
            if ($line['message'] === '' || str_starts_with($line['message'], 'AUTH ')) {
                $auth = $line['message'];
                break;
            }
        }

        if ($auth === '') {
            return;
        }

        foreach (self::SUPPORTED_AUTH_TYPES as $type) {
            if (str_contains($auth, $type)) {
                $this->authType = $type;

                return;
            }
        }

        throw new CakeException('Unsupported auth type: ' . substr($auth, 5));
    }

    /**
     * Connect to SMTP Server
     *
     * @return void
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _connect(): void
    {
        $this->_generateSocket();
        if (!$this->_socket->connect()) {
            throw new SocketException('Unable to connect to SMTP server.');
        }
        $this->_smtpSend(null, '220');

        $config = $this->_config;

        $host = 'localhost';
        if (isset($config['client'])) {
            if (empty($config['client'])) {
                throw new SocketException('Cannot use an empty client name.');
            }
            $host = $config['client'];
        } else {
            $httpHost = env('HTTP_HOST');
            if (is_string($httpHost) && strlen($httpHost)) {
                [$host] = explode(':', $httpHost);
            }
        }

        try {
            $this->_smtpSend("EHLO {$host}", '250');
            if ($config['tls']) {
                $this->_smtpSend('STARTTLS', '220');
                $this->_socket->enableCrypto('tls');
                $this->_smtpSend("EHLO {$host}", '250');
            }
        } catch (SocketException $e) {
            if ($config['tls']) {
                throw new SocketException(
                    'SMTP server did not accept the connection or trying to connect to non TLS SMTP server using TLS.',
                    null,
                    $e
                );
            }
            try {
                $this->_smtpSend("HELO {$host}", '250');
            } catch (SocketException $e2) {
                throw new SocketException('SMTP server did not accept the connection.', null, $e2);
            }
        }

        $this->_parseAuthType();
    }

    /**
     * Send authentication
     *
     * @return void
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _auth(): void
    {
        if (!isset($this->_config['username'], $this->_config['password'])) {
            return;
        }

        $username = $this->_config['username'];
        $password = $this->_config['password'];

        switch ($this->authType) {
            case self::AUTH_PLAIN:
                $this->_authPlain($username, $password);
                break;

            case self::AUTH_LOGIN:
                $this->_authLogin($username, $password);
                break;

            case self::AUTH_XOAUTH2:
                $this->_authXoauth2($username, $password);
                break;

            default:
                $replyCode = $this->_authPlain($username, $password);
                if ($replyCode === '235') {
                    break;
                }

                $this->_authLogin($username, $password);
        }
    }

    /**
     * Authenticate using AUTH PLAIN mechanism.
     *
     * @param string $username Username.
     * @param string $password Password.
     * @return string|null Response code for the command.
     */
    protected function _authPlain(string $username, string $password): ?string
    {
        return $this->_smtpSend(
            sprintf(
                'AUTH PLAIN %s',
                base64_encode(chr(0) . $username . chr(0) . $password)
            ),
            '235|504|534|535'
        );
    }

    /**
     * Authenticate using AUTH LOGIN mechanism.
     *
     * @param string $username Username.
     * @param string $password Password.
     * @return void
     */
    protected function _authLogin(string $username, string $password): void
    {
        $replyCode = $this->_smtpSend('AUTH LOGIN', '334|500|502|504');
        if ($replyCode === '334') {
            try {
                $this->_smtpSend(base64_encode($username), '334');
            } catch (SocketException $e) {
                throw new SocketException('SMTP server did not accept the username.', null, $e);
            }
            try {
                $this->_smtpSend(base64_encode($password), '235');
            } catch (SocketException $e) {
                throw new SocketException('SMTP server did not accept the password.', null, $e);
            }
        } elseif ($replyCode === '504') {
            throw new SocketException('SMTP authentication method not allowed, check if SMTP server requires TLS.');
        } else {
            throw new SocketException(
                'AUTH command not recognized or not implemented, SMTP server may not require authentication.'
            );
        }
    }

    /**
     * Authenticate using AUTH XOAUTH2 mechanism.
     *
     * @param string $username Username.
     * @param string $token Token.
     * @return void
     * @see https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth#smtp-protocol-exchange
     * @see https://developers.google.com/gmail/imap/xoauth2-protocol#smtp_protocol_exchange
     */
    protected function _authXoauth2(string $username, string $token): void
    {
        $authString = base64_encode(sprintf(
            "user=%s\1auth=Bearer %s\1\1",
            $username,
            $token
        ));

        $this->_smtpSend('AUTH XOAUTH2 ' . $authString, '235');
    }

    /**
     * Prepares the `MAIL FROM` SMTP command.
     *
     * @param string $message The email address to send with the command.
     * @return string
     */
    protected function _prepareFromCmd(string $message): string
    {
        return 'MAIL FROM:<' . $message . '>';
    }

    /**
     * Prepares the `RCPT TO` SMTP command.
     *
     * @param string $message The email address to send with the command.
     * @return string
     */
    protected function _prepareRcptCmd(string $message): string
    {
        return 'RCPT TO:<' . $message . '>';
    }

    /**
     * Prepares the `from` email address.
     *
     * @param \Cake\Mailer\Message $message Message instance
     * @return array
     */
    protected function _prepareFromAddress(Message $message): array
    {
        $from = $message->getReturnPath();
        if (!$from) {
            return $message->getFrom();
        }

        return $from;
    }

    /**
     * Prepares the recipient email addresses.
     *
     * @param \Cake\Mailer\Message $message Message instance
     * @return array
     */
    protected function _prepareRecipientAddresses(Message $message): array
    {
        $to = $message->getTo();
        $cc = $message->getCc();
        $bcc = $message->getBcc();

        return array_merge(array_keys($to), array_keys($cc), array_keys($bcc));
    }

    /**
     * Prepares the message body.
     *
     * @param \Cake\Mailer\Message $message Message instance
     * @return string
     */
    protected function _prepareMessage(Message $message): string
    {
        $lines = $message->getBody();
        $messages = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '.')) {
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
     * @param \Cake\Mailer\Message $message Message instance
     * @throws \Cake\Network\Exception\SocketException
     * @return void
     */
    protected function _sendRcpt(Message $message): void
    {
        $from = $this->_prepareFromAddress($message);
        $this->_smtpSend($this->_prepareFromCmd((string)key($from)));

        $messages = $this->_prepareRecipientAddresses($message);
        foreach ($messages as $mail) {
            $this->_smtpSend($this->_prepareRcptCmd($mail));
        }
    }

    /**
     * Send Data
     *
     * @param \Cake\Mailer\Message $message Message instance
     * @return void
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _sendData(Message $message): void
    {
        $this->_smtpSend('DATA', '354');

        $headers = $message->getHeadersString([
            'from',
            'sender',
            'replyTo',
            'readReceipt',
            'to',
            'cc',
            'subject',
            'returnPath',
        ]);
        $message = $this->_prepareMessage($message);

        $this->_smtpSend($headers . "\r\n\r\n" . $message . "\r\n\r\n\r\n.");
        $this->_content = ['headers' => $headers, 'message' => $message];
    }

    /**
     * Disconnect
     *
     * @return void
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _disconnect(): void
    {
        $this->_smtpSend('QUIT', false);
        $this->_socket->disconnect();
        $this->authType = null;
    }

    /**
     * Helper method to generate socket
     *
     * @return void
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _generateSocket(): void
    {
        $this->_socket = new Socket($this->_config);
    }

    /**
     * Protected method for sending data to SMTP connection
     *
     * @param string|null $data Data to be sent to SMTP server
     * @param string|false $checkCode Code to check for in server response, false to skip
     * @return string|null The matched code, or null if nothing matched
     * @throws \Cake\Network\Exception\SocketException
     */
    protected function _smtpSend(?string $data, string|false $checkCode = '250'): ?string
    {
        $this->_lastResponse = [];

        if ($data !== null) {
            $this->_socket->write($data . "\r\n");
        }

        $timeout = $this->_config['timeout'];

        while ($checkCode !== false) {
            $response = '';
            $startTime = time();
            while (!str_ends_with($response, "\r\n") && (time() - $startTime < $timeout)) {
                $bytes = $this->_socket->read();
                if ($bytes === null) {
                    break;
                }
                $response .= $bytes;
            }
            // Catch empty or malformed responses.
            if (!str_ends_with($response, "\r\n")) {
                // Use response message or assume operation timed out.
                throw new SocketException($response ?: 'SMTP timeout.');
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

        return null;
    }
}
