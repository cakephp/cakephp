<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
  * MySQL layer for DBO
  * 
  * @filesource 
  * @author CakePHP Authors/Developers
  * @copyright Copyright (c) 2005, CakePHP Authors/Developers
  * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs.dbo
  * @since CakePHP v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
  * Include DBO.
  */
uses('dbo');

/**
  * MySQL layer for DBO.
  *
  * @package cake
  * @subpackage cake.libs.dbo
  * @since CakePHP v 0.2.9
  */
class DBO_MySQL extends DBO
{

/**
  * Connects to the database using options in the given configuration array.
  *
  * @param array $config Configuration array for connecting
  * @return boolean True if the database could be connected, else false
  */
   function connect ($config) 
   {
      if ($config) 
      {
         $this->config = $config;
         $this->_conn = mysql_pconnect($config['host'],$config['login'],$config['password']);
      }
      $this->connected = $this->_conn? true: false;

      if($this->connected)
         return mysql_select_db($config['database'], $this->_conn);
      else
         die('Could not connect to DB.');
   }

/**
  * Disconnects from database.
  *
  * @return boolean True if the database could be disconnected, else false
  */
   function disconnect () 
   {
      return mysql_close();
   }

/**
  * Executes given SQL statement.
  *
  * @param string $sql SQL statement
  * @return resource Result resource identifier
  */
   function execute ($sql) 
   {
      return mysql_query($sql);
   }

/**
  * Returns a row from given resultset as an array .
  *
  * @param bool $assoc Associative array only, or both?
  * @return array The fetched row as an array
  */
   function fetchRow ($assoc=false) 
   {
      //return mysql_fetch_array($this->_result, $assoc? MYSQL_ASSOC: MYSQL_BOTH);
		$this->mysqlResultSet($this->_result);
		$resultRow = $this->fetchResult();
		return $resultRow;

   }

/**
  * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
  *
  * @return array Array of tablenames in the database
  */
   function tablesList () 
   {
      $result = mysql_list_tables($this->config['database']);

      if (!$result) 
      {
         trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
         exit;
      }
      else 
      {
         $tables = array();
         while ($line = mysql_fetch_array($result))
         {
            $tables[] = $line[0];
         }
         return $tables;
      }
   }

/**
  * Returns an array of the fields in given table name.
  *
  * @param string $table_name Name of database table to inspect
  * @return array Fields in table. Keys are name and type
  */
   function fields ($table_name)
   {
      $fields = false;
      $cols = $this->all("DESC {$table_name}");

      foreach ($cols as $column)
        // $fields[] = array('name'=>$column['Field'], 'type'=>$column['Type']);
			$fields[] = array('name'=>$column[0]['Field'], 'type'=>$column[0]['Type']);

      return $fields;
   }

/**
  * Returns a quoted and escaped string of $data for use in an SQL statement.
  *
  * @param string $data String to be prepared for use in an SQL statement
  * @return string Quoted and escaped
  */
   function prepareValue ($data)
   {
      return "'".mysql_real_escape_string($data)."'";
   }

/**
  * Returns a formatted error message from previous database operation.
  *
  * @return string Error message with error number
  */
   function lastError () 
   {
      return mysql_errno()? mysql_errno().': '.mysql_error(): null;
   }

/**
  * Returns number of affected rows in previous database operation. If no previous operation exists, this returns false.
  *
  * @return int Number of affected rows
  */
   function lastAffected ()
   {
      return $this->_result? mysql_affected_rows(): false;
   }

/**
  * Returns number of rows in previous resultset. If no previous resultset exists, 
  * this returns false.
  *
  * @return int Number of rows in resultset
  */
   function lastNumRows () 
   {
      return $this->_result? @mysql_num_rows($this->_result): false;
   }

/**
  * Returns the ID generated from the previous INSERT operation.
  *
  * @return int 
  */
   function lastInsertId () 
   {
      return mysql_insert_id();
   }

/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
   function selectLimit ($limit, $offset=null)
   {
      return $limit? " LIMIT {$limit}".($offset? "{$offset}": null): null;
   }

/**
 * Enter description here...
 *
 * @param unknown_type $results
 */
   function mysqlResultSet($results)
   {
      $this->results = $results;
      $this->map = array();
      $index = 0;
      $num_fields = mysql_num_fields($results);
      $j=0;
      
      while ($j < $num_fields)
      {
         $column = mysql_fetch_field($results,$j);
         
         if(!empty($column->table))
         {
            $this->map[$index++] = array($column->table, $column->name);
         }
         else
         {
            $this->map[$index++] = array(0, $column->name);
         }
         $j++;
      }
   }
		
/**
 * Enter description here...
 *
 * @return unknown
 */
   function fetchResult()
   {
      if ($row = mysql_fetch_row($this->results))
      {
         $resultRow = array();
         $i =0;
         
         foreach ($row as $index => $field)
         {
            list($table, $column) = $this->map[$index];
            $resultRow[Inflector::singularize($table)][$column] = $row[$index];
            $i++;
         }
         return $resultRow;
      }
      else
      {
         return false;
      }
   }
}

?>