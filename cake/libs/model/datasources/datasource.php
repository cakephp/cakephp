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
 * @since        CakePHP v 0.10.x.1379
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package    cake
 * @subpackage cake.cake.libs.model.datasources
 * @since      CakePHP v 0.10.x.1379
 *
 */
class DataSource extends Object
{

/**
 * Are we connected to the DataSource?
 *
 * @var boolean
 * @access public
 */
   var $connected = false;

/**
 * Print debug info?
 *
 * @var boolean
 * @access public
 */
   var $debug = false;

/**
 * Print full query debug info?
 *
 * @var boolean
 * @access public
 */
   var $fullDebug = false;

/**
 * Error description of last query
 *
 * @var unknown_type
 * @access public
 */
   var $error = null;

/**
 * String to hold how many rows were affected by the last SQL operation.
 *
 * @var unknown_type
 * @access public
 */
   var $affected = null;

/**
 * Number of rows in current resultset
 *
 * @var int
 * @access public
 */
   var $numRows = null;

/**
 * Time the last query took
 *
 * @var unknown_type
 * @access public
 */
   var $took = null;

/**
 * Enter description here...
 *
 * @var unknown_type
 * @access private
 */
   var $_result = null;

/**
 * Queries count.
 *
 * @var unknown_type
 * @access private
 */
   var $_queriesCnt = 0;

/**
 * Total duration of all queries.
 *
 * @var unknown_type
 * @access private
 */
   var $_queriesTime = null;

/**
 * Log of queries executed by this DataSource
 *
 * @var unknown_type
 * @access private
 */
   var $_queriesLog = array();

/**
 * Maximum number of items in query log, to prevent query log taking over
 * too much memory on large amounts of queries -- I we've had problems at
 * >6000 queries on one system.
 *
 * @var int Maximum number of queries in the queries log.
 * @access private
 */
   var $_queriesLogMax = 200;

/**
 * The default configuration of a specific DataSource
 *
 * @var array
 * @access public
 */
    var $_baseConfig = array();

/**
 * Holds references to descriptions loaded by the DataSource
 *
 * @var array
 * @access private
 */
    var $__descriptions = array();

/**
 * A reference to the physical connection of this DataSource
 *
 * @var array
 * @access public
 */
    var $connection = null;

/**
 * The DataSource configuration
 *
 * @var array
 * @access public
 */
    var $config = array();

/**
 * The DataSource configuration key name
 *
 * @var string
 * @access public
 */
    var $configKeyName = null;

/**
 * Whether or not this DataSource is in the middle of a transaction
 *
 * @var boolean
 * @access public
 */
    var $__transactionStarted = false;


   function __construct ()
   {
       parent::__construct();
       if(func_num_args() > 0)
       {
          $this->setConfig(func_get_arg(0));
       }
   }

/**
  * Returns true if the DataSource supports the given interface (method)
  *
  * @param string $interface The name of the interface (method)
  * @return boolean True on success
  */
   function isInterfaceSupported ($interface)
   {
      $methods = get_class_methods(get_class($this));
      $methods = strtolower(implode('|', $methods));
      $methods = explode('|', $methods);
      return in_array(strtolower($interface), $methods);
   }

/**
  * Sets the configuration for the DataSource
  *
  * @param array $config The configuration array
  * @return void
  */
   function setConfig ($config)
   {
       if(is_array($this->_baseConfig))
       {
          $this->config = $this->_baseConfig;
          foreach($config as $key => $val)
          {
             $this->config[$key] = $val;
          }
       }
   }

/**
  * Cache the DataSource description
  *
  * @param string $object The name of the object (model) to cache
  * @param mixed $data The description of the model, usually a string or array
  * @return void
  */
    function __cacheDescription ($object, $data = null)
    {
        if (DEBUG > 0)
        {
            $expires = "+10 seconds";
        }
        else
        {
            $expires = "+1 day";
        }

        if ($data !== null)
        {
            $this->__descriptions[$object] = &$data;
        }

        $new = cache('models'.DS.low(get_class($this)).'_'.$object, serialize($data), $expires);
        if($new != null)
        {
           $new = unserialize($new);
        }
        return $new;
    }

/**
 * Parses conditions array (or just passes it if it's a string)
 * @return string
 *
 */
    function conditions ($conditions)
    {
        return $conditions;
    }

    function name ($name)
    {
        return $name;
    }

    function value ($value)
    {
        return $value;
    }

    function describe ($model)
    {
        if (isset($this->__descriptions[$model->source]))
        {
            return $this->__descriptions[$model->source];
        }

        $cache = $this->__cacheDescription($model->source);
        if ($cache !== null)
        {
            $this->__descriptions[$model->source] = &$cache;
            return $cache;
        }
        return null;
    }

    function create (&$model, $fields = null, $values = null)
    {
        return false;
    }

    function read (&$model, $queryData = array())
    {
        return false;
    }

    function update (&$model, $fields = null, $values = null)
    {
        return false;
    }

    function delete (&$model, $id = null)
    {
        if ($id == null)
        {
            $id = $model->id;
        }
    }

    function fields ($fields)
    {
        return $fields;
    }

    function getColumnType (&$model, $fields)
    {
        return false;
    }

    function insertQueryData($query, $data, $association, $assocData, &$model, &$linkModel, $index)
    {
        $keys = array('{$__cake_id__$}', '{$__cake_foreignKey__$}');
        foreach($keys as $key)
        {
            if (strpos($query, $key) !== false)
            {
                switch($key)
                {
                    case '{$__cake_id__$}':
                        $val = null;
                        if (isset($data[$index][$model->name])) {
                            $val = $data[$index][$model->name][$model->primaryKey];
                        }
                    break;
                    case '{$__cake_foreignKey__$}':

                    break;
                }
                $query = r($key, $this->value($val), $query);
            }
        }
        return $query;
    }

    function resolveKey($model, $key)
    {
        return $key;
    }

    function getFieldValue ($data, $path)
    {
        if (!is_array($path))
        {
            $path = explode('.', $path);
        }
        $tmp = array();

        foreach ($path as $i => $key)
        {
            if (intval($key) > 0 || $key == '0')
            {
                if (isset($data[intval($key)]))
                {
                    $data = $data[intval($key)];
                }
                else
                {
                    return null;
                }
            }
            elseif ($key == '{n}')
            {
                foreach ($data as $j => $val)
                {
                    if (is_subclass_of($this, 'DataSource') || is_subclass_of($this, 'datasource'))
                    {
                        $tmp[] = $this->getFieldValue($val, array_slice($path, $i + 1));
                    }
                    else
                    {
                        $tmp[] = DataSource::getFieldValue($val, array_slice($path, $i + 1));
                    }
                }
                return $tmp;
            }
            else
            {
                if (isset($data[$key]))
                {
                    $data = $data[$key];
                }
                else
                {
                    return null;
                }
            }
        }
        return $data;
    }

    function __destruct ()
    {
        if ($this->connected)
        {
            $this->close();
        }
    }
}

?>