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
/**
 * Load Model and AppModel
 */
loadModel();
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
 * Constructor
 *
 */
	function __construct() {
		$config = Configure::read('Acl.database');
		if(isset($config)) {
			$this->useDbConfig = $config;
		}
		parent::__construct();
	}
/**
 * Retrieves the Aro/Aco node for this model
 *
 * @param mixed $ref
 * @return array
 */
	function node($ref = null) {
		$db =& ConnectionManager::getDataSource($this->useDbConfig);
		$type = $this->currentModel;
		$prefix = $this->tablePrefix;
		$result = null;

		if (!empty($this->useTable)) {
			$table = $this->useTable;
		} else {
			$table = Inflector::pluralize(Inflector::underscore($type));
		}

		if (empty($ref)) {
			return null;
		} elseif (is_string($ref)) {
			$path = explode('/', $ref);
			$start = $path[0];
			unset($path[0]);

			$queryData = array('conditions' => array(
											$db->name("{$type}.lft") . ' <= ' . $db->name("{$type}0.lft"),
											$db->name("{$type}.rght") . ' >= ' . $db->name("{$type}0.rght")),
									'fields' => array('id', 'parent_id', 'model', 'foreign_key', 'alias'),
									'joins' => array(array('table' => $db->name($prefix . $table),
											'alias' => "{$type}0",
											'type' => 'LEFT',
											'conditions' => array("{$type}0.alias" => $start))),
									'order' => $db->name("{$type}.lft") . ' DESC');
			foreach ($path as $i => $alias) {
				$j = $i - 1;

				array_push($queryData['joins'], array(
								'table' => $db->name($prefix . $table),
								'alias' => "{$type}{$i}",
								'type'  => 'LEFT',
								'conditions' => array(
										$db->name("{$type}{$i}.lft") . ' > ' . $db->name("{$type}{$j}.lft"),
										$db->name("{$type}{$i}.rght") . ' < ' . $db->name("{$type}{$j}.rght"),
										$db->name("{$type}{$i}.alias") . ' = ' . $db->value($alias))));

				$queryData['conditions'] = array('or' => array(
				$db->name("{$type}.lft") . ' <= ' . $db->name("{$type}0.lft") . ' AND ' . $db->name("{$type}.rght") . ' >= ' . $db->name("{$type}0.rght"),
				$db->name("{$type}.lft") . ' <= ' . $db->name("{$type}{$i}.lft") . ' AND ' . $db->name("{$type}.rght") . ' >= ' . $db->name("{$type}{$i}.rght")));
			}
			$result = $db->read($this, $queryData, -1);

		} elseif (is_object($ref) && is_a($ref, 'Model')) {
			$ref = array('model' => $ref->currentModel, 'foreign_key' => $ref->id);

		} elseif (is_array($ref) && !(isset($ref['model']) && isset($ref['foreign_key']))) {
			$name = key($ref);
			if (!ClassRegistry::isKeySet($name)) {
				if (!loadModel($name)) {
					trigger_error("Model class '$name' not found in AclNode::node() when trying to bind {$this->currentModel} object", E_USER_WARNING);
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
			$queryData = array('conditions'	=> $ref,
									'fields' => array('id', 'parent_id', 'model', 'foreign_key', 'alias'),
									'joins' => array(array('table' => $db->name($prefix . $table),
									'alias' => "{$type}0",
									'type' => 'LEFT',
									'conditions' => array(
									$db->name("{$type}.lft") . ' <= ' . $db->name("{$type}0.lft"),
									$db->name("{$type}.rght") . ' >= ' . $db->name("{$type}0.rght")))),
									'order' => $db->name("{$type}.lft") . ' DESC');
			$result = $db->read($this, $queryData, -1);

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
/**
 * Constructor
 *
 */
	function __construct() {
		$config = Configure::read('Acl.database');
		if(isset($config)) {
			$this->useDbConfig = $config;
		}
		parent::__construct();
	}
}
?>
