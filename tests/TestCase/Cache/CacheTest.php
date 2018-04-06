<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Cache;

use Cake\Cache\Cache;
use Cake\Cache\CacheRegistry;
use Cake\Cache\Engine\FileEngine;
use Cake\Cache\Engine\NullEngine;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Error\Error;

/**
 * CacheTest class
 */
class CacheTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Cache::enable();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Cache::drop('tests');
        Cache::drop('test_trigger');
    }

    /**
     * Configure cache settings for test
     *
     * @return void
     */
    protected function _configCache()
    {
        Cache::setConfig('tests', [
            'engine' => 'File',
            'path' => TMP,
            'prefix' => 'test_'
        ]);
    }

    /**
     * tests Cache::engine() fallback
     *
     * @return void
     */
    public function testCacheEngineFallback()
    {
        $filename = tempnam(TMP, 'tmp_');

        Cache::setConfig('tests', [
            'engine' => 'File',
            'path' => $filename,
            'prefix' => 'test_',
            'fallback' => 'tests_fallback'
        ]);
        Cache::setConfig('tests_fallback', [
            'engine' => 'File',
            'path' => TMP,
            'prefix' => 'test_',
        ]);

        $engine = Cache::engine('tests');
        $path = $engine->getConfig('path');
        $this->assertSame(TMP, $path);

        Cache::drop('tests');
        Cache::drop('tests_fallback');
        unlink($filename);
    }

    /**
     * tests handling misconfiguration of fallback
     *
     * @return void
     */
    public function testCacheEngineFallbackToSelf()
    {
        $filename = tempnam(TMP, 'tmp_');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot fallback to itself');

        Cache::setConfig('tests', [
            'engine' => 'File',
            'path' => $filename,
            'prefix' => 'test_',
            'fallback' => 'tests'
        ]);

        Cache::engine('tests');

        Cache::drop('tests');
        unlink($filename);
    }

    /**
     * tests Cache::engine() fallback when using groups
     *
     * @return void
     */
    public function testCacheFallbackWithGroups()
    {
        $filename = tempnam(TMP, 'tmp_');

        Cache::setConfig('tests', [
            'engine' => 'File',
            'path' => $filename,
            'prefix' => 'test_',
            'fallback' => 'tests_fallback',
            'groups' => ['group1', 'group2'],
        ]);
        Cache::setConfig('tests_fallback', [
            'engine' => 'File',
            'path' => TMP,
            'prefix' => 'test_',
            'groups' => ['group3', 'group1'],
        ]);

        $result = Cache::groupConfigs('group1');
        $this->assertSame(['group1' => ['tests', 'tests_fallback']], $result);

        $result = Cache::groupConfigs('group2');
        $this->assertSame(['group2' => ['tests']], $result);

        Cache::drop('tests');
        Cache::drop('tests_fallback');
        unlink($filename);
    }

    /**
     * tests cache fallback
     *
     * @return void
     */
    public function testCacheFallbackIntegration()
    {
        $filename = tempnam(TMP, 'tmp_');

        Cache::setConfig('tests', [
            'engine' => 'File',
            'path' => $filename,
            'fallback' => 'tests_fallback',
            'groups' => ['integration_group', 'integration_group_2']
        ]);
        Cache::setConfig('tests_fallback', [
            'engine' => 'File',
            'path' => $filename,
            'fallback' => 'tests_fallback_final',
            'groups' => ['integration_group'],
        ]);
        Cache::setConfig('tests_fallback_final', [
            'engine' => 'File',
            'path' => TMP . 'cake_test' . DS,
            'groups' => ['integration_group_3'],
        ]);

        $this->assertTrue(Cache::write('grouped', 'worked', 'tests'));
        $this->assertTrue(Cache::write('grouped_2', 'worked', 'tests_fallback'));
        $this->assertTrue(Cache::write('grouped_3', 'worked', 'tests_fallback_final'));

        $this->assertTrue(Cache::clearGroup('integration_group', 'tests'));

        $this->assertFalse(Cache::read('grouped', 'tests'));
        $this->assertFalse(Cache::read('grouped_2', 'tests_fallback'));

        $this->assertSame('worked', Cache::read('grouped_3', 'tests_fallback_final'));

        Cache::drop('tests');
        Cache::drop('tests_fallback');
        Cache::drop('tests_fallback_final');
        unlink($filename);
    }

    /**
     * Check that no fatal errors are issued doing normal things when Cache.disable is true.
     *
     * @return void
     */
    public function testNonFatalErrorsWithCacheDisable()
    {
        Cache::disable();
        $this->_configCache();

        $this->assertNull(Cache::write('no_save', 'Noooo!', 'tests'));
        $this->assertFalse(Cache::read('no_save', 'tests'));
        $this->assertNull(Cache::delete('no_save', 'tests'));
    }

    /**
     * Check that a null instance is returned from engine() when caching is disabled.
     *
     * @return void
     */
    public function testNullEngineWhenCacheDisable()
    {
        $this->_configCache();
        Cache::disable();

        $result = Cache::engine('tests');
        $this->assertInstanceOf(NullEngine::class, $result);
    }

    /**
     * Test configuring an invalid class fails
     *
     * @return void
     */
    public function testConfigInvalidClassType()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('Cache engines must use Cake\Cache\CacheEngine');

        Cache::setConfig('tests', [
            'className' => '\StdClass'
        ]);
        $result = Cache::engine('tests');
        $this->assertInstanceOf(NullEngine::class, $result);
    }

    /**
     * Test engine init failing triggers an error but falls back to NullEngine
     *
     * @return void
     */
    public function testConfigFailedInit()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('is not properly configured');

        $mock = $this->getMockForAbstractClass('Cake\Cache\CacheEngine', [], '', true, true, true, ['init']);
        $mock->method('init')->will($this->returnValue(false));
        Cache::setConfig('tests', [
            'engine' => $mock
        ]);
        $result = Cache::engine('tests');
        $this->assertInstanceOf(NullEngine::class, $result);
    }

    /**
     * test configuring CacheEngines in App/libs
     *
     * @return void
     */
    public function testConfigWithLibAndPluginEngines()
    {
        static::setAppNamespace();
        Plugin::load('TestPlugin');

        $config = ['engine' => 'TestAppCache', 'path' => TMP, 'prefix' => 'cake_test_'];
        Cache::setConfig('libEngine', $config);
        $engine = Cache::engine('libEngine');
        $this->assertInstanceOf('TestApp\Cache\Engine\TestAppCacheEngine', $engine);

        $config = ['engine' => 'TestPlugin.TestPluginCache', 'path' => TMP, 'prefix' => 'cake_test_'];
        $result = Cache::setConfig('pluginLibEngine', $config);
        $engine = Cache::engine('pluginLibEngine');
        $this->assertInstanceOf('TestPlugin\Cache\Engine\TestPluginCacheEngine', $engine);

        Cache::drop('libEngine');
        Cache::drop('pluginLibEngine');

        Plugin::unload();
    }

    /**
     * Test write from a config that is undefined.
     *
     * @return void
     */
    public function testWriteNonExistingConfig()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertFalse(Cache::write('key', 'value', 'totally fake'));
    }

    /**
     * Test write from a config that is undefined.
     *
     * @return void
     */
    public function testIncrementNonExistingConfig()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertFalse(Cache::increment('key', 1, 'totally fake'));
    }

    /**
     * Test write from a config that is undefined.
     *
     * @return void
     */
    public function testDecrementNonExistingConfig()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->assertFalse(Cache::decrement('key', 1, 'totally fake'));
    }

    /**
     * Data provider for valid config data sets.
     *
     * @return array
     */
    public static function configProvider()
    {
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
        ];
    }

    /**
     * testConfig method
     *
     * @dataProvider configProvider
     * @return void
     */
    public function testConfigVariants($config)
    {
        $this->assertNotContains('test', Cache::configured(), 'test config should not exist.');
        Cache::setConfig('tests', $config);

        $engine = Cache::engine('tests');
        $this->assertInstanceOf('Cake\Cache\Engine\FileEngine', $engine);
        $this->assertContains('tests', Cache::configured());
    }

    /**
     * testConfigInvalidEngine method
     *
     * @return void
     */
    public function testConfigInvalidEngine()
    {
        $this->expectException(\BadMethodCallException::class);
        $config = ['engine' => 'Imaginary'];
        Cache::setConfig('test', $config);
        Cache::engine('test');
    }

    /**
     * test that trying to configure classes that don't extend CacheEngine fail.
     *
     * @return void
     */
    public function testConfigInvalidObject()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->getMockBuilder(\StdClass::class)
            ->setMockClassName('RubbishEngine')
            ->getMock();
        Cache::setConfig('test', [
            'engine' => '\RubbishEngine'
        ]);
        Cache::engine('tests');
    }

    /**
     * Ensure you cannot reconfigure a cache adapter.
     *
     * @return void
     */
    public function testConfigErrorOnReconfigure()
    {
        $this->expectException(\BadMethodCallException::class);
        Cache::setConfig('tests', ['engine' => 'File', 'path' => TMP]);
        Cache::setConfig('tests', ['engine' => 'Apc']);
    }

    /**
     * Test reading configuration.
     *
     * @return void
     */
    public function testConfigRead()
    {
        $config = [
            'engine' => 'File',
            'path' => TMP,
            'prefix' => 'cake_'
        ];
        Cache::setConfig('tests', $config);
        $expected = $config;
        $expected['className'] = $config['engine'];
        unset($expected['engine']);
        $this->assertEquals($expected, Cache::config('tests'));
    }

    /**
     * test config() with dotted name
     *
     * @return void
     */
    public function testConfigDottedAlias()
    {
        Cache::setConfig('cache.dotted', [
            'className' => 'File',
            'path' => TMP,
            'prefix' => 'cache_value_'
        ]);

        $engine = Cache::engine('cache.dotted');
        $this->assertContains('cache.dotted', Cache::configured());
        $this->assertNotContains('dotted', Cache::configured());
        $this->assertInstanceOf('Cake\Cache\Engine\FileEngine', $engine);
        Cache::drop('cache.dotted');
    }

    /**
     * testGroupConfigs method
     */
    public function testGroupConfigs()
    {
        Cache::drop('test');
        Cache::config('latest', [
            'duration' => 300,
            'engine' => 'File',
            'groups' => ['posts', 'comments'],
        ]);

        $result = Cache::groupConfigs();

        $this->assertArrayHasKey('posts', $result);
        $this->assertContains('latest', $result['posts']);

        $this->assertArrayHasKey('comments', $result);
        $this->assertContains('latest', $result['comments']);

        $result = Cache::groupConfigs('posts');
        $this->assertEquals(['posts' => ['latest']], $result);

        Cache::config('page', [
            'duration' => 86400,
            'engine' => 'File',
            'groups' => ['posts', 'archive'],
        ]);

        $result = Cache::groupConfigs();

        $this->assertArrayHasKey('posts', $result);
        $this->assertContains('latest', $result['posts']);
        $this->assertContains('page', $result['posts']);

        $this->assertArrayHasKey('comments', $result);
        $this->assertContains('latest', $result['comments']);
        $this->assertNotContains('page', $result['comments']);

        $this->assertArrayHasKey('archive', $result);
        $this->assertContains('page', $result['archive']);
        $this->assertNotContains('latest', $result['archive']);

        $result = Cache::groupConfigs('archive');
        $this->assertEquals(['archive' => ['page']], $result);

        Cache::setConfig('archive', [
            'duration' => 86400 * 30,
            'engine' => 'File',
            'groups' => ['posts', 'archive', 'comments'],
        ]);

        $result = Cache::groupConfigs('archive');
        $this->assertEquals(['archive' => ['archive', 'page']], $result);
    }

    /**
     * testGroupConfigsWithCacheInstance method
     */
    public function testGroupConfigsWithCacheInstance()
    {
        Cache::drop('test');
        $cache = new FileEngine();
        $cache->init([
            'duration' => 300,
            'engine' => 'File',
            'groups' => ['users', 'comments'],
        ]);
        Cache::setConfig('cached', $cache);

        $result = Cache::groupConfigs('users');
        $this->assertEquals(['users' => ['cached']], $result);
    }

    /**
     * testGroupConfigsThrowsException method
     */
    public function testGroupConfigsThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        Cache::groupConfigs('bogus');
    }

    /**
     * test that configured returns an array of the currently configured cache
     * config
     *
     * @return void
     */
    public function testConfigured()
    {
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
    public function testDrop()
    {
        static::setAppNamespace();

        $result = Cache::drop('some_config_that_does_not_exist');
        $this->assertFalse($result, 'Drop should not succeed when config is missing.');

        Cache::setConfig('unconfigTest', [
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
    public function testWriteEmptyValues()
    {
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
     * testWriteEmptyValues method
     *
     * @return void
     */
    public function testWriteEmptyKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An empty value is not valid as a cache key');
        $this->_configCache();
        Cache::write(null, 'not null', 'tests');
    }

    /**
     * testReadWriteMany method
     *
     * @return void
     */
    public function testReadWriteMany()
    {
        $this->_configCache();
        $data = [
            'App.falseTest' => false,
            'App.trueTest' => true,
            'App.nullTest' => null,
            'App.zeroTest' => 0,
            'App.zeroTest2' => '0'
        ];
        Cache::writeMany($data, 'tests');

        $read = Cache::readMany(array_keys($data), 'tests');

        $this->assertSame($read['App.falseTest'], false);
        $this->assertSame($read['App.trueTest'], true);
        $this->assertSame($read['App.nullTest'], null);
        $this->assertSame($read['App.zeroTest'], 0);
        $this->assertSame($read['App.zeroTest2'], '0');
    }

    /**
     * testDeleteMany method
     *
     * @return void
     */
    public function testDeleteMany()
    {
        $this->_configCache();
        $data = [
            'App.falseTest' => false,
            'App.trueTest' => true,
            'App.nullTest' => null,
            'App.zeroTest' => 0,
            'App.zeroTest2' => '0'
        ];
        Cache::writeMany(array_merge($data, ['App.keepTest' => 'keepMe']), 'tests');

        Cache::deleteMany(array_keys($data), 'tests');
        $read = Cache::readMany(array_merge(array_keys($data), ['App.keepTest']), 'tests');

        $this->assertSame($read['App.falseTest'], false);
        $this->assertSame($read['App.trueTest'], false);
        $this->assertSame($read['App.nullTest'], false);
        $this->assertSame($read['App.zeroTest'], false);
        $this->assertSame($read['App.zeroTest2'], false);
        $this->assertSame($read['App.keepTest'], 'keepMe');
    }

    /**
     * Test that failed writes cause errors to be triggered.
     *
     * @return void
     */
    public function testWriteTriggerError()
    {
        $this->expectException(\PHPUnit\Framework\Error\Error::class);
        static::setAppNamespace();
        Cache::setConfig('test_trigger', [
            'engine' => 'TestAppCache',
            'prefix' => ''
        ]);

        Cache::write('fail', 'value', 'test_trigger');
    }

    /**
     * testCacheDisable method
     *
     * Check that the "Cache.disable" configuration and a change to it
     * (even after a cache config has been setup) is taken into account.
     *
     * @return void
     */
    public function testCacheDisable()
    {
        Cache::enable();
        Cache::setConfig('test_cache_disable_1', [
            'engine' => 'File',
            'path' => TMP . 'tests'
        ]);

        $this->assertTrue(Cache::write('key_1', 'hello', 'test_cache_disable_1'));
        $this->assertSame(Cache::read('key_1', 'test_cache_disable_1'), 'hello');

        Cache::disable();

        $this->assertNull(Cache::write('key_2', 'hello', 'test_cache_disable_1'));
        $this->assertFalse(Cache::read('key_2', 'test_cache_disable_1'));

        Cache::enable();

        $this->assertTrue(Cache::write('key_3', 'hello', 'test_cache_disable_1'));
        $this->assertSame('hello', Cache::read('key_3', 'test_cache_disable_1'));
        Cache::clear(false, 'test_cache_disable_1');

        Cache::disable();
        Cache::config('test_cache_disable_2', [
            'engine' => 'File',
            'path' => TMP . 'tests'
        ]);

        $this->assertNull(Cache::write('key_4', 'hello', 'test_cache_disable_2'));
        $this->assertFalse(Cache::read('key_4', 'test_cache_disable_2'));

        Cache::enable();

        $this->assertTrue(Cache::write('key_5', 'hello', 'test_cache_disable_2'));
        $this->assertSame(Cache::read('key_5', 'test_cache_disable_2'), 'hello');

        Cache::disable();
        $this->assertNull(Cache::write('key_6', 'hello', 'test_cache_disable_2'));
        $this->assertFalse(Cache::read('key_6', 'test_cache_disable_2'));

        Cache::enable();
        Cache::clear(false, 'test_cache_disable_2');
    }

    /**
     * test clearAll() method
     *
     * @return void
     */
    public function testClearAll()
    {
        Cache::config('configTest', [
            'engine' => 'File',
            'path' => TMP . 'tests'
        ]);
        Cache::config('anotherConfigTest', [
            'engine' => 'File',
            'path' => TMP . 'tests'
        ]);

        Cache::write('key_1', 'hello', 'configTest');
        Cache::write('key_2', 'hello again', 'anotherConfigTest');

        $this->assertSame(Cache::read('key_1', 'configTest'), 'hello');
        $this->assertSame(Cache::read('key_2', 'anotherConfigTest'), 'hello again');

        $result = Cache::clearAll();
        $this->assertTrue($result['configTest']);
        $this->assertTrue($result['anotherConfigTest']);
        $this->assertFalse(Cache::read('key_1', 'configTest'));
        $this->assertFalse(Cache::read('key_2', 'anotherConfigTest'));
        Cache::drop('configTest');
        Cache::drop('anotherConfigTest');
    }

    /**
     * Test toggling enabled state of cache.
     *
     * @return void
     */
    public function testEnableDisableEnabled()
    {
        $this->assertNull(Cache::enable());
        $this->assertTrue(Cache::enabled(), 'Should be on');
        $this->assertNull(Cache::disable());
        $this->assertFalse(Cache::enabled(), 'Should be off');
    }

    /**
     * test remember method.
     *
     * @return void
     */
    public function testRemember()
    {
        $this->_configCache();
        $counter = 0;
        $cacher = function () use ($counter) {
            return 'This is some data ' . $counter;
        };

        $expected = 'This is some data 0';
        $result = Cache::remember('test_key', $cacher, 'tests');
        $this->assertEquals($expected, $result);

        $counter = 1;
        $result = Cache::remember('test_key', $cacher, 'tests');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test add method.
     *
     * @return void
     */
    public function testAdd()
    {
        $this->_configCache();
        Cache::delete('test_add_key', 'tests');

        $result = Cache::add('test_add_key', 'test data', 'tests');
        $this->assertTrue($result);

        $expected = 'test data';
        $result = Cache::read('test_add_key', 'tests');
        $this->assertEquals($expected, $result);

        $result = Cache::add('test_add_key', 'test data 2', 'tests');
        $this->assertFalse($result);
    }

    /**
     * test registry method
     *
     * @return void
     */
    public function testRegistry()
    {
        $this->assertInstanceOf(CacheRegistry::class, Cache::registry());
    }

    /**
     * test registry method setting
     *
     * @return void
     */
    public function testRegistrySet()
    {
        $registry = new CacheRegistry();
        Cache::registry($registry);

        $this->assertSame($registry, Cache::registry());
    }

    /**
     * Test getting the registry
     *
     * @return void
     */
    public function testGetRegistry()
    {
        $this->assertInstanceOf(CacheRegistry::class, Cache::getRegistry());
    }

    /**
     * Test setting the registry
     *
     * @return void
     */
    public function testSetAndGetRegistry()
    {
        $registry = new CacheRegistry();
        Cache::setRegistry($registry);

        $this->assertSame($registry, Cache::getRegistry());
    }
}
