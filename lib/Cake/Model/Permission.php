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
 * @package       Cake.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppModel', 'Model');

/**
 * Permissions linking AROs with ACOs
 *
 * @package       Cake.Model
 */
class Permission extends AppModel {

/**
 * Explicitly disable in-memory query caching
 *
 * @var bool
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
 * @return bool Success (true if ARO has access to action in ACO, false otherwise)
 */
	public function check($aro, $aco, $action = '*') {
		if (!$aro || !$aco) {
			return false;
		}

		$permKeys = $this->getAcoKeys($this->schema());
		$aroPath = $this->Aro->node($aro);
		$acoPath = $this->Aco->node($aco);

		if (!$aroPath) {
			$this->log(__d('cake_dev',
					"%s - Failed ARO node lookup in permissions check. Node references:\nAro: %s\nAco: %s",
					'DbAcl::check()',
					print_r($aro, true),
					print_r($aco, true)),
				E_USER_WARNING
			);
			return false;
		}

		if (!$acoPath) {
			$this->log(__d('cake_dev',
					"%s - Failed ACO node lookup in permissions check. Node references:\nAro: %s\nAco: %s",
					'DbAcl::check()',
					print_r($aro, true),
					print_r($aco, true)),
				E_USER_WARNING
			);
			return false;
		}

		if ($action !== '*' && !in_array('_' . $action, $permKeys)) {
			$this->log(__d('cake_dev', "ACO permissions key %s does not exist in %s", $action, 'DbAcl::check()'), E_USER_NOTICE);
			return false;
		}

		$acoIDs = Hash::extract($acoPath, '{n}.' . $this->Aco->alias . '.id');

		$count = count($aroPath);
		$inherited = array();
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
			}
			$perms = Hash::extract($perms, '{n}.' . $this->alias);
			foreach ($perms as $perm) {
				if ($action === '*') {
					if (empty($perm)) {
						continue;
					}
					foreach ($permKeys as $key) {
						if ($perm[$key] == -1 && !(isset($inherited[$key]) && $inherited[$key] == 1)) {
							// Deny, but only if a child node didnt't explicitly allow
							return false;
						} elseif ($perm[$key] == 1) {
							// Allow & inherit from parent nodes
							$inherited[$key] = $perm[$key];
						}
					}
				} else {
					switch ($perm['_' . $action]) {
						case -1:
							return false;
						case 0:
							continue;
						case 1:
							return true;
					}
				}
			}

			if ($action === '*' && count($inherited) === count($permKeys)) {
				return true;
			}
		}
		return false;
	}

/**
 * Allow $aro to have access to action $actions in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $actions Action (defaults to *) Invalid permissions will result in an exception
 * @param int $value Value to indicate access type (1 to give access, -1 to deny, 0 to inherit)
 * @return bool Success
 * @throws AclException on Invalid permission key.
 */
	public function allow($aro, $aco, $actions = '*', $value = 1) {
		$perms = $this->getAclLink($aro, $aco);
		$permKeys = $this->getAcoKeys($this->schema());
		$save = array();

		if (!$perms) {
			$this->log(__d('cake_dev', '%s - Invalid node', 'DbAcl::allow()'), E_USER_WARNING);
			return false;
		}
		if (isset($perms[0])) {
			$save = $perms[0][$this->alias];
		}

		if ($actions === '*') {
			$save = array_combine($permKeys, array_pad(array(), count($permKeys), $value));
		} else {
			if (!is_array($actions)) {
				$actions = array('_' . $actions);
			}
			foreach ($actions as $action) {
				if ($action{0} !== '_') {
					$action = '_' . $action;
				}
				if (!in_array($action, $permKeys, true)) {
					throw new AclException(__d('cake_dev', 'Invalid permission key "%s"', $action));
				}
				$save[$action] = $value;
			}
		}
		list($save['aro_id'], $save['aco_id']) = array($perms['aro'], $perms['aco']);

		if ($perms['link'] && !empty($perms['link'])) {
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
