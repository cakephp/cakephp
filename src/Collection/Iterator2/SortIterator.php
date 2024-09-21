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
namespace Cake\Collection\Iterator2;

use Cake\Chronos\ChronosDate;
use Cake\Chronos\ChronosTime;
use Cake\Collection\Collection2;
use DateTimeInterface;
use Iterator;
use const SORT_DESC;
use const SORT_NUMERIC;

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
class SortIterator extends Collection2
{
    /**
     * Wraps this iterator around the passed items so when iterated they are returned
     * in order.
     *
     * The callback will receive as first argument each of the elements in $items,
     * the value returned in the callback will be used as the value for sorting such
     * element. Please note that the callback function could be called more than once
     * per element.
     *
     * @param iterable $items The values to sort
     * @param callable|string $callback A function used to return the actual value to
     * be compared. It can also be a string representing the path to use to fetch a
     * column or property in each element
     * @param int $dir either SORT_DESC or SORT_ASC
     * @param int $type the type of comparison to perform, either SORT_STRING
     * SORT_NUMERIC or SORT_NATURAL
     */
    public function __construct(
        iterable $items,
        callable|string $callback,
        int $dir = SORT_DESC,
        int $type = SORT_NUMERIC
    ) {
        if (!is_array($items)) {
            $items = iterator_to_array((new Collection2($items))->unwrap(), false);
        }

        $callback = $this->_propertyExtractor($callback);
        $results = [];
        foreach ($items as $key => $val) {
            $val = $callback($val);
            $isDateTime =
                $val instanceof ChronosDate ||
                $val instanceof ChronosTime ||
                $val instanceof DateTimeInterface;
            if ($isDateTime && $type === SORT_NUMERIC) {
                $val = $val->format('U');
            }
            $results[$key] = $val;
        }

        $dir === SORT_DESC ? arsort($results, $type) : asort($results, $type);

        foreach (array_keys($results) as $key) {
            $results[$key] = $items[$key];
        }
        parent::__construct($results);
    }

    /**
     * {@inheritDoc}
     *
     * @return \Iterator
     */
    public function unwrap(): Iterator
    {
        return $this->getInnerIterator();
    }
}
