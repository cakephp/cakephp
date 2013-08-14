<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * CacheTest class
 *
 * @package       Cake.Test.Case.Cache
 */
class CacheTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Cache.disable', false);

		Cache::drop('tests');
		Cache::config('default', [
			'engine' => 'File',
			'path' => TMP . 'tests'
		]);
	}

/**
 * testEngine method
 *
 * @return void
 */
	public function testEngine() {
		$settings = [
			'engine' => 'File',
			'path' => TMP . 'tests',
			'prefix' => 'cake_test_'
		];
		Cache::config('tests', $settings);
		$engine = Cache::engine('tests');
		$this->assertInstanceOf('Cake\Cache\Engine\FileEngine', $engine);
	}

/**
 * testConfigInvalidEngine method
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testConfigInvalidEngine() {
		$settings = array('engine' => 'Imaginary');
		Cache::config('tests', $settings);
		Cache::engine('tests');
	}

/**
 * Check that no fatal errors are issued doing normal things when Cache.disable is true.
 *
 * @return void
 */
	public function testNonFatalErrorsWithCachedisable() {
		Configure::write('Cache.disable', true);
		Cache::config('tests', [
			'engine' => 'File',
			'path' => TMP, 'prefix' => 'error_test_'
		]);

		Cache::write('no_save', 'Noooo!', 'tests');
		Cache::read('no_save', 'tests');
		Cache::delete('no_save', 'tests');
		Cache::set('duration', '+10 minutes');
	}

/**
 * test configuring CacheEngines in App/libs
 *
 * @return void
 */
	public function testConfigWithLibAndPluginEngines() {
		App::build(array(
			'Lib' => array(CAKE . 'Test/TestApp/Lib/'),
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		), App::RESET);
		Configure::write('App.namespace', 'TestApp');
		Plugin::load('TestPlugin');

		$settings = ['engine' => 'TestAppCache', 'path' => TMP, 'prefix' => 'cake_test_'];
		Cache::config('libEngine', $settings);
		$engine = Cache::engine('libEngine');
		$this->assertInstanceOf('\TestApp\Cache\Engine\TestAppCacheEngine', $engine);

		$settings = ['engine' => 'TestPlugin.TestPluginCache', 'path' => TMP, 'prefix' => 'cake_test_'];
		$result = Cache::config('pluginLibEngine', $settings);
		$engine = Cache::engine('pluginLibEngine');
		$this->assertInstanceOf('\TestPlugin\Cache\Engine\TestPluginCacheEngine', $engine);

		Cache::drop('libEngine');
		Cache::drop('pluginLibEngine');

		Plugin::unload();
	}

/**
 * Test reading from a config that is undefined.
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testInvalidConfig() {
		// In debug mode it would auto create the folder.
		Configure::write('debug', 0);

		Cache::config('tests', array(
			'engine' => 'File',
			'duration' => '+1 year',
			'prefix' => 'testing_invalid_',
			'path' => 'data/',
			'serialize' => true,
		));
		Cache::read('Test', 'tests');
	}

/**
 * Test write from a config that is undefined.
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testWriteNonExistingConfig() {
		$this->assertFalse(Cache::write('key', 'value', 'totally fake'));
	}

/**
 * Test write from a config that is undefined.
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testIncrementNonExistingConfig() {
		$this->assertFalse(Cache::increment('key', 1, 'totally fake'));
	}

/**
 * Test write from a config that is undefined.
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testDecrementNonExistingConfig() {
		$this->assertFalse(Cache::decrement('key', 1, 'totally fake'));
	}

/**
 * test that trying to configure classes that don't extend CacheEngine fail.
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testAttemptingToConfigureANonCacheEngineClass() {
		$this->getMock('\StdClass', array(), array(), 'RubbishEngine');
		Cache::config('tests', array(
			'engine' => '\RubbishEngine'
		));
		Cache::engine('tests');
	}

/**
 * Test that engine() can be used to inject instances.
 *
 * @return void
 */
	public function testSetEngineValid() {
		$engine = $this->getMockForAbstractClass('\Cake\Cache\CacheEngine');
		Cache::config('test', ['engine' => $engine]);
		$this->assertSame($engine, Cache::engine('test'));
	}

