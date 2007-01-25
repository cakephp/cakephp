<?php
/* SVN FILE: $Id$ */
/**
 * Cake Socket connection class.
 *
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP v 1.2.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
uses('validation');

/**
 * Cake network socket connection class.
 *
 * Core base class for network communication.
 *
 * @package		cake
 * @subpackage	cake.cake.libs
 */
class CakeSocket extends Object {
/**
 * Object description
 *
 * @var string
 */
	var $description = 'Remote DataSource Network Socket Interface';
/**
 * Base configuration settings for the socket connection
 *
 * @var array
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
 */
	var $config = array();
/**
 * Reference to socket connection resource
 *
 * @var resource
 */
	var $connection = null;
	
/**
 * This boolean contains the current state of the CakeSocket class
 *
 * @var boolean
 */
	var $connected = false;
	
/**
 * This variable contains an array with the last error number (num) and string (str)
 *
 * @var array
 */
	var $error = array();
		
/**
 * Constructor.
 *
 * @param array $config Socket configuration, which will be merged with the base configuration
 */
	function __construct($config = array()) {
		parent::__construct();
		$this->config = am($this->_baseConfig, $config);
		
		if (!is_numeric($this->config['protocol'])) {
			$this->config['protocol'] = getprotobyname($this->config['protocol']);
		}
		
		return $this->connect();
	}
/**
 * Connect the socket to the given host and port.
 *
 * @return boolean Success
 */
	function connect() {
		if ($this->connection != null) {
			$this->disconnect();
		}

		if ($this->config['persistent'] == true) {
			$tmp = null;			
			$this->connection = @pfsockopen($this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
		} else {
			$this->connection = @fsockopen($this->config['host'], $this->config['port'], $errNum, $errStr, $this->config['timeout']);
		}
		
		if (!empty($errNum) || !empty($errStr)) {
			$this->setLastError($errStr, $errNum);
		}
		
		$this->connected = is_resource($this->connection);
		
		return $this->connected;
	}
		
/**
 * Get the host name of the current connection.
 *
 * @return string Host name
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
 * @return string
 */
	function lastError() {
		if (!empty($this->error)) {
			return $this->error['num'].': '.$this->error['str'];
		} else {
			return null;
		}
	}
/**
 * Set the last error.
 *
 * @param int $errNum Error code
 * @param string $errStr Error string
 * @return void
 */
	function setLastError($errNum, $errStr) {
		$this->lastError = array('num' => $errNum, 'str' => $errStr);
	}
/**
 * Write data to the socket.
 *
 * @param string $data The data to write to the socket
 * @return boolean success
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
 * @param int $length Optional buffer length to read; defaults to 1024
 * @return mixed Socket data
 */
	function read($length = 1024) {
		if (!$this->connected) {
			if (!$this->connect()) {
				return false;
			}
		}
		
		if (!feof($this->connection)) {
			return fread($this->connection, $length);
		} else {
			return false;
		}
	}
/**
 * Abort socket operation.
 *
 * @return boolean Success
 */
	function abort() {
	}
/**
 * Disconnect the socket from the current connection.
 *
 * @return boolean Success
 */
	function disconnect() {
		$this->connected = !@fclose($this->connection);
		
		if (!$this->connected) {
			$this->connection = null;
		}
		return !$this->connected;
	}
/**
 * Destruct.
 *
 * @return void
 */
 	function __destruct() {
 		$this->disconnect();
 	}
}

?>