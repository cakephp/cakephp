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
 * FlashParamEquals
 *
 * @internal
 */
class FlashParamEquals extends Constraint
{
    /**
     * @var \Cake\Http\Session
     */
    protected $session;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $param;

    /**
     * @var int|null
     */
    protected $at;

    /**
     * Constructor
     *
     * @param \Cake\Http\Session|null $session Session
     * @param string $key Flash key
     * @param string $param Param to check
     * @param int|null $at Expected index
     */
    public function __construct(?Session $session, string $key, string $param, ?int $at = null)
    {
        if (!$session) {
            $message = 'There is no stored session data. Perhaps you need to run a request?';
            $message .= ' Additionally, ensure `$this->enableRetainFlashMessages()` has been enabled for the test.';
            throw new AssertionFailedError($message);
        }

        $this->session = $session;
        $this->key = $key;
        $this->param = $param;
        $this->at = $at;
    }

    /**
     * Compare to flash message(s)
     *
     * @param mixed $other Value to compare with
     * @return bool
     */
    public function matches($other): bool
    {
        // Server::run calls Session::close at the end of the request.
        // Which means, that we cannot use Session object here to access the session data.
        // Call to Session::read will start new session (and will erase the data).
        /** @psalm-suppress InvalidScalarArgument */
        $messages = (array)Hash::get($_SESSION, 'Flash.' . $this->key);
        if ($this->at) {
            /** @psalm-suppress InvalidScalarArgument */
            $messages = [Hash::get($_SESSION, 'Flash.' . $this->key . '.' . $this->at)];
        }

        foreach ($messages as $message) {
            if (!isset($message[$this->param])) {
                continue;
            }
            if ($message[$this->param] === $other) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assertion message string
     *
     * @return string
     */
    public function toString(): string
    {
        if ($this->at !== null) {
            return sprintf('is in \'%s\' %s #%d', $this->key, $this->param, $this->at);
        }

        return sprintf('is in \'%s\' %s', $this->key, $this->param);
    }
}
