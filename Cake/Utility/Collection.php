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
use Cake\Utility\Iterator\ExtractIterator;
use Cake\Utility\Iterator\FilterIterator;
use Cake\Utility\Iterator\ReplaceIterator;
use Cake\Utility\Iterator\SortIterator;
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
 * Filtering odd numbers in an array, at the end only the value 2 will
 * be present in the resulting collection:
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

/**
 * Looks through each value in the collection, and returns another collection with
 * all the values that do not pass a truth test. This is the opposite of `filter`.
 *
 * Each time the callback is executed it will receive the value of the element
 * in the current iteration, the key of the element and this collection as
 * arguments, in that order.
 *
 * ##Example:
 *
 * Filtering even numbers in an array, at the end only values 1 and 3 will
 * be present in the resulting collection:
 *
 * {{{
 * $collection = (new Collection([1, 2, 3]))->filter(function($value, $key) {
 *	return $value % 2 === 0;
 * });
 * }}}
 *
 * @param callable $c the method that will receive each of the elements and
 * returns true whether or not they should be out of the resulting collection.
 * @return \Cake\Utility\Iterator\FilterIterator;
 */
	public function reject(callable $c) {
		return new FilterIterator($this, function ($key, $value, $items) use ($c) {
			return !$c($key, $value, $items);
		});
	}

/**
 * Returns true if all values in this collection pass the truth test provided
 * in the callback.
 *
 * Each time the callback is executed it will receive the value of the element
 * in the current iteration and  the key of the element as arguments, in that
 * order.
 *
 * ###Example:
 *
 * {{{
 * $legalAge = (new Collection([24, 45, 60, 15]))->every(function($value, $key) {
 *	return $value >= 21;
 * });
 * }}}
 *
 * @param callable $c a callback function
 * @return boolean true if for all elements in this collection the provided
 * callback returns true, false otherwise
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
 * Returns true if any of the values in this collection pass the truth test
 * provided in the callback.
 *
 * Each time the callback is executed it will receive the value of the element
 * in the current iteration and  the key of the element as arguments, in that
 * order.
 *
 * ###Example:
 *
 * {{{
 * $hasUnderAge = (new Collection([24, 45, 15]))->every(function($value, $key) {
 *	return $value < 21;
 * });
 * }}}
 *
 * @param callable $c a callback function
 * @return boolean true if for all elements in this collection the provided
 * callback returns true, false otherwise
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
 * Returns true if $value is present in this collection. Comparisons are made
 * both by value and type.
 *
 * @param mixed $value the value to check for
 * @return boolean true if $value is present in this collection
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
 * Creates an iterator from another iterator that will modify each of the values
 * by converting them using a callback function.
 */

/**
 * Returns another collection after modifying each of the values in this one using
 * the provided callable.
 *
 * Each time the callback is executed it will receive the value of the element
 * in the current iteration, the key of the element and this collection as
 * arguments, in that order.
 *
 * ##Example:
 *
 * Getting a collection of booleans where true indicates if a person is female:
 *
 * {{{
 * $collection = (new Collection($people))->filter(function($person, $key) {
 *	return $person->sex === 'female';
 * });
 * }}}
 *
 * @param callable $c the method that will receive each of the elements and
 * returns the new value for the key that is being iterated
 * @return \Cake\Utility\Iterator\ReplaceIterator
 */
	public function map(callable $c) {
		return new ReplaceIterator($this, $c);
	}

/**
 * Folds the values in this collection to a single value, as the result of
 * applying the callback function to all elements. $zero is the initial state
 * of the reduction, and each successive step should of it should be returned
 * by the callback function.
 *
 * The callback function is
 *
 * @return void
 */
	public function reduce(callable $c, $zero) {
		$result = $zero;
		foreach ($this as $k => $value) {
			$result = $c($result, $value, $k);
		}
		return $result;
	}

/**
 * Returns a new collection containing the column or property value found in each
 * of th elements, as requested in the $matcher param.
 *
 * The matcher can be a string with a property name to extract or a dot separated
 * path of properties that should be followed to get the last one in the path.
 *
 * If a column or property could not be found for a particular element in the
 * collection, that position is filled with null.
 *
 * ### Example:
 *
 * Extract the user name for all comments in the array:
 *
 * {{{
 * $items = [
 *	['comment' => ['body' => 'cool', 'user' => ['name' => 'Mark']],
 *	['comment' => ['body' => 'very cool', 'user' => ['name' => 'Renan']]
 * ];
 * $extractor = new ExtractIterator($items, 'comment.user.name'');
 * }}}
 *
 * @param string $path a dot separated string symbolizing the path to follow
 * inside the hierarchy of each value so that the column can be extracted.
 * @return \Cake\Utility\Iterator\ExtractIterator
 */
	public function extract($matcher) {
		return new ExtractIterator($this, $matcher);
	}

