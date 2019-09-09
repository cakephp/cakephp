<?php
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
     * @param \Cake\Http\Session $session Session
     * @param string $path Session Path
     */
    public function __construct($session, $path)
    {
        parent::__construct();

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
    public function matches($other)
    {
        return $this->session->read($this->path) === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString()
    {
        return sprintf('is in session path \'%s\'', $this->path);
    }
}
