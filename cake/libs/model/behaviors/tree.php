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
 * @subpackage		cake.cake.libs.model
 * @since			CakePHP v 1.2.0.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */
class TreeBehavior extends ModelBehavior {

	function setup(&$model, $config = array()) {
		$settings = am(array(
			'parent'	=> 'parent_id',
			'left'		=> 'lft',
			'right'		=> 'rght',
			'scope'		=> '1 = 1',
			'type'		=> 'nested'
		), $config);

		/*if (in_array($settings['scope'], $model->getAssociated('belongsTo'))) {
			$data = $model->getAssociated($settings['scope']);
			$parent =& $model->{$data['className']};
			$settings['scope'] = $model->escapeField($data['foreignKey']) . ' = ' . $parent->escapeField($parent->primaryKey, $settings['scope']);
		}*/
		$this->settings[$model->name] = $settings;
	}

/**
 * Sets the parent of the given node
 *
 * @param mixed $parent_id
 * @return boolean True on success, false on failure
 */
	function setparent(&$model, $parent_id = null) {
		extract($this->settings[$model->name]);
		list($node) = array_values($model->find(array($model->escapeField() => $model->id), array($model->primaryKey, $parent, $left, $right), null, -1));
		list($edge) = array_values($model->find(null, "MAX({$right}) AS {$right}", null, -1));
		$edge = ife(empty($edge[$right]), 0, $edge[$right]); // Is the tree empty?

		if (!empty($parent_id)) {
			@list($parentNode) = array_values($model->find(array($model->escapeField() => $parent_id), array($model->primaryKey, $left, $right), null, -1));
			if (empty($parentNode)) {
				trigger_error(__("Null parent in Tree::afterSave()", true), E_USER_WARNING);
				return null;
			}
			$offset = $parentNode[$left];

			if (!empty($node[$left]) && !empty($node[$right])) {
				$shift = ($edge - $node[$left]) + 1;
				$diff  = $node[$right] - $node[$left] + 1;
				$start = $edge + 1;

				// First, move the node (and subnodes) outside the tree
				$model->updateAll(array($right => "{$right} + {$shift}"), array($right => "BETWEEN {$node[$left]} AND {$node[$right]}"));
				$model->updateAll(array($left  => "{$left}  + {$shift}"), array($left  => "BETWEEN {$node[$left]} AND {$node[$right]}"));
				// Close the gap to the right of where the node was
				$model->updateAll(array($right => "{$right} - {$diff}"), array($right => "BETWEEN {$node[$right]} AND {$edge}"));
				$model->updateAll(array($left  => "{$left}  - {$diff}"), array($left  => "BETWEEN {$node[$right]} AND {$edge}"));
				// Open a new gap to insert the node
				$model->updateAll(array($right => "{$right} + {$diff}"), array($right => "> {$offset}"));
				$model->updateAll(array($left  => "{$left}  + {$diff}"), array($left  => "> {$offset}"));
				// Shift the node(s) into position
				$model->updateAll(array($right => "{$right} - {$shift}"), array($right => ">= {$start}"));
				$model->updateAll(array($left  => "{$left}  - {$shift}"), array($left  => ">= {$start}"));
				return;
			}
		} else {
			$offset = $edge;
		}
		$model->updateAll(array($right => "{$right} + 2"), array($right => "> {$offset}"));
		$model->updateAll(array($left  => "{$left}  + 2"), array($left  => "> {$offset}"));
		return $model->save(array($left => $offset + 1, $right => $offset + 2, $parent => $parent_id), false);
	}

	function afterSave(&$model, $created) {
		extract($this->settings[$model->name]);
		if ($created) {
			if (!isset($model->data[$model->name][$parent])) {
				$model->data[$model->name][$parent] = null;
			}
			return $this->setparent($model, $model->data[$model->name][$parent]);
		}
	}

	function beforeDelete(&$model) {
		extract($this->settings[$model->name]);
		list($name, $data) = array($model->name, $model->read());
		$data = $data[$name];
		$diff = $data[$right] - $data[$left] + 1;

		$model->deleteAll(array($scope, $left => '> ' . $data[$left], $right => '< ' . $data[$right]));
		$model->updateAll(array($left => "{$left} - {$diff}"), array($scope, $left => '>= ' . $data[$right]));
		$model->updateAll(array($right => "{$right} - {$diff}"), array($scope, $right => '>= ' . $data[$right]));
	}
/**
 * Get the child nodes of the current model
 *
 * @return array
 */
	function children(&$model) {
		$name = $model->name;
		extract($this->settings[$name]);
		@list($item) = array_values($model->find(array($model->escapeField() => $model->id), array($model->primaryKey, $left, $right), null, -1));
		return $model->findAll(array($right => '< ' . $item[$right], $left  => '> ' . $item[$left]));
	}
/**
 * Get the number of child nodes contained by the current model
 *
 * @return int
 */
	function childcount(&$model) {
		extract($this->settings[$model->name]);
		if (!empty($model->data)) {
			$data = $model->data[$model->name];
		} else {
			list($data) = array_values($model->find(array($model->escapeField() => $model->id), null, null, -1));
		}
		return ($data[$right] - $data[$left] - 1) / 2;
	}
/**
 * Get the parent node of the given Aro or Aco
 *
 * @param mixed $id
 * @return array
 */
	function getparentnode(&$model, $id = null) {
		if (empty($id)) {
			$id = $model->id;
		}
		$path = $this->getPath($model, $id);
		if ($path == null || count($path) < 2) {
			return null;
		} else {
			return $path[count($path) - 2];
		}
	}
/**
 * Gets the path to the given Aro or Aco
 *
 * @param mixed $id
 * @return array
 */
	function getpath(&$model, $id) {
		extract($this->settings[$model->name]);
		$rec = $model->recursive;
		$model->recursive = -1;
		@list($item) = array_values($model->read(null, $id));

		if (empty($item)) {
			return null;
		}

		$results = $model->findAll(
			array($model->escapeField($left) => '<= ' . $item[$left], $model->escapeField($right) => '>= ' . $item[$right]),
			null, array($model->escapeField($left) => 'asc'), null, null, 0
		);
		$model->recursive = $rec;
		return $results;
	}
}

?>