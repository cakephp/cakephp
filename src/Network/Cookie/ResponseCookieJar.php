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
     * @param string $name
     * @param bool|string|array $encryption
     * @return void
     */
    public function queue($name, $encryption = null)
    {
        $cookie = $this->get($name);
        if ($cookie) {
            $raw = [
                'value' => $this->_encrypt($cookie, $encryption),
                'path' => $cookie->path(),
                'expire' => $cookie->expires()->format('U'),
                'domain' => $cookie->domain(),
                'secure' => $cookie->secure(),
                'httpOnly' => $cookie->httpOnly()
            ];

            $this->_rawCookies[$cookie->name()] = $raw;

            $this->remove($name);
        }
    }

    /**
     *
     * @param string $name
     * @param bool|string|array $encryption
     * @return null|\Cake\Network\Cookie\Cookie
     */
    public function unqueue($name, $encryption = null)
    {
        if (isset($this->_rawCookies[$name])) {
            $cookie = $this->_rawCookies[$name];
            $cookie['name'] = $name;
            $cookie['value'] = $this->_decrypt($cookie['value'], $encryption);

            unset($this->_rawCookies[$name]);

            return $this->add($cookie);
        }
    }

    /**
     *
     * @return array
     */
    public function raw()
    {
        foreach (array_keys($this->_cookies) as $name) {
            $this->queue($name);
        }

        return $this->_rawCookies;
    }
}
