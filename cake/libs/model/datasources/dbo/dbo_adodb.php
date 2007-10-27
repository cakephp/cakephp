<?php
/* SVN FILE: $Id$ */

/**
 * AdoDB layer for DBO.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.datasources.dbo
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Include AdoDB files.
 */
vendor ('adodb' . DS . 'adodb.inc');

/**
 * AdoDB DBO implementation.
 *
 * Database abstraction implementation for the AdoDB library.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.datasources.dbo
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
	 var $_adodb_column_types = array(
	 	'C' => 'string',
		'X' => 'text',
		'D' => 'date',
		'T' => 'timestamp',
		'L' => 'boolean',
		'N' => 'float',
		'I' => 'integer',
		'R' => 'integer', // denotes auto-increment or counter field
		'B' => 'binary'
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

		$this->_adodb = NewADOConnection($adodb_driver);

		$this->startQuote = $this->_adodb->nameQuote;
		$this->endQuote = $this->_adodb->nameQuote;

		$this->connected = $this->_adodb->$connect($config['host'], $config['login'], $config['password'], $config['database']);
		return $this->connected;
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
 * Returns a row from given resultset as an array .
 *
 * @return array The fetched row as an array
 */
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

		if (!is_object($this->_result) || $this->_result->EOF) {
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

		  if (!sizeof($tables) > 0) {
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
										'type' => $this->column($column->type)
									);
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
	 	if (empty($limit)) {
	 		return null;
	 	}
		return " LIMIT {$limit}" . ($offset ? "{$offset}" : null);
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
		if (isset($this->_result)) {
			$adodb_metatyper = &$this->_result;
		} else {
			$adodb_metatyper = &$this->_adodb->execute('Select 1');
		}

		$interpreted_type = $adodb_metatyper->MetaType($real);
		if (!isset($this->_adodb_column_types[$interpreted_type])) {
			return 'text';
		}

		return $this->_adodb_column_types[$interpreted_type];
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
	function fields(&$model, $alias = null, $fields = null, $quote = true) {
		if (empty($alias)) {
			$alias = $model->alias;
		}

		if (!is_array($fields)) {
			if ($fields != null) {
				if (strpos($fields, ',')) {
					$fields = explode(',', $fields);
				} else {
					$fields = array($fields);
				}
				$fields = array_map('trim', $fields);
			} else {
				foreach ($model->_tableInfo->value as $field) {
					$fields[] = $field['name'];
				}
			}
		}

		$count = count($fields);

		if ($count >= 1 && $fields[0] != '*' && strpos($fields[0], 'COUNT(*)') === false) {
			for ($i = 0; $i < $count; $i++) {
				if (!preg_match('/^.+\\(.*\\)/', $fields[$i])) {
					$prepend = '';
					if (strpos($fields[$i], 'DISTINCT') !== false) {
						$prepend = 'DISTINCT ';
						$fields[$i] = trim(r('DISTINCT', '', $fields[$i]));
					}

					$dot = strrpos($fields[$i], '.');
					if ($dot === false) {
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
 * Enter description here...
 *
 * @param unknown_type $results
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
		if (!empty($this->results) && $row = $this->results) {
			$resultRow = array();
			$fields = array_keys($row);
			$count = count($fields);
			$i = 0;
			for ($i = 0; $i < $count; $i++) { //$row as $index => $field) {
				list($table, $column) = $this->map[$i];
				$resultRow[$table][$column] = $row[$fields[$i]];
			}
			return $resultRow;
		} else {
			return false;
		}
	}
/**
 * Inserts multiple values into a join table
 *
 * @param string $table
 * @param string $fields
 * @param array $values
 */
	function insertMulti($table, $fields, $values) {
		$count = count($values);
		for ($x = 0; $x < $count; $x++) {
			$this->query("INSERT INTO {$table} ({$fields}) VALUES {$values[$x]}");
		}
	}
}
?>