<?php
/**
 * MemcacheEngineTest file
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
require_once LIBS . 'cache' . DS . 'memcache.php';

class TestMemcacheEngine extends MemcacheEngine {
/**
 * public accessor to _parseServerString
 *
 * @param string $server 
 * @return array
 */
	function parseServerString($server) {
		return $this->_parseServerString($server);
	}
}

/**
 * MemcacheEngineTest class
 *
 * @package       cake.tests.cases.libs.cache
 */
class MemcacheEngineTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->skipIf(!class_exists('Memcache'), '%s Apc is not installed or configured properly');
		$this->_cacheDisable = Configure::read('Cache.disable');
		Configure::write('Cache.disable', false);
		Cache::config('memcache', array(
			'engine' => 'Memcache',
			'prefix' => 'cake_',
			'duration' => 3600
		));
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		Configure::write('Cache.disable', $this->_cacheDisable);
		Cache::drop('memcache');
		Cache::config('default');
	}

/**
 * testSettings method
 *
 * @access public
 * @return void
 */
	function testSettings() {
		$settings = Cache::settings('memcache');
		unset($settings['serialize'], $settings['path']);
		$expecting = array(
			'prefix' => 'cake_',
			'duration'=> 3600,
			'probability' => 100,
			'servers' => array('127.0.0.1'),
			'compress' => false,
			'engine' => 'Memcache'
		);
		$this->assertEqual($settings, $expecting);
	}

/**
 * testSettings method
 *
 * @access public
 * @return void
 */
	function testMultipleServers() {
		$servers = array('127.0.0.1:11211', '127.0.0.1:11222');
		$available = true;
		$Memcache = new Memcache();

		foreach($servers as $server) {
			list($host, $port) = explode(':', $server);
			if (!@$Memcache->connect($host, $port)) {
				$available = false;
			}
		}

		if ($this->skipIf(!$available, '%s Need memcache servers at ' . implode(', ', $servers) . ' to run this test')) {
			return;
		}
		$Memcache = new MemcacheEngine();
		$Memcache->init(array('engine' => 'Memcache', 'servers' => $servers));

		$servers = array_keys($Memcache->__Memcache->getExtendedStats());
		$settings = $Memcache->settings();
		$this->assertEqual($servers, $settings['servers']);
		Cache::drop('dual_server');
	}

/**
 * testConnect method
 *
 * @access public
 * @return void
 */
	function testConnect() {
		$Memcache = new MemcacheEngine();
		$Memcache->init(Cache::settings('memcache'));
		$result = $Memcache->connect('127.0.0.1');
		$this->assertTrue($result);
	}

/**
 * test connecting to an ipv6 server.
 *
 * @return void
 */
	function testConnectIpv6() {
		$Memcache = new MemcacheEngine();
		$result = $Memcache->init(array(
			'prefix' => 'cake_',
			'duration' => 200,
			'engine' => 'Memcache',
			'servers' => array(
				'[::1]:11211'
			)
		));
		$this->assertTrue($result);
	}

/**
 * test non latin domains.
 *
 * @return void
 */
	function testParseServerStringNonLatin() {
		$Memcache = new TestMemcacheEngine();
		$result = $Memcache->parseServerString('schülervz.net:13211');
		$this->assertEqual($result, array('schülervz.net', '13211'));

		$result = $Memcache->parseServerString('sülül:1111');
		$this->assertEqual($result, array('sülül', '1111'));
	}

/**
 * testReadAndWriteCache method
 *
 * @access public
 * @return void
 */
	function testReadAndWriteCache() {
		Cache::set(array('duration' => 1), null, 'memcache');

		$result = Cache::read('test', 'memcache');
		$expecting = '';
		$this->assertEqual($result, $expecting);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('test', $data, 'memcache');
		$this->assertTrue($result);

		$result = Cache::read('test', 'memcache');
		$expecting = $data;
		$this->assertEqual($result, $expecting);

		Cache::delete('test', 'memcache');
	}

