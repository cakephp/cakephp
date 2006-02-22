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
 * @subpackage   cake.cake.libs.model.datasources
 * @since        CakePHP v 0.10.0.1076
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

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
/**
 * Description string for this Database Data Source.
 *
 * @var unknown_type
 */
    var $description = "Database Data Source";
/**
 * Enter description here...
 *
 * @var unknown_type
 */
    var $__bypass = false;
/**
 * Enter description here...
 *
 * @var array
 */
    var $__assocJoins = null;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
    var $startQuote = null;

/**
 * Enter description here...
 *
 * @var unknown_type
 */
    var $endQuote = null;

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

/**
 * Convenience method for DboSource::listSources().
 *
 * @return array
 */
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
      $this->_result = $this->_execute($sql);

      $this->affected = $this->lastAffected();
      $this->took = round((getMicrotime() - $t) * 1000, 0);
      $this->error = $this->lastError();
      $this->numRows = $this->lastNumRows($this->_result);
      $this->logQuery($sql);

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
      if ($this->execute($sql))
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
 * Outputs the contents of the queries log.
 *
 * @param boolean $sorted
 */
    function showLog($sorted=false)
    {
      $log = $sorted?
      sortByKey($this->_queriesLog, 'took', 'desc', SORT_NUMERIC):
      $this->_queriesLog;

      if($this->_queriesCnt > 1)
      {
         $text = 'queries';
      }
      else
      {
          $text = 'query';
      }
      print("<table border=\"1\">\n<tr><th colspan=\"7\">{$this->_queriesCnt} {$text} took {$this->_queriesTime} ms</th></tr>\n");
      print("<tr><td>Nr</td><td>Query</td><td>Error</td><td>Affected</td><td>Num. rows</td><td>Took (ms)</td></tr>\n");

      foreach($log as $k => $i)
      {
         print("<tr><td>".($k + 1)."</td><td>{$i['query']}</td><td>{$i['error']}</td><td style=\"text-align: right\">{$i['affected']}</td><td style=\"text-align: right\">{$i['numRows']}</td><td style=\"text-align: right\">{$i['took']}</td></tr>\n");
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
          return false;// shouldn't we be logging errors somehow?
// TODO: Add hook to error log
      }
    }

