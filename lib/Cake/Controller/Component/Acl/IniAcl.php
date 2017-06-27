<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Controller.Component.Acl
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AclInterface', 'Controller/Component/Acl');

/**
 * IniAcl implements an access control system using an INI file. An example
 * of the ini file used can be found in /config/acl.ini.php.
 *
 * @package       Cake.Controller.Component.Acl
 */
class IniAcl extends CakeObject implements AclInterface {

/**
 * Array with configuration, parsed from ini file
 *
 * @var array
 */
	public $config = null;

/**
 * The Hash::extract() path to the user/aro identifier in the
 * acl.ini file. This path will be used to extract the string
 * representation of a user used in the ini file.
 *
 * @var string
 */
	public $userPath = 'User.username';

/**
 * Initialize method
 *
 * @param Component $component The AclComponent instance.
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
 * @return bool Success
 */
	public function allow($aro, $aco, $action = "*") {
	}

/**
 * No op method, deny cannot be done with IniAcl
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success
 */
	public function deny($aro, $aco, $action = "*") {
	}

/**
 * No op method, inherit cannot be done with IniAcl
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return bool Success
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
		if (!$this->config) {
			$this->config = $this->readConfigFile(CONFIG . 'acl.ini.php');
		}
		$aclConfig = $this->config;

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
		App::uses('IniReader', 'Configure');
		$iniFile = new IniReader(dirname($filename) . DS);
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
