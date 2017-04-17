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

use Cake\Database\Type\DateTimeType;
use Cake\I18n\FrozenTime;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;

/**
 * Test for the DateTime type.
 */
class DateTimeTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\DateTimeType
     */
    public $type;

    /**
     * @var \Cake\Database\Driver
     */
    public $driver;

    /**
     * Original type map
     *
     * @var array
     */
    protected $_originalMap = [];

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = new DateTimeType();
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
    }

    /**
     * Test getDateTimeClassName
     *
     * @return void
     */
    public function testGetDateTimeClassName()
    {
        $this->assertSame(Time::class, $this->type->getDateTimeClassName());

        $this->type->useImmutable();
        $this->assertSame(FrozenTime::class, $this->type->getDateTimeClassName());
    }

    /**
     * Test toPHP
     *
     * @return void
     */
    public function testToPHPEmpty()
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
        $this->assertNull($this->type->toPHP('0000-00-00 00:00:00', $this->driver));
    }

    /**
     * Test toPHP
     *
     * @return void
     */
    public function testToPHPString()
    {
        $result = $this->type->toPHP('2001-01-04 12:13:14', $this->driver);
        $this->assertInstanceOf('Cake\I18n\Time', $result);
        $this->assertEquals('2001', $result->format('Y'));
        $this->assertEquals('01', $result->format('m'));
        $this->assertEquals('04', $result->format('d'));
        $this->assertEquals('12', $result->format('H'));
        $this->assertEquals('13', $result->format('i'));
        $this->assertEquals('14', $result->format('s'));
    }

    /**
     * Test datetime parsing when value include milliseconds.
     *
     * Postgres includes milliseconds in timestamp columns,
     * data from those columns should work.
     *
     * @return void
     */
    public function testToPHPIncludingMilliseconds()
    {
        $in = '2014-03-24 20:44:36.315113';
        $result = $this->type->toPHP($in, $this->driver);
        $this->assertInstanceOf('Cake\I18n\Time', $result);
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatabase()
    {
        $value = '2001-01-04 12:13:14';
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertEquals($value, $result);

        $date = new Time('2013-08-12 15:16:17');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertEquals('2013-08-12 15:16:17', $result);

        $date = 1401906995;
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertEquals('2014-06-04 18:36:35', $result);
    }

    /**
     * Data provider for marshal()
     *
     * @return array
     */
    public function marshalProvider()
    {
        return [
            // invalid types.
            [null, null],
            [false, null],
            [true, null],
            ['', null],
            ['derpy', 'derpy'],
            ['2013-nope!', '2013-nope!'],
            ['13-06-26', '13-06-26'],

            // valid string types
            ['1392387900', new Time('@1392387900')],
            [1392387900, new Time('@1392387900')],
            ['2014-02-14 00:00:00', new Time('2014-02-14 00:00:00')],
            ['2014-02-14 13:14:15', new Time('2014-02-14 13:14:15')],

            // valid array types
            [
                ['year' => '', 'month' => '', 'day' => '', 'hour' => '', 'minute' => '', 'second' => ''],
                null
            ],
            [
                ['year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 13, 'minute' => 14, 'second' => 15],
                new Time('2014-02-14 13:14:15')
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'am'
                ],
                new Time('2014-02-14 01:14:15')
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 12, 'minute' => 04, 'second' => 15,
                    'meridian' => 'pm'
                ],
                new Time('2014-02-14 12:04:15')
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'pm'
                ],
                new Time('2014-02-14 13:14:15')
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                ],
                new Time('2014-02-14 00:00:00')
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 12, 'minute' => 30, 'timezone' => 'Europe/Paris'
                ],
                new Time('2014-02-14 11:30:00', 'UTC')
            ],

            // Invalid array types
            [
                ['year' => 'farts', 'month' => 'derp'],
                new Time(date('Y-m-d 00:00:00'))
            ],
            [
                ['year' => 'farts', 'month' => 'derp', 'day' => 'farts'],
                new Time(date('Y-m-d 00:00:00'))
            ],
            [
                [
                    'year' => '2014', 'month' => '02', 'day' => '14',
                    'hour' => 'farts', 'minute' => 'farts'
                ],
                new Time('2014-02-14 00:00:00')
            ],
            [
                Time::now(),
                Time::now()
            ]
        ];
    }

    /**
     * test marshalling data.
     *
     * @dataProvider marshalProvider
     * @return void
     */
    public function testMarshal($value, $expected)
    {
        $result = $this->type->marshal($value);
        if (is_object($expected)) {
            $this->assertEquals($expected, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * Test that useLocaleParser() can disable locale parsing.
     *
     * @return void
     */
    public function testLocaleParserDisable()
    {
        $expected = new Time('13-10-2013 23:28:00');
        $this->type->useLocaleParser();
        $result = $this->type->marshal('10/13/2013 11:28pm');
        $this->assertEquals($expected, $result);

        $this->type->useLocaleParser(false);
        $result = $this->type->marshal('10/13/2013 11:28pm');
        $this->assertNotEquals($expected, $result);
    }

    /**
     * Tests marshalling dates using the locale aware parser
     *
     * @return void
     */
    public function testMarshalWithLocaleParsing()
    {
        $this->type->useLocaleParser();
        $expected = new Time('13-10-2013 23:28:00');
        $result = $this->type->marshal('10/13/2013 11:28pm');
        $this->assertEquals($expected, $result);

        $this->assertNull($this->type->marshal('11/derp/2013 11:28pm'));
    }

    /**
     * Tests marshalling dates using the locale aware parser and custom format
     *
     * @return void
     */
    public function testMarshalWithLocaleParsingWithFormat()
    {
        $this->type->useLocaleParser()->setLocaleFormat('dd MMM, y hh:mma');
        $expected = new Time('13-10-2013 13:54:00');
        $result = $this->type->marshal('13 Oct, 2013 01:54pm');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that toImmutable changes all the methods to create frozen time instances.
     *
     * @return void
     */
    public function testToImmutableAndToMutable()
    {
        $this->type->useImmutable();
        $this->assertInstanceOf('DateTimeImmutable', $this->type->marshal('2015-11-01 11:23:00'));
        $this->assertInstanceOf('DateTimeImmutable', $this->type->toPHP('2015-11-01 11:23:00', $this->driver));

        $this->type->useMutable();
        $this->assertInstanceOf('DateTime', $this->type->marshal('2015-11-01 11:23:00'));
        $this->assertInstanceOf('DateTime', $this->type->toPHP('2015-11-01 11:23:00', $this->driver));
    }
}