/**
 * Output information about an SQL query. The SQL statement, number of rows in resultset,
 * and execution time in microseconds. If the query fails, an error is output instead.
 *
 * @param string $sql Query to show information on.
 */
	function showQuery($sql)
	{
	    $error = $this->error;

	    if (strlen($sql) > 200 && !$this->fullDebug)
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

/**
 * Enter description here...
 *
 * @param Model $model
 * @param unknown_type $fields
 * @param unknown_type $values
 * @return boolean Success
 */
    function create(&$model, $fields = null, $values = null)
    {
        if ($fields == null)
        {
            unset($fields, $values);
            $fields = array_keys($model->data);
            $values = array_values($model->data);
        }

        foreach ($fields as $field)
        {
            $fieldInsert[] = $this->name($field);
        }

        foreach ($values as $value)
        {
            $valueInsert[] = $this->value($value);
        }

        if($this->execute('INSERT INTO '.$model->table.' ('.join(',', $fieldInsert).') VALUES ('.join(',', $valueInsert).')'))
        {
            return true;
        }
        return false;
    }

/**
 * Enter description here...
 *
 * @param Model $model
 * @param array $queryData
 * @param integer $recursive Number of levels of association
 * @return unknown
 */
    function read (&$model, $queryData = array(), $recursive = null)
    {
        $this->__scrubQueryData($queryData);
        $null = null;
        $array = array();
        $linkedModels = array();
        $this->__bypass = false;
        $this->__assocJoins = null;
        if(!is_null($recursive))
        {
            $_recursive = $model->recursive;
            $model->recursive = $recursive;
        }

        if(!empty($queryData['fields']))
        {
            $this->__bypass = true;
        }

        if ($model->recursive > 0)
        {
            foreach($model->__associations as $type)
            {
                foreach($model->{$type} as $assoc => $assocData)
                {
                    $linkModel =& $model->{$assocData['className']};
                    if($model->name == $linkModel->name && $type != 'hasAndBelongsToMany' && $type != 'hasMany')
                    {
                        if (true === $this->generateSelfAssociationQuery($model, $linkModel, $type, $assoc, $assocData, $queryData, false, $null))
                        {
                            $linkedModels[] = $type.'/'.$assoc;
                        }
                    }
                    else
                    {
                        if (true === $this->generateAssociationQuery($model, $linkModel, $type, $assoc, $assocData, $queryData, false, $null))
                        {
                            $linkedModels[] = $type.'/'.$assoc;
                        }
                    }
                }
            }
        }

// Build final query SQL
        $query = $this->generateAssociationQuery($model, $null, null, null, null, $queryData, false, $null);
        $resultSet = $this->fetchAll($query);

        if ($model->recursive > 0)
        {
            foreach($model->__associations as $type)
            {
                foreach($model->{$type} as $assoc => $assocData)
                {
                    if (!in_array($type.'/'.$assoc, $linkedModels))
                    {
                        $linkModel =& $model->{$assocData['className']};
                        $this->queryAssociation($model, $linkModel, $type, $assoc, $assocData, $array, true, $resultSet, $model->recursive);
                    } else {
                    	// Fetch recursively on belongsTo and hasOne
                        if ($model->recursive > 1)
                        {
//$this->queryAssociation($model, $linkModel, $type, $assoc, $assocData, $array, true, $resultSet, $model->recursive - 1);
                        }
                    }
                }
            }
        }

        if(!is_null($recursive))
        {
            $model->recursive = $_recursive;
        }
        return $resultSet;
    }

/**
 * Enter description here...
 *
 * @param Model $model
 * @param unknown_type $linkModel
 * @param string $type Association type
 * @param unknown_type $association
 * @param unknown_type $assocData
 * @param unknown_type $queryData
 * @param unknown_type $external
 * @param unknown_type $resultSet
 * @param integer $recursive Number of levels of association
 */
    function queryAssociation(&$model, &$linkModel, $type, $association, $assocData, &$queryData, $external = false, &$resultSet, $recursive)
    {
        $query = $this->generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet);
        if ($query)
        {
            foreach ($resultSet as $i => $row)
            {
                $q = $this->insertQueryData($query, $resultSet, $association, $assocData, $model, $linkModel, $i);
                $fetch = $this->fetchAll($q);

                if (!empty($fetch) && is_array($fetch))
                {
                    if ($recursive > 0)
                    {
                        foreach($linkModel->__associations as $type1)
                        {
                            if ($recursive > 1)
                            {
                                foreach($linkModel->{$type1} as $assoc1 => $assocData1)
                                {
                                    $deepModel =& $linkModel->{$assocData1['className']};
                                    if ($deepModel->name != $model->name)
                                    {
                                        $this->queryAssociation($linkModel, $deepModel, $type1, $assoc1, $assocData1, $queryData, true, $fetch, $recursive - 1);
                                    }
                                }
                            }
                        }
                    }
                    $this->__mergeAssociation($resultSet[$i], $fetch, $association, $type);
                }
            }
        }
    }

    function __mergeAssociation(&$data, $merge, $association, $type)
    {
        if (isset($merge[0]) && !isset($merge[0][$association]))
        {
            $association = Inflector::pluralize($association);
        }

        if ($type == 'belongsTo' || $type == 'hasOne')
        {
            if (isset($merge[$association]))
            {
                $data[$association] = $merge[$association][0];
            }
            else
            {
                $data[$association] = $merge[0][$association];
            }
        }
        else
        {
        	foreach ($merge as $i => $row)
            {
                if (count($row) == 1)
                {
                    $data[$association][] = $row[$association];
                }
                else
                {
                    $tmp = array_merge($row[$association], $row);
                    unset($tmp[$association]);
                    $data[$association][] = $tmp;
                }
            }
        }
    }

