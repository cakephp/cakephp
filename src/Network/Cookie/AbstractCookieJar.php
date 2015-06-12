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
            $this->_encrypter = new $this->_cookieEncrypterClassName;
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
        if (isset($this->_cookies[$name])) {
            return $this->_cookies[$name];
        }
    }

    /**
     *
     * @return array
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_cookies);
    }

    /**
     *
     * @param string $value
     * @param mixed $encryption
     * @return mixed
     */
    protected function _decrypt($value, $encryption)
    {
        $encryption = $this->_encryption($encryption);

        return $this->encrypter()->decrypt($value, $encryption);
    }

    /**
     *
     * @param mixed $value
     * @param mixed $encryption
     * @return string
     */
    protected function _encrypt($value, $encryption)
    {
        $encryption = $this->_encryption($encryption);

        return $this->encrypter()->encrypt($value, $encryption);
    }

    /**
     *
     * @param mixed $encryption
     * @return array
     */
    protected function _encryption($encryption)
    {
        if (!is_array($encryption)) {
            $encryption = ['mode' => $encryption];
        }

        return $encryption;
    }
}
