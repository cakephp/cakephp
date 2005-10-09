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
 * @subpackage   cake.cake.libs.model
 * @since        CakePHP v 0.2.9
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
   var $useTable = false;

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
 * Table metadata
 *
 * @var array
 * @access private
 */
   var $_tableInfo = null;
	
/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $_belongsTo = array();
   
/**
 * Array of other Models this Model references in a belongsTo (one-to-one) relationship. 
 *
 * @var array
 * @access private
 */
   var $_belongsToOther = array();

/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $_hasOne = array();
   
/**
 * Array of other Models this Model references in a hasOne (one-to-one) relationship. 
 *
 * @var array
 * @access private
 */
   var $_oneToOne = array();

/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $_hasMany = array();
   
/**
 * Array of other Models this Model references in a hasMany (one-to-many) relationship. 
 *
 * @var array
 * @access private
 */
   var $_oneToMany = array();

/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $_hasAndBelongsToMany = array();
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
 * Enter description here...
 *
 * @var unknown_type
 */
	var $classRegistry;

/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $persistent = null;
   
/**
 * Prefix for tables in model.
 *
 * @var string
 */
   var $tablePrefix = null;
   
/**
 * Constructor. Binds the Model's database table to the object.
 *
 * @param unknown_type $id
 * @param string $table Database table to use.
 * @param unknown_type $db Database connection object.
 */
   function __construct ($id=false, $table=null, $db=null) 
   {

      if($db != null)
      {
          $this->db =& $db;
      }
      else
      {
         $dboFactory = DboFactory::getInstance();  
         $this->db =& $dboFactory ;
         if(empty($this->db))
         {
             $this->_throwMissingConnection();
         }
      }
          
      
      $this->classRegistry =& ClassRegistry::getInstance();
      $this->classRegistry->addObject(get_class($this), $this);
   
      if ($id)
      {
         $this->id = $id;
      }

      $tableName = $table? $table: ($this->useTable? $this->useTable: Inflector::tableize(get_class($this)));
      //Table Prefix Hack - Check to see if the function exists.
	  if (in_array('settableprefix', get_class_methods(get_class($this)))) $this->setTablePrefix();
      // Table Prefix Hack - Get the prefix for this view.
	  $this->tablePrefix? $this->useTable($this->tablePrefix.$tableName): $this->useTable ($tableName);
	  //$this->useTable ($tableName);
      parent::__construct();
      $this->createLinks();
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
            $classCreated = false;
            
            foreach ($associationValue as $option => $optionValue)
            {
               if (($option === 'className') && ($classCreated === false))
               {
                  $className = $optionValue;
               }
               
               if ($classCreated === false)
               {
                  $this->_constructAssociatedModels($className , 'Belongs');
                  $classCreated = true;
               }
               
               switch($option)
               {
                  case 'conditions':
                        $modelConditions = $this->table .'To'. $association . 'Conditions';
                        $conditions = $optionValue;
                        $this->$modelConditions = $conditions;
                  unset($modelConditions);
                  break;
                  
                  case 'order':
                        $modelOrder = $this->table .'To'. $association . 'Order';
                        $order = $optionValue;
                        $this->$modelOrder = $order;
                  unset($modelOrder);
                  break;
                  
                  case 'foreignKey':
                  $modelForeignKey = $this->table .'To'. $association . 'ForeignKey';
                  $foreignKey = $optionValue;
                  $this->$modelForeignKey = $foreignKey;
                  unset($modelForeignKey);
                  break;
                  
                  case 'counterCache':
                  $modelCounterCache= $this->table .'To'. $association . 'counterCache';
                  $counterCache = $optionValue;
                  $this->$modelCounterCache = $counterCache;
                  unset($modelCounterCache);
                  break; 
               }
            }
            $className = Inflector::singularize($className);
            $this->linkAssociation('Belongs', $this->$className->table, $this->id);
            $this->relink('Belongs');
            unset($className);
         }
      }
      else
      {
          $association = explode(',', $this->belongsTo);
          foreach ($association as $modelName) 
          {
              $this->_constructAssociatedModels($modelName , 'Belongs');
              $modelName = Inflector::singularize($modelName);
              $this->linkAssociation('Belongs', $this->$modelName->table, $this->id);
              $this->relink('Belongs');
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
            $classCreated = false;
            
            foreach ($associationValue as $option => $optionValue)
            {
               if (($option === 'className') && ($classCreated === false))
               {
                  $className = $optionValue;
               }
               
               if ($classCreated === false)
               {
                  $this->_constructAssociatedModels($className , 'One');
                  $className = $this->$className->table;
                  $classCreated = true;
               }
               
               switch($option)
               {
                  case 'conditions':
                        $modelConditions = $this->table .'To'. $association . 'Conditions';
                        $conditions = $optionValue;
                        $this->$modelConditions = $conditions;
                  unset($modelConditions);
                  break;
                  
                  case 'order':
                        $modelOrder = $this->table .'To'. $association . 'Order';
                        $order = $optionValue;
                        $this->$modelOrder = $order;
                  unset($modelOrder);
                  break;
                  
                  case 'dependent':
                        $modelDependent = $this->table .'To'. $association . 'Dependent';
                        $dependent = $optionValue;
                        $this->$modelDependent = $dependent;
                  unset($modelDependent);
                  break;
                  
                  case 'foreignKey':
                  $modelForeignKey = $this->table .'To'. $association . 'ForeignKey';
                  $foreignKey = $optionValue;
                  $this->$modelForeignKey = $foreignKey;
                  unset($modelForeignKey);
                  break;
               }
            }
            $className = Inflector::singularize($className);
            $this->linkAssociation('One', $this->$className->table, $this->id);
            $this->relink('One');
            unset($className);
         }
      }
      else
      {
          $association = explode(',', $this->hasOne);
          foreach ($association as $modelName) 
          {
              $this->_constructAssociatedModels($modelName , 'One');
              $modelName = Inflector::singularize($modelName);
              $this->linkAssociation('One', $this->$modelName->table, $this->id);
              $this->relink('One');
          }
      }
   }

	
