<?php
/**
 * BasicsTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

require_once CAKE . 'basics.php';
App::uses('Folder', 'Utility');

/**
 * BasicsTest class
 *
 * @package       Cake.Test.Case
 */
class BasicsTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		App::build(array(
			'locales' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Locale' . DS)
		));
		$this->_language = Configure::read('Config.language');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		App::build();
		Configure::write('Config.language', $this->_language);
	}

/**
 * test the array_diff_key compatibility function.
 *
 * @return void
 */
	public function testArrayDiffKey() {
		$one = array('one' => 1, 'two' => 2, 'three' => 3);
		$two = array('one' => 'one', 'two' => 'two');
		$result = array_diff_key($one, $two);
		$expected = array('three' => 3);
		$this->assertEqual($expected, $result);

		$one = array('one' => array('value', 'value-two'), 'two' => 2, 'three' => 3);
		$two = array('two' => 'two');
		$result = array_diff_key($one, $two);
		$expected = array('one' => array('value', 'value-two'), 'three' => 3);
		$this->assertEqual($expected, $result);

		$one = array('one' => null, 'two' => 2, 'three' => '', 'four' => 0);
		$two = array('two' => 'two');
		$result = array_diff_key($one, $two);
		$expected = array('one' => null, 'three' => '', 'four' => 0);
		$this->assertEqual($expected, $result);

		$one = array('minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true);
		$two = array('minYear' => null, 'maxYear' => null, 'separator' => '-', 'interval' => 1, 'monthNames' => true);
		$result = array_diff_key($one, $two);
		$this->assertEqual($result, array());

	}
/**
 * testHttpBase method
 *
 * @return void
 */
	public function testEnv() {
		$this->skipIf(!function_exists('ini_get') || ini_get('safe_mode') === '1', 'Safe mode is on.');

		$__SERVER = $_SERVER;
		$__ENV = $_ENV;

		$_SERVER['HTTP_HOST'] = 'localhost';
		$this->assertEqual(env('HTTP_BASE'), '.localhost');

		$_SERVER['HTTP_HOST'] = 'com.ar';
		$this->assertEqual(env('HTTP_BASE'), '.com.ar');

		$_SERVER['HTTP_HOST'] = 'example.ar';
		$this->assertEqual(env('HTTP_BASE'), '.example.ar');

		$_SERVER['HTTP_HOST'] = 'example.com';
		$this->assertEqual(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$this->assertEqual(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'subdomain.example.com';
		$this->assertEqual(env('HTTP_BASE'), '.example.com');

		$_SERVER['HTTP_HOST'] = 'example.com.ar';
		$this->assertEqual(env('HTTP_BASE'), '.example.com.ar');

		$_SERVER['HTTP_HOST'] = 'www.example.com.ar';
		$this->assertEqual(env('HTTP_BASE'), '.example.com.ar');

		$_SERVER['HTTP_HOST'] = 'subdomain.example.com.ar';
		$this->assertEqual(env('HTTP_BASE'), '.example.com.ar');

		$_SERVER['HTTP_HOST'] = 'double.subdomain.example.com';
		$this->assertEqual(env('HTTP_BASE'), '.subdomain.example.com');

		$_SERVER['HTTP_HOST'] = 'double.subdomain.example.com.ar';
		$this->assertEqual(env('HTTP_BASE'), '.subdomain.example.com.ar');

		$_SERVER = $_ENV = array();

		$_SERVER['SCRIPT_NAME'] = '/a/test/test.php';
		$this->assertEqual(env('SCRIPT_NAME'), '/a/test/test.php');

		$_SERVER = $_ENV = array();

		$_ENV['CGI_MODE'] = 'BINARY';
		$_ENV['SCRIPT_URL'] = '/a/test/test.php';
		$this->assertEqual(env('SCRIPT_NAME'), '/a/test/test.php');

		$_SERVER = $_ENV = array();

		$this->assertFalse(env('HTTPS'));

		$_SERVER['HTTPS'] = 'on';
		$this->assertTrue(env('HTTPS'));

		$_SERVER['HTTPS'] = '1';
		$this->assertTrue(env('HTTPS'));

		$_SERVER['HTTPS'] = 'I am not empty';
		$this->assertTrue(env('HTTPS'));

		$_SERVER['HTTPS'] = 1;
		$this->assertTrue(env('HTTPS'));

		$_SERVER['HTTPS'] = 'off';
		$this->assertFalse(env('HTTPS'));

		$_SERVER['HTTPS'] = false;
		$this->assertFalse(env('HTTPS'));

		$_SERVER['HTTPS'] = '';
		$this->assertFalse(env('HTTPS'));

		$_SERVER = array();

		$_ENV['SCRIPT_URI'] = 'https://domain.test/a/test.php';
		$this->assertTrue(env('HTTPS'));

		$_ENV['SCRIPT_URI'] = 'http://domain.test/a/test.php';
		$this->assertFalse(env('HTTPS'));

		$_SERVER = $_ENV = array();

		$this->assertNull(env('TEST_ME'));

		$_ENV['TEST_ME'] = 'a';
		$this->assertEqual(env('TEST_ME'), 'a');

		$_SERVER['TEST_ME'] = 'b';
		$this->assertEqual(env('TEST_ME'), 'b');

		unset($_ENV['TEST_ME']);
		$this->assertEqual(env('TEST_ME'), 'b');

		$_SERVER = $__SERVER;
		$_ENV = $__ENV;
	}

/**
 * Test h()
 *
 * @return void
 */
	public function testH() {
		$string = '<foo>';
		$result = h($string);
		$this->assertEqual('&lt;foo&gt;', $result);

		$in = array('this & that', '<p>Which one</p>');
		$result = h($in);
		$expected = array('this &amp; that', '&lt;p&gt;Which one&lt;/p&gt;');
		$this->assertEqual($expected, $result);

		$string = '<foo> & &nbsp;';
		$result = h($string);
		$this->assertEqual('&lt;foo&gt; &amp; &amp;nbsp;', $result);

		$string = '<foo> & &nbsp;';
		$result = h($string, false);
		$this->assertEqual('&lt;foo&gt; &amp; &nbsp;', $result);

		$string = '<foo> & &nbsp;';
		$result = h($string, 'UTF-8');
		$this->assertEqual('&lt;foo&gt; &amp; &amp;nbsp;', $result);

		$arr = array('<foo>', '&nbsp;');
		$result = h($arr);
		$expected = array(
			'&lt;foo&gt;',
			'&amp;nbsp;'
		);
		$this->assertEqual($expected, $result);

		$arr = array('<foo>', '&nbsp;');
		$result = h($arr, false);
		$expected = array(
			'&lt;foo&gt;',
			'&nbsp;'
		);
		$this->assertEqual($expected, $result);

		$arr = array('f' => '<foo>', 'n' => '&nbsp;');
		$result = h($arr, false);
		$expected = array(
			'f' => '&lt;foo&gt;',
			'n' => '&nbsp;'
		);
		$this->assertEqual($expected, $result);
	}

/**
 * Test am()
 *
 * @return void
 */
	public function testAm() {
		$result = am(array('one', 'two'), 2, 3, 4);
		$expected = array('one', 'two', 2, 3, 4);
		$this->assertEqual($expected, $result);

		$result = am(array('one' => array(2, 3), 'two' => array('foo')), array('one' => array(4, 5)));
		$expected = array('one' => array(4, 5),'two' => array('foo'));
		$this->assertEqual($expected, $result);
	}

/**
 * test cache()
 *
 * @return void
 */
	public function testCache() {
		$_cacheDisable = Configure::read('Cache.disable');
		$this->skipIf($_cacheDisable, 'Cache is disabled, skipping cache() tests.');

		Configure::write('Cache.disable', true);
		$result = cache('basics_test', 'simple cache write');
		$this->assertNull($result);

		$result = cache('basics_test');
		$this->assertNull($result);

		Configure::write('Cache.disable', false);
		$result = cache('basics_test', 'simple cache write');
		$this->assertTrue((boolean)$result);
		$this->assertTrue(file_exists(CACHE . 'basics_test'));

		$result = cache('basics_test');
		$this->assertEqual($result, 'simple cache write');
		@unlink(CACHE . 'basics_test');

		cache('basics_test', 'expired', '+1 second');
		sleep(2);
		$result = cache('basics_test', null, '+1 second');
		$this->assertNull($result);

		Configure::write('Cache.disable', $_cacheDisable);
	}

/**
 * test clearCache()
 *
 * @return void
 */
	public function testClearCache() {
		$cacheOff = Configure::read('Cache.disable');
		$this->skipIf($cacheOff, 'Cache is disabled, skipping clearCache() tests.');

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

		// checking if empty files were not removed
		$emptyExists = file_exists(CACHE . 'views' . DS . 'empty');
		if (!$emptyExists) {
			cache('views' . DS . 'empty', '');
		}
		cache('views' . DS . 'basics_test.php', 'simple cache write');
		$this->assertTrue(file_exists(CACHE . 'views' . DS . 'basics_test.php'));
		$this->assertTrue(file_exists(CACHE . 'views' . DS . 'empty'));

		$result = clearCache();
		$this->assertTrue($result);
		$this->assertTrue(file_exists(CACHE . 'views' . DS . 'empty'));
		$this->assertFalse(file_exists(CACHE . 'views' . DS . 'basics_test.php'));
		if (!$emptyExists) {
			unlink(CACHE . 'views' . DS . 'empty');
		}
	}

/**
 * test __()
 *
 * @return void
 */
	public function test__() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __('Plural Rule 1');
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEqual($expected, $result);

		$result = __('Plural Rule 1 (from core)');
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($expected, $result);

		$result = __('Some string with %s', 'arguments');
		$expected = 'Some string with arguments';
		$this->assertEqual($expected, $result);

		$result = __('Some string with %s %s', 'multiple', 'arguments');
		$expected = 'Some string with multiple arguments';
		$this->assertEqual($expected, $result);

		$result = __('Some string with %s %s', array('multiple', 'arguments'));
		$expected = 'Some string with multiple arguments';
		$this->assertEqual($expected, $result);

		$result = __('Testing %2$s %1$s', 'order', 'different');
		$expected = 'Testing different order';
		$this->assertEqual($expected, $result);

		$result = __('Testing %2$s %1$s', array('order', 'different'));
		$expected = 'Testing different order';
		$this->assertEqual($expected, $result);

		$result = __('Testing %.2f number', 1.2345);
		$expected = 'Testing 1.23 number';
		$this->assertEqual($expected, $result);
	}

/**
 * test __n()
 *
 * @return void
 */
	public function test__n() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __n('%d = 1', '%d = 0 or > 1', 0);
		$expected = '%d = 0 or > 1 (translated)';
		$this->assertEqual($expected, $result);

		$result = __n('%d = 1', '%d = 0 or > 1', 1);
		$expected = '%d = 1 (translated)';
		$this->assertEqual($expected, $result);

		$result = __n('%d = 1 (from core)', '%d = 0 or > 1 (from core)', 2);
		$expected = '%d = 0 or > 1 (from core translated)';
		$this->assertEqual($expected, $result);

		$result = __n('%d item.', '%d items.', 1, 1);
		$expected = '1 item.';
		$this->assertEqual($expected, $result);

		$result = __n('%d item for id %s', '%d items for id %s', 2, 2, '1234');
		$expected = '2 items for id 1234';
		$this->assertEqual($expected, $result);

		$result = __n('%d item for id %s', '%d items for id %s', 2, array(2, '1234'));
		$expected = '2 items for id 1234';
		$this->assertEqual($expected, $result);
	}

/**
 * test __d()
 *
 * @return void
 */
	public function test__d() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __d('default', 'Plural Rule 1');
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEqual($expected, $result);

		$result = __d('core', 'Plural Rule 1');
		$expected = 'Plural Rule 1';
		$this->assertEqual($expected, $result);

		$result = __d('core', 'Plural Rule 1 (from core)');
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($expected, $result);

		$result = __d('core', 'Some string with %s', 'arguments');
		$expected = 'Some string with arguments';
		$this->assertEqual($expected, $result);

		$result = __d('core', 'Some string with %s %s', 'multiple', 'arguments');
		$expected = 'Some string with multiple arguments';
		$this->assertEqual($expected, $result);

		$result = __d('core', 'Some string with %s %s', array('multiple', 'arguments'));
		$expected = 'Some string with multiple arguments';
		$this->assertEqual($expected, $result);
	}

