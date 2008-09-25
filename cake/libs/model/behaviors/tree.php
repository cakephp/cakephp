<?php
/* SVN FILE: $Id$ */
/**
 * Tree behavior class.
 *
 * Enables a model object to act as a node-based tree.
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link				http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package			cake
 * @subpackage		cake.cake.libs.model.behaviors
 * @since			CakePHP v 1.2.0.4487
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * Short description for file
 *
 * Long description for file
 *
 * @package		cake
 * @subpackage	cake.cake.libs.model.behaviors
 */
class TreeBehavior extends ModelBehavior {
/**
 * Errors
 *
 * @var array
 */
	var $errors = array();
/**
 * Defaults
 *
 * @var array
 * @access protected
 */
	var $_defaults = array(
		'parent' => 'parent_id', 'left' => 'lft', 'right' => 'rght',
		'scope' => '1 = 1', 'type' => 'nested', '__parentChange' => false, 'recursive' => -1
	);
/**
 * Initiate Tree behavior
 *
 * @param object $model
 * @param array $config
 * @return void
 * @access public
 */
	function setup(&$model, $config = array()) {
		if (!is_array($config)) {
			$config = array('type' => $config);
		}
		$settings = array_merge($this->_defaults, $config);

		if (in_array($settings['scope'], $model->getAssociated('belongsTo'))) {
			$data = $model->getAssociated($settings['scope']);
			$parent =& $model->{$settings['scope']};
			$settings['scope'] = $model->alias . '.' . $data['foreignKey'] . ' = ' . $parent->alias . '.' . $parent->primaryKey;
			$settings['recursive'] = 0;
		}
		$this->settings[$model->alias] = $settings;
	}
/**
 * After save method. Called after all saves
 *
 * Overriden to transparently manage setting the lft and rght fields if and only if the parent field is included in the
 * parameters to be saved.
 *
 * @param AppModel $model
 * @param boolean $created indicates whether the node just saved was created or updated
 * @return boolean true on success, false on failure
 * @access public
 */
	function afterSave(&$model, $created) {
		extract($this->settings[$model->alias]);
		if ($created) {
			if ((isset($model->data[$model->alias][$parent])) && $model->data[$model->alias][$parent]) {
				return $this->_setParent($model, $model->data[$model->alias][$parent], $created);
			}
		} elseif ($__parentChange) {
			$this->settings[$model->alias]['__parentChange'] = false;
			return $this->_setParent($model, $model->data[$model->alias][$parent]);
		}
	}
/**
 * Before delete method. Called before all deletes
 *
 * Will delete the current node and all children using the deleteAll method and sync the table
 *
 * @param AppModel $model
 * @return boolean true to continue, false to abort the delete
 * @access public
 */
	function beforeDelete(&$model) {
		extract($this->settings[$model->alias]);
		list($name, $data) = array($model->alias, $model->read());
		$data = $data[$name];

		if (!$data[$right] || !$data[$left]) {
			return true;
		}
		$diff = $data[$right] - $data[$left] + 1;

		if ($diff > 2) {
			if (is_string($scope)) {
				$scope = array($scope);
			}
			$scope[]["{$model->alias}.{$left} BETWEEN ? AND ?"] = array($data[$left] + 1, $data[$right] - 1);
			$model->deleteAll($scope);
		}
		$this->__sync($model, $diff, '-', '> ' . $data[$right]);
		return true;
	}
/**
 * Before save method. Called before all saves
 *
 * Overriden to transparently manage setting the lft and rght fields if and only if the parent field is included in the
 * parameters to be saved. For newly created nodes with NO parent the left and right field values are set directly by
 * this method bypassing the setParent logic.
 *
 * @since 1.2
 * @param AppModel $model
 * @return boolean true to continue, false to abort the save
 * @access public
 */
	function beforeSave(&$model) {
		extract($this->settings[$model->alias]);

		if (isset($model->data[$model->alias][$model->primaryKey])) {
			if ($model->data[$model->alias][$model->primaryKey]) {
				if (!$model->id) {
					$model->id = $model->data[$model->alias][$model->primaryKey];
				}
			}
			unset($model->data[$model->alias][$model->primaryKey]);
		}

		$this->_addToWhitelist($model, array($left, $right));
		if (!$model->id) {
			if (array_key_exists($parent, $model->data[$model->alias]) && $model->data[$model->alias][$parent]) {
				$parentNode = $model->find('first', array(
					'conditions' => array($scope, $model->escapeField() => $model->data[$model->alias][$parent]),
					'fields' => array($model->primaryKey, $right), 'recursive' => $recursive
				));
				if (!$parentNode) {
					return false;
				}
				list($parentNode) = array_values($parentNode);
				$model->data[$model->alias][$left] = 0; //$parentNode[$right];
				$model->data[$model->alias][$right] = 0; //$parentNode[$right] + 1;
			} else {
				$edge = $this->__getMax($model, $scope, $right, $recursive);
				$model->data[$model->alias][$left] = $edge + 1;
				$model->data[$model->alias][$right] = $edge + 2;
			}
		} elseif (array_key_exists($parent, $model->data[$model->alias])) {
			if ($model->data[$model->alias][$parent] != $model->field($parent)) {
				$this->settings[$model->alias]['__parentChange'] = true;
			}
			if (!$model->data[$model->alias][$parent]) {
				$model->data[$model->alias][$parent] = null;
				$this->_addToWhitelist($model, $parent);
			} else {
				list($node) = array_values($model->find('first', array(
					'conditions' => array($scope,$model->escapeField() => $model->id),
					'fields' => array($model->primaryKey, $parent, $left, $right ), 'recursive' => $recursive)
				));

				$parentNode = $model->find('first', array(
					'conditions' => array($scope, $model->escapeField() => $model->data[$model->alias][$parent]),
					'fields' => array($model->primaryKey, $left, $right), 'recursive' => $recursive
				));
				if (!$parentNode) {
					return false;
				}
				list($parentNode) = array_values($parentNode);

				if (($node[$left] < $parentNode[$left]) && ($parentNode[$right] < $node[$right])) {
					return false;
				} elseif ($node[$model->primaryKey] == $parentNode[$model->primaryKey]) {
					return false;
				}
			}
		}
		return true;
	}
/**
 * Get the number of child nodes
 *
 * If the direct parameter is set to true, only the direct children are counted (based upon the parent_id field)
 * If false is passed for the id parameter, all top level nodes are counted, or all nodes are counted.
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to read or false to read all top level nodes
 * @param boolean $direct whether to count direct, or all, children
 * @return integer number of child nodes
 * @access public
 */
	function childcount(&$model, $id = null, $direct = false) {
		if (is_array($id)) {
			extract (array_merge(array('id' => null), $id));
		}
		if ($id === null && $model->id) {
			$id = $model->id;
		} elseif (!$id) {
			$id = null;
		}
		extract($this->settings[$model->alias]);

		if ($direct) {
			return $model->find('count', array('conditions' => array($scope, $model->escapeField($parent) => $id)));
		}

		if ($id === null) {
			return $model->find('count', array('conditions' => $scope));
		} elseif (!empty ($model->data)) {
			$data = $model->data[$model->alias];
		} else {
			list($data) = array_values($model->find('first', array('conditions' => array($scope, $model->escapeField() => $id), 'recursive' => $recursive)));
		}
		return ($data[$right] - $data[$left] - 1) / 2;
	}
/**
 * Get the child nodes of the current model
 *
 * If the direct parameter is set to true, only the direct children are returned (based upon the parent_id field)
 * If false is passed for the id parameter, top level, or all (depending on direct parameter appropriate) are counted.
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to read
 * @param boolean $direct whether to return only the direct, or all, children
 * @param mixed $fields Either a single string of a field name, or an array of field names
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC") defaults to the tree order
 * @param integer $limit SQL LIMIT clause, for calculating items per page.
 * @param integer $page Page number, for accessing paged data
 * @param integer $recursive The number of levels deep to fetch associated records
 * @return array Array of child nodes
 * @access public
 */
	function children(&$model, $id = null, $direct = false, $fields = null, $order = null, $limit = null, $page = 1, $recursive = null) {
		if (is_array($id)) {
			extract (array_merge(array('id' => null), $id));
		}
		$overrideRecursive = $recursive;

		if ($id === null && $model->id) {
			$id = $model->id;
		} elseif (!$id) {
			$id = null;
		}
		$name = $model->alias;
		extract($this->settings[$model->alias]);

		if (!is_null($overrideRecursive)) {
			$recursive = $overrideRecursive;
		}
		if (!$order) {
			$order = $model->alias . '.' . $left . ' asc';
		}
		if ($direct) {
			$conditions = array($scope, $model->escapeField($parent) => $id);
			return $model->find('all', compact('conditions', 'fields', 'order', 'limit', 'page', 'recursive'));
		}

		if (!$id) {
			$conditions = $scope;
		} else {
			$result = array_values($model->find('first', array(
				'conditions' => array($scope, $model->escapeField() => $id),
				'fields' => array($left, $right),
				'recursive' => $recursive
			)));

			if (empty($result) || !isset($result[0])) {
				return array();
			}
			$conditions = array($scope,
				$model->escapeField($right) . ' <' => $result[0][$right],
				$model->escapeField($left) . ' >' => $result[0][$left]
			);
		}
		return $model->find('all', compact('conditions', 'fields', 'order', 'limit', 'page', 'recursive'));
	}
/**
 * A convenience method for returning a hierarchical array used for HTML select boxes
 *
 * @param AppModel $model
 * @param mixed $conditions SQL conditions as a string or as an array('field' =>'value',...)
 * @param string $keyPath A string path to the key, i.e. "{n}.Post.id"
 * @param string $valuePath A string path to the value, i.e. "{n}.Post.title"
 * @param string $spacer The character or characters which will be repeated
 * @param integer $recursive The number of levels deep to fetch associated records
 * @return array An associative array of records, where the id is the key, and the display field is the value
 * @access public
 */
	function generatetreelist(&$model, $conditions = null, $keyPath = null, $valuePath = null, $spacer = '_', $recursive = null) {
		$overrideRecursive = $recursive;
		extract($this->settings[$model->alias]);
		if (!is_null($overrideRecursive)) {
			$recursive = $overrideRecursive;
		}

		if ($keyPath == null && $valuePath == null && $model->hasField($model->displayField)) {
			$fields = array($model->primaryKey, $model->displayField, $left, $right);
		} else {
			$fields = null;
		}

		if ($keyPath == null) {
			$keyPath = '{n}.' . $model->alias . '.' . $model->primaryKey;
		}

		if ($valuePath == null) {
			$valuePath = array('{0}{1}', '{n}.tree_prefix', '{n}.' . $model->alias . '.' . $model->displayField);

		} elseif (is_string($valuePath)) {
			$valuePath = array('{0}{1}', '{n}.tree_prefix', $valuePath);

		} else {
			$valuePath[0] = '{' . (count($valuePath) - 1) . '}' . $valuePath[0];
			$valuePath[] = '{n}.tree_prefix';
		}
		$order = $model->alias . '.' . $left . ' asc';
		$results = $model->find('all', compact('conditions', 'fields', 'order', 'recursive'));
		$stack = array();

		foreach ($results as $i => $result) {
			while ($stack && ($stack[count($stack) - 1] < $result[$model->alias][$right])) {
				array_pop($stack);
			}
			$results[$i]['tree_prefix'] = str_repeat($spacer,count($stack));
			$stack[] = $result[$model->alias][$right];
		}
		if (empty($results)) {
			return array();
		}
		return Set::combine($results, $keyPath, $valuePath);
	}
/**
 * Get the parent node
 *
 * reads the parent id and returns this node
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to read
 * @param integer $recursive The number of levels deep to fetch associated records
 * @return array Array of data for the parent node
 * @access public
 */
	function getparentnode(&$model, $id = null, $fields = null, $recursive = null) {
		if (is_array($id)) {
			extract (array_merge(array('id' => null), $id));
		}
		$overrideRecursive = $recursive;
		if (empty ($id)) {
			$id = $model->id;
		}
		extract($this->settings[$model->alias]);
		if (!is_null($overrideRecursive)) {
			$recursive = $overrideRecursive;
		}
		$parentId = $model->read($parent, $id);

		if ($parentId) {
			$parentId = $parentId[$model->alias][$parent];
			$parent = $model->find('first', array('conditions' => array($model->escapeField() => $parentId), 'fields' => $fields, 'recursive' => $recursive));

			return $parent;
		}
		return false;
	}
/**
 * Get the path to the given node
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to read
 * @param mixed $fields Either a single string of a field name, or an array of field names
 * @param integer $recursive The number of levels deep to fetch associated records
 * @return array Array of nodes from top most parent to current node
 * @access public
 */
	function getpath(&$model, $id = null, $fields = null, $recursive = null) {
		if (is_array($id)) {
			extract (array_merge(array('id' => null), $id));
		}
		$overrideRecursive = $recursive;
		if (empty ($id)) {
			$id = $model->id;
		}
		extract($this->settings[$model->alias]);
		if (!is_null($overrideRecursive)) {
			$recursive = $overrideRecursive;
		}
		$result = $model->find('first', array('conditions' => array($model->escapeField() => $id), 'fields' => array($left, $right), 'recursive' => $recursive));
		if ($result) {
			$result = array_values($result);
		} else {
			return null;
		}
		$item = $result[0];
		$results = $model->find('all', array(
			'conditions' => array($scope, $model->escapeField($left) . ' <=' => $item[$left], $model->escapeField($right) . ' >=' => $item[$right]),
			'fields' => $fields, 'order' => array($model->escapeField($left) => 'asc'), 'recursive' => $recursive
		));
		return $results;
	}
/**
 * Reorder the node without changing the parent.
 *
 * If the node is the last child, or is a top level node with no subsequent node this method will return false
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to move
 * @param mixed $number how many places to move the node or true to move to last position
 * @return boolean true on success, false on failure
 * @access public
 */
	function movedown(&$model, $id = null, $number = 1) {
		if (is_array($id)) {
			extract (array_merge(array('id' => null), $id));
		}
		if (!$number) {
			return false;
		}
		if (empty ($id)) {
			$id = $model->id;
		}
		extract($this->settings[$model->alias]);
		list($node) = array_values($model->find('first', array(
			'conditions' => array($scope, $model->escapeField() => $id),
			'fields' => array($model->primaryKey, $left, $right, $parent), 'recursive' => $recursive
		)));
		if ($node[$parent]) {
			list($parentNode) = array_values($model->find('first', array(
				'conditions' => array($scope, $model->escapeField() => $node[$parent]),
				'fields' => array($model->primaryKey, $left, $right), 'recursive' => $recursive
			)));
			if (($node[$right] + 1) == $parentNode[$right]) {
				return false;
			}
		}
		$nextNode = $model->find('first', array(
			'conditions' => array($scope, $model->escapeField($left) => ($node[$right] + 1)),
			'fields' => array($model->primaryKey, $left, $right), 'recursive' => $recursive)
		);
		if ($nextNode) {
			list($nextNode)= array_values($nextNode);
		} else {
			return false;
		}
		$edge = $this->__getMax($model, $scope, $right, $recursive);
		$this->__sync($model, $edge - $node[$left] + 1, '+', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right]);
		$this->__sync($model, $nextNode[$left] - $node[$left], '-', 'BETWEEN ' . $nextNode[$left] . ' AND ' . $nextNode[$right]);
		$this->__sync($model, $edge - $node[$left] - ($nextNode[$right] - $nextNode[$left]), '-', '> ' . $edge);

