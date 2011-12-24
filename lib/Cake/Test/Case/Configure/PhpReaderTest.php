<?php
/**
 * PhpConfigReaderTest
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Configure
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('PhpReader', 'Configure');

class PhpReaderTest extends CakeTestCase {
/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->path = CAKE . 'Test' . DS . 'test_app' . DS . 'Config'. DS;
	}

/**
 * test reading files
 *
 * @return void
 */
	public function testRead() {
		$reader = new PhpReader($this->path);
		$values = $reader->read('var_test');
		$this->assertEquals('value', $values['Read']);
		$this->assertEquals('buried', $values['Deep']['Deeper']['Deepest']);

		$values = $reader->read('var_test.php');
		$this->assertEquals('value', $values['Read']);
	}

/**
 * Test an exception is thrown by reading files that don't exist.
 *
 * @expectedException ConfigureException
 * @return void
 */
	public function testReadWithNonExistantFile() {
		$reader = new PhpReader($this->path);
		$reader->read('fake_values');
	}

/**
 * test reading an empty file.
 *
 * @expectedException RuntimeException
 * @return void
 */
	public function testReadEmptyFile() {
		$reader = new PhpReader($this->path);
		$reader->read('empty');
	}

/**
 * test reading keys with ../ doesn't work
 *
 * @expectedException ConfigureException
 * @return void
 */
	public function testReadWithDots() {
		$reader = new PhpReader($this->path);
		$reader->read('../empty');
	}

/**
 * test reading from plugins
 *
 * @return void
 */
	public function testReadPluginValue() {
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), true);
		CakePlugin::load('TestPlugin');
		$reader = new PhpReader($this->path);
		$result = $reader->read('TestPlugin.load');
		$this->assertTrue(isset($result['plugin_load']));

		$result = $reader->read('TestPlugin.load.php');
		$this->assertTrue(isset($result['plugin_load']));
		CakePlugin::unload();
	}
}
