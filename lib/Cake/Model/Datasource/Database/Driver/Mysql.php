<?php

namespace Cake\Model\Datasource\Database\Driver;

use PDO,
	Cake\Model\Datasource\Database\Statement;

class Mysql extends \Cake\Model\Datasource\Database\Driver {

/**
 * Base configuration settings for MySQL driver
 *
 * @var array
 */
	protected $_baseConfig = array(
		'persistent' => true,
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'database' => 'cake',
		'port' => '3306',
		'flags' => array(),
		'encoding' => 'utf8'
	);

/**
 * PDO instance associated to this connection
 *
 * @var PDO
 **/
	protected $_conenction;

/**
 * Establishes a conenction to the databse server
 *
 * @param array $config configuretion to be used for creating connection
 * @return boolean true on success
 **/
	public function connect(array $config) {
		$config += $this->_baseConfig;
		$flags = array(
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $config['encoding']
		) + $config['flags'];

		if (empty($config['unix_socket'])) {
			$dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
		} else {
			$dsn = "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
		}

		$this->_connection = new PDO(
			$dsn,
			$config['login'],
			$config['password'],
			$flags
		);

		return true;
	}

/**
 * Disconnects from database server
 *
 * @return void
 **/
	public function disconnect() {
		$this->_connection = null;
	}

/**
 * Returns wheter php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 **/

	public function enabled() {
		return in_array('mysql', PDO::getAvailableDrivers());
	}

}
