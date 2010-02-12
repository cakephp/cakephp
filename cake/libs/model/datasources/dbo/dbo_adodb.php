<?php
/* SVN FILE: $Id$ */
/**
 * AdoDB layer for DBO.
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
 * @subpackage    cake.cake.libs.model.datasources.dbo
 * @since         CakePHP(tm) v 0.2.9
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Include AdoDB files.
 */
App::import('Vendor', 'NewADOConnection', array('file' => 'adodb' . DS . 'adodb.inc.php'));
/**
 * AdoDB DBO implementation.
 *
 * Database abstraction implementation for the AdoDB library.
 *
 * @package       cake
 * @subpackage    cake.cake.libs.model.datasources.dbo
 */
class DboAdodb extends DboSource {
/**
 * Enter description here...
 *
 * @var string
 */
	var $description = "ADOdb DBO Driver";
/**
 * ADOConnection object with which we connect.
 *
 * @var ADOConnection The connection object.
 * @access private
 */
	var $_adodb = null;
/**
 * Array translating ADOdb column MetaTypes to cake-supported metatypes
 *
 * @var array
 * @access private
 */
	var $_adodbColumnTypes = array(
		'string' => 'C',
		'text' => 'X',
		'date' => 'D',
		'timestamp' => 'T',
		'time' => 'T',
		'datetime' => 'T',
		'boolean' => 'L',
		'float' => 'N',
		'integer' => 'I',
		'binary' => 'R',
	);
/**
 * ADOdb column definition
 *
 * @var array
 */
	var $columns = array(
		'primary_key' => array('name' => 'R', 'limit' => 11),
		'string' => array('name' => 'C', 'limit' => '255'),
		'text' => array('name' => 'X'),
		'integer' => array('name' => 'I', 'limit' => '11', 'formatter' => 'intval'),
		'float' => array('name' => 'N', 'formatter' => 'floatval'),
		'timestamp' => array('name' => 'T', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'time' => array('name' => 'T',  'format' => 'H:i:s', 'formatter' => 'date'),
		'datetime' => array('name' => 'T', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
		'date' => array('name' => 'D', 'format' => 'Y-m-d', 'formatter' => 'date'),
		'binary' => array('name' => 'B'),
		'boolean' => array('name' => 'L', 'limit' => '1')
	);
/**
 * Connects to the database using options in the given configuration array.
 *
 * @param array $config Configuration array for connecting
 */
	function connect() {
		$config = $this->config;
		$persistent = strrpos($config['connect'], '|p');

		if ($persistent === false) {
			$adodb_driver = $config['connect'];
			$connect = 'Connect';
		} else {
			$adodb_driver = substr($config['connect'], 0, $persistent);
			$connect = 'PConnect';
		}
		if (!$this->enabled()) {
			return false;
		}
		$this->_adodb = NewADOConnection($adodb_driver);

		$this->_adodbDataDict = NewDataDictionary($this->_adodb, $adodb_driver);

		$this->startQuote = $this->_adodb->nameQuote;
		$this->endQuote = $this->_adodb->nameQuote;

		$this->connected = $this->_adodb->$connect($config['host'], $config['login'], $config['password'], $config['database']);
		$this->_adodbMetatyper = &$this->_adodb->execute('Select 1');
		return $this->connected;
	}
/**
 * Check that AdoDB is available.
 *
 * @return boolean
 **/
	function enabled() {
		return function_exists('NewADOConnection');
	}
/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
		return $this->_adodb->Close();
	}
/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 */
	function _execute($sql) {
		global $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		return $this->_adodb->execute($sql);
	}
/**
 * Returns a row from current resultset as an array .
 *
 * @return array The fetched row as an array
 */
	function fetchRow($sql = null) {
		if (!empty($sql) && is_string($sql) && strlen($sql) > 5) {
			if (!$this->execute($sql)) {
				return null;
			}
		}

		if (!$this->hasResult()) {
			return null;
		} else {
			$resultRow = $this->_result->FetchRow();
			$this->resultSet($resultRow);
			return $this->fetchResult();
		}
	}
/**
 * Begin a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions).
 */
	function begin(&$model) {
		if (parent::begin($model)) {
			if ($this->_adodb->BeginTrans()) {
				$this->_transactionStarted = true;
				return true;
			}
		}
		return false;
	}
/**
 * Commit a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	function commit(&$model) {
		if (parent::commit($model)) {
			$this->_transactionStarted = false;
			return $this->_adodb->CommitTrans();
		}
		return false;
	}
/**
 * Rollback a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions,
 * or a transaction has not started).
 */
	function rollback(&$model) {
		if (parent::rollback($model)) {
			return $this->_adodb->RollbackTrans();
		}
		return false;
	}
/**
 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
 *
 * @return array Array of tablenames in the database
 */
	function listSources() {
		$tables = $this->_adodb->MetaTables('TABLES');

		if (!count($tables) > 0) {
			trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
			exit;
		}
		return $tables;
	}
/**
 * Returns an array of the fields in the table used by the given model.
 *
 * @param AppModel $model Model object
 * @return array Fields in table. Keys are name and type
 */
	function describe(&$model) {
		$cache = parent::describe($model);
		if ($cache != null) {
			return $cache;
		}

		$fields = false;
		$cols = $this->_adodb->MetaColumns($this->fullTableName($model, false));

		foreach ($cols as $column) {
			$fields[$column->name] = array(
										'type' => $this->column($column->type),
										'null' => !$column->not_null,
										'length' => $column->max_length,
									);
			if ($column->has_default) {
				$fields[$column->name]['default'] = $column->default_value;
			}
			if ($column->primary_key == 1) {
				$fields[$column->name]['key'] = 'primary';
			}
		}

		$this->__cacheDescription($this->fullTableName($model, false), $fields);
		return $fields;
	}
/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message
 */
	function lastError() {
		return $this->_adodb->ErrorMsg();
	}
/**
 * Returns number of affected rows in previous database operation, or false if no previous operation exists.
 *
 * @return integer Number of affected rows
 */
	function lastAffected() {
		return $this->_adodb->Affected_Rows();
	}
/**
 * Returns number of rows in previous resultset, or false if no previous resultset exists.
 *
 * @return integer Number of rows in resultset
 */
	function lastNumRows() {
		return $this->_result ? $this->_result->RecordCount() : false;
	}
/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @return int
 *
 * @Returns the last autonumbering ID inserted. Returns false if function not supported.
 */
	function lastInsertId() {
		return $this->_adodb->Insert_ID();
	}
/**
 * Returns a LIMIT statement in the correct format for the particular database.
 *
 * @param integer $limit Limit of results returned
 * @param integer $offset Offset from which to start results
 * @return string SQL limit/offset statement
 * @todo Please change output string to whatever select your database accepts. adodb doesn't allow us to get the correct limit string out of it.
 */
	function limit($limit, $offset = null) {
		if ($limit) {
			$rt = '';
			if (!strpos(strtolower($limit), 'limit') || strpos(strtolower($limit), 'limit') === 0) {
				$rt = ' LIMIT';
			}

			if ($offset) {
				$rt .= ' ' . $offset . ',';
			}

			$rt .= ' ' . $limit;
			return $rt;
		}
		return null;
		// please change to whatever select your database accepts
		// adodb doesn't allow us to get the correct limit string out of it
	}
/**
 * Converts database-layer column types to basic types
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 */
	function column($real) {
		$metaTypes = array_flip($this->_adodbColumnTypes);

		$interpreted_type = $this->_adodbMetatyper->MetaType($real);

		if (!isset($metaTypes[$interpreted_type])) {
			return 'text';
		}
		return $metaTypes[$interpreted_type];
	}
/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column_type The type of the column into which this data will be inserted
 * @param boolean $safe Whether or not numeric data should be handled automagically if no column data is provided
 * @return string Quoted and escaped data
 */
	function value($data, $column = null, $safe = false) {
		$parent = parent::value($data, $column, $safe);
		if ($parent != null) {
			return $parent;
		}

		if ($data === null) {
			return 'NULL';
		}

		if ($data === '') {
			return "''";
		}
		return $this->_adodb->qstr($data);
	}

/**
 * Generates the fields list of an SQL query.
 *
 * @param Model $model
 * @param string $alias Alias tablename
 * @param mixed $fields
 * @return array
 */
	function fields(&$model, $alias = null, $fields = array(), $quote = true) {
		if (empty($alias)) {
			$alias = $model->alias;
		}
		$fields = parent::fields($model, $alias, $fields, false);

		if (!$quote) {
			return $fields;
		}
		$count = count($fields);

		if ($count >= 1 && $fields[0] != '*' && strpos($fields[0], 'COUNT(*)') === false) {
			for ($i = 0; $i < $count; $i++) {
				if (!preg_match('/^.+\\(.*\\)/', $fields[$i]) && !preg_match('/\s+AS\s+/', $fields[$i])) {
					$prepend = '';
					if (strpos($fields[$i], 'DISTINCT') !== false) {
						$prepend = 'DISTINCT ';
						$fields[$i] = trim(str_replace('DISTINCT', '', $fields[$i]));
					}

					if (strrpos($fields[$i], '.') === false) {
						$fields[$i] = $prepend . $this->name($alias) . '.' . $this->name($fields[$i]) . ' AS ' . $this->name($alias . '__' . $fields[$i]);
					} else {
						$build = explode('.', $fields[$i]);
						$fields[$i] = $prepend . $this->name($build[0]) . '.' . $this->name($build[1]) . ' AS ' . $this->name($build[0] . '__' . $build[1]);
					}
				}
			}
		}
		return $fields;
	}
/**
 * Build ResultSets and map data
 *
 * @param array $results
 */
	function resultSet(&$results) {
		$num_fields = count($results);
		$fields = array_keys($results);
		$this->results =& $results;
		$this->map = array();
		$index = 0;
		$j = 0;

		while ($j < $num_fields) {
			$columnName = $fields[$j];

			if (strpos($columnName, '__')) {
				$parts = explode('__', $columnName);
				$this->map[$index++] = array($parts[0], $parts[1]);
			} else {
				$this->map[$index++] = array(0, $columnName);
			}
			$j++;
		}
	}
/**
 * Fetches the next row from the current result set
 *
 * @return unknown
 */
	function fetchResult() {
		if (!empty($this->results)) {
			$row = $this->results;
			$this->results = null;
		} else {
			$row = $this->_result->FetchRow();
		}

		if (empty($row)) {
			return false;
		}

		$resultRow = array();
		$fields = array_keys($row);
		$count = count($fields);
		$i = 0;
		for ($i = 0; $i < $count; $i++) { //$row as $index => $field) {
			list($table, $column) = $this->map[$i];
			$resultRow[$table][$column] = $row[$fields[$i]];
		}
		return $resultRow;
	}
/**
 * Generate a database-native column schema string
 *
 * @param array $column An array structured like the following: array('name'=>'value', 'type'=>'value'[, options]),
 *                      where options can be 'default', 'length', or 'key'.
 * @return string
 */
	function buildColumn($column) {
		$name = $type = null;
		extract(array_merge(array('null' => true), $column));

		if (empty($name) || empty($type)) {
			trigger_error('Column name or type not defined in schema', E_USER_WARNING);
			return null;
		}

		//$metaTypes = array_flip($this->_adodbColumnTypes);
		if (!isset($this->_adodbColumnTypes[$type])) {
			trigger_error("Column type {$type} does not exist", E_USER_WARNING);
			return null;
		}
		$metaType = $this->_adodbColumnTypes[$type];
		$concreteType = $this->_adodbDataDict->ActualType($metaType);
		$real = $this->columns[$type];

		//UUIDs are broken so fix them.
		if ($type == 'string' && isset($real['length']) && $real['length'] == 36) {
			$concreteType = 'CHAR';
		}

		$out = $this->name($name) . ' ' . $concreteType;

		if (isset($real['limit']) || isset($real['length']) || isset($column['limit']) || isset($column['length'])) {
			if (isset($column['length'])) {
				$length = $column['length'];
			} elseif (isset($column['limit'])) {
				$length = $column['limit'];
			} elseif (isset($real['length'])) {
				$length = $real['length'];
			} else {
				$length = $real['limit'];
			}
			$out .= '(' . $length . ')';
		}
		$_notNull = $_default = $_autoInc = $_constraint = $_unsigned = false;

		if (isset($column['key']) && $column['key'] == 'primary' && $type == 'integer') {
			$_constraint = '';
			$_autoInc = true;
		} elseif (isset($column['key']) && $column['key'] == 'primary') {
			$_notNull = '';
		} elseif (isset($column['default']) && isset($column['null']) && $column['null'] == false) {
			$_notNull = true;
			$_default = $column['default'];
		} elseif ( isset($column['null']) && $column['null'] == true) {
			$_notNull = false;
			$_default = 'NULL';
		}
		if (isset($column['default']) && $_default == false) {
			$_default = $this->value($column['default']);
		}
		if (isset($column['null']) && $column['null'] == false) {
			$_notNull = true;
		}
		//use concrete instance of DataDict to make the suffixes for us.
		$out .=	$this->_adodbDataDict->_CreateSuffix($out, $metaType, $_notNull, $_default, $_autoInc, $_constraint, $_unsigned);
		return $out;

	}
/**
 * Checks if the result is valid
 *
 * @return boolean True if the result is valid, else false
 */
	function hasResult() {
		return is_object($this->_result) && !$this->_result->EOF;
	}
}
?>