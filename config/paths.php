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
  * In this file you set paths to different directories used by Cake.
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
 * If index.php file is used instead of an .htaccess file
 * or if user can not set the web root to use the public
 * directory we will define ROOT there otherwise we set it
 * here
 */
if( !defined('ROOT') ){
	define ('ROOT',	'../');
}

/**
 * Path to the application directory.
 */
define ('APP',			ROOT.'app/');

/**
 * Path to the application models directory.
 */
define ('MODELS',			APP.'models/');

/**
 * Path to the application controllers directory.
 */
define ('CONTROLLERS',	APP.'controllers/');

/**
 * Path to the application helpers directory.
 */
define ('HELPERS',		APP.'helpers/');

/**
 * Path to the application views directory.
 */
define ('VIEWS',			APP.'views/');

/**
 * Path to the configuration files directory.
 */
define ('CONFIGS',	ROOT.'config/');

/**
 * Path to the libs directory.
 */
define ('LIBS',		ROOT.'libs/');

/**
 * Path to the public directory.
 */
define ('PUBLIC',		ROOT.'public/');

/**
 * Path to the tests directory.
 */
define ('TESTS',		ROOT.'tests/');

/**
 * Path to the vendors directory.
 */
define ('VENDORS',	ROOT.'vendors/');

/**
 * Path to the controller test directory.
 */
define ('CONTROLLER_TESTS',TESTS.'app/controllers/');

/**
 * Path to the helpers test directory.
 */
define ('HELPER_TESTS',		TESTS.'app/helpers/');

/**
 * Path to the models test directory.
 */
define ('MODEL_TESTS',		TESTS.'app/models/');

/**
 * Path to the lib test directory.
 */
define ('LIB_TESTS',		TESTS.'libs/')

?>
