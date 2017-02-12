<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client\Cookie;

use InvalidArgumentException;
use Iterator;
use Psr\Http\Message\ServerRequestInterface;

class RequestCookies extends CookieCollection implements Iterator
{
    /**
     * Create instance from a server request.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @return \Cake\Http\Client\Cookie\RequestCookies
     */
    public static function createFromRequest(ServerRequestInterface $request)
    {
        $cookies = [];
        $cookieParams = $request->getCookieParams();

        foreach ($cookieParams as $name => $value) {
            $cookies[] = new Cookie($name, $value);
        }

        return new static($cookies);
    }

    /**
     * Checks if the collection has a cookie with the given name
     *
     * @param string $name Name of the cookie
     * @return bool
     */
    public function has($name)
    {
        $key = mb_strtolower($name);

        return isset($this->cookies[$key]);
    }

    /**
     * Get a cookie from the collection by name.
     *
     * @param string $name Name of the cookie to get
     * @throws \InvalidArgumentException
     * @return Cookie
     */
    public function get($name)
    {
        $key = mb_strtolower($name);
        if (isset($this->cookies[$key]) === false) {
            throw new InvalidArgumentException(sprintf('Cookie `%s` does not exist', $name));
        }

        return $this->cookies[$key];
    }

    /**
     * Current
     *
     * @return \Cake\Http\Client\Cookie\Cookie $cookie
     */
    public function current()
    {
        return current($this->cookies);
    }

    /**
     * Key
     *
     * @return string
     */
    public function key()
    {
        $key = key($this->cookies);
        if ($key === null) {
            return $key;
        }
        $cookie = $this->cookies[$key];

        return $cookie->getName();
    }

    /**
     * Next
     *
     * @return void
     */
    public function next()
    {
        next($this->cookies);
    }

    /**
     * Valid
     *
     * @return bool
     */
    public function valid()
    {
        return key($this->cookies) !== null;
    }

    /**
     * Rewind
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->cookies);
    }
}
