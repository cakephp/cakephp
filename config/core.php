<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id: index.php 109 2005-05-16 00:52:42Z phpnut $
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
  * Purpose: core.php
  * Enter description here...
  *
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @package cake
  * @subpackage cake.config
  * @since Cake v 0.2.9
  * @version $Revision: 115 $
  * @modifiedby $LastChangedBy: phpnut $
  * @lastmodified $Date: 2005-05-16 18:47:54 -0500 (Mon, 16 May 2005) $
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
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