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
  * Enter description here...
  * 
  * @filesource 
  * @modifiedby $LastChangedBy$  
  * @lastmodified $Date$
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @copyright Copyright (c) 2005, Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @package cake
  * @subpackage cake.scripts
  * @since Cake v 0.2.9
  * @version $Revision$
  * @license Public_Domain
  *
  */
## DIRECTORIES
##
/**
  * Enter description here...
  *
  */
define ('ROOT', '../');

/**
  * Enter description here...
  *
  */
define ('APP', ROOT.'app/');

/**
  * Enter description here...
  *
  */
define ('MODELS', APP.'models/');

/**
  * Enter description here...
  *
  */
define ('CONTROLLERS', APP.'controllers/');

/**
  * Enter description here...
  *
  */
define ('VIEWS', APP.'views/');

/**
  * Enter description here...
  *
  */
define ('CONFIGS', ROOT.'config/');

/**
  * Enter description here...
  *
  */
define ('LIBS', ROOT.'libs/');

/**
  * Enter description here...
  *
  */
define ('PUBLIC', ROOT.'public/');

## LOAD LIBRARIES
##
require (LIBS.'basics.php');
uses ('bake');
#load_libs ();

$script_name = array_shift($argv);
$action = array_shift($argv);

$bake = new Bake ($action, $argv);

?>