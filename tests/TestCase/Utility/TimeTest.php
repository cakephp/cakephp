<?php
/**
 * TimeTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\Utility\Time;

/**
 * TimeTest class
 *
 */
class TimeTest extends TestCase {

/**
 * Default system timezone identifier
 *
 * @var string
 */
	protected $_systemTimezoneIdentifier = null;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Time = new Time();
		$this->_systemTimezoneIdentifier = date_default_timezone_get();
		Configure::write('Config.language', 'eng');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Time);
		$this->_restoreSystemTimezone();
	}

/**
 * Restored the original system timezone
 *
 * @return void
 */
	protected function _restoreSystemTimezone() {
		date_default_timezone_set($this->_systemTimezoneIdentifier);
	}

/**
 * Provides values and expectations for the toQuarter method
 *
 * @return array
 */
	public function toQuarterProvider() {
		return [
			['2007-12-25', 4],
			['2007-9-25', 3],
			['2007-3-25', 1],
			['2007-3-25', ['2007-01-01', '2007-03-31'], true],
			['2007-5-25', ['2007-04-01', '2007-06-30'], true],
			['2007-8-25', ['2007-07-01', '2007-09-30'], true],
			['2007-12-25', ['2007-10-01', '2007-12-31'], true],
		];
	}

/**
 * testToQuarter method
 *
 * @dataProvider toQuarterProvider
 * @return void
 */
	public function testToQuarter($date, $expected, $range = false) {
		$this->assertEquals($expected, (new Time($date))->toQuarter($range));
	}

/**
 * provider for timeAgoInWords() tests
 *
 * @return array
 */
	public static function timeAgoProvider() {
		return array(
			array('-12 seconds', '12 seconds ago'),
			array('-12 minutes', '12 minutes ago'),
			array('-2 hours', '2 hours ago'),
			array('-1 day', '1 day ago'),
			array('-2 days', '2 days ago'),
			array('-2 days -3 hours', '2 days, 3 hours ago'),
			array('-1 week', '1 week ago'),
			array('-2 weeks -2 days', '2 weeks, 2 days ago'),
			array('+1 week', '1 week'),
			array('+1 week 1 day', '1 week, 1 day'),
			array('+2 weeks 2 day', '2 weeks, 2 days'),
			array('2007-9-24', 'on 24/9/07'),
			array('now', 'just now'),
		);
	}

/**
 * testTimeAgoInWords method
 *
 * @dataProvider timeAgoProvider
 * @return void
 */
	public function testTimeAgoInWords($input, $expected) {
		$time = new Time($input);
		$result = $time->timeAgoInWords();
		$this->assertEquals($expected, $result);
	}

/**
 * provider for timeAgo with an end date.
 *
 * @return void
 */
	public function timeAgoEndProvider() {
		return array(
			array(
				'+4 months +2 weeks +3 days',
				'4 months, 2 weeks, 3 days',
				'8 years'
			),
			array(
				'+4 months +2 weeks +1 day',
				'4 months, 2 weeks, 1 day',
				'8 years'
			),
			array(
				'+3 months +2 weeks',
				'3 months, 2 weeks',
				'8 years'
			),
			array(
				'+3 months +2 weeks +1 day',
				'3 months, 2 weeks, 1 day',
				'8 years'
			),
			array(
				'+1 months +1 week +1 day',
				'1 month, 1 week, 1 day',
				'8 years'
			),
			array(
				'+2 months +2 days',
				'2 months, 2 days',
				'+2 months +2 days'
			),
			array(
				'+2 months +12 days',
				'2 months, 1 week, 5 days',
				'3 months'
			),
		);
	}

/**
 * test the end option for timeAgoInWords
 *
 * @dataProvider timeAgoEndProvider
 * @return void
 */
	public function testTimeAgoInWordsEnd($input, $expected, $end) {
		$time = new Time($input);
		$result = $time->timeAgoInWords(array('end' => $end));
		$this->assertEquals($expected, $result);
	}

