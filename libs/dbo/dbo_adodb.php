<?php
/* SVN FILE: $Id$ */

/**
 * AdoDB layer for DBO.
 * 
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2005, CakePHP Authors/Developers
 *
 * Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com>
 *            Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The MIT License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Authors/Developers
 * @link         https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package      cake
 * @subpackage   cake.libs.dbo
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Include AdoDB files.
 */
require_once(VENDORS.'adodb/adodb.inc.php');

/**
 * Short description for class.
 * 
 * Long description for class
 *
 * @package    cake
 * @subpackage cake.libs.dbo
 * @since      CakePHP v 0.2.9
 */
class DBO_AdoDB extends DBO 
{
   
/**
 * ADOConnection object with which we connect.
 *
 * @var ADOConnection The connection object.
 * @access private
 */
   var $_adodb = null;

/**
 * Connects to the database using options in the given configuration array.
 *
 * @param array $config Configuration array for connecting
 */
   function connect ($config) 
   {
      if ($this->config = $config)
      {
         if (isset($config['driver']))
         {
            $this->_adodb = NewADOConnection($config['driver']);

            $adodb =& $this->_adodb;
            $this->connected = $adodb->Connect($config['host'], $config['login'], $config['password'], $config['database']);
         }
      }

      if(!$this->connected){
        // die('Could not connect to DB.');
      }
   }

/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
   function disconnect () 
   {
      return $this->_adodb->close();
   }

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 */
   function execute ($sql) 
   {
      return $this->_adodb->execute($sql);
   }

/**
 * Returns a row from given resultset as an array .
 *
 * @return array The fetched row as an array
 */
   function fetchRow () 
   {
      return $this->_result->FetchRow();
   }

/**
 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
 *
 * @return array Array of tablenames in the database
 */
   function tablesList () 
   {
      $tables = $this->_adodb->MetaTables('TABLES');

      if (!sizeof($tables)>0) {
         trigger_error(ERROR_NO_TABLE_LIST, E_USER_NOTICE);
         exit;
      }
      return $tables;
   }

/**
 * Returns an array of the fields in given table name.
 *
 * @param string $table_name Name of database table to inspect
 * @return array Fields in table. Keys are name and type
 */
   function fields ($table_name)
   {
      $data = $this->_adodb->MetaColumns($table_name);
      $fields = false;

      foreach ($data as $item)
         $fields[] = array('name'=>$item->name, 'type'=>$item->type);

      return $fields;
   }

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @return string Quoted and escaped
 *
 * :TODO: To be implemented.
 */
   function prepareValue ($data)      
   {
      return $this->_adodb->Quote($data);
   }

/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message
 */
   function lastError () 
   {
      return $this->_adodb->ErrorMsg();
   }

/**
 * Returns number of affected rows in previous database operation. If no previous operation exists, this returns false.
 *
 * @return int Number of affected rows
 */
   function lastAffected ()
   {
      return $this->_adodb->Affected_Rows(); 
   }

/**
 * Returns number of rows in previous resultset. If no previous resultset exists, 
 * this returns false.
 *
 * @return int Number of rows in resultset
 */
   function lastNumRows () 
   {
       return $this->_result? $this->_result->RecordCount(): false;
   }

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @return int 
 *
 * :TODO: To be implemented.
 */
   function lastInsertId ()      { die('Please implement DBO::lastInsertId() first.'); }

/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
   function selectLimit ($limit, $offset=null)
   {
      return " LIMIT {$limit}".($offset? "{$offset}": null);
      // please change to whatever select your database accepts
      // adodb doesn't allow us to get the correct limit string out of it
   }

}

?>