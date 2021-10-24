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

use Cake\Chronos\Date;
use Cake\Core\Configure;
use Cake\Database\Type\DateType;
use Cake\I18n\Time;
use Cake\TestSuite\TestCase;
use DateTimeImmutable;

/**
 * Test for the Date type.
 */
class DateTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\DateType
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
        $this->type = new DateType();
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();

        Configure::write('Error.ignoredDeprecationPaths', [
            'src/Database/Type/DateType.php',
            'src/I18n/Date.php',
            'src/I18n/Time.php',
            'tests/TestCase/Database/Type/DateTypeTest.php',
        ]);
    }

    /**
     * Teardown
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->type->useLocaleParser(false)->setLocaleFormat(null);
    }

    /**
     * Test toPHP
     */
    public function testToPHP(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
        $this->assertNull($this->type->toPHP('0000-00-00', $this->driver));

        $result = $this->type->toPHP('2001-01-04', $this->driver);
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertSame('2001', $result->format('Y'));
        $this->assertSame('01', $result->format('m'));
        $this->assertSame('04', $result->format('d'));
    }

    /**
     * Test converting string dates to PHP values.
     */
    public function testManyToPHP(): void
    {
        $values = [
            'a' => null,
            'b' => '2001-01-04',
            'c' => '2001-01-04 12:13:14.12345',
        ];
        $expected = [
            'a' => null,
            'b' => new Date('2001-01-04'),
            'c' => new Date('2001-01-04'),
        ];
        $this->assertEquals(
            $expected,
            $this->type->manyToPHP($values, array_keys($values), $this->driver)
        );
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $value = '2001-01-04';
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertSame($value, $result);

        $date = new Date('2013-08-12');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12', $result);

        $date = new Time('2013-08-12 15:16:18');
        $result = $this->type->toDatabase($date, $this->driver);
        $this->assertSame('2013-08-12', $result);
    }

    /**
     * Data provider for marshal()
     *
     * @return array
     */
    public function marshalProvider(): array
    {
        Configure::write('Error.ignoredDeprecationPaths', [
            'src/I18n/Date.php',
        ]);

        $date = new Date('@1392387900');

        $data = [
            // invalid types.
            [null, null],
            [false, null],
            [true, null],
            ['', null],
            ['derpy', null],
            ['2013-nope!', null],
            ['2014-02-14 13:14:15', null],

            // valid string types
            ['1392387900', $date],
            [1392387900, $date],
            ['2014-02-14', new Date('2014-02-14')],

            // valid array types
            [
                ['year' => '', 'month' => '', 'day' => ''],
                null,
            ],
            [
                ['year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 13, 'minute' => 14, 'second' => 15],
                new Date('2014-02-14'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'am',
                ],
                new Date('2014-02-14'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                    'hour' => 1, 'minute' => 14, 'second' => 15,
                    'meridian' => 'pm',
                ],
                new Date('2014-02-14'),
            ],
            [
                [
                    'year' => 2014, 'month' => 2, 'day' => 14,
                ],
                new Date('2014-02-14'),
            ],

            // Invalid array types
            [
                ['year' => 'farts', 'month' => 'derp'],
                new Date(date('Y-m-d')),
            ],
            [
                ['year' => 'farts', 'month' => 'derp', 'day' => 'farts'],
                new Date(date('Y-m-d')),
            ],
            [
                [
                    'year' => '2014', 'month' => '02', 'day' => '14',
                    'hour' => 'farts', 'minute' => 'farts',
                ],
                new Date('2014-02-14'),
            ],
        ];

        Configure::delete('Error.ignoredDeprecationPaths');

        return $data;
    }

    /**
     * test marshaling data.
     *
     * @dataProvider marshalProvider
     * @param mixed $value
     * @param mixed $expected
     */
    public function testMarshal($value, $expected): void
    {
        $result = $this->type->marshal($value);
        $this->assertEquals($expected, $result);

        $this->type->useMutable();
        $result = $this->type->marshal($value);
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests marshalling dates using the locale aware parser
     */
    public function testMarshalWithLocaleParsing(): void
    {
        $this->type->useLocaleParser();
        $this->assertNull($this->type->marshal('11/derp/2013'));

        $expected = new Date('13-10-2013');
        $result = $this->type->marshal('10/13/2013');
        $this->assertSame($expected->format('Y-m-d'), $result->format('Y-m-d'));

        $this->type->useMutable();
        $result = $this->type->marshal('10/13/2013');
        $this->assertSame($expected->format('Y-m-d'), $result->format('Y-m-d'));
    }

    /**
     * Tests marshalling dates using the locale aware parser and custom format
     */
    public function testMarshalWithLocaleParsingWithFormat(): void
    {
        $this->type->useLocaleParser()->setLocaleFormat('dd MMM, y');
        $this->assertNull($this->type->marshal('11/derp/2013'));

        $expected = new Date('13-10-2013');
        $result = $this->type->marshal('13 Oct, 2013');
        $this->assertSame($expected->format('Y-m-d'), $result->format('Y-m-d'));

        $this->type->useMutable();
        $result = $this->type->marshal('13 Oct, 2013');
        $this->assertSame($expected->format('Y-m-d'), $result->format('Y-m-d'));
    }

    /**
     * Test that toImmutable changes all the methods to create frozen time instances.
     */
    public function testToImmutableAndToMutable(): void
    {
        $this->type->useImmutable();
        $this->assertInstanceOf('DateTimeImmutable', $this->type->marshal('2015-11-01'));
        $this->assertInstanceOf('DateTimeImmutable', $this->type->toPHP('2015-11-01', $this->driver));

        $this->type->useMutable();
        $this->assertInstanceOf('DateTime', $this->type->marshal('2015-11-01'));
        $this->assertInstanceOf('DateTime', $this->type->toPHP('2015-11-01', $this->driver));
    }
}
