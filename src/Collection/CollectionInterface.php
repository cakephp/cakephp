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

use Iterator;
use JsonSerializable;

/**
 * Describes the methods a Collection should implement. A collection is an immutable
 * list of elements exposing a number of traversing and extracting method for
 * generating other collections.
 */
interface CollectionInterface extends Iterator, JsonSerializable
{

    /**
     * Executes the passed callable for each of the elements in this collection
     * and passes both the value and key for them on each step.
     * Returns the same collection for chaining.
     *
     * ### Example:
     *
     * ```
     * $collection = (new Collection($items))->each(function ($value, $key) {
     *  echo "Element $key: $value";
     * });
     * ```
     *
     * @param callable $c callable function that will receive each of the elements
     * in this collection
     * @return \Cake\Collection\CollectionInterface
     */
    public function each(callable $c);

    /**
     * Looks through each value in the collection, and returns another collection with
     * all the values that pass a truth test. Only the values for which the callback
     * returns true will be present in the resulting collection.
     *
     * Each time the callback is executed it will receive the value of the element
     * in the current iteration, the key of the element and this collection as
     * arguments, in that order.
     *
     * ### Example:
     *
     * Filtering odd numbers in an array, at the end only the value 2 will
     * be present in the resulting collection:
     *
     * ```
     * $collection = (new Collection([1, 2, 3]))->filter(function ($value, $key) {
     *  return $value % 2 === 0;
     * });
     * ```
     *
     * @param callable|null $c the method that will receive each of the elements and
     *   returns true whether or not they should be in the resulting collection.
     *   If left null, a callback that filters out falsey values will be used.
     * @return \Cake\Collection\CollectionInterface
     */
    public function filter(callable $c = null);

    /**
     * Looks through each value in the collection, and returns another collection with
     * all the values that do not pass a truth test. This is the opposite of `filter`.
     *
     * Each time the callback is executed it will receive the value of the element
     * in the current iteration, the key of the element and this collection as
     * arguments, in that order.
     *
     * ### Example:
     *
     * Filtering even numbers in an array, at the end only values 1 and 3 will
     * be present in the resulting collection:
     *
     * ```
     * $collection = (new Collection([1, 2, 3]))->reject(function ($value, $key) {
     *  return $value % 2 === 0;
     * });
     * ```
     *
     * @param callable $c the method that will receive each of the elements and
     * returns true whether or not they should be out of the resulting collection.
     * @return \Cake\Collection\CollectionInterface
     */
    public function reject(callable $c);

    /**
     * Returns true if all values in this collection pass the truth test provided
     * in the callback.
     *
     * Each time the callback is executed it will receive the value of the element
     * in the current iteration and  the key of the element as arguments, in that
     * order.
     *
     * ### Example:
     *
     * ```
     * $overTwentyOne = (new Collection([24, 45, 60, 15]))->every(function ($value, $key) {
     *  return $value > 21;
     * });
     * ```
     *
     * @param callable $c a callback function
     * @return bool true if for all elements in this collection the provided
     * callback returns true, false otherwise
     */
    public function every(callable $c);

    /**
     * Returns true if any of the values in this collection pass the truth test
     * provided in the callback.
     *
     * Each time the callback is executed it will receive the value of the element
     * in the current iteration and the key of the element as arguments, in that
     * order.
     *
     * ### Example:
     *
     * ```
     * $hasYoungPeople = (new Collection([24, 45, 15]))->every(function ($value, $key) {
     *  return $value < 21;
     * });
     * ```
     *
     * @param callable $c a callback function
     * @return bool true if the provided callback returns true for any element in this
     * collection, false otherwise
     */
    public function some(callable $c);

    /**
     * Returns true if $value is present in this collection. Comparisons are made
     * both by value and type.
     *
     * @param mixed $value The value to check for
     * @return bool true if $value is present in this collection
     */
    public function contains($value);

    /**
     * Returns another collection after modifying each of the values in this one using
     * the provided callable.
     *
     * Each time the callback is executed it will receive the value of the element
     * in the current iteration, the key of the element and this collection as
     * arguments, in that order.
     *
     * ### Example:
     *
     * Getting a collection of booleans where true indicates if a person is female:
     *
     * ```
     * $collection = (new Collection($people))->map(function ($person, $key) {
     *  return $person->gender === 'female';
     * });
     * ```
     *
     * @param callable $c the method that will receive each of the elements and
     * returns the new value for the key that is being iterated
     * @return \Cake\Collection\CollectionInterface
     */
    public function map(callable $c);

