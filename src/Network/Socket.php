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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Network;

use Cake\Core\Exception\CakeException;
use Cake\Core\InstanceConfigTrait;
use Cake\Network\Exception\SocketException;
use Cake\Validation\Validation;
use Composer\CaBundle\CaBundle;
use Exception;
use InvalidArgumentException;

/**
 * CakePHP network socket connection class.
 *
 * Core base class for network communication.
 */
class Socket
{
    use InstanceConfigTrait;

    /**
     * Default configuration settings for the socket connection
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'persistent' => false,
        'host' => 'localhost',
        'protocol' => 'tcp',
        'port' => 80,
        'timeout' => 30,
    ];

    /**
     * Reference to socket connection resource
     *
     * @var resource|null
     */
    protected $connection;

    /**
     * This boolean contains the current state of the Socket class
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * This variable contains an array with the last error number (num) and string (str)
     *
     * @var array<string, mixed>
     */
    protected $lastError = [];

    /**
     * True if the socket stream is encrypted after a {@link \Cake\Network\Socket::enableCrypto()} call
     *
     * @var bool
     */
    protected $encrypted = false;

    /**
     * Contains all the encryption methods available
     *
     * @var array<string, int>
     */
    protected $_encryptMethods = [
        'sslv23_client' => STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
        'tls_client' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
        'tlsv10_client' => STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT,
        'tlsv11_client' => STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT,
        'tlsv12_client' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
        'sslv23_server' => STREAM_CRYPTO_METHOD_SSLv23_SERVER,
        'tls_server' => STREAM_CRYPTO_METHOD_TLS_SERVER,
        'tlsv10_server' => STREAM_CRYPTO_METHOD_TLSv1_0_SERVER,
        'tlsv11_server' => STREAM_CRYPTO_METHOD_TLSv1_1_SERVER,
        'tlsv12_server' => STREAM_CRYPTO_METHOD_TLSv1_2_SERVER,
    ];

    /**
     * Used to capture connection warnings which can happen when there are
     * SSL errors for example.
     *
     * @var array<string>
     */
    protected $_connectionErrors = [];

    /**
     * Constructor.
     *
     * @param array<string, mixed> $config Socket configuration, which will be merged with the base configuration
     * @see \Cake\Network\Socket::$_defaultConfig
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Connect the socket to the given host and port.
     *
     * @return bool Success
     * @throws \Cake\Network\Exception\SocketException
     */
    public function connect(): bool
    {
        if ($this->connection) {
            $this->disconnect();
        }

        $hasProtocol = strpos($this->_config['host'], '://') !== false;
        if ($hasProtocol) {
            [$this->_config['protocol'], $this->_config['host']] = explode('://', $this->_config['host']);
        }
        $scheme = null;
        if (!empty($this->_config['protocol'])) {
            $scheme = $this->_config['protocol'] . '://';
        }

        $this->_setSslContext($this->_config['host']);
        if (!empty($this->_config['context'])) {
            $context = stream_context_create($this->_config['context']);
        } else {
            $context = stream_context_create();
        }

        $connectAs = STREAM_CLIENT_CONNECT;
        if ($this->_config['persistent']) {
            $connectAs |= STREAM_CLIENT_PERSISTENT;
        }

        /** @psalm-suppress InvalidArgument */
        set_error_handler([$this, '_connectionErrorHandler']);
        $remoteSocketTarget = $scheme . $this->_config['host'];
        $port = (int)$this->_config['port'];
        if ($port > 0) {
            $remoteSocketTarget .= ':' . $port;
        }

        $errNum = 0;
        $errStr = '';
        $this->connection = $this->_getStreamSocketClient(
            $remoteSocketTarget,
            $errNum,
            $errStr,
            (int)$this->_config['timeout'],
            $connectAs,
            $context
        );
        restore_error_handler();

        if ($this->connection === null && (!$errNum || !$errStr)) {
            $this->setLastError($errNum, $errStr);
            throw new SocketException($errStr, $errNum);
        }

        if ($this->connection === null && $this->_connectionErrors) {
            $message = implode("\n", $this->_connectionErrors);
            throw new SocketException($message, E_WARNING);
        }

        $this->connected = is_resource($this->connection);
        if ($this->connected) {
            /** @psalm-suppress PossiblyNullArgument */
            stream_set_timeout($this->connection, (int)$this->_config['timeout']);
        }

        return $this->connected;
    }

