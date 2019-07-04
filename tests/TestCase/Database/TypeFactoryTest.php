<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database;

use Cake\Database\TypeFactory;
use Cake\Database\TypeInterface;
use Cake\TestSuite\TestCase;
use PDO;
use TestApp\Database\Type\BarType;
use TestApp\Database\Type\FooType;

/**
 * Tests TypeFactory class
 */
class TypeFactoryTest extends TestCase
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
    public function setUp(): void
    {
        $this->_originalMap = TypeFactory::getMap();
        parent::setUp();
    }

    /**
     * Restores Type class state
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        TypeFactory::setMap($this->_originalMap);
    }

    /**
     * Tests Type class is able to instantiate basic types
     *
     * @dataProvider basicTypesProvider
     * @return void
     */
    public function testBuildBasicTypes($name)
    {
        $type = TypeFactory::build($name);
        $this->assertInstanceOf(TypeInterface::class, $type);
        $this->assertEquals($name, $type->getName());
        $this->assertEquals($name, $type->getBaseType());
    }

    /**
     * provides a basics type list to be used as data provided for a test
     *
     * @return array
     */
    public function basicTypesProvider()
    {
        return [
            ['string'],
            ['text'],
            ['smallinteger'],
            ['tinyinteger'],
            ['integer'],
            ['biginteger'],
        ];
    }

    /**
     * Tests trying to build an unknown type throws exception
     *
     * @return void
     */
    public function testBuildUnknownType()
    {
        $this->expectException(\InvalidArgumentException::class);
        TypeFactory::build('foo');
    }

    /**
     * Tests that once a type with a name is instantiated, the reference is kept
     * for future use
     *
     * @return void
     */
    public function testInstanceRecycling()
    {
        $type = TypeFactory::build('integer');
        $this->assertSame($type, TypeFactory::build('integer'));
    }

    /**
     * Tests new types can be registered and built
     *
     * @return void
     */
    public function testMapAndBuild()
    {
        $map = TypeFactory::getMap();
        $this->assertNotEmpty($map);
        $this->assertArrayNotHasKey('foo', $map);

        $fooType = FooType::class;
        TypeFactory::map('foo', $fooType);
        $map = TypeFactory::getMap();
        $this->assertEquals($fooType, $map['foo']);
        $this->assertEquals($fooType, TypeFactory::getMap('foo'));

        TypeFactory::map('foo2', $fooType);
        $map = TypeFactory::getMap();
        $this->assertSame($fooType, $map['foo2']);
        $this->assertSame($fooType, TypeFactory::getMap('foo2'));

        $type = TypeFactory::build('foo2');
        $this->assertInstanceOf($fooType, $type);
    }

    /**
     * Tests new types set with set() are returned by buildAll()
     *
     * @return void
     */
    public function testSetAndBuild()
    {
        $types = TypeFactory::buildAll();
        $this->assertFalse(isset($types['foo']));

        TypeFactory::set('foo', new FooType());
        $types = TypeFactory::buildAll();
        $this->assertTrue(isset($types['foo']));
    }

    /**
     * Tests overwriting type map works for building
     *
     * @return void
     */
    public function testReMapAndBuild()
    {
        $fooType = FooType::class;
        TypeFactory::map('foo', $fooType);
        $type = TypeFactory::build('foo');
        $this->assertInstanceOf($fooType, $type);

        $barType = BarType::class;
        TypeFactory::map('foo', $barType);
        $type = TypeFactory::build('foo');
        $this->assertInstanceOf($barType, $type);
    }

    /**
     * Tests clear function in conjunction with map
     *
     * @return void
     */
    public function testClear()
    {
        $map = TypeFactory::getMap();
        $this->assertNotEmpty($map);

        $type = TypeFactory::build('float');
        TypeFactory::clear();

        $this->assertEmpty(TypeFactory::getMap());
        TypeFactory::setMap($map);
        $newMap = TypeFactory::getMap();

        $this->assertEquals(array_keys($map), array_keys($newMap));
        $this->assertEquals($map['integer'], $newMap['integer']);
        $this->assertEquals($type, TypeFactory::build('float'));
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
        $type = TypeFactory::build('biginteger');
        $integer = time() * time();
        $driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
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
        $type = TypeFactory::build('biginteger');
        $integer = time() * time();
        $driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
        $this->assertEquals(PDO::PARAM_INT, $type->toStatement($integer, $driver));
    }

    /**
     * Tests decimal from database are converted correctly to PHP
     *
     * @return void
     */
    public function testDecimalToPHP()
    {
        $type = TypeFactory::build('decimal');
        $driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();

        $this->assertSame('3.14159', $type->toPHP('3.14159', $driver));
        $this->assertSame('3.14159', $type->toPHP(3.14159, $driver));
        $this->assertSame('3', $type->toPHP(3, $driver));
    }

    /**
     * Tests integers from PHP are converted correctly to statement value
     *
     * @return void
     */
    public function testDecimalToStatement()
    {
        $type = TypeFactory::build('decimal');
        $string = '12.55';
        $driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
        $this->assertEquals(PDO::PARAM_STR, $type->toStatement($string, $driver));
    }

    /**
     * Test setting instances into the factory/registry.
     *
     * @return void
     */
    public function testSet()
    {
        $instance = $this->getMockBuilder(TypeInterface::class)->getMock();
        TypeFactory::set('random', $instance);
        $this->assertSame($instance, TypeFactory::build('random'));
    }
}
