<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Security');
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class SecurityTest extends UnitTestCase {
	var $sut = null;
	
	function setUp() {
		$this->sut =& Security::getInstance();
	}
	
	function testInactiveMins() {
		Configure::write('Security.level', 'high');
		$this->assertEqual(10, Security::inactiveMins());

		Configure::write('Security.level', 'medium');
		$this->assertEqual(100, Security::inactiveMins());

		Configure::write('Security.level', 'low');
		$this->assertEqual(300, Security::inactiveMins());
	}
	
	function testGenerateAuthkey() {
		$this->assertEqual(strlen(Security::generateAuthKey()), 40);
	}
	
	function testValidateAuthKey() {
		$authKey = Security::generateAuthKey();
		$this->assertTrue(Security::validateAuthKey($authKey));
	}

	function testHash() {
		$key = 'someKey';
		$this->assertIdentical(strlen(Security::hash($key, null, false)), 40);
		$this->assertIdentical(strlen(Security::hash($key, 'sha1', false)), 40);
		$this->assertIdentical(strlen(Security::hash($key, null, true)), 40);
		$this->assertIdentical(strlen(Security::hash($key, 'sha1', true)), 40);

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


		if (function_exists('mhash')) {
			$this->assertIdentical(strlen(Security::hash($key, 'sha256', false)), 64);
			$this->assertIdentical(strlen(Security::hash($key, 'sha256', true)), 64);
		} else {
			$this->assertIdentical(strlen(Security::hash($key, 'sha256', false)), 32);
			$this->assertIdentical(strlen(Security::hash($key, 'sha256', true)), 32);
		}
	}

	function testCipher() {
		$length = 10;
		$txt = '';
		for ($i = 0; $i < $length; $i++) { 
			$txt .= rand(0, 255);
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
	}
}
?>