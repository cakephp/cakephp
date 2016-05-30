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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Type;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Test for the String type.
 */
class JsonTypeTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = Type::build('json');
        $this->driver = $this->getMock('Cake\Database\Driver');
    }

    /**
     * Test toPHP
     *
     * @return void
     */
    public function testToPHP()
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
        $this->assertSame('word', $this->type->toPHP(json_encode('word'), $this->driver));
        $this->assertSame(2.123, $this->type->toPHP(json_encode(2.123), $this->driver));
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatabase()
    {
        $this->assertSame('null', $this->type->toDatabase(null, $this->driver));
        $this->assertSame(json_encode('word'), $this->type->toDatabase('word', $this->driver));
        $this->assertSame(json_encode(2.123), $this->type->toDatabase(2.123, $this->driver));
        $this->assertSame(json_encode(['a' => 'b']), $this->type->toDatabase(['a' => 'b'], $this->driver));
    }

    /**
     * Tests that passing an invalid value will throw an exception
     *
     * @expectedException InvalidArgumentException
     * @return void
     */
    public function testToDatabaseInvalid()
    {
        $value = fopen(__FILE__, 'r');
        $this->type->toDatabase($value, $this->driver);
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
        $this->assertSame(2.123, $this->type->marshal(2.123));
        $this->assertSame([1, 2, 3], $this->type->marshal([1, 2, 3]));
        $this->assertSame(['a' => 1, 2, 3], $this->type->marshal(['a' => 1, 2, 3]));
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
