<?php
/* SVN FILE: $Id$ */

/**
 * PostgreSQL layer for DBO.
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
 * @subpackage   cake.cake.libs.model.datasources.dbo
 * @since        CakePHP v 0.9.1.114
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Include DBO.
  */
uses('model'.DS.'datasources'.DS.'dbo_source');

/**
 * PostgreSQL layer for DBO.
 *
 * Long description for class
 *
 * @package    cake
 * @subpackage cake.cake.libs.model.datasources.dbo
 * @since      CakePHP v 0.9.1.114
 */
class  DboPostgres extends DboSource
{


   var $description = "PostgreSQL DBO Driver";

   var $_baseConfig = array('persistent' => true,
                            'host'       => 'localhost',
                            'login'      => 'root',
                            'password'   => '',
                            'database'   => 'cake',
                            'port'       => 3306
                          );

   var $columns = array(
        'primary_key' => array('name' => 'serial primary key'),
        'string'      => array('name' => 'varchar', 'limit' => '255'),
        'text'        => array('name' => 'text'),
        'integer'     => array('name' => 'integer'),
        'float'       => array('name' => 'float'),
        'datetime'    => array('name' => 'timestamp'),
        'timestamp'   => array('name' => 'timestamp'),
        'time'        => array('name' => 'time'),
        'date'        => array('name' => 'date'),
        'binary'      => array('name' => 'bytea'),
        'boolean'     => array('name' => 'boolean')
   );



/**
  * Connects to the database using options in the given configuration array.
  *
  * @return True if successfully connected.
  */
   function connect ()
   {
      $config = $this->config;
      $connect = $config['connect'];

      $this->connection = $connect("dbname={$config['database']} user={$config['login']} password={$config['password']}");
      if ($this->connection)
      {
          $this->connected = true;
      }
      else
      {
          $this->connected = false;
      }

      return $this->connected;
   }

/**
  * Disconnects from database.
  *
  * @return boolean True if the database could be disconnected, else false
  */
   function disconnect ()
   {
      return pg_close($this->connection);
   }

/**
  * Executes given SQL statement.
  *
  * @param string $sql SQL statement
  * @return resource Result resource identifier
  */
   function __execute ($sql)
   {
      return pg_query($this->connection, $sql);
   }

   function query ()
   {
      $args = func_get_args();
      echo "<pre>";
      print_r($args);
      echo "</pre>";
      die();
      if (count($args) == 1)
      {
         return $this->fetchAll($args[0]);
      }
      elseif (count($args) > 1 && strpos($args[0], 'findBy') === 0)
      {
         $field = Inflector::underscore(str_replace('findBy', '', $args[0]));
         $query = '`' . $args[2]->name . '`.`' . $field . '` = ' . $this->value($args[1][0]);
         return $args[2]->find($query);
      }
      elseif (count($args) > 1 && strpos($args[0], 'findAllBy') === 0)
      {
         $field = Inflector::underscore(str_replace('findAllBy', '', $args[0]));
         $query = '`' . $args[2]->name . '`.`' . $field . '` = ' . $this->value($args[1][0]);
         return $args[2]->findAll($query);
      }
   }

/**
  * Returns a row from given resultset as an array .
  *
  * @return array The fetched row as an array
  */
   function fetchRow ()
   {
       return pg_fetch_array($this->_result);
   }

/**
  * Returns an array of tables in the database. If there are no tables, an error is raised and the application exits.
  *
  * @return array Array of tablenames in the database
  */
   function listSources ()
   {
      $sql = "SELECT a.relname AS name
         FROM pg_class a, pg_user b
         WHERE ( relkind = 'r') and relname !~ '^pg_' AND relname !~ '^sql_'
         AND relname !~ '^xin[vx][0-9]+' AND b.usesysid = a.relowner
         AND NOT (EXISTS (SELECT viewname FROM pg_views WHERE viewname=a.relname));";

      $this->execute($sql);
      $result = $this->fetchRow();

      if (!$result)
      {
         return null;
      }
      else
      {
         $tables = array();
         foreach ($result as $item)
         {
             $tables[] = $item['name'];
         }
         return $tables;
      }
   }

/**
  * Returns an array of the fields in given table name.
  *
  * @param string $tableName Name of database table to inspect
  * @return array Fields in table. Keys are name and type
  */
   function fields ($tableName)
   {
      $sql = "SELECT c.relname, a.attname, t.typname FROM pg_class c, pg_attribute a, pg_type t WHERE c.relname = '{$tableName}' AND a.attnum > 0 AND a.attrelid = c.oid AND a.atttypid = t.oid";

      $fields = false;
      foreach ($this->all($sql) as $field) {
         $fields[] = array(
            'name' => $field['attname'],
            'type' => $field['typname']);
      }

      return $fields;
   }

/**
  * Returns a quoted and escaped string of $data for use in an SQL statement.
  *
  * @param string $data String to be prepared for use in an SQL statement
  * @return string Quoted and escaped
  */
   function name ($data)
   {
      return "'". $data."'";
   }

/**
  * Returns a quoted and escaped string of $data for use in an SQL statement.
  *
  * @param string $data String to be prepared for use in an SQL statement
  * @return string Quoted and escaped
  */
   function value ($data)
   {
      return "'".pg_escape_string($data)."'";
   }

/**
  * Returns a formatted error message from previous database operation.
  *
  * @return string Error message
  */
   function lastError ()
   {
      $last_error = pg_last_error($this->connection);
      if ($last_error)
      {
          return $last_error;
      }
      return null;
   }

/**
  * Returns number of affected rows in previous database operation. If no previous operation exists, this returns false.
  *
  * @return int Number of affected rows
  */
   function lastAffected ()
   {
      if ($this->_result)
      {
          return pg_affected_rows($this->_result);
      }
      return false;
   }

/**
  * Returns number of rows in previous resultset. If no previous resultset exists,
  * this returns false.
  *
  * @return int Number of rows in resultset
  */
   function lastNumRows ()
   {
      if ($this->_result)
      {
          return pg_num_rows($this->_result);
      }
      return false;
   }

/**
  * Returns the ID generated from the previous INSERT operation.
  *
  * @param string $source Name of the database table
  * @param string $field Name of the ID database field. Defaults to "id"
  * @return int
  */
   function lastInsertId ($source, $field='id')
   {
      $sql = "SELECT CURRVAL('{$source}_{$field}_seq') AS max";
      $res = $this->rawQuery($sql);
      $data = $this->fetchRow($res);
      return $data['max'];
   }

/**
 * Returns a limit statement in the correct format for the particular database.
 *
 * @param int $limit Limit of results returned
 * @param int $offset Offset from which to start results
 * @return string SQL limit/offset statement
 */
   function limit ($limit, $offset = null)
   {
      $rt = ' LIMIT ' . $limit;
      if ($offset)
      {
          $rt .= ' OFFSET ' . $offset;
      }
      return $rt;
   }

}

?>