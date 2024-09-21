<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection;

use AppendIterator;
use ArrayIterator;
use BackedEnum;
use Cake\Collection\Iterator2\BufferedIterator;
use Cake\Collection\Iterator2\ExtractIterator;
use Cake\Collection\Iterator2\FilterIterator;
use Cake\Collection\Iterator2\InsertIterator;
use Cake\Collection\Iterator2\MapReduce;
use Cake\Collection\Iterator2\NestIterator;
use Cake\Collection\Iterator2\ReplaceIterator;
use Cake\Collection\Iterator2\SortIterator;
use Cake\Collection\Iterator2\StoppableIterator;
use Cake\Collection\Iterator2\TreeIterator;
use Cake\Collection\Iterator2\UnfoldIterator;
use Cake\Collection\Iterator2\UniqueIterator;
use Cake\Collection\Iterator2\ZipIterator;
use Countable;
use Exception;
use Generator;
use IteratorIterator;
use InvalidArgumentException;
use Iterator;
use JsonSerializable;
use LimitIterator;
use LogicException;
use RecursiveIteratorIterator;
use UnitEnum;
use const SORT_ASC;
use const SORT_DESC;
use const SORT_NUMERIC;

/**
 * A collection is an immutable list of elements with a handful of functions to
 * iterate, group, transform and extract information from it.
 *
 * @template-extends \IteratorIterator<mixed, mixed, \Traversable<mixed>>
 */
class Collection2 extends IteratorIterator implements JsonSerializable, Countable
{
    use ExtractTrait;

    /**
     * Constructor. You can provide an array or any traversable object
     *
     * @param iterable $items Items.
     * @throws \InvalidArgumentException If passed incorrect type for items.
     */
    public function __construct(iterable $items)
    {
        if (is_array($items)) {
            $items = new ArrayIterator($items);
        }

        parent::__construct($items);
    }

    /**
     * Returns a new collection.
     *
     * Allows classes which use this trait to determine their own
     * type of returned collection interface
     *
     * @param mixed ...$args Constructor arguments.
     * @return self
     */
    protected function newCollection(mixed ...$args): Collection2
    {
        return new Collection2(...$args);
    }

    /**
     * Applies a callback to the elements in this collection.
     *
     * ### Example:
     *
     * ```
     * $collection = (new Collection($items))->each(function ($value, $key) {
     *  echo "Element $key: $value";
     * });
     * ```
     *
     * @param callable $callback Callback to run for each element in collection.
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->optimizeUnwrap() as $k => $v) {
            $callback($v, $k);
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
     * @param callable|null $callback the method that will receive each of the elements and
     *   returns true whether they should be in the resulting collection.
     *   If left null, a callback that filters out falsey values will be used.
     * @return self
     */
    public function filter(?callable $callback = null): Collection2
    {
        $callback ??= fn ($v) => (bool)$v;

        return new FilterIterator($this->unwrap(), $callback);
    }

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
     * @param callable $callback the method that will receive each of the elements and
     *   returns true whether they should be out of the resulting collection.
     *   If left null, a callback that filters out truthy values will be used.
     * @return self
     */
    public function reject(?callable $callback = null): Collection2
    {
        $callback ??= fn ($v) => (bool)$v;

        return new FilterIterator($this->unwrap(), fn ($value, $key, $items) => !$callback($value, $key, $items));
    }

    /**
     * Loops through each value in the collection and returns a new collection
     * with only unique values based on the value returned by ``callback``.
     *
     * The callback is passed the value as the first argument and the key as the
     * second argument.
     *
     * @param callable $callback the method that will receive each of the elements and
     * returns the value used to determine uniqueness.
     * @return self
     */
    public function unique(?callable $callback = null): Collection2
    {
        $callback ??= fn ($v) => $v;

        return new UniqueIterator($this->unwrap(), $callback);
    }