/**
 * testExpiry method
 *
 * @access public
 * @return void
 */
	function testExpiry() {
		Cache::set(array('duration' => 1), 'memcache');

		$result = Cache::read('test', 'memcache');
		$this->assertFalse($result);

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data, 'memcache');
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test', 'memcache');
		$this->assertFalse($result);

		Cache::set(array('duration' =>  "+1 second"), 'memcache');

		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('other_test', $data, 'memcache');
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('other_test', 'memcache');
		$this->assertFalse($result);

		Cache::config('memcache', array('duration' => '+1 second'));
		sleep(2);

		$result = Cache::read('other_test', 'memcache');
		$this->assertFalse($result);

		Cache::config('memcache', array('duration' => '+29 days'));
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('long_expiry_test', $data, 'memcache');
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('long_expiry_test', 'memcache');
		$expecting = $data;
		$this->assertEqual($result, $expecting);

		Cache::config('memcache', array('duration' => 3600));
	}

/**
 * testDeleteCache method
 *
 * @access public
 * @return void
 */
	function testDeleteCache() {
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('delete_test', $data, 'memcache');
		$this->assertTrue($result);

		$result = Cache::delete('delete_test', 'memcache');
		$this->assertTrue($result);
	}

/**
 * testDecrement method
 *
 * @access public
 * @return void
 */
	function testDecrement() {
		$result = Cache::write('test_decrement', 5, 'memcache');
		$this->assertTrue($result);

		$result = Cache::decrement('test_decrement', 1, 'memcache');
		$this->assertEqual(4, $result);

		$result = Cache::read('test_decrement', 'memcache');
		$this->assertEqual(4, $result);

		$result = Cache::decrement('test_decrement', 2, 'memcache');
		$this->assertEqual(2, $result);

		$result = Cache::read('test_decrement', 'memcache');
		$this->assertEqual(2, $result);
	}

/**
 * testIncrement method
 *
 * @access public
 * @return void
 */
	function testIncrement() {
		$result = Cache::write('test_increment', 5, 'memcache');
		$this->assertTrue($result);

		$result = Cache::increment('test_increment', 1, 'memcache');
		$this->assertEqual(6, $result);

		$result = Cache::read('test_increment', 'memcache');
		$this->assertEqual(6, $result);

		$result = Cache::increment('test_increment', 2, 'memcache');
		$this->assertEqual(8, $result);

		$result = Cache::read('test_increment', 'memcache');
		$this->assertEqual(8, $result);
	}

/**
 * test that configurations don't conflict, when a file engine is declared after a memcache one.
 *
 * @return void
 */
	function testConfigurationConflict() {
		Cache::config('long_memcache', array(
		  'engine' => 'Memcache',
		  'duration'=> '+2 seconds',
		  'servers' => array('127.0.0.1:11211'),
		));
		Cache::config('short_memcache', array(
		  'engine' => 'Memcache',
		  'duration'=> '+1 seconds',
		  'servers' => array('127.0.0.1:11211'),
		));
		Cache::config('some_file', array('engine' => 'File'));

		$this->assertTrue(Cache::write('duration_test', 'yay', 'long_memcache'));
		$this->assertTrue(Cache::write('short_duration_test', 'boo', 'short_memcache'));

		$this->assertEqual(Cache::read('duration_test', 'long_memcache'), 'yay', 'Value was not read %s');
		$this->assertEqual(Cache::read('short_duration_test', 'short_memcache'), 'boo', 'Value was not read %s');

		sleep(1);
		$this->assertEqual(Cache::read('duration_test', 'long_memcache'), 'yay', 'Value was not read %s');

		sleep(2);
		$this->assertFalse(Cache::read('short_duration_test', 'short_memcache'), 'Cache was not invalidated %s');
		$this->assertFalse(Cache::read('duration_test', 'long_memcache'), 'Value did not expire %s');

		Cache::delete('duration_test', 'long_memcache');
		Cache::delete('short_duration_test', 'short_memcache');
	}

/**
 * test clearing memcache.
 *
 * @return void
 */
	function testClear() {
		Cache::write('some_value', 'value', 'memcache');

		$result = Cache::clear(false, 'memcache');
		$this->assertTrue($result);
		$this->assertFalse(Cache::read('some_value', 'memcache'));
	}
/**
 * test that a 0 duration can succesfully write.
 *
 * @return void
 */
	function testZeroDuration() {
		Cache::config('memcache', array('duration' => 0));
		$result = Cache::write('test_key', 'written!', 'memcache');

		$this->assertTrue($result, 'Could not write with duration 0');
		$result = Cache::read('test_key', 'memcache');
		$this->assertEqual($result, 'written!');
		
	}

}
