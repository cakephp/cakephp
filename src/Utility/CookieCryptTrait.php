<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.1.6
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

use RuntimeException;

/**
 * Cookie Crypt Trait.
 *
 * Provides the encrypt/decrypt logic for the CookieComponent.
 *
 * @link https://book.cakephp.org/3.0/en/controllers/components/cookie.html
 */
trait CookieCryptTrait
{

    /**
     * Valid cipher names for encrypted cookies.
     *
     * @var array
     */
    protected $_validCiphers = ['aes', 'rijndael'];

    /**
     * Returns the encryption key to be used.
     *
     * @return string
     */
    abstract protected function _getCookieEncryptionKey();

    /**
     * Encrypts $value using public $type method in Security class
     *
     * @param string $value Value to encrypt
     * @param string|bool $encrypt Encryption mode to use. False
     *   disabled encryption.
     * @param string|null $key Used as the security salt if specified.
     * @return string Encoded values
     */
    protected function _encrypt($value, $encrypt, $key = null)
    {
        if (is_array($value)) {
            $value = $this->_implode($value);
        }
        if ($encrypt === false) {
            return $value;
        }
        $this->_checkCipher($encrypt);
        $prefix = 'Q2FrZQ==.';
        $cipher = null;
        if ($key === null) {
            $key = $this->_getCookieEncryptionKey();
        }
        if ($encrypt === 'rijndael') {
            $cipher = Security::rijndael($value, $key, 'encrypt');
        }
        if ($encrypt === 'aes') {
            $cipher = Security::encrypt($value, $key);
        }

        return $prefix . base64_encode($cipher);
    }

    /**
     * Helper method for validating encryption cipher names.
     *
     * @param string $encrypt The cipher name.
     * @return void
     * @throws \RuntimeException When an invalid cipher is provided.
     */
    protected function _checkCipher($encrypt)
    {
        if (!in_array($encrypt, $this->_validCiphers)) {
            $msg = sprintf(
                'Invalid encryption cipher. Must be one of %s or false.',
                implode(', ', $this->_validCiphers)
            );
            throw new RuntimeException($msg);
        }
    }

    /**
     * Decrypts $value using public $type method in Security class
     *
     * @param array $values Values to decrypt
     * @param string|bool $mode Encryption mode
     * @param string|null $key Used as the security salt if specified.
     * @return string|array Decrypted values
     */
    protected function _decrypt($values, $mode, $key = null)
    {
        if (is_string($values)) {
            return $this->_decode($values, $mode, $key);
        }

        $decrypted = [];
        foreach ($values as $name => $value) {
            $decrypted[$name] = $this->_decode($value, $mode, $key);
        }

        return $decrypted;
    }

    /**
     * Decodes and decrypts a single value.
     *
     * @param string $value The value to decode & decrypt.
     * @param string|false $encrypt The encryption cipher to use.
     * @param string|null $key Used as the security salt if specified.
     * @return string|array Decoded values.
     */
    protected function _decode($value, $encrypt, $key)
    {
        if (!$encrypt) {
            return $this->_explode($value);
        }
        $this->_checkCipher($encrypt);
        $prefix = 'Q2FrZQ==.';
        $prefixLength = strlen($prefix);

        if (strncmp($value, $prefix, $prefixLength) !== 0) {
            return '';
        }

        $value = base64_decode(substr($value, $prefixLength), true);

        if ($value === false || $value === '') {
            return '';
        }

        if ($key === null) {
            $key = $this->_getCookieEncryptionKey();
        }
        if ($encrypt === 'rijndael') {
            $value = Security::rijndael($value, $key, 'decrypt');
        }
        if ($encrypt === 'aes') {
            $value = Security::decrypt($value, $key);
        }

        if ($value === false) {
            return '';
        }

        return $this->_explode($value);
    }

    /**
     * Implode method to keep keys are multidimensional arrays
     *
     * @param array $array Map of key and values
     * @return string A json encoded string.
     */
    protected function _implode(array $array)
    {
        return json_encode($array);
    }

    /**
     * Explode method to return array from string set in CookieComponent::_implode()
     * Maintains reading backwards compatibility with 1.x CookieComponent::_implode().
     *
     * @param string $string A string containing JSON encoded data, or a bare string.
     * @return string|array Map of key and values
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
