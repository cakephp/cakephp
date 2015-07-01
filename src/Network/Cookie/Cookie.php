<?php

namespace Cake\Network\Cookie;

use Cake\Core\InstanceConfigTrait;
use Cake\I18n\Time;
use Cake\Utility\Hash;
use UnexpectedValueException;

class Cookie implements CookieInterface
{

    const TIMEOUT = '-1 month';

    use InstanceConfigTrait;

    /**
     * Default config
     *
     * @var array
     */
    protected $_defaultConfig = [
        'path' => null,
        'domain' => '',
        'secure' => false,
        'httpOnly' => false,
        'expires' => '+1 month',
    ];

    /**
     *
     * @var string
     */
    protected $_name;

    /**
     *
     * @var mixed
     */
    protected $_value;

    /**
     *
     * @param string|array $config Cookie name or an array with cookie options.
     * @param mixed $value An optional value to be set to this cookie.
     * @throws UnexpectedValueException
     */
    public function __construct($config = [], $value = null)
    {
        if (is_string($config)) {
            $config = ['name' => $config];
        }

        if (!isset($config['name']) || $config['name'] === '') {
            throw new UnexpectedValueException('Cookie must have a name.');
        }

        if (is_string($config['name'])) {
            $this->_name = $config['name'];
        } else {
            $msg = sprintf("Cookie name must be a string, %s given.", gettype($config['name']));
            throw new UnexpectedValueException($msg);
        }
        unset($config['name']);

        if ($value !== null) {
            $config['value'] = $value;
        }

        if (isset($config['value'])) {
            $this->_value = $config['value'];
            unset($config['value']);
        }

        $this->config($config);
    }

    /**
     *
     * @param string|null $key
     * @return mixed
     */
    public function read($key = null)
    {
        if ($key === null || !is_array($this->_value)) {
            return $this->_value;
        }

        return Hash::get($this->_value, $key);
    }

    /**
     *
     * @param mixed $key
     * @param mixed $value
     * @return \Cake\Network\Cookie\Cookie
     */
    public function write($key, $value = null)
    {
        if ($value !== null) {
            $this->_insert($key, $value);
        } else {
            $this->_merge($key);
        }

        return $this;
    }

    /**
     *
     * @param mixed $value
     * @return void
     */
    protected function _merge($value)
    {
        if (is_array($this->_value)) {
            $this->_value = Hash::merge($this->_value, $value);
        } else {
            $this->_value = $value;
        }
    }

    /**
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    protected function _insert($key, $value)
    {
        if (!is_array($this->_value)) {
            $this->_value = (array)$this->_value;
        }

        $this->_value = Hash::insert($this->_value, $key, $value);
    }

    /**
     *
     * @param string|null $key
     * @return \Cake\Network\Cookie\Cookie
     */
    public function remove($key = null)
    {
        if ($key == null || !is_array($this->_value)) {
            $this->_value = null;
        } else {
            $this->_value = Hash::remove($this->_value, $key);
        }

        return $this;
    }

    /**
     *
     * @return \Cake\Network\Cookie\Cookie
     */
    public function invalidate()
    {
        $this->expires(static::TIMEOUT);

        return $this;
    }

    /**
     *
     * @param string $name
     * @return string|\Cake\Network\Cookie\Cookie
     */
    public function name($name = null)
    {
        if ($name !== null) {
            $this->_name = $name;

            return $this;
        }

        return $name;
    }

    /**
     *
     * @param string $path
     * @return string|\Cake\Network\Cookie\Cookie
     */
    public function path($path = null)
    {
        return $this->config('path', $path);
    }

    /**
     *
     * @param string $domain
     * @return string|\Cake\Network\Cookie\Cookie
     */
    public function domain($domain = null)
    {
        return $this->config('domain', $domain);
    }

    /**
     *
     * @param bool $secure
     * @return bool|\Cake\Network\Cookie\Cookie
     */
    public function secure($secure = null)
    {
        return $this->config('secure', $secure);
    }

    /**
     *
     * @param bool $httpOnly
     * @return bool|\Cake\Network\Cookie\Cookie
     */
    public function httpOnly($httpOnly = null)
    {
        return $this->config('httpOnly', $httpOnly);
    }

    /**
     *
     * @param mixed $expires
     * @return \Cake\I18n\Time|\Cake\Network\Cookie\Cookie
     */
    public function expires($expires = null)
    {
        $value = $this->config('expires', $expires);

        if ($expires !== null) {
            return $value;
        }

        return new Time($value);
    }
}
