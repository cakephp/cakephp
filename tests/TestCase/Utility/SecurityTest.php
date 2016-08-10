<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Utility\Crypto\Mcrypt;
use Cake\Utility\Crypto\OpenSsl;
use Cake\Utility\Security;

/**
 * SecurityTest class
 */
class SecurityTest extends TestCase
{

    /**
     * testHash method
     *
     * @return void
     */
    public function testHash()
    {
        $_hashType = Security::$hashType;

        $key = 'someKey';
        $hash = 'someHash';

        $this->assertSame(40, strlen(Security::hash($key, null, false)));
        $this->assertSame(40, strlen(Security::hash($key, 'sha1', false)));
        $this->assertSame(40, strlen(Security::hash($key, null, true)));
        $this->assertSame(40, strlen(Security::hash($key, 'sha1', true)));

        $result = Security::hash($key, null, $hash);
        $this->assertSame($result, 'e38fcb877dccb6a94729a81523851c931a46efb1');

        $result = Security::hash($key, 'sha1', $hash);
        $this->assertSame($result, 'e38fcb877dccb6a94729a81523851c931a46efb1');

        $hashType = 'sha1';
        Security::setHash($hashType);
        $this->assertSame($hashType, Security::$hashType);
        $this->assertSame(40, strlen(Security::hash($key, null, true)));
        $this->assertSame(40, strlen(Security::hash($key, null, false)));

        $this->assertSame(32, strlen(Security::hash($key, 'md5', false)));
        $this->assertSame(32, strlen(Security::hash($key, 'md5', true)));

        $hashType = 'md5';
        Security::setHash($hashType);
        $this->assertSame($hashType, Security::$hashType);
        $this->assertSame(32, strlen(Security::hash($key, null, false)));
        $this->assertSame(32, strlen(Security::hash($key, null, true)));

        $this->assertSame(64, strlen(Security::hash($key, 'sha256', false)));
        $this->assertSame(64, strlen(Security::hash($key, 'sha256', true)));

        Security::setHash($_hashType);
    }

    /**
     * testRijndael method
     *
     * @return void
     */
    public function testRijndael()
    {
        $this->skipIf(!function_exists('mcrypt_encrypt'));
        $engine = Security::engine();

        Security::engine(new Mcrypt());
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

        $result = Security::rijndael($txt, $key, 'encrypt');
        $this->assertEquals($txt, Security::rijndael($result, $key, 'decrypt'));

        $result = Security::rijndael('', $key, 'encrypt');
        $this->assertEquals('', Security::rijndael($result, $key, 'decrypt'));

        $key = 'this is my key of over 32 chars, yes it is';
        $result = Security::rijndael($txt, $key, 'encrypt');
        $this->assertEquals($txt, Security::rijndael($result, $key, 'decrypt'));

        Security::engine($engine);
    }

    /**
     * testRijndaelInvalidOperation method
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testRijndaelInvalidOperation()
    {
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';
        Security::rijndael($txt, $key, 'foo');
    }

    /**
     * testRijndaelInvalidKey method
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testRijndaelInvalidKey()
    {
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'too small';
        Security::rijndael($txt, $key, 'encrypt');
    }

    /**
     * Test encrypt/decrypt.
     *
     * @return void
     */
    public function testEncryptDecrypt()
    {
        $txt = 'The quick brown fox';
        $key = 'This key is longer than 32 bytes long.';
        $result = Security::encrypt($txt, $key);
        $this->assertNotEquals($txt, $result, 'Should be encrypted.');
        $this->assertNotEquals($result, Security::encrypt($txt, $key), 'Each result is unique.');
        $this->assertEquals($txt, Security::decrypt($result, $key));
    }

    /**
     * Test that changing the key causes decryption to fail.
     *
     * @return void
     */
    public function testDecryptKeyFailure()
    {
        $txt = 'The quick brown fox';
        $key = 'This key is longer than 32 bytes long.';
        $result = Security::encrypt($txt, $key);

        $key = 'Not the same key. This one will fail';
        $this->assertFalse(Security::decrypt($txt, $key), 'Modified key will fail.');
    }

    /**
     * Test that decrypt fails when there is an hmac error.
     *
     * @return void
     */
    public function testDecryptHmacFailure()
    {
        $txt = 'The quick brown fox';
        $key = 'This key is quite long and works well.';
        $salt = 'this is a delicious salt!';
        $result = Security::encrypt($txt, $key, $salt);

        // Change one of the bytes in the hmac.
        $result[10] = 'x';
        $this->assertFalse(Security::decrypt($result, $key, $salt), 'Modified hmac causes failure.');
    }

