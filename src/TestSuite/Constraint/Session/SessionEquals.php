<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.7.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Session;

use Cake\Utility\Hash;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * SessionEquals
 *
 * @internal
 */
class SessionEquals extends Constraint
{
    /**
     * @var string
     */
    protected string $path;

    /**
     * Constructor
     *
     * @param string $path Session Path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * Compare session value
     *
     * @param mixed $other Value to compare with
     * @return bool
     */
    public function matches(mixed $other): bool
    {
        // Server::run calls Session::close at the end of the request.
        // Which means, that we cannot use Session object here to access the session data.
        // Call to Session::read will start new session (and will erase the data).
        /** @psalm-suppress InvalidScalarArgument */
        return Hash::get($_SESSION, $this->path) === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf("is in session path '%s'", $this->path);
    }
}
