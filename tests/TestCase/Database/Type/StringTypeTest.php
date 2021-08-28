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
 * @since         3.1.7
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Driver;
use Cake\Database\TypeFactory;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PDO;

/**
 * Test for the String type.
 */
class StringTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\TypeInterface
     */
    protected $type;

    /**
     * @var \Cake\Database\Driver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $driver;

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->type = TypeFactory::build('string');
        $this->driver = $this->getMockBuilder(Driver::class)->getMock();
    }

    /**
     * Test toPHP
     */
    public function testToPHP(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
        $this->assertSame('word', $this->type->toPHP('word', $this->driver));
        $this->assertSame('2.123', $this->type->toPHP(2.123, $this->driver));
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $obj = $this->getMockBuilder('StdClass')
            ->addMethods(['__toString'])
            ->getMock();
        $obj->method('__toString')->will($this->returnValue('toString called'));

        $this->assertNull($this->type->toDatabase(null, $this->driver));
        $this->assertSame('word', $this->type->toDatabase('word', $this->driver));
        $this->assertSame('2.123', $this->type->toDatabase(2.123, $this->driver));
        $this->assertSame('toString called', $this->type->toDatabase($obj, $this->driver));
    }

    /**
     * Tests that passing an invalid value will throw an exception
     */
    public function testToDatabaseInvalidArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->type->toDatabase([1, 2, 3], $this->driver);
    }

    /**
     * Test marshalling
     */
    public function testMarshal(): void
    {
        $this->assertNull($this->type->marshal(null));
        $this->assertNull($this->type->marshal([1, 2, 3]));
        $this->assertSame('word', $this->type->marshal('word'));
        $this->assertSame('2.123', $this->type->marshal(2.123));
    }

    /**
     * Test that the PDO binding type is correct.
     */
    public function testToStatement(): void
    {
        $this->assertSame(PDO::PARAM_STR, $this->type->toStatement('', $this->driver));
    }
}
