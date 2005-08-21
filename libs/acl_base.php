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
 * Access Control List.
 * 
 * @filesource 
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.libs
 * @since CakePHP v 0.9.2
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 */

uses('error_messages');

/**
 * Access Control List abstract class. Not to be instantiated. 
 * Subclasses of this class are used by AclHelper to perform ACL checks in Cake.
 *
 * @package cake
 * @subpackage libs
 * @since CakePHP v 0.9.2
 *
 */
class AclBase
{
   
   function AclBase()
   {
      //No instantiations or constructor calls (even statically)
      if (strcasecmp(get_class($this), "AclBase") == 0 || !is_subclass_of($this, "AclBase"))
      {
         trigger_error(ERROR_ABSTRACT_CONSTRUCTION, E_USER_ERROR);
         return NULL;
      }
      
   }
   
   function check($aro, $aco) {}
   
}   
?>