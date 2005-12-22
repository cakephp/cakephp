<?php
/* SVN FILE: $Id$ */

/**
 * Access Control List factory class.
 * 
 * Permissions system.
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
 * @subpackage   cake.cake.libs.controller.components
 * @since        CakePHP v 0.10.0.1076
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Access Control List factory class.
 * 
 * Looks for ACL implementation class in core config, and returns an instance of that class.
 *
 * @package    cake
 * @subpackage cake.cake.libs.controller.components
 * @since      CakePHP v 0.10.0.1076
 *
 */
class AclComponent extends Object
{

   var $_instance  = null;
   var $controller = true;

/**
 * Enter description here...
 *
 */
   function __construct()
   {
      $this->getACL();
   }

/**
 * Static function used to gain an instance of the correct ACL class.
 *
 * @return MyACL
 */
   function &getACL() 
   {
      if($this->_instance == null)
      {
        uses('controller'.DS.'components'.DS.ACL_FILENAME);
        $classname = ACL_CLASSNAME;
        $this->_instance = new $classname;
      }
      return $this->_instance;
   }

/**
 * Enter description here...
 *
 */
   function _initACL()
   {
      
   }

/**
 * Pass-thru function for ACL check instance.
 *
 * @return boolean
 */
   function check($aro, $aco, $action = "*")
   {
      return $this->_instance->check($aro, $aco, $action);
   }


/**
 * Pass-thru function for ACL allow instance.
 *
 * @return boolean
 */
   function allow($aro, $aco, $action = "*")
   {
      return $this->_instance->allow($aro, $aco, $action);
   }

/**
 * Pass-thru function for ACL deny instance.
 *
 * @return boolean
 */
   function deny($aro, $aco, $action = "*")
   {
      return $this->_instance->deny($aro, $aco, $action);
   }

/**
 * Pass-thru function for ACL inherit instance.
 *
 * @return boolean
 */
   function inherit($aro, $aco, $action = "*")
   {
      return $this->_instance->inherit($aro, $aco, $action);
   }

/**
 * Pass-thru function for ACL grant instance.
 *
 * @return boolean
 */
   function grant($aro, $aco, $action = "*")
   {
      return $this->_instance->grant($aro, $aco, $action);
   }

/**
 * Pass-thru function for ACL grant instance.
 *
 * @return boolean
 */
   function revoke($aro, $aco, $action = "*")
   {
      return $this->_instance->revoke($aro, $aco, $action);
   }


/**
 * Pass-thru function for ACL getAro instance.
 *
 * @return Aro
 */
   function getAro($id)
   {
      return $this->_instance->getAro($id);
   }


/**
 * Pass-thru function for ACL getAco instance.
 *
 * @return Aco
 */
   function getAco($id)
   {
      return $this->_instance->getAco($id);
   }

}

?>
