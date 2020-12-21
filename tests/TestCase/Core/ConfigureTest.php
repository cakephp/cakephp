<?php
declare(strict_types=1);

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
namespace Cake\Test\TestCase\Core;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\TestSuite\TestCase;

/**
 * ConfigureTest
 */
class ConfigureTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Cache::disable();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        if (file_exists(TMP . 'cache/persistent/cake_core_core_paths')) {
            unlink(TMP . 'cache/persistent/cake_core_core_paths');
        }
        if (file_exists(TMP . 'cache/persistent/cake_core_dir_map')) {
            unlink(TMP . 'cache/persistent/cake_core_dir_map');
        }
        if (file_exists(TMP . 'cache/persistent/cake_core_file_map')) {
            unlink(TMP . 'cache/persistent/cake_core_file_map');
        }
        if (file_exists(TMP . 'cache/persistent/cake_core_object_map')) {
            unlink(TMP . 'cache/persistent/cake_core_object_map');
        }
        if (file_exists(TMP . 'cache/persistent/test.config.php')) {
            unlink(TMP . 'cache/persistent/test.config.php');
        }
        if (file_exists(TMP . 'cache/persistent/test.php')) {
            unlink(TMP . 'cache/persistent/test.php');
        }
        Configure::drop('test');
        Cache::enable();
    }

    /**
     * testReadOrFail method
     *
     * @return void
     */
    public function testReadOrFail()
    {
        $expected = 'ok';
        Configure::write('This.Key.Exists', $expected);
        $result = Configure::readOrFail('This.Key.Exists');
        $this->assertSame($expected, $result);
    }

    /**
     * testReadOrFail method
     *
     * @return void
     */
    public function testReadOrFailThrowingException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected configuration key "This.Key.Does.Not.exist" not found');
        Configure::readOrFail('This.Key.Does.Not.exist');
    }

    /**
     * testRead method
     *
     * @return void
     */
    public function testRead()
    {
        $expected = 'ok';
        Configure::write('level1.level2.level3_1', $expected);
        Configure::write('level1.level2.level3_2', 'something_else');
        $result = Configure::read('level1.level2.level3_1');
        $this->assertSame($expected, $result);

        $result = Configure::read('level1.level2.level3_2');
        $this->assertSame('something_else', $result);

        $result = Configure::read('debug');
        $this->assertGreaterThanOrEqual(0, $result);

        $result = Configure::read();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('debug', $result);
        $this->assertArrayHasKey('level1', $result);

        $result = Configure::read('something_I_just_made_up_now');
        $this->assertNull($result, 'Missing key should return null.');

        $default = 'default';
        $result = Configure::read('something_I_just_made_up_now', $default);
        $this->assertSame($default, $result);

        $default = ['default'];
        $result = Configure::read('something_I_just_made_up_now', $default);
        $this->assertEquals($default, $result);
    }

    /**
     * testWrite method
     *
     * @return void
     */
    public function testWrite()
    {
        Configure::write('SomeName.someKey', 'myvalue');
        $result = Configure::read('SomeName.someKey');
        $this->assertSame('myvalue', $result);

        Configure::write('SomeName.someKey', null);
        $result = Configure::read('SomeName.someKey');
        $this->assertNull($result);

        $expected = ['One' => ['Two' => ['Three' => ['Four' => ['Five' => 'cool']]]]];
        Configure::write('Key', $expected);

        $result = Configure::read('Key');
        $this->assertEquals($expected, $result);

        $result = Configure::read('Key.One');
        $this->assertEquals($expected['One'], $result);

        $result = Configure::read('Key.One.Two');
        $this->assertEquals($expected['One']['Two'], $result);

        $result = Configure::read('Key.One.Two.Three.Four.Five');
        $this->assertSame('cool', $result);

        Configure::write('one.two.three.four', '4');
        $result = Configure::read('one.two.three.four');
        $this->assertSame('4', $result);
    }

    /**
     * test setting display_errors with debug.
     *
     * @return void
     */
    public function testDebugSettingDisplayErrors()
    {
        $this->skipIf(
            defined('HHVM_VERSION'),
            'Cannot change display_errors at runtime in HHVM'
        );
        Configure::write('debug', false);
        $result = ini_get('display_errors');
        $this->assertSame('0', $result);

        Configure::write('debug', true);
        $result = ini_get('display_errors');
        $this->assertSame('1', $result);
    }

    /**
     * testDelete method
     *
     * @return void
     */
    public function testDelete()
    {
        Configure::write('SomeName.someKey', 'myvalue');
        $result = Configure::read('SomeName.someKey');
        $this->assertSame('myvalue', $result);

        Configure::delete('SomeName.someKey');
        $result = Configure::read('SomeName.someKey');
        $this->assertNull($result);

        Configure::write('SomeName', ['someKey' => 'myvalue', 'otherKey' => 'otherValue']);

        $result = Configure::read('SomeName.someKey');
        $this->assertSame('myvalue', $result);

        $result = Configure::read('SomeName.otherKey');
        $this->assertSame('otherValue', $result);

        Configure::delete('SomeName');

        $result = Configure::read('SomeName.someKey');
        $this->assertNull($result);

        $result = Configure::read('SomeName.otherKey');
        $this->assertNull($result);
    }

    /**
     * testCheck method
     *
     * @return void
     */
    public function testCheck()
    {
        Configure::write('ConfigureTestCase', 'value');
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertFalse(Configure::check('NotExistingConfigureTestCase'));
    }

    /**
     * testCheckingSavedEmpty method
     *
     * @return void
     */
    public function testCheckingSavedEmpty()
    {
        Configure::write('ConfigureTestCase', 0);
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        Configure::write('ConfigureTestCase', '0');
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        Configure::write('ConfigureTestCase', false);
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        Configure::write('ConfigureTestCase', null);
        $this->assertFalse(Configure::check('ConfigureTestCase'));
    }

    /**
     * testCheckKeyWithSpaces method
     *
     * @return void
     */
    public function testCheckKeyWithSpaces()
    {
        Configure::write('Configure Test', 'test');
        $this->assertTrue(Configure::check('Configure Test'));
        Configure::delete('Configure Test');

        Configure::write('Configure Test.Test Case', 'test');
        $this->assertTrue(Configure::check('Configure Test.Test Case'));
    }

    /**
     * testCheckEmpty
     *
     * @return void
     */
    public function testCheckEmpty()
    {
        $this->assertFalse(Configure::check(''));
    }

    /**
     * testLoad method
     *
     * @return void
     */
    public function testLoadExceptionOnNonExistentFile()
    {
        $this->expectException(\RuntimeException::class);
        Configure::config('test', new PhpConfig());
        Configure::load('nonexistent_configuration_file', 'test');
    }

    /**
     * test load method for default config creation
     *
     * @return void
     */
    public function testLoadDefaultConfig()
    {
        try {
            Configure::load('nonexistent_configuration_file');
        } catch (\Exception $e) {
            $this->assertTrue(Configure::isConfigured('default'));
            $this->assertFalse(Configure::isConfigured('nonexistent_configuration_file'));
        }
    }

    /**
     * test load with merging
     *
     * @return void
     */
    public function testLoadWithMerge()
    {
        Configure::config('test', new PhpConfig(CONFIG));

        $result = Configure::load('var_test', 'test');
        $this->assertTrue($result);

        $this->assertSame('value', Configure::read('Read'));

        $result = Configure::load('var_test2', 'test', true);
        $this->assertTrue($result);

        $this->assertSame('value2', Configure::read('Read'));
        $this->assertSame('buried2', Configure::read('Deep.Second.SecondDeepest'));
        $this->assertSame('buried', Configure::read('Deep.Deeper.Deepest'));
        $this->assertSame('Overwrite', Configure::read('TestAcl.classname'));
        $this->assertSame('one', Configure::read('TestAcl.custom'));
    }

    /**
     * test loading with overwrite
     *
     * @return void
     */
    public function testLoadNoMerge()
    {
        Configure::config('test', new PhpConfig(CONFIG));

        $result = Configure::load('var_test', 'test');
        $this->assertTrue($result);

        $this->assertSame('value', Configure::read('Read'));

        $result = Configure::load('var_test2', 'test', false);
        $this->assertTrue($result);

        $this->assertSame('value2', Configure::read('Read'));
        $this->assertSame('buried2', Configure::read('Deep.Second.SecondDeepest'));
        $this->assertNull(Configure::read('Deep.Deeper.Deepest'));
    }

    /**
     * Test load() replacing existing data
     *
     * @return void
     */
    public function testLoadWithExistingData()
    {
        Configure::config('test', new PhpConfig(CONFIG));
        Configure::write('my_key', 'value');

        Configure::load('var_test', 'test');
        $this->assertSame('value', Configure::read('my_key'), 'Should not overwrite existing data.');
        $this->assertSame('value', Configure::read('Read'), 'Should load new data.');
    }

    /**
     * Test load() merging on top of existing data
     *
     * @return void
     */
    public function testLoadMergeWithExistingData()
    {
        Configure::config('test', new PhpConfig());
        Configure::write('my_key', 'value');
        Configure::write('Read', 'old');
        Configure::write('Deep.old', 'old');
        Configure::write('TestAcl.classname', 'old');

        Configure::load('var_test', 'test', true);
        $this->assertSame('value', Configure::read('Read'), 'Should load new data.');
        $this->assertSame('buried', Configure::read('Deep.Deeper.Deepest'), 'Should load new data');
        $this->assertSame('old', Configure::read('Deep.old'), 'Should not destroy old data.');
        $this->assertSame('value', Configure::read('my_key'), 'Should not destroy data.');
        $this->assertSame('Original', Configure::read('TestAcl.classname'), 'No arrays');
    }

    /**
     * testLoad method
     *
     * @return void
     */
    public function testLoadPlugin()
    {
        Configure::config('test', new PhpConfig());
        $this->loadPlugins(['TestPlugin']);
        $result = Configure::load('TestPlugin.load', 'test');
        $this->assertTrue($result);
        $expected = '/test_app/Plugin/TestPlugin/Config/load.php';
        $config = Configure::read('plugin_load');
        $this->assertSame($expected, $config);

        $result = Configure::load('TestPlugin.more.load', 'test');
        $this->assertTrue($result);
        $expected = '/test_app/Plugin/TestPlugin/Config/more.load.php';
        $config = Configure::read('plugin_more_load');
        $this->assertSame($expected, $config);
        $this->clearPlugins();
    }

    /**
     * testStore method
     *
     * @return void
     */
    public function testStoreAndRestore()
    {
        Cache::enable();
        Cache::setConfig('configure', [
            'className' => 'File',
            'path' => TMP . 'tests',
        ]);

        Configure::write('Testing', 'yummy');
        $this->assertTrue(Configure::store('store_test', 'configure'));

        Configure::delete('Testing');
        $this->assertNull(Configure::read('Testing'));

        Configure::restore('store_test', 'configure');
        $this->assertSame('yummy', Configure::read('Testing'));

        Cache::delete('store_test', 'configure');
        Cache::drop('configure');
    }

    /**
     * test that store and restore only store/restore the provided data.
     *
     * @return void
     */
    public function testStoreAndRestoreWithData()
    {
        Cache::enable();
        Cache::setConfig('configure', [
            'className' => 'File',
            'path' => TMP . 'tests',
        ]);

        Configure::write('testing', 'value');
        Configure::store('store_test', 'configure', ['store_test' => 'one']);
        Configure::delete('testing');
        $this->assertNull(Configure::read('store_test'), 'Calling store with data shouldn\'t modify runtime.');

        Configure::restore('store_test', 'configure');
        $this->assertSame('one', Configure::read('store_test'));
        $this->assertNull(Configure::read('testing'), 'Values that were not stored are not restored.');

        Cache::delete('store_test', 'configure');
        Cache::drop('configure');
    }

    /**
     * testVersion method
     *
     * @return void
     */
    public function testVersion()
    {
        $original = Configure::version();
        $this->assertTrue(version_compare($original, '4.0', '>='));

        Configure::write('Cake.version', 'banana');
        $this->assertSame('banana', Configure::version());

        Configure::delete('Cake.version');
        $this->assertSame($original, Configure::version());
    }

    /**
     * Tests adding new engines.
     *
     * @return void
     */
    public function testEngineSetup()
    {
        $engine = new PhpConfig();
        Configure::config('test', $engine);
        $configured = Configure::configured();

        $this->assertContains('test', $configured);

        $this->assertTrue(Configure::isConfigured('test'));
        $this->assertFalse(Configure::isConfigured('fake_garbage'));

        $this->assertTrue(Configure::drop('test'));
        $this->assertFalse(Configure::drop('test'), 'dropping things that do not exist should return false.');
    }

    /**
     * Tests adding new engines as numeric strings.
     *
     * @return void
     */
    public function testEngineSetupNumeric()
    {
        $engine = new PhpConfig();
        Configure::config('123', $engine);
        $configured = Configure::configured();

        $this->assertContains('123', $configured);

        $this->assertTrue(Configure::isConfigured('123'));

        $this->assertTrue(Configure::drop('123'));
        $this->assertFalse(Configure::drop('123'), 'dropping things that do not exist should return false.');
    }

    /**
     * Test that clear wipes all values.
     *
     * @return void
     */
    public function testClear()
    {
        Configure::write('test', 'value');
        Configure::clear();
        $this->assertNull(Configure::read('debug'));
        $this->assertNull(Configure::read('test'));
    }

    /**
     * @return void
     */
    public function testDumpNoAdapter()
    {
        $this->expectException(\Cake\Core\Exception\CakeException::class);
        Configure::dump(TMP . 'test.php', 'does_not_exist');
    }

    /**
     * test dump integrated with the PhpConfig.
     *
     * @return void
     */
    public function testDump()
    {
        Configure::config('test_Engine', new PhpConfig(TMP));

        $result = Configure::dump('config_test', 'test_Engine');
        $this->assertGreaterThan(0, $result);
        $result = file_get_contents(TMP . 'config_test.php');
        $this->assertStringContainsString('<?php', $result);
        $this->assertStringContainsString('return ', $result);
        if (file_exists(TMP . 'config_test.php')) {
            unlink(TMP . 'config_test.php');
        }
    }

    /**
     * Test dumping only some of the data.
     *
     * @return void
     */
    public function testDumpPartial()
    {
        Configure::config('test_Engine', new PhpConfig(TMP));
        Configure::write('Error', ['test' => 'value']);

        $result = Configure::dump('config_test', 'test_Engine', ['Error']);
        $this->assertGreaterThan(0, $result);
        $result = file_get_contents(TMP . 'config_test.php');
        $this->assertStringContainsString('<?php', $result);
        $this->assertStringContainsString('return ', $result);
        $this->assertStringContainsString('Error', $result);
        $this->assertStringNotContainsString('debug', $result);

        if (file_exists(TMP . 'config_test.php')) {
            unlink(TMP . 'config_test.php');
        }
    }

    /**
     * Test the consume method.
     *
     * @return void
     */
    public function testConsume()
    {
        $this->assertNull(Configure::consume('DoesNotExist'), 'Should be null on empty value');
        Configure::write('Test', ['key' => 'value', 'key2' => 'value2']);

        $result = Configure::consume('Test.key');
        $this->assertSame('value', $result);

        $result = Configure::read('Test.key2');
        $this->assertSame('value2', $result, 'Other values should remain.');

        $result = Configure::consume('Test');
        $expected = ['key2' => 'value2'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testConsumeEmpty
     *
     * @return void
     */
    public function testConsumeEmpty()
    {
        Configure::write('Test', ['key' => 'value', 'key2' => 'value2']);

        $result = Configure::consume('');
        $this->assertNull($result);
    }

    /**
     * testConsumeOrFail method
     *
     * @return void
     */
    public function testConsumeOrFail()
    {
        $expected = 'ok';
        Configure::write('This.Key.Exists', $expected);
        $result = Configure::consumeOrFail('This.Key.Exists');
        $this->assertSame($expected, $result);
    }

    /**
     * testConsumeOrFail method
     *
     * @return void
     */
    public function testConsumeOrFailThrowingException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expected configuration key "This.Key.Does.Not.exist" not found');
        Configure::consumeOrFail('This.Key.Does.Not.exist');
    }
}
