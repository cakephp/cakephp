<?php

namespace Cake\Network\Cookie;

use Cake\Network\Cookie\CookieEncrypter;

class RequestCookieJar extends AbstractCookieJar
{

    /**
     *
     * @param array $cookies
     */
    public function __construct(array $cookies, CookieEncrypter $encrypter = null)
    {
        parent::__construct($encrypter);

        foreach ($cookies as $name => $cookieData) {
            $this->_rawCookies[$name] = $cookieData;
        }
    }

    /**
     *
     * @param string $name
     * @param string|bool|array $encryption
     * @return \Cake\Network\Cookie\Cookie
     */
    public function get($name, $encryption = null)
    {
        $cookie = parent::get($name);

        if ($cookie === null && isset($this->_rawCookies[$name])) {
            $value = $this->_decrypt($this->_rawCookies[$name], $encryption);

            $cookie = new $this->_cookieClassName($name, $value);

            $this->_cookies[$cookie->name()] = $cookie;
        }

        return $cookie;
    }
}