    /**
     * Check the connection status after calling `connect()`.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Create a stream socket client. Mock utility.
     *
     * @param string $remoteSocketTarget remote socket
     * @param int $errNum error number
     * @param string $errStr error string
     * @param int $timeout timeout
     * @param int $connectAs flags
     * @param resource $context context
     * @return resource|null
     */
    protected function _getStreamSocketClient($remoteSocketTarget, &$errNum, &$errStr, $timeout, $connectAs, $context)
    {
        $resource = stream_socket_client(
            $remoteSocketTarget,
            $errNum,
            $errStr,
            $timeout,
            $connectAs,
            $context
        );

        if ($resource) {
            return $resource;
        }

        return null;
    }

    /**
     * Configure the SSL context options.
     *
     * @param string $host The host name being connected to.
     * @return void
     */
    protected function _setSslContext(string $host): void
    {
        foreach ($this->_config as $key => $value) {
            if (substr($key, 0, 4) !== 'ssl_') {
                continue;
            }
            $contextKey = substr($key, 4);
            if (empty($this->_config['context']['ssl'][$contextKey])) {
                $this->_config['context']['ssl'][$contextKey] = $value;
            }
            unset($this->_config[$key]);
        }
        if (!isset($this->_config['context']['ssl']['SNI_enabled'])) {
            $this->_config['context']['ssl']['SNI_enabled'] = true;
        }
        if (empty($this->_config['context']['ssl']['peer_name'])) {
            $this->_config['context']['ssl']['peer_name'] = $host;
        }
        if (empty($this->_config['context']['ssl']['cafile'])) {
            $this->_config['context']['ssl']['cafile'] = CaBundle::getBundledCaBundlePath();
        }
        if (!empty($this->_config['context']['ssl']['verify_host'])) {
            $this->_config['context']['ssl']['CN_match'] = $host;
        }
        unset($this->_config['context']['ssl']['verify_host']);
    }

    /**
     * socket_stream_client() does not populate errNum, or $errStr when there are
     * connection errors, as in the case of SSL verification failure.
     *
     * Instead we need to handle those errors manually.
     *
     * @param int $code Code number.
     * @param string $message Message.
     * @return void
     */
    protected function _connectionErrorHandler(int $code, string $message): void
    {
        $this->_connectionErrors[] = $message;
    }

    /**
     * Get the connection context.
     *
     * @return array|null Null when there is no connection, an array when there is.
     */
    public function context(): ?array
    {
        if (!$this->connection) {
            return null;
        }

        return stream_context_get_options($this->connection);
    }

    /**
     * Get the host name of the current connection.
     *
     * @return string Host name
     */
    public function host(): string
    {
        if (Validation::ip($this->_config['host'])) {
            return gethostbyaddr($this->_config['host']);
        }

        return gethostbyaddr($this->address());
    }

    /**
     * Get the IP address of the current connection.
     *
     * @return string IP address
     */
    public function address(): string
    {
        if (Validation::ip($this->_config['host'])) {
            return $this->_config['host'];
        }

        return gethostbyname($this->_config['host']);
    }

    /**
     * Get all IP addresses associated with the current connection.
     *
     * @return array IP addresses
     */
    public function addresses(): array
    {
        if (Validation::ip($this->_config['host'])) {
            return [$this->_config['host']];
        }

        return gethostbynamel($this->_config['host']);
    }

    /**
     * Get the last error as a string.
     *
     * @return string|null Last error
     */
    public function lastError(): ?string
    {
        if (!empty($this->lastError)) {
            return $this->lastError['num'] . ': ' . $this->lastError['str'];
        }

        return null;
    }

    /**
     * Set the last error.
     *
     * @param int|null $errNum Error code
     * @param string $errStr Error string
     * @return void
     */
    public function setLastError(?int $errNum, string $errStr): void
    {
        $this->lastError = ['num' => $errNum, 'str' => $errStr];
    }

    /**
     * Write data to the socket.
     *
     * @param string $data The data to write to the socket.
     * @return int Bytes written.
     */
    public function write(string $data): int
    {
        if (!$this->connected && !$this->connect()) {
            return 0;
        }
        $totalBytes = strlen($data);
        $written = 0;
        while ($written < $totalBytes) {
            /** @psalm-suppress PossiblyNullArgument */
            $rv = fwrite($this->connection, substr($data, $written));
            if ($rv === false || $rv === 0) {
                return $written;
            }
            $written += $rv;
        }

        return $written;
    }

