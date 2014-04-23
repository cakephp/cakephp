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
namespace Cake\Collection\Iterator;

use Cake\Collection\ExtractTrait;
use SplHeap;

/**
 * An iterator that will return the passed items in order. The order is given by
 * the value returned in a callback function that maps each of the elements.
 *
 * ###Example:
 *
 * {{{
 * $items = [$user1, $user2, $user3];
 * $sorted = new SortIterator($items, function($user) {
 *	return $user->age;
 * });
 *
 * // output all user name order by their age in descending order
 * foreach ($sorted as $user) {
 *	echo $user->name;
 * }
 * }}}
 *
 * This iterator does not preserve the keys passed in the original elements.
 */
class SortIterator extends SplHeap {

	use ExtractTrait;

/**
 * Original items passed to this iterator
 *
 * @var array|\Traversable
 */
	protected $_items;

/**
 * The callback used to extract the column or property from the elements
 *
 * @var callable
 */
	protected $_callback;

/**
 * The direction in which the elements should be sorted. The constants
 * `SORT_ASC` and `SORT_DESC` are the accepted values
 *
 * @var string
 */
	protected $_dir;

/**
 * The type of sort comparison to perform.
 *
 * @var string
 */
	protected $_type;

/**
 * Wraps this iterator around the passed items so when iterated they are returned
 * in order.
 *
 * The callback will receive as first argument each of the elements in $items,
 * the value returned in the callback will be used as the value for sorting such
 * element. Please not that the callback function could be called more than once
 * per element.
 *
 * @param array|\Traversable $items The values to sort
 * @param callable|string $callback A function used to return the actual value to
 * be compared. It can also be a string representing the path to use to fetch a
 * column or property in each element
 * @param int $dir either SORT_DESC or SORT_ASC
 * @param int $type the type of comparison to perform, either SORT_STRING
 * SORT_NUMERIC or SORT_NATURAL
 */
	public function __construct($items, $callback, $dir = SORT_DESC, $type = SORT_NUMERIC) {
		$this->_items = $items;
		$this->_dir = $dir;
		$this->_type = $type;
		$this->_callback = $this->_propertyExtractor($callback);
	}

/**
 * The comparison function used to sort the elements
 *
 * @param mixed $a an element in the list
 * @param mixed $b an element in the list
 * @return int
 */
	public function compare($a, $b) {
		if ($this->_dir === SORT_ASC) {
			list($a, $b) = [$b, $a];
		}

		$callback = $this->_callback;
		$a = $callback($a);
		$b = $callback($b);

		if ($this->_type === SORT_NUMERIC) {
			return $a - $b;
		}

		if ($this->_type === SORT_NATURAL) {
			return strnatcmp($a, $b);
		}

		if ($this->_type === SORT_STRING) {
			return strcmp($a, $b);
		}

		return strcoll($a, $b);
	}

/**
 * Returns the top of the heap. Rewinds the iterator if the heap is empty.
 *
 * @return mixed
 */
	public function top() {
		if ($this->isEmpty()) {
			$this->rewind();
		}
		if ($this->isEmpty()) {
			return null;
		}
		return parent::top();
	}

/**
 * SplHeap removes elements upon iteration. Implementing rewind so that
 * this iterator can be reused, at least at a cost.
 *
 * @return void
 */
	public function rewind() {
		foreach ($this->_items as $item) {
			$this->insert($item);
		}
	}

}
