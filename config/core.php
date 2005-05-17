<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
  * Purpose: core.php
  * Enter description here...
  *
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.config
  * @since Cake v 0.2.9
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

// Debugging level
// 0: production, 1: development, 2: full debug with sql
/**
  * Enter description here...
  *
  */
define ('DEBUG', 0);

// Full-page caching
/**
  * Enter description here...
  *
  */
define ('CACHE_PAGES', false);
// Cache lifetime in seconds, 0 for debugging, -1 for eternity,
/**
  * Enter description here...
  *
  */
define ('CACHE_PAGES_FOR', -1);


/**
  * Advanced configuration
  */
// Debug options
if (DEBUG) {
	error_reporting(E_ALL);
	ini_set('error_reporting', E_ALL);
	$TIME_START = getmicrotime();
}

?>