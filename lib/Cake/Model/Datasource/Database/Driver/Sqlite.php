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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Datasource\Database\Driver;

use Cake\Model\Datasource\Database\Statement\SqliteStatement;
use Cake\Model\Datasource\Database\Dialect\SqliteDialectTrait;
use PDO;

class Sqlite extends \Cake\Model\Datasource\Database\Driver {

	use PDODriverTrait;
	use SqliteDialectTrait;

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
		'init' => [],
		'dsn' => null
	];

/**
 * Establishes a connection to the databse server
 *
 * @param array $config configuration to be used for creating connection
 * @return boolean true on success
 */
	public function connect(array $config) {
		$config += $this->_baseConfig + ['login' => null, 'password' => null];
		$config['flags'] += [
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		];

		if (empty($config['dsn'])) {
			$config['dsn'] = "sqlite:{$config['database']}";
		}

		$this->_connect($config);

		if (!empty($config['init'])) {
			foreach ((array)$config['init'] as $command) {
				$this->connection()->exec($command);
			}
		}

		return true;
	}

/**
 * Returns whether php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 */

	public function enabled() {
		return in_array('sqlite', PDO::getAvailableDrivers());
	}

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return Cake\Model\Datasource\Database\Statement
 */
	public  function prepare($sql) {
		$statement = $this->connection()->prepare($sql);
		return new SqliteStatement($statement, $this);
	}

}
