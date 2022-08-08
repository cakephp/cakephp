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
use TestApp\Model\Enum\AuthorGenderEnum;

/**
 * Test for the String type.
 */
class EnumTypeTest extends TestCase
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
        $this->type = TypeFactory::build('enum');
        $this->driver = $this->getMockBuilder(Driver::class)->getMock();
    }

    /**
     * Test toPHP
     */
    public function testToPHP(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));

        // Doesn't work yet
        //$this->assertSame(AuthorGenderEnum::FEMALE, $this->type->toPHP('female', $this->driver));
        //$this->assertSame(AuthorGenderEnum::MALE, $this->type->toPHP('male', $this->driver));
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $this->assertNull($this->type->toDatabase(null, $this->driver));
        $this->assertSame('female', $this->type->toDatabase(AuthorGenderEnum::FEMALE, $this->driver));
        $this->assertSame('female', $this->type->toDatabase(AuthorGenderEnum::FEMALE->value, $this->driver));
        $this->assertSame('male', $this->type->toDatabase(AuthorGenderEnum::MALE, $this->driver));
        $this->assertSame('male', $this->type->toDatabase(AuthorGenderEnum::MALE->value, $this->driver));
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
        $this->assertSame('female', $this->type->marshal('female'));
    }

    /**
     * Test that the PDO binding type is correct.
     */
    public function testToStatement(): void
    {
        $this->assertSame(PDO::PARAM_STR, $this->type->toStatement('male', $this->driver));
    }

    /**
     * Test converting string datetimes to PHP values.
     */
    public function testManyToPHP(): void
    {
        $values = [
            'a' => null,
            'b' => 'female',
        ];
        $expected = [
            'a' => null,
            'b' => AuthorGenderEnum::FEMALE,
        ];

        // Doesn't work yet
        //$this->assertEquals(
        //    $expected,
        //    $this->type->manyToPHP($values, array_keys($values), $this->driver)
        //);
    }
}
