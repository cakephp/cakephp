<?php
/**
 * ObjectTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Object;
use Cake\Core\Plugin;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * TestObject class
 *
 */
class TestObject extends Object {

/**
 * firstName property
 *
 * @var string 'Joel'
 */
	public $firstName = 'Joel';

/**
 * lastName property
 *
 * @var string 'Moss'
 */
	public $lastName = 'Moss';

/**
 * methodCalls property
 *
 * @var array
 */
	public $methodCalls = array();

/**
 * emptyMethod method
 *
 * @return void
 */
	public function emptyMethod() {
		$this->methodCalls[] = 'emptyMethod';
	}

/**
 * oneParamMethod method
 *
 * @param mixed $param
 * @return void
 */
	public function oneParamMethod($param) {
		$this->methodCalls[] = array('oneParamMethod' => array($param));
	}

/**
 * twoParamMethod method
 *
 * @param mixed $param
 * @param mixed $paramTwo
 * @return void
 */
	public function twoParamMethod($param, $paramTwo) {
		$this->methodCalls[] = array('twoParamMethod' => array($param, $paramTwo));
	}

/**
 * threeParamMethod method
 *
 * @param mixed $param
 * @param mixed $paramTwo
 * @param mixed $paramThree
 * @return void
 */
	public function threeParamMethod($param, $paramTwo, $paramThree) {
		$this->methodCalls[] = array('threeParamMethod' => array($param, $paramTwo, $paramThree));
	}

/**
 * fourParamMethod method
 *
 * @param mixed $param
 * @param mixed $paramTwo
 * @param mixed $paramThree
 * @param mixed $paramFour
 * @return void
 */
	public function fourParamMethod($param, $paramTwo, $paramThree, $paramFour) {
		$this->methodCalls[] = array('fourParamMethod' => array($param, $paramTwo, $paramThree, $paramFour));
	}

/**
 * fiveParamMethod method
 *
 * @param mixed $param
 * @param mixed $paramTwo
 * @param mixed $paramThree
 * @param mixed $paramFour
 * @param mixed $paramFive
 * @return void
 */
	public function fiveParamMethod($param, $paramTwo, $paramThree, $paramFour, $paramFive) {
		$this->methodCalls[] = array('fiveParamMethod' => array($param, $paramTwo, $paramThree, $paramFour, $paramFive));
	}

/**
 * crazyMethod method
 *
 * @param mixed $param
 * @param mixed $paramTwo
 * @param mixed $paramThree
 * @param mixed $paramFour
 * @param mixed $paramFive
 * @param mixed $paramSix
 * @param mixed $paramSeven
 * @return void
 */
	public function crazyMethod($param, $paramTwo, $paramThree, $paramFour, $paramFive, $paramSix, $paramSeven = null) {
		$this->methodCalls[] = array('crazyMethod' => array($param, $paramTwo, $paramThree, $paramFour, $paramFive, $paramSix, $paramSeven));
	}

/**
 * methodWithOptionalParam method
 *
 * @param mixed $param
 * @return void
 */
	public function methodWithOptionalParam($param = null) {
		$this->methodCalls[] = array('methodWithOptionalParam' => array($param));
	}

/**
 * undocumented function
 *
 * @param array $properties
 * @return void
 */
	public function set($properties = array()) {
		return parent::_set($properties);
	}

}

/**
 * Object Test class
 *
 */
class ObjectTest extends TestCase {

/**
 * fixtures
 *
 * @var string
 */
	public $fixtures = array('core.post', 'core.test_plugin_comment', 'core.comment');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->object = new TestObject();
		Configure::write('App.namespace', 'TestApp');
		Configure::write('Security.salt', 'not-the-default');
		Log::drop('stdout');
		Log::drop('stderr');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload();
		Log::reset();
		unset($this->object);
	}