/**
 * test __dn()
 *
 * @return void
 */
	public function test__dn() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dn('default', '%d = 1', '%d = 0 or > 1', 0);
		$expected = '%d = 0 or > 1 (translated)';
		$this->assertEqual($expected, $result);

		$result = __dn('core', '%d = 1', '%d = 0 or > 1', 0);
		$expected = '%d = 0 or > 1';
		$this->assertEqual($expected, $result);

		$result = __dn('core', '%d = 1 (from core)', '%d = 0 or > 1 (from core)', 0);
		$expected = '%d = 0 or > 1 (from core translated)';
		$this->assertEqual($expected, $result);

		$result = __dn('default', '%d = 1', '%d = 0 or > 1', 1);
		$expected = '%d = 1 (translated)';
		$this->assertEqual($expected, $result);

		$result = __dn('core', '%d item.', '%d items.', 1, 1);
		$expected = '1 item.';
		$this->assertEqual($expected, $result);

		$result = __dn('core', '%d item for id %s', '%d items for id %s', 2, 2, '1234');
		$expected = '2 items for id 1234';
		$this->assertEqual($expected, $result);

		$result = __dn('core', '%d item for id %s', '%d items for id %s', 2, array(2, '1234'));
		$expected = '2 items for id 1234';
		$this->assertEqual($expected, $result);
	}

