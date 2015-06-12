<?php

namespace Cake\Network\Cookie;

use Cake\Network\Cookie\CookieEncrypter;

class RequestCookieJar extends AbstractCookieJar
{

    /**
     *
     * @param array $cookies
     * @param CookieEncrypter $encrypter
     */
    public function __construct(array $cookies, CookieEncrypter $encrypter = null)
    {
        parent::__construct($encrypter);

        foreach ($cookies as $name => $value) {
            $this->_cookies[$name] = $value;
        }
    }

    /**
     *
     * @param string $name
     * @param string|bool|array $encryption
     * @return null|\Cake\Network\Cookie\Cookie
     */
    public function get($name, $encryption = null)
    {
        $value = parent::get($name);

        if ($value !== null) {
            $value = $this->_decrypt($value, $encryption);
            $cookie = new $this->_cookieClassName($name, $value);

            return $cookie;
        }
    }
}