/**
 * Enter description here...
 *
 */
	function _hasManyLinks()
	{
	   if(is_array($this->hasMany))
	   {
	      foreach ($this->hasMany as $association => $associationValue)
	      {
            $className = $association;
            $classCreated = false;

	         foreach ($associationValue as $option => $optionValue)
	         {
	            if (($option === 'className') && ($classCreated === false))
	            {
                  $className = $optionValue;
               }
               
               if ($classCreated === false)
               {
                  $this->_constructAssociatedModels($className , 'Many');
                  $classCreated = true;
               }
	            switch ($option)
	            {
	               case 'conditions':
	                 //$this->__joinedHasMany[$count][$this->table]['conditions'] = $optionValue;
	               break;
	               
	               case 'order':
	                 //$this->__joinedHasMany[$count][$this->table]['order'] = $optionValue;
	               break;
	               
	               case 'foreignKey':
   	               $modelForeignKey = $this->table .'To'. $className . 'ForeignKey';
   	               $foreignKey = $optionValue;
   	               $this->$modelForeignKey = $foreignKey;
   	               unset($modelForeignKey);
	               break;
	               
	               case 'dependent':
	               //$this->__joinedHasMany[$count][$this->table]['dependent'] = $optionValue;
	               break;
	               
	               case 'exclusive':
	               //$this->__joinedHasMany[$count][$this->table]['exclusive'] = $optionValue;
	               break;
	               
	               case 'finderSql':
	               //$this->__joinedHasMany[$count][$this->table]['finderSql'] = $optionValue;
	               break;
	               
	               case 'counterSql':
	               //$this->__joinedHasMany[$count][$this->table]['counterSql'] = $optionValue;
	               break;
	            }
	         }
	         $className = Inflector::singularize($className);
	         $this->linkAssociation('Many', $this->$className->table, $this->id);
	         $this->relink('Many');
	         unset($className);
	      }
	   }
	   else
	   {
	       $association = explode(',', $this->hasMany);
	       foreach ($association as $modelName) 
	       {
	           $this->_constructAssociatedModels($modelName , 'Many');
	           $modelName = Inflector::singularize($modelName);
	           $this->linkAssociation('Many', $this->$modelName->table, $this->id);
	           $this->relink('Many');  
	       }
	   }
	}

/**
 * Enter description here...
 *
 */
   function _hasAndBelongsToManyLinks()
   {
      if(is_array($this->hasAndBelongsToMany))
      {
      }
      else
      {
         //$this->_hasAndBelongsToMany = explode(',', $this->hasAndBelongsToMany);
         
	       $association = explode(',', $this->hasAndBelongsToMany);
	       foreach ($association as $modelName) 
	       {
	           $this->_constructAssociatedModels($modelName , 'ManyTo');
	           $modelName = Inflector::singularize($modelName);
	           $this->linkAssociation('ManyTo', $this->$modelName->table, $this->id);
	           $this->relink('ManyTo');  
	       }
      }
   }

