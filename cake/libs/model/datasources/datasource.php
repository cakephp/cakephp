<?php
/* SVN FILE: $Id$ */
/**
 * DataSource base class
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.model.datasources
 * @since         CakePHP(tm) v 0.10.5.1790
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * DataSource base class
 *
 * Long description for file
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model.datasources
 */
class DataSource extends Object {
/**
 * Are we connected to the DataSource?
 *
 * @var boolean
 * @access public
 */
	var $connected = false;
/**
 * Print full query debug info?
 *
 * @var boolean
 * @access public
 */
	var $fullDebug = false;
/**
 * Error description of last query
 *
 * @var unknown_type
 * @access public
 */
	var $error = null;
/**
 * String to hold how many rows were affected by the last SQL operation.
 *
 * @var string
 * @access public
 */
	var $affected = null;
/**
 * Number of rows in current resultset
 *
 * @var int
 * @access public
 */
	var $numRows = null;
/**
 * Time the last query took
 *
 * @var int
 * @access public
 */
	var $took = null;
/**
 * The starting character that this DataSource uses for quoted identifiers.
 *
 * @var string
 */
	var $startQuote = null;
/**
 * The ending character that this DataSource uses for quoted identifiers.
 *
 * @var string
 */
	var $endQuote = null;
/**
 * Enter description here...
 *
 * @var array
 * @access protected
 */
	var $_result = null;
/**
 * Queries count.
 *
 * @var int
 * @access protected
 */
	var $_queriesCnt = 0;
/**
 * Total duration of all queries.
 *
 * @var unknown_type
 * @access protected
 */
	var $_queriesTime = null;
/**
 * Log of queries executed by this DataSource
 *
 * @var unknown_type
 * @access protected
 */
	var $_queriesLog = array();
/**
 * Maximum number of items in query log, to prevent query log taking over
 * too much memory on large amounts of queries -- I we've had problems at
 * >6000 queries on one system.
 *
 * @var int Maximum number of queries in the queries log.
 * @access protected
 */
	var $_queriesLogMax = 200;
/**
 * Caches serialzed results of executed queries
 *
 * @var array Maximum number of queries in the queries log.
 * @access protected
 */
	var $_queryCache = array();
/**
 * The default configuration of a specific DataSource
 *
 * @var array
 * @access protected
 */
	var $_baseConfig = array();
/**
 * Holds references to descriptions loaded by the DataSource
 *
 * @var array
 * @access private
 */
	var $__descriptions = array();
/**
 * Holds a list of sources (tables) contained in the DataSource
 *
 * @var array
 * @access protected
 */
	var $_sources = null;
/**
 * A reference to the physical connection of this DataSource
 *
 * @var array
 * @access public
 */
	var $connection = null;
/**
 * The DataSource configuration
 *
 * @var array
 * @access public
 */
	var $config = array();
/**
 * The DataSource configuration key name
 *
 * @var string
 * @access public
 */
	var $configKeyName = null;
/**
 * Whether or not this DataSource is in the middle of a transaction
 *
 * @var boolean
 * @access protected
 */
	var $_transactionStarted = false;
/**
 * Whether or not source data like available tables and schema descriptions
 * should be cached
 *
 * @var boolean
 */
	var $cacheSources = true;
/**
 * Constructor.
 */
	function __construct($config = array()) {
		parent::__construct();
		$this->setConfig($config);
	}
/**
 * Caches/returns cached results for child instances
 *
 * @return array
 */
	function listSources($data = null) {
		if ($this->cacheSources === false) {
			return null;
		}

		if ($this->_sources !== null) {
			return $this->_sources;
		}

		$key = ConnectionManager::getSourceName($this) . '_' . $this->config['database'] . '_list';
		$key = preg_replace('/[^A-Za-z0-9_\-.+]/', '_', $key);
		$sources = Cache::read($key, '_cake_model_');

		if (empty($sources)) {
			$sources = $data;
			Cache::write($key, $data, '_cake_model_');
		}

		$this->_sources = $sources;
		return $sources;
	}
/**
 * Convenience method for DboSource::listSources().  Returns source names in lowercase.
 *
 * @return array
 */
	function sources($reset = false) {
		if ($reset === true) {
			$this->_sources = null;
		}
		return array_map('strtolower', $this->listSources());
	}
/**
 * Returns a Model description (metadata) or null if none found.
 *
 * @param Model $model
 * @return mixed
 */
	function describe($model) {
		if ($this->cacheSources === false) {
			return null;
		}
		$table = $this->fullTableName($model, false);
		if (isset($this->__descriptions[$table])) {
			return $this->__descriptions[$table];
		}
		$cache = $this->__cacheDescription($table);

		if ($cache !== null) {
			$this->__descriptions[$table] =& $cache;
			return $cache;
		}
		return null;
	}
/**
 * Begin a transaction
 *
 * @return boolean Returns true if a transaction is not in progress
 */
	function begin(&$model) {
		return !$this->_transactionStarted;
	}
/**
 * Commit a transaction
 *
 * @return boolean Returns true if a transaction is in progress
 */
	function commit(&$model) {
		return $this->_transactionStarted;
	}
/**
 * Rollback a transaction
 *
 * @return boolean Returns true if a transaction is in progress
 */
	function rollback(&$model) {
		return $this->_transactionStarted;
	}
/**
 * Converts column types to basic types
 *
 * @param string $real Real  column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 */
	function column($real) {
		return false;
	}
/**
 * Used to create new records. The "C" CRUD.
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $model The Model to be created.
 * @param array $fields An Array of fields to be saved.
 * @param array $values An Array of values to save.
 * @return boolean success
 */
	function create(&$model, $fields = null, $values = null) {
		return false;
	}
/**
 * Used to read records from the Datasource. The "R" in CRUD
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $model The model being read.
 * @param array $queryData An array of query data used to find the data you want
 * @return mixed
 */
	function read(&$model, $queryData = array()) {
		return false;
	}
/**
 * Update a record(s) in the datasource.
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $model Instance of the model class being updated
 * @param array $fields Array of fields to be updated
 * @param array $values Array of values to be update $fields to.
 * @return boolean Success
 */
	function update(&$model, $fields = null, $values = null) {
		return false;
	}
/**
 * Delete a record(s) in the datasource.
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $model The model class having record(s) deleted
 * @param mixed $id Primary key of the model 
 */
	function delete(&$model, $id = null) {
		if ($id == null) {
			$id = $model->id;
		}
	}
/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param unknown_type $source
 * @return in
 */
	function lastInsertId($source = null) {
		return false;
	}
/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param unknown_type $source
 * @return in
 */
	function lastNumRows($source = null) {
		return false;
	}
/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param unknown_type $source
 * @return in
 */
	function lastAffected($source = null) {
		return false;
	}
/**
 * Check whether the conditions for the Datasource being available
 * are satisfied.  Often used from connect() to check for support
 * before establishing a connection.
 *
 * @return boolean Whether or not the Datasources conditions for use are met.
 **/
	function enabled() {
		return true;
	}
/**
 * Returns true if the DataSource supports the given interface (method)
 *
 * @param string $interface The name of the interface (method)
 * @return boolean True on success
 */
	function isInterfaceSupported($interface) {
		$methods = get_class_methods(get_class($this));
		$methods = strtolower(implode('|', $methods));
		$methods = explode('|', $methods);
		$return = in_array(strtolower($interface), $methods);
		return $return;
	}
/**
 * Sets the configuration for the DataSource
 *
 * @param array $config The configuration array
 * @return void
 */
	function setConfig($config = array()) {
		$this->config = array_merge($this->_baseConfig, $this->config, $config);
	}
/**
 * Cache the DataSource description
 *
 * @param string $object The name of the object (model) to cache
 * @param mixed $data The description of the model, usually a string or array
 */
	function __cacheDescription($object, $data = null) {
		if ($this->cacheSources === false) {
			return null;
		}

		if ($data !== null) {
			$this->__descriptions[$object] =& $data;
		}

		$key = ConnectionManager::getSourceName($this) . '_' . $object;
		$cache = Cache::read($key, '_cake_model_');

		if (empty($cache)) {
			$cache = $data;
			Cache::write($key, $cache, '_cake_model_');
		}

		return $cache;
	}
/**
 * Enter description here...
 *
 * @param unknown_type $query
 * @param unknown_type $data
 * @param unknown_type $association
 * @param unknown_type $assocData
 * @param Model $model
 * @param Model $linkModel
 * @param array $stack
 * @return unknown
 */
	function insertQueryData($query, $data, $association, $assocData, &$model, &$linkModel, $stack) {
		$keys = array('{$__cakeID__$}', '{$__cakeForeignKey__$}');

		foreach ($keys as $key) {
			$val = null;

			if (strpos($query, $key) !== false) {
				switch ($key) {
					case '{$__cakeID__$}':
						if (isset($data[$model->alias]) || isset($data[$association])) {
							if (isset($data[$model->alias][$model->primaryKey])) {
								$val = $data[$model->alias][$model->primaryKey];
							} elseif (isset($data[$association][$model->primaryKey])) {
								$val = $data[$association][$model->primaryKey];
							}
						} else {
							$found = false;
							foreach (array_reverse($stack) as $assoc) {
								if (isset($data[$assoc]) && isset($data[$assoc][$model->primaryKey])) {
									$val = $data[$assoc][$model->primaryKey];
									$found = true;
									break;
								}
							}
							if (!$found) {
								$val = '';
							}
						}
					break;
					case '{$__cakeForeignKey__$}':
						foreach ($model->__associations as $id => $name) {
							foreach ($model->$name as $assocName => $assoc) {
								if ($assocName === $association) {
									if (isset($assoc['foreignKey'])) {
										$foreignKey = $assoc['foreignKey'];

										if (isset($data[$model->alias][$foreignKey])) {
											$val = $data[$model->alias][$foreignKey];
										} elseif (isset($data[$association][$foreignKey])) {
											$val = $data[$association][$foreignKey];
										} else {
											$found = false;
											foreach (array_reverse($stack) as $assoc) {
												if (isset($data[$assoc]) && isset($data[$assoc][$foreignKey])) {
													$val = $data[$assoc][$foreignKey];
													$found = true;
													break;
												}
											}
											if (!$found) {
												$val = '';
											}
										}
									}
									break 3;
								}
							}
						}
					break;
				}
				if (empty($val) && $val !== '0') {
					return false;
				}
				$query = str_replace($key, $this->value($val, $model->getColumnType($model->primaryKey)), $query);
			}
		}
		return $query;
	}
/**
 * To-be-overridden in subclasses.
 *
 * @param unknown_type $model
 * @param unknown_type $key
 * @return unknown
 */
	function resolveKey($model, $key) {
		return $model->alias . $key;
	}
/**
 * Closes the current datasource.
 *
 */
	function __destruct() {
		if ($this->_transactionStarted) {
			$null = null;
			$this->rollback($null);
		}
		if ($this->connected) {
			$this->close();
		}
	}
}
?>