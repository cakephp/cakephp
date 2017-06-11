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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Type;
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
        $this->type = Type::build('integer');
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

        $result = $this->type->toPHP('some data', $this->driver);
        $this->assertSame(0, $result);

        $result = $this->type->toPHP('2', $this->driver);
        $this->assertSame(2, $result);

        $result = $this->type->toPHP('2 bears', $this->driver);
        $this->assertSame(2, $result);

        $result = $this->type->toPHP('-2', $this->driver);
        $this->assertSame(-2, $result);

        $result = $this->type->toPHP(['3', '4'], $this->driver);
        $this->assertSame(1, $result);
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatabase()
    {
        $this->assertNull($this->type->toDatabase(null, $this->driver));

        $result = $this->type->toDatabase('some data', $this->driver);
        $this->assertSame(0, $result);

        $result = $this->type->toDatabase(2, $this->driver);
        $this->assertSame(2, $result);

        $result = $this->type->toDatabase('2', $this->driver);
        $this->assertSame(2, $result);
    }

    /**
     * Tests that passing an invalid value will throw an exception
     *
     * @expectedException \InvalidArgumentException
     * @return void
     */
    public function testToDatabaseInvalid()
    {
        $this->type->toDatabase(['3', '4'], $this->driver);
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
        $this->assertSame(1, $result);
    }

    /**
     * Test that the PDO binding type is correct.
     *
     * @return void
     */
    public function testToStatement()
    {
        $this->assertEquals(PDO::PARAM_INT, $this->type->toStatement('', $this->driver));
    }
}
