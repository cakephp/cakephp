<?php
/**
 *
 * PHP Version 5.4
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2013, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database\Driver;

use Cake\Model\Datasource\Database\Statement;
use Cake\Model\Datasource\Database\Dialect\PostgresDialect;
use PDO;

class Postgres extends \Cake\Model\Datasource\Database\Driver {

	use PDODriver { connect as protected _connect; }
	use PostgresDialect;

/**
 * Base configuration settings for Postgres driver
 *
 * @var array
 */
	protected $_baseConfig = [
		'persistent' => true,
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'database' => 'cake',
		'schema' => 'public',
		'port' => 5432,
		'encoding' => 'utf8',
		'flags' => array(),
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
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		if (empty($config['dsn'])) {
			$config['dsn'] = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
		}

		$this->_connect($config);
		if (!empty($config['encoding'])) {
			$this->setEncoding($config['encoding']);
		}
		if (!empty($config['schema'])) {
			$this->setSchema($config['schema']);
		}
		return true;
	}

/**
 * Returns whether php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 **/

	public function enabled() {
		return in_array('pgsql', PDO::getAvailableDrivers());
	}

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return Cake\Model\Datasource\Database\Statement
 **/
	public  function prepare($sql) {
		$statement = $this->connection()->prepare($sql);
		return new Statement($statement, $this);
	}

/**
 * Sets connection encoding
 *
 * @return void
 **/
	public function setEncoding($encoding) {
		$this->_connection->exec('SET NAMES ' . $this->_connection->quote($encoding));
	}

/**
 * Sets connection default schema, if any relation defined in a query is not fully qualified
 * postgres will fallback to looking the relation into defined default schema
 *
 * @return void
 **/
	public function setSchema($schema) {
		$this->_connection->exec('SET search_path TO ' . $this->_connection->quote($schema));
	}

}
