<?php

namespace Cake\Network\Cookie;

class ResponseCookieJar
{
    protected $_cookies = [];

    public function get($name) {
        return $this->_cookies[$name];
    }
    
    public function create($name, $config = []) {
        $cookie = new Cookie($name, $config);

        // $this->add($cookie) ???

        return $cookie;
    }

    public function add(Cookie $cookie) {
        $this->_cookies[$cookie->name()] = $cookie;

        return $this;
    }

    /**
     * This would be called by Response at runtime.
     */
    public function set() {
        foreach($this->_cookies as $cookie) {
            setcookie(
                $cookie->name(),
                $cookie->value(),
                $cookie->config('expire'),
                $cookie->config('path'),
                $cookie->config('domain'),
                $cookie->config('secure'),
                $cookie->config('httpOnly')
            );
        }
    }
}
