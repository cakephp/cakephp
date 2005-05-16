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
  * Enter description here...
  * 
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.scripts
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
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