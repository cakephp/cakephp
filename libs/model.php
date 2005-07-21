<?php 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 * Purpose: Model
 * DBO-backed object data model, loosely based on RoR concepts (www.rubyonrails.com).
 * Automatically selects a database table name based on a pluralized lowercase object class name
 * (i.e. class 'User' => table 'users'; class 'Man' => table 'men')
 * The table is required to have at least 'id auto_increment', 'created datetime', 
 * and 'modified datetime' fields
 *
 * To do:
 *   - schema-related cross-table ($has_one, $has_many, $belongs_to)
 *
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Enter description here...
 */
uses('object', 'validators', 'inflector');

/**
 * DBO-backed object data model, loosely based on RoR concepts (www.rubyonrails.com).
 * Automatically selects a database table name based on a pluralized lowercase object class name
 * (i.e. class 'User' => table 'users'; class 'Man' => table 'men')
 * The table is required to have at least 'id auto_increment', 'created datetime', 
 * and 'modified datetime' fields.
 *
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.2.9
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
   var $use_table = false;

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
   var $_table_info = null;
   
/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
	var $_hasOne = array();

/**
  * Enter description here...
  *
  * @var unknown_type
  * @access private
  */
	var $_belongsTo = array();
	
	
/**
 * Array of other Models this Model references in a one-to-many relationship. 
 *
 * @var array
 * @access private
 */
   var $_oneToMany = array();

/**
 * Array of other Models this Model references in a one-to-one relationship. 
 *
 * @var array
 * @access private
 */
   var $_oneToOne = array();

/**
 * Array of other Models this Model references in a has-many relationship. 
 *
 * @var array
 * @access private
 */
   var $_hasMany = array();

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
  * 
  * @var unknown_type
  * @access private
  */
	var $_count;

