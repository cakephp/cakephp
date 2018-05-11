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
namespace Cake\TestSuite\Constraint\Response;

use Cake\Http\Response;
use Cake\Utility\CookieCryptTrait;
use InvalidArgumentException;

/**
 * CookieEncryptedEquals
 *
 * @internal
 */
class CookieEncryptedEquals extends CookieEquals
{
    use CookieCryptTrait;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $mode;

    /**
     * Constructor.
     *
     * @param Response $response Response
     * @param string $cookieName Cookie name
     * @param string $mode Mode
     * @param string $key Key
     */
    public function __construct(Response $response, $cookieName, $mode, $key)
    {
        parent::__construct($response, $cookieName);

        $this->key = $key;
        $this->mode = $mode;
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected content
     * @return bool
     */
    public function matches($other)
    {
        $cookie = $this->response->getCookie($this->cookieName);

        return $this->_decrypt($cookie['value'], $this->mode) === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString()
    {
        return sprintf('was encrypted in cookie \'%s\'', $this->cookieName);
    }

    /**
     * Returns the encryption key
     *
     * @return string
     */
    protected function _getCookieEncryptionKey()
    {
        return $this->key;
    }
}
