<?php

namespace Cake\Network\Cookie;

use Cake\Core\InstanceConfigTrait;
use Cake\I18n\Time;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use UnexpectedValueException;

class Cookie
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
        'salt' => null,
        'httpOnly' => false,
        'encryption' => 'aes',
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
     * @var string
     */
    protected $_cookieEncryptor = 'Cake\Network\Cookie\CookieEncryptor';

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
            $this->value($config['value']);
            unset($config['value']);
        }

        $config += [
            'salt' => Security::salt()
        ];

        $this->config($config);
    }

    /**
     *
     * @param string|null $key
     * @return mixed
     */
    public function read($key = null)
    {
        if ($key === null) {
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
            Hash::insert($this->_value, $key, $value);
        } else {
            Hash::merge($this->_value, $key);
        }

        return $this;
    }

    /**
     *
     * @param string $key
     * @return \Cake\Network\Cookie\Cookie
     */
    public function remove($key)
    {
        Hash::remove($this->_value, $key);

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

    /**
     *
     * @param string $value
     * @return string
     */
    public function value($value = null)
    {
        if ($value === null) {
            return $this->_encrypt();
        }
        $this->_value = $this->_decrypt($value);

        return $this;
    }

    /**
     *
     * @return string
     */
    protected function _encrypt()
    {
        $className = $this->_cookieEncryptor;

        return $className::encrypt(
            $this->_value,
            $this->_config['encryption'],
            $this->_config['salt']
        );
    }

    /**
     *
     * @param string $value
     * @return mixed
     */
    protected function _decrypt($value)
    {
        $className = $this->_cookieEncryptor;

        return $className::decrypt($value, $this->_config['encryption'], $this->_config['salt']);
    }
}
