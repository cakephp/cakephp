<?php
/**
 * CacheTest file
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
 * @package       Cake.Test.Case.Cache
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Cache', 'Cache');

/**
 * CacheTest class
 *
 * @package       Cake.Test.Case.Cache
 */
class CacheTest extends CakeTestCase {

	protected $_count = 0;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->_cacheDisable = Configure::read('Cache.disable');
		Configure::write('Cache.disable', false);

		$this->_defaultCacheConfig = Cache::config('default');
		Cache::config('default', array('engine' => 'File', 'path' => TMP . 'tests'));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Cache::drop('latest');
		Cache::drop('page');
		Cache::drop('archive');
		Configure::write('Cache.disable', $this->_cacheDisable);
		Cache::config('default', $this->_defaultCacheConfig['settings']);
	}

/**
 * testConfig method
 *
 * @return void
 */
	public function testConfig() {
		$settings = array('engine' => 'File', 'path' => TMP . 'tests', 'prefix' => 'cake_test_');
		$results = Cache::config('new', $settings);
		$this->assertEquals(Cache::config('new'), $results);
		$this->assertTrue(isset($results['engine']));
		$this->assertTrue(isset($results['settings']));
	}

/**
 * testConfigInvalidEngine method
 *
 * @expectedException CacheException
 * @return void
 */
	public function testConfigInvalidEngine() {
		$settings = array('engine' => 'Imaginary');
		Cache::config('imaginary', $settings);
	}