		if (is_int($number)) {
			$number--;
		}
		if ($number) {
			$this->moveDown($model, $id, $number);
		}
		return true;
	}
/**
 * Reorder the node without changing the parent.
 *
 * If the node is the first child, or is a top level node with no previous node this method will return false
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to move
 * @param mixed $number how many places to move the node, or true to move to first position
 * @return boolean true on success, false on failure
 * @access public
 */
	function moveup(&$model, $id = null, $number = 1) {
		if (is_array($id)) {
			extract (array_merge(array('id' => null), $id));
		}
		if (!$number) {
			return false;
		}
		if (empty ($id)) {
			$id = $model->id;
		}
		extract($this->settings[$model->alias]);
		list($node) = array_values($model->find('first', array(
			'conditions' => array($scope, $model->escapeField() => $id),
			'fields' => array($model->primaryKey, $left, $right, $parent ), 'recursive' => $recursive
		)));
		if ($node[$parent]) {
			list($parentNode) = array_values($model->find('first', array(
				'conditions' => array($scope, $model->escapeField() => $node[$parent]),
				'fields' => array($model->primaryKey, $left, $right), 'recursive' => $recursive
			)));
			if (($node[$left] - 1) == $parentNode[$left]) {
				return false;
			}
		}
		$previousNode = $model->find('first', array(
			'conditions' => array($scope, $model->escapeField($right) => ($node[$left] - 1)),
			'fields' => array($model->primaryKey, $left, $right),
			'recursive' => $recursive
		));

		if ($previousNode) {
			list($previousNode) = array_values($previousNode);
		} else {
			return false;
		}
		$edge = $this->__getMax($model, $scope, $right, $recursive);
		$this->__sync($model, $edge - $previousNode[$left] +1, '+', 'BETWEEN ' . $previousNode[$left] . ' AND ' . $previousNode[$right]);
		$this->__sync($model, $node[$left] - $previousNode[$left], '-', 'BETWEEN ' .$node[$left] . ' AND ' . $node[$right]);
		$this->__sync($model, $edge - $previousNode[$left] - ($node[$right] - $node[$left]), '-', '> ' . $edge);
		if (is_int($number)) {
			$number--;
		}
		if ($number) {
			$this->moveUp($model, $id, $number);
		}
		return true;
	}
