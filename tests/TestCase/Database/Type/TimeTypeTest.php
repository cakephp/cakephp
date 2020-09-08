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

use Cake\Database\Type\TimeType;
use Cake\I18n\I18n;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;

/**
 * Test for the Time type.
 */
class TimeTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\TimeType
     */
    protected $type;

    /**
     * @var \Cake\Database\Driver
     */
    protected $driver;

    /**
     * @var string
     */
    protected $locale;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->type = new TimeType();
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
        $this->locale = I18n::getLocale();
    }

    /**
     * Teardown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        I18n::setLocale($this->locale);
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
        $this->assertSame('00', $result->format('s'));

        $result = $this->type->toPHP('00:00:15', $this->driver);
        $this->assertSame('15', $result->format('s'));

        $result = $this->type->toPHP('16:30:15', $this->driver);
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('16', $result->format('H'));
        $this->assertSame('30', $result->format('i'));
        $this->assertSame('15', $result->format('s'));
    }

    /**
     * Test converting string times to PHP values.
     *
     * @return void
     */
    public function testManyToPHP()
    {
        $values = [
            'a' => null,
            'b' => '01:30:13',
        ];
        $expected = [
            'a' => null,
            'b' => new Time('01:30:13'),
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
        $value = '16:30:15';
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertSame($value, $result);

        $date = new Time('16:30:15');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('16:30:15', $result);

        $date = new Time('2013-08-12 15:16:18');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('15:16:18', $result);
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
            ['derpy', null],
            ['16-nope!', null],
            ['2014-02-14 13:14:15', null],

            // valid string types
            ['1392387900', $date],
            [1392387900, $date],
            ['13:10:10', new Time('13:10:10')],
            ['14:15', new Time('14:15:00')],

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
                new Time('2014-02-14 13:14:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'am',
                ],
                new Time('2014-02-14 01:14:15'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'pm',
                ],
                new Time('2014-02-14 13:14:15'),
            ],
            [
                [
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                ],
                new Time('01:14:15'),
            ],

            // Invalid array types
            [
                ['hour' => 'nope', 'minute' => 14, 'second' => 15],
                new Time(date('Y-m-d 00:14:15')),
            ],
            [
                [
                    'year' => '2014', 'month' => '02', 'day' => '14',
                    'hour' => 'nope', 'minute' => 'nope',
                ],
                new Time('2014-02-14 00:00:00'),
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
            $this->assertInstanceOf(DateTimeImmutable::class, $result);
        } else {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * Tests marshalling times using the locale aware parser
     *
     * @return void
     */
    public function testMarshalWithLocaleParsing()
    {
        $this->type->useLocaleParser();

        $expected = new Time('23:23:00');
        $result = $this->type->marshal('11:23pm');
        $this->assertSame($expected->format('H:i'), $result->format('H:i'));
        $this->assertNull($this->type->marshal('derp:23'));

        $this->type->useLocaleParser(false);
    }

    /**
     * Tests marshalling times in denmark.
     *
     * @return void
     */
    public function testMarshalWithLocaleParsingDanishLocale()
    {
        $updated = setlocale(LC_COLLATE, 'da_DK.utf8');
        $this->skipIf($updated === false, 'Could not set locale to da_DK.utf8, skipping test.');

        $this->type->useLocaleParser();

        I18n::setLocale('da_DK');
        $expected = new Time('03:20:00');
        $result = $this->type->marshal('03.20');
        $this->assertSame($expected->format('H:i'), $result->format('H:i'));

        $this->type->useLocaleParser(false);
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
        $this->assertInstanceOf('DateTimeImmutable', $this->type->toPHP('11:23:12', $this->driver));

        $this->type->useMutable();
        $this->assertInstanceOf('DateTime', $this->type->marshal('11:23:12'));
        $this->assertInstanceOf('DateTime', $this->type->toPHP('11:23:12', $this->driver));
    }
}