/**
 * test __c()
 *
 * @return void
 */
	public function test__c() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __c('Plural Rule 1', 6);
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEqual($expected, $result);

		$result = __c('Plural Rule 1 (from core)', 6);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($expected, $result);

		$result = __c('Some string with %s', 6, 'arguments');
		$expected = 'Some string with arguments';
		$this->assertEqual($expected, $result);

		$result = __c('Some string with %s %s', 6, 'multiple', 'arguments');
		$expected = 'Some string with multiple arguments';
		$this->assertEqual($expected, $result);

		$result = __c('Some string with %s %s', 6, array('multiple', 'arguments'));
		$expected = 'Some string with multiple arguments';
		$this->assertEqual($expected, $result);
	}

/**
 * test __dc()
 *
 * @return void
 */
	public function test__dc() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dc('default', 'Plural Rule 1', 6);
		$expected = 'Plural Rule 1 (translated)';
		$this->assertEqual($expected, $result);

		$result = __dc('default', 'Plural Rule 1 (from core)', 6);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($expected, $result);

		$result = __dc('core', 'Plural Rule 1', 6);
		$expected = 'Plural Rule 1';
		$this->assertEqual($expected, $result);

		$result = __dc('core', 'Plural Rule 1 (from core)', 6);
		$expected = 'Plural Rule 1 (from core translated)';
		$this->assertEqual($expected, $result);

		$result = __dc('core', 'Some string with %s', 6, 'arguments');
		$expected = 'Some string with arguments';
		$this->assertEqual($expected, $result);

		$result = __dc('core', 'Some string with %s %s', 6, 'multiple', 'arguments');
		$expected = 'Some string with multiple arguments';
		$this->assertEqual($expected, $result);

		$result = __dc('core', 'Some string with %s %s', 6, array('multiple', 'arguments'));
		$expected = 'Some string with multiple arguments';
		$this->assertEqual($expected, $result);
	}