/**
 * test the custom string options for timeAgoInWords
 *
 * @return void
 */
	public function testTimeAgoInWordsCustomStrings() {
		$time = new Time('-8 years -4 months -2 weeks -3 days');
		$result = $time->timeAgoInWords(array(
			'relativeString' => 'at least %s ago',
			'accuracy' => array('year' => 'year'),
			'end' => '+10 years'
		));
		$expected = 'at least 8 years ago';
		$this->assertEquals($expected, $result);

		$time = new Time('+4 months +2 weeks +3 days');
		$result = $time->timeAgoInWords(array(
			'absoluteString' => 'exactly on %s',
			'accuracy' => array('year' => 'year'),
			'end' => '+2 months'
		));
		$expected = 'exactly on ' . date('j/n/y', strtotime('+4 months +2 weeks +3 days'));
		$this->assertEquals($expected, $result);
	}

/**
 * Test the accuracy option for timeAgoInWords()
 *
 * @return void
 */
	public function testTimeAgoInWordsAccuracy() {
		$time = new Time('+8 years +4 months +2 weeks +3 days');
		$result = $time->timeAgoInWords(array(
			'accuracy' => array('year' => 'year'),
			'end' => '+10 years'
		));
		$expected = '8 years';
		$this->assertEquals($expected, $result);

		$time = new Time('+8 years +4 months +2 weeks +3 days');
		$result = $time->timeAgoInWords(array(
			'accuracy' => array('year' => 'month'),
			'end' => '+10 years'
		));
		$expected = '8 years, 4 months';
		$this->assertEquals($expected, $result);

		$time = new Time('+8 years +4 months +2 weeks +3 days');
		$result = $time->timeAgoInWords(array(
			'accuracy' => array('year' => 'week'),
			'end' => '+10 years'
		));
		$expected = '8 years, 4 months, 2 weeks';
		$this->assertEquals($expected, $result);

		$time = new Time('+8 years +4 months +2 weeks +3 days');
		$result = $time->timeAgoInWords(array(
			'accuracy' => array('year' => 'day'),
			'end' => '+10 years'
		));
		$expected = '8 years, 4 months, 2 weeks, 3 days';
		$this->assertEquals($expected, $result);

		$time = new Time('+1 years +5 weeks');
		$result = $time->timeAgoInWords(array(
			'accuracy' => array('year' => 'year'),
			'end' => '+10 years'
		));
		$expected = '1 year';
		$this->assertEquals($expected, $result);

		$time = new Time('+58 minutes');
		$result = $time->timeAgoInWords(array(
			'accuracy' => 'hour'
		));
		$expected = 'in about an hour';
		$this->assertEquals($expected, $result);

		$time = new Time('+23 hours');
		$result = $time->timeAgoInWords(array(
			'accuracy' => 'day'
		));
		$expected = 'in about a day';
		$this->assertEquals($expected, $result);
	}

/**
 * Test the format option of timeAgoInWords()
 *
 * @return void
 */
	public function testTimeAgoInWordsWithFormat() {
		$time = new Time('2007-9-25');
		$result = $time->timeAgoInWords(array('format' => 'Y-m-d'));
		$this->assertEquals('on 2007-09-25', $result);

		$time = new Time('2007-9-25');
		$result = $time->timeAgoInWords(array('format' => 'Y-m-d'));
		$this->assertEquals('on 2007-09-25', $result);

		$time = new Time('+2 weeks +2 days');
		$result = $time->timeAgoInWords(array('format' => 'Y-m-d'));
		$this->assertRegExp('/^2 weeks, [1|2] day(s)?$/', $result);

		$time = new Time('+2 months +2 days');
		$result = $time->timeAgoInWords(array('end' => '1 month', 'format' => 'Y-m-d'));
		$this->assertEquals('on ' . date('Y-m-d', strtotime('+2 months +2 days')), $result);
	}