    /**
     * Test that changing the hmac salt will cause failures.
     *
     * @return void
     */
    public function testDecryptHmacSaltFailure()
    {
        $txt = 'The quick brown fox';
        $key = 'This key is quite long and works well.';
        $salt = 'this is a delicious salt!';
        $result = Security::encrypt($txt, $key, $salt);

        $salt = 'humpty dumpty had a great fall.';
        $this->assertFalse(Security::decrypt($result, $key, $salt), 'Modified salt causes failure.');
    }

    /**
     * Test that short keys cause errors
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid key for encrypt(), key must be at least 256 bits (32 bytes) long.
     * @return void
     */
    public function testEncryptInvalidKey()
    {
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'this is too short';
        Security::encrypt($txt, $key);
    }

    /**
     * Test encrypting falsey data
     *
     * @return void
     */
    public function testEncryptDecryptFalseyData()
    {
        $key = 'This is a key that is long enough to be ok.';

        $result = Security::encrypt('', $key);
        $this->assertSame('', Security::decrypt($result, $key));

        $result = Security::encrypt(false, $key);
        $this->assertSame('', Security::decrypt($result, $key));

        $result = Security::encrypt(null, $key);
        $this->assertSame('', Security::decrypt($result, $key));

        $result = Security::encrypt(0, $key);
        $this->assertSame('0', Security::decrypt($result, $key));

        $result = Security::encrypt('0', $key);
        $this->assertSame('0', Security::decrypt($result, $key));
    }

    /**
     * Test that short keys cause errors
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid key for decrypt(), key must be at least 256 bits (32 bytes) long.
     * @return void
     */
    public function testDecryptInvalidKey()
    {
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'this is too short';
        Security::decrypt($txt, $key);
    }

    /**
     * Test that empty data cause errors
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The data to decrypt cannot be empty.
     * @return void
     */
    public function testDecryptInvalidData()
    {
        $txt = '';
        $key = 'This is a key that is long enough to be ok.';
        Security::decrypt($txt, $key);
    }

    /**
     * Test that values encrypted with open ssl can be decrypted with mcrypt and the reverse.
     *
     * @return void
     */
    public function testEngineEquivalence()
    {
        $this->skipIf(!defined('MCRYPT_RIJNDAEL_128'), 'This needs mcrypt extension to be loaded.');

        $restore = Security::engine();
        $txt = "Obi-wan you're our only hope";
        $key = 'This is my secret key phrase it is quite long.';
        $salt = 'A tasty salt that is delicious';

        Security::engine(new Mcrypt());
        $cipher = Security::encrypt($txt, $key, $salt);
        $this->assertEquals($txt, Security::decrypt($cipher, $key, $salt));

        Security::engine(new OpenSsl());
        $this->assertEquals($txt, Security::decrypt($cipher, $key, $salt));

        Security::engine(new OpenSsl());
        $cipher = Security::encrypt($txt, $key, $salt);
        $this->assertEquals($txt, Security::decrypt($cipher, $key, $salt));

        Security::engine(new Mcrypt());
        $this->assertEquals($txt, Security::decrypt($cipher, $key, $salt));
    }

    /**
     * Tests that the salt can be set and retrieved
     *
     * @return void
     */
    public function testSalt()
    {
        Security::salt('foobarbaz');
        $this->assertEquals('foobarbaz', Security::salt());
    }

    /**
     * Test the randomBytes method.
     *
     * @return void
     */
    public function testRandomBytes()
    {
        $value = Security::randomBytes(16);
        $this->assertSame(16, strlen($value));

        $value = Security::randomBytes(64);
        $this->assertSame(64, strlen($value));

        $this->assertRegExp('/[^0-9a-f]/', $value, 'should return a binary string');
    }

    /**
     * Test the insecureRandomBytes method
     *
     * @return void
     */
    public function testInsecureRandomBytes()
    {
        $value = Security::insecureRandomBytes(16);
        $this->assertSame(16, strlen($value));

        $value = Security::insecureRandomBytes(64);
        $this->assertSame(64, strlen($value));

        $this->assertRegExp('/[^0-9a-f]/', $value, 'should return a binary string');
    }
}
