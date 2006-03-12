<?php
/* SVN FILE: $Id$ */

/**
 * Object-relational mapper.
 *
 * DBO-backed object data model, for mapping database tables to Cake objects.
 *
 * PHP versions 5
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
 * @subpackage   cake.cake.libs.model
 * @since        CakePHP v 0.10.0.0
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Included libs
 */
uses('class_registry', 'validators');


/**
 * Object-relational mapper.
 *
 * DBO-backed object data model.
 * Automatically selects a database table name based on a pluralized lowercase object class name
 * (i.e. class 'User' => table 'users'; class 'Man' => table 'men')
 * The table is required to have at least 'id auto_increment', 'created datetime',
 * and 'modified datetime' fields.
 *
 * @package    cake
 * @subpackage cake.cake.libs.model
 * @since      CakePHP v 0.2.9
 *
 */
class Model extends Object
{

/**
 * The name of the DataSource connection that this Model uses
 *
 * @var string
 * @access public
 */
    var $useDbConfig = 'default';

/**
 * The DataSource connection object that this Model uses
 *
 * @var unknown_type
 * @access public
 */
    var $db = null;

/**
 * Enter description here... Still used?
 *
 * @var unknown_type
 * @access public
 * @todo Is this still used? -OJ 22 nov 2006
 */
    var $parent = false;

/**
 * Custom database table name.
 *
 * @var string
 * @access public
 */
    var $useTable = null;

/**
 * Custom display field name. Display fields are used by Scaffold, in SELECT boxes' OPTION elements.
 *
 * @var string
 * @access public
 */
    var $displayField = null;

/**
 *Value of the primary key ID of the record that this model is currently pointing to
 *
 * @var unknown_type
 * @access public
 */
    var $id = false;

/**
 * Container for the data that this model gets from persistent storage (the database).
 *
 * @var array
 * @access public
 */
    var $data = array();

/**
 * Table name for this Model.
 *
 * @var string
 * @access public
 */
    var $table = false;

/**
 * The name of the ID field for this Model.
 *
 * @var string
 * @access public
 */
    var $primaryKey = null;

/**
 * Table metadata
 *
 * @var array
 * @access private
 */
    var $_tableInfo = null;

/**
 * List of validation rules. Append entries for validation as ('field_name' => '/^perl_compat_regexp$/')
 * that have to match with preg_match(). Use these rules with Model::validate()
 *
 * @var array
 */
    var $validate = array();

/**
 * Errors in validation
 * @var array
 */
    var $validationErrors = null;

/**
 * Database table prefix for tables in model.
 *
 * @var string
 */
    var $tablePrefix = null;

/**
 * Name of the model.
 *
 * @var string
 */
    var $name = null;

/**
 * Name of the current model.
 *
 * @var string
 */
    var $currentModel = null;

/**
 * List of table names included in the Model description. Used for associations.
 *
 * @var array
 */
    var $tableToModel = array();

/**
 * List of Model names by used tables. Used for associations.
 *
 * @var array
 */
    var $modelToTable = array();

/**
 * List of Foreign Key names to used tables. Used for associations.
 *
 * @var array
 */
    var $keyToTable = array();

/**
 * Alias table names for model, for use in SQL JOIN statements.
 *
 * @var array
 */
    var $alias = array();

/**
 * Whether or not transactions for this model should be logged
 *
 * @var boolean
 */
    var $logTransactions = false;

/**
 * Whether or not to enable transactions for this model (i.e. BEGIN/COMMIT/ROLLBACK)
 *
 * @var boolean
 */
    var $transactional = false;

/**
 * belongsTo association
 *
 * @var array
 */
    var $belongsTo = array();

/**
 * hasOne association
 *
 * @var array
 */
    var $hasOne = array();

/**
 * hasMany association
 *
 * @var array
 */
    var $hasMany = array();

/**
 * hasAndBelongsToMany association
 *
 * @var array
 */
    var $hasAndBelongsToMany = array();

/**
 * Depth of recursive association
 *
 * @var int
 */
    var $recursive = 1;

/**
 * Default association keys
 *
 * @var array
 */
    var $__associationKeys = array('belongsTo' 	=> array('className', 'conditions', 'order', 'foreignKey', 'counterCache'),
                                  'hasOne' 		=> array('className', 'conditions', 'order', 'foreignKey', 'dependent'),
                                  'hasMany' 	=> array('className', 'conditions', 'order', 'foreignKey', 'fields', 'dependent', 'exclusive', 'finderQuery', 'counterQuery'),
                                  'hasAndBelongsToMany' => array('className', 'joinTable', 'fields', 'foreignKey', 'associationForeignKey', 'conditions', 'order', 'uniq', 'finderQuery', 'deleteQuery', 'insertQuery')
                                 );

/**
 * Holds provided/generated association key names and other data for all associations
 *
 * @var array
 */
    var $__associations = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');

/**
 * The last inserted ID of the data that this model created
 *
 * @var int
 * @access private
 */
    var $__insertID = null;

/**
 * The number of records returned by the last query
 *
 * @access private
 * @var int
 */
    var $__numRows = null;

/**
 * The number of records affected by the last query
 *
 * @access private
 * @var int
 */
    var $__affectedRows = null;

/**
 * Constructor. Binds the Model's database table to the object.
 *
 * @param integer $id
 * @param string $table Name of database table to use.
 * @param DataSource $ds DataSource connection object.
 */
    function __construct ($id=false, $table=null, $ds=null)
    {
        parent::__construct();

        if ($this->name === null)
        {
            $this->name = get_class($this);
        }
        if ($this->primaryKey === null)
        {
            $this->primaryKey = 'id';
        }

        $this->currentModel = Inflector::underscore($this->name);

        ClassRegistry::addObject($this->currentModel, $this);
        $this->id = $id;

        if($this->useTable !== false)
        {
            $this->setDataSource($ds);
            if ($table)
            {
                $tableName = $table;
            }
            else
            {
                if ($this->useTable)
                {
                    $tableName = $this->useTable;
                }
                else
                {
                    $tableName = Inflector::tableize($this->name);
                }
            }

            if (in_array('settableprefix', get_class_methods($this)))
            {
                $this->setTablePrefix();
            }

            if ($this->tablePrefix)
            {
                $this->setSource($this->tablePrefix.$tableName);
            }
            else
            {
                $this->setSource($tableName);
            }

            $this->__createLinks();

            if ($this->displayField == null)
            {
                if ($this->hasField('title'))
                {
                    $this->displayField = 'title';
                }
                if ($this->hasField('name'))
                {
                    $this->displayField = 'name';
                }
                if ($this->displayField == null)
                {
                    $this->displayField = $this->primaryKey;
                }
            }
        }
    }

/**
 * Handles custom method calls, like findBy<field> for DB models,
 * and custom RPC calls for remote data sources
 *
 * @param unknown_type $method
 * @param array $params
 * @return unknown
 * @access protected
 */
    function __call($method, $params)
    {
        return $this->db->query($method, $params, $this);
    }

/**
 * Bind model associations on the fly.
 *
 * @param array $params
 * @return true
 */
    function bindModel($params)
    {
        foreach($params as $assoc => $model)
        {
            $this->__backAssociation[$assoc] = $this->{$assoc};
            foreach($model as $key => $value)
            {
                $modelName = $key;
                $this->__constructLinkedModel($modelName, $modelName);
                $this->{$assoc}[$modelName] = $model[$modelName];
                $this->__generateAssociation($assoc, $modelName);
            }
        }
        return true;
    }

/**
 * Turn off associations on the fly.
 *
 * @param array $params
 * @return true
 */
    function unbindModel($params)
    {
        foreach($params as $assoc => $models)
        {
            $this->__backAssociation[$assoc] = $this->{$assoc};
            foreach($models as $model)
            {
                $this->__backAssociation = array_merge($this->__backAssociation, $this->{$assoc});
                unset($this->{$assoc}[$model]);
            }
        }
        return true;
    }

/**
 * Private helper method to create a set of associations.
 *
 * @access private
 */
    function __createLinks()
    {
// Convert all string-based associations to array based
        foreach($this->__associations as $type)
        {
            if(!is_array($this->{$type}))
            {
                $this->{$type} = explode(',', $this->{$type});
                foreach ($this->{$type} as $i => $className)
                {
                    $className = trim($className);
                    unset($this->{$type}[$i]);
                    $this->{$type}[$className] = array();
                }
            }

            foreach ($this->{$type} as $assoc => $value)
            {
                $className = $assoc;
                if (isset($value['className']) && $value['className'] !== null)
                {
                    $className = $value['className'];
                }
                $this->__constructLinkedModel($assoc, $className);
            }
        }

        foreach($this->__associations as $type)
        {
            foreach ($this->{$type} as $assoc => $value)
            {
                $this->__generateAssociation($type, $assoc);
            }
        }
    }

/**
 * Private helper method to create associated models of given class.
 * @param string $assoc
 * @param string $className Class name
 * @param string $type Type of assocation
 * @access private
 */
    function __constructLinkedModel($assoc, $className)
    {
        $colKey = Inflector::underscore($className);
        if(ClassRegistry::isKeySet($colKey))
        {
            $this->{$className} = ClassRegistry::getObject($colKey);
        }
        else
        {
            $this->{$className} = new $className();
        }

        $this->alias[$assoc] = $this->{$className}->table;
        $this->tableToModel[$this->{$className}->table] = $className;
        $this->modelToTable[$className] = $this->{$className}->table;
    }

/**
 * Build array-based association from string.
 *
 * @param string $type "Belongs", "One", "Many", "ManyTo"
 * @param string $assoc
 * @todo Is the second parameter in use at the moment? It is not referred to in the  method OJ, 30. jan 2006
 * @access private
 */
    function __generateAssociation ($type, $assoc)
    {
        foreach ($this->{$type} as $assocKey => $assocData)
        {
            $class = $assocKey;
            if (isset($this->{$type}[$assocKey]['className']) && $this->{$type}[$assocKey]['className'] !== null)
            {
                $class = $this->{$type}[$assocKey]['className'];
            }
            foreach($this->__associationKeys[$type] as $key)
            {
                if (!isset($this->{$type}[$assocKey][$key]) || $this->{$type}[$assocKey][$key] == null)
                {
                    $data = '';
                    switch($key)
                    {
                        case 'fields':
                            $data = '';
                        break;
                        case 'foreignKey':
                            $data = Inflector::singularize($this->table).'_id';
                            if ($type == 'belongsTo')
                            {
                                $data = Inflector::singularize($this->{$class}->table).'_id';
                            }
                        break;
                        case 'associationForeignKey':
                            $data = Inflector::singularize($this->{$class}->table) . '_id';
                        break;
                        case 'joinTable':
                            $tables = array($this->table, $this->{$class}->table);
                            sort($tables);
                            $data = $tables[0].'_'.$tables[1];
                        break;
                        case 'className':
                            $data = $class;
                        break;
                    }
                    $this->{$type}[$assocKey][$key] = $data;
                }
                if($key == 'foreignKey' && !isset($this->keyToTable[$this->{$type}[$assocKey][$key]]))
                {
                    $this->keyToTable[$this->{$type}[$assocKey][$key]][0] = $this->{$class}->table;
                    $this->keyToTable[$this->{$type}[$assocKey][$key]][1] = $this->{$class}->name;
                }
            }
        }
    }

/**
 * Sets a custom table for your controller class. Used by your controller to select a database table.
 *
 * @param string $tableName Name of the custom table
 */
    function setSource($tableName)
    {
        if($this->db->isInterfaceSupported('listSources'))
        {
            if (!in_array(low($tableName), $this->db->listSources()) && !in_array($tableName, $this->db->listSources()))
            {
                return $this->cakeError('missingTable',array(array('className' => $this->name,
                                                                  'table' => $tableName)));
            }
            else
            {
                $this->table = $tableName;
                $this->tableToModel[$this->table] = $this->name;
                $this->loadInfo();
            }
        }
        else
        {
            $this->table = $tableName;
            $this->tableToModel[$this->table] = $this->name;
            $this->loadInfo();
        }
    }


/**
 * This function does two things: 1) it scans the array $one for the primary key,
 * and if that's found, it sets the current id to the value of $one[id].
 * For all other keys than 'id' the keys and values of $one are copied to the 'data' property of this object.
 * 2) Returns an array with all of $one's keys and values.
 * (Alternative indata: two strings, which are mangled to
 * a one-item, two-dimensional array using $one for a key and $two as its value.)
 *
 * @param mixed $one Array or string of data
 * @param string $two Value string for the alternative indata method
 * @return unknown
 */
    function set ($one, $two=null)
    {
        $this->validationErrors = null;
        if (is_array($one))
        {
            $data = $one;
        }
        else
        {
            $data = array($one=>$two);
        }

        foreach ($data as $n => $v)
        {
            foreach ($v as $x => $y)
            {
                if($x == $this->primaryKey)
                {
                    $this->id = $y;
                }
                $this->data[$n][$x] = $y;
            }
        }
        return $data;
    }

/**
 * Returns an array of table metadata (column names and types) from the database.
 *
 * @return array Array of table metadata
 */
    function loadInfo ()
    {
      if (!is_object($this->_tableInfo) && $this->db->isInterfaceSupported('describe'))
      {
          $this->_tableInfo = new NeatArray($this->db->describe($this));
      }
      return $this->_tableInfo;
    }

/**
 * Returns an associative array of field names and column types.
 *
 * @return array
 */
    function getColumnTypes ()
    {
        $columns = $this->loadInfo();
        $columns = $columns->value;

        $cols = array();
        foreach($columns as $col) {
            $cols[$col['name']] = $this->db->column($col['type']);
        }
        return $cols;
    }

/**
 * Returns the column type of a column in the model
 *
 * @param string $column The name of the model column
 * @return string
 */
    function getColumnType ($column)
    {
        $columns = $this->loadInfo();
        $columns = $columns->value;

        $cols = array();
        foreach($columns as $col) {
            if ($col['name'] == $column)
            {
                return $this->db->column($col['type']);
            }
        }
        return null;
    }

/**
 * Returns true if this Model has given field in its database table.
 *
 * @param string $name Name of field to look for
 * @return boolean
 */
    function hasField ($name)
    {
        if (empty($this->_tableInfo))
        {
          $this->loadInfo();
        }
        if($this->_tableInfo != null)
        {
          return $this->_tableInfo->findIn('name', $name);
        }
        return null;
    }

/**
 * Initializes the model for writing a new record.
 *
 * @return boolean True
 */
    function create ()
    {
      $this->id = false;
      unset($this->data);
      $this->data = array();
      return true;
    }

/**
 * Deprecated
 *
 */
    function setId ($id)
    {
      $this->id = $id;
    }

/**
 * Deprecated. Use query() instead.
 *
 */
    function findBySql ($sql)
    {
      return $this->query($sql);
    }


/**
 * Returns a list of fields from the database
 *
 * @param mixed $id The ID of the record to read
 * @param mixed $fields String of single fieldname, or an array of fieldnames.
 * @return array Array of database fields
 */
    function read ($fields = null, $id = null)
    {
        $this->validationErrors = null;
        if ($id != null)
        {
            $this->id = $id;
        }

        $id = $this->id;
        if (is_array($this->id))
        {
            $id = $this->id[0];
        }

        if ($this->id !== null && $this->id !== false)
        {
            $field = $this->db->name($this->name).'.'.$this->db->name($this->primaryKey);
            return $this->find($field . ' = ' . $this->db->value($id, $this->getColumnType($this->primaryKey)), $fields);
        }
        else
        {
            return false;
        }
    }

/**
 * Returns contents of a field in a query matching given conditions.
 *
 * @param string $name Name of field to get
 * @param array $conditions SQL conditions (defaults to NULL)
 * @param string $order SQL ORDER BY fragment
 * @return field contents
 */
    function field ($name, $conditions = null, $order = null)
    {
        if (isset($this->data[$this->name][$name]))
        {
            return $this->data[$this->name][$name];
        }

        if ($data = $this->find($conditions, $name, $order, 0))
        {
            if (isset($data[$this->name][$name]))
            {
                return $data[$this->name][$name];
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

/**
 * Saves a single field to the database.
 *
 * @param string $name Name of the table field
 * @param mixed $value Value of the field
 * @param boolean $validate Whether or not this model should validate before saving (defaults to false)
 * @return boolean True on success save
 */
    function saveField($name, $value, $validate = false)
    {
        return $this->save(array($this->name => array($name => $value)), $validate);
    }

/**
 * Saves model data to the database.
 * By default, validation occurs before save.
 *
 * @param array $data Data to save.
 * @param boolean $validate If set, validation will be done before the save
 * @param array $fieldList List of fields to allow to be written
 * @return boolean success
 */
    function save ($data = null, $validate = true, $fieldList = array())
    {
        if ($data)
        {
            if (countdim($data) == 1)
            {
                $this->set(array($this->name => $data));
            }
            else
            {
                $this->set($data);
            }
        }

        $whitelist = !(empty($fieldList) || count($fieldList) == 0);

        if(!$this->beforeValidate())
        {
            return false;
        }

        if ($validate && !$this->validates())
        {
            return false;
        }

        if(!$this->beforeSave())
        {
            return false;
        }

        $fields = $values = array();
        $count = 0;

        if(count($this->data) > 1)
        {
            $weHaveMulti = true;
            $joined = false;
        }
        else
        {
            $weHaveMulti = false;
        }

        $newID = null;
        foreach ($this->data as $n => $v)
        {
            if(isset($weHaveMulti) && $count > 0 && count($this->hasAndBelongsToMany) > 0)
            {
                $joined[] = $v;
            }
            else
            {
                foreach ($v as $x => $y)
                {
                    if ($this->hasField($x) && ($whitelist && in_array($x, $fieldList) || !$whitelist))
                    {
                        $fields[] = $x;
                        $values[] = $y;

                        if($x == $this->primaryKey && !is_numeric($y))
                        {
                            $newID = $y;
                        }
                    }
                }
                $count++;
            }
        }

        if (empty($this->id) && $this->hasField('created') && !in_array('created', $fields) && ($whitelist && in_array('created', $fieldList) || !$whitelist))
        {
            $fields[] = 'created';
            $values[] = date('Y-m-d H:i:s');
        }
        if ($this->hasField('modified') && !in_array('modified', $fields) && ($whitelist && in_array('modified', $fieldList) || !$whitelist))
        {
            $fields[] = 'modified';
            $values[] = date('Y-m-d H:i:s');
        }
        if ($this->hasField('updated') && !in_array('updated', $fields) && ($whitelist && in_array('updated', $fieldList) || !$whitelist))
        {
            $fields[] = 'updated';
            $values[] = date('Y-m-d H:i:s');
        }

        if(!$this->exists())
        {
            $this->id = false;
        }

        if(count($fields))
        {
            if(!empty($this->id))
            {
                if ($this->db->update($this, $fields, $values))
                {
                    if(!empty($joined))
                    {
                        $this->__saveMulti($joined, $this->id);
                    }
                    $this->afterSave();
                    $this->data = false;
                    $this->_clearCache();
                    return true;
                }
                else
                {
                    return false;
                }
            }
            else
            {
                if($this->db->create($this, $fields, $values))
                {
                    $this->__insertID = $this->db->lastInsertId($this->table, $this->primaryKey);

                    if (!$this->__insertID && $newID != null)
                    {
                        $this->__insertID = $newID;
                        $this->id = $newID;
                    }
                    else
                    {
                        $this->id = $this->__insertID;
                    }

                    if(!empty($joined))
                    {
                        $this->__saveMulti($joined, $this->id);
                    }

                    $this->afterSave();
                    $this->data = false;
                    $this->_clearCache();
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }
        else
        {
            return false;
        }
    }

/**
 * Saves model hasAndBelongsToMany data to the database.
 *
 * @param array $joined Data to save.
 * @param string $id
 * @return
 * @access private
 */
    function __saveMulti ($joined, $id)
    {
        foreach ($joined as $x => $y)
        {
            foreach ($y as $assoc => $value)
            {
                $joinTable[] = $this->hasAndBelongsToMany[$assoc]['joinTable'];
                $mainKey = $this->hasAndBelongsToMany[$assoc]['foreignKey'];
                $keys[] = $mainKey;
                $keys[] = $this->hasAndBelongsToMany[$assoc]['associationForeignKey'];
                $fields[] = join(',', $keys);
                unset($keys);

                foreach ($value as $update)
                {
                    if(!empty($update))
                    {
                        $values[] = $this->db->value($id, $this->getColumnType($this->primaryKey));
                        $values[] = $this->db->value($update);
                        $values = join(',', $values);
                        $newValues[] = "({$values})";
                        unset($values);
                    }
                }
                if(!empty($newValues))
                {
                    $newValue[] = join(',', $newValues);
                    unset($newValues);
                }
            }
        }

        $total = count($joinTable);
        for ($count = 0; $count < $total; $count++)
        {
            $this->db->execute("DELETE FROM {$joinTable[$count]} WHERE $mainKey = '{$id}'");
            if(!empty($newValue[$count]))
            {
                $this->db->execute("INSERT INTO {$joinTable[$count]} ({$fields[$count]}) VALUES {$newValue[$count]}");
            }
        }
    }

/**
 * Synonym for del().
 *
 * @param mixed $id
 * @see function del
 * @return boolean True on success
 */
    function remove ($id=null)
    {
        return $this->del($id);
    }

/**
 * Removes record for given id. If no id is given, the current id is used. Returns true on success.
 *
 * @param mixed $id Id of record to delete
 * @return boolean True on success
 */
    function del ($id = null, $cascade = true)
    {
        if ($id)
        {
            $this->id = $id;
        }
        $id = $this->id;
        if($this->beforeDelete())
        {
            if ($this->id && $this->db->delete($this))
            {
                $this->__deleteMulti($id);
                $this->__deleteHasMany($id, $cascade);
                $this->__deleteHasOne($id, $cascade);
                $this->afterDelete();
                $this->_clearCache();
                $this->id = false;
                return true;
            }
        }

        return false;
    }

/**
 * Alias for del()
 *
 * @param mixed $id Id of record to delete
 * @return boolean True on success
 */
    function delete ($id = null, $cascade = true)
    {
        return $this->del($id, $cascade);
    }

/**
 * Cascades model deletes to hasMany relationships.
 *
 * @param string $id
 * @return null
 * @access private
 */
    function __deleteHasMany ($id, $cascade)
    {
        foreach ($this->hasMany as $assoc => $data)
        {
            if($data['dependent'] === true && $cascade === true)
            {
                $model =& $this->{$data['className']};
                $field = $model->escapeField($data['foreignKey']);
                $model->recursive = 0;
                $records = $model->findAll("$field = '$id'", $model->primaryKey, null, null);

                foreach($records as $record)
                {
                    $model->del($record[$data['className']][$model->primaryKey]);
                }
            }
        }
    }

/**
 * Cascades model deletes to hasOne relationships.
 *
 * @param string $id
 * @return null
 * @access private
 */
    function __deleteHasOne ($id, $cascade)
    {
        foreach ($this->hasOne as $assoc => $data)
        {
            if($data['dependent'] === true && $cascade === true)
            {
                $model =& $this->{$data['className']};
                $field = $model->escapeField($data['foreignKey']);
                $model->recursive = 0;
                $records = $model->findAll("$field = '$id'", $model->primaryKey, null, null);

                foreach($records as $record)
                {
                    $model->del($record[$data['className']][$model->primaryKey]);
                }
            }
        }
    }

/**
 * Cascades model deletes to HABTM join keys.
 *
 * @param string $id
 * @return null
 * @access private
 */
    function __deleteMulti ($id)
    {
        foreach ($this->hasAndBelongsToMany as $assoc => $data)
        {
            $this->db->execute("DELETE FROM ".$this->db->name($data['joinTable'])." WHERE ".$this->db->name($data['foreignKey'])." = '{$id}'");
        }
    }

/**
 * Returns true if a record with set id exists.
 *
 * @return boolean True if such a record exists
 */
    function exists ()
    {
        if ($this->id)
        {
            $id = $this->id;
            if (is_array($id))
            {
                $id = $id[0];
            }
            return $this->db->hasAny($this->table,$this->primaryKey.'='.$this->db->value($id, $this->getColumnType($this->primaryKey)));
        }
        return false;
    }

/**
 * Returns true if a record that meets given conditions exists
 *
 * @param array $conditions SQL conditions array
 * @return boolean True if such a record exists
 */
    function hasAny ($conditions = null)
    {
        return ($this->findCount($conditions) != false);
    }

/**
 * Return a single row as a resultset array.
 * By using the $recursive parameter, the call can access further "levels of association" than
 * the ones this model is directly associated to.
 *
 * @param array $conditions SQL conditions array
 * @param mixed $fields Either a single string of a field name, or an array of field names
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @param int $recursive The number of levels deep to fetch associated records
 * @return array Array of records
 */
    function find ($conditions = null, $fields = null, $order = null, $recursive = null)
    {
        $data = $this->findAll($conditions, $fields, $order, 1, null, $recursive);
        if (empty($data[0]))
        {
            return false;
        }
        return $data[0];
    }

/**
 * Returns a resultset array with specified fields from database matching given conditions.
 * By using the $recursive parameter, the call can access further "levels of association" than
 * the ones this model is directly associated to.
 *
 * @param mixed $conditions SQL conditions as a string or as an array('field'=>'value',...)
 * @param mixed $fields Either a single string of a field name, or an array of field names
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @param int $limit SQL LIMIT clause, for calculating items per page.
 * @param int $page Page number, for accessing paged data
 * @param int $recursive The number of levels deep to fetch associated records
 * @return array Array of records
 */
    function findAll ($conditions = null, $fields = null, $order = null, $limit = null, $page = 1, $recursive = null)
    {
        $this->id = $this->getID();
        $offset = 0;
        if ($page > 1)
        {
            $offset = ($page - 1) * $limit;
        }
        $limit_str = '';
        if ($limit)
        {
            $limit_str = $this->db->limit($limit, $offset);
        }

        $queryData = array(
            'conditions' 	=> $conditions,
            'fields'		=> $fields,
            'joins'			=> array(),
            'limit'			=> $limit_str,
            'order'			=> $order
        );

        if (!$this->beforeFind($queryData))
        {
            return null;
        }

        $return = $this->afterFind($this->db->read($this, $queryData, $recursive));

        if(isset($this->__backAssociation))
        {
            $this->__resetAssociations();
        }
        return $return;
    }


/**
 * Method is called only when bindTo<ModelName>() is used.
 * This resets the association arrays for the model back
 * to the original as set in the model.
 *
 * @return unknown
 * @access private
 */
    function __resetAssociations()
    {
        foreach ($this->__associations as $type)
        {
            if(isset($this->__backAssociation[$type]))
            {
                $this->{$type} = $this->__backAssociation[$type];
            }
        }
        unset($this->__backAssociation);
        return true;
    }

/**
 * Runs a direct query against the bound DataSource, and returns the result.
 *
 * @param string $data Query data
 * @return array
 */
    function execute ($data)
    {
        $data = $this->db->fetchAll($data);
        foreach ($data as $key => $value)
        {
            foreach ($this->tableToModel as $key1 => $value1)
            {
                if (isset($data[$key][$key1]))
                {
                    $newData[$key][$value1] = $data[$key][$key1];
                }
            }
        }
        if (!empty($newData))
        {
            return $newData;
        }
        return $data;
    }

/**
 * Returns number of rows matching given SQL condition.
 *
 * @param array $conditions SQL conditions array for findAll
 * @param int $recursize The number of levels deep to fetch associated records
 * @return int Number of matching rows
 * @see Model::findAll
 */
    function findCount ($conditions = null, $recursive = 0)
    {
      list($data) = $this->findAll($conditions, 'COUNT(*) AS count', null, null, 1, $recursive);
      if (isset($data[0]['count']))
      {
          return $data[0]['count'];
      }
      return false;
    }

/**
 * Special findAll variation for tables joined to themselves.
 * The table needs the fields id and parent_id to work.
 *
 * @param array $conditions Conditions for the findAll() call
 * @param array $fields Fields for the findAll() call
 * @param string $sort SQL ORDER BY statement
 * @return array
 * @todo Perhaps create a Component with this logic
 */
    function findAllThreaded ($conditions=null, $fields=null, $sort=null)
    {
      return $this->__doThread(Model::findAll($conditions, $fields, $sort), null);
    }

/**
 * Private, recursive helper method for findAllThreaded.
 *
 * @param array $data
 * @param string $root NULL or id for root node of operation
 * @return array
 * @access private
 * @see findAllThreaded
 */
    function __doThread ($data, $root)
    {
        $out = array();
        $sizeOf = sizeof($data);
        for ($ii=0; $ii < $sizeOf; $ii++)
        {
            if (($data[$ii][$this->name]['parent_id'] == $root) || (($root === null) && ($data[$ii][$this->name]['parent_id'] == '0')))
            {
                $tmp = $data[$ii];
                if (isset($data[$ii][$this->name][$this->primaryKey]))
                {
                    $tmp['children'] = $this->__doThread($data, $data[$ii][$this->name][$this->primaryKey]);
                }
                else
                {
                    $tmp['children'] = null;
                }
                $out[] = $tmp;
            }
        }
        return $out;
    }

/**
 * Returns an array with keys "prev" and "next" that holds the id's of neighbouring data,
 * which is useful when creating paged lists.
 *
 * @param string $conditions SQL conditions for matching rows
 * @param string $field Field name (parameter for findAll)
 * @param unknown_type $value
 * @return array Array with keys "prev" and "next" that holds the id's
 */
    function findNeighbours ($conditions = null, $field, $value)
    {
        if(!is_null($conditions))
        {
            $conditions = $conditions.' AND ';
        }
        @list($prev) = Model::findAll($conditions. $field . ' < ' . $this->db->value($value), $field, $field . ' DESC', 1);
        @list($next) = Model::findAll($conditions. $field . ' > ' . $this->db->value($value), $field, $field . ' ASC', 1);

        if (!isset($prev))
        {
            $prev = null;
        }
        if (!isset($next))
        {
            $next = null;
        }
        return array('prev' => $prev, 'next' => $next);
    }

/**
 * Returns a resultset for given SQL statement. Generic SQL queries should be made with this method.
 *
 * @param string $sql SQL statement
 * @return array Resultset
 */
    function query ()
    {
      $params = func_get_args();
      return call_user_func_array(array(&$this->db, 'query'), $params);
    }

/**
 * Returns true if all fields pass validation, otherwise false.
 *
 * @param array $data POST data
 * @return boolean True if there are no errors
 */
    function validates ($data = null)
    {
      if ($data == null)
      {
          $data = $this->data;
      }
      $errors = $this->invalidFields($data);
      return count($errors) == 0;
    }

/**
 * Returns an array of invalid fields.
 *
 * @param array $data
 * @return array Array of invalid fields
 */
    function invalidFields ($data=null)
    {
      if (!isset($this->validate) || is_array($this->validationErrors))
      {
         if (!isset($this->validate))
         {
             return true;
         }
         else
         {
             return $this->validationErrors;
         }
      }

      if ($data == null)
      {
          if (isset($this->data))
          {
              $data = $this->data;
          }
          else
          {
              $data = array();
          }
      }

      $errors = array();
      foreach ($data as $table => $field)
      {
         foreach ($this->validate as $field_name => $validator)
         {
            if (isset($data[$table][$field_name]) && !preg_match($validator, $data[$table][$field_name]))
            {
                $errors[$field_name] = 1;
            }
         }
         $this->validationErrors = $errors;
         return $errors;
      }
    }

/**
 * Returns true if given field name is a foreign key in this Model.
 *
 * @param string $field Returns true if the input string ends in "_id"
 * @return True if the field is a foreign key listed in the belongsTo array.
 */
    function isForeignKey($field)
    {
        $foreignKeys = array();
        if(count($this->belongsTo))
        {
         foreach ($this->belongsTo as $assoc => $data)
         {
            $foreignKeys[] = $data['foreignKey'];
         }
        }

        return (bool)(in_array($field, $foreignKeys));
    }

/**
 * Gets the display field for this model
 *
 * @return string The name of the display field for this Model (i.e. 'name', 'title').
 */
    function getDisplayField()
    {
        return $this->displayField;
    }

/**
 * Returns a resultset array with specified fields from database matching given conditions. Method can be used to generate option lists for SELECT elements.
 *
 * @param mixed $conditions SQL conditions as a string or as an array('field'=>'value',...)
 * @param mixed $fields Either a single string of a field name, or an array of field names
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @param int $limit SQL LIMIT clause, for calculating items per page
 * @param string $keyPath A string path to the key, i.e. "{n}.Post.id"
 * @param string $valuePath A string path to the value, i.e. "{n}.Post.title"
 * @return array An associative array of records, where the id is the key, and the display field is the value
 */
    function generateList ($conditions = null, $order = null, $limit = null, $keyPath = null, $valuePath = null)
    {
        if ($keyPath == null && $valuePath == null)
        {
            $fields = array($this->primaryKey, $this->displayField);
        } else {
            $fields = '*';
        }
        $result = $this->findAll($conditions, $fields, $order, $limit, 1, 0);

        if ($keyPath == null)
        {
            $keyPath = '{n}.'.$this->name.'.'.$this->primaryKey;
        }
        if ($valuePath == null)
        {
            $valuePath = '{n}.'.$this->name.'.'.$this->displayField;
        }
        $keys = $this->db->getFieldValue($result, $keyPath);
        $vals = $this->db->getFieldValue($result, $valuePath);
        return array_combine($keys, $vals);
    }

/**
 * Escapes the field name and prepends the model name. Escaping will be done according to the current database driver's rules.
 *
 * @param unknown_type $field
 * @return string The name of the escaped field for this Model (i.e. id becomes `Post`.`id`).
 */
    function escapeField($field)
    {
        return $this->db->name($this->name).'.'.$this->db->name($field);
    }
/**
 * Returns the current record's ID
 *
 * @param unknown_type $list
 * @return mixed The ID of the current record
 */
    function getID($list = 0)
    {
        if (!is_array($this->id))
        {
            return $this->id;
        }
        if (count($this->id) == 0)
        {
            return false;
        }
        if (isset($this->id[$list]))
        {
            return $this->id[$list];
        }
        foreach ($this->id as $id)
        {
            return $id;
        }
        return false;
    }

/**
 * Returns the ID of the last record this Model inserted
 *
 * @return mixed
 */
    function getLastInsertID ()
    {
      return $this->getInsertID();
    }

/**
 * Returns the ID of the last record this Model inserted
 *
 * @return mixed
 */
    function getInsertID ()
    {
      return $this->__insertID;
    }

/**
 * Returns the number of rows returned from the last query
 *
 * @return int
 */
    function getNumRows ()
    {
//return $this->__numRows;
      return $this->db->lastNumRows();
    }

/**
 * Returns the number of rows affected by the last query
 *
 * @return int
 */
    function getAffectedRows ()
    {
//return $this->__affectedRows;
      return $this->db->lastAffected();
    }

/**
 * Sets the DataSource to which this model is bound
 *
 * @param string $dataSource The name of the DataSource, as defined in Connections.php
 * @return boolean True on success
 */
    function setDataSource($dataSource = null)
    {
        if ($dataSource == null)
        {
            $dataSource = $this->useDbConfig;
        }
        $this->db =& ConnectionManager::getDataSource($dataSource);

        if(!empty($this->db->config['prefix']))
        {
            $this->tablePrefix = $this->db->config['prefix'];
        }
        if(empty($this->db) || $this->db == null || !is_object($this->db))
        {
            return $this->cakeError('missingConnection',array(array('className' => $this->name)));
        }
    }

/**
 * Before find callback
 *
 * @param array $queryData Data used to execute this query, i.e. conditions, order, etc.
 * @return boolean True if the operation should continue, false if it should abort
 */
    function beforeFind(&$queryData)
    {
        return true;
    }

/**
 * After find callback. Can be used to modify any results returned by find and findAll.
 *
 * @param mixed $results The results of the find operation
 * @return mixed Result of the find operation
 */
    function afterFind($results)
    {
        return $results;
    }

/**
 * Before save callback
 *
 * @return boolean True if the operation should continue, false if it should abort
 */
    function beforeSave()
    {
        return true;
    }

/**
 * After save callback
 *
 * @return void
 */
    function afterSave()
    {
        return true;
    }

/**
 * Before delete callback
 *
 * @return boolean True if the operation should continue, false if it should abort
 */
    function beforeDelete()
    {
        return true;
    }

/**
 * After delete callback
 *
 * @return void
 */
    function afterDelete()
    {
        return true;
    }

/**
 * Before validate callback
 *
 * @return void
 */
    function beforeValidate()
    {
        return true;
    }

/**
 * Enter description here...
 *
 * @param string $type If null this deletes cached views if CACHE_CHECK is true
 *                     Will be used to allow deleting query cache also
 * @return boolean true on delete
 */
    function _clearCache($type = null)
    {
        if($type === null)
        {
            if(defined('CACHE_CHECK') && CACHE_CHECK === true)
            {
                $assoc[] =  low(Inflector::pluralize($this->name));
                foreach ($this->__associations as $key => $asscociation)
                {
                    foreach ($this->$asscociation as $key => $className)
                    {
                        $check = low(Inflector::pluralize($className['className']));
                        if(!in_array($check, $assoc))
                        {
                            $assoc[] = low(Inflector::pluralize($className['className']));
                        }
                    }
                }
                echo "<pre>";
                print_r($assoc);
                echo "</pre>";
                die();
                clearCache($assoc);
                return true;
            }
        }
        else
        {
            //Will use for query cache deleting
        }
    }
}

?>