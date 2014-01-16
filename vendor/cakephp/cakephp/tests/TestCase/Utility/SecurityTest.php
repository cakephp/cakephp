<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\Utility\Security;

/**
 * SecurityTest class
 *
 */
class SecurityTest extends TestCase {

/**
 * testGenerateAuthkey method
 *
 * @return void
 */
	public function testGenerateAuthkey() {
		$this->assertEquals(strlen(Security::generateAuthKey()), 40);
	}

/**
 * testValidateAuthKey method
 *
 * @return void
 */
	public function testValidateAuthKey() {
		$authKey = Security::generateAuthKey();
		$this->assertTrue(Security::validateAuthKey($authKey));
	}

/**
 * testHashInvalidSalt method
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testHashInvalidSalt() {
		Security::hash('someKey', 'blowfish', true);
	}

/**
 * testHashAnotherInvalidSalt
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testHashAnotherInvalidSalt() {
		Security::hash('someKey', 'blowfish', '$1$lksdjoijfaoijs');
	}

/**
 * testHashYetAnotherInvalidSalt
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testHashYetAnotherInvalidSalt() {
		Security::hash('someKey', 'blowfish', '$2a$10$123');
	}

/**
 * testHashInvalidCost method
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testHashInvalidCost() {
		Security::setCost(1000);
	}
/**
 * testHash method
 *
 * @return void
 */
	public function testHash() {
		$_hashType = Security::$hashType;

		$key = 'someKey';
		$hash = 'someHash';

		$this->assertSame(strlen(Security::hash($key, null, false)), 40);
		$this->assertSame(strlen(Security::hash($key, 'sha1', false)), 40);
		$this->assertSame(strlen(Security::hash($key, null, true)), 40);
		$this->assertSame(strlen(Security::hash($key, 'sha1', true)), 40);

		$result = Security::hash($key, null, $hash);
		$this->assertSame($result, 'e38fcb877dccb6a94729a81523851c931a46efb1');

		$result = Security::hash($key, 'sha1', $hash);
		$this->assertSame($result, 'e38fcb877dccb6a94729a81523851c931a46efb1');

		$hashType = 'sha1';
		Security::setHash($hashType);
		$this->assertSame(Security::$hashType, $hashType);
		$this->assertSame(strlen(Security::hash($key, null, true)), 40);
		$this->assertSame(strlen(Security::hash($key, null, false)), 40);

		$this->assertSame(strlen(Security::hash($key, 'md5', false)), 32);
		$this->assertSame(strlen(Security::hash($key, 'md5', true)), 32);

		$hashType = 'md5';
		Security::setHash($hashType);
		$this->assertSame(Security::$hashType, $hashType);
		$this->assertSame(strlen(Security::hash($key, null, false)), 32);
		$this->assertSame(strlen(Security::hash($key, null, true)), 32);

		if (!function_exists('hash') && !function_exists('mhash')) {
			$this->assertSame(strlen(Security::hash($key, 'sha256', false)), 32);
			$this->assertSame(strlen(Security::hash($key, 'sha256', true)), 32);
		} else {
			$this->assertSame(strlen(Security::hash($key, 'sha256', false)), 64);
			$this->assertSame(strlen(Security::hash($key, 'sha256', true)), 64);
		}

		Security::setHash($_hashType);
	}

/**
 * Test that hash() works with blowfish.
 *
 * @return void
 */
	public function testHashBlowfish() {
		Security::setCost(10);
		$test = Security::hash('password', 'blowfish');

		$_hashType = Security::$hashType;

		$key = 'someKey';
		$hashType = 'blowfish';
		Security::setHash($hashType);

		$this->assertSame(Security::$hashType, $hashType);
		$this->assertSame(strlen(Security::hash($key, null, false)), 60);

		$password = $submittedPassword = $key;
		$storedPassword = Security::hash($password);

		$hashedPassword = Security::hash($submittedPassword, null, $storedPassword);
		$this->assertSame($storedPassword, $hashedPassword);

		$submittedPassword = 'someOtherKey';
		$hashedPassword = Security::hash($submittedPassword, null, $storedPassword);
		$this->assertNotSame($storedPassword, $hashedPassword);

		$expected = sha1('customsaltsomevalue');
		$result = Security::hash('somevalue', 'sha1', 'customsalt');
		$this->assertSame($expected, $result);

		$oldSalt = Configure::read('Security.salt');
		Configure::write('Security.salt', 'customsalt');

		$expected = sha1('customsaltsomevalue');
		$result = Security::hash('somevalue', 'sha1', true);
		$this->assertSame($expected, $result);

		Configure::write('Security.salt', $oldSalt);
		Security::setHash($_hashType);
	}

/**
 * testRijndael method
 *
 * @return void
 */
	public function testRijndael() {
		$this->skipIf(!function_exists('mcrypt_encrypt'));
		$txt = 'The quick brown fox jumped over the lazy dog.';
		$key = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

		$result = Security::rijndael($txt, $key, 'encrypt');
		$this->assertEquals($txt, Security::rijndael($result, $key, 'decrypt'));

		$result = Security::rijndael($key, $txt, 'encrypt');
		$this->assertEquals($key, Security::rijndael($result, $txt, 'decrypt'));

		$result = Security::rijndael('', $key, 'encrypt');
		$this->assertEquals('', Security::rijndael($result, $key, 'decrypt'));

		$key = 'this is my key of over 32 chars, yes it is';
		$result = Security::rijndael($txt, $key, 'encrypt');
		$this->assertEquals($txt, Security::rijndael($result, $key, 'decrypt'));
	}

/**
 * testRijndaelInvalidOperation method
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testRijndaelInvalidOperation() {
		$txt = 'The quick brown fox jumped over the lazy dog.';
		$key = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';
		Security::rijndael($txt, $key, 'foo');
	}

/**
 * testRijndaelInvalidKey method
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testRijndaelInvalidKey() {
		$txt = 'The quick brown fox jumped over the lazy dog.';
		$key = 'too small';
		Security::rijndael($txt, $key, 'encrypt');
	}

/**
 * Test encrypt/decrypt.
 *
 * @return void
 */
	public function testEncryptDecrypt() {
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
	public function testDecryptKeyFailure() {
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
	public function testDecryptHmacFailure() {
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
	public function testDecryptHmacSaltFailure() {
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
 * @expectedException Cake\Error\Exception
 * @expectedExceptionMessage Invalid key for encrypt(), key must be at least 256 bits (32 bytes) long.
 * @return void
 */
	public function testEncryptInvalidKey() {
		$txt = 'The quick brown fox jumped over the lazy dog.';
		$key = 'this is too short';
		Security::encrypt($txt, $key);
	}

/**
 * Test encrypting falsey data
 *
 * @return void
 */
	public function testEncryptDecryptFalseyData() {
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
 * @expectedException Cake\Error\Exception
 * @expectedExceptionMessage Invalid key for decrypt(), key must be at least 256 bits (32 bytes) long.
 * @return void
 */
	public function testDecryptInvalidKey() {
		$txt = 'The quick brown fox jumped over the lazy dog.';
		$key = 'this is too short';
		Security::decrypt($txt, $key);
	}

/**
 * Test that empty data cause errors
 *
 * @expectedException Cake\Error\Exception
 * @expectedExceptionMessage The data to decrypt cannot be empty.
 * @return void
 */
	public function testDecryptInvalidData() {
		$txt = '';
		$key = 'This is a key that is long enough to be ok.';
		Security::decrypt($txt, $key);
	}

}