/**
 * test __dcn()
 *
 * @return void
 */
	public function test__dcn() {
		Configure::write('Config.language', 'rule_1_po');

		$result = __dcn('default', '%d = 1', '%d = 0 or > 1', 0, 6);
		$expected = '%d = 0 or > 1 (translated)';
		$this->assertEqual($expected, $result);

		$result = __dcn('default', '%d = 1 (from core)', '%d = 0 or > 1 (from core)', 1, 6);
		$expected = '%d = 1 (from core translated)';
		$this->assertEqual($expected, $result);

		$result = __dcn('core', '%d = 1', '%d = 0 or > 1', 0, 6);
		$expected = '%d = 0 or > 1';
		$this->assertEqual($expected, $result);

		$result = __dcn('core', '%d item.', '%d items.', 1, 6, 1);
		$expected = '1 item.';
		$this->assertEqual($expected, $result);

		$result = __dcn('core', '%d item for id %s', '%d items for id %s', 2, 6, 2, '1234');
		$expected = '2 items for id 1234';
		$this->assertEqual($expected, $result);

		$result = __dcn('core', '%d item for id %s', '%d items for id %s', 2, 6, array(2, '1234'));
		$expected = '2 items for id 1234';
		$this->assertEqual($expected, $result);
	}

/**
 * test LogError()
 *
 * @return void
 */
	public function testLogError() {
		@unlink(LOGS . 'error.log');

		LogError('Testing LogError() basic function');
		LogError("Testing with\nmulti-line\nstring");

		$result = file_get_contents(LOGS . 'error.log');
		$this->assertPattern('/Error: Testing LogError\(\) basic function/', $result);
		$this->assertNoPattern("/Error: Testing with\nmulti-line\nstring/", $result);
		$this->assertPattern('/Error: Testing with multi-line string/', $result);
	}

