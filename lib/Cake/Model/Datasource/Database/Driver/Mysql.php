<?php

namespace Cake\Model\Datasource\Database\Driver;

use PDO;

class Mysql extends \Cake\Model\Datasource\Database\Driver {

	use PDODriver { connect as protected _connect; }

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
		'flags' => [],
		'encoding' => 'utf8',
		'dsn' => null
	];

/**
 * Establishes a connection to the databse server
 *
 * @param array $config configuration to be used for creating connection
 * @return boolean true on success
 **/
	public function connect(array $config) {
		$config += $this->_baseConfig;
		$config['flags'] += [
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		];

		if (empty($config['dsn'])) {
			if (empty($config['unix_socket'])) {
				$config['dsn'] = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['encoding']}";
			} else {
				$config['dsn'] = "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
			}
		}

		return $this->_connect($config);
	}

/**
 * Returns whether php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 **/

	public function enabled() {
		return in_array('mysql', PDO::getAvailableDrivers());
	}


}
