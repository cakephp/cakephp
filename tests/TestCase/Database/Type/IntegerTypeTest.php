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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\TypeFactory;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Test for the Integer type.
 */
class IntegerTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\IntegerType
     */
    protected $type;

    /**
     * @var \Cake\Database\Driver
     */
    protected $driver;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->type = TypeFactory::build('integer');
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
    }

    /**
     * Test toPHP
     *
     * @return void
     */
    public function testToPHP()
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));

        $result = $this->type->toPHP('2', $this->driver);
        $this->assertSame(2, $result);

        $result = $this->type->toPHP('2.3', $this->driver);
        $this->assertSame(2, $result);

        $result = $this->type->toPHP('-2', $this->driver);
        $this->assertSame(-2, $result);

        $result = $this->type->toPHP(10, $this->driver);
        $this->assertSame(10, $result);
    }

    /**
     * Test converting string float to PHP values.
     *
     * @return void
     */
    public function testManyToPHP()
    {
        $values = [
            'a' => null,
            'b' => '2.3',
            'c' => '15',
            'd' => '0.0',
            'e' => 10,
        ];
        $expected = [
            'a' => null,
            'b' => 2,
            'c' => 15,
            'd' => 0,
            'e' => 10,
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver)
        );
    }

    /**
     * Test to make sure the method throws an exception for invalid integer values.
     *
     * @return void
     */
    public function testInvalidManyToPHP()
    {
        $this->expectException(\InvalidArgumentException::class);
        $values = [
            'a' => null,
            'b' => '2.3',
            'c' => '15',
            'd' => '0.0',
            'e' => 10,
            'f' => '6a88accf-a34e-4dd9-ade0-8d255ccaecbe',
        ];
        $expected = [
            'a' => null,
            'b' => 2,
            'c' => 15,
            'd' => 0,
            'e' => 10,
            'f' => '6a88accf-a34e-4dd9-ade0-8d255ccaecbe',
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver)
        );
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatabase()
    {
        $this->assertNull($this->type->toDatabase(null, $this->driver));

        $result = $this->type->toDatabase(2, $this->driver);
        $this->assertSame(2, $result);

        $result = $this->type->toDatabase('2', $this->driver);
        $this->assertSame(2, $result);
    }

    /**
     * Invalid Integer Data Provider
     *
     * @return void
     */
    public function invalidIntegerProvider()
    {
        return [
            'array' => [['3', '4']],
            'non-numeric-string' => ['some-data'],
            'uuid' => ['6a88accf-a34e-4dd9-ade0-8d255ccaecbe'],
        ];
    }

    /**
     * Tests that passing an invalid value will throw an exception
     *
     * @dataProvider invalidIntegerProvider
     * @param  mixed $value Invalid value to test against the database type.
     * @return void
     */
    public function testToDatabaseInvalid($value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->type->toDatabase($value, $this->driver);
    }

    /**
     * Test marshalling
     *
     * @return void
     */
    public function testMarshal()
    {
        $result = $this->type->marshal('some data');
        $this->assertNull($result);

        $result = $this->type->marshal('');
        $this->assertNull($result);

        $result = $this->type->marshal('0');
        $this->assertSame(0, $result);

        $result = $this->type->marshal('105');
        $this->assertSame(105, $result);

        $result = $this->type->marshal(105);
        $this->assertSame(105, $result);

        $result = $this->type->marshal('-105');
        $this->assertSame(-105, $result);

        $result = $this->type->marshal(-105);
        $this->assertSame(-105, $result);

        $result = $this->type->marshal('1.25');
        $this->assertSame(1, $result);

        $result = $this->type->marshal('2 monkeys');
        $this->assertNull($result);

        $result = $this->type->marshal(['3', '4']);
        $this->assertNull($result);

        $result = $this->type->marshal('+0123.45e2');
        $this->assertSame(12345, $result);
    }

    /**
     * Test that the PDO binding type is correct.
     *
     * @return void
     */
    public function testToStatement()
    {
        $this->assertSame(PDO::PARAM_INT, $this->type->toStatement('', $this->driver));
    }
}
