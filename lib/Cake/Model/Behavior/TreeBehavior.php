<?php
/**
 * Tree behavior class.
 *
 * Enables a model object to act as a node-based tree.
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Model.Behavior
 * @since         CakePHP v 1.2.0.4487
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ModelBehavior', 'Model');

/**
 * Tree Behavior.
 *
 * Enables a model object to act as a node-based tree. Using Modified Preorder Tree Traversal
 *
 * @see http://en.wikipedia.org/wiki/Tree_traversal
 * @package       Cake.Model.Behavior
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html
 */
class TreeBehavior extends ModelBehavior {

/**
 * Errors
 *
 * @var array
 */
	public $errors = array();

/**
 * Defaults
 *
 * @var array
 */
	protected $_defaults = array(
		'parent' => 'parent_id', 'left' => 'lft', 'right' => 'rght', 'level' => null,
		'scope' => '1 = 1', 'type' => 'nested', '__parentChange' => false, 'recursive' => -1
	);

/**
 * Used to preserve state between delete callbacks.
 *
 * @var array
 */
	protected $_deletedRow = array();

/**
 * Initiate Tree behavior
 *
 * @param Model $Model using this behavior of model
 * @param array $config array of configuration settings.
 * @return void
 */
	public function setup(Model $Model, $config = array()) {
		if (isset($config[0])) {
			$config['type'] = $config[0];
			unset($config[0]);
		}
		$settings = $config + $this->_defaults;

		if (in_array($settings['scope'], $Model->getAssociated('belongsTo'))) {
			$data = $Model->getAssociated($settings['scope']);
			$Parent = $Model->{$settings['scope']};
			$settings['scope'] = $Model->escapeField($data['foreignKey']) . ' = ' . $Parent->escapeField();
			$settings['recursive'] = 0;
		}
		$this->settings[$Model->alias] = $settings;
	}

/**
 * After save method. Called after all saves
 *
 * Overridden to transparently manage setting the lft and rght fields if and only if the parent field is included in the
 * parameters to be saved.
 *
 * @param Model $Model Model using this behavior.
 * @param bool $created indicates whether the node just saved was created or updated
 * @param array $options Options passed from Model::save().
 * @return bool true on success, false on failure
 */
	public function afterSave(Model $Model, $created, $options = array()) {
		extract($this->settings[$Model->alias]);
		if ($created) {
			if ((isset($Model->data[$Model->alias][$parent])) && $Model->data[$Model->alias][$parent]) {
				return $this->_setParent($Model, $Model->data[$Model->alias][$parent], $created);
			}
		} elseif ($this->settings[$Model->alias]['__parentChange']) {
			$this->settings[$Model->alias]['__parentChange'] = false;
			if ($level) {
				$this->_setChildrenLevel($Model, $Model->id);
			}
			return $this->_setParent($Model, $Model->data[$Model->alias][$parent]);
		}
	}

/**
 * Set level for descendents.
 *
 * @param Model $Model Model using this behavior.
 * @param int|string $id Record ID
 * @return void
 */
	protected function _setChildrenLevel(Model $Model, $id) {
		$settings = $Model->Behaviors->Tree->settings[$Model->alias];
		$primaryKey = $Model->primaryKey;
		$depths = array($id => (int)$Model->data[$Model->alias][$settings['level']]);

		$children = $Model->children(
			$id,
			false,
			array($primaryKey, $settings['parent'], $settings['level']),
			$settings['left'],
			null,
			1,
			-1
		);

		foreach ($children as $node) {
			$parentIdValue = $node[$Model->alias][$settings['parent']];
			$depth = (int)$depths[$parentIdValue] + 1;
			$depths[$node[$Model->alias][$primaryKey]] = $depth;

			$Model->updateAll(
				array($Model->escapeField($settings['level']) => $depth),
				array($Model->escapeField($primaryKey) => $node[$Model->alias][$primaryKey])
			);
		}
	}

/**
 * Runs before a find() operation
 *
 * @param Model $Model Model using the behavior
 * @param array $query Query parameters as set by cake
 * @return array
 */
	public function beforeFind(Model $Model, $query) {
		if ($Model->findQueryType === 'threaded' && !isset($query['parent'])) {
			$query['parent'] = $this->settings[$Model->alias]['parent'];
		}
		return $query;
	}

/**
 * Stores the record about to be deleted.
 *
 * This is used to delete child nodes in the afterDelete.
 *
 * @param Model $Model Model using this behavior.
 * @param bool $cascade If true records that depend on this record will also be deleted
 * @return bool
 */
	public function beforeDelete(Model $Model, $cascade = true) {
		extract($this->settings[$Model->alias]);
		$data = $Model->find('first', array(
			'conditions' => array($Model->escapeField($Model->primaryKey) => $Model->id),
			'fields' => array($Model->escapeField($left), $Model->escapeField($right)),
			'order' => false,
			'recursive' => -1));
		if ($data) {
			$this->_deletedRow[$Model->alias] = current($data);
		}
		return true;
	}

/**
 * After delete method.
 *
 * Will delete the current node and all children using the deleteAll method and sync the table
 *
 * @param Model $Model Model using this behavior
 * @return bool true to continue, false to abort the delete
 */
	public function afterDelete(Model $Model) {
		extract($this->settings[$Model->alias]);
		$data = $this->_deletedRow[$Model->alias];
		$this->_deletedRow[$Model->alias] = null;

		if (!$data[$right] || !$data[$left]) {
			return true;
		}
		$diff = $data[$right] - $data[$left] + 1;

		if ($diff > 2) {
			if (is_string($scope)) {
				$scope = array($scope);
			}
			$scope[][$Model->escapeField($left) . " BETWEEN ? AND ?"] = array($data[$left] + 1, $data[$right] - 1);
			$Model->deleteAll($scope);
		}
		$this->_sync($Model, $diff, '-', '> ' . $data[$right]);
		return true;
	}

/**
 * Before save method. Called before all saves
 *
 * Overridden to transparently manage setting the lft and rght fields if and only if the parent field is included in the
 * parameters to be saved. For newly created nodes with NO parent the left and right field values are set directly by
 * this method bypassing the setParent logic.
 *
 * @param Model $Model Model using this behavior
 * @param array $options Options passed from Model::save().
 * @return bool true to continue, false to abort the save
 * @see Model::save()
 */
	public function beforeSave(Model $Model, $options = array()) {
		extract($this->settings[$Model->alias]);

		$this->_addToWhitelist($Model, array($left, $right));
		if ($level) {
			$this->_addToWhitelist($Model, $level);
		}
		$parentIsSet = array_key_exists($parent, $Model->data[$Model->alias]);

		if (!$Model->id || !$Model->exists()) {
			if ($parentIsSet && $Model->data[$Model->alias][$parent]) {
				$parentNode = $this->_getNode($Model, $Model->data[$Model->alias][$parent]);
				if (!$parentNode) {
					return false;
				}

				$Model->data[$Model->alias][$left] = 0;
				$Model->data[$Model->alias][$right] = 0;
				if ($level) {
					$Model->data[$Model->alias][$level] = (int)$parentNode[$Model->alias][$level] + 1;
				}
				return true;
			}

			$edge = $this->_getMax($Model, $scope, $right, $recursive);
			$Model->data[$Model->alias][$left] = $edge + 1;
			$Model->data[$Model->alias][$right] = $edge + 2;
			if ($level) {
				$Model->data[$Model->alias][$level] = 0;
			}
			return true;
		}

		if ($parentIsSet) {
			if ($Model->data[$Model->alias][$parent] != $Model->field($parent)) {
				$this->settings[$Model->alias]['__parentChange'] = true;
			}
			if (!$Model->data[$Model->alias][$parent]) {
				$Model->data[$Model->alias][$parent] = null;
				$this->_addToWhitelist($Model, $parent);
				if ($level) {
					$Model->data[$Model->alias][$level] = 0;
				}
				return true;
			}

			$values = $this->_getNode($Model, $Model->id);
			if (empty($values)) {
				return false;
			}
			list($node) = array_values($values);

			$parentNode = $this->_getNode($Model, $Model->data[$Model->alias][$parent]);
			if (!$parentNode) {
				return false;
			}
			list($parentNode) = array_values($parentNode);

			if (($node[$left] < $parentNode[$left]) && ($parentNode[$right] < $node[$right])) {
				return false;
			}
			if ($node[$Model->primaryKey] === $parentNode[$Model->primaryKey]) {
				return false;
			}
			if ($level) {
				$Model->data[$Model->alias][$level] = (int)$parentNode[$level] + 1;
			}
		}

		return true;
	}

/**
 * Returns a single node from the tree from its primary key
 *
 * @param Model $Model Model using this behavior
 * @param int|string $id The ID of the record to read
 * @return array|bool The record read or false
 */
	protected function _getNode(Model $Model, $id) {
		$settings = $this->settings[$Model->alias];
		$fields = array($Model->primaryKey, $settings['parent'], $settings['left'], $settings['right']);
		if ($settings['level']) {
			$fields[] = $settings['level'];
		}

		return $Model->find('first', array(
			'conditions' => array($Model->escapeField() => $id),
			'fields' => $fields,
			'recursive' => $settings['recursive'],
			'order' => false,
		));
	}

/**
 * Get the number of child nodes
 *
 * If the direct parameter is set to true, only the direct children are counted (based upon the parent_id field)
 * If false is passed for the id parameter, all top level nodes are counted, or all nodes are counted.
 *
 * @param Model $Model Model using this behavior
 * @param int|string|bool $id The ID of the record to read or false to read all top level nodes
 * @param bool $direct whether to count direct, or all, children
 * @return int number of child nodes
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::childCount
 */
	public function childCount(Model $Model, $id = null, $direct = false) {
		if (is_array($id)) {
			extract(array_merge(array('id' => null), $id));
		}
		if ($id === null && $Model->id) {
			$id = $Model->id;
		} elseif (!$id) {
			$id = null;
		}
		extract($this->settings[$Model->alias]);

		if ($direct) {
			return $Model->find('count', array('conditions' => array($scope, $Model->escapeField($parent) => $id)));
		}

		if ($id === null) {
			return $Model->find('count', array('conditions' => $scope));
		} elseif ($Model->id === $id && isset($Model->data[$Model->alias][$left]) && isset($Model->data[$Model->alias][$right])) {
			$data = $Model->data[$Model->alias];
		} else {
			$data = $this->_getNode($Model, $id);
			if (!$data) {
				return 0;
			}
			$data = $data[$Model->alias];
		}
		return ($data[$right] - $data[$left] - 1) / 2;
	}

/**
 * Get the child nodes of the current model
 *
 * If the direct parameter is set to true, only the direct children are returned (based upon the parent_id field)
 * If false is passed for the id parameter, top level, or all (depending on direct parameter appropriate) are counted.
 *
 * @param Model $Model Model using this behavior
 * @param int|string $id The ID of the record to read
 * @param bool $direct whether to return only the direct, or all, children
 * @param string|array $fields Either a single string of a field name, or an array of field names
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC") defaults to the tree order
 * @param int $limit SQL LIMIT clause, for calculating items per page.
 * @param int $page Page number, for accessing paged data
 * @param int $recursive The number of levels deep to fetch associated records
 * @return array Array of child nodes
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::children
 */
	public function children(Model $Model, $id = null, $direct = false, $fields = null, $order = null, $limit = null, $page = 1, $recursive = null) {
		$options = array();
		if (is_array($id)) {
			$options = $this->_getOptions($id);
			extract(array_merge(array('id' => null), $id));
		}
		$overrideRecursive = $recursive;

		if ($id === null && $Model->id) {
			$id = $Model->id;
		} elseif (!$id) {
			$id = null;
		}

		extract($this->settings[$Model->alias]);

		if ($overrideRecursive !== null) {
			$recursive = $overrideRecursive;
		}
		if (!$order) {
			$order = $Model->escapeField($left) . " asc";
		}
		if ($direct) {
			$conditions = array($scope, $Model->escapeField($parent) => $id);
			return $Model->find('all', compact('conditions', 'fields', 'order', 'limit', 'page', 'recursive'));
		}

		if (!$id) {
			$conditions = $scope;
		} else {
			$result = array_values((array)$Model->find('first', array(
				'conditions' => array($scope, $Model->escapeField() => $id),
				'fields' => array($left, $right),
				'recursive' => $recursive,
				'order' => false,
			)));

			if (empty($result) || !isset($result[0])) {
				return array();
			}
			$conditions = array($scope,
				$Model->escapeField($right) . ' <' => $result[0][$right],
				$Model->escapeField($left) . ' >' => $result[0][$left]
			);
		}
		$options = array_merge(compact(
			'conditions', 'fields', 'order', 'limit', 'page', 'recursive'
		), $options);
		return $Model->find('all', $options);
	}

/**
 * A convenience method for returning a hierarchical array used for HTML select boxes
 *
 * @param Model $Model Model using this behavior
 * @param string|array $conditions SQL conditions as a string or as an array('field' =>'value',...)
 * @param string $keyPath A string path to the key, i.e. "{n}.Post.id"
 * @param string $valuePath A string path to the value, i.e. "{n}.Post.title"
 * @param string $spacer The character or characters which will be repeated
 * @param int $recursive The number of levels deep to fetch associated records
 * @return array An associative array of records, where the id is the key, and the display field is the value
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::generateTreeList
 */
	public function generateTreeList(Model $Model, $conditions = null, $keyPath = null, $valuePath = null, $spacer = '_', $recursive = null) {
		$overrideRecursive = $recursive;
		extract($this->settings[$Model->alias]);
		if ($overrideRecursive !== null) {
			$recursive = $overrideRecursive;
		}

		$fields = null;
		if (!$keyPath && !$valuePath && $Model->hasField($Model->displayField)) {
			$fields = array($Model->primaryKey, $Model->displayField, $left, $right);
		}

		$conditions = (array)$conditions;
		if ($scope) {
			$conditions[] = $scope;
		}

		$order = $Model->escapeField($left) . ' asc';
		$results = $Model->find('all', compact('conditions', 'fields', 'order', 'recursive'));

		return $this->formatTreeList($Model, $results, compact('keyPath', 'valuePath', 'spacer'));
	}

/**
 * Formats result of a find() call to a hierarchical array used for HTML select boxes.
 *
 * Note that when using your own find() call this expects the order to be "left" field asc in order
 * to generate the same result as using generateTreeList() directly.
 *
 * Options:
 *
 * - 'keyPath': A string path to the key, i.e. "{n}.Post.id"
 * - 'valuePath': A string path to the value, i.e. "{n}.Post.title"
 * - 'spacer': The character or characters which will be repeated
 *
 * @param Model $Model Model using this behavior
 * @param array $results Result array of a find() call
 * @param array $options Options
 * @return array An associative array of records, where the id is the key, and the display field is the value
 */
	public function formatTreeList(Model $Model, array $results, array $options = array()) {
		if (empty($results)) {
			return array();
		}
		$defaults = array(
			'keyPath' => null,
			'valuePath' => null,
			'spacer' => '_'
		);
		$options += $defaults;

		extract($this->settings[$Model->alias]);

		if (!$options['keyPath']) {
			$options['keyPath'] = '{n}.' . $Model->alias . '.' . $Model->primaryKey;
		}

		if (!$options['valuePath']) {
			$options['valuePath'] = array('%s%s', '{n}.tree_prefix', '{n}.' . $Model->alias . '.' . $Model->displayField);

		} elseif (is_string($options['valuePath'])) {
			$options['valuePath'] = array('%s%s', '{n}.tree_prefix', $options['valuePath']);

		} else {
			array_unshift($options['valuePath'], '%s' . $options['valuePath'][0], '{n}.tree_prefix');
		}

		$stack = array();

		foreach ($results as $i => $result) {
			$count = count($stack);
			while ($stack && ($stack[$count - 1] < $result[$Model->alias][$right])) {
				array_pop($stack);
				$count--;
			}
			$results[$i]['tree_prefix'] = str_repeat($options['spacer'], $count);
			$stack[] = $result[$Model->alias][$right];
		}

		return Hash::combine($results, $options['keyPath'], $options['valuePath']);
	}

/**
 * Get the parent node
 *
 * reads the parent id and returns this node
 *
 * @param Model $Model Model using this behavior
 * @param int|string $id The ID of the record to read
 * @param string|array $fields Fields to get
 * @param int $recursive The number of levels deep to fetch associated records
 * @return array|bool Array of data for the parent node
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::getParentNode
 */
	public function getParentNode(Model $Model, $id = null, $fields = null, $recursive = null) {
		$options = array();
		if (is_array($id)) {
			$options = $this->_getOptions($id);
			extract(array_merge(array('id' => null), $id));
		}
		$overrideRecursive = $recursive;
		if (empty($id)) {
			$id = $Model->id;
		}
		extract($this->settings[$Model->alias]);
		if ($overrideRecursive !== null) {
			$recursive = $overrideRecursive;
		}
		$parentId = $Model->find('first', array(
			'conditions' => array($Model->primaryKey => $id),
			'fields' => array($parent),
			'order' => false,
			'recursive' => -1
		));

		if ($parentId) {
			$parentId = $parentId[$Model->alias][$parent];
			$options = array_merge(array(
				'conditions' => array($Model->escapeField() => $parentId),
				'fields' => $fields,
				'order' => false,
				'recursive' => $recursive
			), $options);
			$parent = $Model->find('first', $options);

			return $parent;
		}
		return false;
	}

/**
 * Convenience method to create default find() options from $arg when it is an
 * associative array.
 *
 * @param array $arg Array
 * @return array Options array
 */
	protected function _getOptions($arg) {
		return count(array_filter(array_keys($arg), 'is_string') > 0) ?
			$arg :
			array();
	}

/**
 * Get the path to the given node
 *
 * @param Model $Model Model using this behavior
 * @param int|string $id The ID of the record to read
 * @param string|array $fields Either a single string of a field name, or an array of field names
 * @param int $recursive The number of levels deep to fetch associated records
 * @return array Array of nodes from top most parent to current node
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::getPath
 */
	public function getPath(Model $Model, $id = null, $fields = null, $recursive = null) {
		$options = array();
		if (is_array($id)) {
			$options = $this->_getOptions($id);
			extract(array_merge(array('id' => null), $id));
		}

		if (!empty($options)) {
			$fields = null;
			if (!empty($options['fields'])) {
				$fields = $options['fields'];
			}
			if (!empty($options['recursive'])) {
				$recursive = $options['recursive'];
			}
		}
		$overrideRecursive = $recursive;
		if (empty($id)) {
			$id = $Model->id;
		}
		extract($this->settings[$Model->alias]);
		if ($overrideRecursive !== null) {
			$recursive = $overrideRecursive;
		}
		$result = $Model->find('first', array(
			'conditions' => array($Model->escapeField() => $id),
			'fields' => array($left, $right),
			'order' => false,
			'recursive' => $recursive
		));
		if ($result) {
			$result = array_values($result);
		} else {
			return array();
		}
		$item = $result[0];
		$options = array_merge(array(
			'conditions' => array(
				$scope,
				$Model->escapeField($left) . ' <=' => $item[$left],
				$Model->escapeField($right) . ' >=' => $item[$right],
			),
			'fields' => $fields,
			'order' => array($Model->escapeField($left) => 'asc'),
			'recursive' => $recursive
		), $options);
		$results = $Model->find('all', $options);
		return $results;
	}

/**
 * Reorder the node without changing the parent.
 *
 * If the node is the last child, or is a top level node with no subsequent node this method will return false
 *
 * @param Model $Model Model using this behavior
 * @param int|string $id The ID of the record to move
 * @param int|bool $number how many places to move the node or true to move to last position
 * @return bool true on success, false on failure
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::moveDown
 */
	public function moveDown(Model $Model, $id = null, $number = 1) {
		if (is_array($id)) {
			extract(array_merge(array('id' => null), $id));
		}
		if (!$number) {
			return false;
		}
		if (empty($id)) {
			$id = $Model->id;
		}
		extract($this->settings[$Model->alias]);
		list($node) = array_values($this->_getNode($Model, $id));
		if ($node[$parent]) {
			list($parentNode) = array_values($this->_getNode($Model, $node[$parent]));
			if (($node[$right] + 1) == $parentNode[$right]) {
				return false;
			}
		}
		$nextNode = $Model->find('first', array(
			'conditions' => array($scope, $Model->escapeField($left) => ($node[$right] + 1)),
			'fields' => array($Model->primaryKey, $left, $right),
			'order' => false,
			'recursive' => $recursive)
		);
		if ($nextNode) {
			list($nextNode) = array_values($nextNode);
		} else {
			return false;
		}
		$edge = $this->_getMax($Model, $scope, $right, $recursive);
		$this->_sync($Model, $edge - $node[$left] + 1, '+', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right]);
		$this->_sync($Model, $nextNode[$left] - $node[$left], '-', 'BETWEEN ' . $nextNode[$left] . ' AND ' . $nextNode[$right]);
		$this->_sync($Model, $edge - $node[$left] - ($nextNode[$right] - $nextNode[$left]), '-', '> ' . $edge);

