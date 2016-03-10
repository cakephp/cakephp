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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

use Cake\Utility\Crypto\Mcrypt;
use Cake\Utility\Crypto\OpenSsl;
use InvalidArgumentException;

/**
 * Security Library contains utility methods related to security
 *
 */
class Security
{

    /**
     * Default hash method. If `$type` param for `Security::hash()` is not specified
     * this value is used. Defaults to 'sha1'.
     *
     * @var string
     */
    public static $hashType = 'sha1';

    /**
     * The HMAC salt to use for encryption and decryption routines
     *
     * @var string
     */
    protected static $_salt;

    /**
     * The crypto implementation to use.
     *
     * @var object
     */
    protected static $_instance;

    /**
     * Create a hash from string using given method.
     *
     * @param string $string String to hash
     * @param string|null $type Hashing algo to use (i.e. sha1, sha256 etc.).
     *   Can be any valid algo included in list returned by hash_algos().
     *   If no value is passed the type specified by `Security::$hashType` is used.
     * @param mixed $salt If true, automatically prepends the application's salt
     *   value to $string (Security.salt).
     * @return string Hash
     * @link http://book.cakephp.org/3.0/en/core-libraries/security.html#hashing-data
     */
    public static function hash($string, $type = null, $salt = false)
    {
        if (empty($type)) {
            $type = static::$hashType;
        }
        $type = strtolower($type);

        if ($salt) {
            if (!is_string($salt)) {
                $salt = static::$_salt;
            }
            $string = $salt . $string;
        }

        return hash($type, $string);
    }

    /**
     * Sets the default hash method for the Security object. This affects all objects
     * using Security::hash().
     *
     * @param string $hash Method to use (sha1/sha256/md5 etc.)
     * @return void
     * @see \Cake\Utility\Security::hash()
     */
    public static function setHash($hash)
    {
        static::$hashType = $hash;
    }

