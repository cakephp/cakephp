<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Database\Driver;

use Cake\Database\Dialect\SqlserverDialectTrait;
use Cake\Database\Statement\PDOStatement;
use PDO;

/**
 * SQLServer driver.
 */
class Sqlserver extends \Cake\Database\Driver {

	use PDODriverTrait;
	use SqlserverDialectTrait;

/**
 * Base configuration settings for Sqlserver driver
 *
 * @var array
 */
	protected $_baseConfig = [
		'persistent' => true,
		'host' => 'localhost\SQLEXPRESS',
		'login' => '',
		'password' => '',
		'database' => 'cake',
		'encoding' => PDO::SQLSRV_ENCODING_UTF8,
		'flags' => [],
		'init' => [],
		'settings' => [],
		'dsn' => null
	];

/**
 * Establishes a connection to the database server
 *
 * @return boolean true on success
 */
	public function connect() {
		if ($this->_connection) {
			return true;
		}
		$config = $this->_config;
		$config['flags'] += [
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		if (!empty($config['encoding'])) {
			$config['flags'][PDO::SQLSRV_ATTR_ENCODING] = $config['encoding'];
		}
		if (empty($config['dsn'])) {
			$config['dsn'] = "sqlsrv:Server={$config['host']};Database={$config['database']};MultipleActiveResultSets=false";
		}

		$this->_connect($config);

		$connection = $this->connection();
		if (!empty($config['init'])) {
			foreach ((array)$config['init'] as $command) {
				$connection->exec($command);
			}
		}
		if (!empty($config['settings']) && is_array($config['settings'])) {
			foreach ($config['settings'] as $key => $value) {
				$connection->exec("SET {$key} {$value}");
			}
		}
		return true;
	}

/**
 * Returns whether PHP is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 */
	public function enabled() {
		return in_array('sqlsrv', PDO::getAvailableDrivers());
	}

/**
 * Prepares a sql statement to be executed
 *
 * @param string|\Cake\Database\Query $query
 * @return \Cake\Database\StatementInterface
 */
	public function prepare($query) {
		$this->connect();
		$statement = $this->_connection->prepare((string)$query, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL]);
		return new PDOStatement($statement, $this);
	}

}
