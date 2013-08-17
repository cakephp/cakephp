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
use Cake\Cache\Engine\FileEngine;
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
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Cache::drop('tests');
	}

	protected function _configCache() {
		Cache::config('tests', [
			'engine' => 'File',
			'path' => TMP,
			'prefix' => 'test_'
		]);
	}

/**
 * Check that no fatal errors are issued doing normal things when Cache.disable is true.
 *
 * @return void
 */
	public function testNonFatalErrorsWithCachedisable() {
		Configure::write('Cache.disable', true);
		$this->_configCache();

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
 * Test write from a config that is undefined.
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testWriteNonExistingConfig() {
		$this->assertFalse(Cache::write('key', 'value', 'totally fake'));
	}

/**
 * Test write from a config that is undefined.
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testIncrementNonExistingConfig() {
		$this->assertFalse(Cache::increment('key', 1, 'totally fake'));
	}

/**
 * Test write from a config that is undefined.
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testDecrementNonExistingConfig() {
		$this->assertFalse(Cache::decrement('key', 1, 'totally fake'));
	}

/**
 * Data provider for valid config data sets.
 *
 * @return array
 */
	public static function configProvider() {
		return [
			'Array of data using engine key.' => [[
				'engine' => 'File',
				'path' => TMP . 'tests',
				'prefix' => 'cake_test_'
			]],
			'Array of data using classname key.' => [[
				'className' => 'File',
				'path' => TMP . 'tests',
				'prefix' => 'cake_test_'
			]],
			'Direct instance' => [new FileEngine()],
			'Closure factory' => [function () {
				return new FileEngine();
			}],
		];
	}
/**
 * testConfig method
 *
 * @dataProvider configProvider
 * @return void
 */
	public function testConfigVariants($settings) {
		$this->assertNotContains('test', Cache::configured(), 'test config should not exist.');
		Cache::config('tests', $settings);

		$engine = Cache::engine('tests');
		$this->assertInstanceOf('Cake\Cache\Engine\FileEngine', $engine);
		$this->assertContains('tests', Cache::configured());
	}

/**
 * testConfigInvalidEngine method
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testConfigInvalidEngine() {
		$settings = array('engine' => 'Imaginary');
		Cache::config('test', $settings);
		Cache::engine('test');
	}

/**
 * test that trying to configure classes that don't extend CacheEngine fail.
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testConfigInvalidObject() {
		$this->getMock('\StdClass', array(), array(), 'RubbishEngine');
		Cache::config('test', array(
			'engine' => '\RubbishEngine'
		));
		Cache::engine('tests');
	}

/**
 * Ensure you cannot reconfigure a cache adapter.
 *
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testConfigErrorOnReconfigure() {
		Cache::config('tests', ['engine' => 'File', 'path' => TMP]);
		Cache::config('tests', ['engine' => 'Apc']);
	}

/**
 * Test reading configuration.
 *
 * @return void
 */
	public function testConfigRead() {
		$settings = [
			'engine' => 'File',
			'path' => TMP,
			'prefix' => 'cake_'
		];
		Cache::config('tests', $settings);
		$expected = $settings;
		$expected['className'] = $settings['engine'];
		unset($expected['engine']);
		$this->assertEquals($expected, Cache::config('tests'));
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
		$this->assertTrue($result, 'Drop should succeed.');

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
 * testWriteEmptyValues method
 *
 * @return void
 */
	public function testWriteEmptyValues() {
		$this->_configCache();
		Cache::write('App.falseTest', false, 'tests');
		$this->assertSame(Cache::read('App.falseTest', 'tests'), false);

		Cache::write('App.trueTest', true, 'tests');
		$this->assertSame(Cache::read('App.trueTest', 'tests'), true);

		Cache::write('App.nullTest', null, 'tests');
		$this->assertSame(Cache::read('App.nullTest', 'tests'), null);

		Cache::write('App.zeroTest', 0, 'tests');
		$this->assertSame(Cache::read('App.zeroTest', 'tests'), 0);

		Cache::write('App.zeroTest2', '0', 'tests');
		$this->assertSame(Cache::read('App.zeroTest2', 'tests'), '0');
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
		$this->_configCache();

		Cache::set(array('duration' => '+1 year'), 'tests');
		$data = Cache::read('test_cache', 'tests');
		$this->assertFalse($data);

		$data = 'this is just a simple test of the cache system';
		$write = Cache::write('test_cache', $data, 'tests');
		$this->assertTrue($write);

		Cache::set(array('duration' => '+1 year'), 'tests');
		$data = Cache::read('test_cache', 'tests');
		$this->assertEquals('this is just a simple test of the cache system', $data);

		Cache::delete('test_cache', 'tests');
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
