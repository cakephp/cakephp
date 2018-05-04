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
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

use Cake\Utility\Crypto\Mcrypt;
use Cake\Utility\Crypto\OpenSsl;
use InvalidArgumentException;
use RuntimeException;

/**
 * Security Library contains utility methods related to security
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
     * @param string|null $algorithm Hashing algo to use (i.e. sha1, sha256 etc.).
     *   Can be any valid algo included in list returned by hash_algos().
     *   If no value is passed the type specified by `Security::$hashType` is used.
     * @param mixed $salt If true, automatically prepends the application's salt
     *   value to $string (Security.salt).
     * @return string Hash
     * @link https://book.cakephp.org/3.0/en/core-libraries/security.html#hashing-data
     */
    public static function hash($string, $algorithm = null, $salt = false)
    {
        if (empty($algorithm)) {
            $algorithm = static::$hashType;
        }
        $algorithm = strtolower($algorithm);

        $availableAlgorithms = hash_algos();
        if (!in_array($algorithm, $availableAlgorithms)) {
            throw new RuntimeException(sprintf(
                'The hash type `%s` was not found. Available algorithms are: %s',
                $algorithm,
                implode(', ', $availableAlgorithms)
            ));
        }

        if ($salt) {
            if (!is_string($salt)) {
                $salt = static::$_salt;
            }
            $string = $salt . $string;
        }

        return hash($algorithm, $string);
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
        if (!function_exists('openssl_random_pseudo_bytes')) {
            throw new RuntimeException(
                'You do not have a safe source of random data available. ' .
                'Install either the openssl extension, or paragonie/random_compat. ' .
                'Or use Security::insecureRandomBytes() alternatively.'
            );
        }

        $bytes = openssl_random_pseudo_bytes($length, $strongSource);
        if (!$strongSource) {
            trigger_error(
                'openssl was unable to use a strong source of entropy. ' .
                'Consider updating your system libraries, or ensuring ' .
                'you have more available entropy.',
                E_USER_WARNING
            );
        }

        return $bytes;
    }

    /**
     * Creates a secure random string.
     *
     * @param int $length String length. Default 64.
     * @return string
     * @since 3.6.0
     */
    public static function randomString($length = 64)
    {
        return substr(
            bin2hex(Security::randomBytes(ceil($length / 2))),
            0,
            $length
        );
    }

    /**
     * Like randomBytes() above, but not cryptographically secure.
     *
     * @param int $length The number of bytes you want.
     * @return string Random bytes in binary.
     * @see \Cake\Utility\Security::randomBytes()
     */
    public static function insecureRandomBytes($length)
    {
        $length *= 2;

        $bytes = '';
        $byteLength = 0;
        while ($byteLength < $length) {
            $bytes .= static::hash(Text::uuid() . uniqid(mt_rand(), true), 'sha512', true);
            $byteLength = strlen($bytes);
        }
        $bytes = substr($bytes, 0, $length);

        return pack('H*', $bytes);
    }

    /**
     * Get the crypto implementation based on the loaded extensions.
     *
     * You can use this method to forcibly decide between mcrypt/openssl/custom implementations.
     *
     * @param \Cake\Utility\Crypto\OpenSsl|\Cake\Utility\Crypto\Mcrypt|null $instance The crypto instance to use.
     * @return \Cake\Utility\Crypto\OpenSsl|\Cake\Utility\Crypto\Mcrypt Crypto instance.
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
     * @return string Encrypted/Decrypted string.
     * @deprecated 3.6.3 This method relies on functions provided by mcrypt
     *   extension which has been deprecated in PHP 7.1 and removed in PHP 7.2.
     *   There's no 1:1 replacement for this method.
     *   Upgrade your code to use Security::encrypt()/Security::decrypt() with
     *   OpenSsl engine instead.
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
     * @return string|bool Decrypted data. Any trailing null bytes will be removed.
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
        if (!static::constantEquals($hmac, $compareHmac)) {
            return false;
        }

        $crypto = static::engine();

        return $crypto->decrypt($cipher, $key);
    }

    /**
     * A timing attack resistant comparison that prefers native PHP implementations.
     *
     * @param string $original The original value.
     * @param string $compare The comparison value.
     * @return bool
     * @see https://github.com/resonantcore/php-future/
     * @since 3.6.2
     */
    public static function constantEquals($original, $compare)
    {
        if (!is_string($original) || !is_string($compare)) {
            return false;
        }
        if (function_exists('hash_equals')) {
            return hash_equals($original, $compare);
        }
        $originalLength = mb_strlen($original, '8bit');
        $compareLength = mb_strlen($compare, '8bit');
        if ($originalLength !== $compareLength) {
            return false;
        }
        $result = 0;
        for ($i = 0; $i < $originalLength; $i++) {
            $result |= (ord($original[$i]) ^ ord($compare[$i]));
        }

        return $result === 0;
    }

    /**
     * Gets the HMAC salt to be used for encryption/decryption
     * routines.
     *
     * @return string The currently configured salt
     */
    public static function getSalt()
    {
        return static::$_salt;
    }

    /**
     * Sets the HMAC salt to be used for encryption/decryption
     * routines.
     *
     * @param string $salt The salt to use for encryption routines.
     * @return void
     */
    public static function setSalt($salt)
    {
        static::$_salt = (string)$salt;
    }

    /**
     * Gets or sets the HMAC salt to be used for encryption/decryption
     * routines.
     *
     * @deprecated 3.5.0 Use getSalt()/setSalt() instead.
     * @param string|null $salt The salt to use for encryption routines. If null returns current salt.
     * @return string The currently configured salt
     */
    public static function salt($salt = null)
    {
        deprecationWarning(
            'Security::salt() is deprecated. ' .
            'Use Security::getSalt()/setSalt() instead.'
        );
        if ($salt === null) {
            return static::$_salt;
        }

        return static::$_salt = (string)$salt;
    }
}
