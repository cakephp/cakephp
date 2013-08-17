<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         CakePHP(tm) v 2.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache\Engine;

use Cake\Cache\Cache;
use Cake\Cache\Engine\RedisEngine;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * RedisEngineTest class
 *
 * @package       Cake.Test.Case.Cache.Engine
 */
class RedisEngineTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->skipIf(!class_exists('Redis'), 'Redis is not installed or configured properly.');

		Cache::enable();
		Cache::config('redis', array(
			'engine' => 'Cake\Cache\Engine\RedisEngine',
			'prefix' => 'cake_',
			'duration' => 3600
		));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Cache::drop('redis');
		Cache::drop('redis_groups');
		Cache::drop('redis_helper');
	}

/**
 * testSettings method
 *
 * @return void
 */
	public function testSettings() {
		$settings = Cache::settings('redis');
		$expecting = array(
			'prefix' => 'cake_',
			'duration' => 3600,
			'probability' => 100,
			'groups' => array(),
			'server' => '127.0.0.1',
			'port' => 6379,
			'timeout' => 0,
			'persistent' => true,
			'password' => false,
		);
		$this->assertEquals($expecting, $settings);
	}

/**
 * testConnect method
 *
 * @return void
 */
	public function testConnect() {
		$Redis = new RedisEngine();
		$this->assertTrue($Redis->init(Cache::settings('redis')));
	}

/**
 * testReadAndWriteCache method
 *
 * @return void
 */
	public function testReadAndWriteCache() {
		Cache::set(['duration' => 1], 'redis');

		$result = Cache::read('test', 'redis');
		$expecting = '';
		$this->assertEquals($expecting, $result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data, 'redis');
		$this->assertTrue($result);

		$result = Cache::read('test', 'redis');
		$expecting = $data;
		$this->assertEquals($expecting, $result);

		$data = array(1, 2, 3);
		$this->assertTrue(Cache::write('array_data', $data, 'redis'));
		$this->assertEquals($data, Cache::read('array_data', 'redis'));

		Cache::delete('test', 'redis');
	}

/**
 * testExpiry method
 *
 * @return void
 */
	public function testExpiry() {
		Cache::set(array('duration' => 1), 'redis');

		$result = Cache::read('test', 'redis');
		$this->assertFalse($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data, 'redis');
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test', 'redis');
		$this->assertFalse($result);

		Cache::set(array('duration' => "+1 second"), 'redis');

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data, 'redis');
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test', 'redis');
		$this->assertFalse($result);

		Cache::set(['duration' => '+1 second'], 'redis');
		sleep(2);

		$result = Cache::read('other_test', 'redis');
		$this->assertFalse($result);

		Cache::set(['duration' => '+29 days'], 'redis');
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('long_expiry_test', $data, 'redis');
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('long_expiry_test', 'redis');
		$expecting = $data;
		$this->assertEquals($expecting, $result);
	}

/**
 * testDeleteCache method
 *
 * @return void
 */
	public function testDeleteCache() {
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test', $data, 'redis');
		$this->assertTrue($result);

		$result = Cache::delete('delete_test', 'redis');
		$this->assertTrue($result);
	}

/**
 * testDecrement method
 *
 * @return void
 */
	public function testDecrement() {
		Cache::delete('test_decrement', 'redis');
		$result = Cache::write('test_decrement', 5, 'redis');
		$this->assertTrue($result);

		$result = Cache::decrement('test_decrement', 1, 'redis');
		$this->assertEquals(4, $result);

		$result = Cache::read('test_decrement', 'redis');
		$this->assertEquals(4, $result);

		$result = Cache::decrement('test_decrement', 2, 'redis');
		$this->assertEquals(2, $result);

		$result = Cache::read('test_decrement', 'redis');
		$this->assertEquals(2, $result);
	}

/**
 * testIncrement method
 *
 * @return void
 */
	public function testIncrement() {
		Cache::delete('test_increment', 'redis');
		$result = Cache::increment('test_increment', 1, 'redis');
		$this->assertEquals(1, $result);

		$result = Cache::read('test_increment', 'redis');
		$this->assertEquals(1, $result);

		$result = Cache::increment('test_increment', 2, 'redis');
		$this->assertEquals(3, $result);

		$result = Cache::read('test_increment', 'redis');
		$this->assertEquals(3, $result);
	}

