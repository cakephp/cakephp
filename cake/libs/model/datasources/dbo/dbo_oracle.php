<?php
/* SVN FILE: $Id$ */
/**
 * Oracle layer for DBO
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP : Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.datasources.dbo
 * @since			CakePHP v 1.2.0.4041
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Include DBO.
 */
uses('model'.DS.'datasources'.DS.'dbo_source');
/**
 * Short description for class.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.datasources.dbo
 */
class DboOracle extends DboSource {
 /**
 * Enter description here...
 *
 * @var unknown_type
 * @access public
 */
	var $config;
 /**
 * Enter description here...
 *
 * @var unknown_type
 * @access public
 */
	var $alias = '';
	
 /**
  * The name of the model's sequence
  *
  * @var unknown_type
  */
 
	var $sequence = '';
	
 /**
 * Enter description here...
 *
 * @var unknown_type
 * @access public
 */
	var $columns = array('primary_key' => array('name' => 'number NOT NULL'),
								'string' => array('name' => 'varchar2', 'limit' => '255'),
								'text' => array('name' => 'varchar2'),
								'integer' => array('name' => 'numeric'),
								'float' => array('name' => 'float'),
								'datetime' => array('name' => 'date'),
								'timestamp' => array('name' => 'date'),
								'time' => array('name' => 'date'),
								'date' => array('name' => 'date'),
								'binary' => array('name' => 'bytea'),
								'boolean' => array('name' => 'boolean'),
								'number' => array('name' => 'numeric'),
								'inet' => array('name' => 'inet'));
 /**
 * Enter description here...
 *
 * @var unknown_type
 * @access protected
 */
	var $_conn;
 /**
 * Enter description here...
 *
 * @var unknown_type
 * @access protected
 */
	var $_limit = -1;
 /**
 * Enter description here...
 *
 * @var unknown_type
 * @access protected
 */
	var $_offset = 0;
 /**
 * Enter description here...
 *
 * @var unknown_type
 * @access protected
 */
	var $_map;
 /**
 * Enter description here...
 *
 * @var unknown_type
 * @access protected
 */
	var $_currentRow;
 /**
 * Enter description here...
 *
 * @var unknown_type
 * @access protected
 */
	var $_numRows;
 /**
 * Enter description here...
 *
 * @var unknown_type
 * @access protected
 */
	var $_results;
/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 * @access public
 */
	function connect() {
		if ($this->connected) {
			return true;
		}
		$config = $this->config;

		if ($config) {
			$this->_conn = ociplogon($config['login'], $config['password'], $config['database']);
		}
		if($this->_conn){
			$this->connected = true;
			$this->execute('alter session set nls_sort=binary_ci');
		}
		if (!$this->connected) {
			return false;
		}
	}

/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 * @access public
 */
	function disconnect() {
		if ($this->_conn) {
			return ocilogoff($this->_conn);
		}
	}
/**
 * Scrape the incoming SQL to create the association map. This is an extremely
 * experimental method that creates the association maps since Oracle will not tell us.
 *
 * @param string $sql
 * @return false if sql is nor a SELECT
 * @access protected
 */
	function _scrapeSQL($sql) {
		if (strpos($sql, 'SELECT') === false) {
			return false;
		}
		$sql = str_replace("\"", '', $sql);
		$preFrom = explode('FROM', $sql);
		$preFrom = $preFrom[0];
		$find	 = array('SELECT');
		$replace = array('');
		$fieldList = trim(str_replace($find, $replace, $preFrom));
		$fields = explode(', ', $fieldList);
		$this->_map = array();

		foreach ($fields as $f) {
			$e = explode('.', $f);
			if (count($e) > 1) {
				$table = $e[0];
				$field = strtolower($e[1]);
			} else {
				$table = 0;
				$field = $e[0];
			}
			$this->_map[] = array($table, $field);
		}
	}
/**
 * Modify a SQL query to limit (and offset) the result set
 *
 * @param int $limit Maximum number of rows to return
 * @param int $offset Row to begin returning
 * @return modified SQL Query
 * @access public
 */
	function limit($limit, $offset = 0) {
		$this->_limit = (float) $limit;
		$this->_offset = (float) $offset;
	}
/**
 * Returns number of rows in previous resultset. If no previous resultset exists,
 * this returns false.
 *
 * @return int Number of rows in resultset
 * @access public
 */
	function lastNumRows() {
		return $this->_numRows;
	}
/**
 * Executes given SQL statement. This is an overloaded method.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier or null
 * @access protected
 */
	function _execute($sql) {
	    #print $sql;
		$this->_scrapeSQL($sql);
		$this->_statementId = ociparse($this->_conn, $sql);

		if (!$this->_statementId) {
			return null;
		}
/*
		if ($this->__transactionStarted) {
			$mode = OCI_DEFAULT;
			print 'default';
		} else {
			$mode = OCI_COMMIT_ON_SUCCESS;
			print 'commit';
		}
*/
		//$mode = OCI_COMMIT_ON_SUCCESS;
		$mode = OCI_DEFAULT;
		
		if (!ociexecute($this->_statementId, $mode)) {
			return false;
		}
		// THIS CAN BE REPLACED WITH a check from ocistatementtype()
		// we're really only executing this for DESCRIBE and SELECT
		if (strpos(strtoupper($sql), 'INSERT') === 0) {
			return $this->_statementId;
		}
		if (strpos(strtoupper($sql), 'UPDATE') === 0) {
			return $this->_statementId;
		}
		if (strpos(strtoupper($sql), 'DELETE') === 0) {
			return $this->_statementId;
		}
		if (strpos(strtoupper($sql), 'ALTER') === 0) {
			return $this->_statementId;
		}
/*
		if (strpos($sql, 'CREATE') >= (int)0) {
			return $this->_statementId;
		}
*/
		if ($this->_limit >= 1) {
			ocisetprefetch($this->_statementId, $this->_limit);
		} else {
			ocisetprefetch($this->_statementId, 3000);
		}
		// fetch occurs here instead of fetchResult in order to get the number of rows
		$this->_numRows = ocifetchstatement($this->_statementId, $this->_results, $this->_offset, $this->_limit, OCI_NUM | OCI_FETCHSTATEMENT_BY_ROW);
		#debug($this->_results);
		$this->_currentRow = 0;
		return $this->_statementId;
	}
/**
 * Enter description here...
 *
 * @return unknown
 * @access public
 */
	function fetchRow() {
		if ($this->_currentRow >= $this->_numRows) {
		    ocifreestatement($this->_statementId);
			return false;
		}
		$resultRow = array();

		foreach ($this->_results[$this->_currentRow] as $index => $field) {
			list($table, $column) = $this->_map[$index];
			if (strpos($column, 'count')) {
				$resultRow[0]['count'] = $field;
			} else {
				$resultRow[$table][$column] = $this->_results[$this->_currentRow][$index];
			}
		}
		$this->_currentRow++;
		return $resultRow;
	}
/**
 * Enter description here...
 *
 * @param unknown_type $sql
 * @return unknown
 * @access public
 */
/*
	function query($sql) {
		$stid = ociparse($this->_conn, $sql);
		$r = ociexecute($stid, OCI_DEFAULT);
		if (!$r) {
			return false;
		}
		$result = array();
		while (ocifetchinto($stid, $row, OCI_ASSOC)) {
			$result[] = $row;
		}
		return $result;
		}
*/
/**
 * Returns an array of tables in the database. If there are no tables, an error is
 * raised and the application exits.
 *
 * @return array tablenames in the database
 * @access public
 */
	function listSources() {
		$sql = 'SELECT view_name AS name FROM user_views UNION SELECT table_name AS name FROM user_tables';
		$stid = ociparse($this->_conn, $sql);
		$r = ociexecute($stid, OCI_DEFAULT);

		if (!$r) {
			return false;
		}
		$tables = array();

		while (ocifetchinto($stid, $row, OCI_ASSOC)) {
			$tables[] = $row['NAME'];
		}
		return $tables;
	}
/**
 * Returns an array of the fields in given table name.
 *
 * @param object instance of a model to inspect
 * @return array Fields in table. Keys are name and type
 * @access public
 */
	function describe(&$model) {
		$cache = parent::describe($model);

		if ($cache != null) {
			return $cache;
		}
		$sql = 'SELECT * FROM user_tab_columns WHERE table_name = \'';
		$sql .= strtoupper($model->table) . '\'';

		if (!$stid = ociparse($this->_conn, $sql)) {
			return false;
		}
		if (!$r = ociexecute($stid, OCI_DEFAULT)) {
			return false;
		}
		$fields = array();

		for ($i=0; ocifetchinto($stid, $row, OCI_ASSOC); $i++) {
			$fields[$i]['name'] = strtolower($row['COLUMN_NAME']);
			$fields[$i]['type'] = $this->column($row['DATA_TYPE']);
		}
		$this->__cacheDescription($this->fullTableName($model, false), $fields);
		return $fields;
	}
/**
 * DANGEROUS. This method quotes Oracle identifiers. This will break all scaffolding
 * compatibility and all of Cake's default assumptions.
 *
 * @param unknown_type $var
 * @return unknown
 * @access public
 */
	function name($var) {
		return $var;
/*
		#print "$var<br>";
		if (strpos($var, '.')) {
			return $var;
		}
		if (strpos($var, "\"") === false) {
			return "\"" . $var . "\"";
		} else {
			return $var;
		}
*/
	}
/**
 * Begin a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions).
 */
	function begin(&$model) {
		//if (parent::begin($model)) {
			//if ($this->execute('BEGIN')) {
				$this->__transactionStarted = true;
				return true;
			//}
		//}
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
			return ocirollback($this->_conn);
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
			$this->__transactionStarted;
			return ocicommit($this->_conn);
		}
		return false;
	}
