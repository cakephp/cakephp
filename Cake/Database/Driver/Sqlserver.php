<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Driver;

use Cake\Database\Dialect\SqlserverDialectTrait;
use PDO;

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
		'host' => 'localhost',
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
			if ($this->pdoDriverName() === 'sqlsrv') {
				$config['dsn'] = "sqlsrv:Server={$config['host']};Database={$config['database']}";
			} else {
				$config['dsn'] = "dblib:host={$config['host']};dbname={$config['database']}";
			}
		}

		$this->_connect($config);

		$connection = $this->connection();
		$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
 * Establishes a connection to the databse server
 *
 * @param array $config configuration to be used for creating connection
 * @return boolean true on success
 */
	protected function _connect(array $config) {
		if ($this->pdoDriverName() !== 'sqlsrv') {
			$connection = new PDO($config['dsn'], $config['login'], $config['password']);
		} else {
			$connection = new PDO($config['dsn'], $config['login'], $config['password'], $config['flags']);
		}
		$this->connection($connection);
		return true;
	}

/**
 * Returns whether php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 */

	public function enabled() {
		return in_array($this->pdoDriverName(), PDO::getAvailableDrivers());
	}

	protected function pdoDriverName() {
		return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'sqlsrv' : 'dblib';
	}

}
