<?php
/* SVN FILE: $Id$ */
/**
 * MemcacheEngineTest file
 *
 * Long description for file
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
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!class_exists('Cache')) {
	require LIBS . 'cache.php';
}
/**
 * MemcacheEngineTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.cache
 */
/**
 * MemcacheEngineTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.cache
 */
class MemcacheEngineTest extends CakeTestCase {
/**
 * skip method
 *
 * @access public
 * @return void
 */
	function skip() {
		$skip = true;
		if (Cache::engine('Memcache')) {
			$skip = false;
		}
		$this->skipIf($skip, '%s Memcache is not installed or configured properly');
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
		Cache::config('default');
	}
/**
 * testSettings method
 *
 * @access public
 * @return void
 */
	function testSettings() {
		$settings = Cache::settings();
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

		$Cache =& Cache::getInstance();
		$MemCache =& $Cache->_Engine['Memcache'];

		$available = true;
		foreach($servers as $server) {
			list($host, $port) = explode(':', $server);
			if (!@$MemCache->__Memcache->connect($host, $port)) {
				$available = false;
			}
		}

		if ($this->skipIf(!$available, '%s Need memcache servers at ' . implode(', ', $servers) . ' to run this test')) {
			return;
		}

		unset($MemCache->__Memcache);
		$MemCache->init(array('engine' => 'Memcache', 'servers' => $servers));

		$servers = array_keys($MemCache->__Memcache->getExtendedStats());
		$settings = Cache::settings();
		$this->assertEqual($servers, $settings['servers']);
	}
/**
 * testConnect method
 *
 * @access public
 * @return void
 */
	function testConnect() {
		$Cache =& Cache::getInstance();
		$result = $Cache->_Engine['Memcache']->connect('127.0.0.1');
		$this->assertTrue($result);
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

		Cache::engine('Memcache', array('duration' => '+1 second'));
		sleep(2);

		$result = Cache::read('other_test');
		$this->assertFalse($result);

		Cache::engine('Memcache', array('duration' => '+31 day'));
		$data = 'this is a test of the emergency broadcasting system';
		$result = Cache::write('long_expiry_test', $data);
		$this->assertTrue($result);

		sleep(2);
		$result = Cache::read('long_expiry_test');
		$expecting = $data;
		$this->assertEqual($result, $expecting);

		$result = Cache::read('long_expiry_test');
		$this->assertTrue($result);

		Cache::engine('Memcache', array('duration' => 3600));
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

}
?>