/**
 * test fileExistsInPath()
 *
 * @return void
 */
	public function testFileExistsInPath() {
		$this->skipUnless(function_exists('ini_set'), '%s ini_set function not available');

		$_includePath = ini_get('include_path');

		$path = TMP . 'basics_test';
		$folder1 = $path . DS . 'folder1';
		$folder2 = $path . DS . 'folder2';
		$file1 = $path . DS . 'file1.php';
		$file2 = $folder1 . DS . 'file2.php';
		$file3 = $folder1 . DS . 'file3.php';
		$file4 = $folder2 . DS . 'file4.php';

		new Folder($path, true);
		new Folder($folder1, true);
		new Folder($folder2, true);
		touch($file1);
		touch($file2);
		touch($file3);
		touch($file4);

		ini_set('include_path', $path . PATH_SEPARATOR . $folder1);

		$this->assertEqual(fileExistsInPath('file1.php'), $file1);
		$this->assertEqual(fileExistsInPath('file2.php'), $file2);
		$this->assertEqual(fileExistsInPath('folder1' . DS . 'file2.php'), $file2);
		$this->assertEqual(fileExistsInPath($file2), $file2);
		$this->assertEqual(fileExistsInPath('file3.php'), $file3);
		$this->assertEqual(fileExistsInPath($file4), $file4);

		$this->assertFalse(fileExistsInPath('file1'));
		$this->assertFalse(fileExistsInPath('file4.php'));

		$Folder = new Folder($path);
		$Folder->delete();

		ini_set('include_path', $_includePath);
	}

/**
 * test convertSlash()
 *
 * @return void
 */
	public function testConvertSlash() {
		$result = convertSlash('\path\to\location\\');
		$expected = '\path\to\location\\';
		$this->assertEqual($expected, $result);

		$result = convertSlash('/path/to/location/');
		$expected = 'path_to_location';
		$this->assertEqual($expected, $result);
	}

/**
 * test debug()
 *
 * @return void
 */
	public function testDebug() {
		ob_start();
			debug('this-is-a-test');
		$result = ob_get_clean();
		$pattern = '/(.+?Test(\/|\\\)Case(\/|\\\)BasicsTest\.php|';
		$pattern .= preg_quote(substr(__FILE__, 1), '/') . ')';
		$pattern .= '.*line.*' . (__LINE__ - 4) . '.*this-is-a-test.*/s';
		$this->assertRegExp($pattern, $result);

		ob_start();
			debug('<div>this-is-a-test</div>', true);
		$result = ob_get_clean();
		$pattern = '/(.+?Test(\/|\\\)Case(\/|\\\)BasicsTest\.php|';
		$pattern .= preg_quote(substr(__FILE__, 1), '/') . ')';
		$pattern .= '.*line.*' . (__LINE__ -4) . '.*&lt;div&gt;this-is-a-test&lt;\/div&gt;.*/s';
		$this->assertRegExp($pattern, $result);

		ob_start();
			debug('<div>this-is-a-test</div>', false);
		$result = ob_get_clean();
		$pattern = '/(.+?Test(\/|\\\)Case(\/|\\\)BasicsTest\.php|';
		$pattern .= preg_quote(substr(__FILE__, 1), '/') . ')';
		$pattern .=	'.*line.*' . (__LINE__ - 4) . '.*\<div\>this-is-a-test\<\/div\>.*/s';
		$this->assertRegExp($pattern, $result);
	}

