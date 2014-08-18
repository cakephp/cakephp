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
namespace Cake\Utility;

use Carbon\Carbon;
use IntlDateFormatter;
use JsonSerializable;

/**
 * Extends the built-in DateTime class to provide handy methods and locale-aware
 * formatting helpers
 *
 */
class Time extends Carbon implements JsonSerializable {

/**
 * The format to use when formatting a time using `Cake\Utility\Time::i18nFormat()`
 * and `__toString`
 *
 * The format should be either the formatting constants from IntlDateFormatter as
 * described in (http://www.php.net/manual/en/class.intldateformatter.php) or a pattern
 * as specified in (http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details)
 *
 * It is possible to provide an array of 2 constants. In this case, the first position
 * will be used for formatting the date part of the object and the second position
 * will be used to format the time part.
 *
 * @var mixed
 * @see \Cake\Utility\Time::i18nFormat()
 */
	protected static $_toStringFormat = [IntlDateFormatter::SHORT, IntlDateFormatter::SHORT];

/**
 * The format to use when formatting a time using `Cake\Utility\Time::nice()`
 *
 * The format should be eiter the formatting constants from IntlDateFormatter as
 * described in (http://www.php.net/manual/en/class.intldateformatter.php) or a pattern
 * as specified in (http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details)
 *
 * It is possible to provide an array of 2 constants. In this case, the first position
 * will be used for formatting the date part of the object and the second position
 * will be used to format the time part.
 *
 * @var mixed
 * @see \Cake\Utility\Time::nice()
 */
	public static $niceFormat = [IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT];

/**
 * The default locale to be used for displaying formatted date strings.
 *
 * @var string
 */
	public static $defaultLocale;

/**
 * The format to use when formatting a time using `Cake\Utility\Time::timeAgoInWords()`
 * and the difference is more than `Cake\Utility\Time::$wordEnd`
 *
 * @var string
 * @see \Cake\Utility\Time::timeAgoInWords()
 */
	public static $wordFormat = [IntlDateFormatter::SHORT, -1];

/**
 * The format to use when formatting a time using `Time::timeAgoInWords()`
 * and the difference is less than `Time::$wordEnd`
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
 * In-memory cache of date formatters
 *
 * @var array
 */
	protected static $_formatters = [];

/**
 * {@inheritDoc}
 */
	public function __construct($time = null, $tz = null) {
		if ($time instanceof \DateTime) {
			list($time, $tz) = [$time->format('Y-m-d H:i:s'), $time->getTimeZone()];
		}

		if (is_numeric($time)) {
			$time = '@' . $time;
		}

		parent::__construct($time, $tz);
	}

/**
 * Returns a nicely formatted date string for this object.
 *
 * The format to be used is stored in the static property `Time::niceFormat`.
 *
 * @param string|\DateTimeZone $timezone Timezone string or DateTimeZone object
 * in which the date will be displayed. The timezone stored for this object will not
 * be changed.
 * @param string $locale The locale name in which the date should be displayed (e.g. pt-BR)
 * @return string Formatted date string
 */
	public function nice($timezone = null, $locale = null) {
		return $this->i18nFormat(static::$niceFormat, $timezone, $locale);
	}

/**
 * Returns true if this object represents a date within the current week
 *
 * @return bool
 */
	public function isThisWeek() {
		return static::now($this->getTimezone())->format('W o') == $this->format('W o');
	}

/**
 * Returns true if this object represents a date within the current month
 *
 * @return bool
 */
	public function isThisMonth() {
		return static::now($this->getTimezone())->format('m Y') == $this->format('m Y');
	}

/**
 * Returns true if this object represents a date within the current year
 *
 * @return bool
 */
	public function isThisYear() {
		return static::now($this->getTimezone())->format('Y') == $this->format('Y');
	}

/**
 * Returns the quarter
 *
 * @param bool $range Range.
 * @return mixed 1, 2, 3, or 4 quarter of year or array if $range true
 */
	public function toQuarter($range = false) {
		$quarter = ceil($this->format('m') / 3);
		if ($range === false) {
			return $quarter;
		}

		$year = $this->format('Y');
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
 * Returns a UNIX timestamp.
 *
 * @return string Unix timestamp
 */
	public function toUnixString() {
		return $this->format('U');
	}

/**
 * Returns either a relative or a formatted absolute date depending
 * on the difference between the current time and this object.
 *
 * ### Options:
 *
 * - `from` => another Time object representing the "now" time
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
 * - `timezone` => The user timezone the timestamp should be formatted in.
 *
 * Relative dates look something like this:
 *
 * - 3 weeks, 4 days ago
 * - 15 seconds ago
 *
 * Default date formatting is d/M/YY e.g: on 18/2/09. Formatting is done internally using
 * `i18nFormat`, see the method for the valid formatting strings
 *
 * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
 * like 'Posted ' before the function output.
 *
 * NOTE: If the difference is one week or more, the lowest level of accuracy is day
 *
 * @param array $options Array of options.
 * @return string Relative time string.
 */
	public function timeAgoInWords(array $options = []) {
		$timezone = null;
		$format = static::$wordFormat;
		$end = static::$wordEnd;
		$relativeString = __d('cake', '%s ago');
		$absoluteString = __d('cake', 'on %s');
		$accuracy = static::$wordAccuracy;
		$from = static::now();
		$opts = ['timezone', 'format', 'end', 'relativeString', 'absoluteString', 'from'];

		foreach ($opts as $option) {
			if (isset($options[$option])) {
				${$option} = $options[$option];
				unset($options[$option]);
			}
		}

		if (isset($options['accuracy'])) {
			if (is_array($options['accuracy'])) {
				$accuracy = $options['accuracy'] + $accuracy;
			} else {
				foreach ($accuracy as $key => $level) {
					$accuracy[$key] = $options['accuracy'];
				}
			}
		}

		$now = $from->format('U');
		$inSeconds = $this->format('U');
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

		if ($diff > abs($now - (new static($end))->format('U'))) {
			$absoluteTime = new static($inSeconds);
			return sprintf($absoluteString, $absoluteTime->i18nFormat($format));
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
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} year', '{0} years', $years, $years);
		}
		if ($fNum >= 2 && $months > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} month', '{0} months', $months, $months);
		}
		if ($fNum >= 3 && $weeks > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} week', '{0} weeks', $weeks, $weeks);
		}
		if ($fNum >= 4 && $days > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} day', '{0} days', $days, $days);
		}
		if ($fNum >= 5 && $hours > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} hour', '{0} hours', $hours, $hours);
		}
		if ($fNum >= 6 && $minutes > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} minute', '{0} minutes', $minutes, $minutes);
		}
		if ($fNum >= 7 && $seconds > 0) {
			$relativeDate .= ($relativeDate ? ', ' : '') . __dn('cake', '{0} second', '{0} seconds', $seconds, $seconds);
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
 * Returns the difference between this date and the provided one in a human
 * readable format.
 *
 * See `Time::timeAgoInWords()` for a full list of options that can be passed
 * to this method.
 *
 * @param \Carbon\Carbon $other the date to diff with
 * @param array $options options accepted by timeAgoInWords
 * @return string
 * @see Time::timeAgoInWords()
 */
	public function diffForHumans(Carbon $other = null, array $options = []) {
		$options = ['from' => $other] + $options;
		return $this->timeAgoInWords($options);
	}

/**
 * Returns true this instance happened within the specified interval
 *
 * @param string|int $timeInterval the numeric value with space then time type.
 *    Example of valid types: 6 hours, 2 days, 1 minute.
 * @return bool
 */
	public function wasWithinLast($timeInterval) {
		$tmp = str_replace(' ', '', $timeInterval);
		if (is_numeric($tmp)) {
			$timeInterval = $tmp . ' days';
		}

		$interval = new static('-' . $timeInterval);
		$now = new static();

		return $this >= $interval && $this <= $now;
	}

/**
 * Returns true this instance will happen within the specified interval
 *
 * @param string|int $timeInterval the numeric value with space then time type.
 *    Example of valid types: 6 hours, 2 days, 1 minute.
 * @return bool
 */
	public function isWithinNext($timeInterval) {
		$tmp = str_replace(' ', '', $timeInterval);
		if (is_numeric($tmp)) {
			$timeInterval = $tmp . ' days';
		}

		$interval = new static('+' . $timeInterval);
		$now = new static();

		return $this <= $interval && $this >= $now;
	}

/**
 * Returns a formatted string for this time object using the preferred format and
 * language for the specified locale.
 *
 * It is possible to specify the desired format for the string to be displayed.
 * You can either pass `IntlDateFormatter` constants as the first argument of this
 * function, or pass a full ICU date formatting string as specified in the following
 * resource: http://www.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details.
 *
 * ## Examples
 *
 * {{{
 * $time = new Time('2014-04-20 22:10');
 * $time->i18nFormat(); // outputs '4/20/14, 10:10 PM' for the en-US locale
 * $time->i18nFormat(\IntlDateFormatter::FULL); // Use the full date and time format
 * $time->i18nFormat([\IntlDateFormatter::FULL, \IntlDateFormatter::Short]); // Use full date but short time format
 * $time->i18nFormat('YYYY-MM-dd HH:mm:ss'); // outputs '2014-04-20 22:10'
 * }}}
 *
 * If you wish to control the default format to be used for this method, you can alter
 * the value of the static `Time::$defaultLocale` variable and set it to one of the
 * possible formats accepted by this function.
 *
 * You can read about the available IntlDateFormatter constants at
 * http://www.php.net/manual/en/class.intldateformatter.php
 *
 * If you need to display the date in a different timezone than the one being used for
 * this Time object without altering its internal state, you can pass a timezone
 * string or object as the second parameter.
 *
 * Finally, should you need to use a different locale for displaying this time object,
 * pass a locale string as the third parameter to this function.
 *
 * ## Examples
 *
 * {{{
 * $time = new Time('2014-04-20 22:10');
 * $time->i18nFormat(null, null, 'de-DE');
 * $time->i18nFormat(\IntlDateFormatter::FULL, 'Europe/Berlin', 'de-DE');
 * }}}
 *
 * You can control the default locale to be used by setting the static variable
 * `Time::$defaultLocale` to a  valid locale string. If empty, the default will be
 * taken from the `intl.default_locale` ini config.
 *
 * @param string|int $format Format string.
 * @param string|\DateTimeZone $timezone Timezone string or DateTimeZone object
 * in which the date will be displayed. The timezone stored for this object will not
 * be changed.
 * @param string $locale The locale name in which the date should be displayed (e.g. pt-BR)
 * @return string Formatted and translated date string
 */
	public function i18nFormat($format = null, $timezone = null, $locale = null) {
		$time = $this;

		if ($timezone) {
			$time = clone $this;
			$time->timezone($timezone);
		}

		$format = $format !== null ? $format : static::$_toStringFormat;
		$locale = $locale ?: static::$defaultLocale;
		return $this->_formatObject($time, $format, $locale);
	}

/**
 * Returns a translated and localized date string.
 * Implements what IntlDateFormatter::formatObject() is in PHP 5.5+
 *
 * @param \DateTime $date Date.
 * @param string|int|array $format Format.
 * @param string $locale The locale name in which the date should be displayed.
 * @return string
 */
	protected function _formatObject($date, $format, $locale) {
		$pattern = $dateFormat = $timeFormat = $calendar = null;

		if (is_array($format)) {
			list($dateFormat, $timeFormat) = $format;
		} elseif (is_numeric($format)) {
			$dateFormat = $format;
		} else {
			$dateFormat = $timeFormat = IntlDateFormatter::FULL;
			$pattern = $format;
		}

		$timezone = $date->getTimezone()->getName();
		$key = "{$locale}.{$dateFormat}.{$timeFormat}.{$timezone}.{$calendar}.{$pattern}";

		if (!isset(static::$_formatters[$key])) {
			static::$_formatters[$key] = datefmt_create(
				$locale,
				$dateFormat,
				$timeFormat,
				$timezone === '+00:00' ? 'UTC' : $timezone,
				$calendar,
				$pattern
			);
		}

		return static::$_formatters[$key]->format($date);
	}

/**
 * {@inheritDoc}
 */
	public function __toString() {
		return $this->i18nFormat();
	}

/**
 * Get list of timezone identifiers
 *
 * @param int|string $filter A regex to filter identifer
 *   Or one of DateTimeZone class constants
 * @param string $country A two-letter ISO 3166-1 compatible country code.
 *   This option is only used when $filter is set to DateTimeZone::PER_COUNTRY
 * @param bool $group If true (default value) groups the identifiers list by primary region
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
 * Resets the format used to the default when converting an instance of this type to
 * a string
 *
 * @return void
 */
	public static function resetToStringFormat() {
		static::setToStringFormat([IntlDateFormatter::SHORT, IntlDateFormatter::SHORT]);
	}

/**
 * Sets the default format used when type converting instances of this type to string
 *
 * @param string|int $format Format.
 * @return void
 */
	public static function setToStringFormat($format) {
		static::$_toStringFormat = $format;
	}

/**
 * Returns a string that should be serialized when converting this object to json
 *
 * @return string
 */
	public function jsonSerialize() {
		return $this->format(static::ISO8601);
	}

/**
 * Returns the data that should be displayed when debugging this object
 *
 * @return array
 */
	public function __debugInfo() {
		return [
			'time' => $this->format(static::ISO8601),
			'timezone' => $this->getTimezone()->getName(),
			'fixedNowTime' => $this->hasTestNow() ? $this->getTestNow()->format(static::ISO8601) : false
		];
	}

}
