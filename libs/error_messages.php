<?PHP 
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005 Cake Authors/Developers                      + //
// +                                                                  + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +                                                                  + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + You may not use this file except in compliance with the License. + //
// +                                                                  + //
// + You may obtain a copy of the License at:                         + //
// + License page: http://www.opensource.org/licenses/mit-license.php + //
// +------------------------------------------------------------------+ //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: Error Messages Defines
  * 
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
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
define ('ERROR_404', "The requested address /%s was not found on this server."); // second %s contains short error message

/**
  * Enter description here...
  *
  */
define ('ERROR_500', "Application error, sorry.");

?>