<?php
/**
 * ApcEngineTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.cache
 * @since         CakePHP(tm) v 1.2.0.5434
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!class_exists('Cache')) {
	require LIBS . 'cache.php';
}

/**
 * ApcEngineTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.cache
 */
class ApcEngineTest extends CakeTestCase {

/**
 * skip method
 *
 * @access public
 * @return void
 */
	function skip() {
		$skip = true;
		if (function_exists('apc_store')) {
			$skip = false;
		}
		$this->skipIf($skip, '%s Apc is not installed or configured properly');
	}

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
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
		Cache::set(array('duration' => 1));

		$result = Cache::read('test');
		$expecting = '';
		$this->assertEqual($result, $expecting);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data);
		$this->assertTrue($result);

		$result = Cache::read('test');
		$expecting = $data;
		$this->assertEqual($result, $expecting);

		Cache::delete('test');
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
		$this->assertEqual('Should save', $result);
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

		Cache::set(array('duration' =>  1));

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data);
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test');
		$this->assertFalse($result);

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
		$result = Cache::write('test_decrement', 5);
		$this->assertTrue($result);

		$result = Cache::decrement('test_decrement');
		$this->assertEqual(4, $result);

		$result = Cache::read('test_decrement');
		$this->assertEqual(4, $result);

		$result = Cache::decrement('test_decrement', 2);
		$this->assertEqual(2, $result);

		$result = Cache::read('test_decrement');
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
		$result = Cache::write('test_increment', 5);
		$this->assertTrue($result);

		$result = Cache::increment('test_increment');
		$this->assertEqual(6, $result);

		$result = Cache::read('test_increment');
		$this->assertEqual(6, $result);

		$result = Cache::increment('test_increment', 2);
		$this->assertEqual(8, $result);

		$result = Cache::read('test_increment');
		$this->assertEqual(8, $result);
	}
}
