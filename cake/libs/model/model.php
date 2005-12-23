<?php
/* SVN FILE: $Id$ */

/**
 * Object-relational mapper.
 *
 * DBO-backed object data model, for mapping database tables to Cake objects.
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
 * @subpackage   cake.cake.libs.model
 * @since        CakePHP v 0.10.0.0
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Enter description here...
 */
uses('object',  'class_registry', 'validators', 'inflector');


/**
 * Short description for class
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
 * Enter description here...
 *
 * @var unknown_type
 * @access public
 */
   var $parent = false;

/**
 * Custom database table name
 *
 * @var string
 * @access public
 */
   var $useTable = null;

/**
 * Enter description here...
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
 * Primary Key.
 *
 * If set in the model sub class this will be used.
 * If not set, 'id' will be expected as the primary key
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
 * Array of other Models this Model references in a belongsTo (one-to-one) relationship.
 *
 * @var array
 * @access private
 */
   var $_belongsToOther = array();


/**
 * Array of other Models this Model references in a hasOne (one-to-one) relationship.
 *
 * @var array
 * @access private
 */
   var $_oneToOne = array();


/**
 * Array of other Models this Model references in a hasMany (one-to-many) relationship.
 *
 * @var array
 * @access private
 */
   var $_oneToMany = array();


/**
 * Array of other Models this Model references in a hasAndBelongsToMany (many-to-many) relationship.
 *
 * @var array
 * @access private
 */
   var $_manyToMany  = array();

/**
 * Enter description here...
 *
 * append entries for validation as ('field_name' => '/^perl_compat_regexp$/') that has to match with preg_match()
 * validate with Model::validate()
 * @var array
 */
   var $validate = array();

/**
 * Append entries for validation as ('field_name' => '/^perl_compat_regexp$/') that has to match with preg_match()
 * validate with Model::validate()
 * @var array
 */
   var $validationErrors = null;

/**
 * Prefix for tables in model.
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
 * Name of the model.
 *
 * @var string
 */
   var $currentModel = null;

/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $tableToModel = array();

/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $alias = array();

/**
 * Custom database configuration name to use
 *
 * @var string
 * @access public
 */
   var $useDbConfig = 'default';


/**
 * Constructor. Binds the Model's database table to the object.
 *
 * @param unknown_type $id
 * @param string $table Database table to use.
 * @param unknown_type $db Database connection object.
 */
    function __construct ($id=false, $table=null, $db=null)
    {
        if($this->name === null)
        {
            $this->name = get_class($this);
        }

        if($this->primaryKey == null)
        {
            $this->primaryKey = 'id';
        }

        $this->currentModel = Inflector::underscore($this->name);

        if($db != null)
        {
            $this->db =& $db;
        }
        else
        {
            $dboFactory = DboFactory::getInstance($this->useDbConfig);
            $this->db =& $dboFactory;
            if(empty($this->db))
            {
                $this->missingConnection();
            }
        }

        $classRegistry =& ClassRegistry::getInstance();
        $classRegistry->addObject($this->currentModel, $this);

        if ($id)
        {
            $this->id = $id;
        }
        if($this->useTable !== false)
        {
        $tableName = $table? $table: ($this->useTable? $this->useTable: Inflector::tableize($this->name));

        if (in_array('settableprefix', get_class_methods($this->name)))
        {
            $this->setTablePrefix();
        }

        $this->tablePrefix? $this->setTable($this->tablePrefix.$tableName): $this->setTable($tableName);

        parent::__construct();
        $this->createLinks();
        }
        else
        {
            parent::__construct();
        }
    }

/**
 * Creates association relationships.
 *
 */
    function createLinks()
    {
        if (!empty($this->belongsTo))
	    {
	        $this->_belongsToLink();
	    }
	    if (!empty($this->hasOne))
	    {
	        $this->_hasOneLink();
	    }
	    if (!empty($this->hasMany))
	    {
	        $this->_hasManyLinks();
	    }
	    if (!empty($this->hasAndBelongsToMany))
	    {
	        $this->_hasAndBelongsToManyLinks();
	    }
	}

