<?php

namespace Cake\Network\Cookie;

use RuntimeException;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Security;

class CookieEncrypter
{

    const PREFIX = 'Q2FrZQ==.';

    use InstanceConfigTrait;

    /**
     * Default config
     *
     * @var array
     */
    protected $_defaultConfig = [
        'salt' => null,
        'encryption' => 'aes'
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
        $config += [
            'salt' => Security::salt()
        ];

        $this->config($config);
    }

    /**
     *
     * @param mixed $value
     * @param array $options
     * @return string
     */
    public function encrypt($value, array $options = [])
    {
        $options += $this->_config;

        if (is_array($value)) {
            $value = $this->_implode($value);
        }
        if (!$options['mode']) {
            return $value;
        }
        $this->_checkCipher($options['mode']);
        if ($options['mode'] === 'rijndael') {
            $cipher = Security::rijndael($value, $options['salt'], 'encrypt');
        }
        if ($options['mode'] === 'aes') {
            $cipher = Security::encrypt($value, $options['salt']);
        }
        return static::PREFIX . base64_encode($cipher);
    }

    /**
     *
     * @param mixed $values
     * @param array $options
     * @return mixed
     */
    public function decrypt($values, array $options = [])
    {
        $options += $this->_config;

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
     * @throws \RuntimeException
     */
    protected function _checkCipher($mode)
    {
        if (!in_array($mode, $this->_validCiphers)) {
            $msg = sprintf(
                'Invalid encryption cipher. Must be one of %s.', implode(', ', $this->_validCiphers)
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
            $value = Security::rijndael($value, $options['salt'], 'decrypt');
        }
        if ($options['mode'] === 'aes') {
            $value = Security::decrypt($value, $options['salt']);
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
}
