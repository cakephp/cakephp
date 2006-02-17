<?php
/* SVN FILE: $Id$ */

/**
 * This is core configuration file.
 *
 * Use it to configure core behaviour ofCake.
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
 * @subpackage   cake.cake.app.controllers.componenets.dbacl
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

uses('controller'.DS.'components'.DS.'acl_base');
uses('controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aclnode');
uses('controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aco');
uses('controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'acoaction');
uses('controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aro');
uses('controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aros_aco');

/**
 * In this file you can extend the AclBase.
 *
 * @package    cake
 * @subpackage cake.cake.app.controllers.components.dbacl
 */

class DB_ACL extends AclBase
{

/**
 * Enter description here...
 *
 */
   function __construct()
   {

   }

/**
 * Enter description here...
 *
 * @param unknown_type $aro
 * @param unknown_type $aco
 * @param unknown_type $action
 * @return unknown
 */
   function check($aro, $aco, $action = "*")
   {

      $Perms = new ArosAco();
      $Aro = new Aro();
      $Aco = new Aco();

      if($aro == null || $aco == null)
      {
         return false;
      }

      $permKeys = $this->_getAcoKeys($Perms->loadInfo());
      $aroPath = $Aro->getPath($aro);
      $tmpAcoPath = $Aco->getPath($aco);
      $acoPath = array();

      if($action != '*' && !in_array('_' . $action, $permKeys))
      {
         trigger_error('ACO permissions key "' . $action . '" does not exist in DB_ACL::check()', E_USER_ERROR);
      }

      foreach($tmpAcoPath as $a)
      {
         $acoPath[] = $a['Aco']['id'];
      }
      $acoPath = implode(", ", $acoPath);

      for($i = count($aroPath) - 1; $i >= 0; $i--)
      {
         $perms = $Perms->findBySql("select aros_acos.* from aros_acos left join acos on aros_acos.aco_id = acos.id where aros_acos.aro_id = " . $aroPath[$i]['Aro']['id'] . " and aros_acos.aco_id in ({$acoPath}) order by acos.lft asc");
         if($perms == null || count($perms) == 0)
         {
            continue;
         }
         else
         {
            foreach($perms as $perm)
            {
               if($action == '*')
               {
                  // ARO must be cleared for ALL ACO actions
                  foreach($permKeys as $key)
                  {
                     if(isset($perm['aros_acos']))
                     {
                         if($perm['aros_acos'][$key] != 1)
                         {
                            return false;
                         }
                     }
                  }
                  return true;
               }
               else
               {
                  switch($perm['aros_acos']['_' . $action])
                  {
                     case -1:
                        return false;
                     case 0:
                        continue;
                        break;
                     case 1:
                        return true;
                  }
               }
            }
         }
      }

      return false;
   }

/**
 * Allow
 *
 * @return boolean
 */
   function allow($aro, $aco, $action = "*", $value = 1)
   {
      $Perms = new ArosAco();
      $perms = $this->getAclLink($aro, $aco);
      $permKeys = $this->_getAcoKeys($Perms->loadInfo());
      $save = array();


      if($perms == false)
      {
         // One of the nodes does not exist
         return false;
      }

      if(isset($perms[0]))
      {
         $save = $perms[0]['aros_acos'];
      }

      if($action == "*")
      {
         $permKeys = $this->_getAcoKeys($Perms->loadInfo());
         foreach($permKeys as $key)
         {
            $save[$key] = $value;
         }
      }
      else
      {
         if(in_array('_' . $action, $permKeys))
         {
            $save['_' . $action] = $value;
         }
         else
         {
            // Raise an error
            return false;
         }
      }

      $save['aro_id'] = $perms['aro'];
      $save['aco_id'] = $perms['aco'];

      if($perms['link'] != null && count($perms['link']) > 0)
      {
         $save['id'] = $perms['link'][0]['aros_acos']['id'];
      }
      //return $Perms->save(array('ArosAco' => $save));

      if(isset($save['id']))
      {
         $q = 'update aros_acos set ';
         $saveKeys = array();
         foreach($save as $key => $val)
         {
            if($key != 'id')
            {
               $saveKeys[] = $key . ' = ' . $val;
            }
         }
         $q .= implode(', ', $saveKeys) . ' where id = ' . $save['id'];
      }
      else
      {
         $q = 'insert into aros_acos (' . implode(', ', array_keys($save)) . ') values (' . implode(', ', $save) . ')';
      }

      $Perms->db->query($q);
      return true;
   }

/**
 * Deny
 *
 * @return boolean
 */
   function deny($aro, $aco, $action = "*")
   {
      return $this->allow($aro, $aco, $action, -1);
   }

/**
 * Inherit
 *
 * @return boolean
 */
   function inherit($aro, $aco, $action = "*")
   {
      return $this->allow($aro, $aco, $action, 0);
   }

/**
 * Allow alias
 *
 * @return boolean
 */
   function grant($aro, $aco, $action = "*")
   {
      return $this->allow($aro, $aco, $action);
   }

/**
 * Deny alias
 *
 * @return boolean
 */
   function revoke($aro, $aco, $action = "*")
   {
      return $this->deny($aro, $aco, $action);
   }



/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @return unknown
 */
   function getAro($id = null)
   {
     if($id == null)
     {
        // Raise error
     }
     $aro = new Aro();
     $tmp = $aro->find(is_string($aro) ? "aros.alias = '" . addslashes($aro) . "'" : "aros.user_id   = {$aro}");
     $aro->setId($tmp['aro']['id']);
     return $aro;
   }


/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @return unknown
 */
   function getAco($id = null)
   {
     if($id == null)
     {
        // Raise error
     }
     $aco = new Aco();
     $tmp = $aco->find(is_string($aco) ? "acos.alias = '" . addslashes($aco) . "'" : "acos.user_id   = {$aco}");
     $aro->setId($tmp['aco']['id']);
     return $aco;
   }


/**
 * Enter description here...
 *
 * @param unknown_type $aro
 * @param unknown_type $aco
 * @return unknown
 */
   function getAclLink($aro, $aco)
   {
      $Aro = new Aro();
      $Aco = new Aco();

      $qAro = (is_string($aro) ? "alias = '" . addslashes($aro) . "'" : "user_id   = {$aro}");
      $qAco = (is_string($aco) ? "alias = '" . addslashes($aco) . "'" : "object_id = {$aco}");

      $obj = array();
      $obj['Aro'] = $Aro->find($qAro);
      $obj['Aco'] = $Aco->find($qAco);
      $obj['Aro'] = $obj['Aro']['Aro'];
      $obj['Aco'] = $obj['Aco']['Aco'];

      if($obj['Aro'] == null || count($obj['Aro']) == 0 || $obj['Aco'] == null || count($obj['Aco']) == 0)
      {
         return false;
      }

      return array(
         'aro'  => $obj['Aro']['id'],
         'aco'  => $obj['Aco']['id'],
         'link' => $Aro->findBySql("select * from aros_acos where aro_id = {$obj['Aro']['id']} and aco_id = {$obj['Aco']['id']}")
      );
   }

/**
 * Enter description here...
 *
 * @param unknown_type $keys
 * @return unknown
 */
   function _getAcoKeys($keys)
   {
      $newKeys = array();
      $keys = $keys->value;
      foreach($keys as $key)
      {
         if($key['name'] != 'id' && $key['name'] != 'aro_id' && $key['name'] != 'aco_id')
         {
            $newKeys[] = $key['name'];
         }
      }
      return $newKeys;
   }

}

?>