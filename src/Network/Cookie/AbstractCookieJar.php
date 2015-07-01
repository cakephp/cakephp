<?php

namespace Cake\Network\Cookie;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

abstract class AbstractCookieJar implements ArrayAccess, IteratorAggregate
{

    /**
     *
     * @var array
     */
    protected $_cookies = [];

    /**
     *
     * @var string
     */
    protected $_cookieClassName = 'Cake\Network\Cookie\Cookie';

    /**
     *
     * @param string $name
     * @return null|\Cake\Network\CookieCookieInterface
     */
    public function get($name)
    {
        if ($this->has($name)) {
            return $this->_cookies[$name];
        }
    }

    /**
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_cookies[$name]);
    }

    /**
     *
     * @return array
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_cookies);
    }

    /**
     *
     * @param mixed $name
     * @param mixed $value
     * @return \Cake\Network\Cookie\CookieInterface
     */
    protected function _create($name, $value)
    {
        return new $this->_cookieClassName($name, $value);
    }

    /**
     *
     * @param string $name
     * @return bool
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }
}
