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
 * Test for the String type.
 */
class StringTypeTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = Type::build('string');
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
        $this->assertSame('word', $this->type->toPHP('word', $this->driver));
        $this->assertSame('2.123', $this->type->toPHP(2.123, $this->driver));
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatabase()
    {
        $obj = $this->getMockBuilder('StdClass')
            ->setMethods(['__toString'])
            ->getMock();
        $obj->method('__toString')->will($this->returnValue('toString called'));

        $this->assertNull($this->type->toDatabase(null, $this->driver));
        $this->assertSame('word', $this->type->toDatabase('word', $this->driver));
        $this->assertSame('2.123', $this->type->toDatabase(2.123, $this->driver));
        $this->assertSame('toString called', $this->type->toDatabase($obj, $this->driver));
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
     * Test marshalling
     *
     * @return void
     */
    public function testMarshal()
    {
        $this->assertNull($this->type->marshal(null));
        $this->assertSame('word', $this->type->marshal('word'));
        $this->assertSame('2.123', $this->type->marshal(2.123));
        $this->assertSame('', $this->type->marshal([1, 2, 3]));
    }

    /**
     * Test that the PDO binding type is correct.
     *
     * @return void
     */
    public function testToStatement()
    {
        $this->assertEquals(PDO::PARAM_STR, $this->type->toStatement('', $this->driver));
    }
}
