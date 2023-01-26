<?php
declare(strict_types=1);

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

use Cake\Core\Exception\CakeException;

/**
 * OpenSSL implementation of crypto features for Cake\Utility\Security
 *
 * This class is not intended to be used directly and should only
 * be used in the context of {@link \Cake\Utility\Security}.
 *
 * @internal
 */
class OpenSsl
{
    /**
     * @var string
     */
    protected const METHOD_AES_256_CBC = 'aes-256-cbc';

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
     */
    public static function encrypt(string $plain, string $key): string
    {
        $method = static::METHOD_AES_256_CBC;
        $ivSize = openssl_cipher_iv_length($method);
        if ($ivSize === false) {
            throw new CakeException(sprintf('Cannot get the cipher iv length for `%s`', $method));
        }

        $iv = openssl_random_pseudo_bytes($ivSize);

        return $iv . openssl_encrypt($plain, $method, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Decrypt a value using AES-256.
     *
     * @param string $cipher The ciphertext to decrypt.
     * @param string $key The 256 bit/32 byte key to use as a cipher key.
     * @return string|null Decrypted data. Any trailing null bytes will be removed.
     * @throws \InvalidArgumentException On invalid data or key.
     */
    public static function decrypt(string $cipher, string $key): ?string
    {
        $method = static::METHOD_AES_256_CBC;
        $ivSize = openssl_cipher_iv_length($method);
        if ($ivSize === false) {
            throw new CakeException(sprintf('Cannot get the cipher iv length for `%s`', $method));
        }

        $iv = mb_substr($cipher, 0, $ivSize, '8bit');
        $cipher = mb_substr($cipher, $ivSize, null, '8bit');

        $value = openssl_decrypt($cipher, $method, $key, OPENSSL_RAW_DATA, $iv);
        if ($value === false) {
            return null;
        }

        return $value;
    }
}
