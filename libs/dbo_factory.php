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
  * Purpose: DbFactory
  * 
  * Description:
  * Creates DBO-descendant objects from a given db connection configuration
  *
  * @filesource 
  * @author Cake Authors/Developers
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  */

/**
  * Enter description here...
  *
  */
uses('object');
config('database');

/**
  * Enter description here...
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  *
  */
class DboFactory extends Object {

/**
 * Enter description here...
 *
 * @param unknown_type $activeConfig
 * @return unknown
 */
	function make ($activeConfig) {
		if (!class_exists('DATABASE_CONFIG')) return false;

		$config = DATABASE_CONFIG::$activeConfig();

		// special case for AdoDB -- driver name in the form of 'adodb_drivername'
		if (preg_match('#^adodb_(.*)$#i', $config['driver'], $res)) {
			uses('DBO_AdoDB');
			$config['driver'] = $res[1];
			$conn = new DBO_AdoDB($config);
			return $conn;
		}
		// regular, Cake-native db drivers
		else {
			$db_driver_class = 'DBO_'.$config['driver'];
			$db_driver_fn = LIBS.strtolower($db_driver_class.'.php');

			if (file_exists($db_driver_fn)) {
				uses(strtolower($db_driver_class));
				return new $db_driver_class ($config);
			}
			else {
				trigger_error (ERROR_UNKNOWN_DATABASE_DRIVER, E_USER_ERROR);
				return false;
			}
		}
	}
}

?>