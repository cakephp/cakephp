<?php
/**
 * CacheTest file
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
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!class_exists('Cache')) {
	require LIBS . 'cache.php';
}

/**
 * CacheTest class
 *
 * @package       cake.tests.cases.libs
 */
class CacheTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->_cacheDisable = Configure::read('Cache.disable');
		Configure::write('Cache.disable', false);

		$this->_defaultCacheConfig = Cache::config('default');
		Cache::config('default', array('engine' => 'File', 'path' => TMP . 'tests'));
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		Configure::write('Cache.disable', $this->_cacheDisable);
		Cache::config('default', $this->_defaultCacheConfig['settings']);
	}

/**
 * testConfig method
 *
 * @access public
 * @return void
 */
	function testConfig() {
		$settings = array('engine' => 'File', 'path' => TMP . 'tests', 'prefix' => 'cake_test_');
		$results = Cache::config('new', $settings);
		$this->assertEqual($results, Cache::config('new'));
		$this->assertTrue(isset($results['engine']));
		$this->assertTrue(isset($results['settings']));
	}

/**
 * Check that no fatal errors are issued doing normal things when Cache.disable is true.
 *
 * @return void
 */
	function testNonFatalErrorsWithCachedisable() {
		Configure::write('Cache.disable', true);
		Cache::config('test', array('engine' => 'File', 'path' => TMP, 'prefix' => 'error_test_'));

		Cache::write('no_save', 'Noooo!', 'test');
		Cache::read('no_save', 'test');
		Cache::delete('no_save', 'test');
		Cache::set('duration', '+10 minutes');

		Configure::write('Cache.disable', false);
	}

/**
 * test configuring CacheEngines in App/libs
 *
 * @return void
 */
	function testConfigWithLibAndPluginEngines() {
		App::build(array(
			'libs' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'libs' . DS),
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		), true);

		$settings = array('engine' => 'TestAppCache', 'path' => TMP, 'prefix' => 'cake_test_');
		$result = Cache::config('libEngine', $settings);
		$this->assertEqual($result, Cache::config('libEngine'));

		$settings = array('engine' => 'TestPlugin.TestPluginCache', 'path' => TMP, 'prefix' => 'cake_test_');
		$result = Cache::config('pluginLibEngine', $settings);
		$this->assertEqual($result, Cache::config('pluginLibEngine'));

		Cache::drop('libEngine');
		Cache::drop('pluginLibEngine');

		App::build();
	}

/**
 * testInvalidConfig method
 *
 * Test that the cache class doesn't cause fatal errors with a partial path
 *
 * @access public
 * @return void
 */
	function testInvaidConfig() {
		$this->expectError();
		Cache::config('invalid', array(
			'engine' => 'File',
			'duration' => '+1 year',
			'prefix' => 'testing_invalid_',
			'path' => 'data/',
			'serialize' => true,
			'random' => 'wii'
		));
		$read = Cache::read('Test', 'invalid');
		$this->assertEqual($read, null);
	}

/**
 * test that trying to configure classes that don't extend CacheEngine fail.
 *
 * @return void
 */
	function testAttemptingToConfigureANonCacheEngineClass() {
		$this->getMock('StdClass', array(), array(), 'RubbishEngine');
		$this->expectException();
		Cache::config('Garbage', array(
			'engine' => 'Rubbish'
		));
	}

/**
 * testConfigChange method
 *
 * @access public
 * @return void
 */
	function testConfigChange() {
		$_cacheConfigSessions = Cache::config('sessions');
		$_cacheConfigTests = Cache::config('tests');

		$result = Cache::config('sessions', array('engine'=> 'File', 'path' => TMP . 'sessions'));
		$this->assertEqual($result['settings'], Cache::settings('sessions'));

		$result = Cache::config('tests', array('engine'=> 'File', 'path' => TMP . 'tests'));
		$this->assertEqual($result['settings'], Cache::settings('tests'));

		Cache::config('sessions', $_cacheConfigSessions['settings']);
		Cache::config('tests', $_cacheConfigTests['settings']);
	}

/**
 * test that calling config() sets the 'default' configuration up.
 *
 * @return void
 */
	function testConfigSettingDefaultConfigKey() {
		Cache::config('test_name', array('engine' => 'File', 'prefix' => 'test_name_'));

		Cache::write('value_one', 'I am cached', 'test_name');
		$result = Cache::read('value_one', 'test_name');
		$this->assertEqual($result, 'I am cached');

		$result = Cache::read('value_one');
		$this->assertEqual($result, null);

		Cache::write('value_one', 'I am in default config!');
		$result = Cache::read('value_one');
		$this->assertEqual($result, 'I am in default config!');

		$result = Cache::read('value_one', 'test_name');
		$this->assertEqual($result, 'I am cached');

		Cache::delete('value_one', 'test_name');
		Cache::delete('value_one', 'default');
	}

/**
 * testWritingWithConfig method
 *
 * @access public
 * @return void
 */
	function testWritingWithConfig() {
		$_cacheConfigSessions = Cache::config('sessions');

		Cache::write('test_somthing', 'this is the test data', 'tests');

		$expected = array(
			'path' => TMP . 'sessions' . DS,
			'prefix' => 'cake_',
			'lock' => false,
			'serialize' => true,
			'duration' => 3600,
			'probability' => 100,
			'engine' => 'File',
			'isWindows' => DIRECTORY_SEPARATOR == '\\'
		);
		$this->assertEqual($expected, Cache::settings('sessions'));

		Cache::config('sessions', $_cacheConfigSessions['settings']);
	}

/**
 * test that configured returns an array of the currently configured cache
 * settings
 *
 * @return void
 */
	function testConfigured() {
		$result = Cache::configured();
		$this->assertTrue(in_array('_cake_core_', $result));
		$this->assertTrue(in_array('default', $result));
	}

