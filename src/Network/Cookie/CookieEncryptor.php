<?php

namespace Cake\Network\Cookie;

use RuntimeException;
use Cake\Utility\Security;

class CookieEncryptor
{

    const PREFIX = 'Q2FrZQ==.';

    /**
     * Valid cipher names for encrypted cookies.
     *
     * @var array
     */
    protected static $_validCiphers = ['aes', 'rijndael'];

    /**
     *
     * @param mixed $value
     * @param string $mode
     * @param string $salt
     * @return string
     */
    public static function encrypt($value, $mode, $salt)
    {
        if (is_array($value)) {
            $value = static::_implode($value);
        }
        if (!$mode) {
            return $value;
        }
        static::_checkCipher($mode);
        if ($mode === 'rijndael') {
            $cipher = Security::rijndael($value, $salt, 'encrypt');
        }
        if ($mode === 'aes') {
            $cipher = Security::encrypt($value, $salt);
        }
        return static::PREFIX . base64_encode($cipher);
    }

    /**
     *
     * @param mixed $values
     * @param string $mode
     * @param string $salt
     * @return mixed
     */
    public static function decrypt($values, $mode, $salt)
    {
        if (is_string($values)) {
            return static::_decode($values, $mode, $salt);
        }

        $decrypted = [];
        foreach ($values as $name => $value) {
            $decrypted[$name] = static::_decode($value, $mode, $salt);
        }
        return $decrypted;
    }

    /**
     *
     * @param string $mode
     * @throws \RuntimeException
     */
    protected static function _checkCipher($mode)
    {
        if (!in_array($mode, static::$_validCiphers)) {
            $msg = sprintf(
                'Invalid encryption cipher. Must be one of %s.',
                implode(', ', static::$_validCiphers)
            );
            throw new RuntimeException($msg);
        }
    }

    /**
     *
     * @param mixed $value
     * @param string $mode
     * @param string $salt
     * @return mixed
     */
    protected static function _decode($value, $mode, $salt)
    {
        if (!$mode) {
            return static::_explode($value);
        }
        static::_checkCipher($mode);
        $value = base64_decode(substr($value, strlen(static::PREFIX)));
        if ($mode === 'rijndael') {
            $value = Security::rijndael($value, $salt, 'decrypt');
        }
        if ($mode === 'aes') {
            $value = Security::decrypt($value, $salt);
        }
        return static::_explode($value);
    }

    /**
     *
     * @param array $array
     * @return string
     */
    protected static function _implode(array $array)
    {
        return json_encode($array);
    }

    /**
     *
     * @param string $string
     * @return array
     */
    protected static function _explode($string)
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