/**
 * Enter description here...
 *
 * @param unknown_type $model
 * @param unknown_type $linkModel
 * @param unknown_type $type
 * @param unknown_type $association
 * @param unknown_type $assocData
 * @param unknown_type $queryData
 * @param unknown_type $external
 * @param unknown_type $resultSet
 * @return unknown
 */
    function generateSelfAssociationQuery(&$model, &$linkModel, $type, $association = null, $assocData = array(), &$queryData, $external = false, &$resultSet)
    {
        $alias = $association;
        if(!isset($queryData['selfJoin']))
        {
            $queryData['selfJoin'] = array();

            $sql  = 'SELECT ' . join(', ', $this->fields($model, $model->name, $queryData['fields'])). ', ';
            $sql .= join(', ', $this->fields($linkModel, $alias, ''));
            $sql .=  ' FROM '.$model->table.' AS ' . $model->name;
            $sql .=  ' LEFT JOIN '.$linkModel->table.' AS ' . $alias;
            $sql .=  ' ON ';
            $sql .= $this->name($model->name).'.'.$this->name($assocData['foreignKey']);
            $sql .= ' = '.$this->name($alias).'.'.$this->name($linkModel->primaryKey);
            if (!in_array($sql, $queryData['selfJoin']))
            {
                $queryData['selfJoin'][] = $sql;
                return true;
            }
        }
        else
        {
            if(isset($this->__assocJoins))
            {
                $replace = ', ';
                $replace .= join(', ', $this->__assocJoins['fields']);
                $replace .= ' FROM';
            }
            else
            {
                $replace = 'FROM';
            }
            $sql  =  $queryData['selfJoin'][0];
            $sql .= ' ' . join(' ', $queryData['joins']);
            $sql .= $this->conditions($queryData['conditions']).' '.$this->order($queryData['order']);
            $sql .= ' '.$this->limit($queryData['limit']);
            $result = preg_replace('/FROM/', $replace, $sql);
            return $result;
        }
    }