/**
 * Converts database-layer column types to basic types
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 * @access public
 */
	function column($real) {
		if (is_array($real)) {
			$col = $real['name'];

			if (isset($real['limit'])) {
				$col .= '('.$real['limit'].')';
			}
			return $col;
		} else {
			$real = strtolower($real);
		}
		
		$col = r(')', '', $real);
		$limit = null;

		@list($col, $limit) = explode('(', $col);

		if (in_array($col, array('date', 'timestamp'))) {
			return $col;
		}
		if (strpos($col, 'number') !== false) {
			return 'integer';
		}
		if (strpos($col, 'integer') !== false) {
			return 'integer';
		}
		if (strpos($col, 'char') !== false) {
			return 'string';
		}
		if (strpos($col, 'text') !== false) {
			return 'text';
		}
		if (strpos($col, 'blob') !== false) {
			return 'binary';
		}
		if (in_array($col, array('float', 'double', 'decimal'))) {
			return 'float';
		}
		if ($col == 'boolean') {
			return $col;
		}
		return 'text';
	}
/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @return string Quoted and escaped
 * @access public
 */
	function value($data, $column_type = null) {
		switch ($column_type) {
			case 'date':
				$date = date('Y-m-d H:i:s', strtotime($data));
				return "TO_DATE('$date', 'YYYY-MM-DD HH24:MI:SS')";
			default:
				$data2 = str_replace("'", "''", $data);		
				return "'".$data2."'";
		}
	}
	
