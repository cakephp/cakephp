<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Pear::DB layer for DBO.
  * 
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
  * Include DBO.
  *
  */
uses('dbo');
/**
  * Pear::DB layer for DBO.
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  *
  */
class DBO_Pear extends DBO {

/**
  * Connects to the database using options in the given configuration array.
  *
  * @param array $config Configuration array for connecting
  */
	function connect ($config) {
		die('Please implement DBO::connect() first.');
	}

/**
  * Disconnects from database.
  *
  * @return boolean True if the database could be disconnected, else false
  */
	function disconnect () {
		die('Please implement DBO::disconnect() first.');
	}

/**
  * Executes given SQL statement.
  *
  * @param string $sql SQL statement
  * @return resource Result resource identifier
  */
	function execute ($sql) {
		return $this->_pear->query($sql);
	}

/**
  * Returns a row from given resultset as an array .
  *
  * @param unknown_type $res Resultset
  * @return array The fetched row as an array
  */
	function fetchRow ($result) {
		return $result->fetchRow(DB_FETCHMODE_ASSOC);
	}

/**
  * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
  *
  * @return array Array of tablenames in the database
  */
	function tables () {  // POSTGRESQL ONLY! PEAR:DB DOESN'T SUPPORT LISTING TABLES
		$sql = "SELECT a.relname AS name
         FROM pg_class a, pg_user b
         WHERE ( relkind = 'r') and relname !~ '^pg_' AND relname !~ '^sql_'
         AND relname !~ '^xin[vx][0-9]+' AND b.usesysid = a.relowner
         AND NOT (EXISTS (SELECT viewname FROM pg_views WHERE viewname=a.relname));";

		$result = $this->all($sql);

		if (!$result) {
			trigger_error(ERROR_NO_TABLE_LIST, E_USER_ERROR);
			exit;
		}
		else {
			$tables = array();
			foreach ($result as $item) $tables[] = $item['name'];
			return $tables;
		}
	}

/**
  * Returns an array of the fields in given table name.
  *
  * @param string $table_name Name of database table to inspect
  * @return array Fields in table. Keys are name and type
  */
	function fields ($table_name) {
		$data = $this->_pear->tableInfo($table_name);
		$fields = false;

		foreach ($data as $item)
			$fields[] = array('name'=>$item['name'], 'type'=>$item['type']);

		return $fields;
	}

/**
  * Returns a quoted and escaped string of $data for use in an SQL statement.
  *
  * @param string $data String to be prepared for use in an SQL statement
  * @return string Quoted and escaped
  */
	function prepareValue ($data) {
		return $this->_pear->quoteSmart($data);
	}

/**
  * Returns a formatted error message from previous database operation.
  *
  * @return string Error message
  */
	function lastError () {
		return PEAR::isError($this->_result)? $this->_result->getMessage(): null;
	}

/**
  * Returns number of affected rows in previous database operation. If no previous operation exists, this returns false.
  *
  * @return int Number of affected rows
  */
	function lastAffected () {
		return $this->_pear->affectedRows();
	}

/**
  * Returns number of rows in previous resultset. If no previous resultset exists, 
  * this returns false.
  *
  * @return int Number of rows
  */
	function lastNumRows ($result) {
		if (method_exists($result, 'numRows'))
			return $result->numRows();
		else
			return false;
	}

/**
  * Returns the ID generated from the previous INSERT operation.
  *
  * @param string $table Name of the database table
  * @return int 
  */
	function lastInsertId ($table) {
		return $this->field('id', "SELECT MAX(id) FROM {$table}");
	}


}

?>