/**
 * Enter description here...
 *
 * @param unknown_type $className
 * @param unknown_type $type
 * @param unknown_type $settings
 * @access private
 */
   function _constructAssociatedModels($modelName, $type, $settings = false)
   {
      $modelName = Inflector::singularize($modelName);
      $collectionKey = Inflector::underscore($modelName);
      
      switch($type)
      {
         case 'Belongs':
         $joined = 'joinedBelongsTo';
         break;

         case 'One':
         $joined = 'joinedHasOne';
         break;

         case 'Many':
         $joined = 'joinedHasMany';
         break;

         case 'ManyTo':
         $joined = 'joinedHasAndBelongs';
         break;

         default:
         //nothing
         break;
      }
      
      if(!$this->classRegistry->isKeySet($collectionKey))
      {
          $this->{$modelName} =& new $modelName();
      }
      else
      {
          $this->{$modelName} =& $this->classRegistry->getObject($collectionKey); 
      }
      
      $this->{$joined}[] =& $this->$modelName;
   }

/**
 * Updates this model's association links, by emptying the links list, and then link"*Association Type" again.
 *
 * @param unknown_type $type
 */
	function relink ($type) 
	{
	    if(!empty($this->_belongsTo))
	    {
	        foreach ($this->_belongsTo as $table) 
	        {
	            if(is_array($table))
	            {
	                $names[0] = $table[0];
	            } 
	            else 
	            {
	                $names[0] = $table;
	            }
	            $tableName = Inflector::singularize($names[0]);
	            $this->clearLinks($type);
	            $this->linkAssociation($type, $tableName, $this->id);
	        }
	    }
	    
	    if(!empty($this->_hasOne))
	    {
	        foreach ($this->_hasOne as $table) 
	        {
	            if(is_array($table))
	            {
	                $names[0] = $table[0];
	            } 
	            else 
	            {
	                $names[0] = $table;
	            }
	            $tableName = Inflector::singularize($names[0]);
	            $this->clearLinks($type);
	            $this->linkAssociation($type, $tableName, $this->id);
	        }
	    }
	    
	    if(!empty($this->_hasMany))
	    {
	        foreach ($this->_hasMany as $table)
	        {
	            if(is_array($table))
	            {
	                $names[0] = $table[0];
	            }
	            else
	            {
	                $names[0] = $table;
	            }
	              $tableName = Inflector::singularize($names[0]);
	              $this->clearLinks($type);
	              $this->linkAssociation($type, $tableName, $this->id);
	        }
	    }
	    
	    if(!empty($this->_hasAndBelongsToMany))
	    {
	        foreach ($this->_hasAndBelongsToMany as $table)
	        {
	            if(is_array($table))
	            {
	                 $names[0] = $table[0];
	            }
	            else
                  {
                     $names[0] = $table;
                  }
                  $tableName = Inflector::singularize($names[0]);
                  $this->clearLinks($type);
                  $this->linkAssociation($type, $tableName, $this->id);
	        }
	    }
	}
	   

/**
 * Enter description here...
 *
 * @param unknown_type $type
 * @param unknown_type $tableName
 * @param unknown_type $value
 */
   function linkAssociation ($type, $tableName, $value=null) 
   {
      $tableName = Inflector::tableize($tableName);
      $fieldKey = $this->table .'To'. Inflector::singularize($tableName) . 'ForeignKey';

      if(!empty($this->$fieldKey))
      {
         $field_name = $this->$fieldKey;
      }
      else
      {
         if ($type === 'Belongs')
         {
            $field_name = Inflector::singularize($tableName).'_id';
         }
         elseif ($type === 'One')
         {
            $field_name = Inflector::singularize($this->table).'_id';
         }
         else
         {
            $field_name = Inflector::singularize($this->table).'_id';
         }
      }

      
      
      switch ($type)
      {
         case 'Belongs':
         $this->_belongsToOther[] = array($tableName, $field_name, $value);
         break;

         case 'One':
         $this->_oneToOne[] = array($tableName, $field_name, $value);
         break;

         case 'Many':
         $this->_oneToMany[] = array($tableName, $field_name, $value);
         break;

         case 'ManyTo':
         
         //$joinKey = $this->table .'To'. Inflector::singularize($tableName) . 'joinTable';
         //if(!empty($this->$joinKey))
         //{
         //     $joinTable = $this->$joinKey;
         //}
         //else
         //{
             $tableSort[0] = $this->table;
             $tableSort[1] = $tableName;
             sort($tableSort);
             $joinTable = $tableSort[0] . '_' . $tableSort[1];
             $key1 = Inflector::singularize($this->table) . '_id';
             $key2 = Inflector::singularize($tableName) . '_id';
        // }
         $this->_manyToMany[]  = array($tableName, $field_name, $value, $joinTable, $key1, $key2);
         break;
      }
   }
   
