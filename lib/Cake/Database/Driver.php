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
namespace Cake\Database;

use \Cake\Database\SqlDialectTrait;

/**
 * Represents a database diver containing all specificities for
 * a database engine including its SQL dialect
 *
 */
abstract class Driver {

/**
 * Configuration data.
 *
 * @var array
 */
	protected $_config;

/**
 * Base configuration that is merged into the user
 * supplied configuration data.
 *
 * @var array
 */
	protected $_baseConfig = [];

/**
 * Constructor
 *
 * @param array $config The configuration for the driver.
 * @return void
 */
	public function __construct($config = []) {
		$config += $this->_baseConfig;
		$this->_config = $config;
	}

/**
 * Establishes a connection to the database server
 *
 * @return boolean true con success
 */
	public abstract function connect();

/**
 * Disconnects from database server
 *
 * @return void
 */
	public abstract function disconnect();

/**
 * Returns correct connection resource or object that is internally used
 * If first argument is passed,
 *
 * @return void
 */
	public abstract function connection($connection = null);

/**
 * Returns whether php is able to use this driver for connecting to database
 *
 * @return boolean true if it is valid to use this driver
 */
	public abstract function enabled();

/**
 * Prepares a sql statement to be executed
 *
 * @param string $sql
 * @return Cake\Database\Statement
 */
	public abstract function prepare($sql);

/**
 * Starts a transaction
 *
 * @return boolean true on success, false otherwise
 */
	public abstract function beginTransaction();

/**
 * Commits a transaction
 *
 * @return boolean true on success, false otherwise
 */
	public abstract function commitTransaction();

/**
 * Rollsback a transaction
 *
 * @return boolean true on success, false otherwise
 */
	public abstract function rollbackTransaction();

/**
 * Returns whether this driver supports save points for nested transactions
 *
 * @return boolean true if save points are supported, false otherwise
 */
	public function supportsSavePoints() {
		return true;
	}

/**
 * Returns a value in a safe representation to be used in a query string
 *
 * @return string
 */
	public abstract function quote($value, $type);

/**
 * Checks if the driver supports quoting
 *
 * @return boolean
 */
	public function supportsQuoting() {
		return true;
	}

/**
 * Escapes values for use in schema definitions.
 *
 * @param mixed $value The value to escape.
 * @return string String for use in schema definitions.
 */
	public function schemaValue($value) {
		if (is_null($value)) {
			return 'NULL';
		}
		if ($value === false) {
			return 'FALSE';
		}
		if ($value === true) {
			return 'TRUE';
		}
		if (is_float($value)) {
			return str_replace(',', '.', strval($value));
		}
		if ((is_int($value) || $value === '0') || (
			is_numeric($value) && strpos($value, ',') === false &&
			$value[0] != '0' && strpos($value, 'e') === false)
		) {
			return $value;
		}
		return $this->_connection->quote($value, \PDO::PARAM_STR);
	}

/**
 * Returns last id generated for a table or sequence in database
 *
 * @param string $table table name or sequence to get last insert value from
 * @return string|integer
 */
	public function lastInsertId($table = null) {
		return $this->_connection->lastInsertId($table);
	}

/**
 * Check whether or not the driver is connected.
 *
 * @return boolean
 */
	public function isConnected() {
		return $this->_connection !== null;
	}

/**
 * Destructor
 *
 * @return void
 */
	public function __destruct() {
		$this->_connection = null;
	}

}
