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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * ConfigureTest
 *
 */
class ConfigureTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Cache::disable();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
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
        $this->assertEquals($expected, $result);

        $result = Configure::read('level1.level2.level3_2');
        $this->assertEquals('something_else', $result);

        $result = Configure::read('debug');
        $this->assertTrue($result >= 0);

        $result = Configure::read();
        $this->assertTrue(is_array($result));
        $this->assertTrue(isset($result['debug']));
        $this->assertTrue(isset($result['level1']));

        $result = Configure::read('something_I_just_made_up_now');
        $this->assertEquals(null, $result, 'Missing key should return null.');
    }

    /**
     * testWrite method
     *
     * @return void
     */
    public function testWrite()
    {
        $writeResult = Configure::write('SomeName.someKey', 'myvalue');
        $this->assertTrue($writeResult);
        $result = Configure::read('SomeName.someKey');
        $this->assertEquals('myvalue', $result);

        $writeResult = Configure::write('SomeName.someKey', null);
        $this->assertTrue($writeResult);
        $result = Configure::read('SomeName.someKey');
        $this->assertEquals(null, $result);

        $expected = ['One' => ['Two' => ['Three' => ['Four' => ['Five' => 'cool']]]]];
        $writeResult = Configure::write('Key', $expected);
        $this->assertTrue($writeResult);

        $result = Configure::read('Key');
        $this->assertEquals($expected, $result);

        $result = Configure::read('Key.One');
        $this->assertEquals($expected['One'], $result);

        $result = Configure::read('Key.One.Two');
        $this->assertEquals($expected['One']['Two'], $result);

        $result = Configure::read('Key.One.Two.Three.Four.Five');
        $this->assertEquals('cool', $result);

        Configure::write('one.two.three.four', '4');
        $result = Configure::read('one.two.three.four');
        $this->assertEquals('4', $result);
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
        $this->assertEquals(0, $result);

        Configure::write('debug', true);
        $result = ini_get('display_errors');
        $this->assertEquals(1, $result);
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
        $this->assertEquals('myvalue', $result);

        Configure::delete('SomeName.someKey');
        $result = Configure::read('SomeName.someKey');
        $this->assertTrue($result === null);

        Configure::write('SomeName', ['someKey' => 'myvalue', 'otherKey' => 'otherValue']);

        $result = Configure::read('SomeName.someKey');
        $this->assertEquals('myvalue', $result);

        $result = Configure::read('SomeName.otherKey');
        $this->assertEquals('otherValue', $result);

        Configure::delete('SomeName');

        $result = Configure::read('SomeName.someKey');
        $this->assertTrue($result === null);

        $result = Configure::read('SomeName.otherKey');
        $this->assertTrue($result === null);
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
        $this->assertTrue(Configure::write('ConfigureTestCase', 0));
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertTrue(Configure::write('ConfigureTestCase', '0'));
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertTrue(Configure::write('ConfigureTestCase', false));
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertTrue(Configure::write('ConfigureTestCase', null));
        $this->assertFalse(Configure::check('ConfigureTestCase'));
    }

    /**
     * testCheckKeyWithSpaces method
     *
     * @return void
     */
    public function testCheckKeyWithSpaces()
    {
        $this->assertTrue(Configure::write('Configure Test', "test"));
        $this->assertTrue(Configure::check('Configure Test'));
        Configure::delete('Configure Test');

        $this->assertTrue(Configure::write('Configure Test.Test Case', "test"));
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
        $this->assertFalse(Configure::check(null));
    }

    /**
     * testLoad method
     *
     * @expectedException \RuntimeException
     * @return void
     */
    public function testLoadExceptionOnNonExistantFile()
    {
        Configure::config('test', new PhpConfig());
        Configure::load('non_existing_configuration_file', 'test');
    }

    /**
     * test load method for default config creation
     *
     * @return void
     */
    public function testLoadDefaultConfig()
    {
        try {
            Configure::load('non_existing_configuration_file');
        } catch (\Exception $e) {
            $result = Configure::configured('default');
            $this->assertTrue($result);
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

        $this->assertEquals('value', Configure::read('Read'));

        $result = Configure::load('var_test2', 'test', true);
        $this->assertTrue($result);

        $this->assertEquals('value2', Configure::read('Read'));
        $this->assertEquals('buried2', Configure::read('Deep.Second.SecondDeepest'));
        $this->assertEquals('buried', Configure::read('Deep.Deeper.Deepest'));
        $this->assertEquals('Overwrite', Configure::read('TestAcl.classname'));
        $this->assertEquals('one', Configure::read('TestAcl.custom'));
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

        $this->assertEquals('value', Configure::read('Read'));

        $result = Configure::load('var_test2', 'test', false);
        $this->assertTrue($result);

        $this->assertEquals('value2', Configure::read('Read'));
        $this->assertEquals('buried2', Configure::read('Deep.Second.SecondDeepest'));
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
        $this->assertEquals('value', Configure::read('my_key'), 'Should not overwrite existing data.');
        $this->assertEquals('value', Configure::read('Read'), 'Should load new data.');
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
        $this->assertEquals('value', Configure::read('Read'), 'Should load new data.');
        $this->assertEquals('buried', Configure::read('Deep.Deeper.Deepest'), 'Should load new data');
        $this->assertEquals('old', Configure::read('Deep.old'), 'Should not destroy old data.');
        $this->assertEquals('value', Configure::read('my_key'), 'Should not destroy data.');
        $this->assertEquals('Original', Configure::read('TestAcl.classname'), 'No arrays');
    }

    /**
     * testLoad method
     *
     * @return void
     */
    public function testLoadPlugin()
    {
        Configure::config('test', new PhpConfig());
        Plugin::load('TestPlugin');
        $result = Configure::load('TestPlugin.load', 'test');
        $this->assertTrue($result);
        $expected = '/test_app/Plugin/TestPlugin/Config/load.php';
        $config = Configure::read('plugin_load');
        $this->assertEquals($expected, $config);

        $result = Configure::load('TestPlugin.more.load', 'test');
        $this->assertTrue($result);
        $expected = '/test_app/Plugin/TestPlugin/Config/more.load.php';
        $config = Configure::read('plugin_more_load');
        $this->assertEquals($expected, $config);
        Plugin::unload();
    }

    /**
     * testStore method
     *
     * @return void
     */
    public function testStoreAndRestore()
    {
        Cache::enable();
        Cache::config('configure', [
            'className' => 'File',
            'path' => TMP . 'tests'
        ]);

        Configure::write('Testing', 'yummy');
        $this->assertTrue(Configure::store('store_test', 'configure'));

        Configure::delete('Testing');
        $this->assertNull(Configure::read('Testing'));

        Configure::restore('store_test', 'configure');
        $this->assertEquals('yummy', Configure::read('Testing'));

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
        Cache::config('configure', [
            'className' => 'File',
            'path' => TMP . 'tests'
        ]);

        Configure::write('testing', 'value');
        Configure::store('store_test', 'configure', ['store_test' => 'one']);
        Configure::delete('testing');
        $this->assertNull(Configure::read('store_test'), 'Calling store with data shouldn\'t modify runtime.');

        Configure::restore('store_test', 'configure');
        $this->assertEquals('one', Configure::read('store_test'));
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
        $result = Configure::version();
        $this->assertTrue(version_compare($result, '1.2', '>='));
    }

    /**
     * test adding new engines.
     *
     * @return void
     */
    public function testEngineSetup()
    {
        $engine = new PhpConfig();
        Configure::config('test', $engine);
        $configured = Configure::configured();

        $this->assertTrue(in_array('test', $configured));

        $this->assertTrue(Configure::configured('test'));
        $this->assertFalse(Configure::configured('fake_garbage'));

        $this->assertTrue(Configure::drop('test'));
        $this->assertFalse(Configure::drop('test'), 'dropping things that do not exist should return false.');
    }

    /**
     * test engine() throwing exceptions on missing interface.
     *
     * @return void
     */
    public function testEngineExceptionOnIncorrectClass()
    {
        $this->skipIf(PHP_VERSION_ID >= 70000);

        $message = '/^Argument 2 passed to Cake\\\Core\\\Configure::config\(\) must implement interface Cake\\\Core\\\Configure\\\ConfigEngineInterface, instance of stdClass given.*/';
        $this->setExpectedExceptionRegExp('PHPUnit_Framework_Error', $message);
        Configure::config('test', new \stdClass());
    }

    /**
     * test engine() throwing exceptions on missing interface (PHP 7)
     *
     * @return void
     */
    public function testEngineExceptionOnIncorrectClassPhp7()
    {
        $this->skipIf(PHP_VERSION_ID < 70000);

        try {
            Configure::config('test', new \stdClass());
            $this->fail();
        } catch (\BaseException $e) {
            $expectedMessage = 'Argument 2 passed to Cake\Core\Configure::config() must implement interface Cake\Core\Configure\ConfigEngineInterface, instance of stdClass given';
            $this->assertContains($expectedMessage, $e->getMessage());
        }
    }

    /**
     * Test that clear wipes all values.
     *
     * @return void
     */
    public function testClear()
    {
        Configure::write('test', 'value');
        $this->assertTrue(Configure::clear());
        $this->assertNull(Configure::read('debug'));
        $this->assertNull(Configure::read('test'));
    }

    /**
     * @expectedException \Cake\Core\Exception\Exception
     * @return void
     */
    public function testDumpNoAdapter()
    {
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
        $this->assertTrue($result > 0);
        $result = file_get_contents(TMP . 'config_test.php');
        $this->assertContains('<?php', $result);
        $this->assertContains('return ', $result);
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
        $this->assertTrue($result > 0);
        $result = file_get_contents(TMP . 'config_test.php');
        $this->assertContains('<?php', $result);
        $this->assertContains('return ', $result);
        $this->assertContains('Error', $result);
        $this->assertNotContains('debug', $result);

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
        $this->assertEquals('value', $result);

        $result = Configure::read('Test.key2');
        $this->assertEquals('value2', $result, 'Other values should remain.');

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

        $result = Configure::consume(null);
        $this->assertNull($result);
    }
}