    /**
     * Folds the values in this collection to a single value, as the result of
     * applying the callback function to all elements. $zero is the initial state
     * of the reduction, and each successive step of it should be returned
     * by the callback function.
     * If $zero is omitted the first value of the collection will be used in its place
     * and reduction will start from the second item.
     *
     * @param callable $c The callback function to be called
     * @param mixed $zero The state of reduction
     * @return void
     */
    public function reduce(callable $c, $zero = null);

    /**
     * Returns a new collection containing the column or property value found in each
     * of the elements, as requested in the $matcher param.
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
     * ```
     * $items = [
     *  ['comment' => ['body' => 'cool', 'user' => ['name' => 'Mark']],
     *  ['comment' => ['body' => 'very cool', 'user' => ['name' => 'Renan']]
     * ];
     * $extracted = (new Collection($items))->extract('comment.user.name');
     *
     * // Result will look like this when converted to array
     * ['Mark', 'Renan']
     * ```
     *
     * It is also possible to extract a flattened collection out of nested properties
     *
     * ```
     *  $items = [
     *      ['comment' => ['votes' => [['value' => 1], ['value' => 2], ['value' => 3]]],
     *      ['comment' => ['votes' => [['value' => 4]]
     * ];
     * $extracted = (new Collection($items))->extract('comment.votes.{*}.value');
     *
     * // Result will contain
     * [1, 2, 3, 4]
     * ```
     *
     * @param string $matcher a dot separated string symbolizing the path to follow
     * inside the hierarchy of each value so that the column can be extracted.
     * @return \Cake\Collection\CollectionInterface
     */
    public function extract($matcher);

    /**
     * Returns the top element in this collection after being sorted by a property.
     * Check the sortBy method for information on the callback and $type parameters
     *
     * ### Examples:
     *
     * ```
     * // For a collection of employees
     * $max = $collection->max('age');
     * $max = $collection->max('user.salary');
     * $max = $collection->max(function ($e) {
     *  return $e->get('user')->get('salary');
     * });
     *
     * // Display employee name
     * echo $max->name;
     * ```
     *
     * @param callable|string $callback the callback or column name to use for sorting
     * @param int $type the type of comparison to perform, either SORT_STRING
     * SORT_NUMERIC or SORT_NATURAL
     * @see \Cake\Collection\CollectionIterface::sortBy()
     * @return mixed The value of the top element in the collection
     */
    public function max($callback, $type = SORT_NUMERIC);

    /**
     * Returns the bottom element in this collection after being sorted by a property.
     * Check the sortBy method for information on the callback and $type parameters
     *
     * ### Examples:
     *
     * ```
     * // For a collection of employees
     * $min = $collection->min('age');
     * $min = $collection->min('user.salary');
     * $min = $collection->min(function ($e) {
     *  return $e->get('user')->get('salary');
     * });
     *
     * // Display employee name
     * echo $min->name;
     * ```
     *
     * @param callable|string $callback the callback or column name to use for sorting
     * @param int $type the type of comparison to perform, either SORT_STRING
     * SORT_NUMERIC or SORT_NATURAL
     * @see \Cake\Collection\CollectionInterface::sortBy()
     * @return mixed The value of the bottom element in the collection
     */
    public function min($callback, $type = SORT_NUMERIC);

    /**
     * Returns a sorted iterator out of the elements in this collection,
     * ranked in ascending order by the results of running each value through a
     * callback. $callback can also be a string representing the column or property
     * name.
     *
     * The callback will receive as its first argument each of the elements in $items,
     * the value returned by the callback will be used as the value for sorting such
     * element. Please note that the callback function could be called more than once
     * per element.
     *
     * ### Example:
     *
     * ```
     * $items = $collection->sortBy(function ($user) {
     *  return $user->age;
     * });
     *
     * // alternatively
     * $items = $collection->sortBy('age');
     *
     * // or use a property path
     * $items = $collection->sortBy('department.name');
     *
     * // output all user name order by their age in descending order
     * foreach ($items as $user) {
     *  echo $user->name;
     * }
     * ```
     *
     * @param callable|string $callback the callback or column name to use for sorting
     * @param int $dir either SORT_DESC or SORT_ASC
     * @param int $type the type of comparison to perform, either SORT_STRING
     * SORT_NUMERIC or SORT_NATURAL
     * @return \Cake\Collection\CollectionInterface
     */
    public function sortBy($callback, $dir = SORT_DESC, $type = SORT_NUMERIC);

