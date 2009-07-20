<?php
/* SVN FILE: $Id$ */
/**
 * Cake Socket connection class.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 1.2.0
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'Validation');
/**
 * Cake network socket connection class.
 *
 * Core base class for network communication.
 *
 * @package       cake
 * @subpackage    cake.cake.libs
 */
class CakeSocket extends Object {
/**
 * Object description
 *
 * @var string
 * @access public
 */
	var $description = 'Remote DataSource Network Socket Interface';
/**
 * Base configuration settings for the socket connection
 *
 * @var array
 * @access protected
 */
	var $_baseConfig = array(
		'persistent'	=> false,
		'host'			=> 'localhost',
		'protocol'		=> 'tcp',
		'port'			=> 80,
		'timeout'		=> 30
	);
/**
 * Configuration settings for the socket connection
 *
 * @var array
 * @access public
 */
	var $config = array();
/**
 * Reference to socket connection resource
 *
 * @var resource
 * @access public
 */
	var $connection = null;
/**
 * This boolean contains the current state of the CakeSocket class
 *
 * @var boolean
 * @access public
 */
	var $connected = false;
/**
 * This variable contains an array with the last error number (num) and string (str)
 *
 * @var array
 * @access public
 */
	var $lastError = array();
/**
 * Constructor.
 *
 * @param array $config Socket configuration, which will be merged with the base configuration
 */
	function __construct($config = array()) {
		parent::__construct();

		$this->config = array_merge($this->_baseConfig, $config);
		if (!is_numeric($this->config['protocol'])) {
			$this->config['protocol'] = getprotobyname($this->config['protocol']);
		}
	}
/**
 * Connect the socket to the given host and port.
 *
 * @return boolean Success
 * @access public
 */
	function connect() {
		if ($this->connection != null) {
			$this->disconnect();
		}

		$scheme = null;
		if (isset($this->config['request']) && $this->config['request']['uri']['scheme'] == 'https') {
			$scheme = 'ssl://';
		}

		if ($this->config['persistent'] == true) {
			$tmp = null;
			$this->connection = @pfsockopen($scheme.$this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
		} else {
			$this->connection = @fsockopen($scheme.$this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
		}

		if (!empty($errNum) || !empty($errStr)) {
			$this->setLastError($errStr, $errNum);
		}

		$this->connected = is_resource($this->connection);
		if ($this->connected) {
			stream_set_timeout($this->connection, $this->config['timeout']);
		}
		return $this->connected;
	}

/**
 * Get the host name of the current connection.
 *
 * @return string Host name
 * @access public
 */
	function host() {
		if (Validation::ip($this->config['host'])) {
			return gethostbyaddr($this->config['host']);
		} else {
			return gethostbyaddr($this->address());
		}
	}
/**
 * Get the IP address of the current connection.
 *
 * @return string IP address
 * @access public
 */
	function address() {
		if (Validation::ip($this->config['host'])) {
			return $this->config['host'];
		} else {
			return gethostbyname($this->config['host']);
		}
	}
/**
 * Get all IP addresses associated with the current connection.
 *
 * @return array IP addresses
 * @access public
 */
	function addresses() {
		if (Validation::ip($this->config['host'])) {
			return array($this->config['host']);
		} else {
			return gethostbynamel($this->config['host']);
		}
	}
/**
 * Get the last error as a string.
 *
 * @return string Last error
 * @access public
 */
	function lastError() {
		if (!empty($this->lastError)) {
			return $this->lastError['num'].': '.$this->lastError['str'];
		} else {
			return null;
		}
	}
/**
 * Set the last error.
 *
 * @param integer $errNum Error code
 * @param string $errStr Error string
 * @access public
 */
	function setLastError($errNum, $errStr) {
		$this->lastError = array('num' => $errNum, 'str' => $errStr);
	}
/**
 * Write data to the socket.
 *
 * @param string $data The data to write to the socket
 * @return boolean Success
 * @access public
 */
	function write($data) {
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}

		return fwrite($this->connection, $data, strlen($data));
	}

/**
 * Read data from the socket. Returns false if no data is available or no connection could be
 * established.
 *
 * @param integer $length Optional buffer length to read; defaults to 1024
 * @return mixed Socket data
 * @access public
 */
	function read($length = 1024) {
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}

		if (!feof($this->connection)) {
			$buffer = fread($this->connection, $length);
			$info = stream_get_meta_data($this->connection);
			if ($info['timed_out']) {
				$this->setLastError(E_WARNING, __('Connection timed out', true));
				return false;
			}
			return $buffer;
		} else {
			return false;
		}
	}
/**
 * Abort socket operation.
 *
 * @return boolean Success
 * @access public
 */
	function abort() {
	}
/**
 * Disconnect the socket from the current connection.
 *
 * @return boolean Success
 * @access public
 */
	function disconnect() {
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
 *
 * @access private
 */
	function __destruct() {
		$this->disconnect();
	}
/**
 * Resets the state of this Socket instance to it's initial state (before Object::__construct got executed)
 *
 * @return boolean True on success
 * @access public
 */
	function reset($state = null) {
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
}
?>