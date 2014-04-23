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

use Cake\Database\Type;
use Cake\Database\Type\DateType;
use Cake\TestSuite\TestCase;
use Cake\Utility\Time;

/**
 * Test for the Date type.
 */
class DateTypeTest extends TestCase {

/**
 * Setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->type = Type::build('date');
		$this->driver = $this->getMock('Cake\Database\Driver');
	}

/**
 * Test toPHP
 *
 * @return void
 */
	public function testToPHP() {
		$this->assertNull($this->type->toPHP(null, $this->driver));

		$result = $this->type->toPHP('2001-01-04', $this->driver);
		$this->assertInstanceOf('DateTime', $result);
		$this->assertEquals('2001', $result->format('Y'));
		$this->assertEquals('01', $result->format('m'));
		$this->assertEquals('04', $result->format('d'));
	}

/**
 * Tests that passing invalid data will throw an exception
 *
 * @expectedException InvalidArgumentException
 * @return void
 */
	public function testToPHPError() {
		$this->type->toPHP('2001-01-04 10:11:12', $this->driver);
	}

/**
 * Test converting to database format
 *
 * @return void
 */
	public function testToDatabase() {
		$value = '2001-01-04';
		$result = $this->type->toDatabase($value, $this->driver);
		$this->assertEquals($value, $result);

		$date = new Time('2013-08-12');
		$result = $this->type->toDatabase($date, $this->driver);
		$this->assertEquals('2013-08-12', $result);

		$date = new Time('2013-08-12 15:16:18');
		$result = $this->type->toDatabase($date, $this->driver);
		$this->assertEquals('2013-08-12', $result);
	}

/**
 * Data provider for marshal()
 *
 * @return array
 */
	public function marshalProvider() {
		$date = new Time('@1392387900');
		$date->setTime(0, 0, 0);

		return [
			// invalid types.
			[null, null],
			[false, false],
			[true, true],
			['', ''],
			['derpy', 'derpy'],
			['2013-nope!', '2013-nope!'],

			// valid string types
			['1392387900', $date],
			[1392387900, $date],
			['2014-02-14', new Time('2014-02-14')],
			['2014-02-14 13:14:15', new Time('2014-02-14 00:00:00')],

			// valid array types
			[
				['year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 13, 'minute' => 14, 'second' => 15],
				new Time('2014-02-14 00:00:00')
			],
			[
				[
					'year' => 2014, 'month' => 2, 'day' => 14,
					'hour' => 1, 'minute' => 14, 'second' => 15,
					'meridian' => 'am'
				],
				new Time('2014-02-14 00:00:00')
			],
			[
				[
					'year' => 2014, 'month' => 2, 'day' => 14,
					'hour' => 1, 'minute' => 14, 'second' => 15,
					'meridian' => 'pm'
				],
				new Time('2014-02-14 00:00:00')
			],
			[
				[
					'year' => 2014, 'month' => 2, 'day' => 14,
				],
				new Time('2014-02-14 00:00:00')
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
		];
	}

/**
 * test marshaling data.
 *
 * @dataProvider marshalProvider
 * @return void
 */
	public function testMarshal($value, $expected) {
		$result = $this->type->marshal($value);
		$this->assertEquals($expected, $result);
	}

}
