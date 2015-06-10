<?php

namespace Cake\Network\Cookie;

use Cake\Core\Configure;

class RequestCookieJar extends AbstractCookieJar
{

    public function __construct(array $cookies)
    {
        foreach ($cookies as $name => $cookieData) {
            $cookieData += (array)Configure::read("Cookies.$name");
            $cookieData['name'] = $name;

            $cookie = new $this->_cookieClassName($cookieData);
            $this->_cookies[$cookie->name()] = $cookie;
        }
    }
}
