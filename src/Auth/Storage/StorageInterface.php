<?php
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
     * @param array|\ArrayAccess $user User record.
     * @return void
     */
    public function write($user);

    /**
     * Delete user record.
     *
     * @return void
     */
    public function delete();

    /**
     * Get/set redirect URL.
     *
     * @param mixed $url Redirect URL. If `null` returns current URL. If `false`
     *   deletes currently set URL.
     * @return string|array|null
     */
    public function redirectUrl($url = null);
}
