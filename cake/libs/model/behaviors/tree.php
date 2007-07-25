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
 * Copyright (c)	2006, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright (c) 2006, Cake Software Foundation, Inc.
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

	function setup(&$model, $config = array()) {
		$settings = am(array(
		'parent' => 'parent_id',
		'left' => 'lft',
		'right' => 'rght',
		'scope' => '1 = 1',
		'type' => 'nested'
		), $config);

		/*if (in_array($settings['scope'], $model->getAssociated('belongsTo'))) {
			$data = $model->getAssociated($settings['scope']);
			$parent =& $model->{$data['className']};
			$settings['scope'] = $model->escapeField($data['foreignKey']) . ' = ' . $parent->escapeField($parent->primaryKey, $settings['scope']);
			}*/
		$this->settings[$model->name] = $settings;
	}
/**
 * After save method. Called after all saves
 *
 * Overriden to transparently manage setting the lft and rght fields if and only if the parent field is included in the
 * parameters to be saved.
 *
 * @param AppModel $model
 * @param boolean $created indicates whether the node just saved was created or updated
 * @return boolean True on success, false on failure
 */
	function afterSave(&$model, $created) {
		extract($this->settings[$model->name]);
		if ($created) {
			if ((isset($model->data[$model->name][$parent])) && $model->data[$model->name][$parent]) {
				return $this->_setParent($model, $model->data[$model->name][$parent], true);
			} else {
				return true;
			}
		} elseif (array_key_exists($parent, $model->data[$model->name])) {
			return $this->_setParent($model, $model->data[$model->name][$parent], true);
		}
	}
