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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection;

use AppendIterator;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Collection\Iterator\BufferedIterator;
use Cake\Collection\Iterator\ExtractIterator;
use Cake\Collection\Iterator\FilterIterator;
use Cake\Collection\Iterator\InsertIterator;
use Cake\Collection\Iterator\MapReduce;
use Cake\Collection\Iterator\NestIterator;
use Cake\Collection\Iterator\ReplaceIterator;
use Cake\Collection\Iterator\SortIterator;
use Cake\Collection\Iterator\TreeIterator;
use LimitIterator;

/**
 * Offers a handful of method to manipulate iterators
 */
trait CollectionTrait {

	use ExtractTrait;

/**
 * {@inheritDoc}
 *
 */
	public function each(callable $c) {
		foreach ($this as $k => $v) {
			$c($v, $k);
		}
		return $this;
	}

/**
 * {@inheritDoc}
 *
 * @return \Cake\Collection\Iterator\FilterIterator
 */
	public function filter(callable $c = null) {
		if ($c === null) {
			$c = function ($v) {
				return (bool)$v;
			};
		}
		return new FilterIterator($this, $c);
	}

/**
 * {@inheritDoc}
 *
 * @return \Cake\Collection\Iterator\FilterIterator
 */
	public function reject(callable $c) {
		return new FilterIterator($this, function ($key, $value, $items) use ($c) {
			return !$c($key, $value, $items);
		});
	}

/**
 * {@inheritDoc}
 *
 */
	public function every(callable $c) {
		foreach ($this as $key => $value) {
			if (!$c($value, $key)) {
				return false;
			}
		}
		return true;
	}

/**
 * {@inheritDoc}
 *
 */
	public function some(callable $c) {
		foreach ($this as $key => $value) {
			if ($c($value, $key) === true) {
				return true;
			}
		}
		return false;
	}

/**
 * {@inheritDoc}
 *
 */
	public function contains($value) {
		foreach ($this as $v) {
			if ($value === $v) {
				return true;
			}
		}
		return false;
	}

/**
 * {@inheritDoc}
 *
 * @return \Cake\Collection\Iterator\ReplaceIterator
 */
	public function map(callable $c) {
		return new ReplaceIterator($this, $c);
	}

/**
 * {@inheritDoc}
 *
 */
	public function reduce(callable $c, $zero) {
		$result = $zero;
		foreach ($this as $k => $value) {
			$result = $c($result, $value, $k);
		}
		return $result;
	}

/**
 * {@inheritDoc}
 *
 * @return \Cake\Collection\Iterator\ExtractIterator
 */
	public function extract($matcher) {
		return new ExtractIterator($this, $matcher);
	}

/**
 * {@inheritDoc}
 *
 */
	public function max($callback, $type = SORT_NUMERIC) {
		$sorted = new SortIterator($this, $callback, SORT_DESC, $type);
		return $sorted->top();
	}

/**
 * {@inheritDoc}
 *
 */
	public function min($callback, $type = SORT_NUMERIC) {
		$sorted = new SortIterator($this, $callback, SORT_ASC, $type);
		return $sorted->top();
	}

/**
 * {@inheritDoc}
 *
 */
	public function sortBy($callback, $dir = SORT_DESC, $type = SORT_NUMERIC) {
		return new Collection(new SortIterator($this, $callback, $dir, $type));
	}

/**
 * {@inheritDoc}
 *
 */
	public function groupBy($callback) {
		$callback = $this->_propertyExtractor($callback);
		$group = [];
		foreach ($this as $value) {
			$group[$callback($value)][] = $value;
		}
		return new Collection($group);
	}

/**
 * {@inheritDoc}
 *
 */
	public function indexBy($callback) {
		$callback = $this->_propertyExtractor($callback);
		$group = [];
		foreach ($this as $value) {
			$group[$callback($value)] = $value;
		}
		return new Collection($group);
	}

/**
 * {@inheritDoc}
 *
 */
	public function countBy($callback) {
		$callback = $this->_propertyExtractor($callback);

		$mapper = function ($value, $key, $mr) use ($callback) {
			$mr->emitIntermediate($value, $callback($value));
		};

		$reducer = function ($values, $key, $mr) {
			$mr->emit(count($values), $key);
		};
		return new Collection(new MapReduce($this, $mapper, $reducer));
	}

/**
 * {@inheritDoc}
 *
 */
	public function sumOf($matcher) {
		$callback = $this->_propertyExtractor($matcher);
		$sum = 0;
		foreach ($this as $k => $v) {
			$sum += $callback($v, $k);
		}

		return $sum;
	}

/**
 * {@inheritDoc}
 *
 */
	public function shuffle() {
		$elements = iterator_to_array($this);
		shuffle($elements);
		return new Collection($elements);
	}

/**
 * {@inheritDoc}
 *
 */
	public function sample($size = 10) {
		return new Collection(new LimitIterator($this->shuffle(), 0, $size));
	}

/**
 * {@inheritDoc}
 *
 */
	public function take($size = 1, $from = 0) {
		return new Collection(new LimitIterator($this, $from, $size));
	}

/**
 * {@inheritDoc}
 *
 */
	public function match(array $conditions) {
		$matchers = [];
		foreach ($conditions as $property => $value) {
			$extractor = $this->_propertyExtractor($property);
			$matchers[] = function ($v) use ($extractor, $value) {
				return $extractor($v) == $value;
			};
		}

		$filter = function ($value) use ($matchers) {
			$valid = true;
			foreach ($matchers as $match) {
				$valid = $valid && $match($value);
			}
			return $valid;
		};
		return $this->filter($filter);
	}

/**
 * {@inheritDoc}
 *
 */
	public function firstMatch(array $conditions) {
		return $this->match($conditions)->first();
	}

/**
 * {@inheritDoc}
 *
 */
	public function first() {
		foreach ($this->take(1) as $result) {
			return $result;
		}
	}

/**
 * {@inheritDoc}
 *
 */
	public function append($items) {
		$list = new AppendIterator;
		$list->append($this);
		$list->append(new Collection($items));
		return new Collection($list);
	}

/**
 * {@inheritDoc}
 *
 */
	public function combine($keyPath, $valuePath, $groupPath = null) {
		$options = [
			'keyPath' => $this->_propertyExtractor($keyPath),
			'valuePath' => $this->_propertyExtractor($valuePath),
			'groupPath' => $groupPath ? $this->_propertyExtractor($groupPath) : null
		];

		$mapper = function ($value, $key, $mapReduce) use ($options) {
			$rowKey = $options['keyPath'];
			$rowVal = $options['valuePath'];

			if (!($options['groupPath'])) {
				$mapReduce->emit($rowVal($value, $key), $rowKey($value, $key));
				return;
			}

			$key = $options['groupPath']($value, $key);
			$mapReduce->emitIntermediate(
				[$rowKey($value, $key) => $rowVal($value, $key)],
				$key
			);
		};

		$reducer = function ($values, $key, $mapReduce) {
			$result = [];
			foreach ($values as $value) {
				$result += $value;
			}
			$mapReduce->emit($result, $key);
		};

		return new Collection(new MapReduce($this, $mapper, $reducer));
	}

/**
 * {@inheritDoc}
 *
 */
	public function nest($idPath, $parentPath) {
		$parents = [];
		$idPath = $this->_propertyExtractor($idPath);
		$parentPath = $this->_propertyExtractor($parentPath);
		$isObject = !is_array((new Collection($this))->first());

		$mapper = function ($row, $key, $mapReduce) use (&$parents, $idPath, $parentPath) {
			$row['children'] = [];
			$id = $idPath($row, $key);
			$parentId = $parentPath($row, $key);
			$parents[$id] =& $row;
			$mapReduce->emitIntermediate($id, $parentId);
		};

		$reducer = function ($values, $key, $mapReduce) use (&$parents, $isObject) {
			if (empty($key) || !isset($parents[$key])) {
				foreach ($values as $id) {
					$parents[$id] = $isObject ? $parents[$id] : new ArrayObject($parents[$id]);
					$mapReduce->emit($parents[$id]);
				}
				return;
			}

			foreach ($values as $id) {
				$parents[$key]['children'][] =& $parents[$id];
			}
		};

		$collection = new MapReduce($this, $mapper, $reducer);
		if (!$isObject) {
			$collection = (new Collection($collection))->map(function ($value) {
				return (array)$value;
			});
		}

		return new Collection($collection);
	}

/**
 * {@inheritDoc}
 *
 * @return \Cake\Collection\Iterator\InsertIterator
 */
	public function insert($path, $values) {
		return new InsertIterator($this, $path, $values);
	}

/**
 * {@inheritDoc}
 *
 */
	public function toArray($preserveKeys = true) {
		return iterator_to_array($this, $preserveKeys);
	}

/**
 * {@inheritDoc}
 *
 */
	public function jsonSerialize() {
		return $this->toArray();
	}

/**
 * {@inheritDoc}
 *
 */
	public function compile($preserveKeys = true) {
		return new Collection($this->toArray($preserveKeys));
	}

/**
 * {@inheritDoc}
 *
 * @return \Cake\Collection\Iterator\BufferedIterator
 */
	public function buffered() {
		return new BufferedIterator($this);
	}

/**
 * {@inheritDoc}
 *
 * @return \Cake\Collection\Iterator\TreeIterator
 */
	public function listNested($dir = 'desc', $nestingKey = 'children') {
		$dir = strtolower($dir);
		$modes = [
			'desc' => TreeIterator::SELF_FIRST,
			'asc' => TreeIterator::CHILD_FIRST,
			'leaves' => TreeIterator::LEAVES_ONLY
		];
		return new TreeIterator(
			new NestIterator($this, $nestingKey),
			isset($modes[$dir]) ? $modes[$dir] : $dir
		);
	}

}