/**
 * Removes all oassociation links to other Models.
 *
 */
   function clearLinks($type)
   {
      //switch ($type)
      //{
       //  case 'Belongs':
         $this->_belongsToOther = array();
       //  break;

       //  case 'One':
         $this->_oneToOne = array();
       //  break;

       //  case 'Many':
         $this->_oneToMany = array();
       //  break;

       //  case 'ManyTo':
         $this->_manyToMany = array();
       //  break;
     // }
   }


/**
 * Sets a custom table for your controller class. Used by your controller to select a database table.
 *
 * @param string $tableName Name of the custom table
 */
   function useTable ($tableName) 
   {
      if (!in_array(strtolower($tableName), $this->db->tables())) 
      {
         $this->_throwMissingTable($tableName);
         die();
      }
      else
      {
         $this->table = $tableName;
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
/*
         if (!$this->hasField($n)) 
         {
            DEBUG? 
               trigger_error(sprintf(ERROR_NO_FIELD_IN_MODEL_DB, $n, $this->table), E_USER_ERROR):
               trigger_error('Application error occured, trying to set a field name that doesn\'t exist.', E_USER_WARNING);
         }
*/
         foreach ($v as $x => $y)
         {
             if($x == 'id')
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
      
      if(!empty($this->_belongsToOther))
      {
         $this->relink('Belongs');
      }
      
      if(!empty($this->_oneToOne))
      {
         $this->relink('One');
      }
      
      if(!empty($this->_oneToMany))
      {
         $this->relink('Many');
      }
      
      if(!empty($this->_manyToMany))
      {
         $this->relink('ManyTo');
      }
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
      return $this->id? $this->find("$this->table.id = '{$this->id[0]}'", $fields): false;
      }
      else
      {
      return $this->id? $this->find("$this->table.id = '{$this->id}'", $fields): false;
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
      if ($conditions) 
      {
         $conditions = $this->parseConditions($conditions);
         $data = $this->find($conditions, $name, $order);
         return $data[$name];
      }
      elseif (isset($this->data[$name]))
      {
         return $this->data[$name];
      }
      else 
      {
         if ($this->id && $data = $this->read($name)) 
         {
            return isset($data[$name])? $data[$name]: false;
         }
         else 
         {
            return false;
         }
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
      return Model::save(array($this->table=>array($name=>$value)), false);
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
                 $values[] = (ini_get('magic_quotes_gpc') == 1) ? $this->db->prepare(stripslashes($y)) : $this->db->prepare($y); 
                 if($x == 'id' && !is_numeric($y))
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
            
            $sql = "UPDATE {$this->table} SET ".join(',', $sql)." WHERE id = '{$this->id}'";

            if ($this->db->query($sql)) // && $this->db->lastAffected())
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
               return $this->db->hasAny($this->table, "id = '{$this->id}'");
            }
         }
         else 
         {

            $fields = join(',', $fields);
            $values = join(',', $values);

            $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$values})";

            if($this->db->query($sql)) 
            {
               $this->id = $this->db->lastInsertId($this->table, 'id');
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
           foreach ($y as $name => $value)
           {
               $tableSort[0] = $this->table;
               $tableSort[1] = $name;
               sort($tableSort);
               $joinTable[] = $tableSort[0] . '_' . $tableSort[1];
               $mainKey = Inflector::singularize($this->table) . '_id';
               
               $keys[] = $mainKey;
               $keys[] = Inflector::singularize($name) . '_id';
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
       
       for ($count = 0; $count < count($joinTable); $count++) 
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
      if ($this->id && $this->db->query("DELETE FROM {$this->table} WHERE id = '{$this->id}'")) 
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
      return $this->id? $this->db->hasAny($this->table, "id = '{$this->id}'"): false;
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
               list($table, $field, $value) = $rule;
               $joins[] = "LEFT JOIN {$table} ON {$table}.{$field} = {$this->table}.id";
           }
       }
       
       if(!empty($this->_belongsToOther))
       {

           foreach ($this->_belongsToOther as $rule)
           {
               list($table, $field, $value) = $rule;
               $joins[] = "LEFT JOIN {$table} ON {$this->table}.{$field} = {$table}.id";
           }
       }
       
      $joins = count($joins)? join(' ', $joins): null;
      $whers = count($whers)? '('.join(' AND ', $whers).')': null;
      $conditions .= ($conditions && $whers? ' AND ': null).$whers;
      
      $offset = $page > 1? ($page-1) * $limit: 0;

      $limit_str = $limit
         ? $this->db->selectLimit($limit, $offset)
         : '';
      
      $sql = 
         "SELECT "
         .join(', ', $f)
         ." FROM {$this->table} {$joins}"
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
           list($table, $field, $value) = $rule;
           foreach ($datacheck as $key => $value1)
           {
               foreach ($value1 as $key2 => $value2)
               {
                   if($key2 === Inflector::singularize($this->table))
                   {
                       $oneToManySelect[$table] = $this->db->all("SELECT * FROM {$table} 
                                                  WHERE ($field)  = '{$value2['id']}'");
                       if( !empty($oneToManySelect[$table]) && is_array($oneToManySelect[$table]))
                       {
                           $newKey = Inflector::singularize($table);
                           foreach ($oneToManySelect[$table] as $key => $value)
                           {
                               $oneToManySelect1[$table][$key] = $value[$newKey];
                           }
                           $merged = array_merge_recursive($data[$count],$oneToManySelect1);
                           $newdata[$count] = $merged;
                           unset( $oneToManySelect[$table], $oneToManySelect1);
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
           $this->joinedHasMany[] = new NeatArray($this->db->fields($table));
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
           list($table, $field, $value, $joineTable, $joinKey1, $JoinKey2) = $rule;
           
           foreach ($datacheck as $key => $value1)
           {
               foreach ($value1 as $key2 => $value2)
               {
                   if($key2 === Inflector::singularize($this->table))
                   {
                       if( 0 == strncmp($key2, $joinKey1, strlen($key2)) )
                       {
                           if(!empty ($value2['id']))
                           {
                               $tmpSQL = "SELECT * FROM {$table}
                                          JOIN {$joineTable} ON {$joineTable}.{$joinKey1} = '$value2[id]' 
                                          AND {$joineTable}.{$JoinKey2} = {$table} .id";
                               
                               $manyToManySelect[$table] = $this->db->all($tmpSQL);
                           }
                           if( !empty($manyToManySelect[$table]) && is_array($manyToManySelect[$table]))
                           {
                               $newKey = Inflector::singularize($table);
                               foreach ($manyToManySelect[$table] as $key => $value)
                               {
                                   $manyToManySelect1[$table][$key] = $value[$newKey];
                               }
                               $merged = array_merge_recursive($data[$count],$manyToManySelect1);
                               $newdata[$count] = $merged;
                               unset( $manyToManySelect[$table], $manyToManySelect1 );
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
           if(empty($newValue2) && !empty($original))
           {
               for ($i = 0; $i< count($original); $i++) 
               {
                   $newValue2[$i] = $original[$i];
               }
               if(count($this->_manyToMany < 2))
               {
                   $newValue = $newValue2;
               }
           }
           elseif(!empty($original))
           {
               for ($i = 0; $i< count($original); $i++) 
               {
                   $newValue[$i]  = array_merge($newValue2[$i], $original[$i]);
               }
           }
           $this->joinedHasAndBelongs[] = new NeatArray($this->db->fields($table));
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
      return $this->db->all($sql);
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
      list($prev) = Model::findAll($conditions." AND {$field} < '{$value}'", $field, "{$field} DESC", 1);
      list($next) = Model::findAll($conditions." AND {$field} > '{$value}'", $field, "{$field} ASC", 1);
      
      return array('prev'=>$prev['id'], 'next'=>$next['id']);
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
      foreach ($this->data as $table => $field)
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
            list($table, $key, $value) = $rule;
            $foreignKeys[$key] = $key;
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
	     $dispField = 'id';

	   return $dispField;
	}

/**
 * Enter description here...
 *
 * @return unknown
 */
	function getLastInsertID()
	{
     return $this->db->lastInsertId($this->table, 'id');
	}
	
/**
 * Enter description here...
 *
 * @param unknown_type $tableName
 */
	function _throwMissingTable($tableName)
	{
	    $error =& new AppController();
        $error->missingTable = get_class($this);
        call_user_func_array(array(&$error, 'missingTable'), $tableName);
        exit;
	}
	
/**
 * Enter description here...
 *
 */
	function _throwMissingConnection()
	{
	    $error =& new AppController();
        $error->missingConnection = get_class($this);
        call_user_func_array(array(&$error, 'missingConnection'), null);
        exit;
	}
}

?>