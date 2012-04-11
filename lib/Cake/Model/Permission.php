<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AppModel', 'Model');

/**
 * Permissions linking AROs with ACOs
 *
 * @package       Cake.Model
 */
class Permission extends AppModel {

/**
 * Model name
 *
 * @var string
 */
	public $name = 'Permission';

/**
 * Explicitly disable in-memory query caching
 *
 * @var boolean
 */
	public $cacheQueries = false;

/**
 * Override default table name
 *
 * @var string
 */
	public $useTable = 'aros_acos';

/**
 * Permissions link AROs with ACOs
 *
 * @var array
 */
	public $belongsTo = array('Aro', 'Aco');

/**
 * No behaviors for this model
 *
 * @var array
 */
	public $actsAs = null;

/**
 * Constructor, used to tell this model to use the
 * database configured for ACL
 */
	public function __construct() {
		$config = Configure::read('Acl.database');
		if (!empty($config)) {
			$this->useDbConfig = $config;
		}
		parent::__construct();
	}

/**
 * Checks if the given $aro has access to action $action in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success (true if ARO has access to action in ACO, false otherwise)
 */
	public function check($aro, $aco, $action = "*") {
		if ($aro == null || $aco == null) {
			return false;
		}

		$permKeys = $this->getAcoKeys($this->schema());
		$aroPath = $this->Aro->node($aro);
		$acoPath = $this->Aco->node($aco);

		if (empty($aroPath) || empty($acoPath)) {
			trigger_error(__d('cake_dev', "DbAcl::check() - Failed ARO/ACO node lookup in permissions check.  Node references:\nAro: ") . print_r($aro, true) . "\nAco: " . print_r($aco, true), E_USER_WARNING);
			return false;
		}

		if ($acoPath == null || $acoPath == array()) {
			trigger_error(__d('cake_dev', "DbAcl::check() - Failed ACO node lookup in permissions check.  Node references:\nAro: ") . print_r($aro, true) . "\nAco: " . print_r($aco, true), E_USER_WARNING);
			return false;
		}

		if ($action != '*' && !in_array('_' . $action, $permKeys)) {
			trigger_error(__d('cake_dev', "ACO permissions key %s does not exist in DbAcl::check()", $action), E_USER_NOTICE);
			return false;
		}

		$inherited = array();
		$acoIDs = Hash::extract($acoPath, '{n}.' . $this->Aco->alias . '.id');

		$count = count($aroPath);
		for ($i = 0; $i < $count; $i++) {
			$permAlias = $this->alias;

			$perms = $this->find('all', array(
				'conditions' => array(
					"{$permAlias}.aro_id" => $aroPath[$i][$this->Aro->alias]['id'],
					"{$permAlias}.aco_id" => $acoIDs
				),
				'order' => array($this->Aco->alias . '.lft' => 'desc'),
				'recursive' => 0
			));

			if (empty($perms)) {
				continue;
			} else {
				$perms = Hash::extract($perms, '{n}.' . $this->alias);
				foreach ($perms as $perm) {
					if ($action == '*') {

						foreach ($permKeys as $key) {
							if (!empty($perm)) {
								if ($perm[$key] == -1) {
									return false;
								} elseif ($perm[$key] == 1) {
									$inherited[$key] = 1;
								}
							}
						}

						if (count($inherited) === count($permKeys)) {
							return true;
						}
					} else {
						switch ($perm['_' . $action]) {
							case -1:
								return false;
							case 0:
								continue;
							break;
							case 1:
								return true;
							break;
						}
					}
				}
			}
		}
		return false;
	}

/**
 * Allow $aro to have access to action $actions in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $actions Action (defaults to *)
 * @param integer $value Value to indicate access type (1 to give access, -1 to deny, 0 to inherit)
 * @return boolean Success
 */
	public function allow($aro, $aco, $actions = "*", $value = 1) {
		$perms = $this->getAclLink($aro, $aco);
		$permKeys = $this->getAcoKeys($this->schema());
		$save = array();

		if ($perms == false) {
			trigger_error(__d('cake_dev', 'DbAcl::allow() - Invalid node'), E_USER_WARNING);
			return false;
		}
		if (isset($perms[0])) {
			$save = $perms[0][$this->alias];
		}

		if ($actions == "*") {
			$save = array_combine($permKeys, array_pad(array(), count($permKeys), $value));
		} else {
			if (!is_array($actions)) {
				$actions = array('_' . $actions);
			}
			if (is_array($actions)) {
				foreach ($actions as $action) {
					if ($action{0} != '_') {
						$action = '_' . $action;
					}
					if (in_array($action, $permKeys)) {
						$save[$action] = $value;
					}
				}
			}
		}
		list($save['aro_id'], $save['aco_id']) = array($perms['aro'], $perms['aco']);

		if ($perms['link'] != null && !empty($perms['link'])) {
			$save['id'] = $perms['link'][0][$this->alias]['id'];
		} else {
			unset($save['id']);
			$this->id = null;
		}
		return ($this->save($save) !== false);
	}

/**
 * Get an array of access-control links between the given Aro and Aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @return array Indexed array with: 'aro', 'aco' and 'link'
 */
	public function getAclLink($aro, $aco) {
		$obj = array();
		$obj['Aro'] = $this->Aro->node($aro);
		$obj['Aco'] = $this->Aco->node($aco);

		if (empty($obj['Aro']) || empty($obj['Aco'])) {
			return false;
		}
		$aro = Hash::extract($obj, 'Aro.0.' . $this->Aro->alias . '.id');
		$aco = Hash::extract($obj, 'Aco.0.' . $this->Aco->alias . '.id');
		$aro = current($aro);
		$aco = current($aco);

		return array(
			'aro' => $aro,
			'aco' => $aco,
			'link' => $this->find('all', array('conditions' => array(
				$this->alias . '.aro_id' => $aro,
				$this->alias . '.aco_id' => $aco
			)))
		);
	}

/**
 * Get the crud type keys
 *
 * @param array $keys Permission schema
 * @return array permission keys
 */
	public function getAcoKeys($keys) {
		$newKeys = array();
		$keys = array_keys($keys);
		foreach ($keys as $key) {
			if (!in_array($key, array('id', 'aro_id', 'aco_id'))) {
				$newKeys[] = $key;
			}
		}
		return $newKeys;
	}
}
