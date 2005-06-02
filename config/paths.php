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
 * If the index.php file is used instead of an .htaccess file
 * or if the user can not set the web root to use the public
 * directory we will define ROOT there, otherwise we set it
 * here.
 */
if( !defined('ROOT') ){
	define ('ROOT',   '../');
}

/**
 * Path to the application's directory.
 */
define ('APP',         ROOT.'app'.DS);

/**
 * Path to the application's models directory.
 */
define ('MODELS',          APP.'models'.DS);

/**
 * Path to the application's controllers directory.
 */
define ('CONTROLLERS',     APP.'controllers'.DS);

/**
 * Path to the application's helpers directory.
 */
define ('HELPERS',         APP.'helpers'.DS);

/**
 * Path to the application's views directory.
 */
define ('VIEWS',      	   APP.'views'.DS);

/**
 * Path to the application's view's layouts directory.
 */
define ('LAYOUTS',             APP.'views'.DS.'layouts'.DS);

/**
 * Path to the application's view's elements directory.
 * It's supposed to hold pieces of PHP/HTML that are used on multiple pages
 * and are not linked to a particular layout (like polls, footers and so on).
 */
define ('ELEMENTS',            APP.'views'.DS.'elements'.DS);

/**
 * Path to the configuration files directory.
 */
define ('CONFIGS',     ROOT.'config'.DS);

/**
 * Path to the libs directory.
 */
define ('LIBS',   	  ROOT.'libs'.DS);

/**
 * Path to the logs directory.
 */
define ('LOGS',   	  ROOT.'logs'.DS);

/**
 * Path to the modules directory.
 */
define ('MODULES',     ROOT.'modules'.DS);

/**
 * Path to the public directory.
 */
define ('PUBLIC',   	  ROOT.'public'.DS);

/**
 * Path to the tests directory.
 */
define ('TESTS',   	  ROOT.'tests'.DS);

/**
 * Path to the vendors directory.
 */
define ('VENDORS',     ROOT.'vendors'.DS);

/**
 * Path to the controller test directory.
 */
define ('CONTROLLER_TESTS',TESTS.'app'.DS.'controllers'.DS);

/**
 * Path to the helpers test directory.
 */
define ('HELPER_TESTS',   	TESTS.'app'.DS.'helpers'.DS);

/**
 * Path to the models' test directory.
 */
define ('MODEL_TESTS',   	TESTS.'app'.DS.'models'.DS);

/**
 * Path to the lib test directory.
 */
define ('LIB_TESTS',   	   TESTS.'libs'.DS);

?>
