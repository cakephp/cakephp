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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection\Iterator;

use ArrayIterator;
use IteratorAggregate;

/**
 * Implements a simplistic version of the popular Map-Reduce algorithm. Acts
 * like an iterator for the original passed data after each result has been
 * processed, thus offering a transparent wrapper for results coming from any
 * source.
 */
class MapReduce implements IteratorAggregate
{

    /**
     * Holds the shuffled results that were emitted from the map
     * phase
     *
     * @var array
     */
    protected $_intermediate = [];

    /**
     * Holds the results as emitted during the reduce phase
     *
     * @var array
     */
    protected $_result = [];

    /**
     * Whether the Map-Reduce routine has been executed already on the data
     *
     * @var bool
     */
    protected $_executed = false;

    /**
     * Holds the original data that needs to be processed
     *
     * @var \Traversable
     */
    protected $_data;

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
     * @var callable
     */
    protected $_reducer;

    /**
     * Count of elements emitted during the Reduce phase
     *
     * @var string
     */
    protected $_counter = 0;

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
     * @param \Traversable $data the original data to be processed
     * @param callable $mapper the mapper callback. This function will receive 3 arguments.
     * The first one is the current value, second the current results key and third is
     * this class instance so you can call the result emitters.
     * @param callable $reducer the reducer callback. This function will receive 3 arguments.
     * The first one is the list of values inside a bucket, second one is the name
     * of the bucket that was created during the mapping phase and third one is an
     * instance of this class.
     */
    public function __construct(\Traversable $data, callable $mapper, callable $reducer = null)
    {
        $this->_data = $data;
        $this->_mapper = $mapper;
        $this->_reducer = $reducer;
    }

    /**
     * Returns an iterator with the end result of running the Map and Reduce
     * phases on the original data
     *
     * @return \ArrayIterator
     */
    public function getIterator()
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
     * @param mixed $value The record itself to store in the bucket
     * @param string $bucket the name of the bucket where to put the record
     * @return void
     */
    public function emitIntermediate($value, $bucket)
    {
        $this->_intermediate[$bucket][] = $value;
    }

    /**
     * Appends a new record to the final list of results and optionally assign a key
     * for this record.
     *
     * @param mixed $value The value to be appended to the final list of results
     * @param string $key and optional key to assign to the value
     * @return void
     */
    public function emit($value, $key = null)
    {
        $this->_result[$key === null ? $this->_counter : $key] = $value;
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
    protected function _execute()
    {
        $mapper = $this->_mapper;
        foreach ($this->_data as $key => $value) {
            $mapper($value, $key, $this);
        }
        $this->_data = null;

        if (!empty($this->_intermediate) && empty($this->_reducer)) {
            throw new \LogicException('No reducer function was provided');
        }

        $reducer = $this->_reducer;
        foreach ($this->_intermediate as $key => $list) {
            $reducer($list, $key, $this);
        }
        $this->_intermediate = [];
        $this->_executed = true;
    }
}