/**
 * Recover a corrupted tree
 *
 * The mode parameter is used to specify the source of info that is valid/correct. The opposite source of data
 * will be populated based upon that source of info. E.g. if the MPTT fields are corrupt or empty, with the $mode
 * 'parent' the values of the parent_id field will be used to populate the left and right fields. The missingParentAction
 * parameter only applies to "parent" mode and determines what to do if the parent field contains an id that is not present.
 *
 * @todo Could be written to be faster, *maybe*. Ideally using a subquery and putting all the logic burden on the DB.
 * @param AppModel $model
 * @param string $mode parent or tree
 * @param mixed $missingParentAction 'return' to do nothing and return, 'delete' to
 * delete, or the id of the parent to set as the parent_id
 * @return boolean true on success, false on failure
 * @access public
 */
	function recover(&$model, $mode = 'parent', $missingParentAction = null) {
		if (is_array($mode)) {
			extract (array_merge(array('mode' => 'parent'), $mode));
		}
		extract($this->settings[$model->alias]);
		$model->recursive = $recursive;
		if ($mode == 'parent') {
			$model->bindModel(array('belongsTo' => array('VerifyParent' => array(
				'className' => $model->alias,
				'foreignKey' => $parent,
				'fields' => array($model->primaryKey, $left, $right, $parent),
			))));
			$missingParents = $model->find('list', array(
				'recursive' => 0,
				'conditions' => array($scope, array(
					'NOT' => array($model->escapeField($parent) => null), $model->VerifyParent->escapeField() => null
				))
			));
			$model->unbindModel(array('belongsTo' => array('VerifyParent')));
			if ($missingParents) {
				if ($missingParentAction == 'return') {
					foreach ($missingParents as $id => $display) {
						$this->errors[]	= 'cannot find the parent for ' . $model->alias . ' with id ' . $id . '(' . $display . ')';

					}
					return false;
				} elseif ($missingParentAction == 'delete') {
					$model->deleteAll(array($model->primaryKey => array_flip($missingParents)));
				} else {
					$model->updateAll(array($parent => $missingParentAction), array($model->escapeField($model->primaryKey) => array_flip($missingParents)));
				}
			}
			$count = 1;
			foreach ($model->find('all', array('conditions' => $scope, 'fields' => array($model->primaryKey), 'order' => $left)) as $array) {
				$model->{$model->primaryKey} = $array[$model->alias][$model->primaryKey];
				$lft = $count++;
				$rght = $count++;
				$model->save(array($left => $lft, $right => $rght), array('callbacks' => false));
			}
			foreach ($model->find('all', array('conditions' => $scope, 'fields' => array($model->primaryKey, $parent), 'order' => $left)) as $array) {
				$model->create();
				$model->id = $array[$model->alias][$model->primaryKey];
				$this->_setParent($model, $array[$model->alias][$parent]);
			}
		} else {
			foreach ($model->find('all', array('conditions' => $scope, 'fields' => array($model->primaryKey, $parent), 'order' => $left)) as $array) {
				$path = $this->getpath($model, $array[$model->alias][$model->primaryKey]);
				if ($path == null || count($path) < 2) {
					$parentId = null;
				} else {
					$parentId = $path[count($path) - 2][$model->alias][$model->primaryKey];
				}
				$model->updateAll(array($parent => $parentId), array($model->escapeField() => $array[$model->alias][$model->primaryKey]));
			}
		}
		return true;
	}
