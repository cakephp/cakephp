<?PHP 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <http://sputnik.pl/cake>                                    + //
// + Copyright: (c) 2005 Michal Tatarynowicz                          + //
// +                                                                  + //
// + Author(s): (c) 2005 Michal Tatarynowicz <tatarynowicz@gmail.com> + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under the Public Domain Licence                         + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Tort Validators
  * Used to validate data in Models.
  *
  * @filesource 
  * @modifiedby $LastChangedBy$  
  * @lastmodified $Date$
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @copyright Copyright (c) 2005, Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @license Public_Domain
  *
  */

/**
  * Enter description here...
  *
  */
define('VALID_NOT_EMPTY', '/.+/');

/**
  * Enter description here...
  *
  */
define('VALID_NUMBER', '/^[0-9]+$/');

/**
  * Enter description here...
  *
  */
define('VALID_EMAIL', '/^([a-z0-9][a-z0-9_\-\.\+]*)@([a-z0-9][a-z0-9\.\-]{0,63}\.[a-z]{2,3})$/i');

/**
  * Enter description here...
  *
  */
define('VALID_TAG', '/[a-z_]+/i');

/**
  * Enter description here...
  *
  */
define('VALID_TAGS', '/[a-z_ ]+/i');

?>