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

use ArrayIterator;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;

/**
 * An iterator that will return the passed items in order. The order is given by
 * the value returned in a callback function that maps each of the elements.
 *
 * ### Example:
 *
 * ```
 * $items = [$user1, $user2, $user3];
 * $sorted = new SortIterator($items, function ($user) {
 *  return $user->age;
 * });
 *
 * // output all user name order by their age in descending order
 * foreach ($sorted as $user) {
 *  echo $user->name;
 * }
 * ```
 *
 * This iterator does not preserve the keys passed in the original elements.
 */
class SortIterator extends Collection
{

    /**
     * The callback function to use for extracting the sortable property.
     *
     * @var callable
     */
    protected $_callback;

    /**
     * The sorting direction
     *
     * @var integer
     */
    protected $_dir;

    /**
     * The sorting type
     *
     * @var integer
     */
    protected $_type;

    /**
     * Wraps this iterator around the passed items so when iterated they are returned
     * in order.
     *
     * The callback will receive as first argument each of the elements in $items,
     * the value returned in the callback will be used as the value for sorting such
     * element. Please not that the callback function could be called more than once
     * per element.
     *
     * @param array|\Traversable $items The values to sort
     * @param callable|string $callback A function used to return the actual value to
     * be compared. It can also be a string representing the path to use to fetch a
     * column or property in each element
     * @param int $dir either SORT_DESC or SORT_ASC
     * @param int $type the type of comparison to perform, either SORT_STRING
     * SORT_NUMERIC or SORT_NATURAL
     */
    public function __construct($items, $callback, $dir = SORT_DESC, $type = SORT_NUMERIC)
    {
        parent::__construct($items);
        $this->_callback = $callback;
        $this->_dir = $dir;
        $this->_type = $type;
    }

    /**
     * Returns the iterator wrapped by this class
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        $items = iterator_to_array(parent::getIterator(), false);
        $callback = $this->_propertyExtractor($this->_callback);
        $results = [];
        foreach ($items as $key => $value) {
            $results[$key] = $callback($value);
        }

        $this->_dir === SORT_DESC ?
            arsort($results, $this->_type) :
            asort($results, $this->_type);

        foreach (array_keys($results) as $key) {
            $results[$key] = $items[$key];
        }

        return new ArrayIterator($results);
    }
}
