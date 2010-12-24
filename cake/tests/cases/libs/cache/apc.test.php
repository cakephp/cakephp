<?php
/**
 * ApcEngineTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.cache
 * @since         CakePHP(tm) v 1.2.0.5434
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!class_exists('Cache')) {
	require LIBS . 'cache.php';
}

/**
 * ApcEngineTest class
 *
 * @package       cake.tests.cases.libs.cache
 */
class ApcEngineTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->skipIf(!function_exists('apc_store'), '%s Apc is not installed or configured properly');
		$this->_cacheDisable = Configure::read('Cache.disable');
		Configure::write('Cache.disable', false);
		Cache::config('apc', array('engine' => 'Apc', 'prefix' => 'cake_'));
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		Configure::write('Cache.disable', $this->_cacheDisable);
		Cache::drop('apc');
		Cache::config('default');
	}

/**
 * testReadAndWriteCache method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteCache() {
		Cache::set(array('duration' => 1), 'apc');

		$result = Cache::read('test', 'apc');
		$expecting = '';
		$this->assertEqual($result, $expecting);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data, 'apc');
		$this->assertTrue($result);

		$result = Cache::read('test', 'apc');
		$expecting = $data;
		$this->assertEqual($result, $expecting);

		Cache::delete('test', 'apc');
	}

/**
 * testExpiry method
 *
 * @access public
 * @return void
 */
	function testExpiry() {
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
 * @access public
 * @return void
 */
	function testDeleteCache() {
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test', $data, 'apc');
		$this->assertTrue($result);

		$result = Cache::delete('delete_test', 'apc');
		$this->assertTrue($result);
	}

/**
 * testDecrement method
 *
 * @access public
 * @return void
 */
	function testDecrement() {
		if ($this->skipIf(!function_exists('apc_dec'), 'No apc_dec() function, cannot test decrement() %s')) {
			return;
		}
		$result = Cache::write('test_decrement', 5, 'apc');
		$this->assertTrue($result);

		$result = Cache::decrement('test_decrement', 1, 'apc');
		$this->assertEqual(4, $result);

		$result = Cache::read('test_decrement', 'apc');
		$this->assertEqual(4, $result);

		$result = Cache::decrement('test_decrement', 2, 'apc');
		$this->assertEqual(2, $result);

		$result = Cache::read('test_decrement', 'apc');
		$this->assertEqual(2, $result);
		
	}

/**
 * testIncrement method
 *
 * @access public
 * @return void
 */
	function testIncrement() {
		if ($this->skipIf(!function_exists('apc_inc'), 'No apc_inc() function, cannot test increment() %s')) {
			return;
		}
		$result = Cache::write('test_increment', 5, 'apc');
		$this->assertTrue($result);

		$result = Cache::increment('test_increment', 1, 'apc');
		$this->assertEqual(6, $result);

		$result = Cache::read('test_increment', 'apc');
		$this->assertEqual(6, $result);

		$result = Cache::increment('test_increment', 2, 'apc');
		$this->assertEqual(8, $result);

		$result = Cache::read('test_increment', 'apc');
		$this->assertEqual(8, $result);
	}

/**
 * test the clearing of cache keys
 *
 * @return void
 */
	function testClear() {
		Cache::write('some_value', 'value', 'apc');

		$result = Cache::clear(false, 'apc');
		$this->assertTrue($result);
		$this->assertFalse(Cache::read('some_value', 'apc'));
	}
}
