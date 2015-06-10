<?php

namespace Cake\Network\Cookie;

class ResponseCookieJar extends AbstractCookieJar
{

    /**
     *
     * @param string|array|Cookie $cookie String for a cookie name, array for a cookie config or a Cookie object.
     * @param mixed $value Optional cookie value.
     * @return \Cake\Network\Cookie\Cookie
     */
    public function add($cookie, $value = null)
    {
        if (!$cookie instanceof $this->_cookieClassName) {
            $cookie = new $this->_cookieClassName($cookie, $value);
        }

        $this->_cookies[$cookie->name()] = $cookie;

        return $cookie;
    }

    /**
     *
     * @param string $name
     * @return null|\Cake\Network\Cookie\Cookie
     */
    public function remove($name)
    {
        if (isset($this->_cookies[$name])) {
            $cookie = $this->_cookies[$name];
            unset($this->_cookies[$name]);

            return $cookie;
        }
    }

    /**
     *
     * @param string $name
     * @return null|\Cake\Network\Cookie\Cookie
     */
    public function forget($name)
    {
        if (isset($this->_cookies[$name])) {
            $cookie = $this->_cookies[$name];
            return $cookie->forget();
        }
    }
}
