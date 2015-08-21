<?php
/**
 * XcacheEngineTest file
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
 * @package       Cake.Test.Case.Cache.Engine
 * @since         CakePHP(tm) v 1.2.0.5434
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Cache', 'Cache');

/**
 * XcacheEngineTest class
 *
 * @package       Cake.Test.Case.Cache.Engine
 */
class XcacheEngineTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		if (!function_exists('xcache_set')) {
			$this->markTestSkipped('Xcache is not installed or configured properly');
		}
		$this->_cacheDisable = Configure::read('Cache.disable');
		Configure::write('Cache.disable', false);
		Cache::config('xcache', array('engine' => 'Xcache', 'prefix' => 'cake_'));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Configure::write('Cache.disable', $this->_cacheDisable);
		Cache::drop('xcache');
		Cache::drop('xcache_groups');
		Cache::config('default');
	}

/**
 * testSettings method
 *
 * @return void
 */
	public function testSettings() {
		$settings = Cache::settings();
		$expecting = array(
			'prefix' => 'cake_',
			'duration' => 3600,
			'probability' => 100,
			'engine' => 'Xcache',
		);
		$this->assertTrue(isset($settings['PHP_AUTH_USER']));
		$this->assertTrue(isset($settings['PHP_AUTH_PW']));

		unset($settings['PHP_AUTH_USER'], $settings['PHP_AUTH_PW']);
		$this->assertEquals($settings, $expecting);
	}

/**
 * testReadAndWriteCache method
 *
 * @return void
 */
	public function testReadAndWriteCache() {
		Cache::set(array('duration' => 1));

		$result = Cache::read('test');
		$expecting = '';
		$this->assertEquals($expecting, $result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data);
		$this->assertTrue($result);

		$result = Cache::read('test');
		$expecting = $data;
		$this->assertEquals($expecting, $result);

		Cache::delete('test');
	}

/**
 * testExpiry method
 *
 * @return void
 */
	public function testExpiry() {
		Cache::set(array('duration' => 1));
		$result = Cache::read('test');
		$this->assertFalse($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data);
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test');
		$this->assertFalse($result);

		Cache::set(array('duration' => "+1 second"));

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
 * @return void
 */
	public function testDeleteCache() {
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test', $data);
		$this->assertTrue($result);

		$result = Cache::delete('delete_test');
		$this->assertTrue($result);
	}

/**
 * testClearCache method
 *
 * @return void
 */
	public function testClearCache() {
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('clear_test_1', $data);
		$this->assertTrue($result);

		$result = Cache::write('clear_test_2', $data);
		$this->assertTrue($result);

		$result = Cache::clear();
		$this->assertTrue($result);
	}

/**
 * testDecrement method
 *
 * @return void
 */
	public function testDecrement() {
		$result = Cache::write('test_decrement', 5);
		$this->assertTrue($result);

		$result = Cache::decrement('test_decrement');
		$this->assertEquals(4, $result);

		$result = Cache::read('test_decrement');
		$this->assertEquals(4, $result);

		$result = Cache::decrement('test_decrement', 2);
		$this->assertEquals(2, $result);

		$result = Cache::read('test_decrement');
		$this->assertEquals(2, $result);
	}

/**
 * testIncrement method
 *
 * @return void
 */
	public function testIncrement() {
		$result = Cache::write('test_increment', 5);
		$this->assertTrue($result);

		$result = Cache::increment('test_increment');
		$this->assertEquals(6, $result);

		$result = Cache::read('test_increment');
		$this->assertEquals(6, $result);

		$result = Cache::increment('test_increment', 2);
		$this->assertEquals(8, $result);

		$result = Cache::read('test_increment');
		$this->assertEquals(8, $result);
	}

/**
 * Tests that configuring groups for stored keys return the correct values when read/written
 * Shows that altering the group value is equivalent to deleting all keys under the same
 * group
 *
 * @return void
 */
	public function testGroupsReadWrite() {
		Cache::config('xcache_groups', array(
			'engine' => 'Xcache',
			'duration' => 0,
			'groups' => array('group_a', 'group_b'),
			'prefix' => 'test_'
		));
		$this->assertTrue(Cache::write('test_groups', 'value', 'xcache_groups'));
		$this->assertEquals('value', Cache::read('test_groups', 'xcache_groups'));

		xcache_inc('test_group_a', 1);
		$this->assertFalse(Cache::read('test_groups', 'xcache_groups'));
		$this->assertTrue(Cache::write('test_groups', 'value2', 'xcache_groups'));
		$this->assertEquals('value2', Cache::read('test_groups', 'xcache_groups'));

		xcache_inc('test_group_b', 1);
		$this->assertFalse(Cache::read('test_groups', 'xcache_groups'));
		$this->assertTrue(Cache::write('test_groups', 'value3', 'xcache_groups'));
		$this->assertEquals('value3', Cache::read('test_groups', 'xcache_groups'));
	}

/**
 * Tests that deleteing from a groups-enabled config is possible
 *
 * @return void
 */
	public function testGroupDelete() {
		Cache::config('xcache_groups', array(
			'engine' => 'Xcache',
			'duration' => 0,
			'groups' => array('group_a', 'group_b'),
			'prefix' => 'test_'
		));
		$this->assertTrue(Cache::write('test_groups', 'value', 'xcache_groups'));
		$this->assertEquals('value', Cache::read('test_groups', 'xcache_groups'));
		$this->assertTrue(Cache::delete('test_groups', 'xcache_groups'));

		$this->assertFalse(Cache::read('test_groups', 'xcache_groups'));
	}

/**
 * Test clearing a cache group
 *
 * @return void
 */
	public function testGroupClear() {
		Cache::config('xcache_groups', array(
			'engine' => 'Xcache',
			'duration' => 0,
			'groups' => array('group_a', 'group_b'),
			'prefix' => 'test_'
		));

		$this->assertTrue(Cache::write('test_groups', 'value', 'xcache_groups'));
		$this->assertTrue(Cache::clearGroup('group_a', 'xcache_groups'));
		$this->assertFalse(Cache::read('test_groups', 'xcache_groups'));

		$this->assertTrue(Cache::write('test_groups', 'value2', 'xcache_groups'));
		$this->assertTrue(Cache::clearGroup('group_b', 'xcache_groups'));
		$this->assertFalse(Cache::read('test_groups', 'xcache_groups'));
	}

/**
 * Test that failed add write return false.
 *
 * @return void
 */
	public function testAdd() {
		Cache::set(array('duration' => 1), null);
		Cache::delete('test_add_key', 'default');

		$result = Cache::add('test_add_key', 'test data', 'default');
		$this->assertTrue($result);

		$expected = 'test data';
		$result = Cache::read('test_add_key', 'default');
		$this->assertEquals($expected, $result);

		$result = Cache::add('test_add_key', 'test data 2', 'default');
		$this->assertFalse($result);
	}
}
