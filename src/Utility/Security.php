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

use InvalidArgumentException;

/**
 * Security Library contains utility methods related to security
 *
 */
class Security {

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
 * Generate authorization hash.
 *
 * @return string Hash
 */
	public static function generateAuthKey() {
		return Security::hash(String::uuid());
	}

/**
 * Create a hash from string using given method.
 *
 * @param string $string String to hash
 * @param string $type Hashing algo to use (i.e. sha1, sha256 etc.).
 *   Can be any valid algo included in list returned by hash_algos().
 *   If no value is passed the type specified by `Security::$hashType` is used.
 * @param mixed $salt If true, automatically prepends the application's salt
 *   value to $string (Security.salt).
 * @return string Hash
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/security.html#Security::hash
 */
	public static function hash($string, $type = null, $salt = false) {
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
 * @see Security::hash()
 */
	public static function setHash($hash) {
		static::$hashType = $hash;
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
	public static function rijndael($text, $key, $operation) {
		if (empty($key)) {
			throw new InvalidArgumentException('You cannot use an empty key for Security::rijndael()');
		}
		if (empty($operation) || !in_array($operation, array('encrypt', 'decrypt'))) {
			throw new InvalidArgumentException('You must specify the operation for Security::rijndael(), either encrypt or decrypt');
		}
		if (strlen($key) < 32) {
			throw new InvalidArgumentException('You must use a key larger than 32 bytes for Security::rijndael()');
		}
		$algorithm = MCRYPT_RIJNDAEL_256;
		$mode = MCRYPT_MODE_CBC;
		$ivSize = mcrypt_get_iv_size($algorithm, $mode);

		$cryptKey = substr($key, 0, 32);

		if ($operation === 'encrypt') {
			$iv = mcrypt_create_iv($ivSize, MCRYPT_DEV_URANDOM);
			return $iv . '$$' . mcrypt_encrypt($algorithm, $cryptKey, $text, $mode, $iv);
		}
		$iv = substr($text, 0, $ivSize);
		$text = substr($text, $ivSize + 2);
		return rtrim(mcrypt_decrypt($algorithm, $cryptKey, $text, $mode, $iv), "\0");
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
 * @param string $hmacSalt The salt to use for the HMAC process. Leave null to use Security.salt.
 * @return string Encrypted data.
 * @throws \InvalidArgumentException On invalid data or key.
 */
	public static function encrypt($plain, $key, $hmacSalt = null) {
		self::_checkKey($key, 'encrypt()');

		if ($hmacSalt === null) {
			$hmacSalt = static::$_salt;
		}

		// Generate the encryption and hmac key.
		$key = substr(hash('sha256', $key . $hmacSalt), 0, 32);

		$algorithm = MCRYPT_RIJNDAEL_128;
		$mode = MCRYPT_MODE_CBC;

		$ivSize = mcrypt_get_iv_size($algorithm, $mode);
		$iv = mcrypt_create_iv($ivSize, MCRYPT_DEV_URANDOM);
		$ciphertext = $iv . mcrypt_encrypt($algorithm, $key, $plain, $mode, $iv);
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
	protected static function _checkKey($key, $method) {
		if (strlen($key) < 32) {
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
 * @param string $hmacSalt The salt to use for the HMAC process. Leave null to use Security.salt.
 * @return string Decrypted data. Any trailing null bytes will be removed.
 * @throws InvalidArgumentException On invalid data or key.
 */
	public static function decrypt($cipher, $key, $hmacSalt = null) {
		self::_checkKey($key, 'decrypt()');
		if (empty($cipher)) {
			throw new InvalidArgumentException('The data to decrypt cannot be empty.');
		}
		if ($hmacSalt === null) {
			$hmacSalt = static::$_salt;
		}

		// Generate the encryption and hmac key.
		$key = substr(hash('sha256', $key . $hmacSalt), 0, 32);

		// Split out hmac for comparison
		$macSize = 64;
		$hmac = substr($cipher, 0, $macSize);
		$cipher = substr($cipher, $macSize);

		$compareHmac = hash_hmac('sha256', $cipher, $key);
		if ($hmac !== $compareHmac) {
			return false;
		}

		$algorithm = MCRYPT_RIJNDAEL_128;
		$mode = MCRYPT_MODE_CBC;
		$ivSize = mcrypt_get_iv_size($algorithm, $mode);

		$iv = substr($cipher, 0, $ivSize);
		$cipher = substr($cipher, $ivSize);
		$plain = mcrypt_decrypt($algorithm, $key, $cipher, $mode, $iv);
		return rtrim($plain, "\0");
	}

/**
 * Gets or sets the HMAC salt to be used for encryption/decryption
 * routines.
 *
 * @param string $salt The salt to use for encryption routines
 * @return string The currently configured salt
 */
	public static function salt($salt = null) {
		if ($salt === null) {
			return static::$_salt;
		}
		return static::$_salt = (string)$salt;
	}

}
