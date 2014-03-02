<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Type;
use Cake\Database\Type\DateType;
use Cake\TestSuite\TestCase;

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

		$result = $this->type->toPHP('2001-01-04 10:11:12', $this->driver);
		$this->assertFalse($result);
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

		$date = new \DateTime('2013-08-12');
		$result = $this->type->toDatabase($date, $this->driver);
		$this->assertEquals('2013-08-12', $result);

		$date = new \DateTime('2013-08-12 15:16:18');
		$result = $this->type->toDatabase($date, $this->driver);
		$this->assertEquals('2013-08-12', $result);
	}

/**
 * Data provider for marshall()
 *
 * @return array
 */
	public function marshallProvider() {
		$date = new \DateTime('@1392387900');
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
			['2014-02-14', new \DateTime('2014-02-14')],
			['2014-02-14 13:14:15', new \DateTime('2014-02-14 00:00:00')],

			// valid array types
			[
				['year' => 2014, 'month' => 2, 'day' => 14, 'hour' => 13, 'minute' => 14, 'second' => 15],
				new \DateTime('2014-02-14 00:00:00')
			],
			[
				[
					'year' => 2014, 'month' => 2, 'day' => 14,
					'hour' => 1, 'minute' => 14, 'second' => 15,
					'meridian' => 'am'
				],
				new \DateTime('2014-02-14 00:00:00')
			],
			[
				[
					'year' => 2014, 'month' => 2, 'day' => 14,
					'hour' => 1, 'minute' => 14, 'second' => 15,
					'meridian' => 'pm'
				],
				new \DateTime('2014-02-14 00:00:00')
			],
			[
				[
					'year' => 2014, 'month' => 2, 'day' => 14,
				],
				new \DateTime('2014-02-14 00:00:00')
			],

			// Invalid array types
			[
				['year' => 'farts', 'month' => 'derp'],
				new \DateTime(date('Y-m-d 00:00:00'))
			],
			[
				['year' => 'farts', 'month' => 'derp', 'day' => 'farts'],
				new \DateTime(date('Y-m-d 00:00:00'))
			],
			[
				[
					'year' => '2014', 'month' => '02', 'day' => '14',
					'hour' => 'farts', 'minute' => 'farts'
				],
				new \DateTime('2014-02-14 00:00:00')
			],
		];
	}

/**
 * test marshalling data.
 *
 * @dataProvider marshallProvider
 * @return void
 */
	public function testMarshall($value, $expected) {
		$result = $this->type->marshall($value);
		$this->assertEquals($expected, $result);
	}

}