/**
 * Enter description here...
 *
 * @access private
 */
    function _belongsToLink()
    {
        if(is_array($this->belongsTo))
        {
            foreach ($this->belongsTo as $association => $associationValue)
            {
                $className = $association;
                $this->_associationSwitch($association, $className, $associationValue, 'Belongs');
            }
        }
        else
        {
            $association = explode(',', $this->belongsTo);
            foreach ($association as $className)
            {
                $this->_constructAssociatedModels($className, $className , 'Belongs');
                $this->linkAssociation('Belongs', $className, $className, $this->id);
            }
        }
    }

/**
 * Enter description here...
 *
 * @access private
 */
    function _hasOneLink()
    {
        if(is_array($this->hasOne))
        {
            foreach ($this->hasOne as $association => $associationValue)
            {
                $className = $association;
                $this->_associationSwitch($association, $className, $associationValue, 'One');
            }
        }
        else
        {
            $association = explode(',', $this->hasOne);
            foreach ($association as $className)
            {
                $this->_constructAssociatedModels($className, $className , 'One');
                $this->linkAssociation('One', $className, $className, $this->id);
            }
        }
    }

/**
 * Enter description here...
 *
 * @access private
 */
	function _hasManyLinks()
	{
	    if(is_array($this->hasMany))
	    {
	        foreach ($this->hasMany as $association => $associationValue)
	        {
	            $className = $association;
	            $this->_associationSwitch($association, $className, $associationValue, 'Many');
	        }
	    }
	   else
	   {
	       $association = explode(',', $this->hasMany);
	       foreach ($association as $className)
	       {
	           $this->_constructAssociatedModels($className, $className , 'Many');
	           $this->linkAssociation('Many', $className, $className, $this->id);
	       }
	   }
	}

/**
 * Enter description here...
 *
 * @access private
 */
    function _hasAndBelongsToManyLinks()
    {
        if(is_array($this->hasAndBelongsToMany))
        {
            foreach ($this->hasAndBelongsToMany as $association => $associationValue)
            {
                $className = $association;
                $this->_associationSwitch($association, $className, $associationValue, 'ManyTo');
            }
        }
        else
        {
            $association = explode(',', $this->hasAndBelongsToMany);
            foreach ($association as $className)
            {
                $this->_constructAssociatedModels($className, $className , 'ManyTo');
                $this->linkAssociation('ManyTo', $className, $className, $this->id);
            }
        }
    }

/**
 * Enter description here...
 *
 * @param unknown_type $className
 * @param unknown_type $associationValue
 * @param unknown_type $type
 * @access private
 */
    function _associationSwitch($association, $className, $associationValue, $type)
    {
        $classCreated = false;

        foreach ($associationValue as $option => $optionValue)
        {
            if (($option === 'className') && ($classCreated === false))
            {
                $className = $optionValue;
            }

            if ($classCreated === false)
            {
                $this->_constructAssociatedModels($association, $className , $type);
                $classCreated = true;
            }

            switch($option)
            {
                case 'associationForeignKey':
                    $this->{$association.'_associationforeignkey'} = $optionValue;
                break;

                case 'conditions':
                    $this->{$association.'_conditions'} = $optionValue;
                break;

                case 'counterCache':
                    $this->{$association.'_countercache'} = $optionValue;
                break;

                case 'counterSql':
                    $this->{$association.'_countersql'} = $optionValue;
	            break;

	            case 'deleteSql':
                    $this->{$association.'_deletesql'} = $optionValue;
	            break;

	            case 'dependent':
	               $this->{$association.'_dependent'} = $optionValue;
	            break;

	            case 'exclusive':
                    $this->{$association.'_exclusive'} = $optionValue;
	            break;

	            case 'finderSql':
                    $this->{$association.'_findersql'} = $optionValue;
	            break;

	            case 'foreignKey':
                    $this->{$association.'_foreignkey'} = $optionValue;
	            break;

	            case 'insertSql':
                    $this->{$association.'_insertsql'} = $optionValue;
	            break;

	            case 'joinTable':
                    $this->{$association.'_jointable'} = $optionValue;
	            break;

	            case 'order':
                    $this->{$association.'_order'} = $optionValue;
                break;

                case 'uniq':
                    $this->{$association.'_uniq'} = $optionValue;
	            break;

                case 'fields':
                    $this->{$association.'_fields'} = $optionValue;
	            break;
               }
        }
        $this->linkAssociation($type, $association, $className, $this->id);
    }

