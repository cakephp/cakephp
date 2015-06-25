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
    public function set($cookie, $value = null)
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
        $cookie = $this->get($name);
        if ($cookie) {
            unset($this->_cookies[$name]);
        }

        return $cookie;
    }

    /**
     *
     * @param string $name
     * @return null|\Cake\Network\Cookie\Cookie
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
     * @return array
     */
    public function raw()
    {
        $cookies = [];
        foreach ($this->_cookies as $cookie) {
            $raw = [
                'value' => $this->_encrypter->encrypt($cookie->name(), $cookie->read()),
                'path' => $cookie->path(),
                'expire' => $cookie->expires()->format('U'),
                'domain' => $cookie->domain(),
                'secure' => $cookie->secure(),
                'httpOnly' => $cookie->httpOnly()
            ];

            $cookies[] = $raw;
        }

        return $cookies;
    }
}
