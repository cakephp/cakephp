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
namespace Cake\Utility\Crypto;

/**
 * OpenSSL implementation of crypto features for Cake\Utility\Security
 *
 * OpenSSL should be favored over mcrypt as it is actively maintained and
 * more widely available.
 */
class OpenSsl {

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
		throw new \LogicException('rijndael is not compatible with OpenSSL. Use mcrypt instead.');
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
	public static function encrypt($plain, $key, $hmacSalt = null) {
		$method = 'AES-128-CBC';
		$ivSize = openssl_cipher_iv_length($method);
		$iv = openssl_random_pseudo_bytes($ivSize);
		$ciphertext = $iv . openssl_encrypt($plain, $method, $key, 0, $iv);
		$hmac = hash_hmac('sha256', $ciphertext, $key);
		return $hmac . $ciphertext;
	}

/**
 * Decrypt a value using AES-256.
 *
 * @param string $cipher The ciphertext to decrypt.
 * @param string $key The 256 bit/32 byte key to use as a cipher key.
 * @return string Decrypted data. Any trailing null bytes will be removed.
 * @throws InvalidArgumentException On invalid data or key.
 */
	public static function decrypt($cipher, $key) {
		// Split out hmac for comparison
		$macSize = 64;
		$hmac = substr($cipher, 0, $macSize);
		$cipher = substr($cipher, $macSize);

		$compareHmac = hash_hmac('sha256', $cipher, $key);
		// TODO time constant comparison?
		if ($hmac !== $compareHmac) {
			return false;
		}
		$method = 'AES-128-CBC';
		$ivSize = openssl_cipher_iv_length($method);

		$iv = substr($cipher, 0, $ivSize);
		$cipher = substr($cipher, $ivSize);
		$plain = openssl_decrypt($cipher, $method, $key, 0, $iv);
		return rtrim($plain, "\0");
	}
}

