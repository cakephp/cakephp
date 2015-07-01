<?php

namespace Cake\Network\Cookie;

use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Security;
use RuntimeException;

class CookieEncrypter
{

    const ALL = '_all_';
    const PREFIX = 'Q2FrZQ==.';

    use InstanceConfigTrait;

    /**
     * Default config
     *
     * @var array
     */
    protected $_defaultConfig = [
        static::ALL => [
            'key' => null,
            'encryption' => 'aes'
        ]
    ];

    /**
     * Valid cipher names for encrypted cookies.
     *
     * @var array
     */
    protected $_validCiphers = ['aes', 'rijndael'];

    /**
     *
     * @param array $config
     */
    protected function __construct(array $config = [])
    {
        $this->_defaultConfig[static::ALL]['key'] = Security::salt();

        $this->config($config);
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     * @return string
     */
    public function encrypt($name, $value)
    {
        $options = $this->_encryption($name);

        if (is_array($value)) {
            $value = $this->_implode($value);
        }
        if (!$options['mode']) {
            return $value;
        }
        $this->_checkCipher($options['mode']);
        if ($options['mode'] === 'rijndael') {
            $cipher = Security::rijndael($value, $options['key'], 'encrypt');
        }
        if ($options['mode'] === 'aes') {
            $cipher = Security::encrypt($value, $options['key']);
        }
        return static::PREFIX . base64_encode($cipher);
    }

    /**
     *
     * @param string $name
     * @param mixed $values
     * @return mixed
     */
    public function decrypt($name, $values)
    {
        $options += $this->_encryption($name);

        if (is_string($values)) {
            return $this->_decode($values, $options);
        }

        $decrypted = [];
        foreach ($values as $name => $value) {
            $decrypted[$name] = $this->_decode($value);
        }
        return $decrypted;
    }

    /**
     *
     * @param string $mode
     * @return void
     * @throws \RuntimeException
     */
    protected function _checkCipher($mode)
    {
        if (!in_array($mode, $this->_validCiphers)) {
            $msg = sprintf(
                'Invalid encryption cipher. Must be one of %s.',
                implode(', ', $this->_validCiphers)
            );
            throw new RuntimeException($msg);
        }
    }

    /**
     *
     * @param mixed $value
     * @param array $options
     * @return mixed
     */
    protected function _decode($value, $options)
    {
        if (!$options['mode']) {
            return $this->_explode($value);
        }
        $this->_checkCipher($options['mode']);
        $value = base64_decode(substr($value, strlen(static::PREFIX)));
        if ($options['mode'] === 'rijndael') {
            $value = Security::rijndael($value, $options['key'], 'decrypt');
        }
        if ($options['mode'] === 'aes') {
            $value = Security::decrypt($value, $options['key']);
        }
        return $this->_explode($value);
    }

    /**
     *
     * @param array $array
     * @return string
     */
    protected function _implode(array $array)
    {
        return json_encode($array);
    }

    /**
     *
     * @param string $string
     * @return array
     */
    protected function _explode($string)
    {
        $first = substr($string, 0, 1);
        if ($first === '{' || $first === '[') {
            $ret = json_decode($string, true);
            return ($ret !== null) ? $ret : $string;
        }
        $array = [];
        foreach (explode(',', $string) as $pair) {
            $key = explode('|', $pair);
            if (!isset($key[1])) {
                return $key[0];
            }
            $array[$key[0]] = $key[1];
        }
        return $array;
    }

    /**
     *
     * @param string $name
     * @return array
     */
    protected function _encryption($name)
    {
        $config = (array)$this->config($name);
        $config += $this->_config[static::ALL];

        return $config;
    }
}
