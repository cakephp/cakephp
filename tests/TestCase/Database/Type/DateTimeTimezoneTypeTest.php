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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Core\Configure;
use Cake\Database\Type\DateTimeTimezoneType;
use Cake\I18n\FrozenTime;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;
use DateTimeZone;

/**
 * Test for the DateTimeTimezone type.
 */
class DateTimeTimezoneTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\DateTimeTimezoneType
     */
    protected $type;

    /**
     * @var \Cake\Database\Driver
     */
    protected $driver;

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->type = new DateTimeTimezoneType();
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();

        Configure::write('Error.ignoredDeprecationPaths', [
            'src/I18n/Time.php',
        ]);
    }

    /**
     * Test toPHP
     */
    public function testToPHPEmpty(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
        $this->assertNull($this->type->toPHP('0000-00-00 00:00:00', $this->driver));
        $this->assertNull($this->type->toPHP('0000-00-00 00:00:00.000000', $this->driver));
        $this->assertNull($this->type->toPHP('0000-00-00 00:00:00.000000+000', $this->driver));
    }

    /**
     * Test toPHP
     */
    public function testToPHPString(): void
    {
        $result = $this->type->toPHP('2001-01-04 12:13:14.123456+02:00', $this->driver);
        $this->assertInstanceOf(FrozenTime::class, $result);
        $this->assertSame('2001', $result->format('Y'));
        $this->assertSame('01', $result->format('m'));
        $this->assertSame('04', $result->format('d'));
        $this->assertSame('10', $result->format('H'));
        $this->assertSame('13', $result->format('i'));
        $this->assertSame('14', $result->format('s'));
        $this->assertSame('123456', $result->format('u'));
        $this->assertSame('+00:00', $result->format('P'));

        // test extra fractional second past microseconds being ignored
        $result = $this->type->toPHP('2001-01-04 12:13:14.1234567+02:00', $this->driver);
        $this->assertInstanceOf(FrozenTime::class, $result);
        $this->assertSame('123456', $result->format('u'));

        $this->type->setDatabaseTimezone('Asia/Kolkata'); // UTC+5:30
        $result = $this->type->toPHP('2001-01-04 12:00:00.123456', $this->driver);
        $this->assertInstanceOf(FrozenTime::class, $result);
        $this->assertSame('2001', $result->format('Y'));
        $this->assertSame('01', $result->format('m'));
        $this->assertSame('04', $result->format('d'));
        $this->assertSame('06', $result->format('H'));
        $this->assertSame('30', $result->format('i'));
        $this->assertSame('00', $result->format('s'));
        $this->assertSame('123456', $result->format('u'));
        $this->assertSame('+00:00', $result->format('P'));
    }

    /**
     * Test toPHP keeping database time zone
     */
    public function testToPHPStringKeepDatabaseTimezone(): void
    {
        $this->type->setKeepDatabaseTimezone(true);
        $result = $this->type->toPHP('2001-01-04 12:13:14.123456+02:00', $this->driver);
        $this->assertInstanceOf(FrozenTime::class, $result);
        $this->assertSame('2001', $result->format('Y'));
        $this->assertSame('01', $result->format('m'));
        $this->assertSame('04', $result->format('d'));
        $this->assertSame('12', $result->format('H'));
        $this->assertSame('13', $result->format('i'));
        $this->assertSame('14', $result->format('s'));
        $this->assertSame('123456', $result->format('u'));
        $this->assertSame('+02:00', $result->format('P'));
        $this->type->setKeepDatabaseTimezone(false);
    }

    /**
     * Test converting string datetimes to PHP values.
     */
    public function testManyToPHP(): void
    {
        $values = [
            'a' => null,
            'b' => '2001-01-04 12:13:14',
            'c' => '2001-01-04 12:13:14.123',
            'd' => '2001-01-04 12:13:14.123456',
            // test extra fractional second past microseconds being ignored
            'e' => '2001-01-04 12:13:14.1234567',
            'f' => '2001-01-04 12:13:14.123456+02:00',
        ];
        $expected = [
            'a' => null,
            'b' => new FrozenTime('2001-01-04 12:13:14'),
            'c' => new FrozenTime('2001-01-04 12:13:14.123'),
            'd' => new FrozenTime('2001-01-04 12:13:14.123456'),
            'e' => new FrozenTime('2001-01-04 12:13:14.123456'),
            'f' => new FrozenTime('2001-01-04 10:13:14.123456+00:00'),
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver)
        );

        $this->type->setDatabaseTimezone('Asia/Kolkata'); // UTC+5:30
        $values = [
            'a' => null,
            'b' => '2001-01-04 12:13:14',
            'c' => '2001-01-04 12:13:14.123',
            'd' => '2001-01-04 12:13:14.123456',
        ];
        $expected = [
            'a' => null,
            'b' => new FrozenTime('2001-01-04 06:43:14'),
            'c' => new FrozenTime('2001-01-04 06:43:14.123'),
            'd' => new FrozenTime('2001-01-04 06:43:14.123456'),
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver)
        );
    }

    /**
     * Test converting to database format with microseconds
     */
    public function testToDatabase(): void
    {
        $value = '2001-01-04 12:13:14.123456';
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertSame($value, $result);

        // test extra fractional second past microseconds being ignored
        $date = new Time('2013-08-12 15:16:17.1234567');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 15:16:17.123456+00:00', $result);

        $date = new Time('2013-08-12 15:16:17.123456');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 15:16:17.123456+00:00', $result);

        $date = new Time('2013-08-12 15:16:17.123456+02:00');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 15:16:17.123456+02:00', $result);

        $date = new Time('2013-08-12 15:16:17.123456');

        $tz = $date->getTimezone();
        $this->type->setDatabaseTimezone('Asia/Kolkata'); // UTC+5:30
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 20:46:17.123456+05:30', $result);
        $this->assertEquals($tz, $date->getTimezone());

        $this->type->setDatabaseTimezone(new DateTimeZone('Asia/Kolkata'));
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 20:46:17.123456+05:30', $result);
        $this->type->setDatabaseTimezone(null);

        $date = new FrozenTime('2013-08-12 15:16:17.123456');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 15:16:17.123456+00:00', $result);

        $this->type->setDatabaseTimezone('Asia/Kolkata'); // UTC+5:30
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 20:46:17.123456+05:30', $result);
        $this->type->setDatabaseTimezone(null);
    }

    /**
     * Test converting to database format without microseconds
     */
    public function testToDatabaseNoMicroseconds(): void
    {
        $date = new Time('2013-08-12 15:16:17');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 15:16:17.000000+00:00', $result);

        $date = new Time('2013-08-12 15:16:17+02:00');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 15:16:17.000000+02:00', $result);

        $date = new Time('2013-08-12 15:16:17');

        $tz = $date->getTimezone();
        $this->type->setDatabaseTimezone('Asia/Kolkata'); // UTC+5:30
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 20:46:17.000000+05:30', $result);
        $this->assertEquals($tz, $date->getTimezone());

        $this->type->setDatabaseTimezone(new DateTimeZone('Asia/Kolkata'));
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 20:46:17.000000+05:30', $result);
        $this->type->setDatabaseTimezone(null);

        $date = new FrozenTime('2013-08-12 15:16:17');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 15:16:17.000000+00:00', $result);

        $this->type->setDatabaseTimezone('Asia/Kolkata'); // UTC+5:30
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12 20:46:17.000000+05:30', $result);
        $this->type->setDatabaseTimezone(null);

        $date = 1401906995;
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2014-06-04 18:36:35.000000+00:00', $result);
    }

    /**
     * Data provider for marshal() with microseconds
     *
     * @return array
     */
    public function marshalProvider(): array
    {
        Configure::write('Error.ignoredDeprecationPaths', [
            'src/I18n/Time.php',
        ]);

        $data = [
            // invalid types.
            [null, null],
            [false, null],
            [true, null],
            ['', null],
            ['derpy', null],
            ['2013-nope!', null],
            ['2014-02-14T13:14:15.1234567', null],
            ['2017-04-05T17:18:00.1234567+00:00', null],

            // valid string types
            ['2014-02-14 12:02', new Time('2014-02-14 12:02')],
            ['2014-02-14 12:02:12', new Time('2014-02-14 12:02:12')],
            ['2014-02-14 00:00:00.123456', new FrozenTime('2014-02-14 00:00:00.123456')],
            ['2014-02-14 13:14:15.123456', new FrozenTime('2014-02-14 13:14:15.123456')],
            ['2014-02-14T13:14', new Time('2014-02-14T13:14:00')],
            ['2014-02-14T13:14:12', new Time('2014-02-14T13:14:12')],
            ['2014-02-14T13:14:15.123456', new FrozenTime('2014-02-14T13:14:15.123456')],
            ['2017-04-05T17:18:00.123456+02:00', new FrozenTime('2017-04-05T17:18:00.123456+02:00')],
            ['2017-04-05T17:18:00.123456+0200', new FrozenTime('2017-04-05T17:18:00.123456+02:00')],
            ['2017-04-05T17:18:00.123456 Europe/Paris', new FrozenTime('2017-04-05T17:18:00.123456+02:00')],

            // valid array types
            [
                ['year' => '', 'month' => '', 'day' => '', 'hour' => '', 'minute' => '', 'second' => '', 'microsecond' => ''],
                null,
            ],
            [
                ['year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 13, 'minute' => 14, 'second' => 15, 'microsecond' => 123456],
                new FrozenTime('2014-02-14 13:14:15.123456'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15, 'microsecond' => 123456,
                    'meridian' => 'am',
                ],
                new FrozenTime('2014-02-14 01:14:15.123456'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 12, 'minute' => 04, 'second' => 15, 'microsecond' => 123456,
                    'meridian' => 'pm',
                ],
                new FrozenTime('2014-02-14 12:04:15.123456'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15, 'microsecond' => 123456,
                    'meridian' => 'pm',
                ],
                new FrozenTime('2014-02-14 13:14:15.123456'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 12, 'minute' => 30, 'microsecond' => 123456, 'timezone' => 'Europe/Paris',
                ],
                new FrozenTime('2014-02-14 11:30:00.123456', 'UTC'),
            ],
        ];

        Configure::delete('Error.ignoredDeprecationPaths');

        return $data;
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
     * Data provider for marshal() without microseconds
     *
     * @return array
     */
    public function marshalProviderWithoutMicroseconds(): array
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
            ['1392387900', new FrozenTime('@1392387900')],
            [1392387900, new FrozenTime('@1392387900')],
            ['2014-02-14 00:00:00', new FrozenTime('2014-02-14 00:00:00')],
            ['2014-02-14 13:14:15', new FrozenTime('2014-02-14 13:14:15')],
            ['2014-02-14T13:14:15', new FrozenTime('2014-02-14T13:14:15')],
            ['2017-04-05T17:18:00+02:00', new FrozenTime('2017-04-05T17:18:00+02:00')],

            // valid array types
            [
                ['year' => '', 'month' => '', 'day' => '', 'hour' => '', 'minute' => '', 'second' => ''],
                null,
            ],
            [
                ['year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 13, 'minute' => 14, 'second' => 15],
                new FrozenTime('2014-02-14 13:14:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'am',
                ],
                new FrozenTime('2014-02-14 01:14:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 12, 'minute' => 04, 'second' => 15,
                    'meridian' => 'pm',
                ],
                new FrozenTime('2014-02-14 12:04:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'pm',
                ],
                new FrozenTime('2014-02-14 13:14:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                ],
                new FrozenTime('2014-02-14 00:00:00'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 12, 'minute' => 30, 'timezone' => 'Europe/Paris',
                ],
                new FrozenTime('2014-02-14 11:30:00', 'UTC'),
            ],

            // Invalid array types
            [
                ['year' => 'farts', 'month' => 'derp'],
                new FrozenTime(date('Y-m-d 00:00:00')),
            ],
            [
                ['year' => 'farts', 'month' => 'derp', 'day' => 'farts'],
                new FrozenTime(date('Y-m-d 00:00:00')),
            ],
            [
                [
                    'year' => '2014', 'month' => '02', 'day' => '14',
                    'hour' => 'farts', 'minute' => 'farts',
                ],
                new FrozenTime('2014-02-14 00:00:00'),
            ],
            [
                FrozenTime::now(),
                FrozenTime::now(),
            ],
        ];
    }

    /**
     * test marshalling data.
     *
     * @dataProvider marshalProviderWithoutMicroseconds
     * @param mixed $value
     * @param mixed $expected
     */
    public function testMarshalWithoutMicroseconds($value, $expected): void
    {
        $result = $this->type->marshal($value);
        if (is_object($expected)) {
            $this->assertEquals($expected, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }
}
