<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Security', 'Utility');

/**
 * SecurityTest class
 *
 * @package       Cake.Test.Case.Utility
 */
class SecurityTest extends CakeTestCase {

/**
 * sut property
 *
 * @var mixed
 */
	public $sut = null;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::delete('Security.useOpenSsl');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Configure::delete('Security.useOpenSsl');
	}

/**
 * testInactiveMins method
 *
 * @return void
 */
	public function testInactiveMins() {
		Configure::write('Security.level', 'high');
		$this->assertEquals(10, Security::inactiveMins());

		Configure::write('Security.level', 'medium');
		$this->assertEquals(100, Security::inactiveMins());

		Configure::write('Security.level', 'low');
		$this->assertEquals(300, Security::inactiveMins());
	}

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
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testHashInvalidSalt() {
		Security::hash('someKey', 'blowfish', true);
	}

/**
 * testHashAnotherInvalidSalt
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testHashAnotherInvalidSalt() {
		Security::hash('someKey', 'blowfish', '$1$lksdjoijfaoijs');
	}

/**
 * testHashYetAnotherInvalidSalt
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testHashYetAnotherInvalidSalt() {
		Security::hash('someKey', 'blowfish', '$2a$10$123');
	}

/**
 * testHashInvalidCost method
 *
 * @expectedException PHPUnit_Framework_Error
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

		if (!function_exists('hash') && !function_exists('mhash')) {
			$this->assertSame(32, strlen(Security::hash($key, 'sha256', false)));
			$this->assertSame(32, strlen(Security::hash($key, 'sha256', true)));
		} else {
			$this->assertSame(64, strlen(Security::hash($key, 'sha256', false)));
			$this->assertSame(64, strlen(Security::hash($key, 'sha256', true)));
		}

		Security::setHash($_hashType);
	}

/**
 * Test that blowfish doesn't return '' when the salt is ''
 *
 * @return void
 */
	public function testHashBlowfishEmptySalt() {
		$test = Security::hash('password', 'blowfish');
		$this->skipIf(strpos($test, '$2a$') === false, 'Blowfish hashes are incorrect.');

		$stored = '';
		$hash = Security::hash('anything', 'blowfish', $stored);
		$this->assertNotEquals($stored, $hash);

		$hash = Security::hash('anything', 'blowfish', false);
		$this->assertNotEquals($stored, $hash);

		$hash = Security::hash('anything', 'blowfish', null);
		$this->assertNotEquals($stored, $hash);
	}

