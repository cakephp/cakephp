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

use Cake\Collection\Iterator\NoChildrenIterator;
use IteratorIterator;
use RecursiveIterator;

/**
 * An iterator that can be used to generate nested iterators out of each of
 * applying an function to each of the elements in this iterator.
 *
 * @internal
 * @see Collection::unfold()
 */
class UnfoldIterator extends IteratorIterator implements RecursiveIterator
{

    /**
     * A functions that gets passed each of the elements of this iterator and
     * that must return an array or Traversable object.
     *
     * @var callable
     */
    protected $_unfolder;

    /**
     * Creates the iterator that will generate child iterators from each of the
     * elements it was constructed with.
     *
     * @param array|\Traversable $items The list of values to iterate
     * @param callable $unfolder A callable function that will receive the
     * current item and key. It must return an array or Traversable object
     * out of which the nested iterators will be yielded.
     */
    public function __construct($items, callable $unfolder)
    {
        $this->_unfolder = $unfolder;
        parent::__construct($items);
    }

    /**
     * Returns true as each of the elements in the array represent a
     * list of items
     *
     * @return bool
     */
    public function hasChildren()
    {
        return true;
    }

    /**
     * Returns an iterator containing the items generated out of transforming
     * the current value with the callable function.
     *
     * @return \RecursiveIterator
     */
    public function getChildren()
    {
        $current = $this->current();
        $key = $this->key();
        $unfolder = $this->_unfolder;

        return new NoChildrenIterator($unfolder($current, $key, $this));
    }
}
