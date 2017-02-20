<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Cookie;

use Cake\Utility\Security;
use RuntimeException;

/**
 * Cookie Crypt Trait.
 *
 * Provides the encrypt/decrypt logic.
 */
trait CookieCryptTrait
{

    /**
     * Valid cipher names for encrypted cookies.
     *
     * @var array
     */
    protected $_validCiphers = [
        'aes',
        'rijndael'
    ];

    /**
     * Encryption cipher
     *
     * @param string
     */
    protected $encryptionCipher = 'aes';

    /**
     * The key for encrypting and decrypting the cookie
     *
     * @var string
     */
    protected $encryptionKey = '';

    /**
     * Sets the encryption cipher
     *
     * @param string $cipher Cipher
     * @return $this
     */
    public function setEncryptionCipher($cipher)
    {
        $this->checkCipher($cipher);
        $this->encryptionCipher = $cipher;

        return $this;
    }

    /**
     * Sets the encryption key
     *
     * @param string $key Encryption key
     * @return $this
     */
    public function setEncryptionKey($key)
    {
        $this->encryptionKey = $key;

        return $this;
    }

    /**
     * Returns the encryption key to be used.
     *
     * @return string
     */
    public function getEncryptionKey()
    {
        if (empty($this->encryptionKey)) {
            return Security::salt();
        }

        return $this->encryptionKey;
    }

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
            $value = $this->_flatten($value);
        }

        $this->checkCipher($encrypt);
        $prefix = 'Q2FrZQ==.';
        $cipher = null;

        if ($key === null) {
            $key = $this->getCryptoKey();
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
    protected function checkCipher($encrypt)
    {
        if (!in_array($encrypt, $this->_validCiphers)) {
            $msg = sprintf(
                'Invalid encryption cipher. Must be one of %s.',
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
            return $this->_expand($value);
        }

        $this->checkCipher($encrypt);
        $prefix = 'Q2FrZQ==.';
        $value = base64_decode(substr($value, strlen($prefix)));
        if ($key === null) {
            $key = $this->getEncryptionKey();
        }
        if ($encrypt === 'rijndael') {
            $value = Security::rijndael($value, $key, 'decrypt');
        }
        if ($encrypt === 'aes') {
            $value = Security::decrypt($value, $key);
        }

        return $this->_expand($value);
    }
}