/**
 * Test that hash() works with blowfish.
 *
 * @return void
 */
	public function testHashBlowfish() {
		$test = Security::hash('password', 'blowfish');
		$this->skipIf(strpos($test, '$2a$') === false, 'Blowfish hashes are incorrect.');

		Security::setCost(10);
		$_hashType = Security::$hashType;

		$key = 'someKey';
		$hashType = 'blowfish';
		Security::setHash($hashType);

		$this->assertSame($hashType, Security::$hashType);
		$this->assertSame(60, strlen(Security::hash($key, null, false)));

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
 * testCipher method
 *
 * @return void
 */
	public function testCipher() {
		$length = 10;
		$txt = '';
		for ($i = 0; $i < $length; $i++) {
			$txt .= mt_rand(0, 255);
		}
		$key = 'my_key';
		$result = Security::cipher($txt, $key);
		$this->assertEquals($txt, Security::cipher($result, $key));

		$txt = '';
		$key = 'my_key';
		$result = Security::cipher($txt, $key);
		$this->assertEquals($txt, Security::cipher($result, $key));

		$txt = 123456;
		$key = 'my_key';
		$result = Security::cipher($txt, $key);
		$this->assertEquals($txt, Security::cipher($result, $key));

		$txt = '123456';
		$key = 'my_key';
		$result = Security::cipher($txt, $key);
		$this->assertEquals($txt, Security::cipher($result, $key));
	}

/**
 * testCipherEmptyKey method
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testCipherEmptyKey() {
		$txt = 'some_text';
		$key = '';
		Security::cipher($txt, $key);
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
 * Test that rijndael() can still decrypt values with a fixed iv.
 *
 * @return void
 */
	public function testRijndaelBackwardCompatibility() {
		$this->skipIf(!function_exists('mcrypt_encrypt'));

		$txt = 'The quick brown fox jumped over the lazy dog.';
		$key = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

		// Encrypted before random iv
		$value = base64_decode('1WPjnq96LMzLGwNgmudHF+cAIqVUN5DaUZEpf5tm1EzSgt5iYY9o3d66iRI/fKJLTlTVGsa8HzW0jDNitmVXoQ==');
		$this->assertEquals($txt, Security::rijndael($value, $key, 'decrypt'));
	}

/**
 * testRijndaelInvalidOperation method
 *
 * @expectedException PHPUnit_Framework_Error
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
 * @expectedException PHPUnit_Framework_Error
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
		$this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
		$txt = 'The quick brown fox';
		$key = 'This key is longer than 32 bytes long.';
		$result = Security::encrypt($txt, $key);
		$this->assertNotEquals($txt, $result, 'Should be encrypted.');
		$this->assertNotEquals($result, Security::encrypt($txt, $key), 'Each result is unique.');
		$this->assertEquals($txt, Security::decrypt($result, $key));
	}

/**
 * Tests that encrypted strings are compatible between the mcrypt and openssl engine.
 *
 * @dataProvider plainTextProvider
 * @param string $txt Plain text to be encrypted.
 * @return void
 */
	public function testEncryptDecryptCompatibility($txt) {
		$this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
		$this->skipIf(!extension_loaded('openssl'), 'This test requires openssl to be installed');
		$this->skipIf(version_compare(PHP_VERSION, '5.3.3', '<'), 'This test requires PHP 5.3.3 or greater');

		$key = '12345678901234567890123456789012';

		Configure::write('Security.useOpenSsl', false);
		$mcrypt = Security::encrypt($txt, $key);

		Configure::write('Security.useOpenSsl', true);
		$openssl = Security::encrypt($txt, $key);

		$this->assertEquals(strlen($mcrypt), strlen($openssl));

		Configure::write('Security.useOpenSsl', false);
		$this->assertEquals($txt, Security::decrypt($mcrypt, $key));
		$this->assertEquals($txt, Security::decrypt($openssl, $key));

		Configure::write('Security.useOpenSsl', true);
		$this->assertEquals($txt, Security::decrypt($mcrypt, $key));
		$this->assertEquals($txt, Security::decrypt($openssl, $key));
	}

/**
 * Data provider for testEncryptDecryptCompatibility
 *
 * @return array
 */
	public function plainTextProvider() {
		return array(
			array(''),
			array('abcdefg'),
			array('1234567890123456'),
			array('The quick brown fox'),
			array('12345678901234567890123456789012'),
			array('The quick brown fox jumped over the lazy dog.'),
			array('何らかのマルチバイト文字列'),
		);
	}

/**
 * Test that changing the key causes decryption to fail.
 *
 * @return void
 */
	public function testDecryptKeyFailure() {
		$this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
		$txt = 'The quick brown fox';
		$key = 'This key is longer than 32 bytes long.';
		Security::encrypt($txt, $key);

		$key = 'Not the same key. This one will fail';
		$this->assertFalse(Security::decrypt($txt, $key), 'Modified key will fail.');
	}

/**
 * Test that decrypt fails when there is an hmac error.
 *
 * @return void
 */
	public function testDecryptHmacFailure() {
		$this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
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
		$this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
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
 * @expectedException CakeException
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
		$this->skipIf(!extension_loaded('mcrypt'), 'This test requires mcrypt to be installed');
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
 * @expectedException CakeException
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
 * @expectedException CakeException
 * @expectedExceptionMessage The data to decrypt cannot be empty.
 * @return void
 */
	public function testDecryptInvalidData() {
		$txt = '';
		$key = 'This is a key that is long enough to be ok.';
		Security::decrypt($txt, $key);
	}

/**
 * Test the random method.
 *
 * @return void
 */
	public function testRandomBytes() {
		$value = Security::randomBytes(16);
		$this->assertSame(16, strlen($value));

		$value = Security::randomBytes(64);
		$this->assertSame(64, strlen($value));
	}
}
