<?php

namespace Cake\Network\Cookie;

use Cake\Core\InstanceConfigTrait;
use Cake\I18n\Time;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use UnexpectedValueException;

class Cookie
{

    const EXPIRES_FORMAT = 'U';
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
     * @param mixed        $value  An optional value to be set to this cookie.
     * @throws UnexpectedValueException
     */
    public function __construct($config = [], $value = null)
    {
        if (is_string($config)) {
            $config = ['name' => $config];
        }

        if (empty($config['name'])) {
            throw new UnexpectedValueException('Cookie must have a name.');
        }

        $this->_name = $config['name'];
        unset($config['name']);

        if ($value !== null) {
            $config['value'] = $value;
        }

        if (isset($config['value'])) {
            $this->_value = $this->_decrypt($config['value']);
            unset($config['value']);
        }

        $config += [
            'key' => Security::salt()
        ];

        $this->config($config);
    }

    public function read($key = null)
    {
        if ($key === null) {
            return $this->_value;
        }

        return Hash::get($this->_value, $key);
    }

    public function write($key, $value = null)
    {
        if ($value !== null) {
            Hash::insert($this->_value, $key, $value);
        } else {
            Hash::merge($this->_value, $key);
        }

        return $this;
    }

    public function remove($key)
    {
        return Hash::remove($this->_value, $key);
    }

    public function forget()
    {
        $this->expires(static::TIMEOUT);

        return $this;
    }

    public function name($name = null)
    {
        if ($name !== null) {
            $this->_name = $name;

            return $this;
        }

        return $name;
    }

    public function path($path = null)
    {
        return $this->config('path', $path);
    }

    public function domain($domain = null)
    {
        return $this->config('domain', $domain);
    }

    public function secure($secure = null)
    {
        return $this->config('secure', $secure);
    }

    public function httpOnly($httpOnly = null)
    {
        return $this->config('httpOnly', $httpOnly);
    }

    public function expires($expires = null)
    {
        $value = $this->config('expires', $expires);

        if ($expires !== null) {
            return $expires;
        }

        $expires = new Time($value);

        return $expires->format(self::EXPIRES_FORMAT);
    }

    public function value()
    {
        return $this->_encrypt();
    }

    protected function _encrypt()
    {
        $className = $this->_cookieEncryptor;

        return $className::encrypt(
            $this->_value,
            $this->_config['encryption'],
            $this->_config['salt']
        );
    }

    protected function _decrypt($value)
    {
        $className = $this->_cookieEncryptor;

        return $className::decrypt($value, $this->_config['encryption'], $this->_config['salt']);
    }
}