    /**
     * Get random bytes from a secure source.
     *
     * This method will fall back to an insecure source an trigger a warning
     * if it cannot find a secure source of random data.
     *
     * @param int $length The number of bytes you want.
     * @return string Random bytes in binary.
     */
    public static function randomBytes($length)
    {
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return openssl_random_pseudo_bytes($length);
        }
        trigger_error(
            'You do not have a safe source of random data available. ' .
            'Install either the openssl extension, or paragonie/random_compat. ' .
            'Falling back to an insecure random source.',
            E_USER_WARNING
        );
        $bytes = '';
        while ($bytes < $length) {
            $bytes .= static::hash(Text::uuid() . uniqid(mt_rand(), true), 'sha512', true);
        }
        return substr($bytes, 0, $length);
    }

    /**
     * Get the crypto implementation based on the loaded extensions.
     *
     * You can use this method to forcibly decide between mcrypt/openssl/custom implementations.
     *
     * @param object $instance The crypto instance to use.
     * @return object Crypto instance.
     * @throws \InvalidArgumentException When no compatible crypto extension is available.
     */
    public static function engine($instance = null)
    {
        if ($instance === null && static::$_instance === null) {
            if (extension_loaded('openssl')) {
                $instance = new OpenSsl();
            } elseif (extension_loaded('mcrypt')) {
                $instance = new Mcrypt();
            }
        }
        if ($instance) {
            static::$_instance = $instance;
        }
        if (isset(static::$_instance)) {
            return static::$_instance;
        }
        throw new InvalidArgumentException(
            'No compatible crypto engine available. ' .
            'Load either the openssl or mcrypt extensions'
        );
    }

    /**
     * Encrypts/Decrypts a text using the given key using rijndael method.
     *
     * @param string $text Encrypted string to decrypt, normal string to encrypt
     * @param string $key Key to use as the encryption key for encrypted data.
     * @param string $operation Operation to perform, encrypt or decrypt
     * @throws \InvalidArgumentException When there are errors.
     * @return string Encrypted/Decrypted string
     */
    public static function rijndael($text, $key, $operation)
    {
        if (empty($key)) {
            throw new InvalidArgumentException('You cannot use an empty key for Security::rijndael()');
        }
        if (empty($operation) || !in_array($operation, ['encrypt', 'decrypt'])) {
            throw new InvalidArgumentException('You must specify the operation for Security::rijndael(), either encrypt or decrypt');
        }
        if (mb_strlen($key, '8bit') < 32) {
            throw new InvalidArgumentException('You must use a key larger than 32 bytes for Security::rijndael()');
        }
        $crypto = static::engine();
        return $crypto->rijndael($text, $key, $operation);
    }

    /**
     * Encrypt a value using AES-256.
     *
     * *Caveat* You cannot properly encrypt/decrypt data with trailing null bytes.
     * Any trailing null bytes will be removed on decryption due to how PHP pads messages
     * with nulls prior to encryption.
     *
     * @param string $plain The value to encrypt.
     * @param string $key The 256 bit/32 byte key to use as a cipher key.
     * @param string|null $hmacSalt The salt to use for the HMAC process. Leave null to use Security.salt.
     * @return string Encrypted data.
     * @throws \InvalidArgumentException On invalid data or key.
     */
    public static function encrypt($plain, $key, $hmacSalt = null)
    {
        self::_checkKey($key, 'encrypt()');

        if ($hmacSalt === null) {
            $hmacSalt = static::$_salt;
        }
        // Generate the encryption and hmac key.
        $key = mb_substr(hash('sha256', $key . $hmacSalt), 0, 32, '8bit');

        $crypto = static::engine();
        $ciphertext = $crypto->encrypt($plain, $key);
        $hmac = hash_hmac('sha256', $ciphertext, $key);
        return $hmac . $ciphertext;
    }

    /**
     * Check the encryption key for proper length.
     *
     * @param string $key Key to check.
     * @param string $method The method the key is being checked for.
     * @return void
     * @throws \InvalidArgumentException When key length is not 256 bit/32 bytes
     */
    protected static function _checkKey($key, $method)
    {
        if (mb_strlen($key, '8bit') < 32) {
            throw new InvalidArgumentException(
                sprintf('Invalid key for %s, key must be at least 256 bits (32 bytes) long.', $method)
            );
        }
    }

    /**
     * Decrypt a value using AES-256.
     *
     * @param string $cipher The ciphertext to decrypt.
     * @param string $key The 256 bit/32 byte key to use as a cipher key.
     * @param string|null $hmacSalt The salt to use for the HMAC process. Leave null to use Security.salt.
     * @return string Decrypted data. Any trailing null bytes will be removed.
     * @throws \InvalidArgumentException On invalid data or key.
     */
    public static function decrypt($cipher, $key, $hmacSalt = null)
    {
        self::_checkKey($key, 'decrypt()');
        if (empty($cipher)) {
            throw new InvalidArgumentException('The data to decrypt cannot be empty.');
        }
        if ($hmacSalt === null) {
            $hmacSalt = static::$_salt;
        }

        // Generate the encryption and hmac key.
        $key = mb_substr(hash('sha256', $key . $hmacSalt), 0, 32, '8bit');

        // Split out hmac for comparison
        $macSize = 64;
        $hmac = mb_substr($cipher, 0, $macSize, '8bit');
        $cipher = mb_substr($cipher, $macSize, null, '8bit');

        $compareHmac = hash_hmac('sha256', $cipher, $key);
        if (!static::_constantEquals($hmac, $compareHmac)) {
            return false;
        }

        $crypto = static::engine();
        return $crypto->decrypt($cipher, $key);
    }

    /**
     * A timing attack resistant comparison that prefers native PHP implementations.
     *
     * @param string $hmac The hmac from the ciphertext being decrypted.
     * @param string $compare The comparison hmac.
     * @return bool
     * @see https://github.com/resonantcore/php-future/
     */
    protected static function _constantEquals($hmac, $compare)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($hmac, $compare);
        }
        $hashLength = mb_strlen($hmac, '8bit');
        $compareLength = mb_strlen($compare, '8bit');
        if ($hashLength !== $compareLength) {
            return false;
        }
        $result = 0;
        for ($i = 0; $i < $hashLength; $i++) {
            $result |= (ord($hmac[$i]) ^ ord($compare[$i]));
        }
        return $result === 0;
    }

    /**
     * Gets or sets the HMAC salt to be used for encryption/decryption
     * routines.
     *
     * @param string|null $salt The salt to use for encryption routines. If null returns current salt.
     * @return string The currently configured salt
     */
    public static function salt($salt = null)
    {
        if ($salt === null) {
            return static::$_salt;
        }
        return static::$_salt = (string)$salt;
    }
}
