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
  * Purpose: Error Messages Defines
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
define ('ERROR_NO_CONTROLLER_SET', '[Dispatcher] No default controller, can\'t continue, check routes config');

/**
  * Enter description here...
  *
  */
define ('ERROR_UNKNOWN_CONTROLLER', '[Dispatcher] Specified controller "%s" doesn\'t exist, create it first');

/**
  * Enter description here...
  *
  */
define ('ERROR_NO_ACTION', '[Dispatcher] Action "%s" is not defined in the "%s" controller, create it first');

/**
  * Enter description here...
  *
  */
define ('ERROR_IN_VIEW', '[Controller] Error in view "%s", got: <blockquote>%s</blockquote>');

/**
  * Enter description here...
  *
  */
define ('ERROR_NO_VIEW', '[Controller] No template file for view "%s" (expected "%s"), create it first');

/**
  * Enter description here...
  *
  */
define ('ERROR_IN_LAYOUT', '[Controller] Error in layout "%s", got: <blockquote>"%s"</blockquote>');

/**
  * Enter description here...
  *
  */
define ('ERROR_NO_LAYOUT', '[Controller] Couln\'t find layout "%s" (expected "%s"), create it first');

/**
  * Enter description here...
  *
  */
define ('ERROR_NO_TABLE_LIST', '[Database] Couldn\'t get table list, check database config');

/**
  * Enter description here...
  *
  */
define ('ERROR_NO_MODEL_TABLE', '[Model] No DB table for model "%s" (expected "%s"), create it first');

/**
  * Enter description here...
  *
  */
define ('ERROR_NO_FIELD_IN_MODEL_DB', '[Model::set()] Field "%s" is not present in table "%s", check database schema');

/**
  * Enter description here...
  *
  */
define ('SHORT_ERROR_MESSAGE', '<div class="error_message"><i>%s</i></div>');

/**
  * Enter description here...
  *
  */
define ('ERROR_CANT_GET_ORIGINAL_IMAGE', '[Image] Couln\'t load original image %s (tried from "%s")');

/**
  * Enter description here...
  *
  */
define ('ERROR_404', "The requested URL /%s was not found on this server (%s).");

/**
  * Enter description here...
  *
  */
define ('ERROR_500', "Application error, sorry.");

?>