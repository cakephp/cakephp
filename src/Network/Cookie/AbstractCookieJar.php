<?php

namespace Cake\Network\Cookie;

use IteratorAggregate;

abstract class AbstractCookieJar implements IteratorAggregate
{

    protected $_cookies = [];

    protected $_cookieClassName = 'Cake\Network\Cookie\Cookie';

    public function get($name)
    {
        if (isset($this->_cookies[$name])) {
            return $this->_cookies[$name];
        }
    }

    public function getIterator()
    {
        return $this->_cookies;
    }
}
