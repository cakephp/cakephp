<?php
/**
 * DataSource base class
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model.Datasource
 * @since         CakePHP(tm) v 0.10.5.1790
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * DataSource base class
 *
 * @package       Cake.Model.Datasource
 */
class DataSource extends Object {

/**
 * Are we connected to the DataSource?
 *
 * @var boolean
 */
	public $connected = false;

/**
 * The default configuration of a specific DataSource
 *
 * @var array
 */
	protected $_baseConfig = array();

/**
 * Holds references to descriptions loaded by the DataSource
 *
 * @var array
 */
	protected $_descriptions = array();

/**
 * Holds a list of sources (tables) contained in the DataSource
 *
 * @var array
 */
	protected $_sources = null;

/**
 * The DataSource configuration
 *
 * @var array
 */
	public $config = array();

/**
 * Whether or not this DataSource is in the middle of a transaction
 *
 * @var boolean
 */
	protected $_transactionStarted = false;

/**
 * Whether or not source data like available tables and schema descriptions
 * should be cached
 *
 * @var boolean
 */
	public $cacheSources = true;

/**
 * Constructor.
 *
 * @param array $config Array of configuration information for the datasource.
 */
	public function __construct($config = array()) {
		parent::__construct();
		$this->setConfig($config);
	}

/**
 * Caches/returns cached results for child instances
 *
 * @param mixed $data
 * @return array Array of sources available in this datasource.
 */
	public function listSources($data = null) {
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

		return $this->_sources = $sources;
	}

/**
 * Returns a Model description (metadata) or null if none found.
 *
 * @param Model|string $model
 * @return array Array of Metadata for the $model
 */
	public function describe($model) {
		if ($this->cacheSources === false) {
			return null;
		}
		if (is_string($model)) {
			$table = $model;
		} else {
			$table = $model->tablePrefix . $model->table;
		}

		if (isset($this->_descriptions[$table])) {
			return $this->_descriptions[$table];
		}
		$cache = $this->_cacheDescription($table);

		if ($cache !== null) {
			$this->_descriptions[$table] =& $cache;
			return $cache;
		}
		return null;
	}

/**
 * Begin a transaction
 *
 * @return boolean Returns true if a transaction is not in progress
 */
	public function begin() {
		return !$this->_transactionStarted;
	}

/**
 * Commit a transaction
 *
 * @return boolean Returns true if a transaction is in progress
 */
	public function commit() {
		return $this->_transactionStarted;
	}

/**
 * Rollback a transaction
 *
 * @return boolean Returns true if a transaction is in progress
 */
	public function rollback() {
		return $this->_transactionStarted;
	}

/**
 * Converts column types to basic types
 *
 * @param string $real Real  column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 */
	public function column($real) {
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
	public function create(Model $model, $fields = null, $values = null) {
		return false;
	}

/**
 * Used to read records from the Datasource. The "R" in CRUD
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $model The model being read.
 * @param array $queryData An array of query data used to find the data you want
 * @param integer $recursive Number of levels of association
 * @return mixed
 */
	public function read(Model $model, $queryData = array(), $recursive = null) {
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
 * @param mixed $conditions
 * @return boolean Success
 */
	public function update(Model $model, $fields = null, $values = null, $conditions = null) {
		return false;
	}

/**
 * Delete a record(s) in the datasource.
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $model The model class having record(s) deleted
 * @param mixed $conditions The conditions to use for deleting.
 * @return void
 */
	public function delete(Model $model, $id = null) {
		return false;
	}

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param mixed $source
 * @return mixed Last ID key generated in previous INSERT
 */
	public function lastInsertId($source = null) {
		return false;
	}

/**
 * Returns the number of rows returned by last operation.
 *
 * @param mixed $source
 * @return integer Number of rows returned by last operation
 */
	public function lastNumRows($source = null) {
		return false;
	}

/**
 * Returns the number of rows affected by last query.
 *
 * @param mixed $source
 * @return integer Number of rows affected by last query.
 */
	public function lastAffected($source = null) {
		return false;
	}

/**
 * Check whether the conditions for the Datasource being available
 * are satisfied.  Often used from connect() to check for support
 * before establishing a connection.
 *
 * @return boolean Whether or not the Datasources conditions for use are met.
 */
	public function enabled() {
		return true;
	}

/**
 * Sets the configuration for the DataSource.
 * Merges the $config information with the _baseConfig and the existing $config property.
 *
 * @param array $config The configuration array
 * @return void
 */
	public function setConfig($config = array()) {
		$this->config = array_merge($this->_baseConfig, $this->config, $config);
	}

/**
 * Cache the DataSource description
 *
 * @param string $object The name of the object (model) to cache
 * @param mixed $data The description of the model, usually a string or array
 * @return mixed
 */
	protected function _cacheDescription($object, $data = null) {
		if ($this->cacheSources === false) {
			return null;
		}

		if ($data !== null) {
			$this->_descriptions[$object] =& $data;
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
 * Replaces `{$__cakeID__$}` and `{$__cakeForeignKey__$}` placeholders in query data.
 *
 * @param string $query Query string needing replacements done.
 * @param array $data Array of data with values that will be inserted in placeholders.
 * @param string $association Name of association model being replaced
 * @param array $assocData
 * @param Model $model Instance of the model to replace $__cakeID__$
 * @param Model $linkModel Instance of model to replace $__cakeForeignKey__$
 * @param array $stack
 * @return string String of query data with placeholders replaced.
 * @todo Remove and refactor $assocData, ensure uses of the method have the param removed too.
 */
	public function insertQueryData($query, $data, $association, $assocData, Model $model, Model $linkModel, $stack) {
		$keys = array('{$__cakeID__$}', '{$__cakeForeignKey__$}');

		foreach ($keys as $key) {
			$val = null;
			$type = null;

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
						$type = $model->getColumnType($model->primaryKey);
					break;
					case '{$__cakeForeignKey__$}':
						foreach ($model->associations() as $id => $name) {
							foreach ($model->$name as $assocName => $assoc) {
								if ($assocName === $association) {
									if (isset($assoc['foreignKey'])) {
										$foreignKey = $assoc['foreignKey'];
										$assocModel = $model->$assocName;
										$type = $assocModel->getColumnType($assocModel->primaryKey);

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
				$query = str_replace($key, $this->value($val, $type), $query);
			}
		}
		return $query;
	}

/**
 * To-be-overridden in subclasses.
 *
 * @param Model $model Model instance
 * @param string $key Key name to make
 * @return string Key name for model.
 */
	public function resolveKey(Model $model, $key) {
		return $model->alias . $key;
	}

/**
 * Returns the schema name. Override this in subclasses.
 *
 * @return string schema name
 * @access public
 */
	public function getSchemaName() {
		return null;
	}

/**
 * Closes a connection. Override in subclasses
 *
 * @return boolean
 * @access public
 */
	public function close() {
		return $this->connected = false;
	}

/**
 * Closes the current datasource.
 *
 */
	public function __destruct() {
		if ($this->_transactionStarted) {
			$this->rollback();
		}
		if ($this->connected) {
			$this->close();
		}
	}

}
