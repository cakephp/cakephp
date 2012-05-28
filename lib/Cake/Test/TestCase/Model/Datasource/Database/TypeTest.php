<?php
/**
 * 
 * PHP Version 5.x
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 3.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Model\Datasource\Database;

use Cake\Model\Datasource\Database\Type,
	PDO;

/**
 * Mock class for testing type registering
 *
 **/
class FooType extends \Cake\Model\Datasource\Database\Type {

}

/**
 * Tests Type class
 *
 **/
class TypeTest extends \Cake\TestSuite\TestCase {

/**
 * Original type map
 *
 * @var array
 **/
	protected $_originalMap = array();

/**
 * Backsup original Type class state
 *
 * @return void
 **/
	public function setUp() {
		$this->_originalMap = Type::map();
		parent::setUp();
	}

/**
 * Restores Type class state
 *
 * @return void
 **/
	public function tearDown() {
		Type::map($this->_originalMap);
	}

/**
 * Tests Type class is able to instantiate basic types
 *
 * @dataProvider basicTypesProvider
 * @return void
 **/
	public function testBuildBasicTypes($name) {
		$type = Type::build($name);
		$this->assertInstanceOf('\Cake\Model\Datasource\Database\Type', $type);
		$this->assertEquals($name, $type->getName());
	}

/**
 * provides a basics type list to be used as data proviced for a test
 *
 * @return void
 **/
	public function basicTypesProvider() {
		return array(
			array('float'),
			array('integer'),
			array('string'),
			array('text')
		);
	}

/**
 * Tests trying to build an uknown type throws exception
 *
 * @expectedException InvalidArgumentException
 * @return void
 **/
	public function testBuildUnknownType() {
		Type::build('foo');
	}

/**
 * Tests that once a type with a name is instantiated, the reference is kept
 * for future use
 *
 * @return void
 **/
	public function testInstanceRecycling() {
		$type = Type::build('integer');
		$this->assertSame($type, Type::build('integer'));
	}

/**
 * Tests new types can be registered and built
 *
 * @return void
 **/
	public function testMapAndBuild() {
		$map = Type::map();
		$this->assertNotEmpty($map);
		$this->assertFalse(isset($map['foo']));

		$fooType =  __NAMESPACE__ . '\FooType';
		Type::map('foo', $fooType);
		$map = Type::map();
		$this->assertEquals($fooType, $map['foo']);
		$this->assertEquals($fooType, Type::map('foo'));

		$type = Type::build('foo');
		$this->assertInstanceOf($fooType, $type);
		$this->assertEquals('foo', $type->getName());
	}

/**
 * Tests clear function in conjuntion with map
 *
 * @return void
 **/
	public function testClear() {
		$map = Type::map();
		$this->assertNotEmpty($map);

		$type = Type::build('float');
		Type::clear();

		$this->assertEmpty(Type::map());
		Type::map($map);
		$this->assertEquals($map, Type::map());

		$this->assertNotSame($type, Type::build('float'));
	}

/**
 * Tests floats from database are converted correctly to PHP
 *
 * @return void
 **/
	public function testFloatToPHP() {
		$type = Type::build('float');
		$float = '3.14159';
		$driver = $this->getMock('\Cake\Model\Datasource\Database\Driver');
		$this->assertEquals(3.14159, $type->toPHP($float, $driver));
		$this->assertEquals(3.14159, $type->toPHP(3.14159, $driver));
		$this->assertEquals(3.00, $type->toPHP(3, $driver));
	}

/**
 * Tests floats from PHP are converted correctly to statement value
 *
 * @return void
 **/

	public function testFloatToStatement() {
		$type = Type::build('float');
		$float = '3.14159';
		$driver = $this->getMock('\Cake\Model\Datasource\Database\Driver');
		$this->assertEquals(PDO::PARAM_STR, $type->toStatement($float, $driver));
	}

/**
 * Tests integers from database are converted correctly to PHP
 *
 * @return void
 **/
	public function testIntegerToPHP() {
		$type = Type::build('integer');
		$integer = '3';
		$driver = $this->getMock('\Cake\Model\Datasource\Database\Driver');
		$this->assertEquals(3, $type->toPHP($integer, $driver));
		$this->assertEquals(3, $type->toPHP(3, $driver));
		$this->assertEquals(3, $type->toPHP(3.57, $driver));
	}

/**
 * Tests integers from PHP are converted correctly to statement value
 *
 * @return void
 **/
	public function testIntegerToStatement() {
		$type = Type::build('integer');
		$integer = '3';
		$driver = $this->getMock('\Cake\Model\Datasource\Database\Driver');
		$this->assertEquals(PDO::PARAM_INT, $type->toStatement($integer, $driver));
	}

/**
 * Tests integers from database are converted correctly to PHP
 *
 * @return void
 **/
	public function testStringToPHP() {
		$type = Type::build('string');
		$string = 'foo';
		$driver = $this->getMock('\Cake\Model\Datasource\Database\Driver');
		$this->assertEquals('foo', $type->toPHP($string, $driver));
		$this->assertEquals('3', $type->toPHP(3, $driver));
		$this->assertEquals('3.14159', $type->toPHP(3.14159, $driver));
	}

/**
 * Tests integers from PHP are converted correctly to statement value
 *
 * @return void
 **/
	public function testStringToStatement() {
		$type = Type::build('string');
		$string = '3';
		$driver = $this->getMock('\Cake\Model\Datasource\Database\Driver');
		$this->assertEquals(PDO::PARAM_STR, $type->toStatement($string, $driver));
	}

/**
 * Tests integers from database are converted correctly to PHP
 *
 * @return void
 **/
	public function testTextToPHP() {
		$type = Type::build('string');
		$string = 'foo';
		$driver = $this->getMock('\Cake\Model\Datasource\Database\Driver');
		$this->assertEquals('foo', $type->toPHP($string, $driver));
		$this->assertEquals('3', $type->toPHP(3, $driver));
		$this->assertEquals('3.14159', $type->toPHP(3.14159, $driver));
	}

/**
 * Tests integers from PHP are converted correctly to statement value
 *
 * @return void
 **/
	public function testTextToStatement() {
		$type = Type::build('string');
		$string = '3';
		$driver = $this->getMock('\Cake\Model\Datasource\Database\Driver');
		$this->assertEquals(PDO::PARAM_STR, $type->toStatement($string, $driver));
	}

}
