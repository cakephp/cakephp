<?php

namespace Cake\Network\Cookie;

use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;

class Cookie
{

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

    public function __construct($name, $config = [])
    {
        $this->_name = $name;

        if (is_string($config)) {
            $config = ['value' => $config];
        }

        if (isset($config['value'])) {
            $this->write($config['value']);
            unset($config['value']);
        }

        $this->config($config);
    }

    public function read($key)
    {
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

    public function delete($key)
    {
        return Hash::remove($this->_value, $key);
    }

    public function forget()
    {
        //forget entire cookie - timeout or something
    }

    public function name($name = null)
    {
        if ($name !== null) {
            $this->_name = $name;

            return $this;
        }

        return $name;
    }

    public function value()
    {
        //return encrypted value
    }
}
