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
		'timezone' => null,
		'init' => [],
		'dsn' => null
	];

/**
 * Establishes a connection to the database server
 *
 * @return bool true on success
 */
	public function connect() {
		if ($this->_connection) {
			return true;
		}
		$config = $this->_config;

		if ($config['timezone'] === 'UTC') {
			$config['timezone'] = '+0:00';
		}

		if (!empty($config['timezone'])) {
			$config['init'][] = sprintf("SET time_zone = '%s'", $config['timezone']);
		}

		$config['flags'] += [
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		if ($config['init']) {
			$config['flags'] += [PDO::MYSQL_ATTR_INIT_COMMAND => implode(';', (array)$config['init'])];
		}

		if (!empty($config['ssl_key']) && !empty($config['ssl_cert'])) {
			$config['flags'][PDO::MYSQL_ATTR_SSL_KEY] = $config['ssl_key'];
			$config['flags'][PDO::MYSQL_ATTR_SSL_CERT] = $config['ssl_cert'];
		}
		if (!empty($config['ssl_ca'])) {
			$config['flags'][PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
		}

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
 * @return bool true if it is valid to use this driver
 */
	public function enabled() {
		return in_array('mysql', PDO::getAvailableDrivers());
	}

}
