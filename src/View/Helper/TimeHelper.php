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
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Utility\Time;
use Cake\View\Helper;
use Cake\View\Helper\StringTemplateTrait;

/**
 * Time Helper class for easy use of time data.
 *
 * Manipulation of time data.
 *
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html
 * @see \Cake\Utility\Time
 */
class TimeHelper extends Helper {

	use StringTemplateTrait;

/**
 * Returns a UNIX timestamp, given either a UNIX timestamp or a valid strtotime() date string.
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return Cake\Utility\Time
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function fromString($dateString, $timezone = null) {
		return (new Time($dateString))->timezone($timezone);
	}

/**
 * Returns a nicely formatted date string for given Datetime string.
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @param string $format The format to use. If null, `CakeTime::$niceFormat` is used
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function nice($dateString = null, $timezone = null, $locale = null) {
		return (new Time($dateString))->nice($timezone, $locale);
	}

/**
 * Returns true if given datetime string is today.
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return bool True if datetime string is today
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isToday($dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->isToday();
	}

/**
 * Returns true if given datetime string is in the future.
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return bool True if datetime string is today
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isFuture($dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->isFuture();
	}

/**
 * Returns true if given datetime string is in the past.
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return bool True if datetime string is today
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isPast($dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->isPast();
	}

/**
 * Returns true if given datetime string is within this week.
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return bool True if datetime string is within current week
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isThisWeek($dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->isThisWeek();
	}

/**
 * Returns true if given datetime string is within this month
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return bool True if datetime string is within current month
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isThisMonth($dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->isThisMonth();
	}

/**
 * Returns true if given datetime string is within current year.
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return bool True if datetime string is within current year
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isThisYear($dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->isThisYear();
	}

/**
 * Returns true if given datetime string was yesterday.
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return bool True if datetime string was yesterday
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 *
 */
	public function wasYesterday($dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->isYesterday();
	}

/**
 * Returns true if given datetime string is tomorrow.
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return bool True if datetime string was yesterday
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isTomorrow($dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->isTomorrow();
	}

/**
 * Returns the quarter
 *
 * @see \Cake\Utility\Time::toQuarter()
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param bool $range if true returns a range in Y-m-d format
 * @return mixed 1, 2, 3, or 4 quarter of year or array if $range true
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toQuarter($dateString, $range = false) {
		return (new Time($dateString))->toQuarter($range);
	}

/**
 * Returns a UNIX timestamp from a textual datetime description. Wrapper for PHP function strtotime().
 *
 * @see \Cake\Utility\Time::toUnix()
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return int Unix timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toUnix($dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->toUnixString();
	}

/**
 * Returns a date formatted for Atom RSS feeds.
 *
 * @see \Cake\Utility\Time::toAtom()
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toAtom($dateString, $timezone = null) {
		$timezone = $timezone ?: date_default_timezone_get();
		return (new Time($dateString))->timezone($timezone)->toATOMString();
	}

/**
 * Formats date for RSS feeds
 *
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toRSS($dateString, $timezone = null) {
		$timezone = $timezone ?: date_default_timezone_get();
		return (new Time($dateString))->timezone($timezone)->toRSSString();
	}

/**
 * Formats date for RSS feeds
 *
 * @see \Cake\Utility\Time::timeAgoInWords()
 *
 * ## Additional options
 *
 * - `element` - The element to wrap the formatted time in.
 *   Has a few additional options:
 *   - `tag` - The tag to use, defaults to 'span'.
 *   - `class` - The class name to use, defaults to `time-ago-in-words`.
 *   - `title` - Defaults to the $dateTime input.
 *
 * @param int|string|\DateTime $dateTime UNIX timestamp, strtotime() valid string or DateTime object
 * @param array $options Default format if timestamp is used in $dateString
 * @return string Relative time string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function timeAgoInWords($dateTime, array $options = array()) {
		$element = null;

		if (!empty($options['element'])) {
			$element = array(
				'tag' => 'span',
				'class' => 'time-ago-in-words',
				'title' => $dateTime
			);

			if (is_array($options['element'])) {
				$element = $options['element'] + $element;
			} else {
				$element['tag'] = $options['element'];
			}
			unset($options['element']);
		}
		$relativeDate = (new Time($dateTime))->timeAgoInWords($options);

		if ($element) {
			$relativeDate = sprintf(
				'<%s%s>%s</%s>',
				$element['tag'],
				$this->templater()->formatAttributes($element, array('tag')),
				$relativeDate,
				$element['tag']
			);
		}
		return $relativeDate;
	}

/**
 * Returns true if specified datetime was within the interval specified, else false.
 * @see \Cake\Utility\Time::wasWithinLast()
 *
 * @param string|int $timeInterval the numeric value with space then time type.
 *    Example of valid types: 6 hours, 2 days, 1 minute.
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return bool
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function wasWithinLast($timeInterval, $dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->wasWithinLast($timeInterval);
	}

/**
 * Returns true if specified datetime is within the interval specified, else false.
 *
 * @see \Cake\Utility\Time::isWithinLast()
 *
 * @param string|int $timeInterval the numeric value with space then time type.
 *    Example of valid types: 6 hours, 2 days, 1 minute.
 * @param int|string|\DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return bool
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isWithinNext($timeInterval, $dateString, $timezone = null) {
		return (new Time($dateString, $timezone))->isWithinNext($timeInterval);
	}

/**
 * Returns gmt as a UNIX timestamp.
 *
 * @see \Cake\Utility\Time::gmt()
 *
 * @param int|string|\DateTime $string UNIX timestamp, strtotime() valid string or DateTime object
 * @return int UNIX timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function gmt($string = null) {
		return (new Time($string))->toUnixString();
	}

/**
 * Returns a formatted date string, given either a UNIX timestamp or a valid strtotime() date string.
 * This function also accepts a time string and a format string as first and second parameters.
 * In that case this function behaves as a wrapper for Time::i18nFormat()
 *
 * @see \Cake\Utility\Time::i18nFormat()
 *
 * @param int|string|\DateTime $date UNIX timestamp, strtotime() valid string or DateTime object (or a date format string)
 * @param int|string $format date format string (or a UNIX timestamp, strtotime() valid string or DateTime object)
 * @param bool|string $invalid Default value to display on invalid dates
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Formatted and translated date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function format($date, $format = null, $invalid = false, $timezone = null) {
		return $this->i18nFormat($date, $format, $invalid, $timezone);
	}

/**
 * Returns a formatted date string, given either a UNIX timestamp or a valid strtotime() date string.
 * It takes into account the default date format for the current language if a LC_TIME file is used.
 *
 * @see \Cake\Utility\Time::i18nFormat()
 *
 * @param int|string|\DateTime $date UNIX timestamp, strtotime() valid string or DateTime object
 * @param string $format strftime format string.
 * @param bool|string $invalid Default value to display on invalid dates
 * @param string|\DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Formatted and translated date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 * @throws \InvalidArgumentException When the date cannot be parsed
 */
	public function i18nFormat($date, $format = null, $invalid = false, $timezone = null) {
		try {
			$time = new Time($date, $timezone);
			return $time->i18nFormat($format, $timezone);
		} catch (\Exception $e) {
			if ($invalid === false) {
				throw $e;
			}
			return $invalid;
		}
	}

/**
 * Event listeners.
 *
 * @return array
 */
	public function implementedEvents() {
		return [];
	}

}
