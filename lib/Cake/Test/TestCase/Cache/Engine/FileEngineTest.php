<?php
/**
 * FileEngineTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.5434
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * FileEngineTest class
 *
 * @package       Cake.Test.Case.Cache.Engine
 */
class FileEngineTest extends TestCase {

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
		Configure::write('Cache.file_test', [
			'engine' => 'File',
			'path' => CACHE
		]);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		Cache::clear(false, 'file_test');
		Cache::drop('file_test');
		Cache::drop('file_groups');
		Cache::drop('file_groups2');
		Cache::drop('file_groups3');
		parent::tearDown();
	}

/**
 * testReadAndWriteCache method
 *
 * @return void
 */
	public function testReadAndWriteCache() {
		$result = Cache::write(null, 'here', 'file_test');
		$this->assertFalse($result);

		Cache::set(array('duration' => 1), 'file_test');

		$result = Cache::read('test', 'file_test');
		$expecting = '';
		$this->assertEquals($expecting, $result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data, 'file_test');
		$this->assertTrue(file_exists(CACHE . 'cake_test'));

		$result = Cache::read('test', 'file_test');
		$expecting = $data;
		$this->assertEquals($expecting, $result);

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
		$resultB = Cache::read('rw', 'file_test');

		Cache::delete('rw', 'file_test');
		$this->assertEquals('first write', $result);
		$this->assertEquals('second write', $resultB);
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

		Cache::set(array('duration' => "+1 second"), 'file_test');

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
		$this->assertFalse(file_exists(TMP . 'tests/delete_test'));

		$result = Cache::delete('delete_test', 'file_test');
		$this->assertFalse($result);
	}

/**
 * testSerialize method
 *
 * @return void
 */
	public function testSerialize() {
		Configure::write('Cache.file_test', ['engine' => 'File', 'serialize' => true]);
		$data = 'this is a test of the emergency broadcasting system';
		$write = Cache::write('serialize_test', $data, 'file_test');
		$this->assertTrue($write);

		Cache::set(['serialize' => false], 'file_test');
		$read = Cache::read('serialize_test', 'file_test');

		$delete = Cache::delete('serialize_test', 'file_test');
		$this->assertSame($read, serialize($data));
		$this->assertSame(unserialize($read), $data);
	}

/**
 * testClear method
 *
 * @return void
 */
	public function testClear() {
		Configure::write('Cache.file_test', ['engine' => 'File', 'duration' => 1]);

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

		$dataOne = $dataTwo = $expected = 'content to cache';
		$FileOne->write('prefix_one_key_one', $dataOne, DAY);
		$FileTwo->write('prefix_two_key_two', $dataTwo, DAY);

		$this->assertEquals($expected, $FileOne->read('prefix_one_key_one'));
		$this->assertEquals($expected, $FileTwo->read('prefix_two_key_two'));

		$FileOne->clear(false);
		$this->assertEquals($expected, $FileTwo->read('prefix_two_key_two'), 'secondary config was cleared by accident.');
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
		$this->assertEquals('here', $result);

		$result = Cache::clear(false, 'file_test');
		$this->assertTrue($result);
	}

