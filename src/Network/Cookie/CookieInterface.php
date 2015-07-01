<?php

namespace Cake\Network\Cookie;

interface CookieInterface
{

    /**
     *
     * @param string|null $key
     * @return mixed
     */
    public function read($key = null);

    /**
     *
     * @param mixed $key
     * @param mixed $value
     * @return \Cake\Network\Cookie\CookieInterface
     */
    public function write($key, $value = null);

    /**
     *
     * @param string|null $key
     * @return \Cake\Network\Cookie\CookieInterface
     */
    public function remove($key = null);

    /**
     *
     * @return \Cake\Network\Cookie\CookieInterface
     */
    public function invalidate();

    /**
     *
     * @param string $name
     * @return string|\Cake\Network\Cookie\CookieInterface
     */
    public function name($name = null);

    /**
     *
     * @param string $path
     * @return string|\Cake\Network\Cookie\CookieInterface
     */
    public function path($path = null);

    /**
     *
     * @param string $domain
     * @return string|\Cake\Network\Cookie\CookieInterface
     */
    public function domain($domain = null);

    /**
     *
     * @param bool $secure
     * @return bool|\Cake\Network\Cookie\CookieInterface
     */
    public function secure($secure = null);

    /**
     *
     * @param bool $httpOnly
     * @return bool|\Cake\Network\Cookie\CookieInterface
     */
    public function httpOnly($httpOnly = null);

    /**
     *
     * @param mixed $expires
     * @return \Cake\I18n\Time|\Cake\Network\Cookie\CookieInterface
     */
    public function expires($expires = null);
}