    /**
     * Splits a collection into sets, grouped by the result of running each value
     * through the callback. If $callback is a string instead of a callable,
     * groups by the property named by $callback on each of the values.
     *
     * When $callback is a string it should be a property name to extract or
     * a dot separated path of properties that should be followed to get the last
     * one in the path.
     *
     * ### Example:
     *
     * ```
     * $items = [
     *  ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
     *  ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
     *  ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
     * ];
     *
     * $group = (new Collection($items))->groupBy('parent_id');
     *
     * // Or
     * $group = (new Collection($items))->groupBy(function ($e) {
     *  return $e['parent_id'];
     * });
     *
     * // Result will look like this when converted to array
     * [
     *  10 => [
     *      ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
     *      ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
     *  ],
     *  11 => [
     *      ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
     *  ]
     * ];
     * ```
     *
     * @param callable|string $callback the callback or column name to use for grouping
     * or a function returning the grouping key out of the provided element
     * @return \Cake\Collection\CollectionInterface
     */
    public function groupBy($callback);

    /**
     * Given a list and a callback function that returns a key for each element
     * in the list (or a property name), returns an object with an index of each item.
     * Just like groupBy, but for when you know your keys are unique.
     *
     * When $callback is a string it should be a property name to extract or
     * a dot separated path of properties that should be followed to get the last
     * one in the path.
     *
     * ### Example:
     *
     * ```
     * $items = [
     *  ['id' => 1, 'name' => 'foo'],
     *  ['id' => 2, 'name' => 'bar'],
     *  ['id' => 3, 'name' => 'baz'],
     * ];
     *
     * $indexed = (new Collection($items))->indexBy('id');
     *
     * // Or
     * $indexed = (new Collection($items))->indexBy(function ($e) {
     *  return $e['id'];
     * });
     *
     * // Result will look like this when converted to array
     * [
     *  1 => ['id' => 1, 'name' => 'foo'],
     *  3 => ['id' => 3, 'name' => 'baz'],
     *  2 => ['id' => 2, 'name' => 'bar'],
     * ];
     * ```
     *
     * @param callable|string $callback the callback or column name to use for indexing
     * or a function returning the indexing key out of the provided element
     * @return \Cake\Collection\CollectionInterface
     */
    public function indexBy($callback);

    /**
     * Sorts a list into groups and returns a count for the number of elements
     * in each group. Similar to groupBy, but instead of returning a list of values,
     * returns a count for the number of values in that group.
     *
     * When $callback is a string it should be a property name to extract or
     * a dot separated path of properties that should be followed to get the last
     * one in the path.
     *
     * ### Example:
     *
     * ```
     * $items = [
     *  ['id' => 1, 'name' => 'foo', 'parent_id' => 10],
     *  ['id' => 2, 'name' => 'bar', 'parent_id' => 11],
     *  ['id' => 3, 'name' => 'baz', 'parent_id' => 10],
     * ];
     *
     * $group = (new Collection($items))->countBy('parent_id');
     *
     * // Or
     * $group = (new Collection($items))->countBy(function ($e) {
     *  return $e['parent_id'];
     * });
     *
     * // Result will look like this when converted to array
     * [
     *  10 => 2,
     *  11 => 1
     * ];
     * ```
     *
     * @param callable|string $callback the callback or column name to use for indexing
     * or a function returning the indexing key out of the provided element
     * @return \Cake\Collection\CollectionInterface
     */
    public function countBy($callback);

    /**
     * Returns the total sum of all the values extracted with $matcher
     * or of this collection.
     *
     * ### Example:
     *
     * ```
     * $items = [
     *  ['invoice' => ['total' => 100],
     *  ['invoice' => ['total' => 200]
     * ];
     *
     * $total = (new Collection($items))->sumOf('invoice.total');
     *
     * // Total: 300
     *
     * $total = (new Collection([1, 2, 3]))->sumOf();
     * // Total: 6
     * ```
     *
     * @param string|callable|null $matcher The property name to sum or a function
     * If no value is passed, an identity function will be used.
     * that will return the value of the property to sum.
     * @return float|int
     */
    public function sumOf($matcher = null);