		if (is_int($number)) {
			$number--;
		}
		if ($number) {
			$this->moveDown($Model, $id, $number);
		}
		return true;
	}

/**
 * Reorder the node without changing the parent.
 *
 * If the node is the first child, or is a top level node with no previous node this method will return false
 *
 * @param Model $Model Model using this behavior
 * @param int|string $id The ID of the record to move
 * @param int|bool $number how many places to move the node, or true to move to first position
 * @return bool true on success, false on failure
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::moveUp
 */
	public function moveUp(Model $Model, $id = null, $number = 1) {
		if (is_array($id)) {
			extract(array_merge(array('id' => null), $id));
		}
		if (!$number) {
			return false;
		}
		if (empty($id)) {
			$id = $Model->id;
		}
		extract($this->settings[$Model->alias]);
		list($node) = array_values($this->_getNode($Model, $id));
		if ($node[$parent]) {
			list($parentNode) = array_values($this->_getNode($Model, $node[$parent]));
			if (($node[$left] - 1) == $parentNode[$left]) {
				return false;
			}
		}
		$previousNode = $Model->find('first', array(
			'conditions' => array($scope, $Model->escapeField($right) => ($node[$left] - 1)),
			'fields' => array($Model->primaryKey, $left, $right),
			'order' => false,
			'recursive' => $recursive
		));

		if ($previousNode) {
			list($previousNode) = array_values($previousNode);
		} else {
			return false;
		}
		$edge = $this->_getMax($Model, $scope, $right, $recursive);
		$this->_sync($Model, $edge - $previousNode[$left] + 1, '+', 'BETWEEN ' . $previousNode[$left] . ' AND ' . $previousNode[$right]);
		$this->_sync($Model, $node[$left] - $previousNode[$left], '-', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right]);
		$this->_sync($Model, $edge - $previousNode[$left] - ($node[$right] - $node[$left]), '-', '> ' . $edge);
		if (is_int($number)) {
			$number--;
		}
		if ($number) {
			$this->moveUp($Model, $id, $number);
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
 * @param Model $Model Model using this behavior
 * @param string $mode parent or tree
 * @param string|int $missingParentAction 'return' to do nothing and return, 'delete' to
 * delete, or the id of the parent to set as the parent_id
 * @return bool true on success, false on failure
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::recover
 */
	public function recover(Model $Model, $mode = 'parent', $missingParentAction = null) {
		if (is_array($mode)) {
			extract(array_merge(array('mode' => 'parent'), $mode));
		}
		extract($this->settings[$Model->alias]);
		$Model->recursive = $recursive;
		if ($mode === 'parent') {
			$Model->bindModel(array('belongsTo' => array('VerifyParent' => array(
				'className' => $Model->name,
				'foreignKey' => $parent,
				'fields' => array($Model->primaryKey, $left, $right, $parent),
			))));
			$missingParents = $Model->find('list', array(
				'recursive' => 0,
				'conditions' => array($scope, array(
					'NOT' => array($Model->escapeField($parent) => null), $Model->VerifyParent->escapeField() => null
				)),
				'order' => false,
			));
			$Model->unbindModel(array('belongsTo' => array('VerifyParent')));
			if ($missingParents) {
				if ($missingParentAction === 'return') {
					foreach ($missingParents as $id => $display) {
						$this->errors[] = 'cannot find the parent for ' . $Model->alias . ' with id ' . $id . '(' . $display . ')';
					}
					return false;
				} elseif ($missingParentAction === 'delete') {
					$Model->deleteAll(array($Model->escapeField($Model->primaryKey) => array_flip($missingParents)), false);
				} else {
					$Model->updateAll(array($Model->escapeField($parent) => $missingParentAction), array($Model->escapeField($Model->primaryKey) => array_flip($missingParents)));
				}
			}

			$this->_recoverByParentId($Model);
		} else {
			$db = ConnectionManager::getDataSource($Model->useDbConfig);
			foreach ($Model->find('all', array('conditions' => $scope, 'fields' => array($Model->primaryKey, $parent), 'order' => $left)) as $array) {
				$path = $this->getPath($Model, $array[$Model->alias][$Model->primaryKey]);
				$parentId = null;
				if (count($path) > 1) {
					$parentId = $path[count($path) - 2][$Model->alias][$Model->primaryKey];
				}
				$Model->updateAll(array($parent => $db->value($parentId, $parent)), array($Model->escapeField() => $array[$Model->alias][$Model->primaryKey]));
			}
		}
		return true;
	}

/**
 * _recoverByParentId
 *
 * Recursive helper function used by recover
 *
 * @param Model $Model Model instance.
 * @param int $counter Counter
 * @param mixed $parentId Parent record Id
 * @return int counter
 */
	protected function _recoverByParentId(Model $Model, $counter = 1, $parentId = null) {
		$params = array(
			'conditions' => array(
				$this->settings[$Model->alias]['parent'] => $parentId
			),
			'fields' => array($Model->primaryKey),
			'page' => 1,
			'limit' => 100,
			'order' => array($Model->primaryKey)
		);

		$scope = $this->settings[$Model->alias]['scope'];
		if ($scope && ($scope !== '1 = 1' && $scope !== true)) {
			$params['conditions'][] = $scope;
		}

		$children = $Model->find('all', $params);
		$hasChildren = (bool)$children;

		if ($parentId !== null) {
			if ($hasChildren) {
				$Model->updateAll(
					array($this->settings[$Model->alias]['left'] => $counter),
					array($Model->escapeField() => $parentId)
				);
				$counter++;
			} else {
				$Model->updateAll(
					array(
						$this->settings[$Model->alias]['left'] => $counter,
						$this->settings[$Model->alias]['right'] => $counter + 1
					),
					array($Model->escapeField() => $parentId)
				);
				$counter += 2;
			}
		}

		while ($children) {
			foreach ($children as $row) {
				$counter = $this->_recoverByParentId($Model, $counter, $row[$Model->alias][$Model->primaryKey]);
			}

			if (count($children) !== $params['limit']) {
				break;
			}
			$params['page']++;
			$children = $Model->find('all', $params);
		}

		if ($parentId !== null && $hasChildren) {
			$Model->updateAll(
				array($this->settings[$Model->alias]['right'] => $counter),
				array($Model->escapeField() => $parentId)
			);
			$counter++;
		}

		return $counter;
	}

/**
 * Reorder method.
 *
 * Reorders the nodes (and child nodes) of the tree according to the field and direction specified in the parameters.
 * This method does not change the parent of any node.
 *
 * Requires a valid tree, by default it verifies the tree before beginning.
 *
 * Options:
 *
 * - 'id' id of record to use as top node for reordering
 * - 'field' Which field to use in reordering defaults to displayField
 * - 'order' Direction to order either DESC or ASC (defaults to ASC)
 * - 'verify' Whether or not to verify the tree before reorder. defaults to true.
 *
 * @param Model $Model Model using this behavior
 * @param array $options array of options to use in reordering.
 * @return bool true on success, false on failure
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::reorder
 */
	public function reorder(Model $Model, $options = array()) {
		$options += array('id' => null, 'field' => $Model->displayField, 'order' => 'ASC', 'verify' => true);
		extract($options);
		if ($verify && !$this->verify($Model)) {
			return false;
		}
		$verify = false;
		extract($this->settings[$Model->alias]);
		$fields = array($Model->primaryKey, $field, $left, $right);
		$sort = $field . ' ' . $order;
		$nodes = $this->children($Model, $id, true, $fields, $sort, null, null, $recursive);

		$cacheQueries = $Model->cacheQueries;
		$Model->cacheQueries = false;
		if ($nodes) {
			foreach ($nodes as $node) {
				$id = $node[$Model->alias][$Model->primaryKey];
				$this->moveDown($Model, $id, true);
				if ($node[$Model->alias][$left] != $node[$Model->alias][$right] - 1) {
					$this->reorder($Model, compact('id', 'field', 'order', 'verify'));
				}
			}
		}
		$Model->cacheQueries = $cacheQueries;
		return true;
	}

/**
 * Remove the current node from the tree, and reparent all children up one level.
 *
 * If the parameter delete is false, the node will become a new top level node. Otherwise the node will be deleted
 * after the children are reparented.
 *
 * @param Model $Model Model using this behavior
 * @param int|string $id The ID of the record to remove
 * @param bool $delete whether to delete the node after reparenting children (if any)
 * @return bool true on success, false on failure
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::removeFromTree
 */
	public function removeFromTree(Model $Model, $id = null, $delete = false) {
		if (is_array($id)) {
			extract(array_merge(array('id' => null), $id));
		}
		extract($this->settings[$Model->alias]);

		list($node) = array_values($this->_getNode($Model, $id));

		if ($node[$right] == $node[$left] + 1) {
			if ($delete) {
				return $Model->delete($id);
			}
			$Model->id = $id;
			return $Model->saveField($parent, null);
		} elseif ($node[$parent]) {
			list($parentNode) = array_values($this->_getNode($Model, $node[$parent]));
		} else {
			$parentNode[$right] = $node[$right] + 1;
		}

		$db = ConnectionManager::getDataSource($Model->useDbConfig);
		$Model->updateAll(
			array($parent => $db->value($node[$parent], $parent)),
			array($Model->escapeField($parent) => $node[$Model->primaryKey])
		);
		$this->_sync($Model, 1, '-', 'BETWEEN ' . ($node[$left] + 1) . ' AND ' . ($node[$right] - 1));
		$this->_sync($Model, 2, '-', '> ' . ($node[$right]));
		$Model->id = $id;

		if ($delete) {
			$Model->updateAll(
				array(
					$Model->escapeField($left) => 0,
					$Model->escapeField($right) => 0,
					$Model->escapeField($parent) => null
				),
				array($Model->escapeField() => $id)
			);
			return $Model->delete($id);
		}
		$edge = $this->_getMax($Model, $scope, $right, $recursive);
		if ($node[$right] == $edge) {
			$edge = $edge - 2;
		}
		$Model->id = $id;
		return $Model->save(
			array($left => $edge + 1, $right => $edge + 2, $parent => null),
			array('callbacks' => false, 'validate' => false)
		);
	}

/**
 * Check if the current tree is valid.
 *
 * Returns true if the tree is valid otherwise an array of (type, incorrect left/right index, message)
 *
 * @param Model $Model Model using this behavior
 * @return mixed true if the tree is valid or empty, otherwise an array of (error type [index, node],
 *  [incorrect left/right index,node id], message)
 * @link http://book.cakephp.org/2.0/en/core-libraries/behaviors/tree.html#TreeBehavior::verify
 */
	public function verify(Model $Model) {
		extract($this->settings[$Model->alias]);
		if (!$Model->find('count', array('conditions' => $scope))) {
			return true;
		}
		$min = $this->_getMin($Model, $scope, $left, $recursive);
		$edge = $this->_getMax($Model, $scope, $right, $recursive);
		$errors = array();

		for ($i = $min; $i <= $edge; $i++) {
			$count = $Model->find('count', array('conditions' => array(
				$scope, 'OR' => array($Model->escapeField($left) => $i, $Model->escapeField($right) => $i)
			)));
			if ($count != 1) {
				if (!$count) {
					$errors[] = array('index', $i, 'missing');
				} else {
					$errors[] = array('index', $i, 'duplicate');
				}
			}
		}
		$node = $Model->find('first', array(
			'conditions' => array($scope, $Model->escapeField($right) . '< ' . $Model->escapeField($left)),
			'order' => false,
			'recursive' => 0
		));
		if ($node) {
			$errors[] = array('node', $node[$Model->alias][$Model->primaryKey], 'left greater than right.');
		}

		$Model->bindModel(array('belongsTo' => array('VerifyParent' => array(
			'className' => $Model->name,
			'foreignKey' => $parent,
			'fields' => array($Model->primaryKey, $left, $right, $parent)
		))));

		$rows = $Model->find('all', array('conditions' => $scope, 'recursive' => 0));
		foreach ($rows as $instance) {
			if ($instance[$Model->alias][$left] === null || $instance[$Model->alias][$right] === null) {
				$errors[] = array('node', $instance[$Model->alias][$Model->primaryKey],
					'has invalid left or right values');
			} elseif ($instance[$Model->alias][$left] == $instance[$Model->alias][$right]) {
				$errors[] = array('node', $instance[$Model->alias][$Model->primaryKey],
					'left and right values identical');
			} elseif ($instance[$Model->alias][$parent]) {
				if (!$instance['VerifyParent'][$Model->primaryKey]) {
					$errors[] = array('node', $instance[$Model->alias][$Model->primaryKey],
						'The parent node ' . $instance[$Model->alias][$parent] . ' doesn\'t exist');
				} elseif ($instance[$Model->alias][$left] < $instance['VerifyParent'][$left]) {
					$errors[] = array('node', $instance[$Model->alias][$Model->primaryKey],
						'left less than parent (node ' . $instance['VerifyParent'][$Model->primaryKey] . ').');
				} elseif ($instance[$Model->alias][$right] > $instance['VerifyParent'][$right]) {
					$errors[] = array('node', $instance[$Model->alias][$Model->primaryKey],
						'right greater than parent (node ' . $instance['VerifyParent'][$Model->primaryKey] . ').');
				}
			} elseif ($Model->find('count', array('conditions' => array($scope, $Model->escapeField($left) . ' <' => $instance[$Model->alias][$left], $Model->escapeField($right) . ' >' => $instance[$Model->alias][$right]), 'recursive' => 0))) {
				$errors[] = array('node', $instance[$Model->alias][$Model->primaryKey], 'The parent field is blank, but has a parent');
			}
		}
		if ($errors) {
			return $errors;
		}
		return true;
	}

/**
 * Returns the depth level of a node in the tree.
 *
 * @param Model $Model Model using this behavior
 * @param int|string $id The primary key for record to get the level of.
 * @return int|bool Integer of the level or false if the node does not exist.
 */
	public function getLevel(Model $Model, $id = null) {
		if ($id === null) {
			$id = $Model->id;
		}

		$node = $Model->find('first', array(
			'conditions' => array($Model->escapeField() => $id),
			'order' => false,
			'recursive' => -1
		));

		if (empty($node)) {
			return false;
		}

		extract($this->settings[$Model->alias]);

		return $Model->find('count', array(
			'conditions' => array(
				$scope,
				$left . ' <' => $node[$Model->alias][$left],
				$right . ' >' => $node[$Model->alias][$right]
			),
			'order' => false,
			'recursive' => -1
		));
	}

/**
 * Sets the parent of the given node
 *
 * The force parameter is used to override the "don't change the parent to the current parent" logic in the event
 * of recovering a corrupted table, or creating new nodes. Otherwise it should always be false. In reality this
 * method could be private, since calling save with parent_id set also calls setParent
 *
 * @param Model $Model Model using this behavior
 * @param int|string $parentId Parent record Id
 * @param bool $created True if newly created record else false.
 * @return bool true on success, false on failure
 */
	protected function _setParent(Model $Model, $parentId = null, $created = false) {
		extract($this->settings[$Model->alias]);
		list($node) = array_values($this->_getNode($Model, $Model->id));
		$edge = $this->_getMax($Model, $scope, $right, $recursive, $created);

		if (empty($parentId)) {
			$this->_sync($Model, $edge - $node[$left] + 1, '+', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right], $created);
			$this->_sync($Model, $node[$right] - $node[$left] + 1, '-', '> ' . $node[$left], $created);
		} else {
			$values = $this->_getNode($Model, $parentId);

			if ($values === false) {
				return false;
			}
			$parentNode = array_values($values);

			if (empty($parentNode) || empty($parentNode[0])) {
				return false;
			}
			$parentNode = $parentNode[0];

			if (($Model->id === $parentId)) {
				return false;
			} elseif (($node[$left] < $parentNode[$left]) && ($parentNode[$right] < $node[$right])) {
				return false;
			}
			if (empty($node[$left]) && empty($node[$right])) {
				$this->_sync($Model, 2, '+', '>= ' . $parentNode[$right], $created);
				$result = $Model->save(
					array($left => $parentNode[$right], $right => $parentNode[$right] + 1, $parent => $parentId),
					array('validate' => false, 'callbacks' => false)
				);
				$Model->data = $result;
			} else {
				$this->_sync($Model, $edge - $node[$left] + 1, '+', 'BETWEEN ' . $node[$left] . ' AND ' . $node[$right], $created);
				$diff = $node[$right] - $node[$left] + 1;

				if ($node[$left] > $parentNode[$left]) {
					if ($node[$right] < $parentNode[$right]) {
						$this->_sync($Model, $diff, '-', 'BETWEEN ' . $node[$right] . ' AND ' . ($parentNode[$right] - 1), $created);
						$this->_sync($Model, $edge - $parentNode[$right] + $diff + 1, '-', '> ' . $edge, $created);
					} else {
						$this->_sync($Model, $diff, '+', 'BETWEEN ' . $parentNode[$right] . ' AND ' . $node[$right], $created);
						$this->_sync($Model, $edge - $parentNode[$right] + 1, '-', '> ' . $edge, $created);
					}
				} else {
					$this->_sync($Model, $diff, '-', 'BETWEEN ' . $node[$right] . ' AND ' . ($parentNode[$right] - 1), $created);
					$this->_sync($Model, $edge - $parentNode[$right] + $diff + 1, '-', '> ' . $edge, $created);
				}
			}
		}
		return true;
	}