/**
 * test that calling config() sets the 'default' configuration up.
 *
 * @return void
 */
	public function testConfigSettingDefaultConfigKey() {
		Cache::config('tests', [
			'engine' => 'File',
			'prefix' => 'tests_'
		]);

		Cache::write('value_one', 'I am cached', 'tests');
		$result = Cache::read('value_one', 'tests');
		$this->assertEquals('I am cached', $result);

		$result = Cache::read('value_one');
		$this->assertEquals(null, $result);

		Cache::write('value_one', 'I am in default config!');
		$result = Cache::read('value_one');
		$this->assertEquals('I am in default config!', $result);

		$result = Cache::read('value_one', 'tests');
		$this->assertEquals('I am cached', $result);

		Cache::delete('value_one', 'tests');
		Cache::delete('value_one', 'default');
	}

/**
 * testGroupConfigs method
 */
	public function testGroupConfigs() {
		Cache::config('latest', [
			'duration' => 300,
			'engine' => 'File',
			'groups' => ['posts', 'comments'],
		]);

		$expected = [
			'posts' => ['latest'],
			'comments' => ['latest'],
		];
		$engine = Cache::engine('latest');
		$result = Cache::groupConfigs();
		$this->assertEquals($expected, $result);

		$result = Cache::groupConfigs('posts');
		$this->assertEquals(['posts' => ['latest']], $result);

		Cache::config('page', [
			'duration' => 86400,
			'engine' => 'File',
			'groups' => ['posts', 'archive'],
		]);

		$engine = Cache::engine('page');
		$result = Cache::groupConfigs();
		$expected = [
			'posts' => ['latest', 'page'],
			'comments' => ['latest'],
			'archive' => ['page']
		];
		$this->assertEquals($expected, $result);

		$result = Cache::groupConfigs('archive');
		$this->assertEquals(['archive' => ['page']], $result);

		Cache::config('archive', [
			'duration' => 86400 * 30,
			'engine' => 'File',
			'groups' => ['posts', 'archive', 'comments'],
		]);

		$engine = Cache::engine('archive');
		$result = Cache::groupConfigs('archive');
		$this->assertEquals(['archive' => ['archive', 'page']], $result);
	}

/**
 * testGroupConfigsThrowsException method
 * @expectedException Cake\Error\Exception
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
		Cache::drop('default');
		$result = Cache::configured();
		$this->assertContains('_cake_core_', $result);
		$this->assertNotContains('default', $result, 'Unconnected engines should not display.');
	}

/**
 * test that drop removes cache configs, and that further attempts to use that config
 * do not work.
 *
 * @return void
 */
	public function testDrop() {
		App::build(array(
			'Lib' => array(CAKE . 'Test/TestApp/Lib/'),
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		), App::RESET);
		Configure::write('App.namespace', 'TestApp');

		$result = Cache::drop('some_config_that_does_not_exist');
		$this->assertFalse($result);

		Cache::engine('default');
		$result = Cache::drop('default');
		$this->assertTrue($result, 'Built engines should be dropped');

		Cache::config('unconfigTest', [
			'engine' => 'TestAppCache'
		]);
		$this->assertInstanceOf(
			'TestApp\Cache\Engine\TestAppCacheEngine',
			Cache::engine('unconfigTest')
		);

		$this->assertTrue(Cache::drop('unconfigTest'));
	}