/**
 * Enter description here...
 *
 * @param unknown_type $className
 * @param unknown_type $type
 * @access private
 */
    function _constructAssociatedModels($association, $className, $type)
    {
        $collectionKey = Inflector::underscore($className);
        $classRegistry =& ClassRegistry::getInstance();

        if(!$classRegistry->isKeySet($collectionKey))
        {
            $this->{$className} = new $className();
        }
        else
        {
            $this->{$className} = $classRegistry->getObject($collectionKey);
        }

        switch($type)
        {
            case 'Belongs':
                $this->{$association.'_conditions'} = null;
                $this->{$association.'_order'} = null;
                $this->{$association.'_foreignkey'} = Inflector::singularize($this->{$className}->table).'_id';
                $this->{$association.'_countercache'} = null;
            break;

            case 'One':
                $this->{$association.'_conditions'} = null;
                $this->{$association.'_order'} = null;
                $this->{$association.'_dependent'} = null;
                $this->{$association.'_foreignkey'} = Inflector::singularize($this->table).'_id';
            break;

            case 'Many':
                $this->{$association.'_conditions'} = null;
                $this->{$association.'_order'} = null;
                $this->{$association.'_foreignkey'} = Inflector::singularize($this->table).'_id';
                $this->{$association.'_fields'} = '*';
                $this->{$association.'_dependent'} = null;
                $this->{$association.'_exclusive'} = null;
                $this->{$association.'_findersql'} = null;
                $this->{$association.'_countersql'} = null;
            break;

            case 'ManyTo':
                $tableSort[0] = $this->table;
                $tableSort[1] = $this->{$className}->table;
                sort($tableSort);
                $joinTable = $tableSort[0] . '_' . $tableSort[1];
                $key1 = Inflector::singularize($this->table) . '_id';
                $key2 = Inflector::singularize($this->{$className}->table) . '_id';
                $this->{$association.'_jointable'} = $joinTable;
                $this->{$association.'_fields'} = '*';
                $this->{$association.'_foreignkey'} = $key1;
                $this->{$association.'_associationforeignkey'} = $key2;
                $this->{$association.'_conditions'} = null;
                $this->{$association.'_order'} = null;
                $this->{$association.'_uniq'} = null;
                $this->{$association.'_findersql'} = null;
                $this->{$association.'_deletesql'} = null;
                $this->{$association.'_insertsql'} = null;
            break;
        }
        $this->tableToModel[$this->{$className}->table] = $className;
    }

/**
 * Enter description here...
 *
 * @param unknown_type $type
 * @param unknown_type $tableName
 * @param unknown_type $value
 */
    function linkAssociation ($type, $association, $model, $value=null)
    {
        switch ($type)
        {
            case 'Belongs':
                $this->alias[$association] = $this->{$model}->table;
                $this->_belongsToOther[] = array($association, $model, $value);
            break;

            case 'One':
                $this->alias[$association] = $this->{$model}->table;
                $this->_oneToOne[] = array($association, $model, $value);
            break;

            case 'Many':
                $this->alias[$association] = $this->{$model}->table;
                $this->_oneToMany[] = array($association, $model, $value);
            break;

            case 'ManyTo':
                $this->alias[$association] = $this->{$model}->table;
                $this->_manyToMany[]  = array($association, $model, $value);
            break;
        }
    }

/**
 * Sets a custom table for your controller class. Used by your controller to select a database table.
 *
 * @param string $tableName Name of the custom table
 */
    function setTable($tableName)
    {
        if (!in_array(strtolower($tableName), $this->db->tables()))
        {
            $this->missingTable($tableName);
            exit();
        }
        else
        {
            $this->table = $tableName;
            $this->tableToModel[$this->table] = $this->name;
            $this->loadInfo();
        }
    }


/**
 * This function does two things: 1) it scans the array $one for they key 'id',
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
        $data = is_array($one)? $one : array($one=>$two);

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
 * Sets current Model id to given $id.
 *
 * @param int $id Id
 */
    function setId ($id)
    {
        $this->id = $id;
    }

