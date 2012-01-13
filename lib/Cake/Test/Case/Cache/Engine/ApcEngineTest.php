<?php
/**
 * ApcEngineTest file
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
 * ApcEngineTest class
 *
 * @package       Cake.Test.Case.Cache.Engine
 */
class ApcEngineTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		$this->skipIf(!function_exists('apc_store'), 'Apc is not installed or configured properly.');

		$this->_cacheDisable = Configure::read('Cache.disable');
		Configure::write('Cache.disable', false);
		Cache::config('apc', array('engine' => 'Apc', 'prefix' => 'cake_'));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		Configure::write('Cache.disable', $this->_cacheDisable);
		Cache::drop('apc');
		Cache::config('default');
	}

/**
 * testReadAndWriteCache method
 *
 * @return void
 */
	public function testReadAndWriteCache() {
		Cache::set(array('duration' => 1), 'apc');

		$result = Cache::read('test', 'apc');
		$expecting = '';
		$this->assertEquals($result, $expecting);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data, 'apc');
		$this->assertTrue($result);

		$result = Cache::read('test', 'apc');
		$expecting = $data;
		$this->assertEquals($result, $expecting);

		Cache::delete('test', 'apc');
	}

/**
 * Writing cache entries with duration = 0 (forever) should work.
 *
 * @return void
 */
	function testReadWriteDurationZero() {
		Cache::config('apc', array('engine' => 'Apc', 'duration' => 0, 'prefix' => 'cake_'));
		Cache::write('zero', 'Should save', 'apc');
		sleep(1);

		$result = Cache::read('zero', 'apc');
		$this->assertEquals('Should save', $result);
	}

/**
 * testExpiry method
 *
 * @return void
 */
	public function testExpiry() {
		Cache::set(array('duration' => 1), 'apc');

		$result = Cache::read('test', 'apc');
		$this->assertFalse($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data, 'apc');
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test', 'apc');
		$this->assertFalse($result);

		Cache::set(array('duration' =>  1), 'apc');

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data, 'apc');
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test', 'apc');
		$this->assertFalse($result);

		sleep(2);
		$result = Cache::read('other_test', 'apc');
		$this->assertFalse($result);
	}

/**
 * testDeleteCache method
 *
 * @return void
 */
	public function testDeleteCache() {
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test', $data, 'apc');
		$this->assertTrue($result);

		$result = Cache::delete('delete_test', 'apc');
		$this->assertTrue($result);
	}

/**
 * testDecrement method
 *
 * @return void
 */
	public function testDecrement() {
		$this->skipIf(!function_exists('apc_dec'), 'No apc_dec() function, cannot test decrement().');

		$result = Cache::write('test_decrement', 5, 'apc');
		$this->assertTrue($result);

		$result = Cache::decrement('test_decrement', 1, 'apc');
		$this->assertEquals(4, $result);

		$result = Cache::read('test_decrement', 'apc');
		$this->assertEquals(4, $result);

		$result = Cache::decrement('test_decrement', 2, 'apc');
		$this->assertEquals(2, $result);

		$result = Cache::read('test_decrement', 'apc');
		$this->assertEquals(2, $result);

	}

/**
 * testIncrement method
 *
 * @return void
 */
	public function testIncrement() {
		$this->skipIf(!function_exists('apc_inc'), 'No apc_inc() function, cannot test increment().');

		$result = Cache::write('test_increment', 5, 'apc');
		$this->assertTrue($result);

		$result = Cache::increment('test_increment', 1, 'apc');
		$this->assertEquals(6, $result);

		$result = Cache::read('test_increment', 'apc');
		$this->assertEquals(6, $result);

		$result = Cache::increment('test_increment', 2, 'apc');
		$this->assertEquals(8, $result);

		$result = Cache::read('test_increment', 'apc');
		$this->assertEquals(8, $result);
	}

/**
 * test the clearing of cache keys
 *
 * @return void
 */
	public function testClear() {
		apc_store('not_cake', 'survive');
		Cache::write('some_value', 'value', 'apc');

		$result = Cache::clear(false, 'apc');
		$this->assertTrue($result);
		$this->assertFalse(Cache::read('some_value', 'apc'));
		$this->assertEquals('survive', apc_fetch('not_cake'));
		apc_delete('not_cake');
	}
}
