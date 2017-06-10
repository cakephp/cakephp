<?php
/**
 * CakePHP Socket connection class.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Network
 * @since         CakePHP(tm) v 1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Validation', 'Utility');

/**
 * CakePHP network socket connection class.
 *
 * Core base class for network communication.
 *
 * @package       Cake.Network
 */
class CakeSocket {

/**
 * CakeSocket description
 *
 * @var string
 */
	public $description = 'Remote DataSource Network Socket Interface';

/**
 * Base configuration settings for the socket connection
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => false,
		'host' => 'localhost',
		'protocol' => 'tcp',
		'port' => 80,
		'timeout' => 30,
		'cryptoType' => 'tls',
	);

/**
 * Configuration settings for the socket connection
 *
 * @var array
 */
	public $config = array();

/**
 * Reference to socket connection resource
 *
 * @var resource
 */
	public $connection = null;

/**
 * This boolean contains the current state of the CakeSocket class
 *
 * @var bool
 */
	public $connected = false;

/**
 * This variable contains an array with the last error number (num) and string (str)
 *
 * @var array
 */
	public $lastError = array();

/**
 * True if the socket stream is encrypted after a CakeSocket::enableCrypto() call
 *
 * @var bool
 */
	public $encrypted = false;

/**
 * Contains all the encryption methods available
 *
 * @var array
 */
	protected $_encryptMethods = array(
		// @codingStandardsIgnoreStart
		'sslv2_client' => STREAM_CRYPTO_METHOD_SSLv2_CLIENT,
		'sslv3_client' => STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
		'sslv23_client' => STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
		'tls_client' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
		'sslv2_server' => STREAM_CRYPTO_METHOD_SSLv2_SERVER,
		'sslv3_server' => STREAM_CRYPTO_METHOD_SSLv3_SERVER,
		'sslv23_server' => STREAM_CRYPTO_METHOD_SSLv23_SERVER,
		'tls_server' => STREAM_CRYPTO_METHOD_TLS_SERVER,
		// @codingStandardsIgnoreEnd
	);

/**
 * Used to capture connection warnings which can happen when there are
 * SSL errors for example.
 *
 * @var array
 */
	protected $_connectionErrors = array();

/**
 * Constructor.
 *
 * @param array $config Socket configuration, which will be merged with the base configuration
 * @see CakeSocket::$_baseConfig
 */
	public function __construct($config = array()) {
		$this->config = array_merge($this->_baseConfig, $config);

		$this->_addTlsVersions();
	}

/**
 * Add TLS versions that are dependent on specific PHP versions.
 *
 * These TLS versions are not supported by older PHP versions,
 * so we have to conditionally set them if they are supported.
 *
 * As of PHP5.6.6, STREAM_CRYPTO_METHOD_TLS_CLIENT does not include
 * TLS1.1 or 1.2. If we have TLS1.2 support we need to update the method map.
 *
 * @see https://bugs.php.net/bug.php?id=69195
 * @see https://github.com/php/php-src/commit/10bc5fd4c4c8e1dd57bd911b086e9872a56300a0
 * @return void
 */
	protected function _addTlsVersions() {
		$conditionalCrypto = array(
			'tlsv1_1_client' => 'STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT',
			'tlsv1_2_client' => 'STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT',
			'tlsv1_1_server' => 'STREAM_CRYPTO_METHOD_TLSv1_1_SERVER',
			'tlsv1_2_server' => 'STREAM_CRYPTO_METHOD_TLSv1_2_SERVER'
		);
		foreach ($conditionalCrypto as $key => $const) {
			if (defined($const)) {
				$this->_encryptMethods[$key] = constant($const);
			}
		}

		// @codingStandardsIgnoreStart
		if (isset($this->_encryptMethods['tlsv1_2_client'])) {
			$this->_encryptMethods['tls_client'] = STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
		}
		if (isset($this->_encryptMethods['tlsv1_2_server'])) {
			$this->_encryptMethods['tls_server'] = STREAM_CRYPTO_METHOD_TLS_SERVER | STREAM_CRYPTO_METHOD_TLSv1_1_SERVER | STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;
		}
		// @codingStandardsIgnoreEnd
	}

/**
 * Connects the socket to the given host and port.
 *
 * @return bool Success
 * @throws SocketException
 */
	public function connect() {
		if ($this->connection) {
			$this->disconnect();
		}

		$hasProtocol = strpos($this->config['host'], '://') !== false;
		if ($hasProtocol) {
			list($this->config['protocol'], $this->config['host']) = explode('://', $this->config['host']);
		}
		$scheme = null;
		if (!empty($this->config['protocol'])) {
			$scheme = $this->config['protocol'] . '://';
		}
		if (!empty($this->config['proxy'])) {
			$scheme = 'tcp://';
		}

		$host = $this->config['host'];
		if (isset($this->config['request']['uri']['host'])) {
			$host = $this->config['request']['uri']['host'];
		}
		$this->_setSslContext($host);

		if (!empty($this->config['context'])) {
			$context = stream_context_create($this->config['context']);
		} else {
			$context = stream_context_create();
		}

		$connectAs = STREAM_CLIENT_CONNECT;
		if ($this->config['persistent']) {
			$connectAs |= STREAM_CLIENT_PERSISTENT;
		}

		set_error_handler(array($this, '_connectionErrorHandler'));
		$this->connection = stream_socket_client(
			$scheme . $this->config['host'] . ':' . $this->config['port'],
			$errNum,
			$errStr,
			$this->config['timeout'],
			$connectAs,
			$context
		);
		restore_error_handler();

		if (!empty($errNum) || !empty($errStr)) {
			$this->setLastError($errNum, $errStr);
			throw new SocketException($errStr, $errNum);
		}

		if (!$this->connection && $this->_connectionErrors) {
			$message = implode("\n", $this->_connectionErrors);
			throw new SocketException($message, E_WARNING);
		}

		$this->connected = is_resource($this->connection);
		if ($this->connected) {
			stream_set_timeout($this->connection, $this->config['timeout']);

			if (!empty($this->config['request']) &&
				$this->config['request']['uri']['scheme'] === 'https' &&
				!empty($this->config['proxy'])
			) {
				$req = array();
				$req[] = 'CONNECT ' . $this->config['request']['uri']['host'] . ':' .
					$this->config['request']['uri']['port'] . ' HTTP/1.1';
				$req[] = 'Host: ' . $this->config['host'];
				$req[] = 'User-Agent: php proxy';
				if (!empty($this->config['proxyauth'])) {
					$req[] = 'Proxy-Authorization: ' . $this->config['proxyauth'];
				}

				fwrite($this->connection, implode("\r\n", $req) . "\r\n\r\n");

				while (!feof($this->connection)) {
					$s = rtrim(fgets($this->connection, 4096));
					if (preg_match('/^$/', $s)) {
						break;
					}
				}

				$this->enableCrypto($this->config['cryptoType'], 'client');
			}
		}
		return $this->connected;
	}

/**
 * Configure the SSL context options.
 *
 * @param string $host The host name being connected to.
 * @return void
 */
	protected function _setSslContext($host) {
		foreach ($this->config as $key => $value) {
			if (substr($key, 0, 4) !== 'ssl_') {
				continue;
			}
			$contextKey = substr($key, 4);
			if (empty($this->config['context']['ssl'][$contextKey])) {
				$this->config['context']['ssl'][$contextKey] = $value;
			}
			unset($this->config[$key]);
		}
		if (version_compare(PHP_VERSION, '5.3.2', '>=')) {
			if (!isset($this->config['context']['ssl']['SNI_enabled'])) {
				$this->config['context']['ssl']['SNI_enabled'] = true;
			}
			if (version_compare(PHP_VERSION, '5.6.0', '>=')) {
				if (empty($this->config['context']['ssl']['peer_name'])) {
					$this->config['context']['ssl']['peer_name'] = $host;
				}
			} else {
				if (empty($this->config['context']['ssl']['SNI_server_name'])) {
					$this->config['context']['ssl']['SNI_server_name'] = $host;
				}
			}
		}
		if (empty($this->config['context']['ssl']['cafile'])) {
			$this->config['context']['ssl']['cafile'] = CAKE . 'Config' . DS . 'cacert.pem';
		}
		if (!empty($this->config['context']['ssl']['verify_host'])) {
			$this->config['context']['ssl']['CN_match'] = $host;
		}
		unset($this->config['context']['ssl']['verify_host']);
	}

/**
 * socket_stream_client() does not populate errNum, or $errStr when there are
 * connection errors, as in the case of SSL verification failure.
 *
 * Instead we need to handle those errors manually.
 *
 * @param int $code Code.
 * @param string $message Message.
 * @return void
 */
	protected function _connectionErrorHandler($code, $message) {
		$this->_connectionErrors[] = $message;
	}

/**
 * Gets the connection context.
 *
 * @return null|array Null when there is no connection, an array when there is.
 */
	public function context() {
		if (!$this->connection) {
			return null;
		}
		return stream_context_get_options($this->connection);
	}

/**
 * Gets the host name of the current connection.
 *
 * @return string Host name
 */
	public function host() {
		if (Validation::ip($this->config['host'])) {
			return gethostbyaddr($this->config['host']);
		}
		return gethostbyaddr($this->address());
	}

/**
 * Gets the IP address of the current connection.
 *
 * @return string IP address
 */
	public function address() {
		if (Validation::ip($this->config['host'])) {
			return $this->config['host'];
		}
		return gethostbyname($this->config['host']);
	}

/**
 * Gets all IP addresses associated with the current connection.
 *
 * @return array IP addresses
 */
	public function addresses() {
		if (Validation::ip($this->config['host'])) {
			return array($this->config['host']);
		}
		return gethostbynamel($this->config['host']);
	}

/**
 * Gets the last error as a string.
 *
 * @return string|null Last error
 */
	public function lastError() {
		if (!empty($this->lastError)) {
			return $this->lastError['num'] . ': ' . $this->lastError['str'];
		}
		return null;
	}

/**
 * Sets the last error.
 *
 * @param int $errNum Error code
 * @param string $errStr Error string
 * @return void
 */
	public function setLastError($errNum, $errStr) {
		$this->lastError = array('num' => $errNum, 'str' => $errStr);
	}

/**
 * Writes data to the socket.
 *
 * @param string $data The data to write to the socket
 * @return bool Success
 */
	public function write($data) {
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}
		$totalBytes = strlen($data);
		for ($written = 0, $rv = 0; $written < $totalBytes; $written += $rv) {
			$rv = fwrite($this->connection, substr($data, $written));
			if ($rv === false || $rv === 0) {
				return $written;
			}
		}
		return $written;
	}

