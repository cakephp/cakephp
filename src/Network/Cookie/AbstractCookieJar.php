<?php

namespace Cake\Network\Cookie;

use ArrayIterator;
use Cake\Network\Cookie\CookieEncrypter;
use IteratorAggregate;

abstract class AbstractCookieJar implements IteratorAggregate
{

    /**
     *
     * @var array
     */
    protected $_cookies = [];

    /**
     *
     * @var array
     */
    protected $_rawCookies = [];

    /**
     *
     * @var \Cake\Network\Cookie\CookieEncrypter
     */
    protected $_encrypter;

    /**
     *
     * @var string
     */
    protected $_cookieClassName = 'Cake\Network\Cookie\Cookie';

    /**
     *
     * @var string
     */
    protected $_cookieEncrypterClassName = 'Cake\Network\Cookie\CookieEncrypter';

    /**
     *
     * @param \Cake\Network\Cookie\CookieEncrypter $encrypter
     */
    public function __construct(CookieEncrypter $encrypter = null)
    {
        $this->_encrypter = $encrypter;
    }

    /**
     *
     * @param null|\Cake\Network\Cookie\CookieEncrypter $encrypter
     * @return \Cake\Network\Cookie\CookieEncrypter
     */
    public function encrypter(CookieEncrypter $encrypter = null)
    {
        if ($encrypter !== null) {
            $this->_encrypter = $encrypter;
        }
        if ($this->_encrypter === null) {
            $this->_encrypter = new $this->_cookieEncrypterClassName();
        }

        return $this->_encrypter;
    }

    /**
     *
     * @param string $name
     * @return null|\Cake\Network\Cookie\Cookie
     */
    public function get($name)
    {
        if ($this->has($name)) {
            return $this->_cookies[$name];
        }
    }

    /**
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_cookies[$name]);
    }

    /**
     *
     * @return array
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_cookies);
    }
}
