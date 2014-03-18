<?php
/**
 * Cake Time utility class file.
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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Utility;

use Cake\Core\Configure;

/**
 * Time Helper class for easy use of time data.
 *
 * Manipulation of time data.
 *
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html
 */
class Time {

/**
 * The format to use when formatting a time using `Cake\Utility\Time::nice()`
 *
 * The format should use the locale strings as defined in the PHP docs under
 * `strftime` (http://php.net/manual/en/function.strftime.php)
 *
 * @var string
 * @see \Cake\Utility\Time::format()
 */
	public static $niceFormat = '%a, %b %eS %Y, %H:%M';

/**
 * The format to use when formatting a time using `Cake\Utility\Time::timeAgoInWords()`
 * and the difference is more than `Cake\Utility\Time::$wordEnd`
 *
 * @var string
 * @see \Cake\Utility\Time::timeAgoInWords()
 */
	public static $wordFormat = 'j/n/y';

/**
 * The format to use when formatting a time using `Cake\Utility\Time::timeAgoInWords()`
 * and the difference is less than `Cake\Utility\Time::$wordEnd`
 *
 * @var array
 * @see \Cake\Utility\Time::timeAgoInWords()
 */
	public static $wordAccuracy = array(
		'year' => "day",
		'month' => "day",
		'week' => "day",
		'day' => "hour",
		'hour' => "minute",
		'minute' => "minute",
		'second' => "second",
	);

/**
 * The end of relative time telling
 *
 * @var string
 * @see \Cake\Utility\Time::timeAgoInWords()
 */
	public static $wordEnd = '+1 month';

/**
 * Temporary variable containing the timestamp value, used internally in convertSpecifiers()
 *
 * @var integer
 */
	protected static $_time = null;

/**
 * Converts a string representing the format for the function strftime and returns a
 * windows safe and i18n aware format.
 *
 * @param string $format Format with specifiers for strftime function.
 *    Accepts the special specifier %S which mimics the modifier S for date()
 * @param string $time UNIX timestamp
 * @return string windows safe and date() function compatible format for strftime
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::convertSpecifiers
 */
	public static function convertSpecifiers($format, $time = null) {
		if (!$time) {
			$dateTime = new \DateTime;
			$time = $dateTime->getTimestamp();
		}
		static::$_time = $time;
		return preg_replace_callback('/\%(\w+)/', array(__CLASS__, '_translateSpecifier'), $format);
	}

/**
 * Auxiliary function to translate a matched specifier element from a regular expression into
 * a windows safe and i18n aware specifier
 *
 * @param array $specifier match from regular expression
 * @return string converted element
 */
	protected static function _translateSpecifier($specifier) {
		switch ($specifier[1]) {
			case 'a':
				$abday = __dc('cake', 'abday', 5);
				if (is_array($abday)) {
					return $abday[date('w', static::$_time)];
				}
				break;
			case 'A':
				$day = __dc('cake', 'day', 5);
				if (is_array($day)) {
					return $day[date('w', static::$_time)];
				}
				break;
			case 'c':
				$format = __dc('cake', 'd_t_fmt', 5);
				if ($format !== 'd_t_fmt') {
					return static::convertSpecifiers($format, static::$_time);
				}
				break;
			case 'C':
				return sprintf("%02d", date('Y', static::$_time) / 100);
			case 'D':
				return '%m/%d/%y';
			case 'e':
				if (DS === '/') {
					return '%e';
				}
				$day = date('j', static::$_time);
				if ($day < 10) {
					$day = ' ' . $day;
				}
				return $day;
			case 'eS' :
				return date('jS', static::$_time);
			case 'b':
			case 'h':
				$months = __dc('cake', 'abmon', 5);
				if (is_array($months)) {
					return $months[date('n', static::$_time) - 1];
				}
				return '%b';
			case 'B':
				$months = __dc('cake', 'mon', 5);
				if (is_array($months)) {
					return $months[date('n', static::$_time) - 1];
				}
				break;
			case 'n':
				return "\n";
			case 'p':
			case 'P':
				$default = array('am' => 0, 'pm' => 1);
				$meridiem = $default[date('a', static::$_time)];
				$format = __dc('cake', 'am_pm', 5);
				if (is_array($format)) {
					$meridiem = $format[$meridiem];
					return ($specifier[1] === 'P') ? strtolower($meridiem) : strtoupper($meridiem);
				}
				break;
			case 'r':
				$complete = __dc('cake', 't_fmt_ampm', 5);
				if ($complete !== 't_fmt_ampm') {
					return str_replace('%p', static::_translateSpecifier(array('%p', 'p')), $complete);
				}
				break;
			case 'R':
				return date('H:i', static::$_time);
			case 't':
				return "\t";
			case 'T':
				return '%H:%M:%S';
			case 'u':
				return ($weekDay = date('w', static::$_time)) ? $weekDay : 7;
			case 'x':
				$format = __dc('cake', 'd_fmt', 5);
				if ($format !== 'd_fmt') {
					return static::convertSpecifiers($format, static::$_time);
				}
				break;
			case 'X':
				$format = __dc('cake', 't_fmt', 5);
				if ($format !== 't_fmt') {
					return static::convertSpecifiers($format, static::$_time);
				}
				break;
		}
		return $specifier[0];
	}

/**
 * Converts given time (in server's time zone) to user's local time, given his/her timezone.
 *
 * @param string $serverTime UNIX timestamp
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return integer UNIX timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::convert
 */
	public static function convert($serverTime, $timezone) {
		static $serverTimezone = null;
		if ($serverTimezone === null || (date_default_timezone_get() !== $serverTimezone->getName())) {
			$serverTimezone = new \DateTimeZone(date_default_timezone_get());
		}
		$serverOffset = $serverTimezone->getOffset(new \DateTime('@' . $serverTime));
		$gmtTime = $serverTime - $serverOffset;
		if (is_numeric($timezone)) {
			$userOffset = $timezone * (60 * 60);
		} else {
			$timezone = static::timezone($timezone);
			$userOffset = $timezone->getOffset(new \DateTime('@' . $gmtTime));
		}
		$userTime = $gmtTime + $userOffset;
		return (int)$userTime;
	}

/**
 * Returns a timezone object from a string or the user's timezone object
 *
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * 	If null it tries to get timezone from 'Config.timezone' config var
 * @return DateTimeZone Timezone object
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::timezone
 */
	public static function timezone($timezone = null) {
		static $tz = null;

		if (is_object($timezone)) {
			if ($tz === null || $tz->getName() !== $timezone->getName()) {
				$tz = $timezone;
			}
		} else {
			if ($timezone === null) {
				$timezone = Configure::read('Config.timezone');
				if ($timezone === null) {
					$timezone = date_default_timezone_get();
				}
			}

			if ($tz === null || $tz->getName() !== $timezone) {
				$tz = new \DateTimeZone($timezone);
			}
		}

		return $tz;
	}

/**
 * Returns a UNIX timestamp, given either a UNIX timestamp or a valid strtotime() date string.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Parsed timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::fromString
 */
	public static function fromString($dateString, $timezone = null) {
		if (empty($dateString)) {
			return false;
		}

		$containsDummyDate = (is_string($dateString) && substr($dateString, 0, 10) === '0000-00-00');
		if ($containsDummyDate) {
			return false;
		}

		if (is_int($dateString) || is_numeric($dateString)) {
			$date = intval($dateString);
		} elseif (
			$dateString instanceof \DateTime &&
			$dateString->getTimezone()->getName() !== date_default_timezone_get()
		) {
			$clone = clone $dateString;
			$clone->setTimezone(new \DateTimeZone(date_default_timezone_get()));
			$date = (int)$clone->format('U') + $clone->getOffset();
		} elseif ($dateString instanceof \DateTime) {
			$date = (int)$dateString->format('U');
		} else {
			$date = strtotime($dateString);
		}

		if ($date === -1 || empty($date)) {
			return false;
		}

		if ($timezone === null) {
			$timezone = Configure::read('Config.timezone');
		}

		if ($timezone !== null) {
			return static::convert($date, $timezone);
		}
		return $date;
	}

/**
 * Returns a nicely formatted date string for given Datetime string.
 *
 * See http://php.net/manual/en/function.strftime.php for information on formatting
 * using locale strings.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @param string $format The format to use. If null, `TimeHelper::$niceFormat` is used
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::nice
 */
	public static function nice($dateString = null, $timezone = null, $format = null) {
		if (!$dateString) {
			$dateTime = new \DateTime;
			$dateString = $dateTime->getTimestamp();
		}
		$date = static::fromString($dateString, $timezone);

		if (!$format) {
			$format = static::$niceFormat;
		}
		return static::_strftime(static::convertSpecifiers($format, $date), $date);
	}

/**
 * Returns a partial SQL string to search for all records between two dates.
 *
 * @param integer|string|DateTime $begin UNIX timestamp, strtotime() valid string or DateTime object
 * @param integer|string|DateTime $end UNIX timestamp, strtotime() valid string or DateTime object
 * @param string $fieldName Name of database field to compare with
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Partial SQL string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::daysAsSql
 */
	public static function daysAsSql($begin, $end, $fieldName, $timezone = null) {
		$dateTime = new \DateTime;
		$begin = $dateTime->setTimestamp(static::fromString($begin, $timezone))
			->format('Y-m-d') . ' 00:00:00';
		$end = $dateTime->setTimestamp(static::fromString($end, $timezone))
			->format('Y-m-d') . ' 23:59:59';

		return "($fieldName >= '$begin') AND ($fieldName <= '$end')";
	}

/**
 * Returns a partial SQL string to search for all records between two times
 * occurring on the same day.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string $fieldName Name of database field to compare with
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Partial SQL string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::dayAsSql
 */
	public static function dayAsSql($dateString, $fieldName, $timezone = null) {
		return static::daysAsSql($dateString, $dateString, $fieldName, $timezone);
	}

/**
 * Returns true if given datetime string is today.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is today
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isToday
 */
	public static function isToday($dateString, $timezone = null) {
		return static::_isWithinTimeSpan($dateString, 'now', 'Y-m-d', $timezone);
	}

/**
 * Returns true if given datetime string is in the future.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is in the future
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isFuture
 */
	public static function isFuture($dateString, $timezone = null) {
		$timestamp = static::fromString($dateString, $timezone);
		$dateTime = new \DateTime;
		return $timestamp > $dateTime->getTimestamp();
	}

/**
 * Returns true if given datetime string is in the past.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is in the past
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isPast
 */
	public static function isPast($dateString, $timezone = null) {
		$timestamp = static::fromString($dateString, $timezone);
		$dateTime = new \DateTime;
		return $timestamp < $dateTime->getTimestamp();
	}

/**
 * Returns true if given datetime string is within this week.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is within current week
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isThisWeek
 */
	public static function isThisWeek($dateString, $timezone = null) {
		return static::_isWithinTimeSpan($dateString, 'now', 'W o', $timezone);
	}

/**
 * Returns true if given datetime string is within this month
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is within current month
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isThisMonth
 */
	public static function isThisMonth($dateString, $timezone = null) {
		return static::_isWithinTimeSpan($dateString, 'now', 'm Y', $timezone);
	}

/**
 * Returns true if given datetime string is within current year.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is within current year
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isThisYear
 */
	public static function isThisYear($dateString, $timezone = null) {
		return static::_isWithinTimeSpan($dateString, 'now', 'Y', $timezone);
	}

/**
 * Returns true if given datetime string was yesterday.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string was yesterday
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::wasYesterday
 */
	public static function wasYesterday($dateString, $timezone = null) {
		return static::_isWithinTimeSpan($dateString, 'yesterday', 'Y-m-d', $timezone);
	}

/**
 * Returns true if given datetime string is tomorrow.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string was yesterday
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isTomorrow
 */
	public static function isTomorrow($dateString, $timezone = null) {
		return static::_isWithinTimeSpan($dateString, 'tomorrow', 'Y-m-d', $timezone);
	}

/**
 * Returns the quarter
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param boolean $range if true returns a range in Y-m-d format
 * @return mixed 1, 2, 3, or 4 quarter of year or array if $range true
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toQuarter
 */
	public static function toQuarter($dateString, $range = false) {
		$dateTime = new \DateTime;
		$dateTime->setTimestamp(static::fromString($dateString));

		$quarter = ceil($dateTime->format('m') / 3);
		if ($range === false) {
			return $quarter;
		}

		$year = $dateTime->format('Y');
		switch ($quarter) {
			case 1:
				return array($year . '-01-01', $year . '-03-31');
			case 2:
				return array($year . '-04-01', $year . '-06-30');
			case 3:
				return array($year . '-07-01', $year . '-09-30');
			case 4:
				return array($year . '-10-01', $year . '-12-31');
		}
	}

/**
 * Returns a UNIX timestamp from a textual datetime description. Wrapper for PHP function strtotime().
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return integer Unix timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toUnix
 */
	public static function toUnix($dateString, $timezone = null) {
		return static::fromString($dateString, $timezone);
	}

/**
 * Returns a formatted date in server's timezone.
 *
 * If a DateTime object is given or the dateString has a timezone
 * segment, the timezone parameter will be ignored.
 *
 * If no timezone parameter is given and no DateTime object, the passed $dateString will be
 * considered to be in the UTC timezone.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @param string $format date format string
 * @return mixed Formatted date
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toServer
 */
	public static function toServer($dateString, $timezone = null, $format = 'Y-m-d H:i:s') {
		if ($timezone === null) {
			$timezone = new \DateTimeZone('UTC');
		} elseif (is_string($timezone)) {
			$timezone = new \DateTimeZone($timezone);
		} elseif (!($timezone instanceof \DateTimeZone)) {
			return false;
		}

		if ($dateString instanceof \DateTime) {
			$date = $dateString;
		} elseif (is_int($dateString) || is_numeric($dateString)) {
			$dateString = (int)$dateString;

			$date = new \DateTime('@' . $dateString);
			$date->setTimezone($timezone);
		} else {
			$date = new \DateTime($dateString, $timezone);
		}

		$date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
		return $date->format($format);
	}

/**
 * Returns a date formatted for Atom RSS feeds.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toAtom
 */
	public static function toAtom($dateString, $timezone = null) {
		$dateTime = new \DateTime;
		return $dateTime->setTimestamp(static::fromString($dateString, $timezone))
			->format($dateTime::ATOM);
	}

/**
 * Formats date for RSS feeds
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toRSS
 */
	public static function toRSS($dateString, $timezone = null) {
		$dateTime = new \DateTime;
		$date = static::fromString($dateString, $timezone);

		if ($timezone === null) {
			return $dateTime->setTimestamp($date)
				->format('r');
		}

		$userOffset = $timezone;
		if (!is_numeric($timezone)) {
			if (!is_object($timezone)) {
				$timezone = new \DateTimeZone($timezone);
			}
			$currentDate = new \DateTime('@' . $date);
			$currentDate->setTimezone($timezone);
			$userOffset = $timezone->getOffset($currentDate) / 60 / 60;
		}

		$timezone = '+0000';
		if ($userOffset != 0) {
			$hours = (int)floor(abs($userOffset));
			$minutes = (int)(fmod(abs($userOffset), $hours) * 60);
			$timezone = ($userOffset < 0 ? '-' : '+') . str_pad($hours, 2, '0', STR_PAD_LEFT) . str_pad($minutes, 2, '0', STR_PAD_LEFT);
		}
		return $dateTime->setTimestamp($date)
			->format('D, d M Y H:i:s') . ' ' . $timezone;
	}

/**
 * Returns either a relative or a formatted absolute date depending
 * on the difference between the current time and given datetime.
 * $datetime should be in a *strtotime* - parsable format, like MySQL's datetime datatype.
 *
 * ### Options:
 *
 * - `format` => a fall back format if the relative time is longer than the duration specified by end
 * - `accuracy` => Specifies how accurate the date should be described (array)
 *    - year =>   The format if years > 0   (default "day")
 *    - month =>  The format if months > 0  (default "day")
 *    - week =>   The format if weeks > 0   (default "day")
 *    - day =>    The format if weeks > 0   (default "hour")
 *    - hour =>   The format if hours > 0   (default "minute")
 *    - minute => The format if minutes > 0 (default "minute")
 *    - second => The format if seconds > 0 (default "second")
 * - `end` => The end of relative time telling
 * - `relativeString` => The printf compatible string when outputting relative time
 * - `absoluteString` => The printf compatible string when outputting absolute time
 * - `userOffset` => Users offset from GMT (in hours) *Deprecated* use timezone intead.
 * - `timezone` => The user timezone the timestamp should be formatted in.
 *
 * Relative dates look something like this:
 *
 * - 3 weeks, 4 days ago
 * - 15 seconds ago
 *
 * Default date formatting is d/m/yy e.g: on 18/2/09
 *
 * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
 * like 'Posted ' before the function output.
 *
 * NOTE: If the difference is one week or more, the lowest level of accuracy is day
 *
 * @param integer|string|DateTime $dateTime Datetime UNIX timestamp, strtotime() valid string or DateTime object
 * @param array $options Default format if timestamp is used in $dateString
 * @return string Relative time string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::timeAgoInWords
 */
	public static function timeAgoInWords($dateTime, $options = array()) {
		$timezone = null;
		$format = static::$wordFormat;
		$end = static::$wordEnd;
		$relativeString = __d('cake', '%s ago');
		$absoluteString = __d('cake', 'on %s');
		$accuracy = static::$wordAccuracy;

		if (is_array($options)) {
			if (isset($options['timezone'])) {
				$timezone = $options['timezone'];
			} elseif (isset($options['userOffset'])) {
				$timezone = $options['userOffset'];
			}

			if (isset($options['accuracy'])) {
				if (is_array($options['accuracy'])) {
					$accuracy = array_merge($accuracy, $options['accuracy']);
				} else {
					foreach ($accuracy as $key => $level) {
						$accuracy[$key] = $options['accuracy'];
					}
				}
			}

			if (isset($options['format'])) {
				$format = $options['format'];
			}
			if (isset($options['end'])) {
				$end = $options['end'];
			}
			if (isset($options['relativeString'])) {
				$relativeString = $options['relativeString'];
				unset($options['relativeString']);
			}
			if (isset($options['absoluteString'])) {
				$absoluteString = $options['absoluteString'];
				unset($options['absoluteString']);
			}
			unset($options['end'], $options['format']);
		} else {
			$format = $options;
		}

		$now = static::fromString(time(), $timezone);
		$inSeconds = static::fromString($dateTime, $timezone);
		$backwards = ($inSeconds > $now);

		$futureTime = $now;
		$pastTime = $inSeconds;
		if ($backwards) {
			$futureTime = $inSeconds;
			$pastTime = $now;
		}
		$diff = $futureTime - $pastTime;

		if (!$diff) {
			return __d('cake', 'just now', 'just now');
		}

		if ($diff > abs($now - self::fromString($end))) {
			return sprintf($absoluteString, date($format, $inSeconds));
		}

		// If more than a week, then take into account the length of months
		if ($diff >= 604800) {
			list($future['H'], $future['i'], $future['s'], $future['d'], $future['m'], $future['Y']) = explode('/', date('H/i/s/d/m/Y', $futureTime));

			list($past['H'], $past['i'], $past['s'], $past['d'], $past['m'], $past['Y']) = explode('/', date('H/i/s/d/m/Y', $pastTime));
			$years = $months = $weeks = $days = $hours = $minutes = $seconds = 0;

			$years = $future['Y'] - $past['Y'];
			$months = $future['m'] + ((12 * $years) - $past['m']);

			if ($months >= 12) {
				$years = floor($months / 12);
				$months = $months - ($years * 12);
			}
			if ($future['m'] < $past['m'] && $future['Y'] - $past['Y'] === 1) {
				$years--;
			}

			if ($future['d'] >= $past['d']) {
				$days = $future['d'] - $past['d'];
			} else {
				$daysInPastMonth = date('t', $pastTime);
				$daysInFutureMonth = date('t', mktime(0, 0, 0, $future['m'] - 1, 1, $future['Y']));

				if (!$backwards) {
					$days = ($daysInPastMonth - $past['d']) + $future['d'];
				} else {
					$days = ($daysInFutureMonth - $past['d']) + $future['d'];
				}

				if ($future['m'] != $past['m']) {
					$months--;
				}
			}

			if (!$months && $years >= 1 && $diff < ($years * 31536000)) {
				$months = 11;
				$years--;
			}

			if ($months >= 12) {
				$years = $years + 1;
				$months = $months - 12;
			}

			if ($days >= 7) {
				$weeks = floor($days / 7);
				$days = $days - ($weeks * 7);
			}
		} else {
			$years = $months = $weeks = 0;
			$days = floor($diff / 86400);

			$diff = $diff - ($days * 86400);

			$hours = floor($diff / 3600);
			$diff = $diff - ($hours * 3600);

			$minutes = floor($diff / 60);
			$diff = $diff - ($minutes * 60);
			$seconds = $diff;
		}

		$fWord = $accuracy['second'];
		if ($years > 0) {
			$fWord = $accuracy['year'];
		} elseif (abs($months) > 0) {
			$fWord = $accuracy['month'];
		} elseif (abs($weeks) > 0) {
			$fWord = $accuracy['week'];
		} elseif (abs($days) > 0) {
			$fWord = $accuracy['day'];
		} elseif (abs($hours) > 0) {
			$fWord = $accuracy['hour'];
		} elseif (abs($minutes) > 0) {
			$fWord = $accuracy['minute'];
		}

		$fNum = str_replace(array('year', 'month', 'week', 'day', 'hour', 'minute', 'second'), array(1, 2, 3, 4, 5, 6, 7), $fWord);

		$relativeDate = '';
		if ($fNum >= 1 && $years > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '%d year', '%d years', $years, $years);
		}
		if ($fNum >= 2 && $months > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '%d month', '%d months', $months, $months);
		}
		if ($fNum >= 3 && $weeks > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '%d week', '%d weeks', $weeks, $weeks);
		}
		if ($fNum >= 4 && $days > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '%d day', '%d days', $days, $days);
		}
		if ($fNum >= 5 && $hours > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '%d hour', '%d hours', $hours, $hours);
		}
		if ($fNum >= 6 && $minutes > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '%d minute', '%d minutes', $minutes, $minutes);
		}
		if ($fNum >= 7 && $seconds > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '%d second', '%d seconds', $seconds, $seconds);
		}

		// When time has passed
		if (!$backwards && $relativeDate) {
			return sprintf($relativeString, $relativeDate);
		}
		if (!$backwards) {
			$aboutAgo = array(
				'second' => __d('cake', 'about a second ago'),
				'minute' => __d('cake', 'about a minute ago'),
				'hour' => __d('cake', 'about an hour ago'),
				'day' => __d('cake', 'about a day ago'),
				'week' => __d('cake', 'about a week ago'),
				'year' => __d('cake', 'about a year ago')
			);

			return $aboutAgo[$fWord];
		}

		// When time is to come
		if (!$relativeDate) {
			$aboutIn = array(
				'second' => __d('cake', 'in about a second'),
				'minute' => __d('cake', 'in about a minute'),
				'hour' => __d('cake', 'in about an hour'),
				'day' => __d('cake', 'in about a day'),
				'week' => __d('cake', 'in about a week'),
				'year' => __d('cake', 'in about a year')
			);

			return $aboutIn[$fWord];
		}

		return $relativeDate;
	}

