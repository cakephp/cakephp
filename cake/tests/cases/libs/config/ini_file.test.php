<?php

App::import('Core', 'config/IniFile');

class IniFileTest extends CakeTestCase {

/**
 * The test file that will be read.
 *
 * @var string
 */
	var $file;

/**
 * setup
 *
 * @return void
 */
	function setup() {
		parent::setup();
		$this->file = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config'. DS . 'acl.ini.php';
	}

/**
 * test constrction
 *
 * @return void
 */
	function testConstruct() {
		$config = new IniFile($this->file);

		$this->assertTrue(isset($config['admin']));
		$this->assertTrue(isset($config['paul']['groups']));
		$this->assertEquals('ads', $config['admin']['deny']);
	}

/**
 * no other sections should exist.
 *
 * @return void
 */
	function testReadingOnlyOneSection() {
		$config = new IniFile($this->file, 'admin');

		$this->assertTrue(isset($config['groups']));
		$this->assertEquals('administrators', $config['groups']);
	}

/**
 * test getting all the values as an array
 *
 * @return void
 */
	function testAsArray() {
		$config = new IniFile($this->file);
		$content = $config->asArray();
		
		$this->assertTrue(isset($content['admin']['groups']));
		$this->assertTrue(isset($content['paul']['groups']));
	}

/**
 * test that values cannot be modified
 *
 * @expectedException LogicException
 */
	function testNoModification() {
		$config = new IniFile($this->file);
		$config['admin'] = 'something';
	}
}