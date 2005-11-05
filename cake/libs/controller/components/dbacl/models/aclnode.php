<?php

require_once(CAKE . 'app_model.php');

class AclNode extends AppModel
{	

   var $useTable = false;
   function __construct()
   {
      parent::__construct();
      $this->__setTable();
   }

   function create($link_id = 0, $parent_id = null, $alias = '') 
   {
      parent::create();

      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(ERROR_ABSTRACT_CONSTRUCTION, E_USER_ERROR);
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

   function getPath($id)
   {
      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(ERROR_ABSTRACT_CONSTRUCTION, E_USER_ERROR);
         return NULL;
      }
      extract($this->__dataVars());

      $item = $this->find($this->_resolveID($id, $secondary_id));
      return $this->findAll("lft <= {$item[$class]['lft']} and rght >= {$item[$class]['rght']}");
   }

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

   function _resolveID($id, $fKey)
   {
      $key = (is_string($id) ? 'alias' : $fKey);
      $val = (is_string($id) ? '"' . addslashes($id) . '"' : $id);
      return "{$key} = {$val}";
   }

   function _syncTable($table, $dir, $lft, $rght)
   {
      $shift = ($dir == 2 ? 1 : 2);
      $this->db->query("UPDATE $table SET rght = rght " . ($dir > 0 ? "+" : "-") . " {$shift} WHERE rght > " . $rght);
      $this->db->query("UPDATE $table SET lft  = lft  " . ($dir > 0 ? "+" : "-") . " {$shift} WHERE lft  > " . $lft);
   }

   function __dataVars()
   {
      $vars = array();
      $class = strtolower(get_class($this));
      $vars['secondary_id'] = ($class == 'aro' ? 'user_id' : 'object_id');
      $vars['data_name']    = $class;
      $vars['table_name']   = $class . 's';
      $vars['class']        = ucwords($class);
      return $vars;
   }

   function __setTable()
   {
      $this->table = strtolower(get_class($this)) . "s";
   }
}

?>