/**
 * Constructor. Binds the Model's database table to the object.
 *
 * @param unknown_type $id
 * @param string $table Database table to use.
 * @param unknown_type $db Database connection object.
 */
   function __construct ($id=false, $table=null, $db=null) 
   {
      $this->db = $db? $db: DboFactory::getInstance();
   
      if ($id)
      {
         $this->id = $id;
      }

      $table_name = $table? $table: ($this->use_table? $this->use_table: Inflector::tableize(get_class($this)));
      $this->useTable ($table_name);
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
	      return $this->_belongsToLink();
	   }
	   if (!empty($this->hasOne))
	   {
	      $this->_hasOneLink();
	   }
	   if (!empty($this->hasMany))
	   {
	      return $this->_hasManyLinks();
	   }
	   if (!empty($this->hasAndBelongsToMany))
	   {
	      return $this->_hasAndBelongsToManyLinks();
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
         if (count($this->id) > 1)
         {
            $this->_count++;
         }
         else
         {
            $this->_count = false;	
         }
         
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
                  $this->$className = &new $className();	
                  $classCreated = true;
                  $this->_belongsTo = array($association,$className);
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
            
            $this->_constructAssociatedModels($className , 'Belongs');
            unset($className);
            
            if (!count($this->id) > 1)
            {
               $this->_resetCount();
            }
         }
      }
      else
      {
         $this->_resetCount();
         if (count($this->id) > 1)
         {
            $this->_count++;
         }
         else
         {
            $this->_count = false;	
         }
				
				$association = explode(',', $this->belongsTo);
				foreach ($association as $modelName) 
				{
					$this->_constructAssociatedModels($modelName , 'Belongs');
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
         if (count($this->id) > 1)
         {
            $this->_count++;
         }
         else
         {
            $this->_count = false;	
         }
         
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
                  $this->$className = new $className();	
                  $classCreated = true;
                  $this->_hasOne = array($association,$className);
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
            
            $this->_constructAssociatedModels($className , 'One');
            unset($className);
            
            if (!count($this->id) > 1)
            {
               $this->_resetCount();
            }
         }
      }
      else
      {
         $this->_resetCount();
         if (count($this->id) > 1)
         {
            $this->_count++;
         }
         else
         {
            $this->_count = false;	
         }
				
				$association = explode(',', $this->hasOne);
				foreach ($association as $modelName) 
				{
					$this->_constructAssociatedModels($modelName , 'One');
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
	      $this->_resetCount();
	      
	      foreach ($this->hasMany as $association => $associationValue)
	      {
	         $className = $association;
	         $this->_hasMany = array($association,$className);
	         
	         foreach ($associationValue as $option => $optionValue)
	         {
	            switch ($option)
	            {
	               case 'className':
	                 //$this->__joinedHasMany[$count][$this->table]['className'] = $optionValue;
	                 //$this->__joinedHasMany[$count][$this->table]['association'] = $association;
	               break;
	               
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
	         $this->linkAssociation('Many', $className, $this->id[$this->_count]);
	      }
	   }
	   else
      {
         $this->_resetCount();
         if (count($this->id) > 1)
         {
            $this->_count++;
         }
         else
         {
            $this->_count = false;	
         }
				
				$association = explode(',', $this->hasMany);
				foreach ($association as $modelName) 
				{
					$this->linkAssociation('Many', $modelName, $this->id[$this->_count]);;
				}
			}
	}

   function _hasAndBelongsToManyLinks()
   {
      if(is_array($this->hasAndBelongsToMany))
      {
      }
      else
      {
         $this->_hasAndBelongsToMany = explode(',', $this->hasAndBelongsToMany);
      }
   }
/**
 * Enter description here...
 *
 * @return unknown
 */
	function _resetCount()
	{
	   return $this->_count = 0;
	}
	
/**
 * Enter description here...
 *
 * @param unknown_type $className
 * @param unknown_type $type
 * @param unknown_type $settings
 * @access private
 */
   function _constructAssociatedModels($className, $type, $settings = false)
   {
      $modelName = Inflector::singularize($className);
      
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
      $this->linkAssociation($type, $modelName, $this->id[$this->_count]);
      
      if(!isset($this->$className))
      {
         $this->$className = new $className();
      }
      $this->{$joined}[] = $this->$className;
      $this->relink($type);
   }
   
   

/**
 * Updates this model's association links, by emptying the links list, and then link"*Association Type" again.
 *
 * @param unknown_type $type
 */
	function relink ($type) 
	{
	    switch ($type)
	    {
	       case 'Belongs':
	           foreach ($this->_belongsTo as $table) 
	           {
	              if(is_array($table))
	              {
	                 $names[] = explode(',', $table);
	              } 
	              else 
	              {
	                 $names[0] = $table;
	                 $names[1] = $table;
	              }
	              $className = $names[1];
	              $tableName = Inflector::singularize($names[0]);
	              $this->$className->clearLinks($type);
	              $this->$className->linkAssociation($type, $tableName, $this->id);
	           }
	       break;
	       
	       case 'One':
	           foreach ($this->_hasOne as $table) 
	           {
	              if(is_array($table))
	              {
	                 $names[] = explode(',', $table);
	              } 
	              else 
	              {
	                 $names[0] = $table;
	                 $names[1] = $table;
	              }
	              $className = $names[1];
	              $tableName = Inflector::singularize($names[0]);
	              $this->$className->clearLinks($type);
	              $this->$className->linkAssociation($type, $tableName, $this->id);
	           }
	       break;
	       
	       case 'Many':
	           foreach ($this->_hasMany as $table)
	           {
	              if(is_array($table))
	              {
	                 $names[] = explode(',', $table);
	              }
	              else
	              {
			         $names[0] = $table;
			         $names[1] = $table;
	              }
	              $className = $names[1];
	              $tableName = Inflector::singularize($names[0]);
	              $this->clearLinks($type);
	              $this->linkAssociation($type, $tableName, $this->id[0]);
	           }
	       break;

          case 'ManyTo':
               foreach ($this->_manyToMany as $table)
               {
                  if(is_array($table))
                  {
                     $names[] = explode(',', $table);
                  }
                  else
                  {
                     $names[0] = $table;
                     $names[1] = $table;
                  }
                  $className = $names[1];
                  $tableName = Inflector::singularize($names[0]);
                  $this->clearLinks($type);
                  $this->linkAssociation($type, $tableName, $this->id[0]);
               }
	       break;
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
         if ($type === 'Belongs' || $type === 'One')
         {
            $field_name = Inflector::singularize($tableName).'_id';
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
         $this->_manyToMany = array();
         break;
      }
   }
   
/**
 * Removes all oassociation links to other Models.
 *
 */
   function clearLinks($type)
   {
      switch ($type)
      {
         case 'Belongs':
         $this->_belongsToOther = array();
         break;

         case 'One':
         $this->_oneToOne = array();
         break;

         case 'Many':
         $this->_oneToMany = array();
         break;

         case 'ManyTo':
         $this->_manyToMany = array();
         break;
      }
   }


/**
 * Sets a custom table for your controller class. Used by your controller to select a database table.
 *
 * @param string $table_name Name of the custom table
 */
   function useTable ($table_name) 
   {
      if (!in_array(strtolower($table_name), $this->db->tables())) 
      {
         trigger_error (sprintf(ERROR_NO_MODEL_TABLE, get_class($this), $table_name), E_USER_ERROR);
         die();
      }
      else
      {
         $this->table = $table_name;
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
      $data = is_array($one)? $one: array($one=>$two);

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
         //$n == 'id'? $this->setId($v): $this->data[$n] = $v;

         foreach ($v as $x => $y)
         {
         			//$x == 'id'? $this->id = $y: $this->data[$n][$x] = $y;
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
      if (empty($this->_table_info)) 
      {
         $this->_table_info = new NeatArray($this->db->fields($this->table));
      }
      return $this->_table_info;
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
      if (empty($this->_table_info))
      { 
      	$this->loadInfo();
      }
      return $this->_table_info->findIn('name', $name);
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
      //return $this->id? $this->find("id = '{$this->id}'", $fields): false;
      return $this->id? $this->find("$this->table.id = '{$this->id[0]}'", $fields): false;

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
      return $this->save(array($name=>$value), false);
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
   
      foreach ($this->data as $n=>$v)
      {
         foreach ($v as $x => $y)
         {
      
         	if ($this->hasField($x)) 
         	{
            	$fields[] = $x;
            	$values[] = $this->db->prepare($y);
         	}
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
            
            if ($this->db->query($sql) && $this->db->lastAffected())
            {
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
      $data = $this->findAll($conditions, $fields, $order, 1);
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
            $out[] = "{$key}=".($value===null? 'null': $this->db->prepare($value));
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
            $joins[] = "LEFT JOIN {$table} ON {$this->table}.{$field} = {$table}.id";
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

      $offset = $page > 1? $page*$limit: 0;

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
         $datacheck = $data;
         foreach ($this->_oneToMany as $rule)
         {
            $count = 0;
            list($table, $field, $value) = $rule;
            foreach ($datacheck as $key => $value1)
            {
               foreach ($value1 as $key2 => $value2)
               {
                  $select = $this->db->all("SELECT * FROM {$table} WHERE ($field)  = {$value2['id']}");
                  $data2 = array_merge_recursive($data[$count],$select);
                  $data1[$count] = $data2;
               }
               $count++;
            }
            $data = $data1;
            $this->joinedHasMany[] = new NeatArray($this->db->fields($table));
         }
      }
      return $data;
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
      list($data) = $this->findAll($conditions, 'COUNT(*) AS count');
      return isset($data['count'])? $data['count']: false;
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
      return $this->_doThread($this->findAll($conditions, $fields, $sort), null);
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
      list($prev) = $this->findAll($conditions." AND {$field} < '{$value}'", $field, "{$field} DESC", 1);
      list($next) = $this->findAll($conditions." AND {$field} > '{$value}'", $field, "{$field} ASC", 1);
      
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
            if (!isset($data[$table][$field_name]) || !preg_match($validator, $data[$table][$field_name]))
            {
            	$errors[$field_name] = 1;
            }
         }
         $this->validationErrors = $errors;
         return $errors;
      }
   }

}

?>