    /**
     * Returns a new collection with the elements placed in a random order,
     * this function does not preserve the original keys in the collection.
     *
     * @return \Cake\Collection\CollectionInterface
     */
    public function shuffle();

    /**
     * Returns a new collection with maximum $size random elements
     * from this collection
     *
     * @param int $size the maximum number of elements to randomly
     * take from this collection
     * @return \Cake\Collection\CollectionInterface
     */
    public function sample($size = 10);

    /**
     * Returns a new collection with maximum $size elements in the internal
     * order this collection was created. If a second parameter is passed, it
     * will determine from what position to start taking elements.
     *
     * @param int $size the maximum number of elements to take from
     * this collection
     * @param int $from A positional offset from where to take the elements
     * @return \Cake\Collection\CollectionInterface
     */
    public function take($size = 1, $from = 0);

    /**
     * Returns a new collection that will skip the specified amount of elements
     * at the beginning of the iteration.
     *
     * @param int $howMany The number of elements to skip.
     * @return \Cake\Collection\CollectionInterface
     */
    public function skip($howMany);

    /**
     * Looks through each value in the list, returning a Collection of all the
     * values that contain all of the key-value pairs listed in $conditions.
     *
     * ### Example:
     *
     * ```
     * $items = [
     *  ['comment' => ['body' => 'cool', 'user' => ['name' => 'Mark']],
     *  ['comment' => ['body' => 'very cool', 'user' => ['name' => 'Renan']]
     * ];
     *
     * $extracted = (new Collection($items))->match(['user.name' => 'Renan']);
     *
     * // Result will look like this when converted to array
     * [
     *  ['comment' => ['body' => 'very cool', 'user' => ['name' => 'Renan']]
     * ]
     * ```
     *
     * @param array $conditions a key-value list of conditions where
     * the key is a property path as accepted by `Collection::extract,
     * and the value the condition against with each element will be matched
     * @return \Cake\Collection\CollectionInterface
     */
    public function match(array $conditions);

    /**
     * Returns the first result matching all of the key-value pairs listed in
     * conditions.
     *
     * @param array $conditions a key-value list of conditions where the key is
     * a property path as accepted by `Collection::extract`, and the value the
     * condition against with each element will be matched
     * @see \Cake\Collection\CollectionInterface::match()
     * @return mixed
     */
    public function firstMatch(array $conditions);

    /**
     * Returns the first result in this collection
     *
     * @return mixed The first value in the collection will be returned.
     */
    public function first();

    /**
     * Returns the last result in this collection
     *
     * @return mixed The last value in the collection will be returned.
     */
    public function last();

    /**
     * Returns a new collection as the result of concatenating the list of elements
     * in this collection with the passed list of elements
     *
     * @param array|\Traversable $items Items list.
     * @return \Cake\Collection\CollectionInterface
     */
    public function append($items);

    /**
     * Returns a new collection where the values extracted based on a value path
     * and then indexed by a key path. Optionally this method can produce parent
     * groups based on a group property path.
     *
     * ### Examples:
     *
     * ```
     * $items = [
     *  ['id' => 1, 'name' => 'foo', 'parent' => 'a'],
     *  ['id' => 2, 'name' => 'bar', 'parent' => 'b'],
     *  ['id' => 3, 'name' => 'baz', 'parent' => 'a'],
     * ];
     *
     * $combined = (new Collection($items))->combine('id', 'name');
     *
     * // Result will look like this when converted to array
     * [
     *  1 => 'foo',
     *  2 => 'bar',
     *  3 => 'baz',
     * ];
     *
     * $combined = (new Collection($items))->combine('id', 'name', 'parent');
     *
     * // Result will look like this when converted to array
     * [
     *  'a' => [1 => 'foo', 3 => 'baz'],
     *  'b' => [2 => 'bar']
     * ];
     * ```
     *
     * @param callable|string $keyPath the column name path to use for indexing
     * or a function returning the indexing key out of the provided element
     * @param callable|string $valuePath the column name path to use as the array value
     * or a function returning the value out of the provided element
     * @param callable|string|null $groupPath the column name path to use as the parent
     * grouping key or a function returning the key out of the provided element
     * @return \Cake\Collection\CollectionInterface
     */
    public function combine($keyPath, $valuePath, $groupPath = null);

