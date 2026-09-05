<?php
declare(strict_types=1);

/**
 * Cache Session save handler. Allows saving session information into Cache.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         5.4.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Session;

use Cake\Core\Exception\CakeException;
use SessionHandlerInterface;

abstract class AbstractSession implements SessionHandlerInterface
{
    /**
     * Method called on open of a database session.
     *
     * @param string $path The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool Success
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Method called on close of a database session.
     *
     * @return bool Success
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Validate a session ID.
     *
     * @param string $id The session ID to validate.
     * @return bool
     */
    public function validateId(string $id): bool
    {
        return true;
    }

    /**
     * Create a new session ID.
     *
     * @return string
     */
    public function create_sid(): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return session_create_id() ?: throw new CakeException('Unable to create a session ID.');
    }

    /**
     * Read session data.
     *
     * @param string $id ID that uniquely identifies session in cache.
     * @return string|false Session data or false if it does not exist.
     */
    abstract public function read(string $id): string|false;

    /**
     * Write session data.
     *
     * @param string $id ID that uniquely identifies session in cache.
     * @param string $data The data to be saved.
     * @return bool True for successful write, false otherwise.
     */
    abstract public function write(string $id, string $data): bool;

    /**
     * Destroy a session.
     *
     * @param string $id ID that uniquely identifies session in cache.
     * @return bool
     */
    abstract public function destroy(string $id): bool;

    /**
     * Cleanup old sessions.
     *
     * @param int $max_lifetime Sessions that have not updated for the last maxlifetime seconds will be removed.
     * @return int|false
     */
    abstract public function gc(int $max_lifetime): int|false;
}
