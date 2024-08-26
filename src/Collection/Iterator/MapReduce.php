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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection\Iterator;

use ArrayIterator;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * Implements a simplistic version of the popular Map-Reduce algorithm. Acts
 * like an iterator for the original passed data after each result has been
 * processed, thus offering a transparent wrapper for results coming from any
 * source.
 *
 * @template-implements \IteratorAggregate<mixed>
 */
class MapReduce implements IteratorAggregate
{
    /**
     * Holds the shuffled results that were emitted from the map
     * phase
     *
     * @var array
     */
    protected array $_intermediate = [];

    /**
     * Holds the results as emitted during the reduce phase
     *
     * @var array
     */
    protected array $_result = [];

    /**
     * Whether the Map-Reduce routine has been executed already on the data
     *
     * @var bool
     */
    protected bool $_executed = false;

    /**
     * Holds the original data that needs to be processed
     *
     * @var iterable
     */
    protected iterable $_data;

    /**
     * A callable that will be executed for each record in the original data
     *
     * @var callable
     */
    protected $_mapper;

    /**
     * A callable that will be executed for each intermediate record emitted during
     * the Map phase
     *
     * @var callable|null
     */
    protected $_reducer;

    /**
     * Count of elements emitted during the Reduce phase
     *
     * @var int
     */
    protected int $_counter = 0;

    /**
     * Constructor
     *
     * ### Example:
     *
     * Separate all unique odd and even numbers in an array
     *
     * ```
     *  $data = new \ArrayObject([1, 2, 3, 4, 5, 3]);
     *  $mapper = function ($value, $key, $mr) {
     *      $type = ($value % 2 === 0) ? 'even' : 'odd';
     *      $mr->emitIntermediate($value, $type);
     *  };
     *
     *  $reducer = function ($numbers, $type, $mr) {
     *      $mr->emit(array_unique($numbers), $type);
     *  };
     *  $results = new MapReduce($data, $mapper, $reducer);
     * ```
     *
     * Previous example will generate the following result:
     *
     * ```
     *  ['odd' => [1, 3, 5], 'even' => [2, 4]]
     * ```
     *
     * @param iterable $data The original data to be processed.
     * @param callable $mapper the mapper callback. This function will receive 3 arguments.
     * The first one is the current value, second the current results key and third is
     * this class instance so you can call the result emitters.
     * @param callable|null $reducer the reducer callback. This function will receive 3 arguments.
     * The first one is the list of values inside a bucket, second one is the name
     * of the bucket that was created during the mapping phase and third one is an
     * instance of this class.
     */
    public function __construct(iterable $data, callable $mapper, ?callable $reducer = null)
    {
        $this->_data = $data;
        $this->_mapper = $mapper;
        $this->_reducer = $reducer;
    }

    /**
     * Returns an iterator with the end result of running the Map and Reduce
     * phases on the original data
     *
     * @return \Traversable
     */
    public function getIterator(): Traversable
    {
        if (!$this->_executed) {
            $this->_execute();
        }

        return new ArrayIterator($this->_result);
    }

    /**
     * Appends a new record to the bucket labelled with $key, usually as a result
     * of mapping a single record from the original data.
     *
     * @param mixed $val The record itself to store in the bucket
     * @param mixed $bucket the name of the bucket where to put the record
     * @param mixed $key An optional key to assign to the value
     * @return void
     */
    public function emitIntermediate(mixed $val, mixed $bucket, mixed $key = null): void
    {
        if ($key === null) {
            $this->_intermediate[$bucket][] = $val;

            return;
        }

        $this->_intermediate[$bucket][$key] = $val;
    }

    /**
     * Appends a new record to the final list of results and optionally assign a key
     * for this record.
     *
     * @param mixed $val The value to be appended to the final list of results
     * @param mixed $key and optional key to assign to the value
     * @return void
     */
    public function emit(mixed $val, mixed $key = null): void
    {
        $this->_result[$key ?? $this->_counter] = $val;
        $this->_counter++;
    }

    /**
     * Runs the actual Map-Reduce algorithm. This is iterate the original data
     * and call the mapper function for each , then for each intermediate
     * bucket created during the Map phase call the reduce function.
     *
     * @return void
     * @throws \LogicException if emitIntermediate was called but no reducer function
     * was provided
     */
    protected function _execute(): void
    {
        $mapper = $this->_mapper;
        foreach ($this->_data as $key => $val) {
            $mapper($val, $key, $this);
        }

        if ($this->_intermediate && $this->_reducer === null) {
            throw new LogicException('No reducer function was provided');
        }

        $reducer = $this->_reducer;
        if ($reducer !== null) {
            foreach ($this->_intermediate as $key => $list) {
                $reducer($list, $key, $this);
            }
        }
        $this->_intermediate = [];
        $this->_executed = true;
    }
}
