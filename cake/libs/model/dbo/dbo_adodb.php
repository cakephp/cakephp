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
 * Copyright (c) 2006, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs.model.dbo
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Include AdoDB files.
 */
vendor('adodb'.DS.'adodb.inc.php');
uses('model'.DS.'datasources'.DS.'dbo_source');

/**
 * AdoDB DBO implementation.
 *
 * Database abstraction implementation for the AdoDB library.
 *
 * @package    cake
 * @subpackage cake.cake.libs.model.dbo
 * @since      CakePHP v 0.2.9
 */
class DboAdodb extends DboSource
{
/**
 * Enter description here...
 *
 * @var unknown_type
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
        'B' => 'binary');

/**
 * Connects to the database using options in the given configuration array.
 *
 * @param array $config Configuration array for connecting
 */
    function connect ()
    {
      $config = $this->config;
      $persistent = strrpos($config['connect'], '|p');
      if($persistent === FALSE){
         $adodb_driver = $config['connect'];
         $connect = 'Connect';
      }
      else{
         $adodb_driver = substr($config['connect'], 0, $persistent);
         $connect = 'PConnect';
      }

      $this->_adodb = NewADOConnection($adodb_driver);
      $adodb =& $this->_adodb;

      $this->connected = $adodb->$connect($config['host'], $config['login'], $config['password'], $config['database']);

      if(!$this->connected)
      {
//die('Could not connect to DB.');
      }
    }

/**
 * Disconnects from database.
 *
 * @return boolean True if the database could be disconnected, else false
 */
    function disconnect ()
    {
      return $this->_adodb->Close();
    }

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 */
    function _execute ($sql)
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
         if($this->_result->EOF)
         {
             return null;
         }
         return $this->_result->FetchRow();
     }

/**
 * Begin a transaction
 *
 * @param unknown_type $model
 * @return boolean True on success, false on fail
 * (i.e. if the database/model does not support transactions).
 */
    function begin (&$model)
    {
        if (parent::begin($model))
        {
            if ($this->_adodb->BeginTrans())
            {
                $this->__transactionStarted = true;
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
    function commit (&$model)
    {
        if (parent::commit($model))
        {
            $this->__transactionStarted;
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
    function rollback (&$model)
    {
        if (parent::rollback($model))
        {
            return $this->_adodb->RollbackTrans();
        }
        return false;
    }

/**
 * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
 *
 * @return array Array of tablenames in the database
 */
    function tablesList ()
    {
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
 *
 * @param AppModel $model Model object
 * @return array Fields in table. Keys are name and type
 */
    function describe (&$model)
    {
        $cache = parent::describe($model);
        if ($cache != null)
        {
            return $cache;
        }
        $fields = false;
        $cols = $this->_adodb->MetaColumns($model->table);
        foreach ($cols as $column)
        {
            $fields[] = array('name'=>$column->name, 'type'=>$column->type);
        }
        $this->__cacheDescription($model->table, $fields);
        return $fields;
    }

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @return string Quoted and escaped
 *
 * @todo To be implemented.
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
 * Returns number of affected rows in previous database operation, or false if no previous operation exists.
 *
 * @return int Number of affected rows
 */
    function lastAffected ()
    {
      return $this->_adodb->Affected_Rows();
    }

/**
 * Returns number of rows in previous resultset, or false if no previous resultset exists.
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
 * @Returns the last autonumbering ID inserted. Returns false if function not supported.
 */
    function lastInsertId ()
    {
        return $this->_adodb->Insert_ID();
    }

/**
 * Returns a LIMIT statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 * @todo Please change output string to whatever select your database accepts. adodb doesn't allow us to get the correct limit string out of it.
 */
    function selectLimit ($limit, $offset=null)
    {
      return " LIMIT {$limit}".($offset? "{$offset}": null);
// please change to whatever select your database accepts
// adodb doesn't allow us to get the correct limit string out of it
    }

/**
 * Converts database-layer column types to basic types
 *
 * @param string $real Real database-layer column type (i.e. "varchar(255)")
 * @return string Abstract column type (i.e. "string")
 */
    function column($real)
    {
        if ( isset( $this->_result ))
        {
            $adodb_metatyper = & $this->_result;
        }
        else
        {
            $adodb_metatyper = & $this->_adodb->execute('Select 1');
        }
        $interpreted_type = $adodb_metatyper->MetaType($real);

        if (!isset($this->_adodb_column_types[$interpreted_type]))
        {
            return 'text';
        }
        return $this->_adodb_column_types[ $interpreted_type ] ;
    }

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column_type The type of the column into which this data will be inserted
 * @param boolean $safe Whether or not numeric data should be handled automagically if no column data is provided
 * @return string Quoted and escaped data
 */
    function value ($data, $column = null, $safe = false)
    {
        $parent = parent::value($data, $column, $safe);

        if ($parent != null)
        {
            return $parent;
        }

        if ($data === null)
        {
            return 'NULL';
        }

        if($data == '')
        {
            return  "''";
        }

        if (ini_get('magic_quotes_gpc') == 1)
        {
            $data = stripslashes($data);
        }

        return $this->_adodb->qstr( $data );
    }

/**
 * Returns an array of all result rows for a given SQL query.
 * Returns false if no rows matched.
 *
 * @param string $sql SQL statement
 * @param boolean $cache Enables returning/storing cached query results
 * @param string $modelName Name of model for first array dimension of results
 * @return array Array of resultset rows, or false if no rows matched
 */
    function fetchAll ($sql, $cache = true, $modelName = null)
    {
        $result = parent::fetchAll( $sql, $cache );
        if (!$result)
        {
            return false;
        }
        foreach($result as $key => $value)
        {
            $return[$key][$modelName] = $value;
        }
        return $return;
    }
}
?>