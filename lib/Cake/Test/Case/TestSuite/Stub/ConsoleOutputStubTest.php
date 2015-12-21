<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.8
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses("ConsoleOutputStub", "TestSuite/Stub");

/*
 * ConsoleOutputStub test
 */
class ConsoleOutputStubTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->stub = new ConsoleOutputStub();
	}

/**
 * Test that stub can be used as an instance of ConsoleOutput
 *
 * @return void
 */
	public function testCanActAsConsoleOutput() {
		$this->assertInstanceOf("ConsoleOutput", $this->stub);
	}

/**
 * Test write method
 *
 * @return void
 */
	public function testWrite() {
		$this->stub->write(array("foo", "bar", "baz"));
		$this->assertEquals(array("foo", "bar", "baz"), $this->stub->messages());
	}

/**
 * Test overwrite method
 *
 * @return void
 */
	public function testOverwrite() {
		$this->stub->write(array("foo", "bar", "baz"));
		$this->stub->overwrite("bat");
		$this->assertEquals(array("foo", "bar", "baz", "", "bat"), $this->stub->messages());
	}
}