/**
 * test timeAgoInWords() with negative values.
 *
 * @return void
 */
	public function testTimeAgoInWordsNegativeValues() {
		$time = new Time('-2 months -2 days');
		$result = $time->timeAgoInWords(array('end' => '3 month'));
		$this->assertEquals('2 months, 2 days ago', $result);

		$time = new Time('-2 months -2 days');
		$result = $time->timeAgoInWords(array('end' => '3 month'));
		$this->assertEquals('2 months, 2 days ago', $result);

		$time = new Time('-2 months -2 days');
		$result = $time->timeAgoInWords(array('end' => '1 month', 'format' => 'Y-m-d'));
		$this->assertEquals('on ' . date('Y-m-d', strtotime('-2 months -2 days')), $result);

		$time = new Time('-2 years -5 months -2 days');
		$result = $time->timeAgoInWords(array('end' => '3 years'));
		$this->assertEquals('2 years, 5 months, 2 days ago', $result);

		$time = new Time('-2 weeks -2 days');
		$result = $time->timeAgoInWords(array('format' => 'Y-m-d'));
		$this->assertEquals('2 weeks, 2 days ago', $result);

		$time = new Time('-3 years -12 months');
		$result = $time->timeAgoInWords();
		$expected = 'on ' . $time->format('j/n/y');
		$this->assertEquals($expected, $result);

		$time = new Time('-1 month -1 week -6 days');
		$result = $time->timeAgoInWords(
			array('end' => '1 year', 'accuracy' => array('month' => 'month'))
		);
		$this->assertEquals('1 month ago', $result);

		$time = new Time('-1 years -2 weeks -3 days');
		$result = $time->timeAgoInWords(
			array('accuracy' => array('year' => 'year'))
		);
		$expected = 'on ' . $time->format('j/n/y');
		$this->assertEquals($expected, $result);

		$time = new Time('-13 months -5 days');
		$result = $time->timeAgoInWords(array('end' => '2 years'));
		$this->assertEquals('1 year, 1 month, 5 days ago', $result);

		$time = new Time('-58 minutes');
		$result = $time->timeAgoInWords(array('accuracy' => 'hour'));
		$this->assertEquals('about an hour ago', $result);

		$time = new Time('-23 hours');
		$result = $time->timeAgoInWords(array('accuracy' => 'day'));
		$this->assertEquals('about a day ago', $result);
	}

/**
 * testNice method
 *
 * @return void
 */
	public function testNice() {
		$time = new Time('2014-04-20 20:00', 'UTC');
		$this->assertEquals('Apr 20, 2014, 8:00 PM', $time->nice());

		$result = $time->nice('America/New_York');
		$this->assertEquals('Apr 20, 2014, 4:00 PM', $result);
		$this->assertEquals('UTC', $time->getTimezone()->getName());
	}

/**
 * testToUnix method
 *
 * @return void
 */
	public function testToUnix() {
		$this->assertEquals(time(), $this->Time->toUnix(time()));
		$this->assertEquals(strtotime('+1 day'), $this->Time->toUnix('+1 day'));
		$this->assertEquals(strtotime('+0 days'), $this->Time->toUnix('+0 days'));
		$this->assertEquals(strtotime('-1 days'), $this->Time->toUnix('-1 days'));
		$this->assertEquals(false, $this->Time->toUnix(''));
		$this->assertEquals(false, $this->Time->toUnix(null));
	}

/**
 * testToAtom method
 *
 * @return void
 */
	public function testToAtom() {
		$dateTime = new \DateTime;
		$this->assertEquals($dateTime->format($dateTime::ATOM), $this->Time->toAtom($dateTime->getTimestamp()));
	}

/**
 * testToRss method
 *
 * @return void
 */
	public function testToRss() {
		$date = '2012-08-12 12:12:45';
		$time = strtotime($date);
		$this->assertEquals(date('r', $time), $this->Time->toRss($time));

		$timezones = array('Europe/London', 'Europe/Brussels', 'UTC', 'America/Denver', 'America/Caracas', 'Asia/Kathmandu');
		foreach ($timezones as $timezone) {
			$yourTimezone = new \DateTimeZone($timezone);
			$yourTime = new \DateTime($date, $yourTimezone);
			$userOffset = $yourTimezone->getOffset($yourTime) / HOUR;
			$time = $yourTime->format('U');
			$this->assertEquals($yourTime->format('r'), $this->Time->toRss($time, $userOffset), "Failed on $timezone");
			$this->assertEquals($yourTime->format('r'), $this->Time->toRss($time, $timezone), "Failed on $timezone");
		}
	}