    /**
     * Returns true if all values in this collection pass the truth test provided
     * in the callback.
     *
     * The callback is passed the value and key of the element being tested and should
     * return true if the test passed.
     *
     * ### Example:
     *
     * ```
     * $overTwentyOne = (new Collection([24, 45, 60, 15]))->every(function ($value, $key) {
     *  return $value > 21;
     * });
     * ```
     *
     * Empty collections always return true.
     *
     * @param callable $callback a callback function
     * @return bool true if for all elements in this collection the provided
     *   callback returns true, false otherwise.
     */
    public function every(callable $callback): bool
    {
        foreach ($this->optimizeUnwrap() as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if any of the values in this collection pass the truth test
     * provided in the callback.
     *
     * The callback is passed the value and key of the element being tested and should
     * return true if the test passed.
     *
     * ### Example:
     *
     * ```
     * $hasYoungPeople = (new Collection([24, 45, 15]))->some(function ($value, $key) {
     *  return $value < 21;
     * });
     * ```
     *
     * @param callable $callback a callback function
     * @return bool true if the provided callback returns true for any element in this
     * collection, false otherwise
     */
    public function some(callable $callback): bool
    {
        foreach ($this->optimizeUnwrap() as $key => $value) {
            if ($callback($value, $key) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if $value is present in this collection. Comparisons are made
     * both by value and type.
     *
     * @param mixed $value The value to check for
     * @return bool true if $value is present in this collection
     */
    public function contains(mixed $value): bool
    {
        foreach ($this->optimizeUnwrap() as $v) {
            if ($value === $v) {
                return true;
            }
        }

        return false;
    }

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
     * @param callable $callback the method that will receive each of the elements and
     * returns the new value for the key that is being iterated
     * @return self
     */
    public function map(callable $callback): Collection2
    {
        return new ReplaceIterator($this->unwrap(), $callback);
    }

    /**
     * Folds the values in this collection to a single value, as the result of
     * applying the callback function to all elements. $zero is the initial state
     * of the reduction, and each successive step of it should be returned
     * by the callback function.
     * If $zero is omitted the first value of the collection will be used in its place
     * and reduction will start from the second item.
     *
     * @param callable $callback The callback function to be called
     * @param mixed $initial The state of reduction
     * @return mixed
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $isFirst = func_num_args() < 2;

        $result = $initial;
        foreach ($this->optimizeUnwrap() as $k => $value) {
            if ($isFirst) {
                $result = $value;
                $isFirst = false;
                continue;
            }
            $result = $callback($result, $value, $k);
        }

        return $result;
    }

    /**
     * Returns a new collection containing the column or property value found in each
     * of the elements.
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
     * @param callable|string $path A dot separated path of column to follow
     * so that the final one can be returned or a callable that will take care
     * of doing that.
     * @return self
     */
    public function extract(callable|string $path): Collection2
    {
        $extractor = new ExtractIterator($this->unwrap(), $path);
        if (is_string($path) && str_contains($path, '{*}')) {
            return $extractor
                ->filter(function ($data) {
                    return $data !== null && (is_iterable($data));
                })
                ->unfold();
        }

        return $extractor;
    }

    /**
     * Returns the top element in this collection after being sorted by a property.
     * Check the sortBy method for information on the callback and $sort parameters
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
     * @param callable|string $path The column name to use for sorting or callback that returns the value.
     * @param int $sort The sort type, one of SORT_STRING, SORT_NUMERIC or SORT_NATURAL
     * @see \Cake\Collection\Collection::sortBy()
     * @return mixed The value of the top element in the collection
     */
    public function max(callable|string $path, int $sort = SORT_NUMERIC): mixed
    {
        return (new SortIterator($this->unwrap(), $path, SORT_DESC, $sort))->first();
    }

    /**
     * Returns the bottom element in this collection after being sorted by a property.
     * Check the sortBy method for information on the callback and $sort parameters
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
     * @param callable|string $path The column name to use for sorting or callback that returns the value.
     * @param int $sort The sort type, one of SORT_STRING, SORT_NUMERIC or SORT_NATURAL
     * @see \Cake\Collection\Collection::sortBy()
     * @return mixed The value of the bottom element in the collection
     */
    public function min(callable|string $path, int $sort = SORT_NUMERIC): mixed
    {
        return (new SortIterator($this->unwrap(), $path, SORT_ASC, $sort))->first();
    }

    /**
     * Returns the average of all the values extracted with $path
     * or of this collection.
     *
     * ### Example:
     *
     * ```
     * $items = [
     *  ['invoice' => ['total' => 100]],
     *  ['invoice' => ['total' => 200]]
     * ];
     *
     * $total = (new Collection($items))->avg('invoice.total');
     *
     * // Total: 150
     *
     * $total = (new Collection([1, 2, 3]))->avg();
     * // Total: 2
     * ```
     *
     * The average of an empty set or 0 rows is `null`. Collections with `null`
     * values are not considered empty.
     *
     * @param callable|string|null $path The property name to compute the average or a function
     * If no value is passed, an identity function will be used.
     * that will return the value of the property to compute the average.
     * @return float|int|null
     */
    public function avg(callable|string|null $path = null): float|int|null
    {
        $result = $this;
        if ($path !== null) {
            $result = $result->extract($path);
        }
        $result = $result
            ->reduce(function ($acc, $current) {
                [$count, $sum] = $acc;

                return [$count + 1, $sum + $current];
            }, [0, 0]);

        if ($result[0] === 0) {
            return null;
        }

        return $result[1] / $result[0];
    }

    /**
     * Returns the median of all the values extracted with $path
     * or of this collection.
     *
     * ### Example:
     *
     * ```
     * $items = [
     *  ['invoice' => ['total' => 400]],
     *  ['invoice' => ['total' => 500]]
     *  ['invoice' => ['total' => 100]]
     *  ['invoice' => ['total' => 333]]
     *  ['invoice' => ['total' => 200]]
     * ];
     *
     * $total = (new Collection($items))->median('invoice.total');
     *
     * // Total: 333
     *
     * $total = (new Collection([1, 2, 3, 4]))->median();
     * // Total: 2.5
     * ```
     *
     * The median of an empty set or 0 rows is `null`. Collections with `null`
     * values are not considered empty.
     *
     * @param callable|string|null $path The property name to compute the median or a function
     * If no value is passed, an identity function will be used.
     * that will return the value of the property to compute the median.
     * @return float|int|null
     */
    public function median(callable|string|null $path = null): float|int|null
    {
        $items = $this;
        if ($path !== null) {
            $items = $items->extract($path);
        }
        $values = $items->toList();
        sort($values);
        $count = count($values);

        if ($count === 0) {
            return null;
        }

        $middle = (int)($count / 2);

        if ($count % 2) {
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * Returns a sorted iterator out of the elements in this collection,
     * ranked based on the results of applying a callback function to each value.
     * The parameter $path can also be a string representing the column or property name.
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
     * @param callable|string $path The column name to use for sorting or callback that returns the value.
     * @param int $order The sort order, either SORT_DESC or SORT_ASC
     * @param int $sort The sort type, one of SORT_STRING, SORT_NUMERIC or SORT_NATURAL
     * @return self
     */
    public function sortBy(callable|string $path, int $order = SORT_DESC, int $sort = SORT_NUMERIC): Collection2
    {
        return new SortIterator($this->unwrap(), $path, $order, $sort);
    }

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
     * @param callable|string $path The column name to use for grouping or callback that returns the value.
     * or a function returning the grouping key out of the provided element
     * @return self
     */
    public function groupBy(callable|string $path, bool $preserveKeys = false): Collection2
    {
        $callback = $this->_propertyExtractor($path);
        $group = [];
        foreach ($this->optimizeUnwrap() as $key => $value) {
            $pathValue = $callback($value);
            if ($pathValue === null) {
                throw new InvalidArgumentException(
                    'Cannot group by path that does not exist or contains a null value. ' .
                    'Use a callback to return a default value for that path.'
                );
            }
            if ($pathValue instanceof BackedEnum) {
                $pathValue = $pathValue->value;
            } elseif ($pathValue instanceof UnitEnum) {
                $pathValue = $pathValue->name;
            }

            if ($preserveKeys) {
                $group[$pathValue][$key] = $value;
                continue;
            }

            $group[$pathValue][] = $value;
        }

        return $this->newCollection($group);
    }

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
     * @param callable|string $path The column name to use for indexing or callback that returns the value.
     * or a function returning the indexing key out of the provided element
     * @return self
     */
    public function indexBy(callable|string $path): Collection2
    {
        $callback = $this->_propertyExtractor($path);
        $group = [];
        foreach ($this->optimizeUnwrap() as $value) {
            $pathValue = $callback($value);
            if ($pathValue === null) {
                throw new InvalidArgumentException(
                    'Cannot index by path that does not exist or contains a null value. ' .
                    'Use a callback to return a default value for that path.'
                );
            }
            if ($pathValue instanceof BackedEnum) {
                $pathValue = $pathValue->value;
            } elseif ($pathValue instanceof UnitEnum) {
                $pathValue = $pathValue->name;
            }

            $group[$pathValue] = $value;
        }

        return $this->newCollection($group);
    }

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
     * @param callable|string $path The column name to use for indexing or callback that returns the value.
     * or a function returning the indexing key out of the provided element
     * @return self
     */
    public function countBy(callable|string $path): Collection2
    {
        $callback = $this->_propertyExtractor($path);

        $mapper = fn ($value, $key, MapReduce $mr) => $mr->emitIntermediate($value, $callback($value));
        $reducer = fn ($values, $key, MapReduce $mr) => $mr->emit(count($values), $key);

        return $this->newCollection(new MapReduce($this->unwrap(), $mapper, $reducer));
    }

    /**
     * Returns the total sum of all the values extracted with $matcher
     * or of this collection.
     *
     * ### Example:
     *
     * ```
     * $items = [
     *  ['invoice' => ['total' => 100]],
     *  ['invoice' => ['total' => 200]],
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
     * @param callable|string|null $path The property name to sum or a function
     * If no value is passed, an identity function will be used.
     * that will return the value of the property to sum.
     * @return float|int
     */
    public function sumOf(callable|string|null $path = null): float|int
    {
        if ($path === null) {
            return array_sum($this->toList());
        }

        $callback = $this->_propertyExtractor($path);
        $sum = 0;
        foreach ($this->optimizeUnwrap() as $k => $v) {
            $sum += $callback($v, $k);
        }

        return $sum;
    }

    /**
     * Returns a new collection with the elements placed in a random order,
     * this function does not preserve the original keys in the collection.
     *
     * @return self
     */
    public function shuffle(): Collection2
    {
        $items = $this->toList();
        shuffle($items);

        return $this->newCollection($items);
    }

    /**
     * Returns a new collection with maximum $length random elements
     * from this collection
     *
     * @param int $length the maximum number of elements to randomly
     * take from this collection
     * @return self
     */
    public function sample(int $length = 10): Collection2
    {
        return $this->newCollection(new LimitIterator($this->shuffle(), 0, $length));
    }

    /**
     * Returns a new collection with maximum $length elements in the internal
     * order this collection was created. If a second parameter is passed, it
     * will determine from what position to start taking elements.
     *
     * @param int $length the maximum number of elements to take from
     * this collection
     * @param int $offset A positional offset from where to take the elements
     * @return self
     */
    public function take(int $length = 1, int $offset = 0): Collection2
    {
        return $this->newCollection(new LimitIterator($this, $offset, $length));
    }

    /**
     * Returns the last N elements of a collection
     *
     * ### Example:
     *
     * ```
     * $items = [1, 2, 3, 4, 5];
     *
     * $last = (new Collection($items))->takeLast(3);
     *
     * // Result will look like this when converted to array
     * [3, 4, 5];
     * ```
     *
     * @param int $length The number of elements at the end of the collection
     * @return self
     */
    public function takeLast(int $length): Collection2
    {
        if ($length < 1) {
            throw new InvalidArgumentException('The takeLast method requires a number greater than 0.');
        }

        $iterator = $this->optimizeUnwrap();
        if (is_array($iterator)) {
            return $this->newCollection(array_slice($iterator, $length * -1));
        }

        if ($iterator instanceof Countable) {
            $count = count($iterator);

            if ($count === 0) {
                return $this->newCollection([]);
            }

            $iterator = new LimitIterator($iterator, max(0, $count - $length), $length);

            return $this->newCollection($iterator);
        }

        $generator = function ($iterator, $length): Generator {
            $result = [];
            $bucket = 0;
            $offset = 0;

            /**
             * Consider the collection of elements [1, 2, 3, 4, 5, 6, 7, 8, 9], in order
             * to get the last 4 elements, we can keep a buffer of 4 elements and
             * fill it circularly using modulo logic, we use the $bucket variable
             * to track the position to fill next in the buffer. This how the buffer
             * looks like after 4 iterations:
             *
             * 0) 1 2 3 4 -- $bucket now goes back to 0, we have filled 4 elementes
             * 1) 5 2 3 4 -- 5th iteration
             * 2) 5 6 3 4 -- 6th iteration
             * 3) 5 6 7 4 -- 7th iteration
             * 4) 5 6 7 8 -- 8th iteration
             * 5) 9 6 7 8
             *
             *  We can see that at the end of the iterations, the buffer contains all
             *  the last four elements, just in the wrong order. How do we keep the
             *  original order? Well, it turns out that the number of iteration also
             *  give us a clue on what's going on, Let's add a marker for it now:
             *
             * 0) 1 2 3 4
             *    ^ -- The 0) above now becomes the $offset variable
             * 1) 5 2 3 4
             *      ^ -- $offset = 1
             * 2) 5 6 3 4
             *        ^ -- $offset = 2
             * 3) 5 6 7 4
             *          ^ -- $offset = 3
             * 4) 5 6 7 8
             *    ^  -- We use module logic for $offset too
             *          and as you can see each time $offset is 0, then the buffer
             *          is sorted exactly as we need.
             * 5) 9 6 7 8
             *      ^ -- $offset = 1
             *
             * The $offset variable is a marker for splitting the buffer in two,
             * elements to the right for the marker are the head of the final result,
             * whereas the elements at the left are the tail. For example consider step 5)
             * which has an offset of 1:
             *
             * - $head = elements to the right = [6, 7, 8]
             * - $tail = elements to the left =  [9]
             * - $result = $head + $tail = [6, 7, 8, 9]
             *
             * The logic above applies to collections of any size.
             */

            foreach ($iterator as $k => $item) {
                $result[$bucket] = [$k, $item];
                $bucket = (++$bucket) % $length;
                $offset++;
            }

            $offset %= $length;
            $head = array_slice($result, $offset);
            $tail = array_slice($result, 0, $offset);

            foreach ($head as $v) {
                yield $v[0] => $v[1];
            }

            foreach ($tail as $v) {
                yield $v[0] => $v[1];
            }
        };

        return $this->newCollection($generator($iterator, $length));
    }

    /**
     * Returns a new collection that will skip the specified amount of elements
     * at the beginning of the iteration.
     *
     * @param int $length The number of elements to skip.
     * @return self
     */
    public function skip(int $length): Collection2
    {
        return $this->newCollection(new LimitIterator($this, $length));
    }

    /**
     * Looks through each value in the list, returning a Collection of all the
     * values that contain all of the key-value pairs listed in $conditions.
     *
     * ### Example:
     *
     * ```
     * $items = [
     *  ['comment' => ['body' => 'cool', 'user' => ['name' => 'Mark']],
     *  ['comment' => ['body' => 'very cool', 'user' => ['name' => 'Renan']],
     * ];
     *
     * $extracted = (new Collection($items))->match(['user.name' => 'Renan']);
     *
     * // Result will look like this when converted to array
     * [
     *  ['comment' => ['body' => 'very cool', 'user' => ['name' => 'Renan']]]
     * ]
     * ```
     *
     * @param array $conditions a key-value list of conditions where
     * the key is a property path as accepted by `Collection::extract`,
     * and the value the condition against with each element will be matched
     * @return self
     */
    public function match(array $conditions): Collection2
    {
        return $this->filter($this->_createMatcherFilter($conditions));
    }

    /**
     * Returns the first result matching all the key-value pairs listed in
     * conditions.
     *
     * @param array $conditions a key-value list of conditions where the key is
     * a property path as accepted by `Collection::extract`, and the value the
     * condition against with each element will be matched
     * @see \Cake\Collection\Collection::match()
     * @return mixed
     */
    public function firstMatch(array $conditions): mixed
    {
        return $this->match($conditions)->first();
    }

    /**
     * Returns the first result in this collection
     *
     * @return mixed The first value in the collection will be returned.
     */
    public function first(): mixed
    {
        $iterator = new LimitIterator($this, 0, 1);
        foreach ($iterator as $result) {
            return $result;
        }

        return null;
    }

    /**
     * Returns the last result in this collection
     *
     * @return mixed The last value in the collection will be returned.
     */
    public function last(): mixed
    {
        $iterator = $this->optimizeUnwrap();
        if (is_array($iterator)) {
            return array_pop($iterator);
        }

        if ($iterator instanceof Countable) {
            $count = count($iterator);
            if ($count === 0) {
                return null;
            }
            $iterator = new LimitIterator($iterator, $count - 1, 1);
        }

        $result = null;
        foreach ($iterator as $result) {
            // No-op
        }

        return $result;
    }

    /**
     * Returns a new collection as the result of concatenating the list of elements
     * in this collection with the passed list of elements
     *
     * @param iterable $items Items list.
     * @return self
     */
    public function append(iterable $items): Collection2
    {
        $list = new AppendIterator();
        $list->append($this->unwrap());
        $list->append($this->newCollection($items)->unwrap());

        return $this->newCollection($list);
    }

    /**
     * Append a single item creating a new collection.
     *
     * @param mixed $item The item to append.
     * @param mixed $key The key to append the item with. If null a key will be generated.
     * @return self
     */
    public function appendItem(mixed $item, mixed $key = null): Collection2
    {
        if ($key !== null) {
            $data = [$key => $item];
        } else {
            $data = [$item];
        }

        return $this->append($data);
    }

    /**
     * Prepend a set of items to a collection creating a new collection
     *
     * @param iterable $items The items to prepend.
     * @return self
     */
    public function prepend(mixed $items): Collection2
    {
        return $this->newCollection($items)->append($this);
    }

    /**
     * Prepend a single item creating a new collection.
     *
     * @param mixed $item The item to prepend.
     * @param mixed $key The key to prepend the item with. If null a key will be generated.
     * @return self
     */
    public function prependItem(mixed $item, mixed $key = null): Collection2
    {
        if ($key !== null) {
            $data = [$key => $item];
        } else {
            $data = [$item];
        }

        return $this->prepend($data);
    }

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
     *  'b' => [2 => 'bar'],
     * ];
     * ```
     *
     * @param callable|string $keyPath the column name path to use for indexing
     * or a function returning the indexing key out of the provided element
     * @param callable|string $valuePath the column name path to use as the array value
     * or a function returning the value out of the provided element
     * @param callable|string|null $groupPath the column name path to use as the parent
     * grouping key or a function returning the key out of the provided element
     * @return self
     */
    public function combine(
        callable|string $keyPath,
        callable|string $valuePath,
        callable|string|null $groupPath = null
    ): Collection2 {
        $options = [
            'keyPath' => $this->_propertyExtractor($keyPath),
            'valuePath' => $this->_propertyExtractor($valuePath),
            'groupPath' => $groupPath ? $this->_propertyExtractor($groupPath) : null,
        ];

        $mapper = function ($value, $key, MapReduce $mapReduce) use ($options) {
            $rowKey = $options['keyPath'];
            $rowVal = $options['valuePath'];

            if (!$options['groupPath']) {
                $mapKey = $rowKey($value, $key);
                if ($mapKey === null) {
                    throw new InvalidArgumentException(
                        'Cannot index by path that does not exist or contains a null value. ' .
                        'Use a callback to return a default value for that path.'
                    );
                }

                if ($mapKey instanceof BackedEnum) {
                    $mapKey = $mapKey->value;
                } elseif ($mapKey instanceof UnitEnum) {
                    $mapKey = $mapKey->name;
                }

                $mapReduce->emit($rowVal($value, $key), $mapKey);

                return null;
            }

            $key = $options['groupPath']($value, $key);
            if ($key === null) {
                throw new InvalidArgumentException(
                    'Cannot group by path that does not exist or contains a null value. ' .
                    'Use a callback to return a default value for that path.'
                );
            }

            $mapKey = $rowKey($value, $key);
            if ($mapKey === null) {
                throw new InvalidArgumentException(
                    'Cannot index by path that does not exist or contains a null value. ' .
                    'Use a callback to return a default value for that path.'
                );
            }

            $mapReduce->emitIntermediate(
                [$mapKey => $rowVal($value, $key)],
                $key
            );
        };

        $reducer = function ($values, $key, MapReduce $mapReduce): void {
            $result = [];
            foreach ($values as $value) {
                $result += $value;
            }
            $mapReduce->emit($result, $key);
        };

        return $this->newCollection(new MapReduce($this->unwrap(), $mapper, $reducer));
    }

    /**
     * Returns a new collection where the values are nested in a tree-like structure
     * based on an id property path and a parent id property path.
     *
     * @param callable|string $idPath the column name path to use for determining
     * whether an element is a parent of another
     * @param callable|string $parentPath the column name path to use for determining
     * whether an element is a child of another
     * @param string $nestingKey The key name under which children are nested
     * @return self
     */
    public function nest(
        callable|string $idPath,
        callable|string $parentPath,
        string $nestingKey = 'children'
    ): Collection2 {
        $parents = [];
        $idPath = $this->_propertyExtractor($idPath);
        $parentPath = $this->_propertyExtractor($parentPath);
        $isObject = true;

        $mapper = function ($row, $key, MapReduce $mapReduce) use (&$parents, $idPath, $parentPath, $nestingKey): void {
            $row[$nestingKey] = [];
            $id = $idPath($row, $key);
            $parentId = $parentPath($row, $key);
            $parents[$id] = &$row;
            $mapReduce->emitIntermediate($id, $parentId);
        };

        $reducer = function ($values, $key, MapReduce $mapReduce) use (&$parents, &$isObject, $nestingKey) {
            static $foundOutType = false;
            if (!$foundOutType) {
                $isObject = is_object(current($parents));
                $foundOutType = true;
            }
            if (!$key || !isset($parents[$key])) {
                foreach ($values as $id) {
                    /** @psalm-suppress PossiblyInvalidArgument */
                    $parents[$id] = $isObject ? $parents[$id] : new ArrayIterator($parents[$id], 1);
                    $mapReduce->emit($parents[$id]);
                }

                return null;
            }

            $children = [];
            foreach ($values as $id) {
                $children[] = &$parents[$id];
            }
            $parents[$key][$nestingKey] = $children;
        };

        return $this->newCollection(new MapReduce($this->unwrap(), $mapper, $reducer))
            ->map(function ($value) use ($isObject) {
                /** @var \ArrayIterator|\ArrayObject $value */
                return $isObject ? $value : $value->getArrayCopy();
            });
    }

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
     * @return self
     */
    public function insert(string $path, mixed $values): Collection2
    {
        return new InsertIterator($this->unwrap(), $path, $values);
    }

    /**
     * Returns an array representation of the results
     *
     * @param bool $keepKeys Whether to use the keys returned by this
     * collection as the array keys. Keep in mind that it is valid for iterators
     * to return the same key for different elements, setting this value to false
     * can help getting all items if keys are not important in the result.
     * @return array
     */
    public function toArray(bool $keepKeys = true): array
    {
        $iterator = $this->unwrap();
        if ($iterator instanceof ArrayIterator) {
            $items = $iterator->getArrayCopy();

            return $keepKeys ? $items : array_values($items);
        }
        // RecursiveIteratorIterator can return duplicate key values causing
        // data loss when converted into an array
        if ($keepKeys && $iterator::class === RecursiveIteratorIterator::class) {
            $keepKeys = false;
        }

        return iterator_to_array($this, $keepKeys);
    }

    /**
     * Returns an numerically-indexed array representation of the results.
     * This is equivalent to calling `toArray(false)`
     *
     * @return array
     */
    public function toList(): array
    {
        return $this->toArray(false);
    }

    /**
     * Returns the data that can be converted to JSON. This returns the same data
     * as `toArray()` which contains only unique keys.
     *
     * Part of JsonSerializable interface.
     *
     * @return array The data to convert to JSON
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

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
     * @param bool $keepKeys Whether to use the keys returned by this
     * collection as the array keys. Keep in mind that it is valid for iterators
     * to return the same key for different elements, setting this value to false
     * can help getting all items if keys are not important in the result.
     * @return self
     */
    public function compile(bool $keepKeys = true): Collection2
    {
        return $this->newCollection($this->toArray($keepKeys));
    }

    /**
     * Returns a new collection where any operations chained after it are guaranteed
     * to be run lazily. That is, elements will be yielded one at a time.
     *
     * A lazy collection can only be iterated once. A second attempt results in an error.
     *
     * @return self
     */
    public function lazy(): Collection2
    {
        $generator = function (): Generator {
            foreach ($this->unwrap() as $k => $v) {
                yield $k => $v;
            }
        };

        return $this->newCollection($generator());
    }

    /**
     * Returns a new collection where the operations performed by this collection.
     * No matter how many times the new collection is iterated, those operations will
     * only be performed once.
     *
     * This can also be used to make any non-rewindable iterator rewindable.
     *
     * @return self
     */
    public function buffered(): Collection2
    {
        return new BufferedIterator($this->unwrap());
    }

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
     * - desc: RecursiveIteratorIterator::SELF_FIRST
     * - asc: RecursiveIteratorIterator::CHILD_FIRST
     * - leaves: RecursiveIteratorIterator::LEAVES_ONLY
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
     * @param string|int $order The order in which to return the elements
     * @param callable|string $nestingKey The key name under which children are nested
     * or a callable function that will return the children list
     * @return self
     */
    /* Collection2: Skip TreeIterator
    public function listNested(
        string|int $order = 'desc',
        callable|string $nestingKey = 'children'
    ): Collection2 {
        if (is_string($order)) {
            $order = strtolower($order);
            $modes = [
                'desc' => RecursiveIteratorIterator::SELF_FIRST,
                'asc' => RecursiveIteratorIterator::CHILD_FIRST,
                'leaves' => RecursiveIteratorIterator::LEAVES_ONLY,
            ];

            if (!isset($modes[$order])) {
                throw new InvalidArgumentException(sprintf(
                    "Invalid direction `%s` provided. Must be one of: 'desc', 'asc', 'leaves'.",
                    $order
                ));
            }
            $order = $modes[$order];
        }

        return new TreeIterator(
            new NestIterator($this, $nestingKey),
            $order
        );
    }
    */

    /**
     * Creates a new collection that when iterated will stop yielding results if
     * the provided condition evaluates to true.
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
     * @param callable|array $condition the method that will receive each of the elements and
     * returns true when the iteration should be stopped.
     * If an array, it will be interpreted as a key-value list of conditions where
     * the key is a property path as accepted by `Collection::extract`,
     * and the value the condition against with each element will be matched.
     * @return self
     */
    public function stopWhen(callable|array $condition): Collection2
    {
        if (!is_callable($condition)) {
            $condition = $this->_createMatcherFilter($condition);
        }

        return new StoppableIterator($this->unwrap(), $condition);
    }

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
     * @param callable|null $callback A callable function that will receive each of
     * the items in the collection and should return an array or Traversable object
     * @return self
     */
    public function unfold(?callable $callback = null): Collection2
    {
        $callback ??= fn ($v) => $v;

        return $this->newCollection(
            new RecursiveIteratorIterator(
                new UnfoldIterator($this->unwrap(), $callback),
                RecursiveIteratorIterator::LEAVES_ONLY
            )
        );
    }

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
     * @param callable $callback A callable function that will receive
     * this collection as first argument.
     * @return self
     */
    public function through(callable $callback): Collection2
    {
        $result = $callback($this);

        return $result instanceof Collection2 ? $result : $this->newCollection($result);
    }

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
     * @param iterable ...$items The collections to zip.
     * @return self
     */
    public function zip(iterable ...$items): Collection2
    {
        return new ZipIterator(array_merge([$this->unwrap()], $items));
    }

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
     * @param iterable ...$items The collections to zip.
     * @param callable $callback The function to use for zipping the elements together.
     * @return self
     */
    public function zipWith(iterable $items, $callback): Collection2
    {
        if (func_num_args() > 2) {
            $items = func_get_args();
            $callback = array_pop($items);
        } else {
            $items = [$items];
        }

        /** @var callable $callback */
        return new ZipIterator(array_merge([$this->unwrap()], $items), $callback);
    }

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
     * @return self
     */
    public function chunk(int $chunkSize): Collection2
    {
        return $this->map(function ($v, $k, Iterator $iterator) use ($chunkSize) {
            $values = [$v];
            for ($i = 1; $i < $chunkSize; $i++) {
                $iterator->next();
                if (!$iterator->valid()) {
                    break;
                }
                $values[] = $iterator->current();
            }

            return $values;
        });
    }

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
     * @param bool $keepKeys If the keys of the array should be kept
     * @return self
     */
    public function chunkWithKeys(int $chunkSize, bool $keepKeys = true): Collection2
    {
        return $this->map(function ($v, $k, Iterator $iterator) use ($chunkSize, $keepKeys) {
            $key = 0;
            if ($keepKeys) {
                $key = $k;
            }
            $values = [$key => $v];
            for ($i = 1; $i < $chunkSize; $i++) {
                $iterator->next();
                if (!$iterator->valid()) {
                    break;
                }
                if ($keepKeys) {
                    $values[$iterator->key()] = $iterator->current();
                } else {
                    $values[] = $iterator->current();
                }
            }

            return $values;
        });
    }

    /**
     * Returns whether there are elements in this collection
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
    public function isEmpty(): bool
    {
        // phpcs:ignore SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
        foreach ($this as $el) {
            return false;
        }

        return true;
    }

    /**
     * Returns the closest nested iterator that can be safely traversed without
     * losing any possible transformations. This is used mainly to remove empty
     * IteratorIterator wrappers that can only slowdown the iteration process.
     *
     * @return \Iterator
     */
    public function unwrap(): Iterator
    {
        $iterator = $this;
        while ($iterator::class === Collection::class) {
            $iterator = $iterator->getInnerIterator();
        }

        if ($iterator !== $this && $iterator instanceof Collection2) {
            return $iterator->unwrap();
        }

        return $iterator;
    }

    /**
     * Create a new collection that is the cartesian product of the current collection
     *
     * In order to create a cartesian product a collection must contain a single dimension
     * of data.
     *
     * ### Example
     *
     * ```
     * $collection = new Collection([['A', 'B', 'C'], [1, 2, 3]]);
     * $result = $collection->cartesianProduct()->toArray();
     * $expected = [
     *     ['A', 1],
     *     ['A', 2],
     *     ['A', 3],
     *     ['B', 1],
     *     ['B', 2],
     *     ['B', 3],
     *     ['C', 1],
     *     ['C', 2],
     *     ['C', 3],
     * ];
     * ```
     *
     * @param callable|null $operation A callable that allows you to customize the product result.
     * @param callable|null $filter A filtering callback that must return true for a result to be part
     *   of the final results.
     * @return self
     */
    public function cartesianProduct(?callable $operation = null, ?callable $filter = null): Collection2
    {
        if ($this->isEmpty()) {
            return $this->newCollection([]);
        }

        $collectionArrays = [];
        $collectionArraysKeys = [];
        $collectionArraysCounts = [];

        foreach ($this->toList() as $value) {
            $valueCount = count($value);
            if ($valueCount !== count($value, COUNT_RECURSIVE)) {
                throw new LogicException('Cannot find the cartesian product of a multidimensional array');
            }

            $collectionArraysKeys[] = array_keys($value);
            $collectionArraysCounts[] = $valueCount;
            $collectionArrays[] = $value;
        }

        $result = [];
        $lastIndex = count($collectionArrays) - 1;
        // holds the indexes of the arrays that generate the current combination
        $currentIndexes = array_fill(0, $lastIndex + 1, 0);

        $changeIndex = $lastIndex;

        while (!($changeIndex === 0 && $currentIndexes[0] === $collectionArraysCounts[0])) {
            $currentCombination = array_map(function ($value, $keys, $index) {
                /** @psalm-suppress InvalidArrayOffset */
                return $value[$keys[$index]];
            }, $collectionArrays, $collectionArraysKeys, $currentIndexes);

            if ($filter === null || $filter($currentCombination)) {
                $result[] = $operation === null ? $currentCombination : $operation($currentCombination);
            }

            $currentIndexes[$lastIndex]++;

            /** @psalm-suppress InvalidArrayOffset */
            for (
                $changeIndex = $lastIndex;
                $currentIndexes[$changeIndex] === $collectionArraysCounts[$changeIndex] && $changeIndex > 0;
                $changeIndex--
            ) {
                $currentIndexes[$changeIndex] = 0;
                $currentIndexes[$changeIndex - 1]++;
            }
        }

        return $this->newCollection($result);
    }

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
     * @return self
     */
    public function transpose(): Collection2
    {
        $arrayValue = $this->toList();
        $length = count(current($arrayValue));
        $result = [];
        foreach ($arrayValue as $row) {
            if (count($row) !== $length) {
                throw new LogicException('Child arrays do not have even length');
            }
        }

        for ($column = 0; $column < $length; $column++) {
            $result[] = array_column($arrayValue, $column);
        }

        return $this->newCollection($result);
    }

    /**
     * Returns the amount of elements in the collection.
     *
     * ## WARNINGS:
     *
     * ### Will change the current position of the iterator:
     *
     * Calling this method at the same time that you are iterating this collections, for example in
     * a foreach, will result in undefined behavior. Avoid doing this.
     *
     *
     * ### Consumes all elements for NoRewindIterator collections:
     *
     * On certain type of collections, calling this method may render unusable afterwards.
     * That is, you may not be able to get elements out of it, or to iterate on it anymore.
     *
     * Specifically any collection wrapping a Generator (a function with a yield statement)
     * or a unbuffered database cursor will not accept any other function calls after calling
     * `count()` on it.
     *
     * Create a new collection with `buffered()` method to overcome this problem.
     *
     * ### Can report more elements than unique keys:
     *
     * Any collection constructed by appending collections together, or by having internal iterators
     * returning duplicate keys, will report a larger amount of elements using this functions than
     * the final amount of elements when converting the collections to a keyed array. This is because
     * duplicate keys will be collapsed into a single one in the final array, whereas this count method
     * is only concerned by the amount of elements after converting it to a plain list.
     *
     * If you need the count of elements after taking the keys in consideration
     * (the count of unique keys), you can call `countKeys()`
     *
     * @return int
     */
    public function count(): int
    {
        $traversable = $this->optimizeUnwrap();

        if (is_array($traversable)) {
            return count($traversable);
        }

        return iterator_count($traversable);
    }

    /**
     * Returns the number of unique keys in this iterator. This is the same as the number of
     * elements the collection will contain after calling `toArray()`
     *
     * This method comes with a number of caveats. Please refer to `Collection2::count()`
     * for details.
     *
     * @see \Cake\Collection\Collection2::count()
     * @return int
     */
    public function countKeys(): int
    {
        return count($this->toArray());
    }

    /**
     * Unwraps this iterator and returns the simplest
     * traversable that can be used for getting the data out
     *
     * @return \Iterator|array
     */
    protected function optimizeUnwrap(): Iterator|array
    {
        $iterator = $this->unwrap();

        if ($iterator::class === ArrayIterator::class) {
            return $iterator->getArrayCopy();
        }

        return $iterator;
    }

    /**
     * Returns an array for serializing this of this object.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->buffered()->toArray();
    }

    /**
     * Rebuilds the Collection instance.
     *
     * @param array $data Data array.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->__construct($data);
    }

    /**
     * Returns an array that can be used to describe the internal state of this
     * object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        try {
            $count = $this->count();
        } catch (Exception) {
            $count = 'An exception occurred while getting count';
        }

        return [
            'count' => $count,
        ];
    }
}
