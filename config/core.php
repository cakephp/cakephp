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
 * This is core configuration file. Use it to configure core behaviour of
 * Cake.
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

/**
 * Set debug level here:
 * - 0: production
 * - 1: development
 * - 2: full debug with sql
 */
define ('DEBUG', 0);

/**
 * Page cacheing setting.
 */
define ('CACHE_PAGES', false);

/**
 * Cache lifetime in seconds, 0 for debugging, -1 for eternity.
 */
define ('CACHE_PAGES_FOR', -1);

/**
 * Set any extra debug options here.
 */
if (DEBUG)
{
    error_reporting(E_ALL);
    ini_set('error_reporting', E_ALL);
    $TIME_START = getmicrotime();
}

?>