/**
 * testFormat method
 *
 * @return void
 */
	public function testFormat() {
		$format = 'D-M-Y';
		$tz = date_default_timezone_get();
		$arr = array(time(), strtotime('+1 days'), strtotime('+1 days'), strtotime('+0 days'));
		foreach ($arr as $val) {
			$this->assertEquals(date($format, $val), $this->Time->format($format, $val));
			$this->assertEquals(date($format, $val), $this->Time->format($format, $val, false, $tz));
		}

		$result = $this->Time->format('Y-m-d', null, 'never');
		$this->assertEquals('never', $result);

		$result = $this->Time->format('2012-01-13', '%d-%m-%Y', 'invalid');
		$this->assertEquals('13-01-2012', $result);

		$result = $this->Time->format('nonsense', '%d-%m-%Y', 'invalid', 'UTC');
		$this->assertEquals('invalid', $result);

		$result = $this->Time->format('0000-00-00', '%d-%m-%Y', 'invalid');
		$this->assertEquals('invalid', $result);
	}

/**
 * testIsToday method
 *
 * @return void
 */
	public function testIsToday() {
		$result = $this->Time->isToday('+1 day');
		$this->assertFalse($result);
		$result = $this->Time->isToday('+1 days');
		$this->assertFalse($result);
		$result = $this->Time->isToday('+0 day');
		$this->assertTrue($result);
		$result = $this->Time->isToday('-1 day');
		$this->assertFalse($result);
	}

/**
 * testIsFuture method
 *
 * @return void
 */
	public function testIsFuture() {
		$this->assertTrue($this->Time->isFuture('+1 month'));
		$this->assertTrue($this->Time->isFuture('+1 days'));
		$this->assertTrue($this->Time->isFuture('+1 minute'));
		$this->assertTrue($this->Time->isFuture('+1 second'));

		$this->assertFalse($this->Time->isFuture('-1 second'));
		$this->assertFalse($this->Time->isFuture('-1 day'));
		$this->assertFalse($this->Time->isFuture('-1 week'));
		$this->assertFalse($this->Time->isFuture('-1 month'));
	}

/**
 * testIsPast method
 *
 * @return void
 */
	public function testIsPast() {
		$this->assertFalse($this->Time->isPast('+1 month'));
		$this->assertFalse($this->Time->isPast('+1 days'));
		$this->assertFalse($this->Time->isPast('+1 minute'));
		$this->assertFalse($this->Time->isPast('+1 second'));

		$this->assertTrue($this->Time->isPast('-1 second'));
		$this->assertTrue($this->Time->isPast('-1 day'));
		$this->assertTrue($this->Time->isPast('-1 week'));
		$this->assertTrue($this->Time->isPast('-1 month'));
	}

/**
 * testIsThisWeek method
 *
 * @return void
 */
	public function testIsThisWeek() {
		// A map of days which goes from -1 day of week to +1 day of week
		$map = array(
			'Mon' => array(-1, 7), 'Tue' => array(-2, 6), 'Wed' => array(-3, 5),
			'Thu' => array(-4, 4), 'Fri' => array(-5, 3), 'Sat' => array(-6, 2),
			'Sun' => array(-7, 1)
		);
		$days = $map[date('D')];

		for ($day = $days[0] + 1; $day < $days[1]; $day++) {
			$this->assertTrue($this->Time->isThisWeek(($day > 0 ? '+' : '') . $day . ' days'));
		}
		$this->assertFalse($this->Time->isThisWeek($days[0] . ' days'));
		$this->assertFalse($this->Time->isThisWeek('+' . $days[1] . ' days'));
	}

