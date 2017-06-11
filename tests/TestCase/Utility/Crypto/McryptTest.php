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
namespace Cake\Test\TestCase\Utility\Crypto;

use Cake\TestSuite\TestCase;
use Cake\Utility\Crypto\Mcrypt;

/**
 * Mcrypt engine tests.
 */
class McryptTest extends TestCase
{

    /**
     * Setup function.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->skipIf(!function_exists('mcrypt_encrypt') || version_compare(PHP_VERSION, '7.1', '>='), 'No mcrypt skipping tests');
        $this->crypt = new Mcrypt();
    }

    /**
     * testRijndael method
     *
     * @return void
     */
    public function testRijndael()
    {
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

        $result = $this->crypt->rijndael($txt, $key, 'encrypt');
        $this->assertEquals($txt, $this->crypt->rijndael($result, $key, 'decrypt'));

        $result = $this->crypt->rijndael($key, $txt, 'encrypt');
        $this->assertEquals($key, $this->crypt->rijndael($result, $txt, 'decrypt'));

        $result = $this->crypt->rijndael('', $key, 'encrypt');
        $this->assertEquals('', $this->crypt->rijndael($result, $key, 'decrypt'));

        $key = 'this is my key of over 32 chars, yes it is';
        $result = $this->crypt->rijndael($txt, $key, 'encrypt');
        $this->assertEquals($txt, $this->crypt->rijndael($result, $key, 'decrypt'));
    }

    /**
     * Test encrypt/decrypt.
     *
     * @return void
     */
    public function testEncryptDecrypt()
    {
        $txt = 'The quick brown fox';
        $key = 'This key is enough bytes';
        $result = $this->crypt->encrypt($txt, $key);
        $this->assertNotEquals($txt, $result, 'Should be encrypted.');
        $this->assertNotEquals($result, $this->crypt->encrypt($txt, $key), 'Each result is unique.');
        $this->assertEquals($txt, $this->crypt->decrypt($result, $key));
    }

    /**
     * Test that changing the key causes decryption to fail.
     *
     * @return void
     */
    public function testDecryptKeyFailure()
    {
        $txt = 'The quick brown fox';

        $key = substr(hash('sha256', 'This key is enough bytes'), 0, 32);
        $result = $this->crypt->encrypt($txt, $key);

        $key = substr(hash('sha256', 'Not the same key.'), 0, 32);
        $this->assertFalse($this->crypt->decrypt($txt, $key), 'Modified key will fail.');
    }

    /**
     * Ensure that data encrypted with 2.x encrypt() function can be decrypted with mcrypt engine.
     *
     * The $cipher variable is base64 encoded data from 2.x encrypt()
     *
     * @return
     */
    public function testDecryptOldData()
    {
        $key = 'My password is nice and long really it is';
        $key = substr(hash('sha256', $key), 0, 32);

        $cipher = 'ZmFkMjdmY2U2NjgzOTkwMGZmMWJiMzY0ZDA5ZDUwZmNjYTdjNWVkZThkMzhmNzdiY' .
            'Tg3ZDFjMzNjNmViMDljMnk9k0LmYpwSZH5eq7GmDozMwHxzh37YaXFQ2TK5gXb5OfTKXv83K+NjAS9lIo/Zvw==';
        $data = base64_decode($cipher);
        $cipher = substr($data, 64);

        $result = $this->crypt->decrypt($cipher, $key);
        $this->assertEquals('This is a secret message', $result);
    }
}
