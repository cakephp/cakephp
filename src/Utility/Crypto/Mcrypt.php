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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility\Crypto;

/**
 * Mcrypt implementation of crypto features for Cake\Utility\Security
 *
 * This class is not intended to be used directly and should only
 * be used in the context of Cake\Utility\Security.
 *
 * @deprecated 3.3.0 It is recommended to use {@see Cake\Utility\Crypto\OpenSsl} instead.
 * @internal
 */
class Mcrypt
{

    /**
     * Encrypts/Decrypts a text using the given key using rijndael method.
     *
     * @param string $text Encrypted string to decrypt, normal string to encrypt
     * @param string $key Key to use as the encryption key for encrypted data.
     * @param string $operation Operation to perform, encrypt or decrypt
     * @throws \LogicException When there are errors.
     * @return string Encrytped binary string data, or decrypted data depending on operation.
     * @deprecated 3.3.0 This method will be removed in 4.0.0.
     */
    public static function rijndael($text, $key, $operation)
    {
        $algorithm = MCRYPT_RIJNDAEL_256;
        $mode = MCRYPT_MODE_CBC;
        $ivSize = mcrypt_get_iv_size($algorithm, $mode);

        $cryptKey = mb_substr($key, 0, 32, '8bit');

        if ($operation === 'encrypt') {
            $iv = mcrypt_create_iv($ivSize, MCRYPT_DEV_URANDOM);

            return $iv . '$$' . mcrypt_encrypt($algorithm, $cryptKey, $text, $mode, $iv);
        }
        $iv = mb_substr($text, 0, $ivSize, '8bit');
        $text = mb_substr($text, $ivSize + 2, null, '8bit');

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
     * @return string Encrypted data.
     * @throws \InvalidArgumentException On invalid data or key.
     * @deprecated 3.3.0 Use Cake\Utility\Crypto\OpenSsl::encrypt() instead.
     */
    public static function encrypt($plain, $key)
    {
        deprecationWarning(
            'Mcrypt::encrypt() is deprecated. ' .
            'Use Cake\Utility\Crypto\OpenSsl::encrypt() instead.'
        );
        $algorithm = MCRYPT_RIJNDAEL_128;
        $mode = MCRYPT_MODE_CBC;

        $ivSize = mcrypt_get_iv_size($algorithm, $mode);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_DEV_URANDOM);

        // Pad out plain to make it AES compatible.
        $pad = ($ivSize - (mb_strlen($plain, '8bit') % $ivSize));
        $plain .= str_repeat(chr($pad), $pad);

        return $iv . mcrypt_encrypt($algorithm, $key, $plain, $mode, $iv);
    }

    /**
     * Decrypt a value using AES-256.
     *
     * @param string $cipher The ciphertext to decrypt.
     * @param string $key The 256 bit/32 byte key to use as a cipher key.
     * @return string Decrypted data. Any trailing null bytes will be removed.
     * @throws \InvalidArgumentException On invalid data or key.
     * @deprecated 3.3.0 Use Cake\Utility\Crypto\OpenSsl::decrypt() instead.
     */
    public static function decrypt($cipher, $key)
    {
        deprecationWarning(
            'Mcrypt::decrypt() is deprecated. ' .
            'Use Cake\Utility\Crypto\OpenSsl::decrypt() instead.'
        );
        $algorithm = MCRYPT_RIJNDAEL_128;
        $mode = MCRYPT_MODE_CBC;
        $ivSize = mcrypt_get_iv_size($algorithm, $mode);

        $iv = mb_substr($cipher, 0, $ivSize, '8bit');
        $cipher = mb_substr($cipher, $ivSize, null, '8bit');
        $plain = mcrypt_decrypt($algorithm, $key, $cipher, $mode, $iv);

        // Remove PKCS#7 padding or Null bytes
        // Newer values will be PKCS#7 padded, while old
        // mcrypt values will be null byte padded.
        $padChar = mb_substr($plain, -1, null, '8bit');
        if ($padChar === "\0") {
            return trim($plain, "\0");
        }
        $padLen = ord($padChar);
        $result = mb_substr($plain, 0, -$padLen, '8bit');

        return $result === '' ? false : $result;
    }
}
