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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility\Crypto;

use Cake\TestSuite\TestCase;
use Cake\Utility\Crypto\OpenSsl;

/**
 * Openssl engine tests.
 */
class OpenSslTest extends TestCase
{
    /**
     * @var OpenSsl
     */
    private $crypt;

    /**
     * Setup function.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->skipIf(!function_exists('openssl_encrypt'), 'No openssl skipping tests');
        $this->crypt = new OpenSsl();
    }

    /**
     * testRijndael method
     *
     * @expectedException \LogicException
     * @return void
     */
    public function testRijndael()
    {
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

        $this->crypt->rijndael($txt, $key, 'encrypt');
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
        $key = 'This key is enough bytes';
        $result = $this->crypt->encrypt($txt, $key);

        $key = 'Not the same key.';
        $this->assertFalse($this->crypt->decrypt($txt, $key), 'Modified key will fail.');
    }
}