/**
 * Test that dropping a cache config refreshes its configuration and
 * creates a new instance.
 *
 * @return void
 */
	public function testDropChangeConfig() {
		Cache::config('tests', [
			'engine' => 'File',
		]);
		$result = Cache::engine('tests');
		$settings = Cache::settings('tests');

		$this->assertEquals(CACHE, $settings['path']);
		$id = spl_object_hash($result);

		Cache::drop('tests');

		Cache::config('tests', [
			'engine' => 'File',
			'extra' => 'value'
		]);
		$result = Cache::engine('tests');
		$this->assertNotEquals($id, spl_object_hash($result));
	}

/**
 * testWriteEmptyValues method
 *
 * @return void
 */
	public function testWriteEmptyValues() {
		Cache::write('App.falseTest', false);
		$this->assertSame(Cache::read('App.falseTest'), false);

		Cache::write('App.trueTest', true);
		$this->assertSame(Cache::read('App.trueTest'), true);

		Cache::write('App.nullTest', null);
		$this->assertSame(Cache::read('App.nullTest'), null);

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
			'Lib' => array(CAKE . 'Test/TestApp/Lib/'),
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		), App::RESET);
		Configure::write('App.namespace', 'TestApp');
		Cache::config('test_trigger', [
			'engine' => 'TestAppCache',
			'prefix' => ''
		]);

		try {
			Cache::write('fail', 'value', 'test_trigger');
			$this->fail('No exception thrown');
		} catch (\PHPUnit_Framework_Error $e) {
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
		Cache::config('test_cache_disable_1', [
			'engine' => 'File',
			'path' => TMP . 'tests'
		]);

		$this->assertTrue(Cache::write('key_1', 'hello', 'test_cache_disable_1'));
		$this->assertSame(Cache::read('key_1', 'test_cache_disable_1'), 'hello');

		Configure::write('Cache.disable', true);

		$this->assertFalse(Cache::write('key_2', 'hello', 'test_cache_disable_1'));
		$this->assertFalse(Cache::read('key_2', 'test_cache_disable_1'));

		Configure::write('Cache.disable', false);

		$this->assertTrue(Cache::write('key_3', 'hello', 'test_cache_disable_1'));
		$this->assertSame(Cache::read('key_3', 'test_cache_disable_1'), 'hello');

		Configure::write('Cache.disable', true);
		Cache::config('test_cache_disable_2', [
			'engine' => 'File',
			'path' => TMP . 'tests'
		]);

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
		Cache::set($_cacheSet);
	}

/**
 * Test that set() modifies settings, which can be read back with
 * settings().
 *
 * @return void
 */
	public function testSetModifySettings() {
		Cache::config('tests', [
			'engine' => 'File',
			'duration' => '+1 minute'
		]);

		$result = Cache::set(['duration' => '+1 year'], 'tests');
		$this->assertEquals(strtotime('+1 year') - time(), $result['duration']);

		$result = Cache::set('duration', '+1 month', 'tests');
		$this->assertEquals(strtotime('+1 month') - time(), $result['duration']);

		$settings = Cache::settings('tests');
		$this->assertEquals($result, $settings, 'set() and settings() should be the same.');
	}

/**
 * Test that calling set() with null, config restores old settings.
 *
 * @return void
 */
	public function testSetModifyAndResetSettings() {
		Cache::config('tests', [
			'engine' => 'File',
			'duration' => '+1 minute'
		]);
		$result = Cache::set('duration', '+1 year', 'tests');
		$this->assertEquals(strtotime('+1 year') - time(), $result['duration']);

		$result = Cache::set(null, 'tests');
		$this->assertEquals(strtotime('+1 minute') - time(), $result['duration']);
	}

/**
 * test set() parameter handling for user cache configs.
 *
 * @return void
 */
	public function testSetOnAlternateConfigs() {
		Cache::config('file_config', [
			'engine' => 'File',
			'prefix' => 'test_file_'
		]);
		Cache::set(['duration' => '+1 year'], 'file_config');
		$settings = Cache::settings('file_config');

		$this->assertEquals('test_file_', $settings['prefix']);
		$this->assertEquals(strtotime('+1 year') - time(), $settings['duration']);
	}

}
