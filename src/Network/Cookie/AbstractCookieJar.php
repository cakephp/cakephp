<?php

namespace Cake\Network\Cookie;

use IteratorAggregate;

abstract class AbstractCookieJar implements IteratorAggregate
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
     * @return null|\Cake\Network\Cookie\Cookie
     */
    public function get($name)
    {
        if (isset($this->_cookies[$name])) {
            return $this->_cookies[$name];
        }
    }

    /**
     *
     * @return array
     */
    public function getIterator()
    {
        return $this->_cookies;
    }
}
