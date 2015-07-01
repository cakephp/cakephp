<?php

namespace Cake\Network\Cookie;

class ResponseCookieJar extends AbstractCookieJar
{

    /**
     *
     * @param string|array|Cookie $cookie String for a cookie name, array for a cookie config or a Cookie object.
     * @param mixed $value Optional cookie value.
     * @return \Cake\Network\Cookie\CookieInterface
     */
    public function set($cookie, $value = null)
    {
        if (!$cookie instanceof $this->_cookieClassName) {
            $cookie = $this->_create($cookie, $value);
        }

        $this->_cookies[$cookie->name()] = $cookie;

        return $cookie;
    }

    /**
     *
     * @param string $name
     * @return null|\Cake\Network\Cookie\CookieInterface
     */
    public function remove($name)
    {
        $cookie = $this->get($name);
        if ($cookie) {
            unset($this->_cookies[$name]);
        }

        return $cookie;
    }

    /**
     *
     * @param string $name
     * @return null|\Cake\Network\Cookie\CookieInterface
     */
    public function invalidate($name)
    {
        $cookie = $this->get($name);
        if ($cookie) {
            return $cookie->invalidate();
        }
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function offsetSet($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     *
     * @param string $name
     * @return void
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }
}
