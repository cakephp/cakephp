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
  * Purpose: Dispatch
  * The main "loop"
  * 
  * @filesource 
  * @modifiedby $LastChangedBy$  
  * @lastmodified $Date$
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @copyright Copyright (c) 2005, Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @package cake
  * @subpackage cake.public
  * @since Cake v 0.2.9
  * @version $Revision$
  * @license Public_Domain
  *
  */


## DIRECTORY LAYOUT
/**
  * Enter description here...
  *
  */
define ('ROOT',	'../');
/**
  * Enter description here...
  *
  */
define ('APP',			ROOT.'app/');
/**
  * Enter description here...
  *
  */
define ('MODELS',			APP.'models/');
/**
  * Enter description here...
  *
  */
define ('CONTROLLERS',	APP.'controllers/');
/**
  * Enter description here...
  *
  */
define ('VIEWS',			APP.'views/');
/**
  * Enter description here...
  *
  */
define ('CONFIGS',	ROOT.'config/');
/**
  * Enter description here...
  *
  */
define ('LIBS',		ROOT.'libs/');
/**
  * Enter description here...
  *
  */
define ('PUBLIC',		ROOT.'public/');

## STARTUP
/**
  * Enter description here...
  *
  */
require (LIBS.'basics.php');
uses ('dispatcher', 'dbo');
uses_config();
uses_database();
uses_tags();

## LOAD MODELS & CONTROLLERS
##
load_models ();
load_controllers ();

## START SESSION
##
session_start();

## RUN THE SCRIPT
##
$url = empty($_GET['url'])? null: $_GET['url'];
$DISPATCHER = new Dispatcher ();
$DISPATCHER->dispatch($url);

## PRINT TIMING
##

/**
  * Enter description here...
  *
  */
if (DEBUG) echo "<!-- ". round(getmicrotime() - $TIME_START, 2) ."s -->";

?>