/**
 * Returns true if specified datetime was within the interval specified, else false.
 *
 * @param string|integer $timeInterval the numeric value with space then time type.
 *    Example of valid types: 6 hours, 2 days, 1 minute.
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::wasWithinLast
 */
	public static function wasWithinLast($timeInterval, $dateString, $timezone = null) {
		$tmp = str_replace(' ', '', $timeInterval);
		if (is_numeric($tmp)) {
			$timeInterval = $tmp . ' ' . __d('cake', 'days');
		}

		$date = static::fromString($dateString, $timezone);
		$interval = static::fromString('-' . $timeInterval);
		$now = static::fromString('now', $timezone);

		return $date >= $interval && $date <= $now;
	}

/**
 * Returns true if specified datetime is within the interval specified, else false.
 *
 * @param string|integer $timeInterval the numeric value with space then time type.
 *    Example of valid types: 6 hours, 2 days, 1 minute.
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean
 */
	public static function isWithinNext($timeInterval, $dateString, $timezone = null) {
		$tmp = str_replace(' ', '', $timeInterval);
		if (is_numeric($tmp)) {
			$timeInterval = $tmp . ' ' . __d('cake', 'days');
		}

		$date = static::fromString($dateString, $timezone);
		$interval = static::fromString('+' . $timeInterval);
		$now = static::fromString('now', $timezone);

		return $date <= $interval && $date >= $now;
	}

