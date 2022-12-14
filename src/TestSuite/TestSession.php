<?php
declare(strict_types=1);

/**
 * A class to contain and retain the session during integration testing.
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
 * @since         4.0.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Utility\Hash;

/**
 * Read only access to the session during testing.
 */
class TestSession
{
    /**
     * @var array|null
     */
    protected ?array $session = null;

    /**
     * @param array|null $session Session data.
     */
    public function __construct(?array $session)
    {
        $this->session = $session;
    }

    /**
     * Returns true if given variable name is set in session.
     *
     * @param string|null $name Variable name to check for
     * @return bool True if variable is there
     */
    public function check(?string $name = null): bool
    {
        if ($this->session === null) {
            return false;
        }

        if ($name === null) {
            return (bool)$this->session;
        }

        return Hash::get($this->session, $name) !== null;
    }

    /**
     * Returns given session variable, or all of them, if no parameters given.
     *
     * @param string|null $name The name of the session variable (or a path as sent to Hash.extract)
     * @return mixed The value of the session variable, null if session not available,
     *   session not started, or provided name not found in the session.
     */
    public function read(?string $name = null): mixed
    {
        if ($this->session === null) {
            return null;
        }

        if ($name === null) {
            return $this->session ?: [];
        }

        return Hash::get($this->session, $name);
    }
}