    /**
     * Returns a new collection where the values are nested in a tree-like structure
     * based on an id property path and a parent id property path.
     *
     * @param callable|string $idPath the column name path to use for determining
     * whether an element is parent of another
     * @param callable|string $parentPath the column name path to use for determining
     * whether an element is child of another
     * @param string $nestingKey The key name under which children are nested
     * @return \Cake\Collection\CollectionInterface
     */
    public function nest($idPath, $parentPath, $nestingKey = 'children');

    /**
     * Returns a new collection containing each of the elements found in `$values` as
     * a property inside the corresponding elements in this collection. The property
     * where the values will be inserted is described by the `$path` parameter.
     *
     * The $path can be a string with a property name or a dot separated path of
     * properties that should be followed to get the last one in the path.
     *
     * If a column or property could not be found for a particular element in the
     * collection as part of the path, the element will be kept unchanged.
     *
     * ### Example:
     *
     * Insert ages into a collection containing users:
     *
     * ```
     * $items = [
     *  ['comment' => ['body' => 'cool', 'user' => ['name' => 'Mark']],
     *  ['comment' => ['body' => 'awesome', 'user' => ['name' => 'Renan']]
     * ];
     * $ages = [25, 28];
     * $inserted = (new Collection($items))->insert('comment.user.age', $ages);
     *
     * // Result will look like this when converted to array
     * [
     *  ['comment' => ['body' => 'cool', 'user' => ['name' => 'Mark', 'age' => 25]],
     *  ['comment' => ['body' => 'awesome', 'user' => ['name' => 'Renan', 'age' => 28]]
     * ];
     * ```
     *
     * @param string $path a dot separated string symbolizing the path to follow
     * inside the hierarchy of each value so that the value can be inserted
     * @param mixed $values The values to be inserted at the specified path,
     * values are matched with the elements in this collection by its positional index.
     * @return \Cake\Collection\CollectionInterface
     */
    public function insert($path, $values);

    /**
     * Returns an array representation of the results
     *
     * @param bool $preserveKeys whether to use the keys returned by this
     * collection as the array keys. Keep in mind that it is valid for iterators
     * to return the same key for different elements, setting this value to false
     * can help getting all items if keys are not important in the result.
     * @return array
     */
    public function toArray($preserveKeys = true);

    /**
     * Returns an numerically-indexed array representation of the results.
     * This is equivalent to calling `toArray(false)`
     *
     * @return array
     */
    public function toList();

    /**
     * Convert a result set into JSON.
     *
     * Part of JsonSerializable interface.
     *
     * @return array The data to convert to JSON
     */
    public function jsonSerialize();

    /**
     * Iterates once all elements in this collection and executes all stacked
     * operations of them, finally it returns a new collection with the result.
     * This is useful for converting non-rewindable internal iterators into
     * a collection that can be rewound and used multiple times.
     *
     * A common use case is to re-use the same variable for calculating different
     * data. In those cases it may be helpful and more performant to first compile
     * a collection and then apply more operations to it.
     *
     * ### Example:
     *
     * ```
     * $collection->map($mapper)->sortBy('age')->extract('name');
     * $compiled = $collection->compile();
     * $isJohnHere = $compiled->some($johnMatcher);
     * $allButJohn = $compiled->filter($johnMatcher);
     * ```
     *
     * In the above example, had the collection not been compiled before, the
     * iterations for `map`, `sortBy` and `extract` would've been executed twice:
     * once for getting `$isJohnHere` and once for `$allButJohn`
     *
     * You can think of this method as a way to create save points for complex
     * calculations in a collection.
     *
     * @param bool $preserveKeys whether to use the keys returned by this
     * collection as the array keys. Keep in mind that it is valid for iterators
     * to return the same key for different elements, setting this value to false
     * can help getting all items if keys are not important in the result.
     * @return \Cake\Collection\CollectionInterface
     */
    public function compile($preserveKeys = true);

