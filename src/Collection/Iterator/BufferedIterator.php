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
namespace Cake\Collection\Iterator;

use Cake\Collection\Collection;
use SplDoublyLinkedList;

/**
 * Creates an iterator from another iterator that will keep the results of the inner
 * iterator in memory, so that results don't have to be re-calculated.
 */
class BufferedIterator extends Collection
{
    /**
     * The in-memory cache containing results from previous iterators
     *
     * @var \SplDoublyLinkedList<mixed>
     */
    protected SplDoublyLinkedList $_buffer;

    /**
     * Points to the next record number that should be fetched
     *
     * @var int
     */
    protected int $_index = 0;

    /**
     * Last record fetched from the inner iterator
     *
     * @var mixed
     */
    protected mixed $_current;

    /**
     * Last key obtained from the inner iterator
     *
     * @var mixed
     */
    protected mixed $_key;

    /**
     * Whether the internal iterator's rewind method was already
     * called
     *
     * @var bool
     */
    protected bool $_started = false;

    /**
     * Whether the internal iterator has reached its end.
     *
     * @var bool
     */
    protected bool $_finished = false;

    /**
     * Maintains an in-memory cache of the results yielded by the internal
     * iterator.
     *
     * @param iterable $items The items to be filtered.
     */
    public function __construct(iterable $items)
    {
        $this->_buffer = new SplDoublyLinkedList();
        parent::__construct($items);
    }

    /**
     * Returns the current key in the iterator
     *
     * @return mixed
     */
    public function key(): mixed
    {
        return $this->_key;
    }

    /**
     * Returns the current record in the iterator
     *
     * @return mixed
     */
    public function current(): mixed
    {
        return $this->_current;
    }

    /**
     * Rewinds the collection
     *
     * @return void
     */
    public function rewind(): void
    {
        if ($this->_index === 0 && !$this->_started) {
            $this->_started = true;
            parent::rewind();

            return;
        }

        $this->_index = 0;
    }

    /**
     * Returns whether the iterator has more elements
     *
     * @return bool
     */
    public function valid(): bool
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
    public function next(): void
    {
        $this->_index++;

        // Don't move inner iterator if we have more buffer
        if ($this->_buffer->offsetExists($this->_index)) {
            return;
        }
        if (!$this->_finished) {
            parent::next();
        }
    }

    /**
     * Returns the number or items in this collection
     *
     * @return int
     */
    public function count(): int
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
     * Magic method used for serializing the iterator instance.
     *
     * @return array
     */
    public function __serialize(): array
    {
        if (!$this->_finished) {
            $this->count();
        }

        return iterator_to_array($this->_buffer);
    }

    /**
     * Magic method used to rebuild the iterator instance.
     *
     * @param array $data Data array.
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->__construct([]);

        foreach ($data as $value) {
            $this->_buffer->push($value);
        }

        $this->_started = true;
        $this->_finished = true;
    }
}
