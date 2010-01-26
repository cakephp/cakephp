<?php
/**
 * TimeHelperTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
App::import('Helper', 'Time');

/**
 * TimeHelperTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view.helpers
 */
class TimeHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->Time = new TimeHelper();
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->Time);
	}

/**
 * testToQuarter method
 *
 * @access public
 * @return void
 */
	function testToQuarter() {
		$result = $this->Time->toQuarter('2007-12-25');
		$this->assertEqual($result, 4);

		$result = $this->Time->toQuarter('2007-9-25');
		$this->assertEqual($result, 3);

		$result = $this->Time->toQuarter('2007-3-25');
		$this->assertEqual($result, 1);

		$result = $this->Time->toQuarter('2007-3-25', true);
		$this->assertEqual($result, array('2007-01-01', '2007-03-31'));

		$result = $this->Time->toQuarter('2007-5-25', true);
		$this->assertEqual($result, array('2007-04-01', '2007-06-30'));

		$result = $this->Time->toQuarter('2007-8-25', true);
		$this->assertEqual($result, array('2007-07-01', '2007-09-30'));

		$result = $this->Time->toQuarter('2007-12-25', true);
		$this->assertEqual($result, array('2007-10-01', '2007-12-31'));
	}

/**
 * testTimeAgoInWords method
 *
 * @access public
 * @return void
 */
	function testTimeAgoInWords() {
		$result = $this->Time->timeAgoInWords(strtotime('+4 months +2 weeks +3 days'), array('end' => '8 years'), true);
		$this->assertEqual($result, '4 months, 2 weeks, 3 days');

		$result = $this->Time->timeAgoInWords(strtotime('+4 months +2 weeks +2 days'), array('end' => '8 years'), true);
		$this->assertEqual($result, '4 months, 2 weeks, 2 days');

		$result = $this->Time->timeAgoInWords(strtotime('+4 months +2 weeks +1 day'), array('end' => '8 years'), true);
		$this->assertEqual($result, '4 months, 2 weeks, 1 day');

		$result = $this->Time->timeAgoInWords(strtotime('+3 months +2 weeks +1 day'), array('end' => '8 years'), true);
		$this->assertEqual($result, '3 months, 2 weeks, 1 day');

		$result = $this->Time->timeAgoInWords(strtotime('+3 months +2 weeks'), array('end' => '8 years'), true);
		$this->assertEqual($result, '3 months, 2 weeks');

		$result = $this->Time->timeAgoInWords(strtotime('+3 months +1 week +6 days'), array('end' => '8 years'), true);
		$this->assertEqual($result, '3 months, 1 week, 6 days');

		$result = $this->Time->timeAgoInWords(strtotime('+2 months +2 weeks +1 day'), array('end' => '8 years'), true);
		$this->assertEqual($result, '2 months, 2 weeks, 1 day');

		$result = $this->Time->timeAgoInWords(strtotime('+2 months +2 weeks'), array('end' => '8 years'), true);
		$this->assertEqual($result, '2 months, 2 weeks');

		$result = $this->Time->timeAgoInWords(strtotime('+2 months +1 week +6 days'), array('end' => '8 years'), true);
		$this->assertEqual($result, '2 months, 1 week, 6 days');

		$result = $this->Time->timeAgoInWords(strtotime('+1 month +1 week +6 days'), array('end' => '8 years'), true);
		$this->assertEqual($result, '1 month, 1 week, 6 days');

		for($i = 0; $i < 200; $i ++) {
			$years = mt_rand(0, 3);
			$months = mt_rand(0, 11);
			$weeks = mt_rand(0, 3);
			$days = mt_rand(0, 6);
			$hours = 0;
			$minutes = 0;
			$seconds = 0;
			$relative_date = '';

			if ($years > 0) {
				// years and months and days
				$relative_date .= ($relative_date ? ', -' : '-') . $years . ' year' . ($years > 1 ? 's' : '');
				$relative_date .= $months > 0 ? ($relative_date ? ', -' : '-') . $months . ' month' . ($months > 1 ? 's' : '') : '';
				$relative_date .= $weeks > 0 ? ($relative_date ? ', -' : '-') . $weeks . ' week' . ($weeks > 1 ? 's' : '') : '';
				$relative_date .= $days > 0 ? ($relative_date ? ', -' : '-') . $days . ' day' . ($days > 1 ? 's' : '') : '';
			} elseif (abs($months) > 0) {
				// months, weeks and days
				$relative_date .= ($relative_date ? ', -' : '-') . $months . ' month' . ($months > 1 ? 's' : '');
				$relative_date .= $weeks > 0 ? ($relative_date ? ', -' : '-') . $weeks . ' week' . ($weeks > 1 ? 's' : '') : '';
				$relative_date .= $days > 0 ? ($relative_date ? ', -' : '-') . $days . ' day' . ($days > 1 ? 's' : '') : '';
			} elseif (abs($weeks) > 0) {
				// weeks and days
				$relative_date .= ($relative_date ? ', -' : '-') . $weeks . ' week' . ($weeks > 1 ? 's' : '');
				$relative_date .= $days > 0 ? ($relative_date ? ', -' : '-') . $days . ' day' . ($days > 1 ? 's' : '') : '';
			} elseif (abs($days) > 0) {
				// days and hours
				$relative_date .= ($relative_date ? ', -' : '-') . $days . ' day' . ($days > 1 ? 's' : '');
				$relative_date .= $hours > 0 ? ($relative_date ? ', -' : '-') . $hours . ' hour' . ($hours > 1 ? 's' : '') : '';
			} elseif (abs($hours) > 0) {
				// hours and minutes
				$relative_date .= ($relative_date ? ', -' : '-') . $hours . ' hour' . ($hours > 1 ? 's' : '');
				$relative_date .= $minutes > 0 ? ($relative_date ? ', -' : '-') . $minutes . ' minute' . ($minutes > 1 ? 's' : '') : '';
			} elseif (abs($minutes) > 0) {
				// minutes only
				$relative_date .= ($relative_date ? ', -' : '-') . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
			} else {
				// seconds only
				$relative_date .= ($relative_date ? ', -' : '-') . $seconds . ' second' . ($seconds != 1 ? 's' : '');
			}

			if (date('j/n/y', strtotime(str_replace(',','',$relative_date))) != '1/1/70') {
				$result = $this->Time->timeAgoInWords(strtotime(str_replace(',','',$relative_date)), array('end' => '8 years'), true);
				if ($relative_date == '0 seconds') {
					$relative_date = '0 seconds ago';
				}

				$relative_date = str_replace('-', '', $relative_date) . ' ago';
				$this->assertEqual($result, $relative_date);

			}
		}

		for ($i = 0; $i < 200; $i ++) {
			$years = mt_rand(0, 3);
			$months = mt_rand(0, 11);
			$weeks = mt_rand(0, 3);
			$days = mt_rand(0, 6);
			$hours = 0;
			$minutes = 0;
			$seconds = 0;

			$relative_date = '';

			if ($years > 0) {
				// years and months and days
				$relative_date .= ($relative_date ? ', ' : '') . $years . ' year' . ($years > 1 ? 's' : '');
				$relative_date .= $months > 0 ? ($relative_date ? ', ' : '') . $months . ' month' . ($months > 1 ? 's' : '') : '';
				$relative_date .= $weeks > 0 ? ($relative_date ? ', ' : '') . $weeks . ' week' . ($weeks > 1 ? 's' : '') : '';
				$relative_date .= $days > 0 ? ($relative_date ? ', ' : '') . $days . ' day' . ($days > 1 ? 's' : '') : '';
			} elseif (abs($months) > 0) {
				// months, weeks and days
				$relative_date .= ($relative_date ? ', ' : '') . $months . ' month' . ($months > 1 ? 's' : '');
				$relative_date .= $weeks > 0 ? ($relative_date ? ', ' : '') . $weeks . ' week' . ($weeks > 1 ? 's' : '') : '';
				$relative_date .= $days > 0 ? ($relative_date ? ', ' : '') . $days . ' day' . ($days > 1 ? 's' : '') : '';
			} elseif (abs($weeks) > 0) {
				// weeks and days
				$relative_date .= ($relative_date ? ', ' : '') . $weeks . ' week' . ($weeks > 1 ? 's' : '');
				$relative_date .= $days > 0 ? ($relative_date ? ', ' : '') . $days . ' day' . ($days > 1 ? 's' : '') : '';
			} elseif (abs($days) > 0) {
				// days and hours
				$relative_date .= ($relative_date ? ', ' : '') . $days . ' day' . ($days > 1 ? 's' : '');
				$relative_date .= $hours > 0 ? ($relative_date ? ', ' : '') . $hours . ' hour' . ($hours > 1 ? 's' : '') : '';
			} elseif (abs($hours) > 0) {
				// hours and minutes
				$relative_date .= ($relative_date ? ', ' : '') . $hours . ' hour' . ($hours > 1 ? 's' : '');
				$relative_date .= $minutes > 0 ? ($relative_date ? ', ' : '') . $minutes . ' minute' . ($minutes > 1 ? 's' : '') : '';
			} elseif (abs($minutes) > 0) {
				// minutes only
				$relative_date .= ($relative_date ? ', ' : '') . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
			} else {
				// seconds only
				$relative_date .= ($relative_date ? ', ' : '') . $seconds . ' second' . ($seconds != 1 ? 's' : '');
			}

			if (date('j/n/y', strtotime(str_replace(',','',$relative_date))) != '1/1/70') {
				$result = $this->Time->timeAgoInWords(strtotime(str_replace(',','',$relative_date)), array('end' => '8 years'), true);
				if ($relative_date == '0 seconds') {
					$relative_date = '0 seconds ago';
				}

				$relative_date = str_replace('-', '', $relative_date) . '';
				$this->assertEqual($result, $relative_date);
			}
		}

		$result = $this->Time->timeAgoInWords(strtotime('-2 years -5 months -2 days'), array('end' => '3 years'), true);
		$this->assertEqual($result, '2 years, 5 months, 2 days ago');

		$result = $this->Time->timeAgoInWords('2007-9-25');
		$this->assertEqual($result, 'on 25/9/07');

		$result = $this->Time->timeAgoInWords('2007-9-25', 'Y-m-d');
		$this->assertEqual($result, 'on 2007-09-25');

		$result = $this->Time->timeAgoInWords('2007-9-25', 'Y-m-d', true);
		$this->assertEqual($result, 'on 2007-09-25');

		$result = $this->Time->timeAgoInWords(strtotime('-2 weeks -2 days'), 'Y-m-d', false);
		$this->assertEqual($result, '2 weeks, 2 days ago');

		$result = $this->Time->timeAgoInWords(strtotime('+2 weeks +2 days'), 'Y-m-d', true);
		$this->assertPattern('/^2 weeks, [1|2] day(s)?$/', $result);

		$result = $this->Time->timeAgoInWords(strtotime('+2 months +2 days'), array('end' => '1 month'));
		$this->assertEqual($result, 'on ' . date('j/n/y', strtotime('+2 months +2 days')));

		$result = $this->Time->timeAgoInWords(strtotime('+2 months +2 days'), array('end' => '3 month'));
		$this->assertPattern('/2 months/', $result);

		$result = $this->Time->timeAgoInWords(strtotime('+2 months +12 days'), array('end' => '3 month'));
		$this->assertPattern('/2 months, 1 week/', $result);

		$result = $this->Time->timeAgoInWords(strtotime('+3 months +5 days'), array('end' => '4 month'));
		$this->assertEqual($result, '3 months, 5 days');

		$result = $this->Time->timeAgoInWords(strtotime('-2 months -2 days'), array('end' => '3 month'));
		$this->assertEqual($result, '2 months, 2 days ago');

		$result = $this->Time->timeAgoInWords(strtotime('-2 months -2 days'), array('end' => '3 month'));
		$this->assertEqual($result, '2 months, 2 days ago');

		$result = $this->Time->timeAgoInWords(strtotime('+2 months +2 days'), array('end' => '3 month'));
		$this->assertPattern('/2 months/', $result);

		$result = $this->Time->timeAgoInWords(strtotime('+2 months +2 days'), array('end' => '1 month', 'format' => 'Y-m-d'));
		$this->assertEqual($result, 'on ' . date('Y-m-d', strtotime('+2 months +2 days')));

		$result = $this->Time->timeAgoInWords(strtotime('-2 months -2 days'), array('end' => '1 month', 'format' => 'Y-m-d'));
		$this->assertEqual($result, 'on ' . date('Y-m-d', strtotime('-2 months -2 days')));

		$result = $this->Time->timeAgoInWords(strtotime('-13 months -5 days'), array('end' => '2 years'));
		$this->assertEqual($result, '1 year, 1 month, 5 days ago');

		$fourHours = $this->Time->timeAgoInWords(strtotime('-5 days -2 hours'), array('userOffset' => -4));
		$result = $this->Time->timeAgoInWords(strtotime('-5 days -2 hours'), array('userOffset' => 4));
		$this->assertEqual($fourHours, $result);

		$result = $this->Time->timeAgoInWords(strtotime('-2 hours'));
		$expected = '2 hours ago';
		$this->assertEqual($expected, $result);

		$result = $this->Time->timeAgoInWords(strtotime('-12 minutes'));
		$expected = '12 minutes ago';
		$this->assertEqual($expected, $result);

		$result = $this->Time->timeAgoInWords(strtotime('-12 seconds'));
		$expected = '12 seconds ago';
		$this->assertEqual($expected, $result);

		$time = strtotime('-3 years -12 months');
		$result = $this->Time->timeAgoInWords($time);
		$expected = 'on ' . date('j/n/y', $time);
		$this->assertEqual($expected, $result);
	}