/**
 * Returns an array of table metadata (column names and types) from the database.
 *
 * @return array Array of table metadata
 */
    function loadInfo ()
    {
        if (empty($this->_tableInfo))
        {
            $this->_tableInfo = new NeatArray($this->db->fields($this->table));
        }
        return $this->_tableInfo;
    }

/**
 * Returns true if given field name exists in this Model's database table.
 * Starts by loading the metadata into the private property table_info if that is not already set.
 *
 * @param string $name Name of table to look in
 * @return boolean
 */
    function hasField ($name)
    {
        if (empty($this->_tableInfo))
        {
            $this->loadInfo();
        }
        return $this->_tableInfo->findIn('name', $name);
    }

/**
 * Initializes the model for writing a new record
 *
 * @return boolean True on success
 */
    function create ()
    {
        $this->id = false;
        unset($this->data);
        $this->data = array();
    }


/**
 * Returns a list of fields from the database
 *
 * @param mixed $fields String of single fieldname, or an array of fieldnames.
 * @return array Array of database fields
 */
    function read ($fields=null)
    {
        $this->validationErrors = null;
        if(is_array($this->id))
        {
            return $this->id? $this->find("$this->name.$this->primaryKey = '{$this->id[0]}'", $fields): false;
        }
        else
        {
            return $this->id? $this->find("$this->name.$this->primaryKey = '{$this->id}'", $fields): false;
        }
    }

