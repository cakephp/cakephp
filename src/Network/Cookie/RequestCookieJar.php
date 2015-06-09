<?php

namespace Cake\Network\Cookie;

class RequestCookieJar
{
    protected $_cookies = [];

    public function __construct()
    {
        foreach ($_COOKIE as $name => $value) {
            $this->_cookies[$name] = new Cookie($name, $value);
        }
    }

    public function get($name) {
        return $this->_cookies[$name];
    }
}
