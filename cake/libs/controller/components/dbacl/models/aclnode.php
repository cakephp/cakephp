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
 * @subpackage   cake.cake.libs.controller.components.dbacl.models
 * @since        CakePHP v 0.10.0.1232
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
 * @subpackage cake.cake.libs.controller.components.dbacl.models
 * @since      CakePHP v 0.10.0.1232
 *
 */
class AclNode extends AppModel
{

/**
 * Enter description here...
 *
 */
   function __construct()
   {
      $this->setSource();
      parent::__construct();
   }

/**
 * Enter description here...
 *
 * @param unknown_type $link_id
 * @param unknown_type $parent_id
 * @param unknown_type $alias
 * @return unknown
 */
   function create($link_id = 0, $parent_id = null, $alias = '')
   {
      parent::create();

      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(__("[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration."), E_USER_ERROR);
         return NULL;
      }
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
         trigger_error(__("[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration."), E_USER_ERROR);
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
 * Enter description here...
 *
 * @param unknown_type $id
 * @return unknown
 */
   function getPath($id)
   {
      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(__("[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration."), E_USER_ERROR);
         return NULL;
      }
      extract($this->__dataVars());

      $item = $this->find($this->_resolveID($id, $secondary_id));
      if($item == null || count($item) == 0)
      {
         return null;
      }
      return $this->findAll("{$data_name}.lft <= {$item[$class]['lft']} and {$data_name}.rght >= {$item[$class]['rght']}");
   }

/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @return unknown
 */
   function getChildren($id)
   {
      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(__("[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration."), E_USER_ERROR);
         return NULL;
      }
      extract($this->__dataVars());

      $item = $this->find($this->_resolveID($id, $secondary_id));
      return $this->findAll("{$data_name}.lft > {$item[$class]['lft']} and {$data_name}.rght < {$item[$class]['rght']}");
   }

/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @param unknown_type $fKey
 * @return unknown
 */
   function _resolveID($id, $fKey)
   {
      $key = (is_string($id) ? 'alias' : $fKey);
      $val = (is_string($id) ? '"' . addslashes($id) . '"' : $id);
      return "{$key} = {$val}";
   }

/**
 * Enter description here...
 *
 * @param unknown_type $table
 * @param unknown_type $dir
 * @param unknown_type $lft
 * @param unknown_type $rght
 */
   function _syncTable($table, $dir, $lft, $rght)
   {
      pr('...Syncing...');
      $shift = ($dir == 2 ? 1 : 2);
      $this->db->query("UPDATE $table SET rght = rght " . ($dir > 0 ? "+" : "-") . " {$shift} WHERE rght > " . $rght);
      $this->db->query("UPDATE $table SET lft  = lft  " . ($dir > 0 ? "+" : "-") . " {$shift} WHERE lft  > " . $lft);
      pr('...Done Syncing...');
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
   function setSource()
   {
      $this->table = low(get_class($this)) . "s";
   }
}

?>