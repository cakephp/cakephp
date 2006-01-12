<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, Cake Software Foundation, Inc. 
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2005, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs.model.datasources.dbo
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Enter description here...
 *
 */
uses('object');

/**
 * Purpose: DBO/ADO
 *
 * Description:
 * A SQL functions wrapper. Provides ability to get results as arrays
 * and query logging (with execution time).
 *
 * Example usage:
 *
 * <code>
 * require_once('dbo_mysql.php'); // or 'dbo_postgres.php'
 *
 * // create and connect the object
 * $db = new DBO_MySQL(array( // or 'DBO_Postgres'
 *    'host'=>'localhost',
 *    'login'=>'username',
 *    'password'=>'password',
 *    'database'=>'database'));
 *
 *  // read the whole query result array (of rows)
 * $all_rows = $db->fetchAll("SELECT a,b,c FROM table");
 *
 *  // read the first row with debugging on
 *  $first_row_only = $db->one("SELECT a,b,c FROM table WHERE a=1", TRUE);
 *
 *  // emulate the usual way of reading query results
 *  if ($db->query("SELECT a,b,c FROM table"))
 *    {
 *      while ($row = $db->fetchArray())
 *          {
 *          print $row['a'].$row['b'].$row['c'];
 *      }
 *  }
 *
 * // show a log of all queries, sorted by execution time
 * $db->showLog(TRUE);
 * </code>
 *
 * @package    cake
 * @subpackage cake.cake.libs.model.datasources.dbo
 * @since      CakePHP v 0.2.9
 */
class DBO extends Object
{

/**
 * Are we connected to the database?
 *
 * @var boolean
 * @access public
 */
   var $connected=FALSE;

/**
 * Connection configuration.
 *
 * @var array
 * @access public
 */
   var $config=FALSE;

/**
 * Enter description here...
 *
 * @var boolean
 * @access public
 */
   var $debug=FALSE;

/**
 * Enter description here...
 *
 * @var boolean
 * @access public
 */
   var $fullDebug=FALSE;

/**
 * Enter description here...
 *
 * @var unknown_type
 * @access public
 */
   var $error=NULL;

/**
 * String to hold how many rows were affected by the last SQL operation.
 *
 * @var unknown_type
 * @access public
 */
   var $affected=NULL;

/**
 * Number of rows in current resultset
 *
 * @var int
 * @access public
 */
   var $numRows=NULL;

/**
 * Time the last query took
 *
 * @var unknown_type
 * @access public
 */
   var $took=NULL;

/**
 * Enter description here...
 *
 * @var unknown_type
 * @access private
 */
   var $_conn=NULL;

/**
 * Enter description here...
 *
 * @var unknown_type
 * @access private
 */
   var $_result=NULL;

/**
 * Queries count.
 *
 * @var unknown_type
 * @access private
 */
   var $_queriesCnt=0;

/**
 * Total duration of all queries.
 *
 * @var unknown_type
 * @access private
 */
   var $_queriesTime=NULL;

/**
 * Enter description here...
 *
 * @var unknown_type
 * @access private
 */
   var $_queriesLog=array();

/**
 * Maximum number of items in query log, to prevent query log taking over
 * too much memory on large amounts of queries -- I we've had problems at
 * >6000 queries on one system.
 *
 * @var int Maximum number of queries in the queries log.
 * @access private
 */
   var $_queriesLogMax=200;


/**
 * Constructor. Sets the level of debug for dbo (fullDebug or debug).
 *
 * @param array $config
 * @return unknown
 */
   function __construct($config=NULL)
   {
      $this->debug = DEBUG > 0;
      $this->fullDebug = DEBUG > 1;
      parent::__construct();
      return $this->connect($config);
   }

/**
 * Destructor. Closes connection to the database.
 *
 */
   function __destructor()
   {
      $this->close();
   }

/**
 * Returns a string with a USE [databasename] SQL statement.
 *
 * @param string $db_name Name of database to use
 * @return unknown Result of the query
 */
   function useDb($db_name)
   {
      return $this->query("USE {$db_name}");
   }

/**
 * Disconnects database, kills the connection and says the connection is closed, and if DEBUG is turned on, the log for this object is shown.
 *
 */
   function close ()
   {
      if ($this->fullDebug) $this->showLog();
      $this->disconnect();
      $this->_conn = NULL;
      $this->connected = false;
   }

/**
 * Prepares a value, or an array of values for database queries by quoting and escaping them.
 *
 * @param mixed $data A value or an array of values to prepare.
 * @return mixed Prepared value or array of values.
 */
   function prepare ($data)
   {
      if (is_array($data))
      {
         $out = null;
         foreach ($data as $key=>$item)
         {
            $out[$key] = $this->prepareValue($item);
         }
         return $out;
      }
      else
      {
         return $this->prepareValue($data);
      }
   }