    /**
     * Read data from the socket. Returns null if no data is available or no connection could be
     * established.
     *
     * @param int $length Optional buffer length to read; defaults to 1024
     * @return string|null Socket data
     */
    public function read(int $length = 1024): ?string
    {
        if (!$this->connected && !$this->connect()) {
            return null;
        }

        /** @psalm-suppress PossiblyNullArgument */
        if (!feof($this->connection)) {
            $buffer = fread($this->connection, $length);
            $info = stream_get_meta_data($this->connection);
            if ($info['timed_out']) {
                $this->setLastError(E_WARNING, 'Connection timed out');

                return null;
            }

            return $buffer;
        }

        return null;
    }

    /**
     * Disconnect the socket from the current connection.
     *
     * @return bool Success
     */
    public function disconnect(): bool
    {
        if (!is_resource($this->connection)) {
            $this->connected = false;

            return true;
        }
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $this->connected = !fclose($this->connection);

        if (!$this->connected) {
            $this->connection = null;
        }

        return !$this->connected;
    }

    /**
     * Destructor, used to disconnect from current connection.
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Resets the state of this Socket instance to it's initial state (before Object::__construct got executed)
     *
     * @param array|null $state Array with key and values to reset
     * @return void
     */
    public function reset(?array $state = null): void
    {
        if (empty($state)) {
            static $initialState = [];
            if (empty($initialState)) {
                $initialState = get_class_vars(self::class);
            }
            $state = $initialState;
        }

        foreach ($state as $property => $value) {
            $this->{$property} = $value;
        }
    }

    /**
     * Encrypts current stream socket, using one of the defined encryption methods
     *
     * @param string $type can be one of 'ssl2', 'ssl3', 'ssl23' or 'tls'
     * @param string $clientOrServer can be one of 'client', 'server'. Default is 'client'
     * @param bool $enable enable or disable encryption. Default is true (enable)
     * @return void
     * @throws \InvalidArgumentException When an invalid encryption scheme is chosen.
     * @throws \Cake\Network\Exception\SocketException When attempting to enable SSL/TLS fails
     * @see stream_socket_enable_crypto
     */
    public function enableCrypto(string $type, string $clientOrServer = 'client', bool $enable = true): void
    {
        if (!array_key_exists($type . '_' . $clientOrServer, $this->_encryptMethods)) {
            throw new InvalidArgumentException('Invalid encryption scheme chosen');
        }
        $method = $this->_encryptMethods[$type . '_' . $clientOrServer];

        if ($method === STREAM_CRYPTO_METHOD_TLS_CLIENT) {
            $method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }
        if ($method === STREAM_CRYPTO_METHOD_TLS_SERVER) {
            $method |= STREAM_CRYPTO_METHOD_TLSv1_1_SERVER | STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;
        }

        try {
            if ($this->connection === null) {
                throw new CakeException('You must call connect() first.');
            }
            $enableCryptoResult = stream_socket_enable_crypto($this->connection, $enable, $method);
        } catch (Exception $e) {
            $this->setLastError(null, $e->getMessage());
            throw new SocketException($e->getMessage(), null, $e);
        }

        if ($enableCryptoResult === true) {
            $this->encrypted = $enable;

            return;
        }

        $errorMessage = 'Unable to perform enableCrypto operation on the current socket';
        $this->setLastError(null, $errorMessage);
        throw new SocketException($errorMessage);
    }

    /**
     * Check the encryption status after calling `enableCrypto()`.
     *
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    /**
     * Temporary magic method to allow accessing protected properties.
     *
     * Will be removed in 5.0.
     *
     * @param string $name Property name.
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'connected':
                deprecationWarning('The property `$connected` is deprecated, use `isConnected()` instead.');

                return $this->connected;

            case 'encrypted':
                deprecationWarning('The property `$encrypted` is deprecated, use `isEncrypted()` instead.');

                return $this->encrypted;

            case 'lastError':
                deprecationWarning('The property `$lastError` is deprecated, use `lastError()` instead.');

                return $this->lastError;

            case 'connection':
                deprecationWarning('The property `$connection` is deprecated.');

                return $this->connection;

            case 'description':
                deprecationWarning('The CakePHP team would love to know your use case for this property.');

                return 'Remote DataSource Network Socket Interface';
        }

        $trace = debug_backtrace();
        $parts = explode('\\', static::class);
        trigger_error(
            sprintf(
                'Undefined property: %s::$%s in %s on line %s',
                array_pop($parts),
                $name,
                $trace[0]['file'],
                $trace[0]['line']
            ),
            E_USER_NOTICE
        );

        return null;
    }
}