/**
 * Enter description here...
 *
 * @param Model $model
 * @param unknown_type $linkModel
 * @param unknown_type $type
 * @param unknown_type $association
 * @param unknown_type $assocData
 * @param unknown_type $queryData
 * @param unknown_type $external
 * @param unknown_type $resultSet
 * @return unknown
 */
    function generateAssociationQuery(&$model, &$linkModel, $type, $association = null, $assocData = array(), &$queryData, $external = false, &$resultSet)
    {
        $this->__scrubQueryData($queryData);
        $joinedOnSelf = false;
        if ($linkModel == null)
        {
            if(array_key_exists('selfJoin', $queryData))
            {
                return $this->generateSelfAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet);
            }
            else
            {
                if(isset($this->__assocJoins))
                {
                    $joinFields = ', ';
                    $joinFields .= join(', ', $this->__assocJoins['fields']);
                }
                else
                {
                    $joinFields = null;
                }
// Generates primary query
            $sql  = 'SELECT ' . join(', ', $this->fields($model, $model->name, $queryData['fields'])) .$joinFields. ' FROM ';
            $sql .= $this->name($model->table).' AS ';
            $sql .= $this->name($model->name).' ' . join(' ', $queryData['joins']).' ';
            $sql .= $this->conditions($queryData['conditions']).' '.$this->order($queryData['order']);
            $sql .= ' '.$this->limit($queryData['limit']);
            }
            return $sql;
        }

        $alias = $association;
        if($model->name == $linkModel->name)
        {
            $joinedOnSelf = true;
        }

        switch ($type)
        {
            case 'hasOne':
                if ($external)
                {
                    if (isset($assocData['finderQuery']))
                    {
                        return $assocData['finderQuery'];
                    }
                    if(!isset($assocData['fields']))
                    {
                        $assocData['fields'] = '';
                    }
                    $sql  = 'SELECT '.join(', ', $this->fields($linkModel, $alias, $assocData['fields']));
                    $sql .= ' FROM '.$this->name($linkModel->table).' AS '.$alias;
                    $conditions = $queryData['conditions'];
                    $condition = $model->escapeField($assocData['foreignKey']);
                    $condition .= '={$__cakeForeignKey__$}';
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
                else if($joinedOnSelf != true)
                {
                    if(!isset($assocData['fields']))
                    {
                        $assocData['fields'] = '';
                    }
                    if($this->__bypass === false)
                    {
                        $fields = join(', ', $this->fields($linkModel, $alias, $assocData['fields']));
                        $this->__assocJoins['fields'][] = $fields;
                    }
                    else
                    {
                        $this->__assocJoins = null;
                    }
                    $sql  = ' LEFT JOIN '.$this->name($linkModel->table);
                    $sql .= ' AS '.$this->name($alias).' ON '.$this->name($alias).'.';
                    $sql .= $this->name($assocData['foreignKey']).'='.$model->escapeField($model->primaryKey);
                    $sql .= $this->order($assocData['order']);

                    if (isset($assocData['conditions']) && !empty($assocData['conditions']))
                    {
                        if(is_array($queryData['conditions']))
                        {
                            $queryData['conditions']  = array_merge($assocData['conditions'], $queryData['conditions']);
                        }
                        else
                        {
                            $queryData['conditions'] = $assocData['conditions'];
                        }
                    }

                    if (!in_array($sql, $queryData['joins']))
                    {
                        $queryData['joins'][] = $sql;
                    }
                    return true;
                }
            break;
            case 'belongsTo':
                if ($external)
                {
                    $sql  = 'SELECT * FROM '.$this->name($linkModel->table).' AS '.$this->name($alias);
                    $conditions = $assocData['conditions'];

                    $condition = $linkModel->escapeField($linkModel->primaryKey);
                    $condition .= '={$__cakeID__$}';

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
                    $sql .= $this->conditions($conditions) . $this->order($assocData['order']);
                    if (isset($assocData['limit']))
                    {
                        $sql .= $this->limit($assocData['limit']);
                    }
                    return $sql;
                }
                else if($joinedOnSelf != true)
                {
                    if(!isset($assocData['fields']))
                    {
                        $assocData['fields'] = '';
                    }
                    if($this->__bypass === false)
                    {
                        $fields = join(', ', $this->fields($linkModel, $alias, $assocData['fields']));
                        $this->__assocJoins['fields'][] = $fields;
                    }
                    else
                    {
                        $this->__assocJoins = null;
                    }
                    $sql  = ' LEFT JOIN '.$this->name($linkModel->table);
                    $sql .= ' AS ' . $this->name($alias) . ' ON ';
                    $sql .= $this->name($model->name).'.'.$this->name($assocData['foreignKey']);
                    $sql .= '='.$this->name($alias).'.'.$this->name($linkModel->primaryKey);

                    if (isset($assocData['conditions']) && !empty($assocData['conditions']))
                    {
                        if(is_array($queryData['conditions']))
                        {
                            $queryData['conditions']  = array_merge($assocData['conditions'], $queryData['conditions']);
                        }
                        else
                        {
                            $queryData['conditions'] = $assocData['conditions'];
                        }
                    }

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
                    $sql  = 'SELECT '.join(', ', $this->fields($linkModel, $alias, $assocData['fields']));
                    $sql .= ' FROM '.$this->name($linkModel->table).' AS '. $this->name($alias);

                    if (is_array($conditions))
                    {
                        $conditions[$alias.'.'.$assocData['foreignKey']] = '{$__cakeID__$}';
                    }
                    else
                    {
                        $cond  = $this->name($alias).'.'.$this->name($assocData['foreignKey']);
                        $cond .= '={$__cakeID__$}';

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

                    $sql = 'SELECT '.join(', ', $this->fields($linkModel, $alias, $assocData['fields']));
                    $sql .= ' FROM '.$this->name($linkModel->table).' AS '.$this->name($alias);
                    $sql .= ' JOIN '.$joinTbl.' ON '.$joinTbl;
                    $sql .= '.'.$this->name($assocData['foreignKey']).'={$__cakeID__$}';
                    $sql .= ' AND '.$joinTbl.'.'.$this->name($assocData['associationForeignKey']);
                    $sql .= ' = '.$this->name($alias).'.'.$this->name($linkModel->primaryKey);

                    $sql .= $this->conditions($assocData['conditions']);
                    $sql .= $this->order($assocData['order']);
                }
                return $sql;
            break;
        }
        return null;
    }

/**
 * Generates and executes an SQL UPDATE statement for given model, fields, and values.
 *
 * @param Model $model
 * @param array $fields
 * @param array $values
 * @return array
 */
    function update (&$model, $fields = null, $values = null)
    {
        $updates = array();
        foreach (array_combine($fields, $values) as $field => $value)
        {
            $updates[] = $this->name($field).'='.$this->value($value);
        }

        $sql  = 'UPDATE '.$this->name($model->table);
        $sql .= ' SET '.join(',', $updates);
        $sql .= ' WHERE '.$this->name($model->primaryKey).'='.$this->value($model->getID());

        return $this->execute($sql);
    }

/**
 * Generates and executes an SQL DELETE statement for given id on given model.
 *
 * @param Model $model
 * @param mixed $id Primary key id number to remove.
 * @return boolean Success
 */
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
            $result = $this->execute('DELETE FROM '.$this->name($model->table).' WHERE '.$this->name($model->primaryKey).'='.$this->value($id));
        }
        if ($result)
        {
            return true;
        }
        return false;
    }