/**
 * Returns the top element in this collection after being sorted by a property.
 * Check method sortBy for information on the callback and $type parameters
 *
 * ###Examples:
 *
 * {{{
 * //For a collection of employees
 * $max = $collection->max('age');
 * $max = $collection->max('user.salary');
 * $max = $collection->max(function($e) {
 *	return $e->get('user')->get('salary');
 * });
 *
 * //Display employee name
 * echo $max->name;
 * }}}
 *
 * @param callable|string the callback or column name to use for sorting
 * @param integer $type the type of comparison to perform, either SORT_STRING
 * SORT_NUMERIC or SORT_NATURAL
 * @see \Cake\Utility\Collection::sortBy()
 */
	public function max($callback, $type = SORT_NUMERIC) {
		$sorted = new SortIterator($this, $callback, SORT_DESC, $type);
		return $sorted->top();
	}

/**
 * Returns the bottom element in this collection after being sorted by a property.
 * Check method sortBy for information on the callback and $type parameters
 *
 * ###Examples:
 *
 * {{{
 * //For a collection of employees
 * $min = $collection->min('age');
 * $min = $collection->min('user.salary');
 * $min = $collection->min(function($e) {
 *	return $e->get('user')->get('salary');
 * });
 *
 * //Display employee name
 * echo $min->name;
 * }}}
 *
 *
 * @param callable|string the callback or column name to use for sorting
 * @param integer $type the type of comparison to perform, either SORT_STRING
 * SORT_NUMERIC or SORT_NATURAL
 * @see \Cake\Utility\Collection::sortBy()
 */
	public function min($callback, $type = SORT_NUMERIC) {
		$sorted = new SortIterator($this, $callback, SORT_ASC, $type);
		return $sorted->top();
	}

/**
 * Returns a sorted iterator out of the elements in this colletion,
 * ranked in ascending order by the results of running each value through a
 * callback. $callback can also be a string representing the column or property
 * name.
 *
 * The callback will receive as first argument each of the elements in $items,
 * the value returned in the callback will be used as the value for sorting such
 * element. Please not that the callback function could be called more than once
 * per element.
 *
 * ###Example:
 *
 * {{{
 * $items = $collection->sortBy(function($user) {
 *	return $user->age;
 * });
 *
 * //alternatively
 * $items = $collection->sortBy('age');
 *
 * //or use a property path
 * $items = $collection->sortBy('department.name');
 *
 * // output all user name order by their age in descending order
 * foreach ($items as $user) {
 *	echo $user->name;
 * }
 * }}}
 *
 * @param callable|string the callback or column name to use for sorting
 * @param integer $dir either SORT_DESC or SORT_ASC
 * @param integer $type the type of comparison to perform, either SORT_STRING
 * SORT_NUMERIC or SORT_NATURAL
 * @return \Cake\Utility\Collection
 */
	public function sortBy($callback, $dir = SORT_DESC, $type = SORT_NUMERIC) {
		return new self(new SortIterator($this, $callback, $dir, $type));
	}

/**
 * Splits a collection into sets, grouped by the result of running each value
 * through the callback. If $callback is is a string instead of a callable,
 * groups by the property named by $callback on each of the values.
 *
 * When $callback is a string it should be a property name to extract or
 * a dot separated path of properties that should be followed to get the last
 * one in the path.
 *
 * ###Example:
 *
 * {{{
 * $items = [
 *	['id' => 1, 'name' => 'foo', 'parent_id' => 10],
 *	['id' => 2, 'name' => 'bar', 'parent_id' => 11],
 *	['id' => 3, 'name' => 'baz', 'parent_id' => 10],
 * ];
 *
 * $group = (new Collection($items))->groupBy('parent_id');
 *
 * //Or
 * $group = (new Collection($items))->groupBy(function($e) {
 *	return $e['parent_id'];
 * });
 *
 * //Result will look like
 * [
 *	10 => [
 *		['id' => 1, 'name' => 'foo', 'parent_id' => 10],
 *		['id' => 3, 'name' => 'baz', 'parent_id' => 10],
 *	],
 *	11 => [
 *		['id' => 2, 'name' => 'bar', 'parent_id' => 11],
 *	]
 * ];
 * }}}
 *
 * @param callable|string the callback or column name to use for grouping
 * or a function returning the grouping key out of the provided element
 * @return \Cake\Utility\Collection
 */
	public function groupBy($callback) {
		$callback = $this->_propertyExtractor($callback);
		$group = [];
		foreach ($this as $value) {
			$group[$callback($value)][] = $value;
		}
		return new self($group);
	}

	public function indexBy($property) {
	}

	public function countBy($property) {
	}

	public function shuffle() {
	}

	public function sample($size) {
	}

/**
 * Returns a callable that can be used to extract a property or column from
 * an array or object based on a dot separated path.
 *
 * @param string|callable $callback A dot separated path of column to follow
 * so that the final one can be returned or a callable that will take care
 * of doing that.
 * @return callable
 */
	protected function _propertyExtractor($callback) {
		if (is_string($callback)) {
			$path = $path = explode('.', $callback);
			$callback = function($element) use ($path) {
				return $this->_extract($element, $path);
			};
		}

		return $callback;
	}

/**
 * Returns a column from $data that can be extracted
 * by iterating over the column names contained in $path
 *
 * @param array|\ArrayAccess $data
 * @param array $path
 * @return mixed
 */
	protected function _extract($data, $path) {
		$value = null;
		foreach ($path as $column) {
			if (!isset($data[$column])) {
				return null;
			}
			$value = $data[$column];
			$data = $value;
		}
		return $value;
	}

}
