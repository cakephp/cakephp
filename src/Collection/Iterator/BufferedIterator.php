<?php
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
namespace Cake\Collection\Iterator;

use Cake\Collection\Collection;
use Countable;
use Serializable;
use SplDoublyLinkedList;

/**
 * Creates an iterator from another iterator that will keep the results of the inner
 * iterator in memory, so that results don't have to be re-calculated.
 */
class BufferedIterator extends Collection implements Countable, Serializable
{
    /**
     * The in-memory cache containing results from previous iterators
     *
     * @var \SplDoublyLinkedList
     */
    protected $_buffer;

    /**
     * Points to the next record number that should be fetched
     *
     * @var int
     */
    protected $_index = 0;

    /**
     * Last record fetched from the inner iterator
     *
     * @var mixed
     */
    protected $_current;

    /**
     * Last key obtained from the inner iterator
     *
     * @var mixed
     */
    protected $_key;

    /**
     * Whether or not the internal iterator's rewind method was already
     * called
     *
     * @var bool
     */
    protected $_started = false;

    /**
     * Whether or not the internal iterator has reached its end.
     *
     * @var bool
     */
    protected $_finished = false;

    /**
     * Maintains an in-memory cache of the results yielded by the internal
     * iterator.
     *
     * @param array|\Traversable $items The items to be filtered.
     */
    public function __construct($items)
    {
        $this->_buffer = new SplDoublyLinkedList();
        parent::__construct($items);
    }

    /**
     * Returns the current key in the iterator
     *
     * @return mixed
     */
    public function key()
    {
        return $this->_key;
    }

    /**
     * Returns the current record in the iterator
     *
     * @return mixed
     */
    public function current()
    {
        return $this->_current;
    }

    /**
     * Rewinds the collection
     *
     * @return void
     */
    public function rewind()
    {
        if ($this->_index === 0 && !$this->_started) {
            $this->_started = true;
            parent::rewind();

            return;
        }

        $this->_index = 0;
    }

    /**
     * Returns whether or not the iterator has more elements
     *
     * @return bool
     */
    public function valid()
    {
        if ($this->_buffer->offsetExists($this->_index)) {
            $current = $this->_buffer->offsetGet($this->_index);
            $this->_current = $current['value'];
            $this->_key = $current['key'];

            return true;
        }

        $valid = parent::valid();

        if ($valid) {
            $this->_current = parent::current();
            $this->_key = parent::key();
            $this->_buffer->push([
                'key' => $this->_key,
                'value' => $this->_current,
            ]);
        }

        $this->_finished = !$valid;

        return $valid;
    }

    /**
     * Advances the iterator pointer to the next element
     *
     * @return void
     */
    public function next()
    {
        $this->_index++;

        if (!$this->_finished) {
            parent::next();
        }
    }

    /**
     * Returns the number or items in this collection
     *
     * @return int
     */
    public function count()
    {
        if (!$this->_started) {
            $this->rewind();
        }

        while ($this->valid()) {
            $this->next();
        }

        return $this->_buffer->count();
    }

    /**
     * Returns a string representation of this object that can be used
     * to reconstruct it
     *
     * @return string
     */
    public function serialize()
    {
        if (!$this->_finished) {
            $this->count();
        }

        return serialize($this->_buffer);
    }

    /**
     * Unserializes the passed string and rebuilds the BufferedIterator instance
     *
     * @param string $buffer The serialized buffer iterator
     * @return void
     */
    public function unserialize($buffer)
    {
        $this->__construct([]);
        $this->_buffer = unserialize($buffer);
        $this->_started = true;
        $this->_finished = true;
    }
}
