<?php
/**
 * XcacheEngineTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Cache.Engine
 * @since         CakePHP(tm) v 1.2.0.5434
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
		Configure::write('Cache.disable', $this->_cacheDisable);
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
}
