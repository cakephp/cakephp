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
use Cake\Database\Type\DecimalType;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Test for the Decimal type.
 */
class DecimalTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\DecimalType
     */
    public $type;

    /**
     * @var \Cake\Database\Driver
     */
    public $driver;

    /**
     * @var string
     */
    public $numberClass;

    /**
     * @var string
     */
    public $localeString;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = Type::build('decimal');
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
        $this->localeString = I18n::getLocale();
        $this->numberClass = DecimalType::$numberClass;

        I18n::setLocale($this->localeString);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        I18n::setLocale($this->localeString);
        DecimalType::$numberClass = $this->numberClass;
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
        $this->assertSame(2.0, $result);

        $result = $this->type->toPHP('15.3', $this->driver);
        $this->assertSame(15.3, $result);
    }

    /**
     * Test converting string decimals to PHP values.
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
        ];
        $expected = [
            'a' => null,
            'b' => 2.3,
            'c' => 15,
            'd' => 0.0,
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
        $result = $this->type->toDatabase('', $this->driver);
        $this->assertNull($result);

        $result = $this->type->toDatabase(null, $this->driver);
        $this->assertNull($result);

        $result = $this->type->toDatabase('some data', $this->driver);
        $this->assertSame('0.000000', $result);

        $result = $this->type->toDatabase(2, $this->driver);
        $this->assertSame('2.000000', $result);

        $result = $this->type->toDatabase(2.99, $this->driver);
        $this->assertSame('2.990000', $result);

        $result = $this->type->toDatabase('2.51', $this->driver);
        $this->assertSame('2.51', $result);
    }

    /**
     * Arrays are invalid.
     *
     * @return void
     */
    public function testToDatabaseInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
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
        $this->assertSame('some data', $result);

        $result = $this->type->marshal('');
        $this->assertNull($result);

        $result = $this->type->marshal('2.51');
        $this->assertSame(2.51, $result);

        $result = $this->type->marshal('3.5 bears');
        $this->assertSame('3.5 bears', $result);

        $result = $this->type->marshal(['3', '4']);
        $this->assertSame(1, $result);
    }

    /**
     * Tests marshalling numbers using the locale aware parser
     *
     * @return void
     */
    public function testMarshalWithLocaleParsing()
    {
        I18n::setLocale('de_DE');
        $this->type->useLocaleParser();
        $expected = 1234.53;
        $result = $this->type->marshal('1.234,53');
        $this->assertEquals($expected, $result);

        I18n::setLocale('en_US');
        $this->type->useLocaleParser();
        $expected = 1234;
        $result = $this->type->marshal('1,234');
        $this->assertEquals($expected, $result);

        I18n::setLocale('pt_BR');
        $this->type->useLocaleParser();
        $expected = 5987123.231;
        $result = $this->type->marshal('5.987.123,231');
        $this->assertEquals($expected, $result);
    }

    /**
     * test marshall() number in the danish locale which uses . for thousands separator.
     *
     * @return void
     */
    public function testMarshallWithLocaleParsingDanish()
    {
        I18n::setLocale('da_DK');

        $this->type->useLocaleParser();
        $expected = 47500.0;
        $result = $this->type->marshal('47.500');
        $this->assertSame($expected, $result);
    }

    /**
     * Test that exceptions are raised on invalid parsers.
     *
     * @return void
     */
    public function testUseLocaleParsingInvalid()
    {
        $this->expectException(\RuntimeException::class);
        DecimalType::$numberClass = 'stdClass';
        $this->type->useLocaleParser();
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