/**
 * test clearing redis.
 *
 * @return void
 */
	public function testClear() {
		Cache::config('redis2', array(
			'engine' => 'Redis',
			'prefix' => 'cake2_',
			'duration' => 3600
		));

		Cache::write('some_value', 'cache1', 'redis');
		$result = Cache::clear(true, 'redis');
		$this->assertTrue($result);
		$this->assertEquals('cache1', Cache::read('some_value', 'redis'));

		Cache::write('some_value', 'cache2', 'redis2');
		$result = Cache::clear(false, 'redis');
		$this->assertTrue($result);
		$this->assertFalse(Cache::read('some_value', 'redis'));
		$this->assertEquals('cache2', Cache::read('some_value', 'redis2'));

		Cache::clear(false, 'redis2');
	}

/**
 * test that a 0 duration can successfully write.
 *
 * @return void
 */
	public function testZeroDuration() {
		Cache::set(['duration' => 0], 'redis');
		$result = Cache::write('test_key', 'written!', 'redis');

		$this->assertTrue($result);
		$result = Cache::read('test_key', 'redis');
		$this->assertEquals('written!', $result);
	}

/**
 * Tests that configuring groups for stored keys return the correct values when read/written
 * Shows that altering the group value is equivalent to deleting all keys under the same
 * group
 *
 * @return void
 */
	public function testGroupReadWrite() {
		Cache::config('redis_groups', [
			'engine' => 'Redis',
			'duration' => 3600,
			'groups' => ['group_a', 'group_b'],
			'prefix' => 'test_'
		]);
		Cache::config('redis_helper', [
			'engine' => 'Redis',
			'duration' => 3600,
			'prefix' => 'test_'
		]);
		$this->assertTrue(Cache::write('test_groups', 'value', 'redis_groups'));
		$this->assertEquals('value', Cache::read('test_groups', 'redis_groups'));

		Cache::increment('group_a', 1, 'redis_helper');
		$this->assertFalse(Cache::read('test_groups', 'redis_groups'));
		$this->assertTrue(Cache::write('test_groups', 'value2', 'redis_groups'));
		$this->assertEquals('value2', Cache::read('test_groups', 'redis_groups'));

		Cache::increment('group_b', 1, 'redis_helper');
		$this->assertFalse(Cache::read('test_groups', 'redis_groups'));
		$this->assertTrue(Cache::write('test_groups', 'value3', 'redis_groups'));
		$this->assertEquals('value3', Cache::read('test_groups', 'redis_groups'));
	}

/**
 * Tests that deleteing from a groups-enabled config is possible
 *
 * @return void
 */
	public function testGroupDelete() {
		Cache::config('redis_groups', [
			'engine' => 'Redis',
			'duration' => 3600,
			'groups' => ['group_a', 'group_b']
		]);
		$this->assertTrue(Cache::write('test_groups', 'value', 'redis_groups'));
		$this->assertEquals('value', Cache::read('test_groups', 'redis_groups'));
		$this->assertTrue(Cache::delete('test_groups', 'redis_groups'));

		$this->assertFalse(Cache::read('test_groups', 'redis_groups'));
	}

/**
 * Test clearing a cache group
 *
 * @return void
 */
	public function testGroupClear() {
		Cache::config('redis_groups', [
			'engine' => 'Redis',
			'duration' => 3600,
			'groups' => ['group_a', 'group_b']
		]);

		$this->assertTrue(Cache::write('test_groups', 'value', 'redis_groups'));
		$this->assertTrue(Cache::clearGroup('group_a', 'redis_groups'));
		$this->assertFalse(Cache::read('test_groups', 'redis_groups'));

		$this->assertTrue(Cache::write('test_groups', 'value2', 'redis_groups'));
		$this->assertTrue(Cache::clearGroup('group_b', 'redis_groups'));
		$this->assertFalse(Cache::read('test_groups', 'redis_groups'));
	}

}
