<?php
/**
 * NumberHelperTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');
App::uses('NumberHelper', 'View/Helper');

/**
 * NumberHelperTestObject class
 */
class NumberHelperTestObject extends NumberHelper {

	public function attach(CakeNumberMock $cakeNumber) {
		$this->_engine = $cakeNumber;
	}

	public function engine() {
		return $this->_engine;
	}

}

/**
 * CakeNumberMock class
 */
class CakeNumberMock {
}

/**
 * NumberHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class NumberHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->View = new View(null);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->View);
	}

/**
 * test CakeNumber class methods are called correctly
 */
	public function testNumberHelperProxyMethodCalls() {
		$methods = array(
			'precision', 'toReadableSize', 'toPercentage', 'format',
			'currency', 'addFormat',
			);
		$CakeNumber = $this->getMock('CakeNumberMock', $methods);
		$Number = new NumberHelperTestObject($this->View, array('engine' => 'CakeNumberMock'));
		$Number->attach($CakeNumber);
		foreach ($methods as $method) {
			$CakeNumber->expects($this->at(0))->method($method);
			$Number->{$method}('who', 'what', 'when', 'where', 'how');
		}
	}

/**
 * test engine override
 */
	public function testEngineOverride() {
		App::build(array(
			'Utility' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Utility' . DS)
		), App::REGISTER);
		$Number = new NumberHelperTestObject($this->View, array('engine' => 'TestAppEngine'));
		$this->assertInstanceOf('TestAppEngine', $Number->engine());

		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load('TestPlugin');
		$Number = new NumberHelperTestObject($this->View, array('engine' => 'TestPlugin.TestPluginEngine'));
		$this->assertInstanceOf('TestPluginEngine', $Number->engine());
		CakePlugin::unload('TestPlugin');
	}

}
