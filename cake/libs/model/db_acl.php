<?php
/* SVN FILE: $Id$ */
/**
 * This is core configuration file.
 *
 * Use it to configure core behaviour ofCake.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs.model
 * @since			CakePHP(tm) v 0.2.9
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Set database config if not defined.
 */
if (!defined('ACL_DATABASE')) {
	define('ACL_DATABASE', 'default');
}
/**
 * Load Model and AppModel
 */
loadModel();
/**
 * In this file you can extend the AclBase.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model
 */
class DB_ACL extends AclBase {
/**
 * Enter description here...
 *
 */
	function __construct() {
		parent::__construct();
		$this->Aro =& new Aro();
		$this->Aco =& new Aco();
	}
/**
 * Enter description here...
 *
 * @param unknown_type $aro
 * @param unknown_type $aco
 * @param unknown_type $action
 * @return unknown
 */
	function initialize(&$component) {
		$component->Aro =& $this->Aro;
		$component->Aco =& $this->Aco;
	}
/**
 * Enter description here...
 *
 * @param unknown_type $aro
 * @param unknown_type $aco
 * @param unknown_type $action
 * @return unknown
 */
	function check($aro, $aco, $action = "*") {

		if ($aro == null || $aco == null) {
			return false;
		}

		$permKeys = $this->_getAcoKeys($this->Aro->Permission->loadInfo());
		$aroPath = $this->Aro->node($aro);
		$acoPath = new Set($this->Aco->node($aco));

		if (empty($aroPath) ||  empty($acoPath)) {
			trigger_error("DB_ACL::check() - Failed ARO/ACO node lookup in permissions check.  Node references:\nAro: " . print_r($aro, true) . "\nAco: " . print_r($aco, true), E_USER_WARNING);
			return false;
		}
		if ($acoPath->get() == null || $acoPath->get() == array()) {
			trigger_error("DB_ACL::check() - Failed ACO node lookup in permissions check.  Node references:\nAro: " . print_r($aro, true) . "\nAco: " . print_r($aco, true), E_USER_WARNING);
			return false;
		}

		$aroNode = $aroPath[0];
		$acoNode = $acoPath->get();
		$acoNode = $acoNode[0];

		if ($action != '*' && !in_array('_' . $action, $permKeys)) {
			trigger_error(sprintf(__("ACO permissions key %s does not exist in DB_ACL::check()", true), $action), E_USER_NOTICE);
			return false;
		}

		for($i = count($aroPath) - 1; $i >= 0; $i--) {
			$perms = $this->Aro->Permission->findAll(
				array(
					'Permission.aro_id' => $aroPath[$i]['Aro']['id'],
					'Permission.aco_id' => $acoPath->extract('{n}.Aco.id')
				),
				null, array('Aco.lft' => 'desc'), null, null, 0
			);

			if (empty($perms)) {
				continue;
			} else {
				foreach(Set::extract($perms, '{n}.Permission') as $perm) {
					if ($action == '*') {
						// ARO must be cleared for ALL ACO actions
						foreach($permKeys as $key) {
							if (!empty($perm)) {
								if ($perm[$key] != 1) {
									return false;
								}
							}
						}
						return true;
					} else {
						switch($perm['_' . $action]) {
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
 * Allow
 *
 * @return boolean
 */
	function allow($aro, $aco, $action = "*", $value = 1) {
		$perms = $this->getAclLink($aro, $aco);
		$permKeys = $this->_getAcoKeys($this->Aro->Permission->loadInfo());
		$save = array();

		if ($perms == false) {
			trigger_error(__('DB_ACL::allow() - Invalid node', true), E_USER_WARNING);
			return false;
		}

		if (isset($perms[0])) {
			$save = $perms[0]['Permission'];
		}

		if ($action == "*") {
			$permKeys = $this->_getAcoKeys($this->Aro->Permission->loadInfo());

			foreach($permKeys as $key) {
				$save[$key] = $value;
			}
		} else {
			if (in_array('_' . $action, $permKeys)) {
				$save['_' . $action] = $value;
			} else {
				trigger_error(__('DB_ACL::allow() - Invalid ACO action', true), E_USER_WARNING);
				return false;
			}
		}

		$save['aro_id'] = $perms['aro'];
		$save['aco_id'] = $perms['aco'];

		if ($perms['link'] != null && count($perms['link']) > 0) {
			$save['id'] = $perms['link'][0]['Permission']['id'];
		}
		return $this->Aro->Permission->save(array('Permission' => $save));
	}
/**
 * Deny
 *
 * @return boolean
 */
	function deny($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action, -1);
	}
/**
 * Inherit
 *
 * @return boolean
 */
	function inherit($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action, 0);
	}
/**
 * Allow alias
 *
 * @return boolean
 */
	function grant($aro, $aco, $action = "*") {
		return $this->allow($aro, $aco, $action);
	}
/**
 * Deny alias
 *
 * @return boolean
 */
	function revoke($aro, $aco, $action = "*") {
		return $this->deny($aro, $aco, $action);
	}
/**
 * Private method
 *
 */
	function &__getObject($id = null, $object) {
		if ($id == null) {
			trigger_error(__('Null id provided in DB_ACL::get', true) . $object, E_USER_WARNING);
			return null;
		}

		if (is_numeric($id)) {
			$conditions = array("{$object}.foreign_key" => $id);
		} else {
			$conditions = array("{$object}.alias" => $id);
		}

		$tmp = $this->{$object}->find($conditions);
		$this->{$object}->id = $tmp[$object]['id'];
		return $this->{$object};
	}
/**
 * Get an array of access-control links between the given Aro and Aco
 *
 * @param mixed $aro
 * @param mixed $aco
 * @return array
 */
	function getAclLink($aro, $aco) {
		$Link = new Permission();

		$obj = array();
		$obj['Aro'] = $this->Aro->node($aro);
		$obj['Aco'] = $this->Aco->node($aco);

		if (empty($obj['Aro']) || empty($obj['Aco'])) {
			return false;
		}

		return array(
			'aro' => Set::extract($obj, 'Aro.0.Aro.id'),
			'aco'  => Set::extract($obj, 'Aco.0.Aco.id'),
			'link' => $this->Aro->Permission->findAll(array(
				'Permission.aro_id' => Set::extract($obj, 'Aro.0.Aro.id'),
				'Permission.aco_id' => Set::extract($obj, 'Aco.0.Aco.id')
			))
		);
	}
/**
 * Enter description here...
 *
 * @param unknown_type $keys
 * @return unknown
 */
	function _getAcoKeys($keys) {
		$newKeys = array();
		$keys = $keys->extract('{n}.name');

		foreach($keys as $key) {
			if (!in_array($key, array('id', 'aro_id', 'aco_id'))) {
				$newKeys[] = $key;
			}
		}
		return $newKeys;
	}
}
/**
 * Short description for file.
 *
 * Long description for file
 *
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model
 */
class AclNode extends AppModel {

	var $useDbConfig = ACL_DATABASE;
/**
 * Explicitly disable in-memory query caching for ACL models
 *
 * @var boolean
 */
	var $cacheQueries = false;
/**
 * ACL models use the Tree behavior
 *
 * @var mixed
 */
	var $actsAs = array('Tree' => 'nested');
/**
 * Retrieves the Aro/Aco node for this model
 *
 * @param mixed $ref
 * @return array
 */
	function node($ref = null) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$type = $this->name;
		$prefix = $this->tablePrefix;

		if (!empty($this->useTable)) {
			$table = $this->useTable;
		} else {
			$table = Inflector::pluralize(Inflector::underscore($type));
		}

		if (empty($ref)) {
			return null;
		} elseif (is_string($ref)) {
			$path = explode('/', $ref);
			$start = $path[count($path) - 1];
			unset($path[count($path) - 1]);

			$query  = "SELECT {$type}.* From {$prefix}{$table} AS {$type} ";
			$query .=  "LEFT JOIN {$prefix}{$table} AS {$type}0 ";
			$query .= "ON {$type}0.alias = " . $db->value($start) . " ";

			foreach ($path as $i => $alias) {
				$j = $i - 1;
				$k = $i + 1;
				$query .= "LEFT JOIN {$prefix}{$table} AS {$type}{$k} ";
				$query .= "ON {$type}{$k}.lft > {$type}{$i}.lft && {$type}{$k}.rght < {$type}{$i}.rght ";
				$query .= "AND {$type}{$k}.alias = " . $db->value($alias) . " ";
			}
			$result = $this->query("{$query} WHERE {$type}.lft <= {$type}0.lft AND {$type}.rght >= {$type}0.rght ORDER BY {$type}.lft DESC", $this->cacheQueries);
		} elseif (is_object($ref) && is_a($ref, 'Model')) {
			$ref = array('model' => $ref->name, 'foreign_key' => $ref->id);
		} elseif (is_array($ref) && !(isset($ref['model']) && isset($ref['foreign_key']))) {
			$name = key($ref);
			if (!ClassRegistry::isKeySet($name)) {
				if (!loadModel($name)) {
					trigger_error("Model class '$name' not found in AclNode::node() when trying to bind {$this->name} object", E_USER_WARNING);
					return null;
				}
				$model =& new $name();
			} else {
				$model =& ClassRegistry::getObject($name);
			}
			$tmpRef = null;
			if (method_exists($model, 'bindNode')) {
				$tmpRef = $model->bindNode($ref);
			}
			if (empty($tmpRef)) {
				$ref = array('model' => $name, 'foreign_key' => $ref[$name][$model->primaryKey]);
			} else {
				if (is_string($tmpRef)) {
					return $this->node($tmpRef);
				}
				$ref = $tmpRef;
			}
		}
		if (is_array($ref)) {
			foreach ($ref as $key => $val) {
				if (strpos($key, $type) !== 0) {
					unset($ref[$key]);
					$ref["{$type}0.{$key}"] = $val;
				}
			}
			$query  = "SELECT {$type}.* From {$prefix}{$table} AS {$type} ";
			$query .=  "LEFT JOIN {$prefix}{$table} AS {$type}0 ";
			$query .= "ON {$type}.lft <= {$type}0.lft AND {$type}.rght >= {$type}0.rght ";
			$result = $this->query("{$query} " . $db->conditions($ref) ." ORDER BY {$type}.lft DESC", $this->cacheQueries);

			if (!$result) {
				trigger_error("AclNode::node() - Couldn't find {$type} node identified by \"" . print_r($ref, true) . "\"", E_USER_WARNING);
			}
		}
		return $result;
	}
}
/**
 * Short description for file.
 *
 * Long description for file
 *
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model
 */
class Aco extends AclNode {
/**
 * Model name
 *
 * @var string
 */
	var $name = 'Aco';
/**
 * Binds to ARO nodes through permissions settings
 *
 * @var array
 */
	var $hasAndBelongsToMany = array('Aro' => array('with' => 'Permission'));
}
/**
 * Short description for file.
 *
 * Long description for file
 *
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model
 */
class AcoAction extends AppModel {
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	 var $belongsTo = 'Aco';
}
/**
 * Short description for file.
 *
 * Long description for file
 *
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model
 */
class Aro extends AclNode {
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $name = 'Aro';
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $hasAndBelongsToMany = array('Aco' => array('with' => 'Permission'));
}
/**
 * Short description for file.
 *
 * Long description for file
 *
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model
 */
class Permission extends AppModel {

	var $useDbConfig = ACL_DATABASE;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	var $cacheQueries = false;
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	 var $name = 'Permission';
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	 var $useTable = 'aros_acos';
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	 var $belongsTo = 'Aro,Aco';
/**
 * Enter description here...
 *
 * @var unknown_type
 */
	 var $actsAs = null;
}
?>