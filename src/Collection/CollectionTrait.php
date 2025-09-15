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
use Cake\Collection\Iterator\BufferedIterator;
use Cake\Collection\Iterator\ExtractIterator;
use Cake\Collection\Iterator\FilterIterator;
use Cake\Collection\Iterator\InsertIterator;
use Cake\Collection\Iterator\MapReduce;
use Cake\Collection\Iterator\NestIterator;
use Cake\Collection\Iterator\ReplaceIterator;
use Cake\Collection\Iterator\SortIterator;
use Cake\Collection\Iterator\StoppableIterator;
use Cake\Collection\Iterator\TreeIterator;
use Cake\Collection\Iterator\UnfoldIterator;
use Cake\Collection\Iterator\UniqueIterator;
use Cake\Collection\Iterator\ZipIterator;
use Countable;
use Generator;
use InvalidArgumentException;
use Iterator;
use LimitIterator;
use LogicException;
use RecursiveIteratorIterator;
use UnitEnum;
use const SORT_ASC;
use const SORT_DESC;
use const SORT_NUMERIC;

/**
 * Offers a handful of methods to manipulate iterators
 */
trait CollectionTrait
{
    use ExtractTrait;

    /**
     * Returns a new collection.
     *
     * Allows classes which use this trait to determine their own
     * type of returned collection interface
     *
     * @param mixed ...$args Constructor arguments.
     * @return \Cake\Collection\CollectionInterface
     */
    protected function newCollection(mixed ...$args): CollectionInterface
    {
        return new Collection(...$args);
    }

