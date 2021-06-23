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
 * @since         3.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Auth\Storage;

/**
 * Describes the methods that any class representing an Auth data storage should
 * comply with.
 *
 * @mixin \Cake\Core\InstanceConfigTrait
 */
interface StorageInterface
{
    /**
     * Read user record.
     *
     * @return \ArrayAccess|array|null
     */
    public function read();

    /**
     * Write user record.
     *
     * @param mixed $user array or \ArrayAccess User record.
     * @return void
     */
    public function write($user): void;

    /**
     * Delete user record.
     *
     * @return void
     */
    public function delete(): void;

    /**
     * Get/set redirect URL.
     *
     * @param mixed $url Redirect URL. If `null` returns current URL. If `false`
     *   deletes currently set URL.
     * @return array|string|null
     */
    public function redirectUrl($url = null);
}
