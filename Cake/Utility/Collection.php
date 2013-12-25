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
namespace Cake\Utility;

use ArrayIterator;
use Cake\Utility\Iterator\FilterIterator;
use InvalidArgumentException;
use IteratorIterator;

class Collection extends IteratorIterator {

	public function __construct($items) {
		if (is_array($items)) {
			$items = new ArrayIterator($items);
		}

		if (!($items instanceof \Traversable)) {
			throw new InvalidArgumentException;
		}
		parent::__construct($items);
	}

	public function getIterator() {
		return $this->_iterator;
	}

/**
 * Executes the passed callable for each of the elements in this collection
 * and passes both the value and key for them on each step.
 * Returns the same collection for chaining.
 *
 * ###Example:
 *
 * {{{
 * $collection = (new Collection($items))->each(function($value, $key) {
 *	echo "Element $key: $value";
 * });
 * }}}
 *
 * @param callable $c callable function that will receive each of the elements
 * in this collection
 * @return \Cake\Utility\Collection
 */
	public function each(callable $c) {
		foreach ($this as $k => $v) {
			$c($v, $k);
		}
		return $this;
	}

/**
 * Looks through each value in the collection, and returns another collection with
 * all the values that pass a truth test. Only the values for which the callback
 * returns true will be present in the resulting collection.
 *
 * Each time the callback is executed it will receive the value of the element
 * in the current iteration, the key of the element and this collection as
 * arguments, in that order.
 *
 * ##Example:
 *
 * Filtering even numbers in an array:
 *
 * {{{
 * $collection = (new Collection([1, 2, 3]))->filter(function($value, $key) {
 *	return $value % 2 === 0;
 * });
 * }}}
 *
 * @param callable $c the method that will receive each of the elements and
 * returns true whether or not they should be in the resulting collection.
 * @return \Cake\Utility\Iterator\FilterIterator;
 */
	public function filter(callable $c) {
		return new FilterIterator($this, $c);
	}

	public function some(callable $c) {
	}

	public function every(callable $c) {
	}

	public function contains(callable $c) {
	}

	public function mapReduce(callable $map, callable $reduce) {
	}

	public function extract($property) {
	}

	public function max() {
	}

	public function min() {
	}

	public function sortBy($property) {
	}

	public function groupBy($property) {
	}

	public function indexBy($property) {
	}

	public function countBy($property) {
	}

	public function shuffle() {
	}

	public function sample($size) {
	}

}
