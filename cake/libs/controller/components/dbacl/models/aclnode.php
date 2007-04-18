<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
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
 * @subpackage		cake.cake.libs.controller.components.dbacl.models
 * @since			CakePHP(tm) v 0.10.0.1232
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs.controller.components.dbacl.models
 *
 */
loadModel();

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

?>