/**
 * testRelative method
 *
 * @access public
 * @return void
 */
	function testRelative() {
		$result = $this->Time->relativeTime('-1 week');
		$this->assertEqual($result, '1 week ago');
		$result = $this->Time->relativeTime('+1 week');
		$this->assertEqual($result, '1 week');
	}

/**
 * testNice method
 *
 * @access public
 * @return void
 */
	function testNice() {
		$time = time() + 2 * DAY;
		$this->assertEqual(date('D, M jS Y, H:i', $time), $this->Time->nice($time));

		$time = time() - 2 * DAY;
		$this->assertEqual(date('D, M jS Y, H:i', $time), $this->Time->nice($time));

		$time = time();
		$this->assertEqual(date('D, M jS Y, H:i', $time), $this->Time->nice($time));

		$time = 0;
		$this->assertEqual(date('D, M jS Y, H:i', time()), $this->Time->nice($time));

		$time = null;
		$this->assertEqual(date('D, M jS Y, H:i', time()), $this->Time->nice($time));
	}

/**
 * testNiceShort method
 *
 * @access public
 * @return void
 */
	function testNiceShort() {
		$time = time() + 2 * DAY;
		if (date('Y', $time) == date('Y')) {
			$this->assertEqual(date('M jS, H:i', $time), $this->Time->niceShort($time));
		} else {
			$this->assertEqual(date('M jSY, H:i', $time), $this->Time->niceShort($time));
		}

		$time = time();
		$this->assertEqual('Today, '.date('H:i', $time), $this->Time->niceShort($time));

		$time = time() - DAY;
		$this->assertEqual('Yesterday, '.date('H:i', $time), $this->Time->niceShort($time));
	}

