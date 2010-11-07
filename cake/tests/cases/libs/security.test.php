<?php
/**
 * SecurityTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Security');

/**
 * SecurityTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class SecurityTest extends CakeTestCase {

/**
 * sut property
 *
 * @var mixed null
 * @access public
 */
	var $sut = null;

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->sut =& Security::getInstance();
	}

/**
 * testInactiveMins method
 *
 * @access public
 * @return void
 */
	function testInactiveMins() {
		Configure::write('Security.level', 'high');
		$this->assertEqual(10, Security::inactiveMins());

		Configure::write('Security.level', 'medium');
		$this->assertEqual(100, Security::inactiveMins());

		Configure::write('Security.level', 'low');
		$this->assertEqual(300, Security::inactiveMins());
	}

/**
 * testGenerateAuthkey method
 *
 * @access public
 * @return void
 */
	function testGenerateAuthkey() {
		$this->assertEqual(strlen(Security::generateAuthKey()), 40);
	}

/**
 * testValidateAuthKey method
 *
 * @access public
 * @return void
 */
	function testValidateAuthKey() {
		$authKey = Security::generateAuthKey();
		$this->assertTrue(Security::validateAuthKey($authKey));
	}

/**
 * testHash method
 *
 * @access public
 * @return void
 */
	function testHash() {
		$Security =& Security::getInstance();
		$_hashType =  $Security->hashType;

		$key = 'someKey';
		$hash = 'someHash';

		$this->assertIdentical(strlen(Security::hash($key, null, false)), 40);
		$this->assertIdentical(strlen(Security::hash($key, 'sha1', false)), 40);
		$this->assertIdentical(strlen(Security::hash($key, null, true)), 40);
		$this->assertIdentical(strlen(Security::hash($key, 'sha1', true)), 40);

		$result = Security::hash($key, null, $hash);
		$this->assertIdentical($result, 'e38fcb877dccb6a94729a81523851c931a46efb1');

		$result = Security::hash($key, 'sha1', $hash);
		$this->assertIdentical($result, 'e38fcb877dccb6a94729a81523851c931a46efb1');

		$hashType = 'sha1';
		Security::setHash($hashType);
		$this->assertIdentical($this->sut->hashType, $hashType);
		$this->assertIdentical(strlen(Security::hash($key, null, true)), 40);
		$this->assertIdentical(strlen(Security::hash($key, null, false)), 40);

		$this->assertIdentical(strlen(Security::hash($key, 'md5', false)), 32);
		$this->assertIdentical(strlen(Security::hash($key, 'md5', true)), 32);

		$hashType = 'md5';
		Security::setHash($hashType);
		$this->assertIdentical($this->sut->hashType, $hashType);
		$this->assertIdentical(strlen(Security::hash($key, null, false)), 32);
		$this->assertIdentical(strlen(Security::hash($key, null, true)), 32);

		if (!function_exists('hash') && !function_exists('mhash')) {
			$this->assertIdentical(strlen(Security::hash($key, 'sha256', false)), 32);
			$this->assertIdentical(strlen(Security::hash($key, 'sha256', true)), 32);
		} else {
			$this->assertIdentical(strlen(Security::hash($key, 'sha256', false)), 64);
			$this->assertIdentical(strlen(Security::hash($key, 'sha256', true)), 64);
		}

		Security::setHash($_hashType);
	}

/**
 * testCipher method
 *
 * @access public
 * @return void
 */
	function testCipher() {
		$length = 10;
		$txt = '';
		for ($i = 0; $i < $length; $i++) {
			$txt .= mt_rand(0, 255);
		}
		$key = 'my_key';
		$result = Security::cipher($txt, $key);
		$this->assertEqual(Security::cipher($result, $key), $txt);

		$txt = '';
		$key = 'my_key';
		$result = Security::cipher($txt, $key);
		$this->assertEqual(Security::cipher($result, $key), $txt);

		$txt = 'some_text';
		$key = '';
		$result = Security::cipher($txt, $key);
		$this->assertError();
		$this->assertIdentical($result, '');

		$txt = 123456;
		$key = 'my_key';
		$result = Security::cipher($txt, $key);
		$this->assertEqual(Security::cipher($result, $key), $txt);

		$txt = '123456';
		$key = 'my_key';
		$result = Security::cipher($txt, $key);
		$this->assertEqual(Security::cipher($result, $key), $txt);
	}
}
