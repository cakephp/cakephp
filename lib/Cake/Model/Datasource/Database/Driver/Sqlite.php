<?php

namespace Cake\Model\Datasource\Database\Driver;

use Cake\Model\Datasource\Database\Statement\BufferedStatement;
use PDO;

class Sqlite extends \Cake\Model\Datasource\Database\Driver {

	use PDODriver { connect as protected _connect; }

/**
 * Base configuration settings for Sqlite driver
 *
 * @var array
 */
	protected $_baseConfig = [
		'persistent' => false,
		'database' => ':memory:',
		'encoding' => 'utf8',
		'flags' => [],
		'dsn' => null
	];

/**
 * Establishes a connection to the databse server
 *
 * @param array $config configuration to be used for creating connection
 * @return boolean true on success
 **/
	public function connect(array $config) {
		$config += $this->_baseConfig + ['login' => null, 'password' => null];
		$config['flags'] += [
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		if (empty($config['dsn'])) {
			$config['dsn'] = "sqlite:{$config['database']}";
		}

		return $this->_connect($config);
	}

/**
 * Returns whether php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 **/

	public function enabled() {
		return in_array('sqlite', PDO::getAvailableDrivers());
	}

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public  function prepare($sql) {
		$statement = $this->connection()->prepare($sql);
		return new BufferedStatement($statement, $this);
	}

}
