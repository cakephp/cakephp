<?php
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
 * @since         3.1.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Type;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Test for the Boolean type.
 */
class BoolTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\BoolType
     */
    public $type;

    /**
     * @var \Cake\Database\Driver
     */
    public $driver;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = Type::build('boolean');
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatabase()
    {
        $this->assertNull($this->type->toDatabase(null, $this->driver));
        $this->assertTrue($this->type->toDatabase(true, $this->driver));
        $this->assertFalse($this->type->toDatabase(false, $this->driver));
        $this->assertTrue($this->type->toDatabase(1, $this->driver));
        $this->assertFalse($this->type->toDatabase(0, $this->driver));
        $this->assertTrue($this->type->toDatabase('1', $this->driver));
        $this->assertFalse($this->type->toDatabase('0', $this->driver));
    }

    /**
     * Test converting an array to boolean results in an exception
     *
     * @return void
     */
    public function testToDatabaseInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->type->toDatabase([1, 2], $this->driver);
    }

    /**
     * Tests that passing an invalid value will throw an exception
     *
     * @return void
     */
    public function testToDatabaseInvalidArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->type->toDatabase([1, 2, 3], $this->driver);
    }

    /**
     * Test converting string booleans to PHP values.
     *
     * @return void
     */
    public function testToPHP()
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
        $this->assertTrue($this->type->toPHP(1, $this->driver));
        $this->assertTrue($this->type->toPHP('1', $this->driver));
        $this->assertTrue($this->type->toPHP('TRUE', $this->driver));
        $this->assertTrue($this->type->toPHP('true', $this->driver));
        $this->assertTrue($this->type->toPHP(true, $this->driver));

        $this->assertFalse($this->type->toPHP(0, $this->driver));
        $this->assertFalse($this->type->toPHP('0', $this->driver));
        $this->assertFalse($this->type->toPHP('FALSE', $this->driver));
        $this->assertFalse($this->type->toPHP('false', $this->driver));
        $this->assertFalse($this->type->toPHP(false, $this->driver));
    }

    /**
     * Test converting string booleans to PHP values.
     *
     * @return void
     */
    public function testManyToPHP()
    {
        $values = [
            'a' => null,
            'b' => 'true',
            'c' => 'TRUE',
            'd' => 'false',
            'e' => 'FALSE',
            'f' => '0',
            'g' => '1',
            'h' => true,
            'i' => false,
        ];
        $expected = [
            'a' => null,
            'b' => true,
            'c' => true,
            'd' => false,
            'e' => false,
            'f' => false,
            'g' => true,
            'h' => true,
            'i' => false,
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver)
        );
    }

    /**
     * Test marshalling booleans
     *
     * @return void
     */
    public function testMarshal()
    {
        $this->assertNull($this->type->marshal(null));
        $this->assertTrue($this->type->marshal(true));
        $this->assertTrue($this->type->marshal(1));
        $this->assertTrue($this->type->marshal('1'));
        $this->assertTrue($this->type->marshal('true'));

        $this->assertFalse($this->type->marshal('false'));
        $this->assertFalse($this->type->marshal('0'));
        $this->assertFalse($this->type->marshal(0));
        $this->assertFalse($this->type->marshal(''));
        $this->assertTrue($this->type->marshal('not empty'));
        $this->assertNull($this->type->marshal(['2', '3']));
    }

    /**
     * Test converting booleans to PDO types.
     *
     * @return void
     */
    public function testToStatement()
    {
        $this->assertEquals(PDO::PARAM_NULL, $this->type->toStatement(null, $this->driver));
        $this->assertEquals(PDO::PARAM_BOOL, $this->type->toStatement(true, $this->driver));
        $this->assertEquals(PDO::PARAM_BOOL, $this->type->toStatement(false, $this->driver));
    }
}
