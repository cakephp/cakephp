<?php
/**
 * DataSource base class
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
 * @package       Cake.Model.Datasource
 * @since         CakePHP(tm) v 0.10.5.1790
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * DataSource base class
 *
 * DataSources are the link between models and the source of data that models represent.
 *
 * @link          http://book.cakephp.org/2.0/en/models/datasources.html#basic-api-for-datasources
 * @package       Cake.Model.Datasource
 */
class DataSource extends Object {

/**
 * Are we connected to the DataSource?
 *
 * @var bool
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
 * @var bool
 */
	protected $_transactionStarted = false;

/**
 * Whether or not source data like available tables and schema descriptions
 * should be cached
 *
 * @var bool
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
 * @param mixed $data Unused in this class.
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
 * @param Model|string $model The model to describe.
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
 * @return bool Returns true if a transaction is not in progress
 */
	public function begin() {
		return !$this->_transactionStarted;
	}

/**
 * Commit a transaction
 *
 * @return bool Returns true if a transaction is in progress
 */
	public function commit() {
		return $this->_transactionStarted;
	}

/**
 * Rollback a transaction
 *
 * @return bool Returns true if a transaction is in progress
 */
	public function rollback() {
		return $this->_transactionStarted;
	}

/**
 * Converts column types to basic types
 *
 * @param string $real Real column type (i.e. "varchar(255)")
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
 * @param Model $Model The Model to be created.
 * @param array $fields An Array of fields to be saved.
 * @param array $values An Array of values to save.
 * @return bool success
 */
	public function create(Model $Model, $fields = null, $values = null) {
		return false;
	}

/**
 * Used to read records from the Datasource. The "R" in CRUD
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $Model The model being read.
 * @param array $queryData An array of query data used to find the data you want
 * @param int $recursive Number of levels of association
 * @return mixed
 */
	public function read(Model $Model, $queryData = array(), $recursive = null) {
		return false;
	}

/**
 * Update a record(s) in the datasource.
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $Model Instance of the model class being updated
 * @param array $fields Array of fields to be updated
 * @param array $values Array of values to be update $fields to.
 * @param mixed $conditions The array of conditions to use.
 * @return bool Success
 */
	public function update(Model $Model, $fields = null, $values = null, $conditions = null) {
		return false;
	}

/**
 * Delete a record(s) in the datasource.
 *
 * To-be-overridden in subclasses.
 *
 * @param Model $Model The model class having record(s) deleted
 * @param mixed $conditions The conditions to use for deleting.
 * @return bool Success
 */
	public function delete(Model $Model, $conditions = null) {
		return false;
	}

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param mixed $source The source name.
 * @return mixed Last ID key generated in previous INSERT
 */
	public function lastInsertId($source = null) {
		return false;
	}

/**
 * Returns the number of rows returned by last operation.
 *
 * @param mixed $source The source name.
 * @return int Number of rows returned by last operation
 */
	public function lastNumRows($source = null) {
		return false;
	}

/**
 * Returns the number of rows affected by last query.
 *
 * @param mixed $source The source name.
 * @return int Number of rows affected by last query.
 */
	public function lastAffected($source = null) {
		return false;
	}

/**
 * Check whether the conditions for the Datasource being available
 * are satisfied. Often used from connect() to check for support
 * before establishing a connection.
 *
 * @return bool Whether or not the Datasources conditions for use are met.
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
 * @param string $association Name of association model being replaced.
 * @param Model $Model Model instance.
 * @param array $stack The context stack.
 * @return mixed String of query data with placeholders replaced, or false on failure.
 */
	public function insertQueryData($query, $data, $association, Model $Model, $stack) {
		$keys = array('{$__cakeID__$}', '{$__cakeForeignKey__$}');

		$modelAlias = $Model->alias;

		foreach ($keys as $key) {
			if (strpos($query, $key) === false) {
				continue;
			}

			$insertKey = $InsertModel = null;
			switch ($key) {
				case '{$__cakeID__$}':
					$InsertModel = $Model;
					$insertKey = $Model->primaryKey;

					break;
				case '{$__cakeForeignKey__$}':
					foreach ($Model->associations() as $type) {
						foreach ($Model->{$type} as $assoc => $assocData) {
							if ($assoc !== $association) {
								continue;
							}

							if (isset($assocData['foreignKey'])) {
								$InsertModel = $Model->{$assoc};
								$insertKey = $assocData['foreignKey'];
							}

							break 3;
						}
					}

					break;
			}

			$val = $dataType = null;
			if (!empty($insertKey) && !empty($InsertModel)) {
				if (isset($data[$modelAlias][$insertKey])) {
					$val = $data[$modelAlias][$insertKey];
				} elseif (isset($data[$association][$insertKey])) {
					$val = $data[$association][$insertKey];
				} else {
					$found = false;
					foreach (array_reverse($stack) as $assocData) {
						if (isset($data[$assocData]) && isset($data[$assocData][$insertKey])) {
							$val = $data[$assocData][$insertKey];
							$found = true;
							break;
						}
					}

					if (!$found) {
						$val = '';
					}
				}

				$dataType = $InsertModel->getColumnType($InsertModel->primaryKey);
			}

			if (empty($val) && $val !== '0') {
				return false;
			}

			$query = str_replace($key, $this->value($val, $dataType), $query);
		}

		return $query;
	}

/**
 * To-be-overridden in subclasses.
 *
 * @param Model $Model Model instance
 * @param string $key Key name to make
 * @return string Key name for model.
 */
	public function resolveKey(Model $Model, $key) {
		return $Model->alias . $key;
	}

/**
 * Returns the schema name. Override this in subclasses.
 *
 * @return string schema name
 */
	public function getSchemaName() {
		return null;
	}

/**
 * Closes a connection. Override in subclasses
 *
 * @return bool
 */
	public function close() {
		return $this->connected = false;
	}

/**
 * Closes the current datasource.
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
