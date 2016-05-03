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
 * @package       Cake.Utility
 * @since         CakePHP(tm) v .0.10.0.1233
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeText', 'Utility');

/**
 * Security Library contains utility methods related to security
 *
 * @package       Cake.Utility
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
 * Get allowed minutes of inactivity based on security level.
 *
 * @deprecated 3.0.0 Exists for backwards compatibility only, not used by the core
 * @return int Allowed inactivity in minutes
 */
	public static function inactiveMins() {
		switch (Configure::read('Security.level')) {
			case 'high':
				return 10;
			case 'medium':
				return 100;
			case 'low':
			default:
				return 300;
		}
	}

/**
 * Generate authorization hash.
 *
 * @return string Hash
 * @deprecated 2.8.1 This method was removed in 3.0.0
 */
	public static function generateAuthKey() {
		return Security::hash(CakeText::uuid());
	}

/**
 * Validate authorization hash.
 *
 * @param string $authKey Authorization hash
 * @return bool Success
 * @deprecated 2.8.1 This method was removed in 3.0.0
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
 * ```
 * $hash = Security::hash($password, 'blowfish');
 * ```
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
 */
	public static function setCost($cost) {
		if ($cost < 4 || $cost > 31) {
			trigger_error(__d(
				'cake_dev',
				'Invalid value, cost must be between %s and %s',
				array(4, 31)
			), E_USER_WARNING);
			return null;
		}
		static::$hashCost = $cost;
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
	public static function randomBytes($length) {
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
		while (strlen($bytes) < $length) {
			$bytes .= static::hash(CakeText::uuid() . uniqid(mt_rand(), true), 'sha512', true);
		}
		return substr($bytes, 0, $length);
	}

/**
 * Runs $text through a XOR cipher.
 *
 * *Note* This is not a cryptographically strong method and should not be used
 * for sensitive data. Additionally this method does *not* work in environments
 * where suhosin is enabled.
 *
 * Instead you should use Security::rijndael() when you need strong
 * encryption.
 *
 * @param string $text Encrypted string to decrypt, normal string to encrypt
 * @param string $key Key to use
 * @return string Encrypted/Decrypted string
 * @deprecated 3.0.0 Will be removed in 3.0.
 */
	public static function cipher($text, $key) {
		if (empty($key)) {
			trigger_error(__d('cake_dev', 'You cannot use an empty key for %s', 'Security::cipher()'), E_USER_WARNING);
			return '';
		}

		srand((int)Configure::read('Security.cipherSeed'));
		$out = '';
		$keyLength = strlen($key);
		for ($i = 0, $textLength = strlen($text); $i < $textLength; $i++) {
			$j = ord(substr($key, $i % $keyLength, 1));
			while ($j--) {
				rand(0, 255);
			}
			$mask = rand(0, 255);
			$out .= chr(ord(substr($text, $i, 1)) ^ $mask);
		}
		srand();
		return $out;
	}

/**
 * Encrypts/Decrypts a text using the given key using rijndael method.
 *
 * Prior to 2.3.1, a fixed initialization vector was used. This was not
 * secure. This method now uses a random iv, and will silently upgrade values when
 * they are re-encrypted.
 *
 * @param string $text Encrypted string to decrypt, normal string to encrypt
 * @param string $key Key to use as the encryption key for encrypted data.
 * @param string $operation Operation to perform, encrypt or decrypt
 * @return string Encrypted/Decrypted string
 */
	public static function rijndael($text, $key, $operation) {
		if (empty($key)) {
			trigger_error(__d('cake_dev', 'You cannot use an empty key for %s', 'Security::rijndael()'), E_USER_WARNING);
			return '';
		}
		if (empty($operation) || !in_array($operation, array('encrypt', 'decrypt'))) {
			trigger_error(__d('cake_dev', 'You must specify the operation for Security::rijndael(), either encrypt or decrypt'), E_USER_WARNING);
			return '';
		}
		if (strlen($key) < 32) {
			trigger_error(__d('cake_dev', 'You must use a key larger than 32 bytes for Security::rijndael()'), E_USER_WARNING);
			return '';
		}
		$algorithm = MCRYPT_RIJNDAEL_256;
		$mode = MCRYPT_MODE_CBC;
		$ivSize = mcrypt_get_iv_size($algorithm, $mode);

		$cryptKey = substr($key, 0, 32);

		if ($operation === 'encrypt') {
			$iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
			return $iv . '$$' . mcrypt_encrypt($algorithm, $cryptKey, $text, $mode, $iv);
		}
		// Backwards compatible decrypt with fixed iv
		if (substr($text, $ivSize, 2) !== '$$') {
			$iv = substr($key, strlen($key) - 32, 32);
			return rtrim(mcrypt_decrypt($algorithm, $cryptKey, $text, $mode, $iv), "\0");
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
 */
	protected static function _crypt($password, $salt = false) {
		if ($salt === false || $salt === null || $salt === '') {
			$salt = static::_salt(22);
			$salt = vsprintf('$2a$%02d$%s', array(static::$hashCost, $salt));
		}

		$invalidCipher = (
			strpos($salt, '$2y$') !== 0 &&
			strpos($salt, '$2x$') !== 0 &&
			strpos($salt, '$2a$') !== 0
		);
		if ($salt === true || $invalidCipher || strlen($salt) < 29) {
			trigger_error(__d(
				'cake_dev',
				'Invalid salt: %s for %s Please visit http://www.php.net/crypt and read the appropriate section for building %s salts.',
				array($salt, 'blowfish', 'blowfish')
			), E_USER_WARNING);
			return '';
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
 * @throws CakeException On invalid data or key.
 */
	public static function encrypt($plain, $key, $hmacSalt = null) {
		static::_checkKey($key, 'encrypt()');

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
 * @param string $key Key to check.
 * @param string $method The method the key is being checked for.
 * @return void
 * @throws CakeException When key length is not 256 bit/32 bytes
 */
	protected static function _checkKey($key, $method) {
		if (strlen($key) < 32) {
			throw new CakeException(__d('cake_dev', 'Invalid key for %s, key must be at least 256 bits (32 bytes) long.', $method));
		}
	}

/**
 * Decrypt a value using AES-256.
 *
 * @param string $cipher The ciphertext to decrypt.
 * @param string $key The 256 bit/32 byte key to use as a cipher key.
 * @param string $hmacSalt The salt to use for the HMAC process. Leave null to use Security.salt.
 * @return string Decrypted data. Any trailing null bytes will be removed.
 * @throws CakeException On invalid data or key.
 */
	public static function decrypt($cipher, $key, $hmacSalt = null) {
		static::_checkKey($key, 'decrypt()');
		if (empty($cipher)) {
			throw new CakeException(__d('cake_dev', 'The data to decrypt cannot be empty.'));
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
