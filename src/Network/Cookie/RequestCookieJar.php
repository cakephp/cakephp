<?php

namespace Cake\Network\Cookie;

class RequestCookieJar extends AbstractCookieJar
{

    /**
     *
     * @param array $cookies
     */
    public function __construct(array $cookies)
    {
        foreach ($cookies as $name => $value) {
            $this->_cookies[$name] = $value;
        }
    }

    /**
     *
     * @param string $name
     * @return null|\Cake\Network\CookieCookieInterface
     */
    public function get($name)
    {
        $value = parent::get($name);

        if ($value !== null) {
            $cookie = $this->_create($name, $value);

            return $cookie;
        }
    }

    /**
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public function offsetSet($name, $value)
    {
        $this->_cookies[$name] = $value;
    }

    /**
     * No op - you can't remove cookies from a request.
     *
     * @param string $name
     * @return void
     */
    public function offsetUnset($name)
    {
    }
}
