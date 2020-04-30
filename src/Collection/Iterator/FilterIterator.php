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
use CallbackFilterIterator;
use Iterator;
use Traversable;

/**
 * Creates a filtered iterator from another iterator. The filtering is done by
 * passing a callback function to each of the elements and taking them out if
 * it does not return true.
 */
class FilterIterator extends Collection
{
    /**
     * The callback used to filter the elements in this collection
     *
     * @var callable
     */
    protected $_callback;

    /**
     * Creates a filtered iterator using the callback to determine which items are
     * accepted or rejected.
     *
     * Each time the callback is executed it will receive the value of the element
     * in the current iteration, the key of the element and the passed $items iterator
     * as arguments, in that order.
     *
     * @param \Traversable|array $items The items to be filtered.
     * @param callable $callback Callback.
     */
    public function __construct($items, callable $callback)
    {
        if (!$items instanceof Iterator) {
            $items = new Collection($items);
        }

        $this->_callback = $callback;
        $wrapper = new CallbackFilterIterator($items, $callback);
        parent::__construct($wrapper);
    }

    /**
     * @inheritDoc
     */
    public function unwrap(): Traversable
    {
        /** @var \IteratorIterator $filter */
        $filter = $this->getInnerIterator();
        $iterator = $filter->getInnerIterator();

        if ($iterator instanceof CollectionInterface) {
            $iterator = $iterator->unwrap();
        }

        if (get_class($iterator) !== ArrayIterator::class) {
            return $filter;
        }

        // ArrayIterator can be traversed strictly.
        // Let's do that for performance gains
        $callback = $this->_callback;
        $res = [];

        foreach ($iterator as $k => $v) {
            if ($callback($v, $k, $iterator)) {
                $res[$k] = $v;
            }
        }

        return new ArrayIterator($res);
    }
}
