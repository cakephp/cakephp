<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\Utility\Crypto\OpenSsl;
use Cake\Utility\Security;
use RuntimeException;

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
     * testInvalidHashTypeException
     *
     * @return void
     */
    public function testInvalidHashTypeException()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/The hash type `doesnotexist` was not found. Available algorithms are: \w+/');

        Security::hash('test', 'doesnotexist', false);
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
        $this->assertSame($txt, Security::decrypt($result, $key));
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
        $this->assertNull(Security::decrypt($txt, $key), 'Modified key will fail.');
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
        $this->assertNull(Security::decrypt($result, $key, $salt), 'Modified hmac causes failure.');
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
        $this->assertNull(Security::decrypt($result, $key, $salt), 'Modified salt causes failure.');
    }

    /**
     * Test that short keys cause errors
     *
     * @return void
     */
    public function testEncryptInvalidKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key for encrypt(), key must be at least 256 bits (32 bytes) long.');
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

        $result = Security::encrypt('0', $key);
        $this->assertSame('0', Security::decrypt($result, $key));
    }

    /**
     * Test that short keys cause errors
     *
     * @return void
     */
    public function testDecryptInvalidKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key for decrypt(), key must be at least 256 bits (32 bytes) long.');
        $txt = 'The quick brown fox jumped over the lazy dog.';
        $key = 'this is too short';
        Security::decrypt($txt, $key);
    }

    /**
     * Test that empty data cause errors
     *
     * @return void
     */
    public function testDecryptInvalidData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The data to decrypt cannot be empty.');
        $txt = '';
        $key = 'This is a key that is long enough to be ok.';
        Security::decrypt($txt, $key);
    }

    /**
     * Test engine
     *
     * @return void
     */
    public function testEngineEquivalence()
    {
        $restore = Security::engine();
        $newEngine = new OpenSsl();

        Security::engine($newEngine);

        $this->assertSame($newEngine, Security::engine());
        $this->assertNotSame($restore, Security::engine());
    }

    /**
     * Tests that the salt can be set and retrieved
     *
     * @return void
     */
    public function testSalt()
    {
        Security::setSalt('foobarbaz');
        $this->assertSame('foobarbaz', Security::getSalt());
    }

    /**
     * Tests that the salt can be set and retrieved
     *
     * @return void
     */
    public function testGetSetSalt()
    {
        Security::setSalt('foobarbaz');
        $this->assertSame('foobarbaz', Security::getSalt());
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

        $this->assertMatchesRegularExpression('/[^0-9a-f]/', $value, 'should return a binary string');
    }

    /**
     * Test the randomString method.
     *
     * @return void
     */
    public function testRandomString()
    {
        $value = Security::randomString(7);
        $this->assertSame(7, strlen($value));

        $value = Security::randomString();
        $this->assertSame(64, strlen($value));

        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $value, 'should return a ASCII string');
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

        $this->assertMatchesRegularExpression('/[^0-9a-f]/', $value, 'should return a binary string');
    }

    /**
     * test constantEquals
     *
     * @return void
     */
    public function testConstantEquals()
    {
        $this->assertFalse(Security::constantEquals('abcde', null));
        $this->assertFalse(Security::constantEquals('abcde', false));
        $this->assertFalse(Security::constantEquals('abcde', true));
        $this->assertFalse(Security::constantEquals('abcde', 1));

        $this->assertFalse(Security::constantEquals(null, 'abcde'));
        $this->assertFalse(Security::constantEquals(false, 'abcde'));
        $this->assertFalse(Security::constantEquals(1, 'abcde'));
        $this->assertFalse(Security::constantEquals(true, 'abcde'));

        $this->assertTrue(Security::constantEquals('abcde', 'abcde'));
        $this->assertFalse(Security::constantEquals('abcdef', 'abcde'));
        $this->assertFalse(Security::constantEquals('abcde', 'abcdef'));
        $this->assertFalse(Security::constantEquals('a', 'abcdef'));

        $snowman = "\xe2\x98\x83";
        $this->assertTrue(Security::constantEquals($snowman, $snowman));
        $this->assertFalse(Security::constantEquals(str_repeat($snowman, 3), $snowman));
    }
}