/**
 * testDaysAsSql method
 *
 * @access public
 * @return void
 */
	function testDaysAsSql() {
		$begin = time();
		$end = time() + DAY;
		$field = 'my_field';
		$expected = '(my_field >= \''.date('Y-m-d', $begin).' 00:00:00\') AND (my_field <= \''.date('Y-m-d', $end).' 23:59:59\')';
		$this->assertEqual($expected, $this->Time->daysAsSql($begin, $end, $field));
	}

/**
 * testDayAsSql method
 *
 * @access public
 * @return void
 */
	function testDayAsSql() {
		$time = time();
		$field = 'my_field';
		$expected = '(my_field >= \''.date('Y-m-d', $time).' 00:00:00\') AND (my_field <= \''.date('Y-m-d', $time).' 23:59:59\')';
		$this->assertEqual($expected, $this->Time->dayAsSql($time, $field));
	}

/**
 * testToUnix method
 *
 * @access public
 * @return void
 */
	function testToUnix() {
		$this->assertEqual(time(), $this->Time->toUnix(time()));
		$this->assertEqual(strtotime('+1 day'), $this->Time->toUnix('+1 day'));
		$this->assertEqual(strtotime('+0 days'), $this->Time->toUnix('+0 days'));
		$this->assertEqual(strtotime('-1 days'), $this->Time->toUnix('-1 days'));
		$this->assertEqual(false, $this->Time->toUnix(''));
		$this->assertEqual(false, $this->Time->toUnix(null));
	}

