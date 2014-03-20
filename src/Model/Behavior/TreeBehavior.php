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
			return $this->_table->find()
				->where([$parent => $id])
				->count();
		}

		$node = $this->_table->find()
			->select([$parent, $left, $right])
			->where([$this->_table->primaryKey() => $id])
			->first();
		$node = $this->_scope($node);

		return ($node->{$right} - $node->{$left} - 1) / 2;
	}

	protected function _scope($query) {
		$config = $this->config();

		if (empty($config['scope'])) {
			return $query;
		}

		if (!is_string($config['scope'])) {
			return $query->where($config['scope']);
		}

		$association = $this->_table->association($query['scope']);
		if (!$association) {
			throw new \InvalidArgumentException("Invalid association name for 'scope'");
		}

		return $query->matching($association->name());
	}

}