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
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Type;
use Cake\TestSuite\TestCase;
use \PDO;

/**
 * Test for the Integer type.
 */
class IntegerTypeTest extends TestCase
{

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
    public function testToDatabseInvalid()
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
        $result = $this->type->marshal('some data', $this->driver);
        $this->assertNull($result);

        $result = $this->type->marshal('', $this->driver);
        $this->assertNull($result);

        $result = $this->type->marshal('0', $this->driver);
        $this->assertSame(0, $result);

        $result = $this->type->marshal('105', $this->driver);
        $this->assertSame(105, $result);

        $result = $this->type->marshal(105, $this->driver);
        $this->assertSame(105, $result);

        $result = $this->type->marshal('-105', $this->driver);
        $this->assertSame(-105, $result);

        $result = $this->type->marshal(-105, $this->driver);
        $this->assertSame(-105, $result);

        $result = $this->type->marshal('1.25', $this->driver);
        $this->assertSame(1, $result);

        $result = $this->type->marshal('2 monkeys', $this->driver);
        $this->assertNull($result);

        $result = $this->type->marshal(['3', '4'], $this->driver);
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
