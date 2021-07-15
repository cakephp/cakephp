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
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Cache;

/**
 * Interface for cache engines that defines methods
 * outside of the PSR16 interface that are used by `Cache`.
 *
 * Internally Cache uses this interface when calling engine
 * methods.
 *
 * @since 3.7.0
 */
interface CacheEngineInterface
{
    /**
     * Write data for key into a cache engine if it doesn't exist already.
     *
     * @param string $key Identifier for the data.
     * @param mixed $value Data to be cached - anything except a resource.
     * @return bool True if the data was successfully cached, false on failure.
     *   Or if the key existed already.
     */
    public function add(string $key, mixed $value): bool;

    /**
     * Increment a number under the key and return incremented value
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to add
     * @return int|false New incremented value, false otherwise
     */
    public function increment(string $key, int $offset = 1): int|false;

    /**
     * Decrement a number under the key and return decremented value
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return int|false New incremented value, false otherwise
     */
    public function decrement(string $key, int $offset = 1): int|false;

    /**
     * Clear all values belonging to the named group.
     *
     * Each implementation needs to decide whether actually
     * delete the keys or just augment a group generation value
     * to achieve the same result.
     *
     * @param string $group name of the group to be cleared
     * @return bool
     */
    public function clearGroup(string $group): bool;
}
