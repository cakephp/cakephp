<?php
/* SVN FILE: $Id$ */

/**
 * {@link http://pear.php.net/package/DB PEAR::DB} layer for DBO.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
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
 * @subpackage		cake.cake.libs.model.dbo
 * @since			CakePHP v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Create an include path required PEAR libraries.
 */
uses ('model' . DS . 'datasources' . DS . 'dbo_source');

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . PEAR);
vendor ('Pear/DB');

/**
 * {@link http://pear.php.net/package/DB PEAR::DB} layer for DBO.
 *
 * Long description for class
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.dbo
 */
class DBO_Pear extends DboSource{

/**
 * PEAR::DB object with which we connect.
 *
 * @var DB The connection object.
 * @access private
 */
	 var $_pear = null;

/**
 * Connects to the database using options in the given configuration array.
 *
 * @param array $config Configuration array for connecting
 * @return boolean True if the database could be connected, else false
 */
	 function connect($config) {
		  $this->config   =$config;
		  $dsn            =$config['driver'] . '://' . $config['login'] . ':' . $config['password'] . '@'
			  . $config['host'] . '/' . $config['database'];
		  $options=array('debug' => DEBUG - 1,
					  'portability' => DB_PORTABILITY_ALL,);

		  $this->_pear    =&DB::connect($dsn, $options);
		  $this->connected=$this->_pear ? true : false;
		  return !(PEAR::isError($this->_pear));
	 }

/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
	 function disconnect() {
		  die ('Please implement DBO::disconnect() first.');
	 }

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 */
	 function execute($sql) {
		  return $this->_pear->query($sql);
	 }

/**
 * Returns a row from given resultset as an array .
 *
 * @return array The fetched row as an array
 */
	 function fetchRow() {
		  return $this->_result->fetchRow(DB_FETCHMODE_ASSOC);
	 }

/**
 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
 * :WARNING: :TODO: POSTGRESQL & MYSQL ONLY! PEAR::DB doesn't support universal table listing.
 *
 * @return array Array of tablenames in the database
 */
	 function tablesList() {
		  $driver=$this->config['driver'];
		  $tables=array();

		  if ('postgres' == $driver) {
				$sql   ="SELECT a.relname AS name
						FROM pg_class a, pg_user b
						WHERE ( relkind = 'r') and relname !~ '^pg_' AND relname !~ '^sql_'
						AND relname !~ '^xin[vx][0-9]+' AND b.usesysid = a.relowner
						AND NOT (EXISTS (SELECT viewname FROM pg_views WHERE viewname=a.relname));";

				$result=$this->all($sql);

				foreach($result as $item) {
					 $tables[] = $item['name'];
				}
		  } elseif('mysql' == $driver) {
				$result=array();
				$result=mysql_list_tables($this->config['database']);

				while($item = mysql_fetch_array($result)) {
					 $tables[] = $item[0];
				}
		  } else {
				die ('Please implement DBO_Pear::tablesList() for your database driver.');
		  }

		  if (!$result) {
				trigger_error(ERROR_NO_TABLE_LIST, E_USER_ERROR);
				exit;
		  } else {
				return $tables;
		  }
	 }

/**
 * Returns an array of the fields in given table name.
 *
 * @param string $tableName Name of database table to inspect
 * @return array Fields in table. Keys are name and type
 */
	 function fields($tableName) {
		  $data  =$this->_pear->tableInfo($tableName);
		  $fields=false;

		  foreach($data as $item) {
				$fields[] = array('name' => $item['name'],
							'type' => $item['type']);
		  }

		  return $fields;
	 }

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @return string Quoted and escaped
 */
	 function prepareValue($data) {
		  return $this->_pear->quoteSmart($data);
	 }

/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message
 */
	 function lastError() {
		  return PEAR::isError($this->_result) ? $this->_result->getMessage() : null;
	 }

/**
 * Returns number of affected rows in previous database operation. If no previous operation exists, this returns false.
 *
 * @return int Number of affected rows
 */
	 function lastAffected() {
		  return $this->_pear->affectedRows();
	 }

/**
 * Returns number of rows in previous resultset. If no previous resultset exists,
 * this returns false.
 *
 * @return int Number of rows in resultset
 */
	 function lastNumRows() {
		  if (method_exists($this->_result, 'numRows')) {
				return $this->_result->numRows();
		  } else {
				return false;
		  }
	 }

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param string $table Name of the database table
 * @return int
 */
	 function lastInsertId($table) {
		  return $this->field('id', "SELECT MAX(id) FROM {$table}");
	 }

/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
	 function selectLimit($limit, $offset = '0') {
		  return ' ' . $this->_pear->modifyLimitQuery('', $offset, $limit);
	 }
}
?>