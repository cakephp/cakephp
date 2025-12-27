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

use Cake\Database\Driver;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Type\FloatType;
use Cake\Datasource\EntityInterface;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use PDO;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

/**
 * Test for the Float type.
 */
#[AllowMockObjectsWithoutExpectations]
class FloatTypeTest extends TestCase
{
    protected array $fixtures = ['core.Datatypes'];

    /**
     * @var \Cake\Database\Type\FloatType
     */
    protected $type;

    /**
     * @var \Cake\Database\Driver
     */
    protected $driver;

    /**
     * @var string
     */
    protected $numberClass;

    /**
     * Setup
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->type = new FloatType();
        $this->driver = $this->createStub(Driver::class);
        $this->numberClass = FloatType::$numberClass;
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        I18n::setLocale(I18n::getDefaultLocale());
        FloatType::$numberClass = $this->numberClass;
    }

    /**
     * Test toPHP
     */
    public function testToPHP(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));

        $result = $this->type->toPHP('2', $this->driver);
        $this->assertSame(2.0, $result);

        $result = $this->type->toPHP('15.3', $this->driver);
        $this->assertSame(15.3, $result);
    }

    /**
     * Test converting string float to PHP values.
     */
    public function testManyToPHP(): void
    {
        $values = [
            'a' => null,
            'b' => '2.3',
            'c' => '15',
            'd' => '0.0',
        ];
        $expected = [
            'a' => null,
            'b' => 2.3,
            'c' => 15,
            'd' => 0.0,
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver),
        );
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $result = $this->type->toDatabase('', $this->driver);
        $this->assertNull($result);

        $result = $this->type->toDatabase(null, $this->driver);
        $this->assertNull($result);

        $result = $this->type->toDatabase('some data', $this->driver);
        $this->assertSame(0.0, $result);

        $result = $this->type->toDatabase(2, $this->driver);
        $this->assertSame(2.0, $result);

        $result = $this->type->toDatabase('2.51', $this->driver);
        $this->assertSame(2.51, $result);

        $result = $this->type->toDatabase(['3', '4'], $this->driver);
        $this->assertSame(1.0, $result);
    }

    /**
     * Test marshalling
     */
    public function testMarshal(): void
    {
        $result = $this->type->marshal('some data');
        $this->assertNull($result);

        $result = $this->type->marshal('');
        $this->assertNull($result);

        $result = $this->type->marshal('2.51');
        $this->assertSame(2.51, $result);

        // allow custom decimal format (https://github.com/cakephp/cakephp/issues/12800)
        $result = $this->type->marshal('1 230,73');
        $this->assertSame('1 230,73', $result);

        $result = $this->type->marshal('3.5 bears');
        $this->assertNull($result);

        $result = $this->type->marshal(['3', '4']);
        $this->assertNull($result);
    }

    /**
     * Tests marshalling numbers using the locale aware parser
     */
    public function testMarshalWithLocaleParsing(): void
    {
        $this->type->useLocaleParser();

        I18n::setLocale('de_DE');
        $expected = 1234.53;
        $result = $this->type->marshal('1.234,53');
        $this->assertSame($expected, $result);

        I18n::setLocale('en_US');
        $expected = 1234.0;
        $result = $this->type->marshal('1,234');
        $this->assertSame($expected, $result);

        I18n::setLocale('pt_BR');
        $expected = 5987123.231;
        $result = $this->type->marshal('5.987.123,231');
        $this->assertSame($expected, $result);

        $this->type->useLocaleParser(false);
    }

    /**
     * Test that exceptions are raised on invalid parsers.
     */
    public function testUseLocaleParsingInvalid(): void
    {
        $this->expectException(DatabaseException::class);
        FloatType::$numberClass = 'stdClass';
        $this->type->useLocaleParser();
    }

    /**
     * Test that the PDO binding type is correct.
     */
    public function testToStatement(): void
    {
        $this->assertSame(PDO::PARAM_STR, $this->type->toStatement('', $this->driver));
    }

    public function testMarshalDefaultsToNull(): void
    {
        $table = $this->getTableLocator()->get('Datatypes');
        $validator = new Validator()->numeric('floaty');
        $table->setValidator('default', $validator);

        // Testing a invalid patchEntity with a validator making floaty required
        $newEntity = $table->newEmptyEntity();
        $newEntity = $table->patchEntity($newEntity, [
            'floaty' => null,
        ]);
        $this->assertFalse($table->save($newEntity));
        $this->assertEquals([
            'floaty' => [
                '_empty' => 'This field cannot be left empty',
            ],
        ], $newEntity->getErrors());

        // Saving via a direct patch should work as there is no validation or marshaling
        $newEntity = $table->newEmptyEntity();
        $newEntity = $newEntity->patch([
            'floaty' => null,
        ]);
        $this->assertInstanceOf(EntityInterface::class, $table->save($newEntity));
        $this->assertNull($newEntity->floaty);

        // Saving via a direct patch with an invalid floaty also works as the string is cast to float
        $newEntity = $table->newEmptyEntity();
        $newEntity = $newEntity->patch([
            'floaty' => 'invalid',
        ]);
        $newEntity = $table->save($newEntity);
        $this->assertInstanceOf(EntityInterface::class, $newEntity);
        $this->assertEquals('invalid', $newEntity->floaty); // which is weird but ok

        // re-fetch the entity to ensure floaty is 0 - which is fine
        $newEntity = $table->get($newEntity->id);
        $this->assertEquals(0, $newEntity->floaty);

        // BUT if a table has no validation present, the marshaling via patchEntity will set floaty to null by default - which is weird
        $validator = new Validator();
        $table->setValidator('default', $validator);
        $newEntity = $table->newEmptyEntity();
        $newEntity = $table->patchEntity($newEntity, [
            'floaty' => 'invalid',
        ]);
        $newEntity = $table->save($newEntity);

        $this->assertInstanceOf(EntityInterface::class, $newEntity);
        $this->assertNull($newEntity->floaty);

        // re-fetching the entity now results in 0, not null anymore as the DB value is not the same as the marshaled one
        $newEntity = $table->get($newEntity->id);
        $this->assertEquals(0, $newEntity->floaty);
    }
}