/**
 * testToAtom method
 *
 * @access public
 * @return void
 */
	function testToAtom() {
		$this->assertEqual(date('Y-m-d\TH:i:s\Z'), $this->Time->toAtom(time()));
	}

/**
 * testToRss method
 *
 * @access public
 * @return void
 */
	function testToRss() {
		$this->assertEqual(date('r'), $this->Time->toRss(time()));
	}

/**
 * testFormat method
 *
 * @access public
 * @return void
 */
	function testFormat() {
		$format = 'D-M-Y';
		$arr = array(time(), strtotime('+1 days'), strtotime('+1 days'), strtotime('+0 days'));
		foreach ($arr as $val) {
			$this->assertEqual(date($format, $val), $this->Time->format($format, $val));
		}

		$result = $this->Time->format('Y-m-d', null, 'never');
		$this->assertEqual($result, 'never');
	}

/**
 * testOfGmt method
 *
 * @access public
 * @return void
 */
	function testGmt() {
		$hour = 3;
		$min = 4;
		$sec = 2;
		$month = 5;
		$day = 14;
		$year = 2007;
		$time = mktime($hour, $min, $sec, $month, $day, $year);
		$expected = gmmktime($hour, $min, $sec, $month, $day, $year);
		$this->assertEqual($expected, $this->Time->gmt(date('Y-n-j G:i:s', $time)));

		$hour = date('H');
		$min = date('i');
		$sec = date('s');
		$month = date('m');
		$day = date('d');
		$year = date('Y');
		$expected = gmmktime($hour, $min, $sec, $month, $day, $year);
		$this->assertEqual($expected, $this->Time->gmt(null));
	}

