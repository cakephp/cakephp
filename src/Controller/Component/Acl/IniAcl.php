<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller\Component\Acl;

use Cake\Configure\Engine\IniConfig;
use Cake\Controller\Component;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;

/**
 * IniAcl implements an access control system using an INI file. An example
 * of the ini file used can be found in /config/acl.ini.php.
 *
 */
class IniAcl implements AclInterface {

	use InstanceConfigTrait {
		config as protected _traitConfig;
	}

/**
 * The Hash::extract() path to the user/aro identifier in the
 * acl.ini file. This path will be used to extract the string
 * representation of a user used in the ini file.
 *
 * @var string
 */
	public $userPath = 'User.username';

/**
 * Default config for this class
 *
 * @var array
 */
	protected $_defaultConfig = [];

/**
 * read/write config
 *
 * Load acl config on first access, rather than on creation, so that there's no
 * needless overhead if the class is loaded but not referenced.
 * Wraps the InstanceConfigTrait method, taking care of the trait's implementation
 * of determining intent from the number ofpassed arguments.
 *
 * @param string|array|null $key The key to get/set, or a complete array of configs.
 * @param mixed|null $value The value to set.
 * @param bool $merge Whether to merge or overwrite existing config defaults to true.
 * @return mixed Config value being read, or the object itself on write operations.
 * @throws \Cake\Error\Exception When trying to set a key that is invalid.
 */
	public function config($key = null, $value = null, $merge = true) {
		if (!$this->_configInitialized) {
			$this->_defaultConfig = $this->readConfigFile(APP . 'Config/acl.ini.php');
		}

		if (is_array($key) || func_num_args() >= 2) {
			return $this->_traitConfig($key, $value, $merge);
		}

		return $this->_traitConfig($key);
	}

/**
 * Initialize method
 *
 * @param Component $component
 * @return void
 */
	public function initialize(Component $component) {
	}

/**
 * No op method, allow cannot be done with IniAcl
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return void
 */
	public function allow($aro, $aco, $action = "*") {
	}

/**
 * No op method, deny cannot be done with IniAcl
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return void
 */
	public function deny($aro, $aco, $action = "*") {
	}

/**
 * No op method, inherit cannot be done with IniAcl
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return void
 */
	public function inherit($aro, $aco, $action = "*") {
	}

/**
 * Main ACL check function. Checks to see if the ARO (access request object) has access to the
 * ACO (access control object).Looks at the acl.ini.php file for permissions
 * (see instructions in /config/acl.ini.php).
 *
 * @param string $aro ARO
 * @param string $aco ACO
 * @param string $action Action
 * @return bool Success
 */
	public function check($aro, $aco, $action = null) {
		$aclConfig = $this->config();

		if (is_array($aro)) {
			$aro = Hash::get($aro, $this->userPath);
		}

		if (isset($aclConfig[$aro]['deny'])) {
			$userDenies = $this->arrayTrim(explode(",", $aclConfig[$aro]['deny']));

			if (array_search($aco, $userDenies)) {
				return false;
			}
		}

		if (isset($aclConfig[$aro]['allow'])) {
			$userAllows = $this->arrayTrim(explode(",", $aclConfig[$aro]['allow']));

			if (array_search($aco, $userAllows)) {
				return true;
			}
		}

		if (isset($aclConfig[$aro]['groups'])) {
			$userGroups = $this->arrayTrim(explode(",", $aclConfig[$aro]['groups']));

			foreach ($userGroups as $group) {
				if (array_key_exists($group, $aclConfig)) {
					if (isset($aclConfig[$group]['deny'])) {
						$groupDenies = $this->arrayTrim(explode(",", $aclConfig[$group]['deny']));

						if (array_search($aco, $groupDenies)) {
							return false;
						}
					}

					if (isset($aclConfig[$group]['allow'])) {
						$groupAllows = $this->arrayTrim(explode(",", $aclConfig[$group]['allow']));

						if (array_search($aco, $groupAllows)) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}

/**
 * Parses an INI file and returns an array that reflects the
 * INI file's section structure. Double-quote friendly.
 *
 * @param string $filename File
 * @return array INI section structure
 */
	public function readConfigFile($filename) {
		$iniFile = new IniConfig(dirname($filename) . DS);
		return $iniFile->read(basename($filename));
	}

/**
 * Removes trailing spaces on all array elements (to prepare for searching)
 *
 * @param array $array Array to trim
 * @return array Trimmed array
 */
	public function arrayTrim($array) {
		foreach ($array as $key => $value) {
			$array[$key] = trim($value);
		}
		array_unshift($array, "");
		return $array;
	}

}
