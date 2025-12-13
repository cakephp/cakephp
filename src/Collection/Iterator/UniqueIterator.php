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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Collection\Iterator;

use Cake\Collection\Collection;

/**
 * Creates a filtered iterator from another iterator. The filtering is done by
 * passing a callback function to each of the elements and taking them out if
 * the value returned is not unique.
 *
 * @template TKey
 * @template TValue
 * @extends \Cake\Collection\Collection<TKey, TValue>
 */
class UniqueIterator extends Collection
{
    /**
     * Creates a filtered iterator using the callback to determine which items are
     * accepted or rejected.
     *
     * The callback is passed the value as the first argument and the key as the
     * second argument.
     *
     * @param iterable<TKey, TValue> $items The items to be filtered.
     * @param callable $callback Callback.
     */
    public function __construct(iterable $items, callable $callback)
    {
        $unique = [];
        $uniqueValues = [];
        foreach ($items as $k => $v) {
            $compareValue = $callback($v, $k);
            if (!in_array($compareValue, $uniqueValues, true)) {
                $unique[$k] = $v;
                $uniqueValues[] = $compareValue;
            }
        }

        parent::__construct($unique);
    }
}
