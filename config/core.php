<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
//////////////////////////////////////////////////////////////////////////

/**
 * This is core configuration file. Use it to configure core behaviour of
 * Cake.
 * 
 * @package cake
 * @subpackage cake.config
 */

/**
 * Set debug level here:
 * - 0: production
 * - 1: development
 * - 2: full debug with sql
 */
define ('DEBUG', 1);

/**
 * Compress output CSS (removing comments, whitespace, repeating tags etc.)
 * This requires a /var/cache directory to be writable by the web server (caching).
 * To use, prefix the CSS link URL with '/ccss/' instead of '/css/' or use Controller::cssTag().
 */
define ('COMPRESS_CSS', false);

?>