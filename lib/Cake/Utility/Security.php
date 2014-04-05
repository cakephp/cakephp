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

App::uses('String', 'Utility');

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
 * @deprecated Exists for backwards compatibility only, not used by the core
 * @return integer Allowed inactivity in minutes
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
 */
	public static function generateAuthKey() {
		return Security::hash(String::uuid());
	}

/**
 * Validate authorization hash.
 *
 * @param string $authKey Authorization hash
 * @return boolean Success
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
			$type = self::$hashType;
		}
		$type = strtolower($type);

		if ($type === 'blowfish') {
			return self::_crypt($string, $salt);
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
		self::$hashType = $hash;
	}

/**
 * Sets the cost for they blowfish hash method.
 *
 * @param integer $cost Valid values are 4-31
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
		self::$hashCost = $cost;
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
 * @deprecated Will be removed in 3.0.
 */
	public static function cipher($text, $key) {
		if (empty($key)) {
			trigger_error(__d('cake_dev', 'You cannot use an empty key for %s', 'Security::cipher()'), E_USER_WARNING);
			return '';
		}

		srand(Configure::read('Security.cipherSeed'));
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
 * @param integer $length The length of the returned salt
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
		if ($salt === false) {
			$salt = self::_salt(22);
			$salt = vsprintf('$2a$%02d$%s', array(self::$hashCost, $salt));
		}

		if ($salt === true || strpos($salt, '$2a$') !== 0 || strlen($salt) < 29) {
			trigger_error(__d(
				'cake_dev',
				'Invalid salt: %s for %s Please visit http://www.php.net/crypt and read the appropriate section for building %s salts.',
				array($salt, 'blowfish', 'blowfish')
			), E_USER_WARNING);
			return '';
		}
		return crypt($password, $salt);
	}

}
