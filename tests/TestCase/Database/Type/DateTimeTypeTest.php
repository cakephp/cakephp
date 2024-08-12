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

use Cake\Chronos\ChronosDate;
use Cake\Database\Type\DateTimeType;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use DateTime as NativeDateTime;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Test for the DateTime type.
 */
class DateTimeTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\DateTimeType
     */
    protected $type;

    /**
     * @var \Cake\Database\Driver
     */
    protected $driver;

    /**
     * Original type map
     *
     * @var array
     */
    protected $_originalMap = [];

    /**
     * @var string
     */
    protected $originalTimeZone;

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->type = new DateTimeType();
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();

        $this->originalTimeZone = date_default_timezone_get();
    }

    /**
     * Reset timezone to its initial value
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        date_default_timezone_set($this->originalTimeZone);
    }

    /**
     * Test getDateTimeClassName
     */
    public function testGetDateTimeClassName(): void
    {
        $this->assertSame(DateTime::class, $this->type->getDateTimeClassName());
    }

    /**
     * Test toPHP
     */
    public function testToPHPEmpty(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
        $this->assertNull($this->type->toPHP('0000-00-00 00:00:00', $this->driver));
    }

    /**
     * Test toPHP
     */
    public function testToPHPString(): void
    {
        $result = $this->type->toPHP('2001-01-04 12:13:14', $this->driver);
        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertSame('2001', $result->format('Y'));
        $this->assertSame('01', $result->format('m'));
        $this->assertSame('04', $result->format('d'));
        $this->assertSame('12', $result->format('H'));
        $this->assertSame('13', $result->format('i'));
        $this->assertSame('14', $result->format('s'));

        $this->type->setDatabaseTimezone('Asia/Kolkata'); // UTC+5:30
        $result = $this->type->toPHP('2001-01-04 12:00:00', $this->driver);
        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertSame('2001', $result->format('Y'));
        $this->assertSame('01', $result->format('m'));
        $this->assertSame('04', $result->format('d'));
        $this->assertSame('06', $result->format('H'));
        $this->assertSame('30', $result->format('i'));
        $this->assertSame('00', $result->format('s'));
    }

    /**
     * Test converting string datetimes to PHP values.
     */
    public function testManyToPHP(): void
    {
        $values = [
            'a' => null,
            'b' => 978610394,
            'c' => '2001-01-04 12:13:14',
        ];
        $expected = [
            'a' => null,
            'b' => new DateTime('2001-01-04 12:13:14'),
            'c' => new DateTime('2001-01-04 12:13:14'),
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver)
        );

        $this->type->setDatabaseTimezone('Asia/Kolkata'); // UTC+5:30
        $values = [
            'a' => null,
            'b' => '2001-01-04 12:13:14',
        ];
        $expected = [
            'a' => null,
            'b' => new DateTime('2001-01-04 06:43:14'),
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver)
        );
    }

    /**
     * Test datetime parsing when value include milliseconds.
     *
     * Postgres includes milliseconds in timestamp columns,
     * data from those columns should work.
     */
    public function testToPHPIncludingMilliseconds(): void
    {
        $in = '2014-03-24 20:44:36.315113';
        $result = $this->type->toPHP($in, $this->driver);
        $this->assertInstanceOf(DateTime::class, $result);
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $value = '2001-01-04 12:13:14';
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertSame($value, $result);

        $date = new DateTime('2013-08-12 15:16:17');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 15:16:17', $result);

        $tz = $date->getTimezone();
        $this->type->setDatabaseTimezone('Asia/Kolkata'); // UTC+5:30
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 20:46:17', $result);
        $this->assertEquals($tz, $date->getTimezone());

        $this->type->setDatabaseTimezone(new DateTimeZone('Asia/Kolkata'));
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 20:46:17', $result);
        $this->type->setDatabaseTimezone(null);

        $date = new DateTime('2013-08-12 15:16:17');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 15:16:17', $result);

        $this->type->setDatabaseTimezone('Asia/Kolkata'); // UTC+5:30
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 20:46:17', $result);
        $this->type->setDatabaseTimezone(null);

        $date = 1401906995;
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2014-06-04 18:36:35', $result);

        $date = new Date('2024-01-27');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2024-01-27 00:00:00', $result);
    }

    /**
     * Data provider for marshal()
     *
     * @return array
     */
    public static function marshalProvider(): array
    {
        return [
            // invalid types.
            [null, null],
            [false, null],
            [true, null],
            ['', null],
            ['derpy', null],
            ['2013-nope!', null],

            // valid string types
            ['1392387900', new DateTime('@1392387900')],
            [1392387900, new DateTime('@1392387900')],
            ['2014-02-14 12:02', new DateTime('2014-02-14 12:02')],
            ['2014-02-14 00:00:00', new DateTime('2014-02-14 00:00:00')],
            ['2014-02-14 13:14:15', new DateTime('2014-02-14 13:14:15')],
            ['2014-02-14T13:14', new DateTime('2014-02-14T13:14:00')],
            ['2014-02-14T13:14:15', new DateTime('2014-02-14T13:14:15')],
            ['2017-04-05T17:18:00+00:00', new DateTime('2017-04-05T17:18:00+00:00')],
            ['2017-04-05T17:18:00+00:00', new DateTime('2017-04-05T17:18:00+00:00')],
            ['2024-03-02 15:46:00.000000', new DateTime('2024-03-02T15:46:00+00:00')],

            [new DateTime('2017-04-05T17:18:00+00:00'), new DateTime('2017-04-05T17:18:00+00:00')],
            [new NativeDateTime('2017-04-05T17:18:00+00:00'), new NativeDateTime('2017-04-05T17:18:00+00:00')],
            [new DateTimeImmutable('2017-04-05T17:18:00+00:00'), new DateTimeImmutable('2017-04-05T17:18:00+00:00')],

            // valid array types
            [
                ['year' => '', 'month' => '', 'day' => '', 'hour' => '', 'minute' => '', 'second' => ''],
                null,
            ],
            [
                ['year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 13, 'minute' => 14, 'second' => 15],
                new DateTime('2014-02-14 13:14:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'am',
                ],
                new DateTime('2014-02-14 01:14:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 12, 'minute' => 04, 'second' => 15,
                    'meridian' => 'pm',
                ],
                new DateTime('2014-02-14 12:04:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'pm',
                ],
                new DateTime('2014-02-14 13:14:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                ],
                new DateTime('2014-02-14 00:00:00'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 12, 'minute' => 30, 'timezone' => 'Europe/Paris',
                ],
                new DateTime('2014-02-14 11:30:00', 'UTC'),
            ],

            // Invalid array types
            [
                ['year' => 'farts', 'month' => 'derp'],
                null,
            ],
            [
                ['year' => 'farts', 'month' => 'derp', 'day' => 'farts'],
                null,
            ],
            [
                [
                    'year' => '2014', 'month' => '02', 'day' => '14',
                    'hour' => 'farts', 'minute' => 'farts',
                ],
                null,
            ],
        ];
    }

    /**
     * test marshalling data.
     *
     * @dataProvider marshalProvider
     * @param mixed $value
     * @param mixed $expected
     */
    public function testMarshal($value, $expected): void
    {
        $result = $this->type->marshal($value);
        if (is_object($expected)) {
            $this->assertEquals($expected, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * test marshalling data with different timezone
     */
    public function testMarshalWithTimezone(): void
    {
        date_default_timezone_set('Europe/Vienna');
        $value = DateTime::now();
        $expected = DateTime::now();
        $result = $this->type->marshal($value);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that the marhsalled datetime instance always has the system's default timezone.
     */
    public function testMarshalDateTimeInstance(): void
    {
        $expected = new DateTime('2020-05-01 23:28:00', 'Europe/Paris');

        $result = $this->type->marshal($expected);
        $this->assertEquals('UTC', $result->getTimezone()->getName());
        $this->assertEquals($expected->toDateTimeString(), $result->addHours(2)->toDateTimeString());
        $this->assertEquals('Europe/Paris', $expected->getTimezone()->getName());
    }

    public function testMarshalWithUserTimezone(): void
    {
        $this->type->setUserTimezone('+0200');

        $value = '2020-05-01 23:28:00';
        $expected = new DateTime($value);

        $result = $this->type->marshal($value);
        $this->assertEquals('UTC', $result->getTimezone()->getName());
        $this->assertEquals($expected, $result->addHours(2));

        $expected = new DateTime('2020-05-01 21:28:00', 'UTC');
        $result = $this->type->marshal([
            'year' => 2020, 'month' => 5, 'day' => 1,
            'hour' => 23, 'minute' => 28, 'second' => 0,
        ]);
        $this->assertEquals('UTC', $result->getTimezone()->getName());
        $this->assertEquals($expected, $result);

        $this->type->setUserTimezone(null);
    }

    /**
     * Test that useLocaleParser() can disable locale parsing.
     */
    public function testLocaleParserDisable(): void
    {
        $expected = new DateTime('13-10-2013 23:28:00');
        $this->type->useLocaleParser();
        $result = $this->type->marshal('10/13/2013 11:28pm');
        $this->assertEquals($expected, $result);

        $this->type->useLocaleParser(false);
        $result = $this->type->marshal('10/13/2013 11:28pm');
        $this->assertNotEquals($expected, $result);
    }

    /**
     * Tests marshalling dates using the locale aware parser
     */
    public function testMarshalWithLocaleParsing(): void
    {
        $this->type->useLocaleParser();

        $expected = new DateTime('13-10-2013 23:28:00');
        $result = $this->type->marshal('10/13/2013 11:28pm');
        $this->assertEquals($expected, $result);

        $this->assertNull($this->type->marshal('11/derp/2013 11:28pm'));

        $this->type->setUserTimezone('+0200');
        $result = $this->type->marshal('10/13/2013 11:28pm');
        $this->assertEquals('UTC', $result->getTimezone()->getName());
        $this->assertEquals($expected, $result->addHours(2));
        $this->type->setUserTimezone(null);

        $this->type->useLocaleParser(false);
    }

    /**
     * Tests marshalling dates using the locale aware parser and custom format
     */
    public function testMarshalWithLocaleParsingWithFormat(): void
    {
        $this->type->useLocaleParser()->setLocaleFormat('dd MMM, y hh:mma');

        $expected = new DateTime('13-10-2013 13:54:00');
        $result = $this->type->marshal('13 Oct, 2013 01:54pm');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test marshaling date into datetime type
     */
    public function testMarshalDateWithTimezone(): void
    {
        date_default_timezone_set('Europe/Vienna');
        $value = new ChronosDate('2023-04-26');

        $result = $this->type->marshal($value);
        $this->assertEquals($value->format('Y-m-d'), $result->format('Y-m-d'));
        $this->assertEquals('Europe/Vienna', $result->getTimezone()->getName());
    }
}