/**
 * Before delete method. Called before all deletes
 *
 * Will delete the current node and all children using the deleteAll method and sync the table
 *
 * @param AppModel $model
 * @return boolean True to continue, false to abort the delete
 */
	function beforeDelete(&$model) {
		extract($this->settings[$model->name]);
		list($name, $data)= array(
		$model->name,
		$model->read());
		$data= $data[$name];

		if (!$data[$right] || !$data[$left]) {
			return true;
		}
		$diff = $data[$right] - $data[$left] + 1;
		$constraint = $scope . ' AND ' . $left . ' BETWEEN ' . $data[$left] . ' AND ' . $data[$right];
		$model->deleteAll($constraint);
		$this->__sync($model, $diff, '-', '> ' . $data[$right], $scope);
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
 * @return boolean True to continue, false to abort the save
 */
	function beforeSave(&$model) {
		extract($this->settings[$model->name]);
		if (isset($model->data[$model->name][$model->primaryKey])) {
			if ($model->data[$model->name][$model->primaryKey]) {
				if (!$model->id) {
					$model->id = $model->data[$model->name][$model->primaryKey];
				}
			}
			unset ($model->data[$model->name][$model->primaryKey]);
		}

		if (!$model->id) {
			if ((!isset($model->data[$model->name][$parent])) || (!$model->data[$model->name][$parent])) {
				$edge = $this->__getMax($model, $scope, $right);
				$model->data[$model->name][$left]= $edge +1;
				$model->data[$model->name][$right]= $edge +2;
			} else {
				$parentNode = $model->find(array($scope, $model->escapeField() => $model->data[$model->name][$parent]),
													array($model->primaryKey), null, -1);
				if (!$parentNode) {
					trigger_error(__('Trying to save a node under a none-existant node in TreeBehavior::beforeSave', E_USER_WARNING));
					return false;
				}
			}
		} elseif (isset($model->data[$model->name][$parent])) {
			if (!$model->data[$model->name][$parent]) {
				$model->data[$model->name][$parent]= null;
			} else {
				list($node) = array_values($model->find(array($scope,$model->escapeField() => $model->id),
													array($model->primaryKey, $parent, $left, $right ), null, -1));

				$parentNode = $model->find(array($scope, $model->escapeField() => $model->data[$model->name][$parent]),
													array($model->primaryKey, $left, $right), null, -1);
				if (!$parentNode) {
					trigger_error(__('Trying to save a node under a none-existant node in TreeBehavior::beforeSave', E_USER_WARNING));
					return false;
				} else {
					list($parentNode) = array_values($parentNode);
					if (($node[$left] < $parentNode[$left]) && ($parentNode[$right] < $node[$right])) {
						trigger_error(__('Trying to save a node under itself in TreeBehavior::beforeSave', E_USER_WARNING));
						return false;
					}
					elseif ($node[$model->primaryKey] == $parentNode[$model->primaryKey]) {
						trigger_error(__('Trying to set a node to be the parent of itself in TreeBehavior::beforeSave', E_USER_WARNING));
						return false;
					}
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
 * @return int number of child nodes
 * @access public
 */
	function childcount(&$model, $id = null, $direct = false) {
		if ($id === null && $model->id) {
			$id = $model->id;
		} elseif (!$id) {
			$id = null;
		}
		extract($this->settings[$model->name]);

		if ($direct) {
			return $model->findCount(array($scope, $parent => $id));
		} else {
			if ($id === null) {
				return $model->findCount($scope);
			}
			elseif (!empty ($model->data)) {
				$data = $model->data[$model->name];
			} else {
				list($data)= array_values($model->find(array($scope, $model->escapeField() => $id), null, null, -1));
			}
			return ($data[$right] - $data[$left] - 1) / 2;
		}
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
 * @param int $limit SQL LIMIT clause, for calculating items per page.
 * @param int $page Page number, for accessing paged data
 * @param int $recursive The number of levels deep to fetch associated records
 * @return array Array of child nodes
 * @access public
 */
	function children(&$model, $id = null, $direct = false, $fields = null, $order = null, $limit = null, $page = 1, $recursive = -1) {
		if ($id === null && $model->id) {
			$id = $model->id;
		} elseif (!$id) {
			$id = null;
		}
		$name = $model->name;
		extract($this->settings[$name]);

		if (!$order) {
			$order = $model->name . '.' . $left . ' asc';
		}
		if ($direct) {
			return $model->findAll(array($scope, $parent => $id), $fields, $order, $limit, $page, $recursive);
		} else {
			if (!$id) {
				$constraint = $scope;
			} else {
				@list($item) = array_values($model->find(array($scope,$model->escapeField() => $id), array($left, $right ), null, -1));
				$constraint = array($scope, $right => '< ' . $item[$right], $left => '> ' . $item[$left]);
			}
			return $model->findAll($constraint, $fields, $order, $limit, $page, $recursive);
		}
	}
/**
 * Placeholder. Output multigrouped
 *
 * A means of putting mptt data in a select box (using groups?) is needed. Must still be possible to select
 * (intermediary) parents.
 *
 * @param AppModel $model
 * @param mixed $conditions SQL conditions as a string or as an array('field' =>'value',...)
 * @param string $order SQL ORDER BY conditions (e.g. "price DESC" or "name ASC")
 * @param int $limit SQL LIMIT clause, for calculating items per page
 * @param string $keyPath A string path to the key, i.e. "{n}.Post.id"
 * @param string $valuePath A string path to the value, i.e. "{n}.Post.title"
 * @param string $groupPath A string path to a value to group the elements by, i.e. "{n}.Post.category_id"
 * @return array An associative array of records, where the id is the key, and the display field is the value
 * @access public
 */
	function generateTreeList(&$model, $conditions = null, $order = null, $limit = null, $keyPath = null, $valuePath = null, $groupPath = null) {
		extract($this->settings[$model->name]);
		/*
		 $model->bindModel(
			array('hasOne'=>
			array('TreeParent'=>
			array(
			'className'=>$model->name,
			'foreignKey'=>'parent_id',
			'conditions'=>'OR 1=1 AND '.$model->escapeField($left).' BETWEEN TreeParent.'.$left.' AND TreeParent.'.$right
			)
			)
			)
			);
			$recursive = $model->recursive;
			$model->recursive = 0;
			....
			$this->recursive = $recursive;
			*/
		$result = $model->query('SELECT Node.id AS id, CONCAT( REPEAT(\'.....\', COUNT(Parent.name)-1), Node.name) AS name ' .
										'FROM ' . $model->tablePrefix . $model->table . ' As Node, ' . $model->tablePrefix . $model->table . ' As Parent ' .
										'WHERE Node.' . $left . ' BETWEEN Parent.' . $left . ' AND Parent.' . $right . ' ' .
										'GROUP BY Node.' . $model->displayField . ' ' .
										'ORDER BY Node.' . $left);
		uses('Set');
		$keys = Set::extract($result, '{n}.Node.id');
		$vals = Set::extract($result, '{n}.0.name');
		if (!empty ($keys) && !empty ($vals)) {
			$out = array();
			if ($groupPath != null) {
				$group = Set::extract($result, $groupPath);

				if (!empty ($group)) {
					$c = count($keys);
					for ($i = 0; $i < $c; $i++) {
						if (!isset($group[$i])) {
							$group[$i] = 0;
						}
						if (!isset($out[$group[$i]])) {
							$out[$group[$i]] = array();
						}
						$out[$group[$i]][$keys[$i]] = $vals[$i];
					}
					return $out;
				}
			}
			$return = array_combine($keys, $vals);
			return $return;
		}
		return null;
	}
/**
 * Get the parent node
 *
 * reads the parent id and returns this node
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to read
 * @param int $recursive The number of levels deep to fetch associated records
 * @return array Array of data for the parent node
 * @access public
 */
	function getparentnode(&$model, $id = null, $fields = null, $recursive = -1) {
		if (empty ($id)) {
			$id = $model->id;
		}
		extract($this->settings[$model->name]);
		$parentId = $model->read($parent, $id);

		if ($parentId) {
			$parentId = $parentId[$model->name][$parent];
			$parent = $model->find(array($model->name . '.' . $model->primaryKey => $parentId), $fields, null, $recursive);

			return $parent;
		} else {
			return false;
		}
	}
/**
 * Get the path to the given node
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to read
 * @param mixed $fields Either a single string of a field name, or an array of field names
 * @param int $recursive The number of levels deep to fetch associated records
 * @return array Array of nodes from top most parent to current node
 * @access public
 */
	function getpath(&$model, $id = null, $fields = null, $recursive = -1) {
		if (empty ($id)) {
			$id = $model->id;
		}
		extract($this->settings[$model->name]);
		@list($item) = array_values($model->find(array($model->name . '.' . $model->primaryKey => $id), array($left, $right)));

		if (empty ($item)) {
			return null;
		}

		$results = $model->findAll(array($scope, $model->escapeField($left) => '<= ' . $item[$left],
						$model->escapeField($right) => '>= ' . $item[$right]),
						$fields, array($model->escapeField($left) => 'asc'), null, null, $recursive);
		return $results;
	}
/**
 * Reorder the node without changing the parent.
 *
 * If the node is the last child, or is a top level node with no subsequent node this method will return false
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to move
 * @param int $number how many places to move the node
 * @return boolean True on success, false on failure
 * @access public
 */
	function moveDown(&$model, $id = null, $number = 1) {
		if (empty ($id)) {
			$id = $model->id;
		}
		extract($this->settings[$model->name]);
		list($node) = array_values($model->find(array($scope, $model->escapeField() => $id),
											array($model->primaryKey, $left, $right, $parent), null, -1));
		if ($node[$parent]) {
			list($parentNode) = array_values($model->find(array($scope,	$model->escapeField() => $node[$parent]),
											array($model->primaryKey, $left, $right), null, -1));
			if (($node[$right] + 1) == $parentNode[$right]) {
				return false;
			}
		}
		$nextNode = $model->find(array($scope, $left => ($node[$right] + 1)),
										array($model->primaryKey, $left, $right), null, -1);
		if ($nextNode) {
			list($nextNode)= array_values($nextNode);
		} else {
			return false;
		}
		$edge = $this->__getMax($model, $scope, $right);
		$this->__sync($model, $edge - $node[$left] + 1, '+', "BETWEEN {$node[$left]} AND {$node[$right]}", $scope);
		$this->__sync($model, $nextNode[$left] - $node[$left], '-', "BETWEEN {$nextNode[$left]} AND {$nextNode[$right]}", $scope);
		$this->__sync($model, $edge - $node[$left] - ($nextNode[$right] - $nextNode[$left]), '-', "> $edge", $scope);
		if ($number > 1) {
			return $this->moveDown($model, $number - 1);
		} else {
			return true;
		}
	}
/**
 * Reorder the node without changing the parent.
 *
 * If the node is the first child, or is a top level node with no previous node this method will return false
 *
 * @param AppModel $model
 * @param mixed $id The ID of the record to move
 * @param int $number how many places to move the node
 * @return boolean True on success, false on failure
 * @access public
 */
	function moveUp(&$model, $id = null, $number = 1) {
		if (empty ($id)) {
			$id = $model->id;
		}
		extract($this->settings[$model->name]);
		list($node) = array_values($model->find(array($scope, $model->escapeField() => $id),
										array($model->primaryKey, $left, $right, $parent ), null, -1));
		if ($node[$parent]) {
			list($parentNode) = array_values($model->find(array( $scope, $model->escapeField() => $node[$parent]),
											array($model->primaryKey, $left, $right), null, -1));
			if (($node[$left] - 1) == $parentNode[$left]) {
				return false;
			}
		}
		$previousNode = $model->find(array($scope, $right => ($node[$left] - 1)),
		array($model->primaryKey, $left, $right), null, -1);
		if ($previousNode) {
			list($previousNode) = array_values($previousNode);
		} else {
			return false;
		}
		$edge = $this->__getMax($model, $scope, $right);
		$this->__sync($model, $edge - $previousNode[$left] +1, '+', "BETWEEN {$previousNode[$left]} AND {$previousNode[$right]}", $scope);
		$this->__sync($model, $node[$left] - $previousNode[$left], '-', "BETWEEN {$node[$left]} AND {$node[$right]}", $scope);
		$this->__sync($model, $edge - $previousNode[$left] - ($node[$right] - $node[$left]), '-', "> $edge", $scope);

		if ($number > 1) {
			return $this->moveUp($model, $number -1);
		} else {
			return true;
		}
	}
/**
 * Recover a corrupted tree
 *
 * The mode parameter is used to specify the source of info that is valid/correct. The opposite source of data
 * will be populated based upon that source of info. E.g. if the MPTT fields are corrupt or empty, with the $mode
 * 'parent' the values of the parent_id field will be used to populate the left and right fields.
 *
 * @todo Could be written to be faster, *maybe*. Ideally using a subquery and putting all the logic burden on the DB.
 * @param AppModel $model
 * @param string $mode parent or tree
 * @return boolean True on success, false on failure
 * @access public
 */
	function recover(&$model, $mode = 'parent') {
		extract($this->settings[$model->name]);
		$model->recursive = -1;
		if ($mode == 'parent') {
			$count = 1;
			foreach ($model->findAll($scope, array($model->primaryKey)) as $array) {
				$model->{$model->primaryKey} = $array[$model->name][$model->primaryKey];
				$lft = $count++;
				$rght = $count++;
				$model->save(array($left => $lft,$right => $rght));
			}
			foreach ($model->findAll($scope, array($model->primaryKey,$parent)) as $array) {
				$model->create();
				$model->id = $array[$model->name][$model->primaryKey];
				$this->_setParent($model, $array[$model->name][$parent], true);
			}
		} else {
			foreach ($model->findAll($scope, array($model->primaryKey, $parent)) as $array) {
				$path = $this->getpath($model, $array[$model->name][$model->primaryKey]);
				if ($path == null || count($path) < 2) {
					$parentId = null;
				} else {
					$parentId = $path[count($path) - 2][$model->name][$model->primaryKey];
				}
				$model->updateAll(array($parent => $parentId), array($model->primaryKey => $array[$model->name][$model->primaryKey]));
			}
		}
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
 * @return boolean True on success, false on failure
 * @access public
 */
	function removeFromTree(&$model, $id = null, $delete = false) {
		if (empty ($id)) {
			$id = $model->id;
		}
		extract($this->settings[$model->name]);
		list($node) = array_values($model->find(array($scope, $model->escapeField() => $id),
										array( $model->primaryKey, $left, $right, $parent), null, -1));
		if ($node[$right] == $node[$left] + 1) {
			return false;
		} elseif ($node[$parent]) {
			list($parentNode)= array_values($model->find(array($scope, $model->escapeField() => $node[$parent]),
											array($model->primaryKey, $left, $right), null, -1));
		} else {
			$parentNode[$right]= $node[$right] + 1;
		}
		$model->updateAll(array($parent => $node[$parent]), array($parent => $node[$model->primaryKey]));
		$this->__sync($model, 1, '-', 'BETWEEN ' . ($node[$left] + 1) . ' AND ' . ($node[$right] - 1), $scope);
		$this->__sync($model, 2, '-', '> ' . ($node[$right]), $scope);
		$model->id = $id;
		if ($delete) {
			$model->updateAll(array($left => null, $right => null, $parent => null), array($model->primaryKey => $id));
			return $model->delete($id);
		} else {
			$edge = $this->__getMax($model, $scope, $right);
			if ($node[$right] == $edge) {
				$edge = $edge - 2;
			}
			$model->id = $id;
			return $model->save(array($left => $edge + 1, $right => $edge + 2, $parent => null));
		}
	}
/**
 * Backward compatible method
 *
 * Returns true if the change is successful.
 *
 * @param AppModel $model
 * @param mixed $parentId The ID to set as the parent of the current node.
 * @return true on success
 * @access public
 */
	function setparent(&$model, $parentId = null , $created = null) {
		extract($this->settings[$model->name]);
		if ($created ===false && $parentId == $model->field($parent)) {
			return true;
		}
		return $model->saveField($parent, $parentId);
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
		extract($this->settings[$model->name]);
		if (!$model->findCount($scope)) {
			return true;
		}
		$min = $this->__getMin($model, $scope, $left);
		$edge = $this->__getMax($model, $scope, $right);
		$errors =  array();

		for ($i = $min; $i <= $edge; $i++) {
			$count = $model->findCount(array($scope, 'OR' => array($left => $i, $right => $i)));
			if ($count != 1) {
				if ($count == 0) {
					$errors[] = array('index', $i, 'missing');
				} else {
					$errors[] = array('index', $i, 'duplicate');
				}
			}
		}
		$count = $model->findCount(array($scope, $right => '< ' . $model->escapeField($left)));
		if ($count != 0) {
			$node = $model->find(array($scope, $right => '< ' . $model->escapeField($left)));
			$errors[] = array('node', $node[$model->primaryKey], 'left greater than right.');
		}

		$model->bindModel(array('belongsTo' =>
								array('VerifyParent' => array('className' => $model->name,
								'foreignKey' => $parent,
								'fields' => array($model->primaryKey, $left, $right, $parent)))));
		foreach ($model->findAll($scope, null, null, null, null, 1) as $instance) {
			if ($instance[$model->name][$parent]) {
				if (!$instance['VerifyParent'][$model->primaryKey]) {
					$errors[] = array('node', $instance[$model->name][$model->primaryKey], 'The parent node ' . $instance[$model->name][$parent] . ' doesn\'t exist');
				} elseif ($instance[$model->name][$left] < $instance['VerifyParent'][$left]) {
					$errors[]= array('node', $instance[$model->name][$model->primaryKey], 'left less than parent (node ' . $instance['VerifyParent'][$model->primaryKey] . ').');
				} elseif ($instance[$model->name][$right] > $instance['VerifyParent'][$right]) {
					$errors[]= array('node', $instance[$model->name][$model->primaryKey], 'right greater than parent (node ' . $instance['VerifyParent'][$model->primaryKey] . ').');
				}
			} elseif ($model->findCount(array($scope, $left => '< ' . $instance[$model->name][$left], $right => '> ' . $instance[$model->name][$right]))) {
				$errors[]= array('node', $instance[$model->name][$model->primaryKey], 'The parent field is blank, but has a parent');
			}
		}
		if ($errors) {
			return $errors;
		} else {
			return true;
		}
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
 * @param boolean $force process even if current parent_id is the same as the value to be saved
 * @return boolean True on success, false on failure
 * @access protected
 */
	function _setParent(&$model, $parentId = null, $force = false) {
		extract($this->settings[$model->name]);
		if (!$force && ($parentId == $model->field($parent))) {
			return false;
		}
		list($node) = array_values($model->find(array($scope, $model->escapeField() => $model->id),
									array($model->primaryKey, $parent, $left, $right), null, -1));
		$edge = $this->__getMax($model, $scope, $right);

		if (empty ($parentId)) {
			$this->__sync($model, $edge - $node[$left] + 1, '+', "BETWEEN {$node[$left]} AND {$node[$right]}", $scope);
			$this->__sync($model, $node[$right] - $node[$left] + 1, '-', "> {$node[$left]}", $scope);
		} else {
			list($parentNode)= array_values($model->find(array($scope, $model->escapeField() => $parentId),
										array($model->primaryKey, $left, $right), null, -1));
			if (empty ($parentNode)) {
				trigger_error(__('Trying to move a node under a none-existant node in TreeBehavior::_setParent', true), E_USER_WARNING);
				return false;
			}
			elseif (($model->id == $parentId)) {
				trigger_error(__('Trying to set a node to be the parent of itself in TreeBehavior::_setParent', E_USER_WARNING));
				return false;
			}
			elseif (($node[$left] < $parentNode[$left]) && ($parentNode[$right] < $node[$right])) {
				trigger_error(__('Trying to move a node under itself in TreeBehavior::_setParent', E_USER_WARNING));
				return false;
			}
			if (empty ($node[$left]) && empty ($node[$right])) {
				$this->__sync($model, 2, '+', ">= {$parentNode[$right]}", $scope);
				$model->save(array($left => $parentNode[$right], $right => $parentNode[$right] + 1, $parent => $parentId), false);
			} else {
				$this->__sync($model, $edge - $node[$left] +1, '+', "BETWEEN {$node[$left]} AND {$node[$right]}", $scope);
				$diff = $node[$right] - $node[$left] + 1;

				if ($node[$left] > $parentNode[$left]) {
					if ($node[$right] < $parentNode[$right]) {
						$this->__sync($model, $diff, '-', "BETWEEN {$node[$right]} AND " . ($parentNode[$right] - 1), $scope);
						$this->__sync($model, $edge - $parentNode[$right] + $diff + 1, '-', "> $edge", $scope);
					} else {
						$this->__sync($model, $diff, '+', "BETWEEN {$parentNode[$right]} AND {$node[$right]}", $scope);
						$this->__sync($model, $edge - $parentNode[$right] + 1, '-', "> $edge", $scope);
					}
				} else {
					$this->__sync($model, $diff, '-', "BETWEEN {$node[$right]} AND " . ($parentNode[$right] - 1), $scope);
					$this->__sync($model, $edge - $parentNode[$right] + $diff + 1, '-', "> $edge", $scope);
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
	function __getMax($model, $scope, $right) {
		list($edge) = array_values($model->find($scope, "MAX({$right}) AS {$right}", null, -1));
		return ife(empty ($edge[$right]), 0, $edge[$right]);
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
	function __getMin($model, $scope, $left) {
		list($edge) = array_values($model->find($scope, "MIN({$left}) AS {$left}", null, -1));
		return ife(empty ($edge[$left]), 0, $edge[$left]); // Is the tree empty?
}
/**
 * Table sync method.
 *
 * Handles table sync operations, Taking account of the behavior scope.
 *
 * @param AppModel $model
 * @param int $shift
 * @param string $direction
 * @param array $conditions
 * @param mixed $scope
 * @param string $field
 * @access protected
 */
	function __sync(&$model, $shift, $dir = '+', $conditions = array(), $scope = '', $field = 'both') {
		$scope = $scope == '1 = 1' ? '' : $scope;
		if ($field == 'both') {
			$this->__sync($model, $shift, $dir, $conditions, $scope, 'lft');
			$field = 'rght';
		}
		if (is_string($conditions)) {
			$conditions = array($field => $conditions);
		}
		if ($scope) {
			if (is_string($scope)) {
				$conditions[]= $scope;
			} else {
				$conditions= am($conditions, $scope);
			}
		}
		$model->updateAll(array($field => "{$field} $dir {$shift}"), $conditions);
	}
}
?>