   function sources ()
   {
      return array_map('strtolower', $this->listSources());
   }

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return unknown
 */
   function rawQuery ($sql)
   {
      $this->took = $this->error = $this->numRows = false;
      return $this->execute($sql);
   }

/**
 * Queries the database with given SQL statement, and obtains some metadata about the result
 * (rows affected, timing, any errors, number of rows in resultset). The query is also logged.
 * If DEBUG is set, the log is shown all the time, else it is only shown on errors.
 *
 * @param string $sql
 * @return unknown
 */
   function query($sql)
   {
      $t = getMicrotime();
      $this->_result = $this->execute($sql);
      $this->affected = $this->lastAffected();
      $this->took = round((getMicrotime()-$t)*1000, 0);
      $this->error = $this->lastError();
      $this->numRows = $this->lastNumRows($this->_result);
      $this->logQuery($sql);
      if (($this->debug && $this->error) || ($this->fullDebug))
      $this->showQuery($sql);

      return $this->error? false: $this->_result;
   }

/**
 * Returns a single row of results from the _last_ SQL query.
 *
 * @param resource $res
 * @return array A single row of results
 */
   function farr ($assoc=false)
   {
      if ($assoc === false)
      {
         return $this->fetchRow();
      }
      else
      {
         return $this->fetchRow($assoc);
      }
   }

/**
 * Returns a single row of results for a _given_ SQL query.
 *
 * @param string $sql SQL statement
 * @return array A single row of results
 */
   function one ($sql)
   {
      return $this->query($sql)? $this->fetchArray(): false;
   }

/**
 * Returns an array of all result rows for a given SQL query.
 * Returns false if no rows matched.
 *
 * @param string $sql SQL statement
 * @return array Array of resultset rows, or false if no rows matched
 */
   function all ($sql)
   {
      if($this->query($sql))
      {
         $out=array();
         while ($item = $this->fetchArray(null, true))
         {
            $out[] = $item;
         }
         return $out;
      }
      else
      {
         return false;
      }
   }

/**
 * Returns a single field of the first of query results for a given SQL query, or false if empty.
 *
 * @param string $name Name of the field
 * @param string $sql SQL query
 * @return unknown
 */
   function field ($name, $sql)
   {
      $data = $this->one($sql);
      return empty($data[$name])? false: $data[$name];
   }

/**
 * Checks if the specified table contains any record matching specified SQL
 *
 * @param string $table Name of table to look in
 * @param string $sql SQL WHERE clause (condition only, not the "WHERE" part)
 * @return boolean True if the table has a matching record, else false
 */
   function hasAny($table, $sql)
   {
      $out = $this->one("SELECT COUNT(*) AS count FROM {$table}".($sql? " WHERE {$sql}":""));
      return is_array($out)? $out[0]['count']: false;
   }

/**
 * Checks if it's connected to the database
 *
 * @return boolean True if the database is connected, else false
 */
   function isConnected()
   {
      return $this->connected;
   }

/**
 * Outputs the contents of the log.
 *
 * @param boolean $sorted
 */
   function showLog($sorted=false)
   {
      $log = $sorted?
      sortByKey($this->_queriesLog, 'took', 'desc', SORT_NUMERIC):
      $this->_queriesLog;

      print("<table border=1>\n<tr><th colspan=7>{$this->_queriesCnt} queries took {$this->_queriesTime} ms</th></tr>\n");
      print("<tr><td>Nr</td><td>Query</td><td>Error</td><td>Affected</td><td>Num. rows</td><td>Took (ms)</td></tr>\n");

      foreach($log AS $k=>$i)
      {
         print("<tr><td>".($k+1)."</td><td>{$i['query']}</td><td>{$i['error']}</td><td align='right'>{$i['affected']}</td><td align='right'>{$i['numRows']}</td><td align='right'>{$i['took']}</td></tr>\n");
      }

      print("</table>\n");
   }

/**
 * Log given SQL query.
 *
 * @param string $sql SQL statement
 */
   function logQuery($sql)
   {
      $this->_queriesCnt++;
      $this->_queriesTime += $this->took;

      $this->_queriesLog[] = array(
      'query'=>$sql,
      'error'=>$this->error,
      'affected'=>$this->affected,
      'numRows'=>$this->numRows,
      'took'=>$this->took
      );

      if (count($this->_queriesLog) > $this->_queriesLogMax)
      {
         array_pop($this->_queriesLog);
      }

      if ($this->error)
      return false; // shouldn't we be logging errors somehow?
   }

/**
 * Output information about an SQL query. The SQL statement, number of rows in resultset,
 * and execution time in microseconds. If the query fails, and error is output instead.
 *
 * @param string $sql
 */
   function showQuery($sql)
   {
      $error = $this->error;

      if (strlen($sql)>200 && !$this->fullDebug)
      {
         $sql = substr($sql, 0, 200) .'[...]';
      }

      if ($this->debug || $error)
      {
         print("<p style=\"text-align:left\"><b>Query:</b> {$sql} <small>[Aff:{$this->affected} Num:{$this->numRows} Took:{$this->took}ms]</small>");
         if($error)
         {
            print("<br /><span style=\"color:Red;text-align:left\"><b>ERROR:</b> {$this->error}</span>");
         }
         print('</p>');
      }
   }
}

?>