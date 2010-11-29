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
}