    /**
     * Returns a new collection where the operations performed by this collection.
     * No matter how many times the new collection is iterated, those operations will
     * only be performed once.
     *
     * This can also be used to make any non-rewindable iterator rewindable.
     *
     * @return \Cake\Collection\CollectionInterface
     */
    public function buffered();

    /**
     * Returns a new collection with each of the elements of this collection
     * after flattening the tree structure. The tree structure is defined
     * by nesting elements under a key with a known name. It is possible
     * to specify such name by using the '$nestingKey' parameter.
     *
     * By default all elements in the tree following a Depth First Search
     * will be returned, that is, elements from the top parent to the leaves
     * for each branch.
     *
     * It is possible to return all elements from bottom to top using a Breadth First
     * Search approach by passing the '$dir' parameter with 'asc'. That is, it will
     * return all elements for the same tree depth first and from bottom to top.
     *
     * Finally, you can specify to only get a collection with the leaf nodes in the
     * tree structure. You do so by passing 'leaves' in the first argument.
     *
     * The possible values for the first argument are aliases for the following
     * constants and it is valid to pass those instead of the alias:
     *
     * - desc: TreeIterator::SELF_FIRST
     * - asc: TreeIterator::CHILD_FIRST
     * - leaves: TreeIterator::LEAVES_ONLY
     *
     * ### Example:
     *
     * ```
     * $collection = new Collection([
     *  ['id' => 1, 'children' => [['id' => 2, 'children' => [['id' => 3]]]]],
     *  ['id' => 4, 'children' => [['id' => 5]]]
     * ]);
     * $flattenedIds = $collection->listNested()->extract('id'); // Yields [1, 2, 3, 4, 5]
     * ```
     *
     * @param string|int $dir The direction in which to return the elements
     * @param string|callable $nestingKey The key name under which children are nested
     * or a callable function that will return the children list
     * @return \Cake\Collection\CollectionInterface
     */
    public function listNested($dir = 'desc', $nestingKey = 'children');

    /**
     * Creates a new collection that when iterated will stop yielding results if
     * the provided condition evaluates to false.
     *
     * This is handy for dealing with infinite iterators or any generator that
     * could start returning invalid elements at a certain point. For example,
     * when reading lines from a file stream you may want to stop the iteration
     * after a certain value is reached.
     *
     * ### Example:
     *
     * Get an array of lines in a CSV file until the timestamp column is less than a date
     *
     * ```
     * $lines = (new Collection($fileLines))->stopWhen(function ($value, $key) {
     *  return (new DateTime($value))->format('Y') < 2012;
     * })
     * ->toArray();
     * ```
     *
     * Get elements until the first unapproved message is found:
     *
     * ```
     * $comments = (new Collection($comments))->stopWhen(['is_approved' => false]);
     * ```
     *
     * @param callable $condition the method that will receive each of the elements and
     * returns false when the iteration should be stopped.
     * If an array, it will be interpreted as a key-value list of conditions where
     * the key is a property path as accepted by `Collection::extract`,
     * and the value the condition against with each element will be matched.
     * @return \Cake\Collection\CollectionInterface
     */
    public function stopWhen($condition);

    /**
     * Creates a new collection where the items are the
     * concatenation of the lists of items generated by the transformer function
     * applied to each item in the original collection.
     *
     * The transformer function will receive the value and the key for each of the
     * items in the collection, in that order, and it must return an array or a
     * Traversable object that can be concatenated to the final result.
     *
     * If no transformer function is passed, an "identity" function will be used.
     * This is useful when each of the elements in the source collection are
     * lists of items to be appended one after another.
     *
     * ### Example:
     *
     * ```
     * $items [[1, 2, 3], [4, 5]];
     * $unfold = (new Collection($items))->unfold(); // Returns [1, 2, 3, 4, 5]
     * ```
     *
     * Using a transformer
     *
     * ```
     * $items [1, 2, 3];
     * $allItems = (new Collection($items))->unfold(function ($page) {
     *  return $service->fetchPage($page)->toArray();
     * });
     * ```
     *
     * @param callable|null $transformer A callable function that will receive each of
     * the items in the collection and should return an array or Traversable object
     * @return \Cake\Collection\CollectionInterface
     */
    public function unfold(callable $transformer = null);