/**
 * testInitSettings method
 *
 * @access public
 * @return void
 */
	function testInitSettings() {
		Cache::config('default', array('engine' => 'File', 'path' => TMP . 'tests'));

		$settings = Cache::settings();
		$expecting = array(
			'engine' => 'File',
			'duration'=> 3600,
			'probability' => 100,
			'path'=> TMP . 'tests',
			'prefix'=> 'cake_',
			'lock' => false,
			'serialize'=> true,
			'isWindows' => DIRECTORY_SEPARATOR == '\\'
		);
		$this->assertEqual($settings, $expecting);
	}

/**
 * test that drop removes cache configs, and that further attempts to use that config
 * do not work.
 *
 * @return void
 */
	function testDrop() {
		App::build(array(
			'libs' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'libs' . DS),
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		), true);

		$result = Cache::drop('some_config_that_does_not_exist');
		$this->assertFalse($result);

		$_testsConfig = Cache::config('tests');
		$result = Cache::drop('tests');
		$this->assertTrue($result);

		Cache::config('unconfigTest', array(
			'engine' => 'TestAppCache'
		));
		$this->assertTrue(Cache::isInitialized('unconfigTest'));

		$this->assertTrue(Cache::drop('unconfigTest'));
		$this->assertFalse(Cache::isInitialized('TestAppCache'));

		Cache::config('tests', $_testsConfig);
		App::build();
	}

/**
 * testWriteEmptyValues method
 *
 * @access public
 * @return void
 */
	function testWriteEmptyValues() {
		Cache::write('App.falseTest', false);
		$this->assertIdentical(Cache::read('App.falseTest'), false);

		Cache::write('App.trueTest', true);
		$this->assertIdentical(Cache::read('App.trueTest'), true);

		Cache::write('App.nullTest', null);
		$this->assertIdentical(Cache::read('App.nullTest'), null);

		Cache::write('App.zeroTest', 0);
		$this->assertIdentical(Cache::read('App.zeroTest'), 0);

		Cache::write('App.zeroTest2', '0');
		$this->assertIdentical(Cache::read('App.zeroTest2'), '0');
	}

/**
 * Test that failed writes cause errors to be triggered.
 *
 * @return void
 */
	function testWriteTriggerError() {
		App::build(array(
			'libs' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'libs' . DS),
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		), true);
	
		Cache::config('test_trigger', array('engine' => 'TestAppCache'));
		try {
			Cache::write('fail', 'value', 'test_trigger');
			$this->fail('No exception thrown');
		} catch (PHPUnit_Framework_Error $e) {
			$this->assertTrue(true);
		}
		Cache::drop('test_trigger');
		App::build();
	}

/**
 * testCacheDisable method
 *
 * Check that the "Cache.disable" configuration and a change to it
 * (even after a cache config has been setup) is taken into account.
 *
 * @link https://trac.cakephp.org/ticket/6236
 * @access public
 * @return void
 */
	function testCacheDisable() {
		Configure::write('Cache.disable', false);
		Cache::config('test_cache_disable_1', array('engine'=> 'File', 'path' => TMP . 'tests'));

		$this->assertTrue(Cache::write('key_1', 'hello', 'test_cache_disable_1'));
		$this->assertIdentical(Cache::read('key_1', 'test_cache_disable_1'), 'hello');

		Configure::write('Cache.disable', true);

		$this->assertFalse(Cache::write('key_2', 'hello', 'test_cache_disable_1'));
		$this->assertFalse(Cache::read('key_2', 'test_cache_disable_1'));

		Configure::write('Cache.disable', false);

		$this->assertTrue(Cache::write('key_3', 'hello', 'test_cache_disable_1'));
		$this->assertIdentical(Cache::read('key_3', 'test_cache_disable_1'), 'hello');

		Configure::write('Cache.disable', true);
		Cache::config('test_cache_disable_2', array('engine'=> 'File', 'path' => TMP . 'tests'));

		$this->assertFalse(Cache::write('key_4', 'hello', 'test_cache_disable_2'));
		$this->assertFalse(Cache::read('key_4', 'test_cache_disable_2'));

		Configure::write('Cache.disable', false);

		$this->assertTrue(Cache::write('key_5', 'hello', 'test_cache_disable_2'));
		$this->assertIdentical(Cache::read('key_5', 'test_cache_disable_2'), 'hello');

		Configure::write('Cache.disable', true);

		$this->assertFalse(Cache::write('key_6', 'hello', 'test_cache_disable_2'));
		$this->assertFalse(Cache::read('key_6', 'test_cache_disable_2'));
	}

/**
 * testSet method
 *
 * @access public
 * @return void
 */
	function testSet() {
		$_cacheSet = Cache::set();

		Cache::set(array('duration' => '+1 year'));
		$data = Cache::read('test_cache');
		$this->assertFalse($data);

		$data = 'this is just a simple test of the cache system';
		$write = Cache::write('test_cache', $data);
		$this->assertTrue($write);

		Cache::set(array('duration' => '+1 year'));
		$data = Cache::read('test_cache');
		$this->assertEqual($data, 'this is just a simple test of the cache system');

		Cache::delete('test_cache');

		$global = Cache::settings();

		Cache::set($_cacheSet);
	}

/**
 * test set() parameter handling for user cache configs.
 *
 * @return void
 */
	function testSetOnAlternateConfigs() {
		Cache::config('file_config', array('engine' => 'File', 'prefix' => 'test_file_'));
		Cache::set(array('duration' => '+1 year'), 'file_config');
		$settings = Cache::settings('file_config');
		
		$this->assertEquals('test_file_', $settings['prefix']);
		$this->assertEquals(31536000, $settings['duration']);
	}
}