/**
 * testRemoveWindowsSlashesFromCache method
 *
 * @return void
 */
	public function testRemoveWindowsSlashesFromCache() {
		Configure::write('Cache.windows_test', [
			'engine' => 'File',
			'isWindows' => true,
			'prefix' => null,
			'path' => TMP
		]);

		$expected = array(
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
		Configure::write('Cache.file_test', array('engine' => 'File', 'path' => TMP . 'tests'));
		Cache::write('App.doubleQuoteTest', '"this is a quoted string"', 'file_test');
		$this->assertSame(Cache::read('App.doubleQuoteTest', 'file_test'), '"this is a quoted string"');
		Cache::write('App.singleQuoteTest', "'this is a quoted string'", 'file_test');
		$this->assertSame(Cache::read('App.singleQuoteTest', 'file_test'), "'this is a quoted string'");

		Configure::write('Cache.file_test', array('isWindows' => true, 'path' => TMP . 'tests'));
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
		$this->skipIf(is_dir(TMP . 'tests/file_failure'), 'Cannot run test directory exists.');

		$engine = new FileEngine();
		$engine->init([
			'engine' => 'File',
			'path' => TMP . 'tests/file_failure'
		]);

		Cache::drop('failure');
	}

/**
 * Testing the mask setting in FileEngine
 *
 * @return void
 */
	public function testMaskSetting() {
		if (DS === '\\') {
			$this->markTestSkipped('File permission testing does not work on Windows.');
		}
		Configure::write('Cache.mask_test', ['engine' => 'File', 'path' => TMP . 'tests']);
		$data = 'This is some test content';
		$write = Cache::write('masking_test', $data, 'mask_test');
		$result = substr(sprintf('%o',fileperms(TMP . 'tests/cake_masking_test')), -4);
		$expected = '0664';
		$this->assertEquals($expected, $result);
		Cache::delete('masking_test', 'mask_test');
		Cache::drop('mask_test');

		Configure::write('Cache.mask_test', ['engine' => 'File', 'mask' => 0666, 'path' => TMP . 'tests']);
		$write = Cache::write('masking_test', $data, 'mask_test');
		$result = substr(sprintf('%o',fileperms(TMP . 'tests/cake_masking_test')), -4);
		$expected = '0666';
		$this->assertEquals($expected, $result);
		Cache::delete('masking_test', 'mask_test');
		Cache::drop('mask_test');

		Configure::write('Cache.mask_test', ['engine' => 'File', 'mask' => 0644, 'path' => TMP . 'tests']);
		$write = Cache::write('masking_test', $data, 'mask_test');
		$result = substr(sprintf('%o',fileperms(TMP . 'tests/cake_masking_test')), -4);
		$expected = '0644';
		$this->assertEquals($expected, $result);
		Cache::delete('masking_test', 'mask_test');
		Cache::drop('mask_test');

		Configure::write('Cache.mask_test', ['engine' => 'File', 'mask' => 0640, 'path' => TMP . 'tests']);
		$write = Cache::write('masking_test', $data, 'mask_test');
		$result = substr(sprintf('%o',fileperms(TMP . 'tests/cake_masking_test')), -4);
		$expected = '0640';
		$this->assertEquals($expected, $result);
		Cache::delete('masking_test', 'mask_test');
		Cache::drop('mask_test');
	}

/**
 * Tests that configuring groups for stored keys return the correct values when read/written
 *
 * @return void
 */
	public function testGroupsReadWrite() {
		Configure::write('Cache.file_groups', [
			'engine' => 'File',
			'duration' => 3600,
			'groups' => array('group_a', 'group_b')
		]);
		$this->assertTrue(Cache::write('test_groups', 'value', 'file_groups'));
		$this->assertEquals('value', Cache::read('test_groups', 'file_groups'));

		$this->assertTrue(Cache::write('test_groups2', 'value2', 'file_groups'));
		$this->assertTrue(Cache::write('test_groups3', 'value3', 'file_groups'));
	}

/**
 * Test that clearing with repeat writes works properly
 */
	public function testClearingWithRepeatWrites() {
		Configure::write('Cache.repeat', [
			'engine' => 'File',
			'groups' => array('users')
		]);

		$this->assertTrue(Cache::write('user', 'rchavik', 'repeat'));
		$this->assertEquals('rchavik', Cache::read('user', 'repeat'));

		Cache::delete('user', 'repeat');
		$this->assertEquals(false, Cache::read('user', 'repeat'));

		$this->assertTrue(Cache::write('user', 'ADmad', 'repeat'));
		$this->assertEquals('ADmad', Cache::read('user', 'repeat'));

		Cache::clearGroup('users', 'repeat');
		$this->assertEquals(false, Cache::read('user', 'repeat'));

		$this->assertTrue(Cache::write('user', 'markstory', 'repeat'));
		$this->assertEquals('markstory', Cache::read('user', 'repeat'));

		Cache::drop('repeat');
	}

/**
 * Tests that deleting from a groups-enabled config is possible
 *
 * @return void
 */
	public function testGroupDelete() {
		Configure::write('Cache.file_groups', [
			'engine' => 'File',
			'duration' => 3600,
			'groups' => array('group_a', 'group_b')
		]);
		$this->assertTrue(Cache::write('test_groups', 'value', 'file_groups'));
		$this->assertEquals('value', Cache::read('test_groups', 'file_groups'));
		$this->assertTrue(Cache::delete('test_groups', 'file_groups'));

		$this->assertFalse(Cache::read('test_groups', 'file_groups'));
	}

/**
 * Test clearing a cache group
 *
 * @return void
 */
	public function testGroupClear() {
		Configure::write('Cache.file_groups', [
			'engine' => 'File',
			'duration' => 3600,
			'groups' => array('group_a', 'group_b')
		]);
		Configure::write('Cache.file_groups2', [
			'engine' => 'File',
			'duration' => 3600,
			'groups' => array('group_b')
		]);
		Configure::write('Cache.file_groups3', [
			'engine' => 'File',
			'duration' => 3600,
			'groups' => array('group_b'),
			'prefix' => 'leading_',
		]);

		$this->assertTrue(Cache::write('test_groups', 'value', 'file_groups'));
		$this->assertTrue(Cache::write('test_groups2', 'value 2', 'file_groups2'));
		$this->assertTrue(Cache::write('test_groups3', 'value 3', 'file_groups3'));

		$this->assertTrue(Cache::clearGroup('group_b', 'file_groups'));
		$this->assertFalse(Cache::read('test_groups', 'file_groups'));
		$this->assertFalse(Cache::read('test_groups2', 'file_groups2'));
		$this->assertEquals('value 3', Cache::read('test_groups3', 'file_groups3'));

		$this->assertTrue(Cache::write('test_groups4', 'value', 'file_groups'));
		$this->assertTrue(Cache::write('test_groups5', 'value 2', 'file_groups2'));
		$this->assertTrue(Cache::write('test_groups6', 'value 3', 'file_groups3'));

		$this->assertTrue(Cache::clearGroup('group_b', 'file_groups'));
		$this->assertFalse(Cache::read('test_groups4', 'file_groups'));
		$this->assertFalse(Cache::read('test_groups5', 'file_groups2'));
		$this->assertEquals('value 3', Cache::read('test_groups6', 'file_groups3'));
	}

/**
 * testInvalidConfig method
 *
 * Test that the cache class doesn't cause fatal errors with a partial path
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testInvalidConfig() {
		Configure::write('Cache.invalid', [
			'engine' => 'File',
			'duration' => '+1 year',
			'prefix' => 'testing_invalid_',
			'path' => 'data/',
			'serialize' => true,
			'random' => 'wii'
		]);
		Cache::read('Test', 'invalid');
	}

}