    /**
     * Passes this collection through a callable as its first argument.
     * This is useful for decorating the full collection with another object.
     *
     * ### Example:
     *
     * ```
     * $items = [1, 2, 3];
     * $decorated = (new Collection($items))->through(function ($collection) {
     *      return new MyCustomCollection($collection);
     * });
     * ```
     *
     * @param callable $handler A callable function that will receive
     * this collection as first argument.
     * @return \Cake\Collection\CollectionInterface
     */
    public function through(callable $handler);

    /**
     * Combines the elements of this collection with each of the elements of the
     * passed iterables, using their positional index as a reference.
     *
     * ### Example:
     *
     * ```
     * $collection = new Collection([1, 2]);
     * $collection->zip([3, 4], [5, 6])->toList(); // returns [[1, 3, 5], [2, 4, 6]]
     * ```
     *
     * @param array|\Traversable ...$items The collections to zip.
     * @return \Cake\Collection\CollectionInterface
     */
    public function zip($items);

    /**
     * Combines the elements of this collection with each of the elements of the
     * passed iterables, using their positional index as a reference.
     *
     * The resulting element will be the return value of the $callable function.
     *
     * ### Example:
     *
     * ```
     * $collection = new Collection([1, 2]);
     * $zipped = $collection->zipWith([3, 4], [5, 6], function (...$args) {
     *   return array_sum($args);
     * });
     * $zipped->toList(); // returns [9, 12]; [(1 + 3 + 5), (2 + 4 + 6)]
     * ```
     *
     * @param array|\Traversable ...$items The collections to zip.
     * @param callable $callable The function to use for zipping the elements together.
     * @return \Cake\Collection\CollectionInterface
     */
    public function zipWith($items, $callable);

    /**
     * Breaks the collection into smaller arrays of the given size.
     *
     * ### Example:
     *
     * ```
     * $items [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
     * $chunked = (new Collection($items))->chunk(3)->toList();
     * // Returns [[1, 2, 3], [4, 5, 6], [7, 8, 9], [10, 11]]
     * ```
     *
     * @param int $chunkSize The maximum size for each chunk
     * @return \Cake\Collection\CollectionInterface
     * @deprecated 4.0.0 Deprecated in favor of chunks
     */
    public function chunk($chunkSize);

    /**
     * Breaks the collection into smaller arrays of the given size.
     *
     * ### Example:
     *
     * ```
     * $items ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6];
     * $chunked = (new Collection($items))->chunkWithKeys(3)->toList();
     * // Returns [['a' => 1, 'b' => 2, 'c' => 3], ['d' => 4, 'e' => 5, 'f' => 6]]
     * ```
     *
     * @param int $chunkSize The maximum size for each chunk
     * @param bool $preserveKeys If the keys of the array should be preserved
     * @return \Cake\Collection\CollectionInterface
     */
    public function chunkWithKeys($chunkSize, $preserveKeys = true);

    /**
     * Returns whether or not there are elements in this collection
     *
     * ### Example:
     *
     * ```
     * $items [1, 2, 3];
     * (new Collection($items))->isEmpty(); // false
     * ```
     *
     * ```
     * (new Collection([]))->isEmpty(); // true
     * ```
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Returns the closest nested iterator that can be safely traversed without
     * losing any possible transformations. This is used mainly to remove empty
     * IteratorIterator wrappers that can only slowdown the iteration process.
     *
     * @return \Iterator
     */
    public function unwrap();

    /**
     * Transpose rows and columns into columns and rows
     *
     * ### Example:
     *
     * ```
     * $items = [
     *       ['Products', '2012', '2013', '2014'],
     *       ['Product A', '200', '100', '50'],
     *       ['Product B', '300', '200', '100'],
     *       ['Product C', '400', '300', '200'],
     * ]
     *
     * $transpose = (new Collection($items))->transpose()->toList();
     *
     * // Returns
     * // [
     * //     ['Products', 'Product A', 'Product B', 'Product C'],
     * //     ['2012', '200', '300', '400'],
     * //     ['2013', '100', '200', '300'],
     * //     ['2014', '50', '100', '200'],
     * // ]
     * ```
     *
     * @return \Cake\Collection\CollectionInterface
     */
    public function transpose();
}
