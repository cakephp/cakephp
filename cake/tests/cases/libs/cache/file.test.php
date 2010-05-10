<?php
/**
 * FileEngineTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.cache
 * @since         CakePHP(tm) v 1.2.0.5434
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!class_exists('Cache')) {
	require LIBS . 'cache.php';
}

/**
 * FileEngineTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.cache
 */
class FileEngineTest extends CakeTestCase {

/**
 * config property
 *
 * @var array
 * @access public
 */
	var $config = array();

/**
 * startCase method
 *
 * @access public
 * @return void
 */
	function startCase() {
		$this->_cacheDisable = Configure::read('Cache.disable');
		$this->_cacheConfig = Cache::config('default');
		Configure::write('Cache.disable', false);
		Cache::config('default', array('engine' => 'File', 'path' => CACHE));
	}

/**
 * endCase method
 *
 * @access public
 * @return void
 */
	function endCase() {
		Configure::write('Cache.disable', $this->_cacheDisable);
		Cache::config('default', $this->_cacheConfig['settings']);
	}

/**
 * testCacheDirChange method
 *
 * @access public
 * @return void
 */
	function testCacheDirChange() {
		$result = Cache::config('sessions', array('engine'=> 'File', 'path' => TMP . 'sessions'));
		$this->assertEqual($result['settings'], Cache::settings('sessions'));

		$result = Cache::config('sessions', array('engine'=> 'File', 'path' => TMP . 'tests'));
		$this->assertEqual($result['settings'], Cache::settings('sessions'));
		$this->assertNotEqual($result['settings'], Cache::settings('default'));
	}

/**
 * testReadAndWriteCache method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteCache() {
		Cache::config('default');

		$result = Cache::write(null, 'here');
		$this->assertFalse($result);

		Cache::set(array('duration' => 1));

		$result = Cache::read('test');
		$expecting = '';
		$this->assertEqual($result, $expecting);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data);
		$this->assertTrue(file_exists(CACHE . 'cake_test'));

		$result = Cache::read('test');
		$expecting = $data;
		$this->assertEqual($result, $expecting);

		Cache::delete('test');
	}

/**
 * testExpiry method
 *
 * @access public
 * @return void
 */
	function testExpiry() {
		Cache::set(array('duration' => 1));

		$result = Cache::read('test');
		$this->assertFalse($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data);
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test');
		$this->assertFalse($result);

		Cache::set(array('duration' =>  "+1 second"));

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data);
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test');
		$this->assertFalse($result);
	}

/**
 * testDeleteCache method
 *
 * @access public
 * @return void
 */
	function testDeleteCache() {
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test', $data);
		$this->assertTrue($result);

		$result = Cache::delete('delete_test');
		$this->assertTrue($result);
		$this->assertFalse(file_exists(TMP . 'tests' . DS . 'delete_test'));

		$result = Cache::delete('delete_test');
		$this->assertFalse($result);
	}

/**
 * testSerialize method
 *
 * @access public
 * @return void
 */
	function testSerialize() {
		Cache::config('default', array('engine' => 'File', 'serialize' => true));
		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('serialize_test', $data);
		$this->assertTrue($write);

		Cache::config('default', array('serialize' => false));
		$read = Cache::read('serialize_test');

		$newread = Cache::read('serialize_test');

		$delete = Cache::delete('serialize_test');

		$this->assertIdentical($read, serialize($data));

		$this->assertIdentical(unserialize($newread), $data);
	}

