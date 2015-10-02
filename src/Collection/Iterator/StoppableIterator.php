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

use Cake\Collection\Collection;

/**
 * Creates an iterator from another iterator that will verify a condition on each
 * step. If the condition evaluates to false, the iterator will not yield more
 * results.
 *
 * @internal
 * @see Collection::stopWhen()
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
     * @var \Iterator
     */
    protected $_innerIterator;

    /**
     * Creates an iterator that can be stopped based on a condition provided by a callback.
     *
     * Each time the condition callback is executed it will receive the value of the element
     * in the current iteration, the key of the element and the passed $items iterator
     * as arguments, in that order.
     *
     * @param array|\Traversable $items The list of values to iterate
     * @param callable $condition A function that will be called for each item in
     * the collection, if the result evaluates to false, no more items will be
     * yielded from this iterator.
     */
    public function __construct($items, callable $condition)
    {
        $this->_condition = $condition;
        parent::__construct($items);
        $this->_innerIterator = $this->getInnerIterator();
    }

    /**
     * Evaluates the condition and returns its result, this controls
     * whether or not more results will be yielded.
     *
     * @return bool
     */
    public function valid()
    {
        if (!parent::valid()) {
            return false;
        }

        $current = $this->current();
        $key = $this->key();
        $condition = $this->_condition;
        return !$condition($current, $key, $this->_innerIterator);
    }
}
