<?php

namespace Cake\Model\Datasource\Database\Driver;

use Cake\Model\Datasource\Database\Statement;
use PDO;

class Mysql extends \Cake\Model\Datasource\Database\Driver {

/**
 * Base configuration settings for MySQL driver
 *
 * @var array
 */
	protected $_baseConfig = [
		'persistent' => true,
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'database' => 'cake',
		'port' => '3306',
		'flags' => array(),
		'encoding' => 'utf8'
	];

/**
 * Establishes a connection to the databse server
 *
 * @param array $config configuration to be used for creating connection
 * @return boolean true on success
 **/
	public function connect(array $config) {
		$config += $this->_baseConfig;
		$flags = [
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $config['encoding']
		] + $config['flags'];

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
 * Returns whether php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 **/

	public function enabled() {
		return in_array('mysql', PDO::getAvailableDrivers());
	}

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public  function prepare($sql) {
		$statement = $this->_connection->prepare($sql);
		return new Statement($statement, $this);
	}

/**
 * Starts a transaction
 *
 * @return boolean true on success, false otherwise
 **/
	public function beginTransaction() {
		return $this->_connection->beginTransaction();
	}

/**
 * Commits a transaction
 *
 * @return boolean true on success, false otherwise
 **/
	public function commitTransaction() {
		return $this->_connection->commit();
	}

/**
 * Rollsback a transaction
 *
 * @return boolean true on success, false otherwise
 **/
	public function rollbackTransaction() {
		return $this->_connection->rollback();
	}

}
