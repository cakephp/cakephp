<?php
/**
 * ConfigureTest file
 *
 * Holds several tests
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
App::import('Core', 'config/PhpReader');

/**
 * ConfigureTest
 *
 * @package       cake.tests.cases.libs
 */
class ConfigureTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->_cacheDisable = Configure::read('Cache.disable');
		$this->_debug = Configure::read('debug');

		Configure::write('Cache.disable', true);
		App::build();
		App::objects('plugin', null, true);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_core_paths')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_core_paths');
		}
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_dir_map')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_dir_map');
		}
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_file_map')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_file_map');
		}
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_object_map')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_object_map');
		}
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'test.config.php')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'test.config.php');
		}
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'test.php')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'test.php');
		}
		Configure::write('debug', $this->_debug);
		Configure::write('Cache.disable', $this->_cacheDisable);
		Configure::drop('test');
	}

/**
 * testRead method
 *
 * @access public
 * @return void
 */
	function testRead() {
		$expected = 'ok';
		Configure::write('level1.level2.level3_1', $expected);
		Configure::write('level1.level2.level3_2', 'something_else');
		$result = Configure::read('level1.level2.level3_1');
		$this->assertEqual($expected, $result);

		$result = Configure::read('level1.level2.level3_2');
		$this->assertEqual($result, 'something_else');

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
 * @access public
 * @return void
 */
	function testWrite() {
		$writeResult = Configure::write('SomeName.someKey', 'myvalue');
		$this->assertTrue($writeResult);
		$result = Configure::read('SomeName.someKey');
		$this->assertEqual($result, 'myvalue');

		$writeResult = Configure::write('SomeName.someKey', null);
		$this->assertTrue($writeResult);
		$result = Configure::read('SomeName.someKey');
		$this->assertEqual($result, null);

		$expected = array('One' => array('Two' => array('Three' => array('Four' => array('Five' => 'cool')))));
		$writeResult = Configure::write('Key', $expected);
		$this->assertTrue($writeResult);

		$result = Configure::read('Key');
		$this->assertEqual($expected, $result);

		$result = Configure::read('Key.One');
		$this->assertEqual($expected['One'], $result);

		$result = Configure::read('Key.One.Two');
		$this->assertEqual($expected['One']['Two'], $result);

		$result = Configure::read('Key.One.Two.Three.Four.Five');
		$this->assertEqual('cool', $result);
	}

/**
 * test setting display_errors with debug.
 *
 * @return void
 */
	function testDebugSettingDisplayErrors() {
		Configure::write('debug', 0);
		$result = ini_get('display_errors');
		$this->assertEqual($result, 0);

		Configure::write('debug', 2);
		$result = ini_get('display_errors');
		$this->assertEqual($result, 1);
	}

/**
 * testDelete method
 *
 * @access public
 * @return void
 */
	function testDelete() {
		Configure::write('SomeName.someKey', 'myvalue');
		$result = Configure::read('SomeName.someKey');
		$this->assertEqual($result, 'myvalue');

		Configure::delete('SomeName.someKey');
		$result = Configure::read('SomeName.someKey');
		$this->assertTrue($result === null);

		Configure::write('SomeName', array('someKey' => 'myvalue', 'otherKey' => 'otherValue'));

		$result = Configure::read('SomeName.someKey');
		$this->assertEqual($result, 'myvalue');

		$result = Configure::read('SomeName.otherKey');
		$this->assertEqual($result, 'otherValue');

		Configure::delete('SomeName');

		$result = Configure::read('SomeName.someKey');
		$this->assertTrue($result === null);

		$result = Configure::read('SomeName.otherKey');
		$this->assertTrue($result === null);
	}

/**
 * testLoad method
 *
 * @expectedException RuntimeException
 * @return void
 */
	function testLoadExceptionOnNonExistantFile() {
		Configure::config('test', new PhpReader());
		$result = Configure::load('non_existing_configuration_file', 'test');
	}

/**
 * test load
 *
 * @return void
 */
	function testLoad() {
		Configure::config('test', new PhpReader(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config' . DS));

		$result = Configure::load('var_test', 'test');
		$this->assertTrue($result);
		
		$this->assertEquals('value', Configure::read('Read'));
	}

/**
 * testLoad method
 *
 * @access public
 * @return void
 */
	function testLoadPlugin() {
		App::build(array('plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)), true);
		Configure::config('test', new PhpReader());

		$result = Configure::load('test_plugin.load', 'test');
		$this->assertTrue($result);
		$expected = '/test_app/plugins/test_plugin/config/load.php';
		$config = Configure::read('plugin_load');
		$this->assertEqual($config, $expected);

		$result = Configure::load('test_plugin.more.load', 'test');
		$this->assertTrue($result);
		$expected = '/test_app/plugins/test_plugin/config/more.load.php';
		$config = Configure::read('plugin_more_load');
		$this->assertEqual($config, $expected);
	}

/**
 * testStore method
 *
 * @access public
 * @return void
 */
	function testStoreAndRestore() {
		Configure::write('Cache.disable', false);

		Configure::write('Testing', 'yummy');
		$this->assertTrue(Configure::store('store_test', 'default'));

		Configure::delete('Testing');
		$this->assertNull(Configure::read('Testing'));

		Configure::restore('store_test', 'default');
		$this->assertEquals('yummy', Configure::read('Testing'));

		Cache::delete('store_test', 'default');
	}

/**
 * test that store and restore only store/restore the provided data.
 *
 * @return void
 */
	function testStoreAndRestoreWithData() {
		Configure::write('Cache.disable', false);

		Configure::write('testing', 'value');
		Configure::store('store_test', 'default', array('store_test' => 'one'));
		Configure::delete('testing');
		$this->assertNull(Configure::read('store_test'), 'Calling store with data shouldnt modify runtime.');

		Configure::restore('store_test', 'default');
		$this->assertEquals('one', Configure::read('store_test'));
		$this->assertNull(Configure::read('testing'), 'Values that were not stored are not restored.');

		Cache::delete('store_test', 'default');
	}

/**
 * testVersion method
 *
 * @access public
 * @return void
 */
	function testVersion() {
		$result = Configure::version();
		$this->assertTrue(version_compare($result, '1.2', '>='));
	}

/**
 * test adding new readers.
 *
 * @return void
 */
	function testReaderSetup() {
		$reader = new PhpReader();
		Configure::config('test', $reader);
		$configured = Configure::configured();

		$this->assertTrue(in_array('test', $configured));

		$this->assertTrue(Configure::configured('test'));
		$this->assertFalse(Configure::configured('fake_garbage'));

		$this->assertTrue(Configure::drop('test'));
		$this->assertFalse(Configure::drop('test'), 'dropping things that do not exist should return false.');
	}

/**
 * test reader() throwing exceptions on missing interface.
 *
 * @expectedException Exception
 * @return void
 */
	function testReaderExceptionOnIncorrectClass() {
		$reader = new StdClass();
		Configure::config('test', $reader);
	}
}