/**
 * Returns contents of a field in a query matching given conditions.
 *
 * @param string $name Name of field to get
 * @param string $conditions SQL conditions (defaults to NULL)
 * @return field contents
 */
    function field ($name, $conditions=null, $order=null)
    {
        if (isset($this->data[$this->name][$name]))
        {
            return $this->data[$this->name][$name];
        }

        if ($conditions)
        {
            $conditions = $this->parseConditions($conditions);
        }

        if ($data = $this->find($conditions, $name, $order))
        {
            return isset($data[$this->name][$name])? $data[$this->name][$name]: false;
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
 * @return boolean True on success save
 */
    function saveField($name, $value)
    {
        return Model::save(array($this->name=>array($name=>$value)), false);
    }

/**
 * Saves model data to the database.
 *
 * @param array $data Data to save.
 * @param boolean $validate
 * @return boolean success
 */
    function save ($data=null, $validate=true)
    {
        if ($data)
        {
            $this->set($data);
        }

        if ($validate && !$this->validates())
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

        foreach ($this->data as $n=>$v)
        {
            if(isset($weHaveMulti) && $count > 0 && !empty($this->_manyToMany))
            {
                $joined[] = $v;
            }
            else
            {
                foreach ($v as $x => $y)
                {
                    if ($this->hasField($x))
                    {
                        $fields[] = $x;
                        $values[] = (ini_get('magic_quotes_gpc') == 1) ?
                                    $this->db->prepare(stripslashes($y)) : $this->db->prepare($y);

                        if($x == $this->primaryKey && !is_numeric($y))
                        {
                            $newID = $y;
                        }
                    }
                }
                $count++;
            }
        }

        if (empty($this->id) && $this->hasField('created') && !in_array('created', $fields))
        {
            $fields[] = 'created';
            $values[] = date("'Y-m-d H:i:s'");
        }
        if ($this->hasField('modified') && !in_array('modified', $fields))
        {
            $fields[] = 'modified';
            $values[] = 'NOW()';
        }
        if(!$this->exists())
        {
            $this->id = false;
        }
        if(count($fields))
        {
            if(!empty($this->id))
            {
                $sql = array();
                foreach (array_combine($fields, $values) as $field=>$value)
                {
                    $sql[] = $field.'='.$value;
                }

                $sql = "UPDATE {$this->table} SET ".join(',', $sql)." WHERE $this->primaryKey = '{$this->id}'";

                if ($this->db->query($sql))
                {
                    if(!empty($joined))
                    {
                        $this->_saveMulti($joined, $this->id);
                    }
                    $this->data = false;
                    return true;
                }
                else
                {
                    return $this->db->hasAny($this->table, "$this->primaryKey = '{$this->id}'");
                }
            }
            else
            {
                $fields = join(',', $fields);
                $values = join(',', $values);

                $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$values})";

                if($this->db->query($sql))
                {
                    $this->id = $this->db->lastInsertId($this->table, $this->primaryKey);
                    if(!empty($joined))
                    {
                        if(!$this->id > 0 && isset($newID))
                        {
                            $this->id = $newID;
                        }
                        $this->_saveMulti($joined, $this->id);
                    }
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
    function _saveMulti ($joined, $id)
    {
        foreach ($joined as $x => $y)
        {
            foreach ($y as $association => $value)
            {
                $joinTable[] = $this->{$association.'_jointable'};
                $mainKey = $this->{$association.'_foreignkey'};
                $keys[] = $mainKey;
                $keys[] = $this->{$association.'_associationforeignkey'};
                $fields[] = join(',', $keys);
                unset($keys);

                foreach ($value as $update)
                {
                    if(!empty($update))
                    {
                        $values[] = (ini_get('magic_quotes_gpc') == 1) ? $this->db->prepare(stripslashes($id)) : $this->db->prepare($id);
                        $values[] = (ini_get('magic_quotes_gpc') == 1) ? $this->db->prepare(stripslashes($update)) : $this->db->prepare($update);
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
            $this->db->query("DELETE FROM {$joinTable[$count]} WHERE $mainKey = '{$id}'");
            if(!empty($newValue[$count]))
            {
                $this->db->query("INSERT INTO {$joinTable[$count]} ({$fields[$count]}) VALUES {$newValue[$count]}");
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
 * @param mixed $id Id of database record to delete
 * @return boolean True on success
 */
    function del ($id=null)
    {
        if ($id)
        {
            $this->id = $id;
        }
        if ($this->id && $this->db->query("DELETE FROM {$this->table} WHERE {$this->primaryKey} = '{$this->id}'"))
        {
            $this->id = false;
            return true;
        }
        else
        {
            return false;
        }
    }

/**
 * Returns true if a record with set id exists.
 *
 * @return boolean True if such a record exists
 */
    function exists ()
    {
        return $this->id? $this->db->hasAny($this->table, "$this->primaryKey = '{$this->id}'"): false;
    }

/**
 * Returns true if a record that meets given conditions exists
 *
 * @return boolean True if such a record exists
 */
    function hasAny ($conditions = null)
    {
        return $this->findCount($conditions);
    }

/**
 * Return a single row as a resultset array.
 *
 * @param string $conditions SQL conditions
 * @param mixed $fields Either a single string of a field name, or an array of field names
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @return array Array of records
 */
    function find ($conditions = null, $fields = null, $order = null)
    {
        $data = Model::findAll($conditions, $fields, $order, 1);
        return empty($data[0])? false: $data[0];
    }

/** parses conditions array (or just passes it if it's a string)
 * @return string
 *
 */
    function parseConditions ($conditions)
    {
        if (is_string($conditions))
        {
            return $conditions;
        }
        elseif (is_array($conditions))
        {
            $out = array();
            foreach ($conditions as $key=>$value)
            {
                $slashedValue = (ini_get('magic_quotes_gpc') == 1) ? $this->db->prepare(stripslashes($value)) : $this->db->prepare($value);
                //Should remove the = below so LIKE and other compares can be used
                $out[] = "{$key}=".($value===null? 'null': $slashedValue);
            }
            return join(' and ', $out);
        }
        else
        {
            return null;
        }
    }

/**
 * Returns a resultset array with specified fields from database matching given conditions.
 *
 * @param mixed $conditions SQL conditions as a string or as an array('field'=>'value',...)
 * @param mixed $fields Either a single string of a field name, or an array of field names
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @param int $limit SQL LIMIT clause, for calculating items per page
 * @param int $page Page number
 * @return array Array of records
 */
    function findAll ($conditions = null, $fields = null, $order = null, $limit=50, $page=1)
    {
        $conditions = $this->parseConditions($conditions);
        $alias = null;
        if (is_array($fields))
        {
            $f = $fields;
        }
        elseif ($fields)
        {
            $f = array($fields);
        }
        else
        {
            $f = array('*');
        }

        $joins = $whers = array();

        if(!empty($this->_oneToOne))
        {
            foreach ($this->_oneToOne as $rule)
            {
                list($association, $model, $value) = $rule;
                if(!empty($this->{$association.'_foreignkey'}))
                {
                    if($this->name == $this->{$model}->name and $this->name == $association)
                    {
                        $alias = 'Child_'.$association;
                    }
                    else
                    {
                        $alias = $association;
                    }
                    $oneToOneConditions = $this->parseConditions($this->{$association.'_conditions'});
                    $oneToOneOrder = $this->{$association.'_order'};

                    $joins[] = "LEFT JOIN {$this->{$model}->table} AS `$alias` ON
                                `$alias`.{$this->{$association.'_foreignkey'}} = {$this->name}.$this->primaryKey"
                                .($oneToOneConditions? " WHERE {$oneToOneConditions}":null)
                                .($oneToOneOrder? " ORDER BY {$oneToOneOrder}": null);
                }
            }
        }

        if(!empty($this->_belongsToOther))
        {
            foreach ($this->_belongsToOther as $rule)
            {
                list($association, $model, $value) = $rule;
                if(!empty($this->{$association.'_foreignkey'}))
                {
                    if($this->name == $this->{$model}->name and $this->name == $association)
                    {
                        $alias = 'Child_'.$association;
                    }
                    else
                    {
                        $alias = $association;
                    }
                    $belongsToOtherConditions = $this->parseConditions($this->{$association.'_conditions'});
                    $belongsToOtherOrder = $this->{$association.'_order'};

                    $joins[] = "LEFT JOIN {$this->{$model}->table} AS `$alias` ON {$this->name}.{$this->{$association.'_foreignkey'}} = `$alias`.{$this->{$model}->primaryKey}"
                                .($belongsToOtherConditions? " WHERE {$belongsToOtherConditions}":null)
                                .($belongsToOtherOrder? " ORDER BY {$belongsToOtherOrder}": null);
                }
            }
        }

        $joins = count($joins)? join(' ', $joins): null;
        $whers = count($whers)? '('.join(' AND ', $whers).')': null;
        $conditions .= ($conditions && $whers? ' AND ': null).$whers;

        $offset = $page > 1? ($page-1) * $limit: 0;

        $limit_str = $limit
            ? $this->db->selectLimit($limit, $offset)
            : '';

        $sql = "SELECT " .join(', ', $f)
                ." FROM {$this->table} AS `{$this->name}` {$joins}"
                .($conditions? " WHERE {$conditions}":null)
                .($order? " ORDER BY {$order}": null)
                .$limit_str;

        $data = $this->db->all($sql);

        if(!empty($this->_oneToMany))
        {
            $newValue = $this->_findOneToMany($data);
            if(!empty($newValue))
            {
                $data = $newValue;
            }
        }

        if(!empty($this->_manyToMany))
        {
            $newValue = $this->_findManyToMany($data);
            if(!empty($newValue))
            {
                $data = $newValue;
            }
        }

         return $data;
    }

/**
 * Enter description here...
 *
 * @param unknown_type $data
 * @return unknown
 */
   function _findOneToMany(&$data)
   {
       $datacheck = $data;
       $original = $data;
       foreach ($this->_oneToMany as $rule)
       {
           $count = 0;
           list($association, $model, $value) = $rule;
           foreach ($datacheck as $key => $value1)
           {
               foreach ($value1 as $key2 => $value2)
               {
                   if($key2 === $this->name)
                   {
                       if($this->{$association.'_findersql'})
                       {
                           $tmpSQL = $this->{$association.'_findersql'};
                       }
                       else
                       {
                           $oneToManyConditions = $this->parseConditions($this->{$association.'_conditions'});
                           $oneToManyOrder = $this->{$association.'_order'};

                           $tmpSQL = "SELECT {$this->{$association.'_fields'}} FROM {$this->{$model}->table} AS `{$association}`
                                      WHERE ({$this->{$association.'_foreignkey'}})  = '{$value2[$this->primaryKey]}'"
                                      .($oneToManyConditions? " {$oneToManyConditions}":null)
                                      .($oneToManyOrder? " ORDER BY {$oneToManyOrder}": null);
                       }

                       $oneToManySelect[$association] = $this->db->all($tmpSQL);

                       if( !empty($oneToManySelect[$association]) && is_array($oneToManySelect[$association]))
                       {
                           $newKey = $association;
                           foreach ($oneToManySelect[$association] as $key => $value)
                           {
                               $oneToManySelect1[$newKey][$key] = $value[$newKey];
                           }
                           $merged = array_merge_recursive($data[$count],$oneToManySelect1);
                           $newdata[$count] = $merged;
                           unset( $oneToManySelect[$association], $oneToManySelect1);
                       }
                       if(!empty($newdata[$count]))
                       {
                           $original[$count] = $newdata[$count];
                       }
                   }
               }
               $count++;
           }
           if(empty($newValue) && !empty($original))
           {
               for ($i = 0; $i< count($original); $i++)
               {
                   $newValue[$i] = $original[$i];
               }
           }
           elseif(!empty($original))
           {
               for ($i = 0; $i< count($original); $i++)
               {
                   $newValue[$i]  = array_merge($newValue[$i], $original[$i]);
               }
           }
       }
       if(!empty($newValue))
       {
           return $newValue;
       }
   }

/**
 * Enter description here...
 *
 * @param unknown_type $data
 * @return unknown
 */
   function _findManyToMany(&$data)
   {
       $datacheck = $data;
       $original = $data;
       foreach ($this->_manyToMany as $rule)
       {
           $count = 0;
           list($association, $model, $value) = $rule;
           foreach ($datacheck as $key => $value1)
           {
               foreach ($value1 as $key2 => $value2)
               {
                   if($key2 === $this->name)
                   {
                       if( 0 == strncmp($key2, $this->{$association.'_foreignkey'}, $key2) )
                       {
                           if(!empty ($value2[$this->primaryKey]))
                           {
                               if($this->{$association.'_findersql'})
                               {
                                   $tmpSQL = $this->{$association.'_findersql'};
                               }
                               else
                               {
                                   $manyToManyConditions = $this->parseConditions($this->{$association.'_conditions'});
                                   $manyToManyOrder = $this->{$association.'_order'};

                                   $tmpSQL = "SELECT {$this->{$association.'_fields'}} FROM {$this->{$model}->table} AS `{$association}`
                                                JOIN {$this->{$association.'_jointable'}}
                                                  ON {$this->{$association.'_jointable'}}.
                                                     {$this->{$association.'_foreignkey'}} = '{$value2[$this->primaryKey]}'
                                                 AND {$this->{$association.'_jointable'}}.
                                                     {$this->{$association.'_associationforeignkey'}} = `{$association}` .{$this->{$model}->primaryKey}"
                                                    .($manyToManyConditions? " WHERE {$manyToManyConditions}":null)
                                                    .($manyToManyOrder? " ORDER BY {$manyToManyOrder}": null);

                               }

                               $manyToManySelect[$association] = $this->db->all($tmpSQL);
                           }
                           if( !empty($manyToManySelect[$association]) && is_array($manyToManySelect[$association]))
                           {
                               $newKey = $association;
                               foreach ($manyToManySelect[$association] as $key => $value)
                               {
                                   $manyToManySelect1[$newKey][$key] = $value[$newKey];
                               }
                               $merged = array_merge_recursive($data[$count],$manyToManySelect1);
                               $newdata[$count] = $merged;
                               unset( $manyToManySelect[$association], $manyToManySelect1 );
                           }
                           if(!empty($newdata[$count]))
                           {
                               $original[$count] = $newdata[$count];
                           }
                       }
                   }
               }
               $count++;
           }
           if(empty($newValue) && !empty($original))
           {
               $originalCount = count($original);
               for ($i = 0; $i< $originalCount; $i++)
               {
                   $newValue[$i] = $original[$i];
               }
           }
           elseif(!empty($original))
           {
               $originalCount = count($original);
               for ($i = 0; $i< $originalCount; $i++)
               {
                   $newValue[$i]  = array_merge($newValue[$i], $original[$i]);
               }
           }
       }
       if(!empty($newValue))
       {
           return $newValue;
       }
   }

/**
 * Returns an array of all rows for given SQL statement.
 *
 * @param string $sql SQL query
 * @return array
 */
   function findBySql ($sql)
   {
       $data = $this->db->all($sql);
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
       else
       {
           return $data;
       }
   }

/**
 * Returns number of rows matching given SQL condition.
 *
 * @param string $conditions SQL conditions (WHERE clause conditions)
 * @return int Number of matching rows
 */
   function findCount ($conditions)
   {
      list($data) = Model::findAll($conditions, 'COUNT(*) AS count');
      return isset($data[0]['count'])? $data[0]['count']: false;
   }

/**
 * Enter description here...
 *
 * @param string $conditions SQL conditions (WHERE clause conditions)
 * @param unknown_type $fields
 * @return unknown
 */
   function findAllThreaded ($conditions=null, $fields=null, $sort=null)
   {
      return $this->_doThread(Model::findAll($conditions, $fields, $sort), null);
   }

/**
 * Enter description here...
 *
 * @param unknown_type $data
 * @param unknown_type $root NULL or id for root node of operation
 * @return array
 * @access private
 */
   function _doThread ($data, $root)
   {
      $out = array();

      for ($ii=0; $ii<sizeof($data); $ii++)
      {
         if ($data[$ii]['parent_id'] == $root)
         {
            $tmp = $data[$ii];
            $tmp['children'] = isset($data[$ii]['id'])? $this->_doThread($data, $data[$ii]['id']): null;
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
 * @param unknown_type $field
 * @param unknown_type $value
 * @return array Array with keys "prev" and "next" that holds the id's
 */
    function findNeighbours ($conditions, $field, $value)
    {
        @list($prev) = Model::findAll($conditions." AND {$field} < '{$value}'", $field, "{$field} DESC", 1);
        @list($next) = Model::findAll($conditions." AND {$field} > '{$value}'", $field, "{$field} ASC", 1);

        $prev = isset($prev) ? $prev: false;
        $next = isset($next) ? $next: false;
        return array('prev'=>$prev, 'next'=>$next);
    }

/**
 * Returns a resultset for given SQL statement.
 *
 * @param string $sql SQL statement
 * @return array Resultset
 */
   function query ($sql)
   {
      return $this->db->query($sql);
   }

/**
 * Returns true if all fields pass validation.
 *
 * @param array $data POST data
 * @return boolean True if there are no errors
 */
   function validates ($data=null)
   {
      $errors = count($this->invalidFields($data? $data: $this->data));

      return $errors == 0;
   }

/**
 * Returns an array of invalid fields.
 *
 * @param array $data Posted data
 * @return array Array of invalid fields
 */
   function invalidFields ($data=null)
   {
      return $this->_invalidFields($data);
   }

/**
 * Returns an array of invalid fields.
 *
 * @param array $data
 * @return array Array of invalid fields
 * @access private
 */
   function _invalidFields ($data=null)
   {
      if (!isset($this->validate))
      {
         return true;
      }

      if (is_array($this->validationErrors))
      {
         return $this->validationErrors;
      }

      $data = ($data? $data: (isset($this->data)? $this->data: array()));
      $errors = array();
      foreach ($data as $table => $field)
      {
         foreach ($this->validate as $field_name=>$validator)
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
 * This function determines whether or not a string is a foreign key
 *
 * @param string $field Returns true if the input string ends in "_id"
 * @return True if the field is a foreign key listed in the belongsTo array.
 */
	function isForeignKey( $field )
	{
	    $foreignKeys = array();

	   if(!empty($this->_belongsToOther))
      {

        foreach ($this->_belongsToOther as $rule)
        {
            list($association, $model, $value) = $rule;
            $foreignKeys[$this->{$association.'_foreignkey'}] = $this->{$association.'_foreignkey'};
        }
      }

	   if( array_key_exists($field, $foreignKeys) )
	   {
	     return true;
	   }
	   return false;
	}

/**
 * Enter description here...
 *
 * @return unknown
 */
	function getDisplayField()
	{
	   //  $displayField defaults to 'name'
	   $dispField = 'name';

	   //  If the $displayField variable is set in this model, use it.
	   if( isset( $this->displayField ) ) {
	      $dispField = $this->displayField;
	   }

	   //  And if the display field does not exist in the table info structure, use the ID field.
	   if( false == $this->hasField( $dispField ) )
	     $dispField = $this->primaryKey;

	   return $dispField;
	}

/**
 * Enter description here...
 *
 * @return unknown
 */
	function getLastInsertID()
	{
     return $this->db->lastInsertId($this->table, $this->primaryKey);
	}
}

?>