/**
 * Check that no fatal errors are issued doing normal things when Cache.disable is true.
 *
 * @return void
 */
	public function testNonFatalErrorsWithCachedisable() {
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
	public function testConfigWithLibAndPluginEngines() {
		App::build(array(
			'Lib' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Lib' . DS),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load('TestPlugin');

		$settings = array('engine' => 'TestAppCache', 'path' => TMP, 'prefix' => 'cake_test_');
		$result = Cache::config('libEngine', $settings);
		$this->assertEquals(Cache::config('libEngine'), $result);

		$settings = array('engine' => 'TestPlugin.TestPluginCache', 'path' => TMP, 'prefix' => 'cake_test_');
		$result = Cache::config('pluginLibEngine', $settings);
		$this->assertEquals(Cache::config('pluginLibEngine'), $result);

		Cache::drop('libEngine');
		Cache::drop('pluginLibEngine');

		App::build();
		CakePlugin::unload();
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
		// In debug mode it would auto create the folder.
		$debug = Configure::read('debug');
		Configure::write('debug', 0);

		Cache::config('invalid', array(
			'engine' => 'File',
			'duration' => '+1 year',
			'prefix' => 'testing_invalid_',
			'path' => 'data/',
			'serialize' => true,
			'random' => 'wii'
		));
		Cache::read('Test', 'invalid');

		Configure::write('debug', $debug);
	}

/**
 * Test reading from a config that is undefined.
 *
 * @return void
 */
	public function testReadNonExistingConfig() {
		$this->assertFalse(Cache::read('key', 'totally fake'));
		$this->assertFalse(Cache::write('key', 'value', 'totally fake'));
		$this->assertFalse(Cache::increment('key', 1, 'totally fake'));
		$this->assertFalse(Cache::decrement('key', 1, 'totally fake'));
	}

/**
 * test that trying to configure classes that don't extend CacheEngine fail.
 *
 * @expectedException CacheException
 * @return void
 */
	public function testAttemptingToConfigureANonCacheEngineClass() {
		$this->getMock('StdClass', array(), array(), 'RubbishEngine');
		Cache::config('Garbage', array(
			'engine' => 'Rubbish'
		));
	}

/**
 * testConfigChange method
 *
 * @return void
 */
	public function testConfigChange() {
		$_cacheConfigSessions = Cache::config('sessions');
		$_cacheConfigTests = Cache::config('tests');

		$result = Cache::config('sessions', array('engine' => 'File', 'path' => TMP . 'sessions'));
		$this->assertEquals(Cache::settings('sessions'), $result['settings']);

		$result = Cache::config('tests', array('engine' => 'File', 'path' => TMP . 'tests'));
		$this->assertEquals(Cache::settings('tests'), $result['settings']);

		Cache::config('sessions', $_cacheConfigSessions['settings']);
		Cache::config('tests', $_cacheConfigTests['settings']);
	}

/**
 * test that calling config() sets the 'default' configuration up.
 *
 * @return void
 */
	public function testConfigSettingDefaultConfigKey() {
		Cache::config('test_name', array('engine' => 'File', 'prefix' => 'test_name_'));

		Cache::write('value_one', 'I am cached', 'test_name');
		$result = Cache::read('value_one', 'test_name');
		$this->assertEquals('I am cached', $result);

		$result = Cache::read('value_one');
		$this->assertEquals(null, $result);

		Cache::write('value_one', 'I am in default config!');
		$result = Cache::read('value_one');
		$this->assertEquals('I am in default config!', $result);

		$result = Cache::read('value_one', 'test_name');
		$this->assertEquals('I am cached', $result);

		Cache::delete('value_one', 'test_name');
		Cache::delete('value_one', 'default');
	}

/**
 * testWritingWithConfig method
 *
 * @return void
 */
	public function testWritingWithConfig() {
		$_cacheConfigSessions = Cache::config('sessions');

		Cache::write('test_something', 'this is the test data', 'tests');

		$expected = array(
			'path' => TMP . 'sessions' . DS,
			'prefix' => 'cake_',
			'lock' => true,
			'serialize' => true,
			'duration' => 3600,
			'probability' => 100,
			'engine' => 'File',
			'isWindows' => DIRECTORY_SEPARATOR === '\\',
			'mask' => 0664,
			'groups' => array()
		);
		$this->assertEquals($expected, Cache::settings('sessions'));

		Cache::config('sessions', $_cacheConfigSessions['settings']);
	}

/**
 * testGroupConfigs method
 *
 * @return void
 */
	public function testGroupConfigs() {
		Cache::config('latest', array(
			'duration' => 300,
			'engine' => 'File',
			'groups' => array(
				'posts', 'comments',
			),
		));

		$expected = array(
			'posts' => array('latest'),
			'comments' => array('latest'),
		);
		$result = Cache::groupConfigs();
		$this->assertEquals($expected, $result);

		$result = Cache::groupConfigs('posts');
		$this->assertEquals(array('posts' => array('latest')), $result);

		Cache::config('page', array(
			'duration' => 86400,
			'engine' => 'File',
			'groups' => array(
				'posts', 'archive'
			),
		));

		$result = Cache::groupConfigs();
		$expected = array(
			'posts' => array('latest', 'page'),
			'comments' => array('latest'),
			'archive' => array('page'),
		);
		$this->assertEquals($expected, $result);

		$result = Cache::groupConfigs('archive');
		$this->assertEquals(array('archive' => array('page')), $result);

		Cache::config('archive', array(
			'duration' => 86400 * 30,
			'engine' => 'File',
			'groups' => array(
				'posts', 'archive', 'comments',
			),
		));

		$result = Cache::groupConfigs('archive');
		$this->assertEquals(array('archive' => array('archive', 'page')), $result);
	}

/**
 * testGroupConfigsThrowsException method
 *
 * @expectedException CacheException
 * @return void
 */
	public function testGroupConfigsThrowsException() {
		Cache::groupConfigs('bogus');
	}

/**
 * test that configured returns an array of the currently configured cache
 * settings
 *
 * @return void
 */
	public function testConfigured() {
		$result = Cache::configured();
		$this->assertTrue(in_array('_cake_core_', $result));
		$this->assertTrue(in_array('default', $result));
	}

/**
 * testInitSettings method
 *
 * @return void
 */
	public function testInitSettings() {
		$initial = Cache::settings();
		$override = array('engine' => 'File', 'path' => TMP . 'tests');
		Cache::config('for_test', $override);

		$settings = Cache::settings();
		$expecting = $override + $initial;
		$this->assertEquals($settings, $expecting);
	}

/**
 * test that drop removes cache configs, and that further attempts to use that config
 * do not work.
 *
 * @return void
 */
	public function testDrop() {
		App::build(array(
			'Lib' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Lib' . DS),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);

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
 * @return void
 */
	public function testWriteEmptyValues() {
		Cache::write('App.falseTest', false);
		$this->assertFalse(Cache::read('App.falseTest'));

		Cache::write('App.trueTest', true);
		$this->assertTrue(Cache::read('App.trueTest'));

		Cache::write('App.nullTest', null);
		$this->assertNull(Cache::read('App.nullTest'));

		Cache::write('App.zeroTest', 0);
		$this->assertSame(Cache::read('App.zeroTest'), 0);

		Cache::write('App.zeroTest2', '0');
		$this->assertSame(Cache::read('App.zeroTest2'), '0');
	}

/**
 * Test that failed writes cause errors to be triggered.
 *
 * @return void
 */
	public function testWriteTriggerError() {
		App::build(array(
			'Lib' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Lib' . DS),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);

		Cache::config('test_trigger', array('engine' => 'TestAppCache', 'prefix' => ''));
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
 * @return void
 */
	public function testCacheDisable() {
		Configure::write('Cache.disable', false);
		Cache::config('test_cache_disable_1', array('engine' => 'File', 'path' => TMP . 'tests'));

		$this->assertTrue(Cache::write('key_1', 'hello', 'test_cache_disable_1'));
		$this->assertSame(Cache::read('key_1', 'test_cache_disable_1'), 'hello');

		Configure::write('Cache.disable', true);

		$this->assertFalse(Cache::write('key_2', 'hello', 'test_cache_disable_1'));
		$this->assertFalse(Cache::read('key_2', 'test_cache_disable_1'));

		Configure::write('Cache.disable', false);

		$this->assertTrue(Cache::write('key_3', 'hello', 'test_cache_disable_1'));
		$this->assertSame(Cache::read('key_3', 'test_cache_disable_1'), 'hello');

		Configure::write('Cache.disable', true);
		Cache::config('test_cache_disable_2', array('engine' => 'File', 'path' => TMP . 'tests'));

		$this->assertFalse(Cache::write('key_4', 'hello', 'test_cache_disable_2'));
		$this->assertFalse(Cache::read('key_4', 'test_cache_disable_2'));

		Configure::write('Cache.disable', false);

		$this->assertTrue(Cache::write('key_5', 'hello', 'test_cache_disable_2'));
		$this->assertSame(Cache::read('key_5', 'test_cache_disable_2'), 'hello');

		Configure::write('Cache.disable', true);

		$this->assertFalse(Cache::write('key_6', 'hello', 'test_cache_disable_2'));
		$this->assertFalse(Cache::read('key_6', 'test_cache_disable_2'));
	}

/**
 * testSet method
 *
 * @return void
 */
	public function testSet() {
		$_cacheSet = Cache::set();

		Cache::set(array('duration' => '+1 year'));
		$data = Cache::read('test_cache');
		$this->assertFalse($data);

		$data = 'this is just a simple test of the cache system';
		$write = Cache::write('test_cache', $data);
		$this->assertTrue($write);

		Cache::set(array('duration' => '+1 year'));
		$data = Cache::read('test_cache');
		$this->assertEquals('this is just a simple test of the cache system', $data);

		Cache::delete('test_cache');

		Cache::settings();

		Cache::set($_cacheSet);
	}

/**
 * test set() parameter handling for user cache configs.
 *
 * @return void
 */
	public function testSetOnAlternateConfigs() {
		Cache::config('file_config', array('engine' => 'File', 'prefix' => 'test_file_'));
		Cache::set(array('duration' => '+1 year'), 'file_config');
		$settings = Cache::settings('file_config');

		$this->assertEquals('test_file_', $settings['prefix']);
		$this->assertEquals(strtotime('+1 year') - time(), $settings['duration']);
	}

/**
 * test remember method.
 *
 * @return void
 */
	public function testRemember() {
		$expected = 'This is some data 0';
		$result = Cache::remember('test_key', array($this, 'cacher'), 'default');
		$this->assertEquals($expected, $result);

		$this->_count = 1;
		$result = Cache::remember('test_key', array($this, 'cacher'), 'default');
		$this->assertEquals($expected, $result);
	}

/**
 * Method for testing Cache::remember()
 *
 * @return string
 */
	public function cacher() {
		return 'This is some data ' . $this->_count;
	}
}
