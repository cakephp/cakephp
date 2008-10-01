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
 * @subpackage		cake.tests.cases
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once CAKE.'basics.php';
/**
 * BasicsTest class
 *
 * @package              cake.tests
 * @subpackage           cake.tests.cases
 */
class BasicsTest extends CakeTestCase {
/**
 * testHttpBase method
 *
 * @return void
 * @access public
 */
	function testHttpBase() {
		$__SERVER = $_SERVER;

		$_SERVER['HTTP_HOST'] = 'localhost';
		$this->assertEqual(env('HTTP_BASE'), '');

		$_SERVER['HTTP_HOST'] = 'example.com';
		$this->assertEqual(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$this->assertEqual(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'subdomain.example.com';
		$this->assertEqual(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'double.subdomain.example.com';
		$this->assertEqual(env('HTTP_BASE'), '.subdomain.example.com');

		$_SERVER = $__SERVER;
	}
/**
 * test uses()
 *
 * @access public
 * @return void
 */	
	 function testUses() {
		$this->assertFalse(class_exists('Security'));
		$this->assertFalse(class_exists('Sanitize'));

		uses('Security', 'Sanitize');

		$this->assertTrue(class_exists('Security'));
		$this->assertTrue(class_exists('Sanitize'));
	}
/**
 * Test h()
 *
 * @access public
 * @return void
 */
	 function testH() {
		$string = '<foo>';
		$result = h($string);
		$this->assertEqual('&lt;foo&gt;', $result);

		$in = array('this & that', '<p>Which one</p>');
		$result = h($in);
		$expected = array('this &amp; that', '&lt;p&gt;Which one&lt;/p&gt;');
		$this->assertEqual($expected, $result);
	}
/**
 * Test a()
 *
 * @access public
 * @return void
 */
	 function testA() {
		$result = a('this', 'that', 'bar');
		$this->assertEqual(array('this', 'that', 'bar'), $result);
	}
/**
 * Test aa()
 *
 * @access public
 * @return void
 */
	 function testAa() {
		$result = aa('a', 'b', 'c', 'd');
		$expected = array('a' => 'b', 'c' => 'd');
		$this->assertEqual($expected, $result);
 
		$result = aa('a', 'b', 'c', 'd', 'e');
		$expected = array('a' => 'b', 'c' => 'd', 'e' => null);
		$this->assertEqual($result, $expected);
	}
/**
 * Test am()
 *
 * @access public
 * @return void
 */	
	 function testAm() {
		$result = am(array('one', 'two'), 2, 3, 4);
		$expected = array('one', 'two', 2, 3, 4);
		$this->assertEqual($result, $expected);

		$result = am(array('one' => array(2, 3), 'two' => array('foo')), array('one' => array(4, 5)));
		$expected = array('one' => array(4, 5),'two' => array('foo'));
		$this->assertEqual($result, $expected);
	}
/**
 * test cache()
 *
 * @access public
 * @return void
 */
	 function testCache() {
		Configure::write('Cache.disable', true);
		$result = cache('basics_test', 'simple cache write');
		$this->assertNull($result);

		$result = cache('basics_test');
		$this->assertNull($result);
 
		Configure::write('Cache.disable', false);
		$result = cache('basics_test', 'simple cache write');
		$this->assertTrue($result);
		$this->assertTrue(file_exists(CACHE . 'basics_test'));

		$result = cache('basics_test');
		$this->assertEqual($result, 'simple cache write');
		@unlink(CACHE . 'basics_test');
		
		cache('basics_test', 'expired', '+1 second');
		sleep(2);
		$result = cache('basics_test', null, '+1 second');
		$this->assertNull($result);
	}

}
?>