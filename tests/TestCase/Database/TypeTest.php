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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\Type;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Mock class for testing type registering
 *
 */
class FooType extends \Cake\Database\Type
{

    public function getBaseType()
    {
        return 'text';
    }
}

/**
 * Tests Type class
 */
class TypeTest extends TestCase
{

    /**
     * Original type map
     *
     * @var array
     */
    protected $_originalMap = [];

    /**
     * Backup original Type class state
     *
     * @return void
     */
    public function setUp()
    {
        $this->_originalMap = Type::map();
        parent::setUp();
    }

    /**
     * Restores Type class state
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        Type::map($this->_originalMap);
    }

    /**
     * Tests Type class is able to instantiate basic types
     *
     * @dataProvider basicTypesProvider
     * @return void
     */
    public function testBuildBasicTypes($name)
    {
        $type = Type::build($name);
        $this->assertInstanceOf('Cake\Database\Type', $type);
        $this->assertEquals($name, $type->getName());
        $this->assertEquals($name, $type->getBaseType());
    }

    /**
     * provides a basics type list to be used as data provided for a test
     *
     * @return void
     */
    public function basicTypesProvider()
    {
        return [
            ['string'],
            ['text'],
        ];
    }

    /**
     * Tests trying to build an unknown type throws exception
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testBuildUnknownType()
    {
        Type::build('foo');
    }

    /**
     * Tests that once a type with a name is instantiated, the reference is kept
     * for future use
     *
     * @return void
     */
    public function testInstanceRecycling()
    {
        $type = Type::build('integer');
        $this->assertSame($type, Type::build('integer'));
    }

    /**
     * Tests new types can be registered and built
     *
     * @return void
     */
    public function testMapAndBuild()
    {
        $map = Type::map();
        $this->assertNotEmpty($map);
        $this->assertFalse(isset($map['foo']));

        $fooType = __NAMESPACE__ . '\FooType';
        Type::map('foo', $fooType);
        $map = Type::map();
        $this->assertEquals($fooType, $map['foo']);
        $this->assertEquals($fooType, Type::map('foo'));

        $type = Type::build('foo');
        $this->assertInstanceOf($fooType, $type);
        $this->assertEquals('foo', $type->getName());
        $this->assertEquals('text', $type->getBaseType());

        $fooType = new FooType();
        Type::map('foo2', $fooType);
        $map = Type::map();
        $this->assertSame($fooType, $map['foo2']);
        $this->assertSame($fooType, Type::map('foo2'));
    }

    /**
     * Tests clear function in conjunction with map
     *
     * @return void
     */
    public function testClear()
    {
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
     * Tests bigintegers from database are converted correctly to PHP
     *
     * @return void
     */
    public function testBigintegerToPHP()
    {
        $this->skipIf(
            PHP_INT_SIZE === 4,
            'This test requires a php version compiled for 64 bits'
        );
        $type = Type::build('biginteger');
        $integer = time() * time();
        $driver = $this->getMock('\Cake\Database\Driver');
        $this->assertSame($integer, $type->toPHP($integer, $driver));
        $this->assertSame($integer, $type->toPHP('' . $integer, $driver));
        $this->assertSame(3, $type->toPHP(3.57, $driver));
    }

    /**
     * Tests bigintegers from PHP are converted correctly to statement value
     *
     * @return void
     */
    public function testBigintegerToStatement()
    {
        $type = Type::build('biginteger');
        $integer = time() * time();
        $driver = $this->getMock('\Cake\Database\Driver');
        $this->assertEquals(PDO::PARAM_INT, $type->toStatement($integer, $driver));
    }

    /**
     * Tests decimal from database are converted correctly to PHP
     *
     * @return void
     */
    public function testDecimalToPHP()
    {
        $type = Type::build('decimal');
        $driver = $this->getMock('\Cake\Database\Driver');

        $this->assertSame(3.14159, $type->toPHP('3.14159', $driver));
        $this->assertSame(3.14159, $type->toPHP(3.14159, $driver));
        $this->assertSame(3.0, $type->toPHP(3, $driver));
        $this->assertSame(1, $type->toPHP(['3', '4'], $driver));
    }

    /**
     * Tests integers from PHP are converted correctly to statement value
     *
     * @return void
     */
    public function testDecimalToStatement()
    {
        $type = Type::build('decimal');
        $string = '12.55';
        $driver = $this->getMock('\Cake\Database\Driver');
        $this->assertEquals(PDO::PARAM_STR, $type->toStatement($string, $driver));
    }

    /**
     * Test setting instances into the factory/registry.
     *
     * @return void
     */
    public function testSet()
    {
        $instance = $this->getMock('Cake\Database\Type');
        Type::set('random', $instance);
        $this->assertSame($instance, Type::build('random'));
    }
}
