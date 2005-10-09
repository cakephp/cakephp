<?php
/* SVN FILE: $Id$ */

/**
 * Error Messages Defines
 * 
 * Long description for file
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
 * @subpackage   cake.cake.libs
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * Error string for when the specified database driver can not be found.
  */
define ('ERROR_UNKNOWN_DATABASE_DRIVER', '[DbFactory] Specified database driver (%s) not found');

/**
  * Error string for when the dispatcher can not find a default controller.
  */
define ('ERROR_NO_CONTROLLER_SET', '[Dispatcher] No default controller, can\'t continue, check routes config');

/**
  * Error string for when the dispatcher can not find a default action.
  */
define ('ERROR_NO_ACTION_SET', '[Dispatcher] No default action, can\'t continue, check routes config');

/**
  * Error string for when the dispatcher can not find given controller.
  */
define ('ERROR_UNKNOWN_CONTROLLER', '[Dispatcher] Specified controller "%s" doesn\'t exist, create it first');

/**
  * Error string for when the dispatcher can not find expected action in controller.
  */
define ('ERROR_NO_ACTION', '[Dispatcher] Action "%s" is not defined in the "%s" controller, create it first');

/**
  * Error string for errors in view.
  */
define ('ERROR_IN_VIEW', '[Controller] Error in view "%s", got: <blockquote>%s</blockquote>');

/**
  * Error string for when the controller can not find expected view.
  */
define ('ERROR_NO_VIEW', '[Controller] No template file for view "%s" (expected "%s"), create it first');

/**
  * Error string for errors in layout.
  */
define ('ERROR_IN_LAYOUT', '[Controller] Error in layout "%s", got: <blockquote>"%s"</blockquote>');

/**
  * Error string for when the controller can not find expected layout.
  */
define ('ERROR_NO_LAYOUT', '[Controller] Couln\'t find layout "%s" (expected "%s"), create it first');

/**
  * Error string for database not being able to access the table list.
  */
define ('ERROR_NO_TABLE_LIST', '[Database] Couldn\'t get table list, check database config');

/**
  * Error string for no corresponding database table found for model.
  */
define ('ERROR_NO_MODEL_TABLE', '[Model] No DB table for model "%s" (expected "%s"), create it first');

/**
  * Error string for Field not present in table. 
  */
define ('ERROR_NO_FIELD_IN_MODEL_DB', '[Model::set()] Field "%s" is not present in table "%s", check database schema');

/**
  * Error string short short error message.
  */
define ('SHORT_ERROR_MESSAGE', '<div class="error_message">%s</div>');

/**
  * Error string for when original image can not be loaded.
  */
define ('ERROR_CANT_GET_ORIGINAL_IMAGE', '[Image] Couldn\'t load original image %s (tried from "%s")');

/**
  * Error string for error 404.
  */
define ('ERROR_404', "The requested address /%s was not found on this server."); // second %s contains short error message

/**
  * Error string for error 500.
  */
define ('ERROR_500', "Application error, sorry.");

/**
  * Error string for attempted construction of an abstract class
  */
define ('ERROR_ABSTRACT_CONSTRUCTION', '[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration.');

?>