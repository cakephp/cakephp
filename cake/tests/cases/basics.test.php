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
 * Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2008, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake.tests
 * @subpackage    cake.tests.cases
 * @since         CakePHP(tm) v 1.2.0.4206
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once CAKE.'basics.php';
/**
 * BasicsTest class
 *
 * @package       cake.tests
 * @subpackage    cake.tests.cases
 */
class BasicsTest extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		Configure::write('localePaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'locale'));
	}
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
/**
 * test clearCache()
 *
 * @access public
 * @return void
 */
	function testClearCache() {
		cache('views' . DS . 'basics_test.cache', 'simple cache write');
		$this->assertTrue(file_exists(CACHE . 'views' . DS . 'basics_test.cache'));

		cache('views' . DS . 'basics_test_2.cache', 'simple cache write 2');
		$this->assertTrue(file_exists(CACHE . 'views' . DS . 'basics_test_2.cache'));

		cache('views' . DS . 'basics_test_3.cache', 'simple cache write 3');
		$this->assertTrue(file_exists(CACHE . 'views' . DS . 'basics_test_3.cache'));

		$result = clearCache(array('basics_test', 'basics_test_2'), 'views', '.cache');
		$this->assertTrue($result);
		$this->assertFalse(file_exists(CACHE . 'views' . DS . 'basics_test.cache'));
		$this->assertFalse(file_exists(CACHE . 'views' . DS . 'basics_test.cache'));
		$this->assertTrue(file_exists(CACHE . 'views' . DS . 'basics_test_3.cache'));

		$result = clearCache(null, 'views', '.cache');
		$this->assertTrue($result);
		$this->assertFalse(file_exists(CACHE . 'views' . DS . 'basics_test_3.cache'));

		// Different path from views and with prefix
		cache('models' . DS . 'basics_test.cache', 'simple cache write');
		$this->assertTrue(file_exists(CACHE . 'models' . DS . 'basics_test.cache'));

		cache('models' . DS . 'basics_test_2.cache', 'simple cache write 2');
		$this->assertTrue(file_exists(CACHE . 'models' . DS . 'basics_test_2.cache'));

		cache('models' . DS . 'basics_test_3.cache', 'simple cache write 3');
		$this->assertTrue(file_exists(CACHE . 'models' . DS . 'basics_test_3.cache'));

		$result = clearCache('basics', 'models', '.cache');
		$this->assertTrue($result);
		$this->assertFalse(file_exists(CACHE . 'models' . DS . 'basics_test.cache'));
		$this->assertFalse(file_exists(CACHE . 'models' . DS . 'basics_test_2.cache'));
		$this->assertFalse(file_exists(CACHE . 'models' . DS . 'basics_test_3.cache'));
	}
/**
 * test __()
 *
 * @access public
 * @return void
 */
	function test__() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __('Plural Rule 1', true);
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEqual($result, $expected);

		$result = __('Plural Rule 1 (from core)', true);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($result, $expected);

		ob_start();
			__('Plural Rule 1 (from core)');
		$result = ob_get_clean();
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($result, $expected);
	}
/**
 * test __n()
 *
 * @access public
 * @return void
 */
	function test__n() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __n('%d = 1', '%d = 0 or > 1', 0, true);
		$expected = '%d = 0 or > 1 (translated)';
		$this->assertEqual($result, $expected);

		$result = __n('%d = 1', '%d = 0 or > 1', 1, true);
		$expected = '%d = 1 (translated)';
		$this->assertEqual($result, $expected);

		$result = __n('%d = 1 (from core)', '%d = 0 or > 1 (from core)', 2, true);
		$expected = '%d = 0 or > 1 (from core translated)';
		$this->assertEqual($result, $expected);

		ob_start();
			__n('%d = 1 (from core)', '%d = 0 or > 1 (from core)', 2);
		$result = ob_get_clean();
		$expected = '%d = 0 or > 1 (from core translated)';
		$this->assertEqual($result, $expected);
	}
/**
 * test __d()
 *
 * @access public
 * @return void
 */
	function test__d() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __d('default', 'Plural Rule 1', true);
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEqual($result, $expected);

		$result = __d('core', 'Plural Rule 1', true);
		$expected = 'Plural Rule 1';
		$this->assertEqual($result, $expected);

		$result = __d('core', 'Plural Rule 1 (from core)', true);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($result, $expected);

		ob_start();
			__d('core', 'Plural Rule 1 (from core)');
		$result = ob_get_clean();
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($result, $expected);
	}
