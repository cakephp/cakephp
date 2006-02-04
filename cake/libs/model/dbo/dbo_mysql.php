<?php
/* SVN FILE: $Id$ */

/**
 * MySQL layer for DBO
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
 * @since        CakePHP v 0.10.5.1790
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
 * Short description for class.
 *
 * Long description for class
 *
 * @package    cake
 * @subpackage cake.cake.libs.model.dbo
 * @since      CakePHP v 0.10.5.1790
 */
class DboMysql extends DboSource
{
/**
 * Enter description here...
 *
 * @var unknown_type
 */
    var $description = "MySQL DBO Driver";

/**
 * Enter description here...
 *
 * @var unknown_type
 */
    var $_baseConfig = array('persistent' => true,
                             'host'       => 'localhost',
                             'login'      => 'root',
                             'password'   => '',
                             'database'   => 'cake',
                             'port'       => 3306);

/**
 * Enter description here...
 *
 * @var unknown_type
 */
    var $columns = array('primary_key' => array('name' => 'int(11) DEFAULT NULL auto_increment'),
                         'string'      => array('name' => 'varchar', 'limit' => '255'),
                         'text'        => array('name' => 'text'),
                         'integer'     => array('name' => 'int', 'limit' => '11'),
                         'float'       => array('name' => 'float'),
                         'datetime'    => array('name' => 'datetime', 'format' => 'Y-m-d h:i:s'),
                         'timestamp'   => array('name' => 'datetime', 'format' => 'Y-m-d h:i:s'),
                         'time'        => array('name' => 'time', 'format' => 'h:i:s'),
                         'date'        => array('name' => 'date', 'format' => 'Y-m-d'),
                         'binary'      => array('name' => 'blob'),
                         'boolean'     => array('name' => 'tinyint', 'limit' => '1'));

/**
 * Enter description here...
 *
 * @param unknown_type $config
 * @return unknown
 */
    function __construct ($config)
    {
        parent::__construct($config);
        return $this->connect();
    }

/**
 * Connects to the database using options in the given configuration array.
 *
 * @return boolean True if the database could be connected, else false
 */
    function connect ()
    {
        $config = $this->config;
        $connect = $config['connect'];

        $this->connected = false;
        $this->connection = $connect($config['host'], $config['login'], $config['password']);
        if ($this->connection)
        {
            $this->connected = true;
        }
        if ($this->connected)
        {
            return mysql_select_db($config['database'], $this->connection);
        }
        else
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
        return @mysql_close($this->connection);
    }

/**
 * Executes given SQL statement.
 *
 * @param string $sql SQL statement
 * @return resource Result resource identifier
 * @access protected
 */
    function _execute ($sql)
    {
        return mysql_query($sql, $this->connection);
    }

/**
 * MySQL query abstraction
 *
 * @return resource Result resource identifier
 */
    function query ()
    {
        $args = func_get_args();
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
 * @param bool $assoc Associative array only, or both?
 * @return array The fetched row as an array
 */
    function fetchRow ($assoc = false)
    {
        if(is_resource($this->_result))
        {
            $this->resultSet($this->_result);
            $resultRow = $this->fetchResult();
            return $resultRow;
        }
        else
        {
            return null;
        }
    }

/**
 * Returns an array of sources (tables) in the database.
 *
 * @return array Array of tablenames in the database
 */
    function listSources ()
    {
        $result = mysql_list_tables($this->config['database'], $this->connection);
        if (!$result)
        {
            return null;
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
 * @param string $tableName Name of database table to inspect
 * @return array Fields in table. Keys are name and type
 */
    function &describe (&$model)
    {
        $cache = parent::describe($model);
        if ($cache != null)
        {
            return $cache;
        }

        $fields = false;
        $cols = $this->query('DESC ' . $this->name($model->table));

        foreach ($cols as $column)
        {
            $colKey = array_keys($column);
            if (isset($column[$colKey[0]]) && !isset($column[0]))
            {
                $column[0] = $column[$colKey[0]];
            }
            if (isset($column[0]))
            {
                $fields[] = array('name' => $column[0]['Field'], 'type' => $column[0]['Type']);
            }
        }
        $this->__cacheDescription($model->table, $fields);
        return $fields;
    }

/**
 * Returns a quoted name of $data for use in an SQL statement.
 *
 * @param string $data Name (table.field) to be prepared for use in an SQL statement
 * @return string Quoted for MySQL
 */
    function name ($data)
    {
        if ($data == '*')
        {
            return '*';
        }
        return '`'. ereg_replace('\.', '`.`', $data) .'`';
    }

/**
 * Returns a quoted and escaped string of $data for use in an SQL statement.
 *
 * @param string $data String to be prepared for use in an SQL statement
 * @param string $column The column into which this data will be inserted
 * @return string Quoted and escaped
 * @todo Add logic that formats/escapes data based on column type
 */
    function value ($data, $column = null)
    {
        $parent = parent::value($data, $column);
        if ($parent != null)
        {
            return $parent;
        }
        if ($data === null)
        {
            return 'NULL';
        }
        if (ini_get('magic_quotes_gpc') == 1)
        {
            $data = stripslashes($data);
        }
        if (version_compare(phpversion(),"4.3.0") == "-1")
        {
            $data = mysql_escape_string($data, $this->connection);
        }
        else
        {
            $data = mysql_real_escape_string($data, $this->connection);
        }
        $return = "'" . $data . "'";
        return $return;
   }

/**
 * Translates between PHP boolean values and MySQL (faked) boolean values
 *
 * @param mixed $data Value to be translated
 * @return mixed Converted boolean value
 */
    function boolean ($data)
    {
        if ($data === true || $data === false)
        {
            if ($data === true)
            {
                return 1;
            }
            return 0;
        }
        else
        {
            if (intval($data !== 0))
            {
                return true;
            }
            return false;
        }
    }

/**
 * Enter description here...
 *
 * @param unknown_type $model
 * @param unknown_type $fields
 * @param unknown_type $values
 * @return unknown
 */
    function create(&$model, $fields = null, $values = null)
    {
        return parent::create($model, $fields, $values);
    }

/**
 * Enter description here...
 *
 * @param unknown_type $model
 * @param unknown_type $fields
 * @param unknown_type $values
 * @return unknown
 */
    function update(&$model, $fields = null, $values = null)
    {
        return parent::update($model, $fields, $values);
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
            if ($this->execute('START TRANSACTION'))
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
            return $this->execute('COMMIT');
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
            return $this->execute('ROLLBACK');
        }
        return false;
    }

/**
 * Returns a formatted error message from previous database operation.
 *
 * @return string Error message with error number
 */
    function lastError ()
    {
        if (mysql_errno($this->connection))
        {
            return mysql_errno($this->connection).': '.mysql_error($this->connection);
        }
        return null;
    }

/**
 * Returns number of affected rows in previous database operation. If no previous operation exists,
 * this returns false.
 *
 * @return int Number of affected rows
 */
    function lastAffected ()
    {
        if ($this->_result)
        {
            return mysql_affected_rows($this->connection);
        }
        return null;
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
            return @mysql_num_rows($this->_result);
        }
        return null;
    }

/**
 * Returns the ID generated from the previous INSERT operation.
 *
 * @param unknown_type $source
 * @return in
 */
    function lastInsertId ($source = null)
    {
        return mysql_insert_id($this->connection);
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
        if ($limit)
        {
            $rt = '';
            if (!strpos(low($limit), 'limit') || strpos(low($limit), 'limit') === 0)
            {
                $rt = ' LIMIT';
            }
            if ($offset)
            {
                $rt .= ' ' . $offset. ',';
            }
            $rt .= ' ' . $limit;
            return $rt;
        }
        return null;
    }

/**
 * Enter description here...
 *
 * @param unknown_type $results
 */
    function resultSet(&$results)
    {
        $this->results =& $results;
        $this->map = array();
        $num_fields = mysql_num_fields($results);
        $index = 0;
        $j = 0;

        while ($j < $num_fields)
        {
            $column = mysql_fetch_field($results,$j);
            if (!empty($column->table))
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
 * Fetches the next row from the current result set
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
                $resultRow[$table][$column] = $row[$index];
                $i++;
            }
            return $resultRow;
        }
        else
        {
            return false;
        }
    }

    function buildSchemaQuery($schema)
    {
        $search  = array('{AUTOINCREMENT}', '{PRIMARY}', '{UNSIGNED}', '{FULLTEXT}',
                         '{FULLTEXT_MYSQL}', '{BOOLEAN}', '{UTF_8}');
        $replace = array('int(11) not null auto_increment', 'primary key', 'unsigned',
                         'FULLTEXT', 'FULLTEXT', 'enum (\'true\', \'false\') NOT NULL default \'true\'',
                         '/*!40100 CHARACTER SET utf8 COLLATE utf8_unicode_ci */');
        $query = trim(str_replace($search, $replace, $schema));
        return $query;
     }
}
?>