/**
 * testLog method
 *
 * @return void
 */
	public function testLog() {
		if (file_exists(LOGS . 'error.log')) {
			unlink(LOGS . 'error.log');
		}
		$this->assertTrue($this->object->log('Test warning 1'));
		$this->assertTrue($this->object->log(array('Test' => 'warning 2')));
		$result = file(LOGS . 'error.log');
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Test warning 1$/', $result[0]);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Error: Array$/', $result[1]);
		$this->assertRegExp('/^\($/', $result[2]);
		$this->assertRegExp('/\[Test\] => warning 2$/', $result[3]);
		$this->assertRegExp('/^\)$/', $result[4]);
		unlink(LOGS . 'error.log');

		$this->assertTrue($this->object->log('Test warning 1', LOG_WARNING));
		$this->assertTrue($this->object->log(array('Test' => 'warning 2'), LOG_WARNING));
		$result = file(LOGS . 'error.log');
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning 1$/', $result[0]);
		$this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Array$/', $result[1]);
		$this->assertRegExp('/^\($/', $result[2]);
		$this->assertRegExp('/\[Test\] => warning 2$/', $result[3]);
		$this->assertRegExp('/^\)$/', $result[4]);
		unlink(LOGS . 'error.log');
	}

/**
 * testSet method
 *
 * @return void
 */
	public function testSet() {
		$this->object->set('a string');
		$this->assertEquals('Joel', $this->object->firstName);

		$this->object->set(array('firstName'));
		$this->assertEquals('Joel', $this->object->firstName);

		$this->object->set(array('firstName' => 'Ashley'));
		$this->assertEquals('Ashley', $this->object->firstName);

		$this->object->set(array('firstName' => 'Joel', 'lastName' => 'Moose'));
		$this->assertEquals('Joel', $this->object->firstName);
		$this->assertEquals('Moose', $this->object->lastName);
	}

/**
 * testToString method
 *
 * @return void
 */
	public function testToString() {
		$result = strtolower($this->object->toString());
		$this->assertEquals(strtolower(__NAMESPACE__) . '\testobject', $result);
	}

/**
 * testMethodDispatching method
 *
 * @return void
 */
	public function testMethodDispatching() {
		$this->object->emptyMethod();
		$expected = array('emptyMethod');
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->oneParamMethod('Hello');
		$expected[] = array('oneParamMethod' => array('Hello'));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->twoParamMethod(true, false);
		$expected[] = array('twoParamMethod' => array(true, false));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->threeParamMethod(true, false, null);
		$expected[] = array('threeParamMethod' => array(true, false, null));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->crazyMethod(1, 2, 3, 4, 5, 6, 7);
		$expected[] = array('crazyMethod' => array(1, 2, 3, 4, 5, 6, 7));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object = new TestObject();
		$this->assertSame($this->object->methodCalls, array());

		$this->object->dispatchMethod('emptyMethod');
		$expected = array('emptyMethod');
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('oneParamMethod', array('Hello'));
		$expected[] = array('oneParamMethod' => array('Hello'));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('twoParamMethod', array(true, false));
		$expected[] = array('twoParamMethod' => array(true, false));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('threeParamMethod', array(true, false, null));
		$expected[] = array('threeParamMethod' => array(true, false, null));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('fourParamMethod', array(1, 2, 3, 4));
		$expected[] = array('fourParamMethod' => array(1, 2, 3, 4));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('fiveParamMethod', array(1, 2, 3, 4, 5));
		$expected[] = array('fiveParamMethod' => array(1, 2, 3, 4, 5));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('crazyMethod', array(1, 2, 3, 4, 5, 6, 7));
		$expected[] = array('crazyMethod' => array(1, 2, 3, 4, 5, 6, 7));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('methodWithOptionalParam', array('Hello'));
		$expected[] = array('methodWithOptionalParam' => array("Hello"));
		$this->assertSame($this->object->methodCalls, $expected);

		$this->object->dispatchMethod('methodWithOptionalParam');
		$expected[] = array('methodWithOptionalParam' => array(null));
		$this->assertSame($this->object->methodCalls, $expected);
	}

}
