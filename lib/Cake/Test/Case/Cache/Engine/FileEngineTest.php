<?php
/**
 * FileEngineTest file
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
 * @package       Cake.Test.Case.Cache.Engine
 * @since         CakePHP(tm) v 1.2.0.5434
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Cache', 'Cache');

/**
 * FileEngineTest class
 *
 * @package       Cake.Test.Case.Cache.Engine
 */
class FileEngineTest extends CakeTestCase {

/**
 * config property
 *
 * @var array
 */
	public $config = array();

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Cache.disable', false);
		Cache::config('file_test', array('engine' => 'File', 'path' => CACHE));
	}

/**
 * teardown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Cache::clear(false, 'file_test');
		Cache::drop('file_test');
	}

/**
 * testCacheDirChange method
 *
 * @return void
 */
	public function testCacheDirChange() {
		$result = Cache::config('sessions', array('engine'=> 'File', 'path' => TMP . 'sessions'));
		$this->assertEquals($result['settings'], Cache::settings('sessions'));

		$result = Cache::config('sessions', array('engine'=> 'File', 'path' => TMP . 'tests'));
		$this->assertEquals($result['settings'], Cache::settings('sessions'));
		$this->assertNotEquals($result['settings'], Cache::settings('default'));
	}

/**
 * testReadAndWriteCache method
 *
 * @return void
 */
	public function testReadAndWriteCache() {
		Cache::config('default');

		$result = Cache::write(null, 'here', 'file_test');
		$this->assertFalse($result);

		Cache::set(array('duration' => 1), 'file_test');

		$result = Cache::read('test', 'file_test');
		$expecting = '';
		$this->assertEquals($result, $expecting);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data, 'file_test');
		$this->assertTrue(file_exists(CACHE . 'cake_test'));

		$result = Cache::read('test', 'file_test');
		$expecting = $data;
		$this->assertEquals($result, $expecting);

		Cache::delete('test', 'file_test');
	}

/**
 * Test read/write on the same cache key. Ensures file handles are re-wound.
 * 
 * @return void
 */
	public function testConsecutiveReadWrite() {
		Cache::write('rw', 'first write', 'file_test');
		$result = Cache::read('rw', 'file_test');

		Cache::write('rw', 'second write', 'file_test');
		$result2 = Cache::read('rw', 'file_test');

		Cache::delete('rw', 'file_test');
		$this->assertEquals('first write', $result);
		$this->assertEquals('second write', $result2);
	}

/**
 * testExpiry method
 *
 * @return void
 */
	public function testExpiry() {
		Cache::set(array('duration' => 1), 'file_test');

		$result = Cache::read('test', 'file_test');
		$this->assertFalse($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data, 'file_test');
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test', 'file_test');
		$this->assertFalse($result);

		Cache::set(array('duration' =>  "+1 second"), 'file_test');

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data, 'file_test');
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test', 'file_test');
		$this->assertFalse($result);
	}

/**
 * testDeleteCache method
 *
 * @return void
 */
	public function testDeleteCache() {
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test', $data, 'file_test');
		$this->assertTrue($result);

		$result = Cache::delete('delete_test', 'file_test');
		$this->assertTrue($result);
		$this->assertFalse(file_exists(TMP . 'tests' . DS . 'delete_test'));

		$result = Cache::delete('delete_test', 'file_test');
		$this->assertFalse($result);
	}

/**
 * testSerialize method
 *
 * @return void
 */
	public function testSerialize() {
		Cache::config('file_test', array('engine' => 'File', 'serialize' => true));
		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('serialize_test', $data, 'file_test');
		$this->assertTrue($write);

		Cache::config('file_test', array('serialize' => false));
		$read = Cache::read('serialize_test', 'file_test');

		$newread = Cache::read('serialize_test', 'file_test');

		$delete = Cache::delete('serialize_test', 'file_test');

		$this->assertSame($read, serialize($data));

		$this->assertSame(unserialize($newread), $data);
	}

/**
 * testClear method
 *
 * @return void
 */
	public function testClear() {
		Cache::config('file_test', array('engine' => 'File', 'duration' => 1));

		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('serialize_test1', $data, 'file_test');
		$write = Cache::write('serialize_test2', $data, 'file_test');
		$write = Cache::write('serialize_test3', $data, 'file_test');
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test3'));
		sleep(2);
		$result = Cache::clear(true, 'file_test');
		$this->assertTrue($result);
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test3'));

		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('serialize_test1', $data, 'file_test');
		$write = Cache::write('serialize_test2', $data, 'file_test');
		$write = Cache::write('serialize_test3', $data, 'file_test');
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertTrue(file_exists(CACHE . 'cake_serialize_test3'));

		$result = Cache::clear(false, 'file_test');
		$this->assertTrue($result);
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test1'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test2'));
		$this->assertFalse(file_exists(CACHE . 'cake_serialize_test3'));
	}

