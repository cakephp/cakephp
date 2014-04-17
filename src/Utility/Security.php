<?php
/**
 * Core Security
 *
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

use Cake\Core\Configure;
use Cake\Error\Exception;

/**
 * Security Library contains utility methods related to security
 *
 */
class Security {

/**
 * Default hash method
 *
 * @var string
 */
	public static $hashType = null;

/**
 * Default cost
 *
 * @var string
 */
	public static $hashCost = '10';

/**
 * Generate authorization hash.
 *
 * @return string Hash
 */
	public static function generateAuthKey() {
		return Security::hash(String::uuid());
	}

/**
 * Validate authorization hash.
 *
 * @param string $authKey Authorization hash
 * @return bool Success
 */
	public static function validateAuthKey($authKey) {
		return true;
	}

/**
 * Create a hash from string using given method or fallback on next available method.
 *
 * #### Using Blowfish
 *
 * - Creating Hashes: *Do not supply a salt*. Cake handles salt creation for
 * you ensuring that each hashed password will have a *unique* salt.
 * - Comparing Hashes: Simply pass the originally hashed password as the salt.
 * The salt is prepended to the hash and php handles the parsing automagically.
 * For convenience the `BlowfishPasswordHasher` class is available for use with
 * the AuthComponent.
 * - Do NOT use a constant salt for blowfish!
 *
 * Creating a blowfish/bcrypt hash:
 *
 * {{{
 * 	$hash = Security::hash($password, 'blowfish');
 * }}}
 *
 * @param string $string String to hash
 * @param string $type Method to use (sha1/sha256/md5/blowfish)
 * @param mixed $salt If true, automatically prepends the application's salt
 *     value to $string (Security.salt). If you are using blowfish the salt
 *     must be false or a previously generated salt.
 * @return string Hash
 * @link http://book.cakephp.org/2.0/en/core-utility-libraries/security.html#Security::hash
 */
	public static function hash($string, $type = null, $salt = false) {
		if (empty($type)) {
			$type = static::$hashType;
		}
		$type = strtolower($type);

		if ($type === 'blowfish') {
			return static::_crypt($string, $salt);
		}
		if ($salt) {
			if (!is_string($salt)) {
				$salt = Configure::read('Security.salt');
			}
			$string = $salt . $string;
		}

		if (!$type || $type === 'sha1') {
			if (function_exists('sha1')) {
				return sha1($string);
			}
			$type = 'sha256';
		}

		if ($type === 'sha256' && function_exists('mhash')) {
			return bin2hex(mhash(MHASH_SHA256, $string));
		}

		if (function_exists('hash')) {
			return hash($type, $string);
		}
		return md5($string);
	}

/**
 * Sets the default hash method for the Security object. This affects all objects using
 * Security::hash().
 *
 * @param string $hash Method to use (sha1/sha256/md5/blowfish)
 * @return void
 * @see Security::hash()
 */
	public static function setHash($hash) {
		static::$hashType = $hash;
	}

/**
 * Sets the cost for they blowfish hash method.
 *
 * @param int $cost Valid values are 4-31
 * @return void
 * @throws \Cake\Error\Exception When cost is invalid.
 */
	public static function setCost($cost) {
		if ($cost < 4 || $cost > 31) {
			throw new Exception(vsprintf(
				'Invalid value, cost must be between %s and %s',
				array(4, 31)
			));
		}
		static::$hashCost = $cost;
	}

/**
 * Encrypts/Decrypts a text using the given key using rijndael method.
 *
 * @param string $text Encrypted string to decrypt, normal string to encrypt
 * @param string $key Key to use as the encryption key for encrypted data.
 * @param string $operation Operation to perform, encrypt or decrypt
 * @throws \Cake\Error\Exception When there are errors.
 * @return string Encrypted/Decrypted string
 */
	public static function rijndael($text, $key, $operation) {
		if (empty($key)) {
			throw new Exception('You cannot use an empty key for Security::rijndael()');
		}
		if (empty($operation) || !in_array($operation, array('encrypt', 'decrypt'))) {
			throw new Exception('You must specify the operation for Security::rijndael(), either encrypt or decrypt');
		}
		if (strlen($key) < 32) {
			throw new Exception('You must use a key larger than 32 bytes for Security::rijndael()');
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
 * Generates a pseudo random salt suitable for use with php's crypt() function.
 * The salt length should not exceed 27. The salt will be composed of
 * [./0-9A-Za-z]{$length}.
 *
 * @param int $length The length of the returned salt
 * @return string The generated salt
 */
	protected static function _salt($length = 22) {
		$salt = str_replace(
			array('+', '='),
			'.',
			base64_encode(sha1(uniqid(Configure::read('Security.salt'), true), true))
		);
		return substr($salt, 0, $length);
	}

/**
 * One way encryption using php's crypt() function. To use blowfish hashing see ``Security::hash()``
 *
 * @param string $password The string to be encrypted.
 * @param mixed $salt false to generate a new salt or an existing salt.
 * @return string The hashed string or an empty string on error.
 * @throws \Cake\Error\Exception on invalid salt values.
 */
	protected static function _crypt($password, $salt = false) {
		if ($salt === false) {
			$salt = static::_salt(22);
			$salt = vsprintf('$2y$%02d$%s', array(static::$hashCost, $salt));
		}

		if ($salt === true || strpos($salt, '$2y$') !== 0 || strlen($salt) < 29) {
			throw new Exception(sprintf(
				'Invalid salt: %s for blowfish Please visit http://www.php.net/crypt and read the appropriate section for building blowfish salts.',
				$salt
			));
		}
		return crypt($password, $salt);
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
 * @throws \Cake\Error\Exception On invalid data or key.
 */
	public static function encrypt($plain, $key, $hmacSalt = null) {
		self::_checkKey($key, 'encrypt()');

		if ($hmacSalt === null) {
			$hmacSalt = Configure::read('Security.salt');
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
 * @param string $key
 * @param string $method The method the key is being checked for.
 * @return void
 * @throws \Cake\Error\Exception When key length is not 256 bit/32 bytes
 */
	protected static function _checkKey($key, $method) {
		if (strlen($key) < 32) {
			throw new Exception(sprintf('Invalid key for %s, key must be at least 256 bits (32 bytes) long.', $method));
		}
	}

/**
 * Decrypt a value using AES-256.
 *
 * @param string $cipher The ciphertext to decrypt.
 * @param string $key The 256 bit/32 byte key to use as a cipher key.
 * @param string $hmacSalt The salt to use for the HMAC process. Leave null to use Security.salt.
 * @return string Decrypted data. Any trailing null bytes will be removed.
 * @throws \Cake\Error\Exception On invalid data or key.
 */
	public static function decrypt($cipher, $key, $hmacSalt = null) {
		self::_checkKey($key, 'decrypt()');
		if (empty($cipher)) {
			throw new Exception('The data to decrypt cannot be empty.');
		}
		if ($hmacSalt === null) {
			$hmacSalt = Configure::read('Security.salt');
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

}
