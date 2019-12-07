<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.7.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Session;

use Cake\Http\Session;
use Cake\Utility\Hash;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * SessionEquals
 *
 * @internal
 */
class SessionEquals extends Constraint
{
    /**
     * @var \Cake\Http\Session
     */
    protected $session;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * Constructor
     *
     * @param \Cake\Http\Session|null $session Session
     * @param string $path Session Path
     */
    public function __construct(?Session $session, string $path)
    {
        if (!$session) {
            throw new AssertionFailedError('There is no stored session data. Perhaps you need to run a request?');
        }

        $this->session = $session;
        $this->path = $path;
    }

    /**
     * Compare session value
     *
     * @param mixed $other Value to compare with
     * @return bool
     */
    public function matches($other): bool
    {
        // Server::run calls Session::close at the end of the request.
        // Which means, that we cannot use Session object here to access the session data.
        // Call to Session::read will start new session (and will erase the data).
        return Hash::get($_SESSION, $this->path) === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('is in session path \'%s\'', $this->path);
    }
}