/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param string
 * @return int
 * @access public
 */
	function lastInsertId($source) {
		$sequence = (!empty($this->sequence)) ? $this->sequence : 'pk_'.$source;
		$sql = "SELECT $sequence.currval FROM dual";
		$stid = ociparse($this->_conn, $sql);
		$r = ociexecute($stid, OCI_DEFAULT);

		if (!$r) {
			return false;
		}
		$result = array();

		while (ocifetchinto($stid, $row, OCI_ASSOC)) {
			$result[] = $row;
		}
		return $result[0]['CURRVAL'];
	}
/**
 * Returns an array of the fields in given table name.
 *
 * @param object model to inspect
 * @param string alias
 * @param string fields
 * @return array Fields in table. Keys are name and type
 * @access public
 */
/*
	function fields ($model, $alias, $f) {
		$sql = "SELECT column_name FROM user_tab_columns ";
		$sql .= "WHERE table_name = '" . strtoupper($model->table) . "'";

		if (!$stid = ociparse($this->_conn, $sql)) {
			return false;
		}
		if (!$r = ociexecute($stid, OCI_DEFAULT)) {
			return false;
		}
		// not sure if this is supposed to be an array or string
		$fields = $f;

		while (ocifetchinto($stid, $row)) {
			$fields[] = $model->name .'.'. $row[0];
		}
		return $fields;
		}
*/
# THE METHODS BELOW HAVE NOT BEEN TESTED!!!
/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message with error number
 * @access public
 */
	function lastError() {
		$errors = ocierror();

		if( ($errors != null) && (isSet($errors["message"])) ) {
			return($errors["message"]);
		}
		return null;
	}
/**
 * Returns number of affected rows in previous database operation. If no previous operation exists, this returns false.
 *
 * @return int Number of affected rows
 * @access public
 */
	function lastAffected() {
		return $this->_statementId? ocirowcount($this->_statementId): false;
	}
}
?>