/**
 * Reads data from the socket. Returns false if no data is available or no connection could be
 * established.
 *
 * @param int $length Optional buffer length to read; defaults to 1024
 * @return mixed Socket data
 */
	public function read($length = 1024) {
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}

		if (!feof($this->connection)) {
			$buffer = fread($this->connection, $length);
			$info = stream_get_meta_data($this->connection);
			if ($info['timed_out']) {
				$this->setLastError(E_WARNING, __d('cake_dev', 'Connection timed out'));
				return false;
			}
			return $buffer;
		}
		return false;
	}

/**
 * Disconnects the socket from the current connection.
 *
 * @return bool Success
 */
	public function disconnect() {
		if (!is_resource($this->connection)) {
			$this->connected = false;
			return true;
		}
		$this->connected = !fclose($this->connection);

		if (!$this->connected) {
			$this->connection = null;
		}
		return !$this->connected;
	}

/**
 * Destructor, used to disconnect from current connection.
 */
	public function __destruct() {
		$this->disconnect();
	}

/**
 * Resets the state of this Socket instance to it's initial state (before CakeObject::__construct got executed)
 *
 * @param array $state Array with key and values to reset
 * @return bool True on success
 */
	public function reset($state = null) {
		if (empty($state)) {
			static $initalState = array();
			if (empty($initalState)) {
				$initalState = get_class_vars(__CLASS__);
			}
			$state = $initalState;
		}

		foreach ($state as $property => $value) {
			$this->{$property} = $value;
		}
		return true;
	}