/**
 * test __dn()
 *
 * @access public
 * @return void
 */
	function test__dn() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dn('default', '%d = 1', '%d = 0 or > 1', 0, true);
		$expected = '%d = 0 or > 1 (translated)';
		$this->assertEqual($result, $expected);

		$result = __dn('core', '%d = 1', '%d = 0 or > 1', 0, true);
		$expected = '%d = 0 or > 1';
		$this->assertEqual($result, $expected);

		$result = __dn('core', '%d = 1 (from core)', '%d = 0 or > 1 (from core)', 0, true);
		$expected = '%d = 0 or > 1 (from core translated)';
		$this->assertEqual($result, $expected);

		$result = __dn('default', '%d = 1', '%d = 0 or > 1', 1, true);
		$expected = '%d = 1 (translated)';
		$this->assertEqual($result, $expected);

		ob_start();
			__dn('core', '%d = 1 (from core)', '%d = 0 or > 1 (from core)', 2);
		$result = ob_get_clean();
		$expected = '%d = 0 or > 1 (from core translated)';
		$this->assertEqual($result, $expected);
	}
/**
 * test __c()
 *
 * @access public
 * @return void
 */
	function test__c() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __c('Plural Rule 1', 5, true);
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEqual($result, $expected);

		$result = __c('Plural Rule 1 (from core)', 5, true);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($result, $expected);

		ob_start();
			__c('Plural Rule 1 (from core)', 5);
		$result = ob_get_clean();
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($result, $expected);
	}
/**
 * test __dc()
 *
 * @access public
 * @return void
 */
	function test__dc() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dc('default', 'Plural Rule 1', 5, true);
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEqual($result, $expected);

		$result = __dc('default', 'Plural Rule 1 (from core)', 5, true);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($result, $expected);

		$result = __dc('core', 'Plural Rule 1', 5, true);
		$expected = 'Plural Rule 1';
		$this->assertEqual($result, $expected);

		$result = __dc('core', 'Plural Rule 1 (from core)', 5, true);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($result, $expected);

		ob_start();
			__dc('default', 'Plural Rule 1 (from core)', 5);
		$result = ob_get_clean();
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($result, $expected);
	}
/**
 * test __dcn()
 *
 * @access public
 * @return void
 */
	function test__dcn() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dcn('default', '%d = 1', '%d = 0 or > 1', 0, 5, true);
		$expected = '%d = 0 or > 1 (translated)';
		$this->assertEqual($result, $expected);

		$result = __dcn('default', '%d = 1 (from core)', '%d = 0 or > 1 (from core)', 1, 5, true);
		$expected = '%d = 1 (from core translated)';
		$this->assertEqual($result, $expected);

		$result = __dcn('core', '%d = 1', '%d = 0 or > 1', 0, 5, true);
		$expected = '%d = 0 or > 1';
		$this->assertEqual($result, $expected);

		ob_start();
			__dcn('default', '%d = 1 (from core)', '%d = 0 or > 1 (from core)', 1, 5);
		$result = ob_get_clean();
		$expected = '%d = 1 (from core translated)';
		$this->assertEqual($result, $expected);
	}
/**
 * test LogError()
 *
 * @access public
 * @return void
 */
	function testLogError() {
		@unlink(LOGS . 'error.log');

		LogError('Testing LogError() basic function');
		LogError("Testing with\nmulti-line\nstring");

		$result = file_get_contents(LOGS . 'error.log');
		$this->assertPattern('/Error: Testing LogError\(\) basic function/', $result);
		$this->assertNoPattern("/Error: Testing with\nmulti-line\nstring/", $result);
		$this->assertPattern('/Error: Testing with multi-line string/', $result);
	}
/**
 * test convertSlash()
 *
 * @access public
 * @return void
 */
	function testConvertSlash() {
		$result = convertSlash('\path\to\location\\');
		$expected = '\path\to\location\\';
		$this->assertEqual($result, $expected);

		$result = convertSlash('/path/to/location/');
		$expected = 'path_to_location';
		$this->assertEqual($result, $expected);
	}
}
?>