<?php
/**
 * PhpConfigReaderTest
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
 * @package       cake.tests.cases
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', 'config/PhpReader');

class PhpReaderTest extends CakeTestCase {
/**
 * setup
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->path = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config'. DS;
	}
/**
 * test reading files
 *
 * @return void
 */
	function testRead() {
		$reader = new PhpReader($this->path);
		$values = $reader->read('var_test');
		$this->assertEquals('value', $values['Read']);
		$this->assertEquals('buried', $values['Deep']['Deeper']['Deepest']);
	}

/**
 * Test an exception is thrown by reading files that don't exist.
 *
 * @expectedException ConfigureException
 * @return void
 */
	function testReadWithNonExistantFile() {
		$reader = new PhpReader($this->path);
		$reader->read('fake_values');
	}

/**
 * test reading an empty file.
 *
 * @expectedException RuntimeException
 * @return void
 */
	function testReadEmptyFile() {
		$reader = new PhpReader($this->path);
		$reader->read('empty');
	}

/**
 * test reading keys with ../ doesn't work
 *
 * @expectedException ConfigureException
 * @return void
 */
	function testReadWithDots() {
		$reader = new PhpReader($this->path);
		$reader->read('../empty');
	}

/**
 * test reading from plugins
 *
 * @return void
 */
	function testReadPluginValue() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		), true);
		$reader = new PhpReader($this->path);
		$result = $reader->read('TestPlugin.load');

		$this->assertTrue(isset($result['plugin_load']));
	}
}