/**
 * testIsThisMonth method
 *
 * @return void
 */
	public function testIsThisMonth() {
		$result = $this->Time->isThisMonth('+0 day');
		$this->assertTrue($result);
		$result = $this->Time->isThisMonth($time = mktime(0, 0, 0, date('m'), mt_rand(1, 28), date('Y')));
		$this->assertTrue($result);
		$result = $this->Time->isThisMonth(mktime(0, 0, 0, date('m'), mt_rand(1, 28), date('Y') - mt_rand(1, 12)));
		$this->assertFalse($result);
		$result = $this->Time->isThisMonth(mktime(0, 0, 0, date('m'), mt_rand(1, 28), date('Y') + mt_rand(1, 12)));
		$this->assertFalse($result);
	}

/**
 * testIsThisYear method
 *
 * @return void
 */
	public function testIsThisYear() {
		$result = $this->Time->isThisYear('+0 day');
		$this->assertTrue($result);
		$result = $this->Time->isThisYear(mktime(0, 0, 0, mt_rand(1, 12), mt_rand(1, 28), date('Y')));
		$this->assertTrue($result);
	}

/**
 * testWasYesterday method
 *
 * @return void
 */
	public function testWasYesterday() {
		$result = $this->Time->wasYesterday('+1 day');
		$this->assertFalse($result);
		$result = $this->Time->wasYesterday('+1 days');
		$this->assertFalse($result);
		$result = $this->Time->wasYesterday('+0 day');
		$this->assertFalse($result);
		$result = $this->Time->wasYesterday('-1 day');
		$this->assertTrue($result);
		$result = $this->Time->wasYesterday('-1 days');
		$this->assertTrue($result);
		$result = $this->Time->wasYesterday('-2 days');
		$this->assertFalse($result);
	}

/**
 * testIsTomorrow method
 *
 * @return void
 */
	public function testIsTomorrow() {
		$result = $this->Time->isTomorrow('+1 day');
		$this->assertTrue($result);
		$result = $this->Time->isTomorrow('+1 days');
		$this->assertTrue($result);
		$result = $this->Time->isTomorrow('+0 day');
		$this->assertFalse($result);
		$result = $this->Time->isTomorrow('-1 day');
		$this->assertFalse($result);
	}

/**
 * testWasWithinLast method
 *
 * @return void
 */
	public function testWasWithinLast() {
		$this->assertTrue($this->Time->wasWithinLast('1 day', '-1 day'));
		$this->assertTrue($this->Time->wasWithinLast('1 week', '-1 week'));
		$this->assertTrue($this->Time->wasWithinLast('1 year', '-1 year'));
		$this->assertTrue($this->Time->wasWithinLast('1 second', '-1 second'));
		$this->assertTrue($this->Time->wasWithinLast('1 minute', '-1 minute'));
		$this->assertTrue($this->Time->wasWithinLast('1 year', '-1 year'));
		$this->assertTrue($this->Time->wasWithinLast('1 month', '-1 month'));
		$this->assertTrue($this->Time->wasWithinLast('1 day', '-1 day'));

		$this->assertTrue($this->Time->wasWithinLast('1 week', '-1 day'));
		$this->assertTrue($this->Time->wasWithinLast('2 week', '-1 week'));
		$this->assertFalse($this->Time->wasWithinLast('1 second', '-1 year'));
		$this->assertTrue($this->Time->wasWithinLast('10 minutes', '-1 second'));
		$this->assertTrue($this->Time->wasWithinLast('23 minutes', '-1 minute'));
		$this->assertFalse($this->Time->wasWithinLast('0 year', '-1 year'));
		$this->assertTrue($this->Time->wasWithinLast('13 month', '-1 month'));
		$this->assertTrue($this->Time->wasWithinLast('2 days', '-1 day'));

		$this->assertFalse($this->Time->wasWithinLast('1 week', '-2 weeks'));
		$this->assertFalse($this->Time->wasWithinLast('1 second', '-2 seconds'));
		$this->assertFalse($this->Time->wasWithinLast('1 day', '-2 days'));
		$this->assertFalse($this->Time->wasWithinLast('1 hour', '-2 hours'));
		$this->assertFalse($this->Time->wasWithinLast('1 month', '-2 months'));
		$this->assertFalse($this->Time->wasWithinLast('1 year', '-2 years'));

		$this->assertFalse($this->Time->wasWithinLast('1 day', '-2 weeks'));
		$this->assertFalse($this->Time->wasWithinLast('1 day', '-2 days'));
		$this->assertFalse($this->Time->wasWithinLast('0 days', '-2 days'));
		$this->assertTrue($this->Time->wasWithinLast('1 hour', '-20 seconds'));
		$this->assertTrue($this->Time->wasWithinLast('1 year', '-60 minutes -30 seconds'));
		$this->assertTrue($this->Time->wasWithinLast('3 years', '-2 months'));
		$this->assertTrue($this->Time->wasWithinLast('5 months', '-4 months'));

		$this->assertTrue($this->Time->wasWithinLast('5 ', '-3 days'));
		$this->assertTrue($this->Time->wasWithinLast('1   ', '-1 hour'));
		$this->assertTrue($this->Time->wasWithinLast('1   ', '-1 minute'));
		$this->assertTrue($this->Time->wasWithinLast('1   ', '-23 hours -59 minutes -59 seconds'));
	}