/**
 * test that clear() doesn't wipe files not in the current engine's prefix.
 *
 * @return void
 */
	public function testClearWithPrefixes() {
		$FileOne = new FileEngine();
		$FileOne->init(array(
			'prefix' => 'prefix_one_',
			'duration' => DAY
		));
		$FileTwo = new FileEngine();
		$FileTwo->init(array(
			'prefix' => 'prefix_two_',
			'duration' => DAY
		));

		$data1 = $data2 = $expected = 'content to cache';
		$FileOne->write('prefix_one_key_one', $data1, DAY);
		$FileTwo->write('prefix_two_key_two', $data2, DAY);

		$this->assertEquals($FileOne->read('prefix_one_key_one'), $expected);
		$this->assertEquals($FileTwo->read('prefix_two_key_two'), $expected);

		$FileOne->clear(false);
		$this->assertEquals($FileTwo->read('prefix_two_key_two'), $expected, 'secondary config was cleared by accident.');
		$FileTwo->clear(false);
	}

/**
 * testKeyPath method
 *
 * @return void
 */
	public function testKeyPath() {
		$result = Cache::write('views.countries.something', 'here', 'file_test');
		$this->assertTrue($result);
		$this->assertTrue(file_exists(CACHE . 'cake_views_countries_something'));

		$result = Cache::read('views.countries.something', 'file_test');
		$this->assertEquals($result, 'here');

		$result = Cache::clear(false, 'file_test');
		$this->assertTrue($result);
	}

/**
 * testRemoveWindowsSlashesFromCache method
 *
 * @return void
 */
	public function testRemoveWindowsSlashesFromCache() {
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
		$this->assertEquals($expected, $data);

		Cache::drop('windows_test');
	}

/**
 * testWriteQuotedString method
 *
 * @return void
 */
	public function testWriteQuotedString() {
		Cache::config('file_test', array('engine' => 'File', 'path' => TMP . 'tests'));
		Cache::write('App.doubleQuoteTest', '"this is a quoted string"', 'file_test');
		$this->assertSame(Cache::read('App.doubleQuoteTest', 'file_test'), '"this is a quoted string"');
		Cache::write('App.singleQuoteTest', "'this is a quoted string'", 'file_test');
		$this->assertSame(Cache::read('App.singleQuoteTest', 'file_test'), "'this is a quoted string'");

		Cache::config('file_test', array('isWindows' => true, 'path' => TMP . 'tests'));
		$this->assertSame(Cache::read('App.doubleQuoteTest', 'file_test'), '"this is a quoted string"');
		Cache::write('App.singleQuoteTest', "'this is a quoted string'", 'file_test');
		$this->assertSame(Cache::read('App.singleQuoteTest', 'file_test'), "'this is a quoted string'");
		Cache::delete('App.singleQuoteTest', 'file_test');
		Cache::delete('App.doubleQuoteTest', 'file_test');
	}

/**
 * check that FileEngine generates an error when a configured Path does not exist.
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testErrorWhenPathDoesNotExist() {
		$this->skipIf(is_dir(TMP . 'tests' . DS . 'file_failure'), 'Cannot run test directory exists.');

		Cache::config('failure', array(
			'engine' => 'File',
			'path' => TMP . 'tests' . DS . 'file_failure'
		));

		Cache::drop('failure');
	}

/**
 * Testing the mask setting in FileEngine
 *
 * @return void
 */
	public function testMaskSetting() {
		Cache::config('mask_test', array('engine' => 'File', 'path' => TMP . 'tests'));
		$data = 'This is some test content';
		$write = Cache::write('masking_test', $data, 'mask_test');
		$result = substr(sprintf('%o',fileperms(TMP . 'tests' . DS .'cake_masking_test')), -4);
		$expected = '0664';
		$this->assertEquals($result, $expected);
		Cache::delete('masking_test', 'mask_test');
		Cache::drop('mask_test');

		Cache::config('mask_test', array('engine' => 'File', 'mask' => 0666, 'path' => TMP . 'tests'));
		$write = Cache::write('masking_test', $data, 'mask_test');
		$result = substr(sprintf('%o',fileperms(TMP . 'tests' . DS .'cake_masking_test')), -4);
		$expected = '0666';
		$this->assertEquals($result, $expected);
		Cache::delete('masking_test', 'mask_test');
		Cache::drop('mask_test');

		Cache::config('mask_test', array('engine' => 'File', 'mask' => 0644, 'path' => TMP . 'tests'));
		$write = Cache::write('masking_test', $data, 'mask_test');
		$result = substr(sprintf('%o',fileperms(TMP . 'tests' . DS .'cake_masking_test')), -4);
		$expected = '0644';
		$this->assertEquals($result, $expected);
		Cache::delete('masking_test', 'mask_test');
		Cache::drop('mask_test');

		Cache::config('mask_test', array('engine' => 'File', 'mask' => 0640, 'path' => TMP . 'tests'));
		$write = Cache::write('masking_test', $data, 'mask_test');
		$result = substr(sprintf('%o',fileperms(TMP . 'tests' . DS .'cake_masking_test')), -4);
		$expected = '0640';
		$this->assertEquals($result, $expected);
		Cache::delete('masking_test', 'mask_test');
		Cache::drop('mask_test');
	}
}