/**
 * Returns gmt as a UNIX timestamp.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @return integer UNIX timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::gmt
 */
	public static function gmt($dateString = null) {
		$dateTime = new \DateTime;
		$time = $dateTime->getTimestamp();
		if ($dateString) {
			$time = static::fromString($dateString);
		}
		return gmmktime(
			intval(date('G', $time)),
			intval(date('i', $time)),
			intval(date('s', $time)),
			intval(date('n', $time)),
			intval(date('j', $time)),
			intval(date('Y', $time))
		);
	}

/**
 * Returns a formatted date string, given either a UNIX timestamp or a valid strtotime() date string.
 * This function also accepts a time string and a format string as first and second parameters.
 * In that case this function behaves as a wrapper for TimeHelper::i18nFormat()
 *
 * ## Examples
 *
 * Create localized & formatted time:
 *
 * {{{
 *   Cake\Utility\Time::format('2012-02-15', '%m-%d-%Y'); // returns 02-15-2012
 *   Cake\Utility\Time::format('2012-02-15 23:01:01', '%c'); // returns preferred date and time based on configured locale
 *   Cake\Utility\Time::format('0000-00-00', '%d-%m-%Y', 'N/A'); // return N/A becuase an invalid date was passed
 *   Cake\Utility\Time::format('2012-02-15 23:01:01', '%c', 'N/A', 'America/New_York'); // converts passed date to timezone
 * }}}
 *
 * @param integer|string|DateTime $date UNIX timestamp, strtotime() valid string or DateTime object (or a date format string)
 * @param integer|string|DateTime $format date format string (or UNIX timestamp, strtotime() valid string or DateTime object)
 * @param boolean|string $default if an invalid date is passed it will output supplied default value. Pass false if you want raw conversion value
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::format
 * @see \Cake\Utility\Time::i18nFormat()
 */
	public static function format($date, $format = null, $default = false, $timezone = null) {
		//Backwards compatible params re-order test
		$time = static::fromString($format, $timezone);

		if ($time === false) {
			return static::i18nFormat($date, $format, $default, $timezone);
		}
		return date($date, $time);
	}