/**
 * Encrypts current stream socket, using one of the defined encryption methods.
 *
 * @param string $type Type which can be one of 'sslv2', 'sslv3', 'sslv23', 'tls', 'tlsv1_1' or 'tlsv1_2'.
 * @param string $clientOrServer Can be one of 'client', 'server'. Default is 'client'.
 * @param bool $enable Enable or disable encryption. Default is true (enable)
 * @return bool True on success
 * @throws InvalidArgumentException When an invalid encryption scheme is chosen.
 * @throws SocketException When attempting to enable SSL/TLS fails.
 * @see stream_socket_enable_crypto
 */
	public function enableCrypto($type, $clientOrServer = 'client', $enable = true) {
		if (!array_key_exists($type . '_' . $clientOrServer, $this->_encryptMethods)) {
			throw new InvalidArgumentException(__d('cake_dev', 'Invalid encryption scheme chosen'));
		}
		$enableCryptoResult = false;
		try {
			$enableCryptoResult = stream_socket_enable_crypto($this->connection, $enable,
				$this->_encryptMethods[$type . '_' . $clientOrServer]);
		} catch (Exception $e) {
			$this->setLastError(null, $e->getMessage());
			throw new SocketException($e->getMessage());
		}
		if ($enableCryptoResult === true) {
			$this->encrypted = $enable;
			return true;
		}
		$errorMessage = __d('cake_dev', 'Unable to perform enableCrypto operation on CakeSocket');
		$this->setLastError(null, $errorMessage);
		throw new SocketException($errorMessage);
	}
}
