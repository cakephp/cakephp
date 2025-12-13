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

use ArrayIterator;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Iterator;
use Traversable;

/**
 * Creates an iterator from another iterator that will verify a condition on each
 * step. If the condition evaluates to false, the iterator will not yield more
 * results.
 *
 * @internal
 * @see \Cake\Collection\Collection::stopWhen()
 * @template TKey
 * @template TValue
 * @extends \Cake\Collection\Collection<TKey, TValue>
 */
class StoppableIterator extends Collection
{
    /**
     * The condition to evaluate for each item of the collection
     *
     * @var callable
     */
    protected $_condition;

    /**
     * A reference to the internal iterator this object is wrapping.
     *
     * @var \Traversable
     */
    protected Traversable $_innerIterator;

    /**
     * Creates an iterator that can be stopped based on a condition provided by a callback.
     *
     * Each time the condition callback is executed it will receive the value of the element
     * in the current iteration, the key of the element and the passed $items iterator
     * as arguments, in that order.
     *
     * @param iterable<TKey, TValue> $items The list of values to iterate
     * @param callable $condition A function that will be called for each item in
     * the collection, if the result evaluates to false, no more items will be
     * yielded from this iterator.
     */
    public function __construct(iterable $items, callable $condition)
    {
        $this->_condition = $condition;
        parent::__construct($items);
        $this->_innerIterator = $this->getInnerIterator();
    }

    /**
     * Evaluates the condition and returns its result, this controls
     * whether more results will be yielded.
     *
     * @return bool
     */
    public function valid(): bool
    {
        if (!parent::valid()) {
            return false;
        }

        $current = $this->current();
        $key = $this->key();
        $condition = $this->_condition;

        return !$condition($current, $key, $this->_innerIterator);
    }

    /**
     * @inheritDoc
     */
    public function unwrap(): Iterator
    {
        $iterator = $this->_innerIterator;

        if ($iterator instanceof CollectionInterface) {
            $iterator = $iterator->unwrap();
        }

        if ($iterator::class !== ArrayIterator::class) {
            return $this;
        }

        // ArrayIterator can be traversed strictly.
        // Let's do that for performance gains

        $callback = $this->_condition;
        $res = [];

        foreach ($iterator as $k => $v) {
            if ($callback($v, $k, $iterator)) {
                break;
            }
            $res[$k] = $v;
        }

        return new ArrayIterator($res);
    }
}