/**
 * testWasWithinLast method
 *
 * @return void
 */
	public function testIsWithinNext() {
		$this->assertFalse($this->Time->isWithinNext('1 day', '-1 day'));
		$this->assertFalse($this->Time->isWithinNext('1 week', '-1 week'));
		$this->assertFalse($this->Time->isWithinNext('1 year', '-1 year'));
		$this->assertFalse($this->Time->isWithinNext('1 second', '-1 second'));
		$this->assertFalse($this->Time->isWithinNext('1 minute', '-1 minute'));
		$this->assertFalse($this->Time->isWithinNext('1 year', '-1 year'));
		$this->assertFalse($this->Time->isWithinNext('1 month', '-1 month'));
		$this->assertFalse($this->Time->isWithinNext('1 day', '-1 day'));

		$this->assertFalse($this->Time->isWithinNext('1 week', '-1 day'));
		$this->assertFalse($this->Time->isWithinNext('2 week', '-1 week'));
		$this->assertFalse($this->Time->isWithinNext('1 second', '-1 year'));
		$this->assertFalse($this->Time->isWithinNext('10 minutes', '-1 second'));
		$this->assertFalse($this->Time->isWithinNext('23 minutes', '-1 minute'));
		$this->assertFalse($this->Time->isWithinNext('0 year', '-1 year'));
		$this->assertFalse($this->Time->isWithinNext('13 month', '-1 month'));
		$this->assertFalse($this->Time->isWithinNext('2 days', '-1 day'));

		$this->assertFalse($this->Time->isWithinNext('1 week', '-2 weeks'));
		$this->assertFalse($this->Time->isWithinNext('1 second', '-2 seconds'));
		$this->assertFalse($this->Time->isWithinNext('1 day', '-2 days'));
		$this->assertFalse($this->Time->isWithinNext('1 hour', '-2 hours'));
		$this->assertFalse($this->Time->isWithinNext('1 month', '-2 months'));
		$this->assertFalse($this->Time->isWithinNext('1 year', '-2 years'));

		$this->assertFalse($this->Time->isWithinNext('1 day', '-2 weeks'));
		$this->assertFalse($this->Time->isWithinNext('1 day', '-2 days'));
		$this->assertFalse($this->Time->isWithinNext('0 days', '-2 days'));
		$this->assertFalse($this->Time->isWithinNext('1 hour', '-20 seconds'));
		$this->assertFalse($this->Time->isWithinNext('1 year', '-60 minutes -30 seconds'));
		$this->assertFalse($this->Time->isWithinNext('3 years', '-2 months'));
		$this->assertFalse($this->Time->isWithinNext('5 months', '-4 months'));

		$this->assertFalse($this->Time->isWithinNext('5 ', '-3 days'));
		$this->assertFalse($this->Time->isWithinNext('1   ', '-1 hour'));
		$this->assertFalse($this->Time->isWithinNext('1   ', '-1 minute'));
		$this->assertFalse($this->Time->isWithinNext('1   ', '-23 hours -59 minutes -59 seconds'));

		$this->assertTrue($this->Time->isWithinNext('7 days', '6 days, 23 hours, 59 minutes, 59 seconds'));
		$this->assertFalse($this->Time->isWithinNext('7 days', '6 days, 23 hours, 59 minutes, 61 seconds'));
	}

