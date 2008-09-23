<?php
/* SVN FILE: $Id$ */

/**
 * ODBC for DBO
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.dbo
 * @since			CakePHP(tm) v 0.10.5.1790
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Short description for class.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.datasources.dbo
 */
class DboOdbc extends DboSource {

/**
 * Driver description
 *
 * @var string
 */
	var $description = "ODBC DBO Driver";

/**
 * Table/column starting quote
 *
 * @var string
 */
	var $startQuote = "`";

/**
 * Table/column end quote
 *
 * @var string
 */
	var $endQuote = "`";

/**
 * Driver base configuration
 *
 * @var array
 */
	var $_baseConfig = array('persistent' => true,
				'login' => 'root',
				'password' => '',
				'database' => 'cake',
				'connect'  => 'odbc_pconnect'
	);

/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $columns = array();

	//	var $columns = array('primary_key' => array('name' => 'int(11) DEFAULT NULL auto_increment'),
	//						'string' => array('name' => 'varchar', 'limit' => '255'),
	//						'text' => array('name' => 'text'),
	//						'integer' => array('name' => 'int', 'limit' => '11'),
	//						'float' => array('name' => 'float'),
	//						'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d h:i:s', 'formatter' => 'date'),
	//						'timestamp' => array('name' => 'datetime', 'format' => 'Y-m-d h:i:s', 'formatter' => 'date'),
	//						'time' => array('name' => 'time', 'format' => 'h:i:s', 'formatter' => 'date'),
	//						'date' => array('name' => 'date', 'format' => 'Y-m-d', 'formatter' => 'date'),
	//						'binary' => array('name' => 'blob'),
	//						'boolean' => array('name' => 'tinyint', 'limit' => '1'));

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
	function connect() {
		$config = $this->config;
		$connect = $config['connect'];

		$this->connected = false;
		$this->connection = $connect($config['database'], $config['login'], $config['password']);

		if ($this->connection) {
			$this->connected = true;
		}

		return $this->connected;
	}

/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	function disconnect() {
		return @odbc_close($this->connection);
	}
/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 * @access protected
 */
	function _execute($sql) {
		return odbc_exec($this->connection, $sql);
	}
/**
 * Returns an array of sources (tables) in the database.
 *
 * @return array Array of tablenames in the database
 */
	function listSources() {

		$cache = parent::listSources();
		if ($cache != null) {
			return $cache;
		}

		/*$result = odbc_tables($this->connection);
		if (function_exists('odbc_fetch_row')) {
			echo 'GOOD';
		} else {
			echo 'BAD';
		}*/

		$result = odbc_tables($this->connection);

		$tables = array();
		while (odbc_fetch_row($result)) {
			array_push($tables, odbc_result($result, "TABLE_NAME"));
		}

		parent::listSources($tables);
		return $tables;
	}
/**
 * Returns an array of the fields in given table name.
 *
 * @param Model $model Model object to describe
 * @return array Fields in table. Keys are name and type
 */
	function &describe(&$model) {
		$cache=parent::describe($model);

		if ($cache != null) {
				return $cache;
		}

		$fields = array();
		$sql = 'SELECT * FROM ' . $this->fullTableName($model);
		$result = odbc_exec($this->connection, $sql);

		$count = odbc_num_fields($result);

		for ($i = 1; $i <= $count; $i++) {
				$cols[$i - 1] = odbc_field_name($result, $i);
		}

		foreach ($cols as $column) {
			$type = odbc_field_type(odbc_exec($this->connection, "SELECT " . $column . " FROM " . $this->fullTableName($model)), 1);
			$fields[$column] = array('type' => $type);
		}

		$this->__cacheDescription($model->tablePrefix . $model->table, $fields);
		return $fields;
	}
	
/**
 * Name
 *
 * @param string $data 
 * @access public
 * @return void
 */
	function name($data) {
		if ($data == '*') {
				return '*';
		}

		$pos = strpos($data, '`');

		if ($pos === false) {
				$data = '' . str_replace('.', '.', $data) . '';
		//$data = '`'. str_replace('.', '`.`', $data) .'`';
		}

		return $data;
	}

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The column into which this data will be inserted
 * @return string Quoted and escaped
 * @todo Add logic that formats/escapes data based on column type
 */
	function value($data, $column = null) {
		$parent=parent::value($data, $column);

		if ($parent != null) {
				return $parent;
		}

		if ($data === null) {
				return 'NULL';
		}

		if (!is_numeric($data)) {
				$return = "'" . $data . "'";
		} else {
				$return = $data;
		}

		return $return;
	}

/**
 * Not sure about this one, MySQL needs it but does ODBC?  Safer just to leave it
 * Translates between PHP boolean values and MySQL (faked) boolean values
 *
 * @param mixed $data Value to be translated
 * @return mixed Converted boolean value
 */
	function boolean($data) {
		if ($data === true || $data === false) {
			if ($data === true) {
				return 1;
			}
			return 0;
		} else {
			if (intval($data !== 0)) {
				return true;
			}
			return false;
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
			if (odbc_autocommit($this->connection, false)) {
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
			if (odbc_commit($this->connection)) {
				$this->_transactionStarted = false;
				return true;
			}
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
			$this->_transactionStarted=false;
			return odbc_rollback($this->connection);
		}
		return false;
	}

/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message with error number
 */
	function lastError() {
		if (odbc_errormsg($this->connection)) {
			return odbc_error($this->connection) . ': ' . odbc_errormsg($this->connection);
		}
		return null;
	}

/**
 * Returns number of affected rows in previous database operation. If no previous operation exists,
 * this returns false.
 *
 * @return integer Number of affected rows
 */
	function lastAffected() {
		if ($this->hasResult()) {
			return false;
		}
		return false;
	}

/**
 * Returns number of rows in previous resultset. If no previous resultset exists,
 * this returns false.
 *
 * @return int Number of rows in resultset
 */
	function lastNumRows() {
		if($this->hasResult()) {
			$counter = 0;
			if(@odbc_fetch_into($this->_result, $results)) {
				return count($results);
			} else {
				return null;
			}
		}
		return null;
	}


/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param unknown_type $source
 * @return int
 */
	function lastInsertId($source = null) {
		$result=$this->fetchRow('SELECT @@IDENTITY');
		return $result[0];
	}

/**
 * Enter description here...
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 */
	function column($real) {
		if (is_array($real)) {
			$col=$real['name'];
			if (isset($real['limit'])) {
				$col .= '(' . $real['limit'] . ')';
			}
			return $col;
		}
		return $real;
	}

/**
* Enter description here...
*
* @param unknown_type $results
*/
	function resultSet(&$results) {
		$this->results =& $results;
		$num_fields = odbc_num_fields($results);
		$this->map = array();
		$index = 0;
		$j = 0;
		while($j < $num_fields) {
			$columnName = odbc_field_name($results, $j+1);

			if(strpos($columnName, '_dot_') !== false) {
				$parts = explode('_dot_', $columnName);
				$this->map[$index++] = array($parts[0], $parts[1]);
			} else {
				$this->map[$index++] = array(0, $columnName);
			}
			$j++;
		}
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
			$alias = $model->name;
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
				foreach($model->tableToModel as $tableName => $modelName) {
					foreach($this->__descriptions[$model->tablePrefix .$tableName] as $field => $type) {
						$fields[] = $modelName .'.' .$field;
					}
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

					if(strrpos($fields[$i], '.') === false) {
						$fields[$i] = $prepend . $this->name($alias) . '.' . $this->name($fields[$i]) . ' AS ' . $this->name($alias . '_dot_' . $fields[$i]);
					} else {
						$build = explode('.', $fields[$i]);
						$fields[$i] = $prepend . $this->name($build[0]) . '.' . $this->name($build[1]) . ' AS ' . $this->name($build[0] . '_dot_' . $build[1]);
					}
				}
			}
		}
		return $fields;
	}


/**
 * Fetches the next row from the current result set
 *
 * @return unknown
 */
	function fetchResult() {
		if($row = odbc_fetch_row($this->results)) {
			$resultRow = array();
			$numFields = odbc_num_fields($this->results);
			$i = 0;
			for($i = 0; $i < $numFields; $i++) {
				list($table, $column) = $this->map[$i];
				$resultRow[$table][$column] = odbc_result($this->results, $i +1);
			}
			return $resultRow;
		} else {
			return false;
		}
	}

}
?>