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
 * @subpackage   cake.cake.libs.controller.components.dbacl.models
 * @since        CakePHP v 0.10.0.1232
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Short description.
 */
require_once(CAKE . 'app_model.php');

/**
 * Short description for file.
 * 
 * Long description for file
 *
 * @package    cake
 * @subpackage cake.cake.libs.controller.components.dbacl.models
 * @since      CakePHP v 0.10.0.1232
 *
 */
class AclNode extends AppModel
{	

/**
 * Enter description here...
 *
 * @var unknown_type
 */
   var $useTable = false;
/**
 * Enter description here...
 *
 */
   function __construct($object = null, $parent = null)
   {
      parent::__construct();
      $this->__setTable();
      if($object != null)
      {
         $this->create($object, $parent);
      }
      exit();
   }

/**
 * Enter description here...
 *
 * @param unknown_type $object A new ACL object.  This can be a string for alias-based ACL, or a Model for object-based ACL
 * @param unknown_type $parent The parent object
 * @return unknown
 */
   function create($object = null, $parent = null) 
   {
      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(ERROR_ABSTRACT_CONSTRUCTION, E_USER_ERROR);
         return NULL;
      }
      parent::create();

      pr($this->__dataVars());
      exit();

      extract($this->__dataVars());

      if($parent_id == null || $parent_id === 0)
      {
         $parent = $this->find(null, "MAX(rght)");
         $parent['lft'] = $parent[0]['MAX(rght)'];

         if($parent[0]['MAX(rght)'] == null)
         {
            // The tree is empty
            $parent['lft'] = 0;
         }
      }
      else
      {
         $parent = $this->find($this->_resolveID($parent_id, $secondary_id));
         if($parent == null || count($parent) == 0)
         {
            trigger_error("Null parent in {$class}::create()", E_USER_ERROR);
         }

         $parent = $parent[$class];
         $this->_syncTable($table_name, 1, $parent['lft'], $parent['lft']);
      }

      $return = $this->save(array($class => array(
        $secondary_id => $link_id,
        'alias'       => $alias,
        'lft'         => $parent['lft'] + 1,
        'rght'        => $parent['lft'] + 2
      )));

      $this->setId($this->getLastInsertID());
      return $return;
   }


/**
 * Enter description here...
 *
 * @param unknown_type $parent_id
 * @param unknown_type $id
 * @return unknown
 */
   function setParent($parent_id = null, $id = null)
   {
      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(ERROR_ABSTRACT_CONSTRUCTION, E_USER_ERROR);
         return null;
      }
      extract($this->__dataVars());

      if($id == null && $this->id == false)
      {
         return false;
      }
      else if($id == null)
      {
         $id = $this->id;
      }

      $object = $this->find($this->_resolveID($id, $secondary_id));
      if($object == null || count($object) == 0)
      {
         // Couldn't find object
         return false;
      }
      $parent = $this->getParent(intval($object[$class][$secondary_id]));

      // Node is already at root, or new parent == old parent
      if(($parent == null && $parent_id == null) || ($parent_id == $parent[$class][$secondary_id]) || ($parent_id == $parent[$class]['alias']))
      {
         return false;
      }

      if($parent_id != null && $parent[$class]['lft'] <= $object[$class]['lft'] && $parent[$class]['rght'] >= $object[$class]['rght'])
      {
         // Can't move object inside self or own child
         return false;
      }
      $this->_syncTable($table_name, 0, $object[$class]['lft'], $object[$class]['lft']);

      if($parent_id == null)
      {
         $parent = $this->find(null, "MAX(rght)");
         $parent['lft'] = $parent[0]['MAX(rght)'];
      }
      else
      {
         $parent = $this->find($this->_resolveID($parent_id, $secondary_id));
         $parent = $parent[$class];
         $this->_syncTable($table_name, 1, $parent['lft'], $parent['lft']);
      }

      $object[$class]['lft']  = $parent['lft'] + 1;
      $object[$class]['rght'] = $parent['lft'] + 2;
      $this->save($object);

      if($parent['lft'] == 0)
      {
         $this->_syncTable($table_name, 2, $parent['lft'], $parent['lft']);
      }

   }


