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
		'implementedFinders' => ['path' => 'findPath'],
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

	public function children($id, $direct = false, $fields = [], $order = null, $limit = null, $page = 1) {
		extract($this->config());
		$primaryKey = $this->_table->primaryKey();

		if ($direct) {
			return $this->_scope($this->_table->find())
				->where([$parent => $id])
				->all();
		}

		$node = $this->_scope($this->_table->find())
			->select([$right, $left])
			->where([$primaryKey => $id])
			->first();

		if (!$node) {
			return false;
		}

		$order = !$order ? [$left => 'ASC'] : $order;
		$query = $this->_scope($this->_table->find());

		if ($fields) {
			$query->select($fields);
		}

		$query->where([
			"{$right} <" => $node->{$right},
			"{$left} >" => $node->{$left}
		]);

		if ($limit) {
			$query->limit($limit);
		}

		if ($page) {
			$query->page($page);
		}

		return $query->order($order)->all();
	}

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
			->where([$right => ($node->{$left} - 1)]);

		$previousNode = $previousNode->first();

		if (!$previousNode) {
			return false;
		}

		$edge = $this->_getMax();
		$this->_sync($edge - $previousNode->{$left} + 1, '+', "BETWEEN {$previousNode->{$left}} AND {$previousNode->{$right}}");
		$this->_sync($node->{$left} - $previousNode->{$left}, '-', "BETWEEN {$node->{$left}} AND {$node->{$right}}");
		$this->_sync($edge - $previousNode->{$left} - ($node->{$right} - $node->{$left}), '-', "> {$edge}");

		$number--;

		if ($number) {
			$this->moveUp($id, $number);
		}

		return true;
	}

	protected function _getMax() {
		return $this->__getMaxOrMin('max');
	}

	protected function _getMin() {
		return $this->__getMaxOrMin('min');
	}

/**
 * Get the maximum index value in the table.
 *
 * @return integer
 */
	private function __getMaxOrMin($maxOrMin = 'max') {
		extract($this->config());
		$LorR = $maxOrMin == 'max' ? $right : $left;
		$DorA = $maxOrMin == 'max' ? 'DESC' : 'ASC';

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
		$query = $this->_scope($this->_table->query());
		$query->update()
			->set([$field => "{$field} {$dir} {$shift}"]);

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
