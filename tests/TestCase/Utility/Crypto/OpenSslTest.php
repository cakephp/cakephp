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
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIf(!function_exists('openssl_encrypt'), 'No openssl skipping tests');
        $this->crypt = new OpenSsl();
    }

    /**
     * Test encrypt/decrypt.
     */
    public function testEncryptDecrypt(): void
    {
        $txt = 'The quick brown fox';
        $key = 'This key is enough bytes';
        $result = $this->crypt->encrypt($txt, $key);
        $this->assertNotEquals($txt, $result, 'Should be encrypted.');
        $this->assertNotEquals($result, $this->crypt->encrypt($txt, $key), 'Each result is unique.');
        $this->assertSame($txt, $this->crypt->decrypt($result, $key));
    }

    /**
     * Test that changing the key causes decryption to fail.
     */
    public function testDecryptKeyFailure(): void
    {
        $txt = 'The quick brown fox';
        $key = 'This key is enough bytes';
        $result = $this->crypt->encrypt($txt, $key);

        $key = 'Not the same key.';
        $this->assertNull($this->crypt->decrypt($txt, $key), 'Modified key will fail.');
    }
}