/**
 * testClear method
 *
 * @access public
 * @return void
 */
	function testClear() {
		Cache::config('default', array('engine' => 'File', 'duration' => 1));
		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('serialize_test1', $data);
		$write = Cache::write('serialize_test2', $data);
		$write = Cache::write('serialize_test3', $data);
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test3'));
		sleep(2);
		$result = Cache::clear(true);
		$this->assertTrue($result);
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test3'));

		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('serialize_test1', $data);
		$write = Cache::write('serialize_test2', $data);
		$write = Cache::write('serialize_test3', $data);
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test3'));

		$result = Cache::clear();
		$this->assertTrue($result);
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test3'));

		Cache::config('default', array('engine' => 'File', 'path' => CACHE . 'views'));

		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('controller_view_1', $data);
		$write = Cache::write('controller_view_2', $data);
		$write = Cache::write('controller_view_3', $data);
		$write = Cache::write('controller_view_10', $data);
		$write = Cache::write('controller_view_11', $data);
		$write = Cache::write('controller_view_12', $data);
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_1'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_2'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_3'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_10'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_11'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_12'));

		clearCache('controller_view_1', 'views', '');
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_1'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_2'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_3'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_10'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_11'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_12'));

		clearCache('controller_view', 'views', '');
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_1'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_2'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_3'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_10'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_11'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_12'));

		$write = Cache::write('controller_view_1', $data);
		$write = Cache::write('controller_view_2', $data);
		$write = Cache::write('controller_view_3', $data);
		$write = Cache::write('controller_view_10', $data);
		$write = Cache::write('controller_view_11', $data);
		$write = Cache::write('controller_view_12', $data);
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_1'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_2'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_3'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_10'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_11'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_12'));

		clearCache(array('controller_view_2', 'controller_view_11', 'controller_view_12'), 'views', '');
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_1'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_2'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_3'));
		$this->assertTrue(file_exists(CACHE . 'views'. DS . 'cake_controller_view_10'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_11'));
		$this->assertFalse(file_exists(CACHE . 'views'. DS . 'cake_controller_view_12'));

		clearCache('controller_view');

		Cache::config('default', array('engine' => 'File', 'path' => CACHE));
	}

/**
 * testKeyPath method
 *
 * @access public
 * @return void
 */
	function testKeyPath() {
		$result = Cache::write('views.countries.something', 'here');
		$this->assertTrue($result);
		$this->assertTrue(file_exists(CACHE . 'cake_views_countries_something'));

		$result = Cache::read('views.countries.something');
		$this->assertEqual($result, 'here');

		$result = Cache::clear();
		$this->assertTrue($result);
	}

/**
 * testRemoveWindowsSlashesFromCache method
 *
 * @access public
 * @return void
 */
	function testRemoveWindowsSlashesFromCache() {
		Cache::config('windows_test', array('engine' => 'File', 'isWindows' => true, 'prefix' => null, 'path' => TMP));

		$expected = array (
			'C:\dev\prj2\sites\cake\libs' => array(
				0 => 'C:\dev\prj2\sites\cake\libs', 1 => 'C:\dev\prj2\sites\cake\libs\view',
				2 => 'C:\dev\prj2\sites\cake\libs\view\scaffolds', 3 => 'C:\dev\prj2\sites\cake\libs\view\pages',
				4 => 'C:\dev\prj2\sites\cake\libs\view\layouts', 5 => 'C:\dev\prj2\sites\cake\libs\view\layouts\xml',
				6 => 'C:\dev\prj2\sites\cake\libs\view\layouts\rss', 7 => 'C:\dev\prj2\sites\cake\libs\view\layouts\js',
				8 => 'C:\dev\prj2\sites\cake\libs\view\layouts\email', 9 => 'C:\dev\prj2\sites\cake\libs\view\layouts\email\text',
				10 => 'C:\dev\prj2\sites\cake\libs\view\layouts\email\html', 11 => 'C:\dev\prj2\sites\cake\libs\view\helpers',
				12 => 'C:\dev\prj2\sites\cake\libs\view\errors', 13 => 'C:\dev\prj2\sites\cake\libs\view\elements',
				14 => 'C:\dev\prj2\sites\cake\libs\view\elements\email', 15 => 'C:\dev\prj2\sites\cake\libs\view\elements\email\text',
				16 => 'C:\dev\prj2\sites\cake\libs\view\elements\email\html', 17 => 'C:\dev\prj2\sites\cake\libs\model',
				18 => 'C:\dev\prj2\sites\cake\libs\model\datasources', 19 => 'C:\dev\prj2\sites\cake\libs\model\datasources\dbo',
				20 => 'C:\dev\prj2\sites\cake\libs\model\behaviors', 21 => 'C:\dev\prj2\sites\cake\libs\controller',
				22 => 'C:\dev\prj2\sites\cake\libs\controller\components', 23 => 'C:\dev\prj2\sites\cake\libs\cache'),
			'C:\dev\prj2\sites\main_site\vendors' => array(
				0 => 'C:\dev\prj2\sites\main_site\vendors', 1 => 'C:\dev\prj2\sites\main_site\vendors\shells',
				2 => 'C:\dev\prj2\sites\main_site\vendors\shells\templates', 3 => 'C:\dev\prj2\sites\main_site\vendors\shells\templates\cdc_project',
				4 => 'C:\dev\prj2\sites\main_site\vendors\shells\tasks', 5 => 'C:\dev\prj2\sites\main_site\vendors\js',
				6 => 'C:\dev\prj2\sites\main_site\vendors\css'),
			'C:\dev\prj2\sites\vendors' => array(
				0 => 'C:\dev\prj2\sites\vendors', 1 => 'C:\dev\prj2\sites\vendors\simpletest',
				2 => 'C:\dev\prj2\sites\vendors\simpletest\test', 3 => 'C:\dev\prj2\sites\vendors\simpletest\test\support',
				4 => 'C:\dev\prj2\sites\vendors\simpletest\test\support\collector', 5 => 'C:\dev\prj2\sites\vendors\simpletest\extensions',
				6 => 'C:\dev\prj2\sites\vendors\simpletest\extensions\testdox', 7 => 'C:\dev\prj2\sites\vendors\simpletest\docs',
				8 => 'C:\dev\prj2\sites\vendors\simpletest\docs\fr', 9 => 'C:\dev\prj2\sites\vendors\simpletest\docs\en'),
			'C:\dev\prj2\sites\main_site\views\helpers' => array(
				0 => 'C:\dev\prj2\sites\main_site\views\helpers')
		);

		Cache::write('test_dir_map', $expected, 'windows_test');
		$data = Cache::read('test_dir_map', 'windows_test');
		Cache::delete('test_dir_map', 'windows_test');
		$this->assertEqual($expected, $data);

		Cache::drop('windows_test');
	}

/**
 * testWriteQuotedString method
 *
 * @access public
 * @return void
 */
	function testWriteQuotedString() {
		Cache::config('default', array('engine' => 'File', 'path' => TMP . 'tests'));
		Cache::write('App.doubleQuoteTest', '"this is a quoted string"');
		$this->assertIdentical(Cache::read('App.doubleQuoteTest'), '"this is a quoted string"');
		Cache::write('App.singleQuoteTest', "'this is a quoted string'");
		$this->assertIdentical(Cache::read('App.singleQuoteTest'), "'this is a quoted string'");

		Cache::config('default', array('isWindows' => true, 'path' => TMP . 'tests'));
		$this->assertIdentical(Cache::read('App.doubleQuoteTest'), '"this is a quoted string"');
		Cache::write('App.singleQuoteTest', "'this is a quoted string'");
		$this->assertIdentical(Cache::read('App.singleQuoteTest'), "'this is a quoted string'");
	}

/**
 * check that FileEngine generates an error when a configured Path does not exist.
 *
 * @return void
 */
	function testErrorWhenPathDoesNotExist() {
		if ($this->skipIf(is_dir(TMP . 'tests' . DS . 'file_failure'), 'Cannot run test directory exists. %s')) {
			return;
		}
		$this->expectError();
		Cache::config('failure', array(
			'engine' => 'File',
			'path' => TMP . 'tests' . DS . 'file_failure'
		));

		Cache::drop('failure');
	}
}