/**
 * Returns a key formatted like a string Model.fieldname(i.e. Post.title, or Country.name)
 *
 * @param unknown_type $model
 * @param unknown_type $key
 * @param unknown_type $assoc
 * @return string
 */
    function resolveKey($model, $key, $assoc = null)
    {
        if ($assoc == null)
        {
            $assoc = $model->name;
        }

        if (!strpos('.', $key))
        {
            return $this->name($model->table).'.'.$this->name($key);
        }
        return $key;
    }

/**
 * Enter description here...
 *
 * @param unknown_type $model
 * @param unknown_type $field
 */
    function getColumnType (&$model, $field)
    {
        $columns = $model->loadInfo();
    }

/**
 * Private helper method to remove query metadata in given data array.
 *
 * @param array $data
 */
    function __scrubQueryData(&$data)
    {
        if (!isset($data['conditions']))
        {
            $data['conditions'] = ' 1 = 1 ';
        }
        if (!isset($data['fields']))
        {
            $data['fields'] = '';
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

/**
 * Generates the fields list of an SQL query.
 *
 * @param Model $model
 * @param string $alias Alias tablename
 * @param mixed $fields
 * @return array
 */
    function fields (&$model, $alias, $fields)
    {
        if (is_array($fields))
        {
            $fields = $fields;
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
                 foreach ($model->_tableInfo->value as $field)
                 {
                     $fields[]= $field['name'];
                 }
            }
        }

        $count = count($fields);
        if ($count >= 1 && $fields[0] != '*')
        {
            for ($i = 0; $i < $count; $i++)
            {
                if(!preg_match('/^avg\\(|^count\\(|^count_big\\(|^min\\(|^max\\(|^distinct|^sum\\(|^concat\\(|^rand\\(|^stddev_pop|^var_pop|^least\\(|^greatest\\(|^octet_length\\(|^length\\(|^extract\\(^translate\\(|^conv\\(/i', $fields[$i]))
                {
                    $dot = strrpos($fields[$i], '.');
                    if ($dot === false)
                    {
                        $fields[$i] = $this->name($alias).'.'.$this->name($fields[$i]);
                    }
                    else
                    {
                        $build = explode('.',$fields[$i]);
                        $fields[$i] = $this->name($build[0]).'.'.$this->name($build[1]);
                    }
                }
            }
        }
        return $fields;
    }

/**
 * Creates a WHERE clause by parsing given conditions data.
 *
 * @param mixed $conditions Array or string of conditions
 * @return string SQL fragment
 */
    function conditions ($conditions)
    {
        $clause = '';
        if (!is_array($conditions))
        {
            if (!preg_match('/^WHERE\\x20|^GROUP\\x20BY\\x20|^HAVING\\x20|^ORDER\\x20BY\\x20/i', $conditions, $match))
            {
                $clause = 'WHERE ';
            }
        }
        if (is_string($conditions))
        {
            if (trim($conditions) == '')
            {
                $conditions = ' 1 = 1';
            }
            else
            {
                $start = null;
                $end = null;
                if(!empty($this->startQuote))
                {
                    $start = '\\\\'.$this->startQuote.'\\\\';
                }
                $end = $this->endQuote;
                if(!empty($this->endQuote))
                {
                    $end = '\\\\'.$this->endQuote.'\\\\';
                }
                preg_match_all('/(\'[^\'\\\]*(?:\\\.[^\'\\\]*)*\')|(?P<field>[a-z0-9_'.$start.$end.']*\\.[a-z0-9_'.$start.$end.']*)/i', $conditions, $match, PREG_PATTERN_ORDER);

                if(isset($match['field'][0]))
                {
                    $pregCount = count($match['field']);
                    for ($i = 0; $i < $pregCount; $i++)
                    {
                        if(!empty($match['field'][$i]))
                        {
                            $conditions = preg_replace('/'.$match['field'][$i].'/', $this->name($match['field'][$i]), $conditions);
                        }
                    }
                }
            }
            return $clause.$conditions;
        }
        else
        {
            $clause = 'WHERE ';
            $out = array();
            $count = 0;
            $operator = null;
            foreach ($conditions as $key => $value)
            {
                if($count > 0)
                {
                    $operator = ' AND ';
                }
                if (is_array($value))
                {
                    $data = $key . ' IN (';
                    foreach ($value as $valElement)
                    {
                        $data .= $this->value($valElement) . ', ';
                    }
                    $data[strlen($data)-2] = ')';
                }
                elseif (is_numeric($key))
                {
                    $data = ' '. $value;
                }
                elseif (preg_match('/^(\\x20(?P<operator>[\\w]+|<=?|>=?|<>|!?=)\\x20)?(?P<value>.*)/i', $value, $match))
                {
                    if (preg_match('/(?P<conditional>\\x20[\\w]*\\x20)/', $key, $regs))
                    {
                        $operator = $regs['conditional'];
                        $key = preg_replace('/'.$regs['conditional'].'/', '', $key);
                    }
                    if(empty($match['operator']))
                    {
                        $match['operator'] = ' = ';
                    }
                    if (strpos($match['value'], '--return') === 0)
                    {
                        $match['value'] = str_replace('--return', '', $match['value']);
                        $data = $this->name($key) . ' '.$match['operator'].' '. $match['value'];
                    }
                    else
                    {
                        $data = $this->name($key) . ' '.$match['operator'].' '. $this->value($match['value']);
                    }
                }
                else
                {
                   if (strpos($value, '--return') === 0)
                    {
                        $value = str_replace('--return', '', $value);
                    }
                    elseif (($value != '{$__cakeID__$}') && ($value != '{$__cakeForeignKey__$}'))
                    {
                        $value = $this->value($value);
                    }

                  $data = $this->name($key) . ' = ';

                  if ($value === null)
                  {
                      $data .= 'null';
                  }
                  else
                  {
                      $data .= $value;
                  }
                }
                $count++;
                $out[] = $operator.$data;
            }
            return $clause . join('', $out);
        }
    }

/**
 * To be overridden in subclasses.
 *
 */
    function limit ()
    {
    }

/**
 * Returns an ORDER BY clause as a string.
 *
 * @param string $key Field reference, as a key (i.e. Post.title)
 * @param string $direction Direction (ASC or DESC)
 * @return string ORDER BY clause
 */
    function order ($keys, $direction = 'ASC')
    {
        if (empty($keys))
        {
            return '';
        }
        if(is_array($keys))
        {
            foreach($keys as $key => $value)
            {
                if(is_numeric($key))
                {
                    $key = $value;
                    $value = ' '.$direction;
                }
                else
                {
                    $value= ' '.$value;
                }
                $order[] = $this->name($key).$value;
            }
            return ' ORDER BY '.join(',', $order);
        }
        else
        {
            $keys = preg_replace('/ORDER\\x20BY/i', '', $keys);
            if (strpos('.', $keys))
            {
                preg_match_all('/([a-zA-Z0-9_]{1,})\\.([a-zA-Z0-9_]{1,})/', $keys, $result, PREG_PATTERN_ORDER);
                $pregCount = count($result[0]);

                for ($i = 0; $i < $pregCount; $i++)
                {
                    $keys = preg_replace('/'.$result[0][$i].'/', $this->name($result[0][$i]), $keys);
                }
                if (preg_match('/\\x20ASC|\\x20DESC/i', $keys))
                {
                    return ' ORDER BY '.$keys;
                }
                else
                {
                    return ' ORDER BY '.$keys.' '.$direction;;
                }
            }
            elseif (preg_match('/(?P<direction>\\x20ASC|\\x20DESC)/i', $keys, $match))
            {
                $direction = $match['direction'];
                $keys = preg_replace('/'.$match['direction'].'/', '', $keys);
            }
            else
            {
                $direction = ' '.$direction;
            }
            return ' ORDER BY '.$this->name($keys).$direction;
        }
    }

/**
 * Disconnects database, kills the connection and says the connection is closed,
 * and if DEBUG is turned on, the log for this object is shown.
 *
 */
    function close ()
    {
        if ($this->fullDebug)
        {
            $this->showLog();
        }
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
}
?>