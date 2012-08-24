<?php
/**
 * Core Security
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         CakePHP(tm) v .0.10.0.1233
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
 * @return integer Allowed inactivity in minutes
 */
	public static function inactiveMins() {
		switch (Configure::read('Security.level')) {
			case 'high':
				return 10;
			break;
			case 'medium':
				return 100;
			break;
			case 'low':
			default:
				return 300;
				break;
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
 * @todo Complete implementation
 */
	public static function validateAuthKey($authKey) {
		return true;
	}

/**
 * Create a hash from string using given method.
 * Fallback on next available method.
 * If you are using blowfish, for comparisons simply pass the originally hashed
 * string as the salt (the salt is prepended to the hash and php handles the
 * parsing automagically. Do NOT use a constant salt for blowfish.
 *
 * @param string $string String to hash
 * @param string $type Method to use (sha1/sha256/md5)
 * @param mixed $salt If true, automatically appends the application's salt
 *     value to $string (Security.salt). If you are using blowfish the salt
 *     must be false or a previously generated salt.
 * @return string Hash
 */
	public static function hash($string, $type = null, $salt = false) {
		if (empty($type)) {
			$type = self::$hashType;
		}
		$type = strtolower($type);

		if ($type === 'blowfish') {
			return self::_crypt($string, $type, $salt);
		}
		if ($salt) {
			if (is_string($salt)) {
				$string = $salt . $string;
			} else {
				$string = Configure::read('Security.salt') . $string;
			}
		}

		if ($type == 'sha1' || $type == null) {
			if (function_exists('sha1')) {
				$return = sha1($string);
				return $return;
			}
			$type = 'sha256';
		}

		if ($type == 'sha256' && function_exists('mhash')) {
			return bin2hex(mhash(MHASH_SHA256, $string));
		}

		if (function_exists('hash')) {
			return hash($type, $string);
		}
		return md5($string);
	}

/**
 * Sets the default hash method for the Security object.  This affects all objects using
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
		self::$hashCost = $cost;
	}

/**
 * Encrypts/Decrypts a text using the given key.
 *
 * @param string $text Encrypted string to decrypt, normal string to encrypt
 * @param string $key Key to use
 * @return string Encrypted/Decrypted string
 */
	public static function cipher($text, $key) {
		if (empty($key)) {
			trigger_error(__d('cake_dev', 'You cannot use an empty key for Security::cipher()'), E_USER_WARNING);
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
 * @param string $text Encrypted string to decrypt, normal string to encrypt
 * @param string $key Key to use
 * @param string $operation Operation to perform, encrypt or decrypt
 * @return string Encrypted/Descrypted string
 */
	public static function rijndael($text, $key, $operation) {
		if (empty($key)) {
			trigger_error(__d('cake_dev', 'You cannot use an empty key for Security::rijndael()'), E_USER_WARNING);
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
		$algorithm = 'rijndael-256';
		$mode = 'cbc';
		$cryptKey = substr($key, 0, 32);
		$iv = substr($key, strlen($key) - 32, 32);
		$out = '';
		if ($operation === 'encrypt') {
			$out .= mcrypt_encrypt($algorithm, $cryptKey, $text, $mode, $iv);
		} elseif ($operation === 'decrypt') {
			$out .= rtrim(mcrypt_decrypt($algorithm, $cryptKey, $text, $mode, $iv), "\0");
		}
		return $out;
	}

/**
 * Generates a pseudo random salt suitable for use with php's crypt() function.
 * The salt length should not exceed 27. The salt will be composed of
 * [./0-9A-Za-z]{$length}.
 *
 * @param integer $length The length of the returned salt
 * @return string The generated salt
 */
	public static function salt($length = 22) {
		$salt = str_replace(
			array('+', '='),
			'.',
			base64_encode(sha1(uniqid(Configure::read('Security.salt'), true), true))
		);
		return substr($salt, 0, $length);
	}

/**
 * One way encryption using php's crypt() function.
 *
 * @param string $password The string to be encrypted.
 * @param string $type The encryption method to use (blowfish)
 * @param mixed $salt false to generate a new salt or an existing salt.
 */
	protected static function _crypt($password, $type = null, $salt = false) {
		$options = array(
			'saltFormat' => array(
				'blowfish' => '$2a$%02d$%s',
			),
			'saltLength' => array(
				'blowfish' => 22,
			),
			'costLimits' => array(
				'blowfish' => array(4, 31),
			)
		);
		extract($options);
		if ($type === null) {
			$hashType = self::$hashType;
		} else {
			$hashType = $type;
		}
		$cost = self::$hashCost;
		if ($salt === false) {
			if (isset($costLimits[$hashType]) && ($cost < $costLimits[$hashType][0] || $cost > $costLimits[$hashType][1])) {
				trigger_error(__d(
					'cake_dev',
					'When using %s you must specify a cost between %s and %s',
					array(
						$hashType,
						$costLimits[$hashType][0],
						$costLimits[$hashType][1]
					)
				), E_USER_WARNING);
				return '';
			}
			$salt = self::salt($saltLength[$hashType]);
			$vspArgs = array(
				$cost,
				$salt,
			);
			$salt = vsprintf($saltFormat[$hashType], $vspArgs);
		} elseif ($salt === true || strpos($salt, '$2a$') !== 0 || strlen($salt) < 29) {
			trigger_error(__d(
				'cake_dev',
				'Invalid salt: %s for %s Please visit http://www.php.net/crypt and read the appropriate section for building %s salts.',
				array($salt, $hashType, $hashType)
			), E_USER_WARNING);
			return '';
		}
		return crypt($password, $salt);
	}

}