    /**
     * @inheritDoc
     */
    public function each(callable $callback)
    {
        foreach ($this->optimizeUnwrap() as $k => $v) {
            $callback($v, $k);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function filter(?callable $callback = null): CollectionInterface
    {
        $callback ??= fn($v) => (bool)$v;

        return new FilterIterator($this->unwrap(), $callback);
    }

    /**
     * @inheritDoc
     */
    public function reject(?callable $callback = null): CollectionInterface
    {
        $callback ??= fn($v) => (bool)$v;

        return new FilterIterator($this->unwrap(), fn($value, $key, $items) => !$callback($value, $key, $items));
    }

    /**
     * @inheritDoc
     */
    public function unique(?callable $callback = null): CollectionInterface
    {
        $callback ??= fn($v) => $v;

        return new UniqueIterator($this->unwrap(), $callback);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function map(callable $callback): CollectionInterface
    {
        return new ReplaceIterator($this->unwrap(), $callback);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function extract(callable|string $path): CollectionInterface
    {
        $extractor = new ExtractIterator($this->unwrap(), $path);
        if (is_string($path) && str_contains($path, '{*}')) {
            return $extractor
                ->filter(function ($data) {
                    return is_iterable($data);
                })
                ->unfold();
        }

        return $extractor;
    }

    /**
     * @inheritDoc
     */
    public function max(callable|string $path, int $sort = SORT_NUMERIC): mixed
    {
        return (new SortIterator($this->unwrap(), $path, SORT_DESC, $sort))->first();
    }

    /**
     * @inheritDoc
     */
    public function min(callable|string $path, int $sort = SORT_NUMERIC): mixed
    {
        return (new SortIterator($this->unwrap(), $path, SORT_ASC, $sort))->first();
    }

    /**
     * @inheritDoc
     */
    public function avg(callable|string|null $path = null): float|int|null
    {
        $result = $this;
        if ($path !== null) {
            $result = $result->extract($path);
        }
        $result = $result
            ->reduce(function (array $acc, $current) {
                [$count, $sum] = $acc;

                return [$count + 1, $sum + $current];
            }, [0, 0]);

        if ($result[0] === 0) {
            return null;
        }

        return $result[1] / $result[0];
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function sortBy(callable|string $path, int $order = SORT_DESC, int $sort = SORT_NUMERIC): CollectionInterface
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
     *   or a function returning the grouping key out of the provided element
     * @param bool $preserveKeys Whether to preserve the keys of the existing
     *   collection when the values are grouped. Defaults to false.
     * @return \Cake\Collection\CollectionInterface
     */
    public function groupBy(callable|string $path, bool $preserveKeys = false): CollectionInterface
    {
        $callback = $this->_propertyExtractor($path);
        $group = [];
        foreach ($this->optimizeUnwrap() as $key => $value) {
            $pathValue = $callback($value);
            if ($pathValue === null) {
                throw new InvalidArgumentException(
                    'Cannot group by path that does not exist or contains a null value. ' .
                    'Use a callback to return a default value for that path.',
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
     * @inheritDoc
     */
    public function indexBy(callable|string $path): CollectionInterface
    {
        $callback = $this->_propertyExtractor($path);
        $group = [];
        foreach ($this->optimizeUnwrap() as $value) {
            $pathValue = $callback($value);
            if ($pathValue === null) {
                throw new InvalidArgumentException(
                    'Cannot index by path that does not exist or contains a null value. ' .
                    'Use a callback to return a default value for that path.',
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
     * @inheritDoc
     */
    public function countBy(callable|string $path): CollectionInterface
    {
        $callback = $this->_propertyExtractor($path);

        $mapper = fn($value, $key, MapReduce $mr) => $mr->emitIntermediate($value, $callback($value));
        $reducer = fn($values, $key, MapReduce $mr) => $mr->emit(count($values), $key);

        return $this->newCollection(new MapReduce($this->unwrap(), $mapper, $reducer));
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function shuffle(): CollectionInterface
    {
        $items = $this->toList();
        shuffle($items);

        return $this->newCollection($items);
    }

    /**
     * @inheritDoc
     */
    public function sample(int $length = 10): CollectionInterface
    {
        return $this->newCollection(new LimitIterator($this->shuffle(), 0, $length));
    }

    /**
     * @inheritDoc
     */
    public function take(int $length = 1, int $offset = 0): CollectionInterface
    {
        return $this->newCollection(new LimitIterator($this, $offset, $length));
    }

    /**
     * @inheritDoc
     */
    public function skip(int $length): CollectionInterface
    {
        return $this->newCollection(new LimitIterator($this, $length));
    }

    /**
     * @inheritDoc
     */
    public function match(array $conditions): CollectionInterface
    {
        return $this->filter($this->_createMatcherFilter($conditions));
    }

    /**
     * @inheritDoc
     */
    public function firstMatch(array $conditions): mixed
    {
        return $this->match($conditions)->first();
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function takeLast(int $length): CollectionInterface
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
     * @inheritDoc
     */
    public function append(iterable $items): CollectionInterface
    {
        $list = new AppendIterator();
        $list->append($this->unwrap());
        $list->append($this->newCollection($items)->unwrap());

        return $this->newCollection($list);
    }

    /**
     * @inheritDoc
     */
    public function appendItem(mixed $item, mixed $key = null): CollectionInterface
    {
        if ($key !== null) {
            $data = [$key => $item];
        } else {
            $data = [$item];
        }

        return $this->append($data);
    }

    /**
     * @inheritDoc
     */
    public function prepend(mixed $items): CollectionInterface
    {
        return $this->newCollection($items)->append($this);
    }

    /**
     * @inheritDoc
     */
    public function prependItem(mixed $item, mixed $key = null): CollectionInterface
    {
        if ($key !== null) {
            $data = [$key => $item];
        } else {
            $data = [$item];
        }

        return $this->prepend($data);
    }

    /**
     * @inheritDoc
     */
    public function combine(
        callable|string $keyPath,
        callable|string $valuePath,
        callable|string|null $groupPath = null,
    ): CollectionInterface {
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
                        'Use a callback to return a default value for that path.',
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
                    'Use a callback to return a default value for that path.',
                );
            }

            $mapKey = $rowKey($value, $key);
            if ($mapKey === null) {
                throw new InvalidArgumentException(
                    'Cannot index by path that does not exist or contains a null value. ' .
                    'Use a callback to return a default value for that path.',
                );
            }

            $mapReduce->emitIntermediate(
                [$mapKey => $rowVal($value, $key)],
                $key,
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
     * @inheritDoc
     */
    public function nest(
        callable|string $idPath,
        callable|string $parentPath,
        string $nestingKey = 'children',
    ): CollectionInterface {
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
     * @inheritDoc
     */
    public function insert(string $path, mixed $values): CollectionInterface
    {
        return new InsertIterator($this->unwrap(), $path, $values);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function toList(): array
    {
        return $this->toArray(false);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     */
    public function compile(bool $keepKeys = true): CollectionInterface
    {
        return $this->newCollection($this->toArray($keepKeys));
    }

    /**
     * @inheritDoc
     */
    public function lazy(): CollectionInterface
    {
        $generator = function (): Generator {
            foreach ($this->unwrap() as $k => $v) {
                yield $k => $v;
            }
        };

        return $this->newCollection($generator());
    }

    /**
     * @inheritDoc
     */
    public function buffered(): CollectionInterface
    {
        return new BufferedIterator($this->unwrap());
    }

    /**
     * @inheritDoc
     */
    public function listNested(
        string|int $order = 'desc',
        callable|string $nestingKey = 'children',
    ): CollectionInterface {
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
                    $order,
                ));
            }
            $order = $modes[$order];
        }

        assert(
            $order === RecursiveIteratorIterator::LEAVES_ONLY ||
            $order === RecursiveIteratorIterator::SELF_FIRST ||
            $order === RecursiveIteratorIterator::CHILD_FIRST,
        );

        return new TreeIterator(
            new NestIterator($this, $nestingKey),
            $order,
        );
    }

    /**
     * @inheritDoc
     */
    public function stopWhen(callable|array $condition): CollectionInterface
    {
        if (!is_callable($condition)) {
            $condition = $this->_createMatcherFilter($condition);
        }

        return new StoppableIterator($this->unwrap(), $condition);
    }

    /**
     * @inheritDoc
     */
    public function unfold(?callable $callback = null): CollectionInterface
    {
        $callback ??= fn($v) => $v;

        return $this->newCollection(
            new RecursiveIteratorIterator(
                new UnfoldIterator($this->unwrap(), $callback),
                RecursiveIteratorIterator::LEAVES_ONLY,
            ),
        );
    }

    /**
     * @inheritDoc
     */
    public function through(callable $callback): CollectionInterface
    {
        $result = $callback($this);

        return $result instanceof CollectionInterface ? $result : $this->newCollection($result);
    }

    /**
     * @inheritDoc
     */
    public function zip(iterable ...$items): CollectionInterface
    {
        return new ZipIterator(array_merge([$this->unwrap()], $items));
    }

    /**
     * @inheritDoc
     */
    public function zipWith(iterable $items, $callback): CollectionInterface
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
     * @inheritDoc
     */
    public function chunk(int $chunkSize): CollectionInterface
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
     * @inheritDoc
     */
    public function chunkWithKeys(int $chunkSize, bool $keepKeys = true): CollectionInterface
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function unwrap(): Iterator
    {
        $iterator = $this;
        while ($iterator::class === Collection::class) {
            $iterator = $iterator->getInnerIterator();
        }

        if ($iterator !== $this && $iterator instanceof CollectionInterface) {
            return $iterator->unwrap();
        }

        /** @var \Iterator */
        return $iterator;
    }

    /**
     * {@inheritDoc}
     *
     * @param callable|null $operation A callable that allows you to customize the product result.
     * @param callable|null $filter A filtering callback that must return true for a result to be part
     *   of the final results.
     * @return \Cake\Collection\CollectionInterface
     * @throws \LogicException
     */
    public function cartesianProduct(?callable $operation = null, ?callable $filter = null): CollectionInterface
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
                return $value[$keys[$index]];
            }, $collectionArrays, $collectionArraysKeys, $currentIndexes);

            if ($filter === null || $filter($currentCombination)) {
                $result[] = $operation === null ? $currentCombination : $operation($currentCombination);
            }

            $currentIndexes[$lastIndex]++;

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
     * {@inheritDoc}
     *
     * @return \Cake\Collection\CollectionInterface
     * @throws \LogicException
     */
    public function transpose(): CollectionInterface
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
     * @inheritDoc
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
     * @inheritDoc
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
}
