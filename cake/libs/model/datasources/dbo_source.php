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
 * @subpackage   cake.cake.libs.model.datasources
 * @since        CakePHP v 0.10.0.1076
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Include DataSource base class
 *
 */
uses('model'.DS.'datasources'.DS.'datasource');

/**
 * DboSource
 *
 * Creates DBO-descendant objects from a given db connection configuration
 *
 * @package    cake
 * @subpackage cake.cake.libs.model.datasources
 * @since      CakePHP v 0.10.0.1076
 *
 */
class DboSource extends DataSource
{

   var $description = "Database Data Source";

/**
 * Constructor
 *
 */
   function __construct($config = null)
   {
      $this->debug = DEBUG > 0;
      $this->fullDebug = DEBUG > 1;
      parent::__construct($config);
      return $this->connect();
   }

/**
 * Prepares a value, or an array of values for database queries by quoting and escaping them.
 *
 * @param mixed $data A value or an array of values to prepare.
 * @return mixed Prepared value or array of values.
 */
   function value ($data, $column = null)
   {
      if (is_array($data))
      {
		  $out = array();
		  foreach ($data as $key => $item)
		  {
			 $out[$key] = $this->value($item);
		  }
		  return $out;
      }
      else
      {
          return null;
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
   function execute($sql)
   {
      $t = getMicrotime();
      $this->_result = $this->__execute($sql);

      $this->affected = $this->lastAffected();
      $this->took = round((getMicrotime() - $t) * 1000, 0);
      $this->error = $this->lastError();
      $this->numRows = $this->lastNumRows($this->_result);
      $this->logQuery($sql);

      if (($this->debug && $this->error) || ($this->fullDebug))
      {
          $this->showQuery($sql);
      }

      if ($this->error)
      {
          return false;
      }
      else
      {
          return $this->_result;
      }
   }

/**
 * Returns a single row of results from the _last_ SQL query.
 *
 * @param resource $res
 * @return array A single row of results
 */
   function fetchArray ($assoc=false)
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
      if ($this->query($sql))
      {
          return $this->fetchArray();
      }
      return false;
   }

/**
 * Returns an array of all result rows for a given SQL query. 
 * Returns false if no rows matched.
 *
 * @param string $sql SQL statement
 * @return array Array of resultset rows, or false if no rows matched
 */
   function fetchAll ($sql)
   {
      if($this->execute($sql))
      {
         $out = array();
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
        if (empty($data[$name]))
        {
            return false;
        }
         else
        {
            return $data[$name];
        }
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
          'query'		=> $sql,
          'error'		=> $this->error,
          'affected'	=> $this->affected,
          'numRows'		=> $this->numRows,
          'took'		=> $this->took
      );

      if (count($this->_queriesLog) > $this->_queriesLogMax)
      {
         array_pop($this->_queriesLog);
      }

      if ($this->error)
      {
          return false; // shouldn't we be logging errors somehow?
          // TODO: Add hook to error log
      }
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

    function create(&$model, $fields = null, $values = null)
    {
        if ($fields == null)
        {
            unset($fields, $values);
            $fields = array_keys($model->data);
            $values = array_values($model->data);
        }

        if($this->execute('INSERT INTO '.$model->source.' ('.join(',', $fields).') VALUES ('.join(',', $values).')'))
        {
            return true;
        }
        return false;
    }

    function read (&$model, $queryData = array(), $recursive = 1)
    {
        $this->__scrubQueryData($queryData);
        $null = null;
        $array = array();
        $linkedModels = array();

        if ($recursive > 0)
        {
            foreach($model->__associations as $type)
            {
                foreach($model->{$type} as $assoc => $assocData)
                {
                    $linkModel =& $model->{$assocData['className']};
                    if (true === $this->generateAssociationQuery($model, $linkModel, $type, $assoc, $assocData, $queryData, false, $null))
                    {
                        $linkedModels[] = $type.$assoc;
                    }
                }
            }
        }

        // Build final query SQL
        $query = $this->generateAssociationQuery($model, $null, null, null, null, $queryData, false, $null);
        $resultSet = $this->fetchAll($query);

        if ($recursive > 0)
        {
            foreach($model->__associations as $type)
            {
                foreach($model->{$type} as $assoc => $assocData)
                {
                    if (!in_array($type.$assoc, $linkedModels))
                    {
                        $linkModel =& $model->{$assocData['className']};
                        $this->queryAssociation($model, $linkModel, $type, $assoc, $assocData, $array, true, $resultSet, $recursive - 1);
                    }
                }
            }
        }
        return $resultSet;
    }

    function queryAssociation(&$model, &$linkModel, $type, $association, $assocData, &$queryData, $external = false, &$resultSet, $recursive = 1)
    {
        //$external = (($linkModel->ds === $this) && $resultSet == null);

        $query = $this->generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet);
        if ($query)
        {
            foreach ($resultSet as $i => $row)
            {
                $q = $this->insertQueryData($query, $resultSet, $association, $assocData, $model, $linkModel, $i);
                $fetch = $this->fetchAll($q);

                if (!empty($fetch) && is_array($fetch))
                {
                    if (isset($fetch[0][$association]))
                    {
                        foreach ($fetch as $j => $row)
                        {
                            $resultSet[$i][$association][$j] = $row[$association];
                        }
                    }
                    else
                    {
                        $plural = Inflector::pluralize($association);
                        foreach ($fetch as $j => $row)
                        {
                            $resultSet[$i][$plural][$j] = $row[$plural];
                        }
                    }
                }
            }
        }
    }

    function generateAssociationQuery(&$model, &$linkModel, $type, $association = null, $assocData = array(), &$queryData, $external = false, &$resultSet)
    {
        $this->__scrubQueryData($queryData);
        if ($linkModel == null)
        {
            // Generates primary query
            $sql  = 'SELECT ' . join(', ', $this->fields($queryData['fields'])) . ' FROM ';
            $sql .= $this->name($model->source).' AS ';
            $sql .= $this->name($model->name).' ' . join(' ', $queryData['joins']).' ';
            $sql .= $this->conditions($queryData['conditions']).' '.$this->order($queryData['order']);
            $sql .= ' '.$this->limit($queryData['limit']);
            return $sql;
        }

        $alias = $association;
        if($model->name == $linkModel->name)
        {
            $alias = Inflector::pluralize($association);
        }

        switch ($type)
        {
            case 'hasOne':
                if ($external)
                {
                    if ($assocData['finderQuery'])
                    {
                        return $assocData['finderQuery'];
                    }
                    $sql  = 'SELECT * FROM '.$this->name($linkModel->source).' AS '.$alias;
                    $conditions = $queryData['conditions'];
                    $condition = $model->escapeField($assocData['foreignKey']);
                    $condition .= '={$__cake_foreignKey__$}';
                    if (is_array($conditions))
                    {
                        $conditions[] = $condition;
                    }
                    else
                    {
                        if (trim($conditions) != '')
                        {
                            $conditions .= ' AND ';
                        }
                        $conditions .= $condition;
                    }
                    $sql .= $this->conditions($queryData['conditions']) . $this->order($queryData['order']);
                    $sql .= $this->limit($queryData['limit']);
                }
                else
                {
                    $sql  = ' LEFT JOIN '.$this->name($linkModel->source);
                    $sql .= ' AS '.$this->name($alias).' ON '.$this->name($alias).'.';
                    $sql .= $this->name($assocData['foreignKey']).'='.$model->escapeField($model->primaryKey);
                    $sql .= $this->conditions($assocData['conditions']);
                    $sql .= $this->order($assocData['order']);

                    if (!in_array($queryData['joins'], $sql))
                    {
                        $queryData['joins'][] = $sql;
                    }
                    return true;
                }
            break;
            case 'belongsTo':
                if ($external)
                {
                    pr('external');
                    $conditions = $assocData['conditions'];
                    $sql  = 'SELECT * FROM '.$this->name($linkModel->source).' AS '.$this->name($alias);

                    $condition = $linkModel->escapeField($assocData['foreignKey']);
                    $condition .= '={$__cake_id__$}';
                    if (is_array($conditions))
                    {
                        $conditions[] = $condition;
                    }
                    else
                    {
                        if (trim($conditions) != '')
                        {
                            $conditions .= ' AND ';
                        }
                        $conditions .= $condition;
                    }
                    $sql .= $this->conditions($queryData['conditions']) . $this->order($queryData['order']);
                    $sql .= $this->limit($queryData['limit']);
                    return $sql;
                }
                else
                {
                    $sql  = ' LEFT JOIN '.$this->name($linkModel->source);
                    $sql .= ' AS ' . $this->name($alias) . ' ON ';
                    $sql .= $this->name($model->name).'.'.$this->name($assocData['foreignKey']);
                    $sql .= '='.$linkModel->escapeField($linkModel->primaryKey);

                    if (!in_array($sql, $queryData['joins']))
                    {
                        $queryData['joins'][] = $sql;
                    }
                    return true;
                }
            break;
            case 'hasMany':
                if(isset($assocData['finderQuery']) && $assocData['finderQuery'] != null)
                {
                    $sql = $assocData['finderQuery'];
                }
                else
                {
                    $conditions = $assocData['conditions'];
                    $sql  = 'SELECT * FROM '.$this->name($linkModel->source).' AS ';
                    $sql .= $this->name($alias);

                    $cond  = $this->name($alias).'.'.$this->name($assocData['foreignKey']);
                    $cond .= '={$__cake_id__$}';
                    if (is_array($conditions))
                    {
                        $conditions[] = $cond;
                    }
                    else
                    {
                        if (trim($conditions) != '')
                        {
                            $conditions .= ' AND ';
                        }
                        $conditions .= $cond;
                    }

                    $sql .= $this->conditions($conditions);
                    $sql .= $this->order($assocData['order']);
                }
                return $sql;
            break;
            case 'hasAndBelongsToMany':
                if(isset($assocData['finderQuery']) && $assocData['finderQuery'] != null)
                {
                    $sql = $assocData['finderQuery'];
                }
                else
                {
                    $joinTbl = $this->name($assocData['joinTable']);
                    $alias = $this->name($alias);

                    $sql = 'SELECT '.join(', ', $this->fields($assocData['fields']));
                    $sql .= ' FROM '.$this->name($linkModel->source).' AS '.$alias;
                    $sql .= ' JOIN '.$joinTbl.' ON '.$joinTbl;
                    $sql .= '.'.$this->name($assocData['foreignKey']).'={$__cake_id__$}';
                    $sql .= ' AND '.$joinTbl.'.'.$this->name($assocData['associationForeignKey']);
                    $sql .= ' = '.$alias.'.'.$this->name($linkModel->primaryKey);

                    $sql .= $this->conditions($assocData['conditions']);
                    $sql .= $this->order($assocData['order']);
                }
                return $sql;
            break;
        }
        return null;
    }

    function update (&$model, $fields = null, $values = null)
    {
        $updates = array();
        foreach (array_combine($fields, $values) as $field => $value)
        {
            $updates[] = $this->name($field).'='.$this->value($value);
        }

        $sql  = 'UPDATE '.$this->name($model->source).' AS '.$this->name($model->name);
        $sql .= ' SET '.join(',', $updates);
        $sql .= ' WHERE '.$model->escapeField($model->primaryKey).'='.$this->value($model->getID());

        return $this->execute($sql);
    }

    function delete (&$model, $id = null)
    {
        $_id = $model->id;
        if ($id != null)
        {
            $model->id = $id;
        }
        if (!is_array($model->id))
        {
            $model->id = array($model->id);
        }
        foreach ($model->id as $id)
        {
            $result = $this->execute('DELETE FROM '.$this->name($model->source).' WHERE '.$this->name($model->primaryKey).'='.$this->value($id));
        }
        if ($result)
        {
            return true;
        }
        return false;
    }

    function resolveKey($model, $key, $assoc = null)
    {
        if ($assoc == null)
        {
            $assoc = $model->name;
        }

        if (!strpos('.', $key))
        {
            return $this->name($model->source).'.'.$this->name($key);
        }
        return $key;
    }

    function getColumnType (&$model, $field)
    {
        $columns = $model->loadInfo();
    }

    function __scrubQueryData(&$data)
    {
        if (!isset($data['conditions']))
        {
            $data['conditions'] = ' 1 ';
        }
        if (!isset($data['fields']))
        {
            $data['fields'] = '*';
        }
        if (!isset($data['joins']))
        {
            $data['joins'] = array();
        }
        if (!isset($data['order']))
        {
            $data['order'] = '';
        }
        if (!isset($data['limit']))
        {
            $data['limit'] = '';
        }
    }

    function fields ($fields)
    {
        if (is_array($fields))
        {
            $f = $fields;
        }
        else
        {
            if ($fields != null)
            {
                if (strpos($fields, ','))
                {
                    $fields = explode(',', $fields);
                }
                else
                {
                    $fields = array($fields);
                }
                $fields = array_map('trim', $fields);
            }
            else
            {
                $fields = array('*');
            }
        }

        if (count($fields) > 1 && $fields[0] != '*')
        {
            for ($i = 0; $i < count($fields); $i++)
            {
                $fields[$i] = $this->name($fields[$i]);
            }
        }
        return $fields;
    }

/**
 * Parses conditions array (or just passes it if it's a string)
 * @return string
 *
 */
    function conditions ($conditions)
    {
        $rt = '';
        if (!strpos(low($conditions), 'where') || strpos(low($conditions), 'where') === 0)
        {
            $rt = ' WHERE ';
        }

        if (is_string($conditions))
        {
            if (trim($conditions) == '')
            {
                $conditions = ' 1';
            }
            return $rt.$conditions;
        }
        elseif (is_array($conditions))
        {
            $out = array();
            foreach ($conditions as $key => $value)
            {
                $slashedValue = $this->value($value);
                //TODO: Remove the = below so LIKE and other compares can be used
                $data = $key . '=';
                if ($value === null)
                {
                    $data .= 'null';
                }
                else
                {
                    $data = $slashedValue;
                }
                $out[] = $data;
            }
            return ' WHERE ' . join(' AND ', $out);
        }
        else
        {
            return $rt.' 1 ';
        }
    }

    function limit ()
    {
    }

    function order ($key, $dir = '')
    {
        if (trim($key) == '')
        {
            return '';
        }
        return ' ORDER BY '.$key.' '.$dir;
    }

/**
 * Disconnects database, kills the connection and says the connection is closed, and if DEBUG is turned on, the log for this object is shown.
 *
 */
    function close ()
    {
       if ($this->fullDebug)
       {
           $this->showLog();
       }
       $this->disconnect();
       $this->_conn = NULL;
       $this->connected = false;
    }

/**
 * Destructor. Closes connection to the database.
 *
 */
    function __destruct()
    {
        if ($this->__transactionStarted)
        {
            $this->rollback();
        }
        $this->close();
        parent::__destruct();
    }
}


?>