/**
 * Reorder method.
 *
 * Reorders the nodes (and child nodes) of the tree according to the field and direction specified in the parameters.
 * This method does not change the parent of any node.
 *
 * Requires a valid tree, by default it verifies the tree before beginning.
 *
 * @param AppModel $model
 * @param array $options
 * @return boolean true on success, false on failure
 */
	function reorder(&$model, $options = array()) {
		$options = array_merge(array('id' => null, 'field' => $model->displayField, 'order' => 'ASC', 'verify' => true), $options);
		extract($options);
		if ($verify && !$model->verify()) {
			return false;
		}
		$verify = false;
		extract($this->settings[$model->alias]);
		$fields = array($model->primaryKey, $field, $left, $right);
		$sort = $field . ' ' . $order;
		$nodes = $model->children($id, true, $fields, $sort, null, null, $recursive);

		if ($nodes) {
			foreach ($nodes as $node) {
				$id = $node[$model->alias][$model->primaryKey];
				$model->moveDown($id, true);
				if ($node[$model->alias][$left] != $node[$model->alias][$right] - 1) {
					$this->reorder($model, compact('id', 'field', 'order', 'verify'));
				}
			}
		}
		return true;
	}
/**
 * Remove the current node from the tree, and reparent all children up one level.
 *
 * If the parameter delete is false, the node will become a new top level node. Otherwise the node will be deleted
 * after the children are reparented.
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to remove
 * @param boolean $delete whether to delete the node after reparenting children (if any)
 * @return boolean true on success, false on failure
 * @access public
 */
	function removefromtree(&$model, $id = null, $delete = false) {
		if (is_array($id)) {
			extract (array_merge(array('id' => null), $id));
		}
		extract($this->settings[$model->alias]);

		list($node) = array_values($model->find('first', array(
			'conditions' => array($scope, $model->escapeField() => $id),
			'fields' => array($model->primaryKey, $left, $right, $parent),
			'recursive' => $recursive
		)));

		if ($node[$right] == $node[$left] + 1) {
			if ($delete) {
				return $model->delete($id);
			} else {
				$model->id = $id;
				return $model->saveField($parent, null);
			}
		} elseif ($node[$parent]) {
			list($parentNode) = array_values($model->find('first', array(
				'conditions' => array($scope, $model->escapeField() => $node[$parent]),
				'fields' => array($model->primaryKey, $left, $right),
				'recursive' => $recursive
			)));
		} else {
			$parentNode[$right] = $node[$right] + 1;
		}

		$model->updateAll(array($parent => $node[$parent]), array($parent => $node[$model->primaryKey]));
		$this->__sync($model, 1, '-', 'BETWEEN ' . ($node[$left] + 1) . ' AND ' . ($node[$right] - 1));
		$this->__sync($model, 2, '-', '> ' . ($node[$right]));
		$model->id = $id;

		if ($delete) {
			$model->updateAll(
				array(
					$model->escapeField($left) => 0,
					$model->escapeField($right) => 0,
					$model->escapeField($parent) => null
				),
				array($model->escapeField() => $id)
			);
			return $model->delete($id);
		} else {
			$edge = $this->__getMax($model, $scope, $right, $recursive);
			if ($node[$right] == $edge) {
				$edge = $edge - 2;
			}
			$model->id = $id;
			return $model->save(
				array($left => $edge + 1, $right => $edge + 2, $parent => null),
				array('callbacks' => false)
			);
		}
	}
