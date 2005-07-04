<?php
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake PHP : Rapid Development Framework <http://www.cakephp.org/> + //
// + Copyright: (c) 2005, CakePHP Authors/Developers                  + //
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
 * @author CakePHP Authors/Developers
 * @copyright Copyright (c) 2005, CakePHP Authors/Developers
 * @link https://trac.cakephp.org/wiki/Authors Authors/Developers
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
 * @since CakePHP v 1.0.0.0
 *
 */
class DboFactory extends Object
{
	/**
	 * A semi-singelton. Returns actual instance, or creates a new one with given config.
	 *
	 * @param string $config Name of key of $dbConfig array to be used.
	 * @return mixed
	 */
	function getInstance($config = null)
	{
		static $instance;

		if (!isset($instance))
		{
			if ($config == null)
			{
				return false;
			}

			$configs = get_class_vars('DATABASE_CONFIG');
			$config  = $configs[$config];

			// special case for AdoDB -- driver name in the form of 'adodb-drivername'
			if (preg_match('#^adodb[\-_](.*)$#i', $config['driver'], $res))
			{
				uses('dbo/dbo_adodb');
				$config['driver'] = $res[1];

				$instance = array(DBO_AdoDB($config));
			}
			// special case for PEAR:DB -- driver name in the form of 'pear-drivername'
			elseif (preg_match('#^pear[\-_](.*)$#i', $config['driver'], $res))
			{
				uses('dbo/dbo_pear');
				$config['driver'] = $res[1];

				$instance = array(new DBO_Pear($config));
			}
			// regular, Cake-native db drivers
			else
			{
				$db_driver_class = 'DBO_'.$config['driver'];
				$db_driver_fn = LIBS.strtolower('dbo'.DS.$db_driver_class.'.php');

				if (file_exists($db_driver_fn))
				{
					uses(strtolower('dbo'.DS.$db_driver_class));
					$instance = array(new $db_driver_class($config));
				}
				else
				{
					trigger_error(ERROR_UNKNOWN_DATABASE_DRIVER, E_USER_ERROR);
					return false;
				}
			}
		}

		return $instance[0];
	}

	/**
	 * Sets config to use. If there is already a connection, close it first.
	 *
	 * @param string $configName Name of the config array key to use.
	 * @return mixed
	 */
	function setConfig($config)
	{
		$db = DboFactory::getInstance();
		if ($db->isConnected() === true)
		{
			$db->close();
		}

		return $this->getInstance($config);
	}
}

?>