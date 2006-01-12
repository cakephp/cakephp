<?php
/* SVN FILE: $Id$ */

/**
 * Access Control List abstract class.
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
 * @subpackage   cake.cake.libs.controller.components
 * @since        CakePHP v 0.10.0.1232
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Access Control List abstract class. Not to be instantiated.
 * Subclasses of this class are used by AclComponent to perform ACL checks in Cake.
 *
 * @package cake
 * @subpackage cake.cake.libs.controller.components
 * @since CakePHP v 0.10.0.1076
 *
 */
class AclBase
{

/**
 * Enter description here...
 *
 * @return AclBase
 */
   function AclBase()
   {
      //No instantiations or constructor calls (even statically)
      if (strcasecmp(get_class($this), "AclBase") == 0 || !is_subclass_of($this, "AclBase"))
      {
         trigger_error(__("[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration."), E_USER_ERROR);
         return NULL;
      }

   }

/**
 * Enter description here...
 *
 * @param unknown_type $aro
 * @param unknown_type $aco
 * @param unknown_type $action
 */
   function check($aro, $aco, $action = "*") {}

}

?>