/**
 * Check if the current tree is valid.
 *
 * Returns true if the tree is valid otherwise an array of (type, incorrect left/right index, message)
 *
 * @param AppModel $model
 * @return mixed true if the tree is valid or empty, otherwise an array of (error type [index, node],
 *  [incorrect left/right index,node id], message)
 * @access public
 */
	function verify(&$model) {
		extract($this->settings[$model->alias]);
		if (!$model->find('count', array('conditions' => $scope))) {
			return true;
		}
		$min = $this->__getMin($model, $scope, $left, $recursive);
		$edge = $this->__getMax($model, $scope, $right, $recursive);
		$errors =  array();

		for ($i = $min; $i <= $edge; $i++) {
			$count = $model->find('count', array('conditions' => array(
				$scope, 'OR' => array($model->escapeField($left) => $i, $model->escapeField($right) => $i)
			)));
			if ($count != 1) {
				if ($count == 0) {
					$errors[] = array('index', $i, 'missing');
				} else {
					$errors[] = array('index', $i, 'duplicate');
				}
			}
		}
		$node = $model->find('first', array('conditions' => array($scope, $model->escapeField($right) . '< ' . $model->escapeField($left)), 'recursive' => 0));
		if ($node) {
			$errors[] = array('node', $node[$model->alias][$model->primaryKey], 'left greater than right.');
		}

		$model->bindModel(array('belongsTo' => array('VerifyParent' => array(
			'className' => $model->alias,
			'foreignKey' => $parent,
			'fields' => array($model->primaryKey, $left, $right, $parent)
		))));

		foreach ($model->find('all', array('conditions' => $scope, 'recursive' => 0)) as $instance) {
			if (is_null($instance[$model->alias][$left]) || is_null($instance[$model->alias][$right])) {
				$errors[] = array('node', $instance[$model->alias][$model->primaryKey],
					'has invalid left or right values');
			} elseif ($instance[$model->alias][$left] == $instance[$model->alias][$right]){
				$errors[] = array('node', $instance[$model->alias][$model->primaryKey],
					'left and right values identical');
			} elseif ($instance[$model->alias][$parent]) {
				if (!$instance['VerifyParent'][$model->primaryKey]) {
					$errors[] = array('node', $instance[$model->alias][$model->primaryKey],
						'The parent node ' . $instance[$model->alias][$parent] . ' doesn\'t exist');
				} elseif ($instance[$model->alias][$left] < $instance['VerifyParent'][$left]) {
					$errors[] = array('node', $instance[$model->alias][$model->primaryKey],
						'left less than parent (node ' . $instance['VerifyParent'][$model->primaryKey] . ').');
				} elseif ($instance[$model->alias][$right] > $instance['VerifyParent'][$right]) {
					$errors[] = array('node', $instance[$model->alias][$model->primaryKey],
						'right greater than parent (node ' . $instance['VerifyParent'][$model->primaryKey] . ').');
				}
			} elseif ($model->find('count', array('conditions' => array($scope, $model->escapeField($left) . '< ' . $instance[$model->alias][$left], $right . '> ' . $instance[$model->alias][$right]), 'recursive' => 0))) {
				$errors[] = array('node', $instance[$model->alias][$model->primaryKey], 'The parent field is blank, but has a parent');
			}
		}

		if ($errors) {
			return $errors;
		}
		return true;
	}
