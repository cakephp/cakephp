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
use CallbackFilterIterator;
use Iterator;

/**
 * Creates a filtered iterator from another iterator. The filtering is done by
 * passing a callback function to each of the elements and taking them out if
 * it does not return true.
 */
class FilterIterator extends Collection
{

    /**
     * Creates a filtered iterator using the callback to determine which items are
     * accepted or rejected.
     *
     * Each time the callback is executed it will receive the value of the element
     * in the current iteration, the key of the element and the passed $items iterator
     * as arguments, in that order.
     *
     * @param \Iterator $items The items to be filtered.
     * @param callable $callback Callback.
     */
    public function __construct(Iterator $items, callable $callback)
    {
        $wrapper = new CallbackFilterIterator($items, $callback);
        parent::__construct($wrapper);
    }
}
