<?php
/**
 * Time Helper class file.
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
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeTime', 'Utility');
App::uses('Multibyte', 'I18n');
App::uses('AppHelper', 'View/Helper');

/**
 * Time Helper class for easy use of time data.
 *
 * Manipulation of time data.
 *
 * @package       Cake.View.Helper
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html
 * @see CakeTime
 */
class TimeHelper extends AppHelper {

/**
 * CakeTime instance
 *
 * @var stdClass
 */
	protected $_engine = null;

/**
 * Constructor
 *
 * ### Settings:
 *
 * - `engine` Class name to use to replace CakeTime functionality
 *            The class needs to be placed in the `Utility` directory.
 *
 * @param View $View the view object the helper is attached to.
 * @param array $settings Settings array
 * @throws CakeException When the engine class could not be found.
 */
	public function __construct(View $View, $settings = array()) {
		$settings = Hash::merge(array('engine' => 'CakeTime'), $settings);
		parent::__construct($View, $settings);
		list($plugin, $engineClass) = pluginSplit($settings['engine'], true);
		App::uses($engineClass, $plugin . 'Utility');
		if (class_exists($engineClass)) {
			$this->_engine = new $engineClass($settings);
		} else {
			throw new CakeException(__d('cake_dev', '%s could not be found', $engineClass));
		}
	}

/**
 * Magic accessor for deprecated attributes.
 *
 * @param string $name Name of the attribute to set.
 * @param string $value Value of the attribute to set.
 * @return void
 */
	public function __set($name, $value) {
		switch ($name) {
			case 'niceFormat':
				$this->_engine->{$name} = $value;
				break;
			default:
				$this->{$name} = $value;
		}
	}

/**
 * Magic isset check for deprecated attributes.
 *
 * @param string $name Name of the attribute to check.
 * @return boolean
 */
	public function __isset($name) {
		if (isset($this->{$name})) {
			return true;
		}
		$magicGet = array('niceFormat');
		if (in_array($name, $magicGet)) {
			return $this->__get($name) !== null;
		}
		return null;
	}

/**
 * Magic accessor for attributes that were deprecated.
 *
 * @param string $name Name of the attribute to get.
 * @return mixed
 */
	public function __get($name) {
		if (isset($this->_engine->{$name})) {
			return $this->_engine->{$name};
		}
		$magicGet = array('niceFormat');
		if (in_array($name, $magicGet)) {
			return $this->_engine->{$name};
		}
		return null;
	}

/**
 * Call methods from CakeTime utility class
 * @return mixed Whatever is returned by called method, or false on failure
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->_engine, $method), $params);
	}

/**
 * Converts a string representing the format for the function strftime and returns a
 * windows safe and i18n aware format.
 *
 * @see CakeTime::convertSpecifiers()
 *
 * @param string $format Format with specifiers for strftime function.
 *    Accepts the special specifier %S which mimics the modifier S for date()
 * @param string $time UNIX timestamp
 * @return string windows safe and date() function compatible format for strftime
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function convertSpecifiers($format, $time = null) {
		return $this->_engine->convertSpecifiers($format, $time);
	}

/**
 * Converts given time (in server's time zone) to user's local time, given his/her timezone.
 *
 * @see CakeTime::convert()
 *
 * @param string $serverTime UNIX timestamp
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return integer UNIX timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function convert($serverTime, $timezone) {
		return $this->_engine->convert($serverTime, $timezone);
	}

/**
 * Returns server's offset
 *
 * @see CakeTime::serverOffset()
 *
 * @return integer Offset
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function serverOffset() {
		return $this->_engine->serverOffset();
	}

/**
 * Returns a UNIX timestamp, given either a UNIX timestamp or a valid strtotime() date string.
 *
 * @see CakeTime::fromString()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Parsed timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function fromString($dateString, $timezone = null) {
		return $this->_engine->fromString($dateString, $timezone);
	}

/**
 * Returns a nicely formatted date string for given Datetime string.
 *
 * @see CakeTime::nice()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @param string $format The format to use. If null, `TimeHelper::$niceFormat` is used
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function nice($dateString = null, $timezone = null, $format = null) {
		return $this->_engine->nice($dateString, $timezone, $format);
	}

/**
 * Returns a formatted descriptive date string for given datetime string.
 *
 * @see CakeTime::niceShort()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime objectp
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Described, relative date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function niceShort($dateString = null, $timezone = null) {
		return $this->_engine->niceShort($dateString, $timezone);
	}

/**
 * Returns a partial SQL string to search for all records between two dates.
 *
 * @see CakeTime::daysAsSql()
 *
 * @param integer|string|DateTime $begin UNIX timestamp, strtotime() valid string or DateTime object
 * @param integer|string|DateTime $end UNIX timestamp, strtotime() valid string or DateTime object
 * @param string $fieldName Name of database field to compare with
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Partial SQL string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function daysAsSql($begin, $end, $fieldName, $timezone = null) {
		return $this->_engine->daysAsSql($begin, $end, $fieldName, $timezone);
	}

/**
 * Returns a partial SQL string to search for all records between two times
 * occurring on the same day.
 *
 * @see CakeTime::dayAsSql()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string $fieldName Name of database field to compare with
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Partial SQL string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function dayAsSql($dateString, $fieldName, $timezone = null) {
		return $this->_engine->dayAsSql($dateString, $fieldName, $timezone);
	}

/**
 * Returns true if given datetime string is today.
 *
 * @see CakeTime::isToday()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return boolean True if datetime string is today
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isToday($dateString, $timezone = null) {
		return $this->_engine->isToday($dateString, $timezone);
	}

/**
 * Returns true if given datetime string is within this week.
 *
 * @see CakeTime::isThisWeek()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return boolean True if datetime string is within current week
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isThisWeek($dateString, $timezone = null) {
		return $this->_engine->isThisWeek($dateString, $timezone);
	}

/**
 * Returns true if given datetime string is within this month
 *
 * @see CakeTime::isThisMonth()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return boolean True if datetime string is within current month
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isThisMonth($dateString, $timezone = null) {
		return $this->_engine->isThisMonth($dateString, $timezone);
	}

/**
 * Returns true if given datetime string is within current year.
 *
 * @see CakeTime::isThisYear()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return boolean True if datetime string is within current year
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isThisYear($dateString, $timezone = null) {
		return $this->_engine->isThisYear($dateString, $timezone);
	}

/**
 * Returns true if given datetime string was yesterday.
 *
 * @see CakeTime::wasYesterday()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return boolean True if datetime string was yesterday
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 *
 */
	public function wasYesterday($dateString, $timezone = null) {
		return $this->_engine->wasYesterday($dateString, $timezone);
	}

/**
 * Returns true if given datetime string is tomorrow.
 *
 * @see CakeTime::isTomorrow()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return boolean True if datetime string was yesterday
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isTomorrow($dateString, $timezone = null) {
		return $this->_engine->isTomorrow($dateString, $timezone);
	}

/**
 * Returns the quarter
 *
 * @see CakeTime::toQuarter()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param boolean $range if true returns a range in Y-m-d format
 * @return mixed 1, 2, 3, or 4 quarter of year or array if $range true
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toQuarter($dateString, $range = false) {
		return $this->_engine->toQuarter($dateString, $range);
	}

/**
 * Returns a UNIX timestamp from a textual datetime description. Wrapper for PHP function strtotime().
 *
 * @see CakeTime::toUnix()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return integer Unix timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toUnix($dateString, $timezone = null) {
		return $this->_engine->toUnix($dateString, $timezone);
	}

/**
 * Returns a date formatted for Atom RSS feeds.
 *
 * @see CakeTime::toAtom()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toAtom($dateString, $timezone = null) {
		return $this->_engine->toAtom($dateString, $timezone);
	}

/**
 * Formats date for RSS feeds
 *
 * @see CakeTime::toRSS()
 *
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toRSS($dateString, $timezone = null) {
		return $this->_engine->toRSS($dateString, $timezone);
	}

/**
 * Formats date for RSS feeds
 *
 * @see CakeTime::timeAgoInWords()
 *
 * ## Addition options
 *
 * - `element` - The element to wrap the formatted time in.
 *   Has a few additional options:
 *   - `tag` - The tag to use, defaults to 'span'.
 *   - `class` - The class name to use, defaults to `time-ago-in-words`.
 *   - `title` - Defaults to the $dateTime input.
 *
 * @param integer|string|DateTime $dateTime UNIX timestamp, strtotime() valid string or DateTime object
 * @param array $options Default format if timestamp is used in $dateString
 * @return string Relative time string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function timeAgoInWords($dateTime, $options = array()) {
		$element = null;

		if (!empty($options['element'])) {
			$element = array(
				'tag' => 'span',
				'class' => 'time-ago-in-words',
				'title' => $dateTime
			);

			if (is_array($options['element'])) {
				$element = array_merge($element, $options['element']);
			} else {
				$element['tag'] = $options['element'];
			}
			unset($options['element']);
		}
		$relativeDate = $this->_engine->timeAgoInWords($dateTime, $options);

		if ($element) {
			$relativeDate = sprintf(
				'<%s%s>%s</%s>',
				$element['tag'],
				$this->_parseAttributes($element, array('tag')),
				$relativeDate,
				$element['tag']
			);
		}
		return $relativeDate;
	}

/**
 * Returns true if specified datetime was within the interval specified, else false.
 *
 * @see CakeTime::wasWithinLast()
 *
 * @param string|integer $timeInterval the numeric value with space then time type.
 *    Example of valid types: 6 hours, 2 days, 1 minute.
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return boolean
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function wasWithinLast($timeInterval, $dateString, $timezone = null) {
		return $this->_engine->wasWithinLast($timeInterval, $dateString, $timezone);
	}

/**
 * Returns true if specified datetime is within the interval specified, else false.
 *
 * @see CakeTime::isWithinLast()
 *
 * @param string|integer $timeInterval the numeric value with space then time type.
 *    Example of valid types: 6 hours, 2 days, 1 minute.
 * @param integer|string|DateTime $dateString UNIX timestamp, strtotime() valid string or DateTime object
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return boolean
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isWithinNext($timeInterval, $dateString, $timezone = null) {
		return $this->_engine->isWithinNext($timeInterval, $dateString, $timezone);
	}

/**
 * Returns gmt as a UNIX timestamp.
 *
 * @see CakeTime::gmt()
 *
 * @param integer|string|DateTime $string UNIX timestamp, strtotime() valid string or DateTime object
 * @return integer UNIX timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function gmt($string = null) {
		return $this->_engine->gmt($string);
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
 *   $this->Time->format('2012-02-15', '%m-%d-%Y'); // returns 02-15-2012
 *   $this->Time->format('2012-02-15 23:01:01', '%c'); // returns preferred date and time based on configured locale
 *   $this->Time->format('0000-00-00', '%d-%m-%Y', 'N/A'); // return N/A becuase an invalid date was passed
 *   $this->Time->format('2012-02-15 23:01:01', '%c', 'N/A', 'America/New_York'); // converts passed date to timezone
 * }}}
 *
 * @see CakeTime::format()
 *
 * @param integer|string|DateTime $format date format string (or a UNIX timestamp, strtotime() valid string or DateTime object)
 * @param integer|string|DateTime $date UNIX timestamp, strtotime() valid string or DateTime object (or a date format string)
 * @param boolean $invalid flag to ignore results of fromString == false
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function format($format, $date = null, $invalid = false, $timezone = null) {
		return $this->_engine->format($format, $date, $invalid, $timezone);
	}

/**
 * Returns a formatted date string, given either a UNIX timestamp or a valid strtotime() date string.
 * It takes into account the default date format for the current language if a LC_TIME file is used.
 *
 * @see CakeTime::i18nFormat()
 *
 * @param integer|string|DateTime $date UNIX timestamp, strtotime() valid string or DateTime object
 * @param string $format strftime format string.
 * @param boolean $invalid flag to ignore results of fromString == false
 * @param string|DateTimeZone $timezone User's timezone string or DateTimeZone object
 * @return string Formatted and translated date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function i18nFormat($date, $format = null, $invalid = false, $timezone = null) {
		return $this->_engine->i18nFormat($date, $format, $invalid, $timezone);
	}

}
