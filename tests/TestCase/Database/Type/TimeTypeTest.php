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

use Cake\Database\Type\TimeType;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;

/**
 * Test for the Time type.
 */
class TimeTypeTest extends TestCase
{

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = new TimeType();
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

        $result = $this->type->toPHP('00:00:00', $this->driver);
        $this->assertEquals('00', $result->format('s'));

        $result = $this->type->toPHP('00:00:15', $this->driver);
        $this->assertEquals('15', $result->format('s'));

        $result = $this->type->toPHP('16:30:15', $this->driver);
        $this->assertInstanceOf('DateTime', $result);
        $this->assertEquals('16', $result->format('H'));
        $this->assertEquals('30', $result->format('i'));
        $this->assertEquals('15', $result->format('s'));
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatabase()
    {
        $value = '16:30:15';
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertEquals($value, $result);

        $date = new Time('16:30:15');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertEquals('16:30:15', $result);

        $date = new Time('2013-08-12 15:16:18');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertEquals('15:16:18', $result);
    }

    /**
     * Data provider for marshal()
     *
     * @return array
     */
    public function marshalProvider()
    {
        $date = new Time('@1392387900');

        return [
            // invalid types.
            [null, null],
            [false, null],
            [true, null],
            ['', null],
            ['derpy', 'derpy'],
            ['16-nope!', '16-nope!'],
            ['14:15', '14:15'],
            ['2014-02-14 13:14:15', '2014-02-14 13:14:15'],

            // valid string types
            ['1392387900', $date],
            [1392387900, $date],
            ['13:10:10', new Time('13:10:10')],

            // valid array types
            [
                ['hour' => '', 'minute' => '', 'second' => ''],
                null,
            ],
            [
                ['hour' => '', 'minute' => '', 'meridian' => ''],
                null,
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
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'pm'
                ],
                new Time('2014-02-14 13:14:15')
            ],
            [
                [
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                ],
                new Time('01:14:15')
            ],

            // Invalid array types
            [
                ['hour' => 'nope', 'minute' => 14, 'second' => 15],
                new Time(date('Y-m-d 00:14:15'))
            ],
            [
                [
                    'year' => '2014', 'month' => '02', 'day' => '14',
                    'hour' => 'nope', 'minute' => 'nope'
                ],
                new Time('2014-02-14 00:00:00')
            ],
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
            $this->assertInstanceOf('DateTime', $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * Tests marshalling dates using the locale aware parser
     *
     * @return void
     */
    public function testMarshalWithLocaleParsing()
    {
        $this->type->useLocaleParser();
        $expected = new Time('23:23:00');
        $result = $this->type->marshal('11:23pm');
        $this->assertEquals($expected->format('H:i'), $result->format('H:i'));

        $this->assertNull($this->type->marshal('derp:23'));
    }

    /**
     * Test that toImmutable changes all the methods to create frozen time instances.
     *
     * @return void
     */
    public function testToImmutableAndToMutable()
    {
        $this->type->useImmutable();
        $this->assertInstanceOf('DateTimeImmutable', $this->type->marshal('11:23:12'));
        $this->assertInstanceOf('DateTimeImmutable', $this->type->toPhp('11:23:12', $this->driver));

        $this->type->useMutable();
        $this->assertInstanceOf('DateTime', $this->type->marshal('11:23:12'));
        $this->assertInstanceOf('DateTime', $this->type->toPhp('11:23:12', $this->driver));
    }
}