/**
 * Sets the parent of the given node
 *
 * The force parameter is used to override the "don't change the parent to the current parent" logic in the event
 * of recovering a corrupted table, or creating new nodes. Otherwise it should always be false. In reality this
 * method could be private, since calling save with parent_id set also calls setParent
 *
 * @param AppModel $model
 * @param mixed $parentId
 * @return boolean true on success, false on failure
 * @access protected
 */
	function _setParent(&$model, $parentId = null, $created = false) {
		extract($this->settings[$model->alias]);
		list($node) = array_values($model->find('first', array(
			'conditions' => array($scope, $model->escapeField() => $model->id),
			'fields' => array($model->primaryKey, $parent, $left, $right),
			'recursive' => $recursive
		)));
		$edge = $this->__getMax($model, $scope, $right, $recursive, $created);

		if (empty ($parentId)) {
			$this->__sync($model, $edge - $node[$left] + 1, '+', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right], $created);
			$this->__sync($model, $node[$right] - $node[$left] + 1, '-', '> ' . $node[$left], $created);
		} else {
			$parentNode = array_values($model->find('first', array(
				'conditions' => array($scope, $model->escapeField() => $parentId),
				'fields' => array($model->primaryKey, $left, $right),
				'recursive' => $recursive
			)));

			if (empty($parentNode) || empty($parentNode[0])) {
				return false;
			}
			$parentNode = $parentNode[0];

			if (($model->id == $parentId)) {
				return false;

			} elseif (($node[$left] < $parentNode[$left]) && ($parentNode[$right] < $node[$right])) {
				return false;
			}
			if (empty ($node[$left]) && empty ($node[$right])) {
				$this->__sync($model, 2, '+', '>= ' . $parentNode[$right], $created);
				$result = $model->save(
					array($left => $parentNode[$right], $right => $parentNode[$right] + 1, $parent => $parentId),
					array('validate' => false, 'callbacks' => false)
				);
				$model->data = $result;
			} else {
				$this->__sync($model, $edge - $node[$left] +1, '+', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right], $created);
				$diff = $node[$right] - $node[$left] + 1;

				if ($node[$left] > $parentNode[$left]) {
					if ($node[$right] < $parentNode[$right]) {
						$this->__sync($model, $diff, '-', 'BETWEEN ' . $node[$right] . ' AND ' . ($parentNode[$right] - 1), $created);
						$this->__sync($model, $edge - $parentNode[$right] + $diff + 1, '-', '> ' . $edge, $created);
					} else {
						$this->__sync($model, $diff, '+', 'BETWEEN ' . $parentNode[$right] . ' AND ' . $node[$right], $created);
						$this->__sync($model, $edge - $parentNode[$right] + 1, '-', '> ' . $edge, $created);
					}
				} else {
					$this->__sync($model, $diff, '-', 'BETWEEN ' . $node[$right] . ' AND ' . ($parentNode[$right] - 1), $created);
					$this->__sync($model, $edge - $parentNode[$right] + $diff + 1, '-', '> ' . $edge, $created);
				}
			}
		}
		return true;
	}
