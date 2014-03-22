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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Model\Behavior;

use ArrayObject;
use Cake\Collection\Collection;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class TreeBehavior extends Behavior {

/**
 * Table instance
 *
 * @var \Cake\ORM\Table
 */
	protected $_table;

/**
 * Default config
 *
 * These are merged with user-provided configuration when the behavior is used.
 *
 * @var array
 */
	protected static $_defaultConfig = [
		'implementedFinders' => [
			'path' => 'findPath',
			'children' => 'findChildren',
		],
		'parent' => 'parent_id',
		'left' => 'lft',
		'right' => 'rght',
		'scope' => null
	];

/**
 * Constructor
 *
 * @param Table $table The table this behavior is attached to.
 * @param array $config The config for this behavior.
 */
	public function __construct(Table $table, array $config = []) {
		parent::__construct($table, $config);
		$this->_table = $table;
	}

	public function findPath($query, $options) {
		if (empty($options['for'])) {
			throw new \InvalidArgumentException("The 'for' key is required for find('path')");
		}

		$config = $this->config();
		list($left, $right) = [$config['left'], $config['right']];
		$node = $this->_table->get($options['for'], ['fields' => [$left, $right]]);

		return $this->_scope($query)
			->where([
				"$left <=" => $node->get($left),
				"$right >=" => $node->get($right)
			]);
	}

/**
 * Get the number of child nodes.
 *
 * @param integer|string $id The ID of the record to read
 * @param boolean $direct whether to count direct, or all, children
 * @return integer Number of child nodes.
 */
	public function childCount($id, $direct = false) {
		$config = $this->config();
		list($parent, $left, $right) = [$config['parent'], $config['left'], $config['right']];

		if ($direct) {
			$count = $this->_table->find()
				->where([$parent => $id])
				->count();
			return $count;
		}

		$node = $this->_table->get($id, [$this->_table->primaryKey() => $id]);

		return ($node->{$right} - $node->{$left} - 1) / 2;
	}

/**
 * Get the child nodes of the current model
 *
 * Available options are:
 *
 * - for: The ID of the record to read.
 * - direct: Boolean, whether to return only the direct (true), or all (false), children. default to false (all children).
 *
 * If the direct option is set to true, only the direct children are returned (based upon the parent_id field)
 * If false is passed for the id parameter, top level, or all (depending on direct parameter appropriate) are counted.
 *
 * @param array $options Array of options as described above
 * @return \Cake\ORM\Query
 */
	public function findChildren($query, $options) {
		extract($this->config());
		extract($options);
		$primaryKey = $this->_table->primaryKey();
		$direct = !isset($direct) ? false : $direct;

		if (empty($for)) {
			throw new \InvalidArgumentException("The 'for' key is required for find('children')");
		}

		if ($query->clause('order') === null) {
			$query->order([$left => 'ASC']);
		}

		if ($direct) {
			return $this->_scope($query)
				->where([$parent => $for]);
		}

		$node = $this->_scope($this->_table->find())
			->select([$right, $left])
			->where([$primaryKey => $for])
			->first();

		if (!$node) {
			return $query;
		}

		return $this->_scope($query)
			->where([
				"{$right} <" => $node->{$right},
				"{$left} >" => $node->{$left}
			]);
	}

/**
 * Reorder the node without changing the parent.
 *
 * If the node is the first child, or is a top level node with no previous node this method will return false
 *
 * @param integer|string $id The ID of the record to move
 * @param integer|boolean $number how many places to move the node, or true to move to first position
 * @return boolean true on success, false on failure
 */
	public function moveUp($id, $number = 1) {
		$primaryKey = $this->_table->primaryKey();
		$config = $this->config();
		extract($config);

		if (!$number) {
			return false;
		}

		$node = $this->_scope($this->_table->find())
			->select([$primaryKey, $parent, $left, $right])
			->where([$primaryKey => $id])
			->first();

		if ($node->{$parent}) {
			$parentNode = $this->_scope($this->_table->find())
				->select([$primaryKey, $left, $right])
				->where([$primaryKey => $node->{$parent}])
				->first();

			if (($node->{$left} - 1) == $parentNode->{$left}) {
				return false;
			}
		}

		$previousNode = $this->_scope($this->_table->find())
			->select([$primaryKey, $left, $right])
			->where([$right => ($node->{$left} - 1)])
			->first();

		if (!$previousNode) {
			return false;
		}

		$edge = $this->_getMax();
		$this->_sync($edge - $previousNode->{$left} + 1, '+', "BETWEEN {$previousNode->{$left}} AND {$previousNode->{$right}}");
		$this->_sync($node->{$left} - $previousNode->{$left}, '-', "BETWEEN {$node->{$left}} AND {$node->{$right}}");
		$this->_sync($edge - $previousNode->{$left} - ($node->{$right} - $node->{$left}), '-', "> {$edge}");

		if (is_int($number)) {
			$number--;
		}

		if ($number) {
			$this->moveUp($id, $number);
		}

		return true;
	}

/**
 * Reorder the node without changing the parent.
 *
 * If the node is the last child, or is a top level node with no subsequent node this method will return false
 *
 * @param integer|string $id The ID of the record to move
 * @param integer|boolean $number how many places to move the node or true to move to last position
 * @return boolean true on success, false on failure
 */
	public function moveDown($id, $number = 1) {
		$primaryKey = $this->_table->primaryKey();
		$config = $this->config();
		extract($config);

		if (!$number) {
			return false;
		}

		$node = $this->_scope($this->_table->find())
			->select([$primaryKey, $parent, $left, $right])
			->where([$primaryKey => $id])
			->first();

		if ($node->{$parent}) {
			$parentNode = $this->_scope($this->_table->find())
				->select([$primaryKey, $left, $right])
				->where([$primaryKey => $node->{$parent}])
				->first();

			if (($node->{$right} + 1) == $parentNode->{$right}) {
				return false;
			}
		}

		$nextNode = $this->_scope($this->_table->find())
			->select([$primaryKey, $left, $right])
			->where([$left => $node->{$right} + 1])
			->first();

		if (!$nextNode) {
			return false;
		}

		$edge = $this->_getMax();
		$this->_sync($edge - $node->{$left} + 1, '+', "BETWEEN {$node->{$left}} AND {$node->{$right}}");
		$this->_sync($nextNode->{$left} - $node->{$left}, '-', "BETWEEN {$nextNode->{$left}} AND {$nextNode->{$right}}");
		$this->_sync($edge - $node->{$left} - ($nextNode->{$right} - $nextNode->{$left}), '-', "> {$edge}");

		if (is_int($number)) {
			$number--;
		}

		if ($number) {
			$this->moveDown($id, $number);
		}

		return true;
	}

/**
 * Get the maximum index value in the table.
 *
 * @return integer
 */
	protected function _getMax() {
		return $this->_getMaxOrMin('max');
	}

/**
 * Get the minimum index value in the table.
 *
 * @return integer
 */
	protected function _getMin() {
		return $this->_getMaxOrMin('min');
	}

/**
 * Get the maximum|minimum index value in the table.
 *
 * @param string $maxOrMin Either 'max' or 'min'
 * @return integer
 */
	protected function _getMaxOrMin($maxOrMin = 'max') {
		extract($this->config());
		$LorR = $maxOrMin === 'max' ? $right : $left;
		$DorA = $maxOrMin === 'max' ? 'DESC' : 'ASC';

		$edge = $this->_scope($this->_table->find())
			->select([$LorR])
			->order([$LorR => $DorA])
			->first();

		if (empty($edge->{$LorR})) {
			return 0;
		}

		return $edge->{$LorR};
	}

	protected function _sync($shift, $dir = '+', $conditions = null, $field = 'both') {
		extract($this->config());

		if ($field === 'both') {
			$this->_sync($shift, $dir, $conditions, $left);
			$field = $right;
		}

		// updateAll + scope
		$exp = new QueryExpression();
		$exp->add("{$field} = ({$field} {$dir} {$shift})");

		$query = $this->_scope($this->_table->query());
		$query->update()
			->set($exp);

		if ($conditions) {
			$conditions = "{$field} {$conditions}";
			$query->where($conditions);
		}

		$statement = $query->execute();
		$success = $statement->rowCount() > 0;

		return $success;
	}

	protected function _scope($query) {
		$config = $this->config();

		if (empty($config['scope'])) {
			return $query;
		} elseif (is_array($config['scope'])) {
			return $query->where($config['scope']);
		} elseif (is_callable($config['scope'])) {
			return $config['scope']($query);
		}

		return $query;
	}
}
