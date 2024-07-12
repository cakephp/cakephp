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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Map;

/**
 * Describe the interface for a map of keys, values, and other metadata.
 */
interface MapInterface
{
    /**
     * Sets a value in the map with the given key and type.
     *
     * @param string $key
     * @param mixed $value
     * @throws \InvalidArgumentException
     */
    public function set(string $key, mixed $value): void;

    /**
     * Gets a value from the map by the given key.
     *
     * @param string $key
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function get(string $key): mixed;
}