/**
 * test formatting dates taking in account preferred i18n locale file
 *
 * @return void
 */
	public function testI18nFormat() {
		Configure::write('Config.language', 'es');

		$time = strtotime('Thu Jan 14 13:59:28 2010');

		$result = $this->Time->i18nFormat($time);
		$expected = '14/01/10';
		$this->assertEquals($expected, $result);

		$result = $this->Time->i18nFormat($time, '%c');
		$expected = 'jue 14 ene 2010 13:59:28 ' . utf8_encode(strftime('%Z', $time));
		$this->assertEquals($expected, $result);

		$result = $this->Time->i18nFormat($time, 'Time is %r, and date is %x');
		$expected = 'Time is 01:59:28 PM, and date is 14/01/10';
		$this->assertEquals($expected, $result);

		$time = strtotime('Wed Jan 13 13:59:28 2010');

		$result = $this->Time->i18nFormat($time);
		$expected = '13/01/10';
		$this->assertEquals($expected, $result);

		$result = $this->Time->i18nFormat($time, '%c');
		$expected = 'mié 13 ene 2010 13:59:28 ' . utf8_encode(strftime('%Z', $time));
		$this->assertEquals($expected, $result);

		$result = $this->Time->i18nFormat($time, 'Time is %r, and date is %x');
		$expected = 'Time is 01:59:28 PM, and date is 13/01/10';
		$this->assertEquals($expected, $result);

		$result = $this->Time->i18nFormat('invalid date', '%x', 'Date invalid');
		$expected = 'Date invalid';
		$this->assertEquals($expected, $result);
	}

/**
 * test new format() syntax which inverts first and second parameters
 *
 * @return void
 */
	public function testFormatNewSyntax() {
		$time = time();
		$this->assertEquals($this->Time->format($time), $this->Time->i18nFormat($time));
		$this->assertEquals($this->Time->format($time, '%c'), $this->Time->i18nFormat($time, '%c'));
	}

/**
 * testListTimezones
 *
 * @return void
 */
	public function testListTimezones() {
		$return = Time::listTimezones();
		$this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
		$this->assertEquals('Bangkok', $return['Asia']['Asia/Bangkok']);
		$this->assertTrue(isset($return['America']['America/Argentina/Buenos_Aires']));
		$this->assertEquals('Argentina/Buenos_Aires', $return['America']['America/Argentina/Buenos_Aires']);
		$this->assertTrue(isset($return['UTC']['UTC']));
		$this->assertFalse(isset($return['Cuba']));
		$this->assertFalse(isset($return['US']));

		$return = Time::listTimezones('#^Asia/#');
		$this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
		$this->assertFalse(isset($return['Pacific']));

		$return = Time::listTimezones('#^(America|Pacific)/#', null, false);
		$this->assertTrue(isset($return['America/Argentina/Buenos_Aires']));
		$this->assertTrue(isset($return['Pacific/Tahiti']));

		$return = Time::listTimezones(\DateTimeZone::ASIA);
		$this->assertTrue(isset($return['Asia']['Asia/Bangkok']));
		$this->assertFalse(isset($return['Pacific']));

		$return = Time::listTimezones(\DateTimeZone::PER_COUNTRY, 'US', false);
		$this->assertTrue(isset($return['Pacific/Honolulu']));
		$this->assertFalse(isset($return['Asia/Bangkok']));
	}

/**
 * Tests that using Cake\Utility\Time::format() with the correct sytax actually converts
 * from one timezone to the other correctly
 *
 * @return void
 */
	public function testCorrectTimezoneConversion() {
		date_default_timezone_set('UTC');
		$date = '2012-01-01 10:00:00';
		$converted = Time::format($date, '%Y-%m-%d %H:%M', '', 'Europe/Copenhagen');
		$expected = new \DateTime($date);
		$expected->setTimezone(new \DateTimeZone('Europe/Copenhagen'));
		$this->assertEquals($expected->format('Y-m-d H:i'), $converted);
	}

}
