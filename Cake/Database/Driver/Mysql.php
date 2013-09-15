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

use Cake\Database\Dialect\MysqlDialectTrait;
use PDO;

class Mysql extends \Cake\Database\Driver {

	use MysqlDialectTrait;
	use PDODriverTrait;

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
		'timezone' => 'UTC',
		'init' => [],
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

		if ($config['timezone'] === 'UTC') {
			$config['timezone'] = '+0:00';
		}

		$config['init'][] = sprintf("SET time_zone = '%s'", $config['timezone']);
		$config['flags'] += [
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::MYSQL_ATTR_INIT_COMMAND => implode(';', (array)$config['init'])
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
 */
	public function enabled() {
		return in_array('mysql', PDO::getAvailableDrivers());
	}

}