/**
 * get the maximum index value in the table.
 *
 * @param AppModel $model
 * @param string $scope
 * @param string $right
 * @return int
 * @access private
 */
	function __getMax($model, $scope, $right, $recursive = -1, $created = false) {
		$db =& ConnectionManager::getDataSource($model->useDbConfig);
		if ($created) {
			if (is_string($scope)) {
				$scope .= " AND {$model->alias}.{$model->primaryKey} <> ";
				$scope .= $db->value($model->id, $model->getColumnType($model->primaryKey));
			} else {
				$scope['NOT'][$model->alias . '.' . $model->primaryKey] = $model->id;
			}
		}
		list($edge) = array_values($model->find('first', array(
			'conditions' => $scope,
			'fields' => $db->calculate($model, 'max', array($right)),
			'recursive' => $recursive
		)));
		return (empty($edge[$right])) ? 0 : $edge[$right];
	}
/**
 * get the minimum index value in the table.
 *
 * @param AppModel $model
 * @param string $scope
 * @param string $right
 * @return int
 * @access private
 */
	function __getMin($model, $scope, $left, $recursive = -1) {
		$db =& ConnectionManager::getDataSource($model->useDbConfig);
		list($edge) = array_values($model->find('first', array(
			'conditions' => $scope,
			'fields' => $db->calculate($model, 'min', array($left)),
			'recursive' => $recursive
		)));
		return (empty($edge[$left])) ? 0 : $edge[$left];
	}
/**
 * Table sync method.
 *
 * Handles table sync operations, Taking account of the behavior scope.
 *
 * @param AppModel $model
 * @param integer $shift
 * @param string $direction
 * @param array $conditions
 * @param string $field
 * @access private
 */
	function __sync(&$model, $shift, $dir = '+', $conditions = array(), $created = false, $field = 'both') {
		$modelRecursive = $model->recursive;
		extract($this->settings[$model->alias]);
		$model->recursive = $recursive;

		if ($field == 'both') {
			$this->__sync($model, $shift, $dir, $conditions, $created, $left);
			$field = $right;
		}
		if (is_string($conditions)) {
			$conditions = array("{$model->alias}.{$field} {$conditions}");
		}
		if (($scope != '1 = 1' && $scope !== true) && $scope) {
			$conditions[] = $scope;
		}
		if ($created) {
			$conditions['NOT'][$model->alias . '.' . $model->primaryKey] = $model->id;
		}
		$model->updateAll(array($model->alias . '.' . $field => $model->alias . '.' . $field . ' ' . $dir . ' ' . $shift), $conditions);
		$model->recursive = $modelRecursive;
	}
}
?>