/**
 * testIsToday method
 *
 * @access public
 * @return void
 */
	function testIsToday() {
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
 * testIsThisWeek method
 *
 * @access public
 * @return void
 */
	function testIsThisWeek() {
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
 * @access public
 * @return void
 */
	function testIsThisMonth() {
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
 * @access public
 * @return void
 */
	function testIsThisYear() {
		$result = $this->Time->isThisYear('+0 day');
		$this->assertTrue($result);
		$result = $this->Time->isThisYear(mktime(0, 0, 0, mt_rand(1, 12), mt_rand(1, 28), date('Y')));
		$this->assertTrue($result);
	}
	/**
 * testWasYesterday method
 *
 * @access public
 * @return void
 */
	function testWasYesterday() {
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
 * @access public
 * @return void
 */
	function testIsTomorrow() {
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
 * @access public
 * @return void
 */
	function testWasWithinLast() {
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
 * testUserOffset method
 *
 * @access public
 * @return void
 */
	function testUserOffset() {
		if ($this->skipIf(!class_exists('DateTimeZone'), '%s DateTimeZone class not available.')) {
			return;
		}


		$timezoneServer = new DateTimeZone(date_default_timezone_get());
		$timeServer = new DateTime('now', $timezoneServer);
		$yourTimezone = $timezoneServer->getOffset($timeServer) / HOUR;

		$expected = time();
		$result = $this->Time->fromString(time(), $yourTimezone);
		$this->assertEqual($result, $expected);
	}

/**
 * test fromString()
 *
 * @access public
 * @return void
 */
	function testFromString() {
		$result = $this->Time->fromString('');
		$this->assertFalse($result);

		$result = $this->Time->fromString(0, 0);
		$this->assertFalse($result);

		$result = $this->Time->fromString('+1 hour');
		$expected = strtotime('+1 hour');
		$this->assertEqual($result, $expected);

		$timezone = date('Z', time());
		$result = $this->Time->fromString('+1 hour', $timezone);
		$expected = $this->Time->convert(strtotime('+1 hour'), $timezone);
		$this->assertEqual($result, $expected);
	}

/**
 * test converting time specifiers using a time definition localfe file
 *
 * @access public
 * @return void
 */
	function testConvertSpecifiers() {
		App::build(array(
			'locales' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'locale' . DS)
		), true);
		Configure::write('Config.language', 'time_test');
		$time = 1263487419; // Thu Jan 14 11:43:39 2010

		$result = $this->Time->convertSpecifiers('%a', $time);
		$expected = 'jue';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%A', $time);
		$expected = 'jueves';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%c', $time);
		$expected = 'jue %d ene %Y %H:%M:%S %Z';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%C', $time);
		$expected = '20';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%D', $time);
		$expected = '%m/%d/%y';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%b', $time);
		$expected = 'ene';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%h', $time);
		$expected = 'ene';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%B', $time);
		$expected = 'enero';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%n', $time);
		$expected = "\n";
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%n', $time);
		$expected = "\n";
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%p', $time);
		$expected = 'AM';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%P', $time);
		$expected = 'am';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%r', $time);
		$expected = '%I:%M:%S AM';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%R', $time);
		$expected = '11:43';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%t', $time);
		$expected = "\t";
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%T', $time);
		$expected = '%H:%M:%S';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%u', $time);
		$expected = 4;
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%x', $time);
		$expected = '%d/%m/%y';
		$this->assertEqual($result, $expected);

		$result = $this->Time->convertSpecifiers('%X', $time);
		$expected = '%H:%M:%S';
		$this->assertEqual($result, $expected);
	}

/**
 * test formatting dates taking in account preferred i18n locale file
 *
 * @access public
 * @return void
 */
	function testI18nFormat() {
		App::build(array(
			'locales' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'locale' . DS)
		), true);
		Configure::write('Config.language', 'time_test');
		$time = 1263495568; //Thu Jan 14 13:59:28 2010

		$result = $this->Time->i18nFormat($time);
		$expected = '14/01/10';
		$this->assertEqual($result, $expected);

		$result = $this->Time->i18nFormat($time, '%c');
		$expected = 'jue 14 ene 2010 13:59:28 ' . strftime('%Z');
		$this->assertEqual($result, $expected);

		$result = $this->Time->i18nFormat($time, 'Time is %r, and date is %x');
		$expected = 'Time is 01:59:28 PM, and date is 14/01/10';
		$this->assertEqual($result, $expected);

		$result = $this->Time->i18nFormat('invalid date', '%x', 'Date invalid');
		$expected = 'Date invalid';
		$this->assertEqual($result, $expected);
	}

/**
 * test new format() syntax which inverts first and secod parameters
 *
 * @access public
 * @return void
 */
	function testFormatNewSyntax() {
		$time = time();
		$this->assertEqual($this->Time->format($time), $this->Time->i18nFormat($time));
		$this->assertEqual($this->Time->format($time, '%c'), $this->Time->i18nFormat($time, '%c'));
	}
}
?>