/**
 * get the maximum index value in the table.
 *
 * @param Model $Model Model Instance.
 * @param string $scope Scoping conditions.
 * @param string $right Right value
 * @param int $recursive Recursive find value.
 * @param bool $created Whether it's a new record.
 * @return int
 */
	protected function _getMax(Model $Model, $scope, $right, $recursive = -1, $created = false) {
		$db = ConnectionManager::getDataSource($Model->useDbConfig);
		if ($created) {
			if (is_string($scope)) {
				$scope .= " AND " . $Model->escapeField() . " <> ";
				$scope .= $db->value($Model->id, $Model->getColumnType($Model->primaryKey));
			} else {
				$scope['NOT'][$Model->alias . '.' . $Model->primaryKey] = $Model->id;
			}
		}
		$name = $Model->escapeField($right);
		list($edge) = array_values($Model->find('first', array(
			'conditions' => $scope,
			'fields' => $db->calculate($Model, 'max', array($name, $right)),
			'recursive' => $recursive,
			'order' => false,
			'callbacks' => false
		)));
		return (empty($edge[$right])) ? 0 : $edge[$right];
	}

/**
 * get the minimum index value in the table.
 *
 * @param Model $Model Model instance.
 * @param string $scope Scoping conditions.
 * @param string $left Left value.
 * @param int $recursive Recurursive find value.
 * @return int
 */
	protected function _getMin(Model $Model, $scope, $left, $recursive = -1) {
		$db = ConnectionManager::getDataSource($Model->useDbConfig);
		$name = $Model->escapeField($left);
		list($edge) = array_values($Model->find('first', array(
			'conditions' => $scope,
			'fields' => $db->calculate($Model, 'min', array($name, $left)),
			'recursive' => $recursive,
			'order' => false,
			'callbacks' => false
		)));
		return (empty($edge[$left])) ? 0 : $edge[$left];
	}

/**
 * Table sync method.
 *
 * Handles table sync operations, Taking account of the behavior scope.
 *
 * @param Model $Model Model instance.
 * @param int $shift Shift by.
 * @param string $dir Direction.
 * @param array $conditions Conditions.
 * @param bool $created Whether it's a new record.
 * @param string $field Field type.
 * @return void
 */
	protected function _sync(Model $Model, $shift, $dir = '+', $conditions = array(), $created = false, $field = 'both') {
		$ModelRecursive = $Model->recursive;
		extract($this->settings[$Model->alias]);
		$Model->recursive = $recursive;

		if ($field === 'both') {
			$this->_sync($Model, $shift, $dir, $conditions, $created, $left);
			$field = $right;
		}
		if (is_string($conditions)) {
			$conditions = array($Model->escapeField($field) . " {$conditions}");
		}
		if (($scope !== '1 = 1' && $scope !== true) && $scope) {
			$conditions[] = $scope;
		}
		if ($created) {
			$conditions['NOT'][$Model->escapeField()] = $Model->id;
		}
		$Model->updateAll(array($Model->escapeField($field) => $Model->escapeField($field) . ' ' . $dir . ' ' . $shift), $conditions);
		$Model->recursive = $ModelRecursive;
	}

}
