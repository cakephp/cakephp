<?PHP
/**
  * Purpose: DbFactory
  * 
  * Description:
  * Creates DBO-descendant objects from a given db connection configuration
  *
  * @filesource 
  * @author Michal Tatarynowicz <tatarynowicz@gmail.com>
  * @author Larry E. Masters aka PhpNut <nut@phpnut.com>
  * @author Kamil Dzielinski aka Brego <brego.dk@gmail.com>
  * @copyright Copyright (c) 2005, Cake Authors/Developers
  * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
  * @version $Revision: 113 $
  * @modifiedby $LastChangedBy: pies $
  * @lastmodified $Date: 2005-05-17 00:53:41 +0200 (Tue, 17 May 2005) $
  * @license http://www.opensource.org/licenses/mit-license.php The MIT License
  *
  */

/**
  * Enter description here...
  *
  */
uses('object');

/**
  * Enter description here...
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  *
  */
class DbFactory extends Object {

	function make ($config) {

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