/**
 * test pr()
 *
 * @return void
 */
	public function testPr() {
		ob_start();
			pr('this is a test');
		$result = ob_get_clean();
		$expected = "<pre>this is a test</pre>";
		$this->assertEqual($expected, $result);

		ob_start();
			pr(array('this' => 'is', 'a' => 'test'));
		$result = ob_get_clean();
		$expected = "<pre>Array\n(\n    [this] => is\n    [a] => test\n)\n</pre>";
		$this->assertEqual($expected, $result);
	}

/**
 * test stripslashes_deep()
 *
 * @return void
 */
	public function testStripslashesDeep() {
		$this->skipIf(ini_get('magic_quotes_sybase') === '1', 'magic_quotes_sybase is on.');

		$this->assertEqual(stripslashes_deep("tes\'t"), "tes't");
		$this->assertEqual(stripslashes_deep('tes\\' . chr(0) .'t'), 'tes' . chr(0) .'t');
		$this->assertEqual(stripslashes_deep('tes\"t'), 'tes"t');
		$this->assertEqual(stripslashes_deep("tes\'t"), "tes't");
		$this->assertEqual(stripslashes_deep('te\\st'), 'test');

		$nested = array(
			'a' => "tes\'t",
			'b' => 'tes\\' . chr(0) .'t',
			'c' => array(
				'd' => 'tes\"t',
				'e' => "te\'s\'t",
				array('f' => "tes\'t")
				),
			'g' => 'te\\st'
			);
		$expected = array(
			'a' => "tes't",
			'b' => 'tes' . chr(0) .'t',
			'c' => array(
				'd' => 'tes"t',
				'e' => "te's't",
				array('f' => "tes't")
				),
			'g' => 'test'
			);
		$this->assertEqual(stripslashes_deep($nested), $expected);
	}

/**
 * test stripslashes_deep() with magic_quotes_sybase on
 *
 * @return void
 */
	public function testStripslashesDeepSybase() {
		$this->skipUnless(ini_get('magic_quotes_sybase') === '1', 'magic_quotes_sybase is off');

		$this->assertEqual(stripslashes_deep("tes\'t"), "tes\'t");

		$nested = array(
			'a' => "tes't",
			'b' => "tes''t",
			'c' => array(
				'd' => "tes'''t",
				'e' => "tes''''t",
				array('f' => "tes''t")
				),
			'g' => "te'''''st"
			);
		$expected = array(
			'a' => "tes't",
			'b' => "tes't",
			'c' => array(
				'd' => "tes''t",
				'e' => "tes''t",
				array('f' => "tes't")
				),
			'g' => "te'''st"
			);
		$this->assertEqual(stripslashes_deep($nested), $expected);
	}

/**
 * test pluginSplit
 *
 * @return void
 */
	public function testPluginSplit() {
		$result = pluginSplit('Something.else');
		$this->assertEqual($result, array('Something', 'else'));

		$result = pluginSplit('Something.else.more.dots');
		$this->assertEqual($result, array('Something', 'else.more.dots'));

		$result = pluginSplit('Somethingelse');
		$this->assertEqual($result, array(null, 'Somethingelse'));

		$result = pluginSplit('Something.else', true);
		$this->assertEqual($result, array('Something.', 'else'));

		$result = pluginSplit('Something.else.more.dots', true);
		$this->assertEqual($result, array('Something.', 'else.more.dots'));

		$result = pluginSplit('Post', false, 'Blog');
		$this->assertEqual($result, array('Blog', 'Post'));

		$result = pluginSplit('Blog.Post', false, 'Ultimate');
		$this->assertEqual($result, array('Blog', 'Post'));
	}
}