/**
 * Returns a formatted date string, given either a UNIX timestamp or a valid strtotime() date string.
 * It takes into account the default date format for the current language if a LC_TIME file is used.
 *
 * @param integer|string|DateTime $date UNIX timestamp, strtotime() valid string or DateTime object
 * @param string $format strftime format string.
 * @param boolean|string $default if an invalid date is passed it will output supplied default value. Pass false if you want raw conversion value
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return string Formatted and translated date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::i18nFormat
 */
	public static function i18nFormat($date, $format = null, $default = false, $timezone = null) {
		$date = static::fromString($date, $timezone);
		if ($date === false && $default !== false) {
			return $default;
		}
		if (empty($format)) {
			$format = '%x';
		}
		return static::_strftime(static::convertSpecifiers($format, $date), $date);
	}

/**
 * Get list of timezone identifiers
 *
 * @param integer|string $filter A regex to filter identifer
 * 	Or one of DateTimeZone class constants
 * @param string $country A two-letter ISO 3166-1 compatible country code.
 * 	This option is only used when $filter is set to DateTimeZone::PER_COUNTRY
 * @param boolean $group If true (default value) groups the identifiers list by primary region
 * @return array List of timezone identifiers
 * @since 2.2
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::listTimezones
 */
	public static function listTimezones($filter = null, $country = null, $group = true) {
		$regex = null;
		if (is_string($filter)) {
			$regex = $filter;
			$filter = null;
		}
		if ($filter === null) {
			$filter = \DateTimeZone::ALL;
		}
		$identifiers = \DateTimeZone::listIdentifiers($filter, $country);

		if ($regex) {
			foreach ($identifiers as $key => $tz) {
				if (!preg_match($regex, $tz)) {
					unset($identifiers[$key]);
				}
			}
		}

		if ($group) {
			$groupedIdentifiers = array();
			foreach ($identifiers as $key => $tz) {
				$item = explode('/', $tz, 2);
				if (isset($item[1])) {
					$groupedIdentifiers[$item[0]][$tz] = $item[1];
				} else {
					$groupedIdentifiers[$item[0]] = array($tz => $item[0]);
				}
			}
			return $groupedIdentifiers;
		}
		return array_combine($identifiers, $identifiers);
	}

/**
 * Multibyte wrapper for strftime.
 *
 * Handles utf8_encoding the result of strftime when necessary.
 *
 * @param string $format Format string.
 * @param integer $date Timestamp to format.
 * @return string formatted string with correct encoding.
 */
	protected static function _strftime($format, $date) {
		$format = strftime($format, $date);
		$encoding = Configure::read('App.encoding');

		if ($encoding === 'UTF-8' && !mb_check_encoding($format, $encoding)) {
			$format = utf8_encode($format);
		}
		return $format;
	}

/**
 * Evaluates if the provided date and time is within a time span.
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string $anchor The relative date and time to compare against
 * @param string $format The date and time format used for comparison
 * @param string|DateTimeZone $timezone Timezone string or DateTimeZone object
 * @return boolean True if datetime string is within the time span
 */
	protected static function _isWithinTimeSpan($dateString, $anchor, $format, $timezone = null) {
		$dateTime = new \DateTime;
		$timestamp = $dateTime->setTimestamp(static::fromString($dateString, $timezone))
			->format($format);
		$now = $dateTime->setTimestamp(static::fromString($anchor, $timezone))
			->format($format);
		return $timestamp === $now;
	}

}
