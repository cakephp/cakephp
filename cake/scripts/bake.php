#!/usr/local/bin/php
<?php
/* SVN FILE: $Id$ */

/**
 * Bake startup script
 *
 * Invokes the Bake class with given parameters.
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
 * @subpackage   cake.cake.scripts
 * @since        CakePHP v 0.2.9
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
  * START-UP
  */
define ('DS', DIRECTORY_SEPARATOR);
define ('ROOT', dirname(dirname(dirname(__FILE__))).DS);
define ('APP_DIR', 'app');

require (ROOT.'cake'.DS.'basics.php');
require (ROOT.'cake'.DS.'config'.DS.'paths.php');
uses ('bake');

$waste = array_shift($argv);
$product = array_shift($argv);

$bake = new Bake ($product, $argv);

?>