/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @return unknown
 */
   function getParent($id)
   {
      $path = $this->getPath($id);
      if($path == null || count($path) < 2)
      {
         return null;
      }
      else
      {
         return $path[count($path) - 2];
      }
   }

/**
 * The path to a node as an array, where the first element of the array is at the root of the tree, and the last element is the requested node
 *
 * @param mixed $id
 * @return array
 */
   function getPath($id)
   {
      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(ERROR_ABSTRACT_CONSTRUCTION, E_USER_ERROR);
         return NULL;
      }
      extract($this->__dataVars());

      $item = $this->find($this->_resolveID($id, $secondary_id));
      if($item == null || count($item) == 0)
      {
         return null;
      }
      return $this->findAll("lft <= {$item[$class]['lft']} and rght >= {$item[$class]['rght']}");
   }

/**
 * Gets the child nodes of a specified element
 *
 * @param mixed $id
 * @return array
 */
   function getChildren($id)
   {
      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(ERROR_ABSTRACT_CONSTRUCTION, E_USER_ERROR);
         return NULL;
      }
      extract($this->__dataVars());

      $item = $this->find($this->_resolveID($id, $secondary_id));
      return $this->findAll("lft > {$item[$class]['lft']} and rght < {$item[$class]['rght']}");
   }

/**
 * Gets a reference to a node object
 *
 * @param unknown_type $obj
 * @param unknown_type $fKey
 * @return unknown
 */
   function _resolveID($obj, $fKey)
   {
      extract($this->__dataVars());
      if(is_object($obj))
      {
         if(isset($obj->id) && isset($obj->name))
         {
            return "model = '{$obj->name}' and {$secondary_id} = {$obj->id}";
         }
         return null;
      }
      else if(is_array($obj))
      {
         $keys = array_keys($obj);
         $key1 = $keys[0];
         if(is_string($key1) && is_array($obj[$key1]) && isset($obj[$key1]['id']))
         {
            return "model = '{$key1}' and {$secondary_id} = {$obj[$key1]['id']}";
         }
         return null;
      }
      else if(is_string($obj))
      {
         $path = explode('/', $obj);
         
      }
      $key = (is_string($id) ? 'alias' : $fKey);
      $val = (is_string($id) ? '"' . addslashes($id) . '"' : $id);
      return "{$key} = {$val}";
   }

/**
 * Private method: modifies the left and right values of affected nodes in a tree when a node is added or removed
 *
 * @param string $table aros or acos, depending on the tree to be modified
 * @param int $dir The direction in which to shift the nodes
 * @param int $lft The left position of the node being added or removed
 * @param int $rght The right position of the node being added or removed
 */
   function _syncTable($table, $dir, $lft, $rght)
   {
      $shift = ($dir == 2 ? 1 : 2);
      $table = strtolower($table);
      $this->db->query("UPDATE {$table} SET rght = rght " . ($dir > 0 ? "+" : "-") . " {$shift} WHERE rght > " . $rght);
      $this->db->query("UPDATE {$table} SET lft  = lft  " . ($dir > 0 ? "+" : "-") . " {$shift} WHERE lft  > " . $lft);
   }

/**
 * Enter description here...
 *
 * @return unknown
 */
   function __dataVars()
   {
      $vars = array();
      $class = Inflector::camelize(strtolower(get_class($this)));
      $vars['secondary_id'] = (strtolower($class) == 'aro' ? 'user_id' : 'object_id');
      $vars['data_name']    = $class;
      $vars['table_name']   = $class . 's';
      $vars['class']        = Inflector::camelize($class);
      return $vars;
   }

/**
 * Enter description here...
 *
 */
   function __setTable()
   {
      $this->table = strtolower(get_class($this)) . "s";
   }
}

?>