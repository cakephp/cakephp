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
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View\Helper;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Error;
use Cake\Utility\Hash;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Time Helper class for easy use of time data.
 *
 * Manipulation of time data.
 *
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html
 * @see Cake\Utility\Time
 */
class TimeHelper extends Helper {

/**
 * Cake\Utility\Time instance
 *
 * @var Cake\Utility\Time
 */
	protected $_engine = null;

/**
 * Constructor
 *
 * ### Settings:
 *
 * - `engine` Class name to use to replace Cake\Utility\Time functionality
 *            The class needs to be placed in the `Utility` directory.
 *
 * @param View $View the view object the helper is attached to.
 * @param array $settings Settings array
 * @throws Cake\Error\Exception When the engine class could not be found.
 */
	public function __construct(View $View, $settings = array()) {
		$settings = Hash::merge(array('engine' => 'Cake\Utility\Time'), $settings);
		parent::__construct($View, $settings);
		$engineClass = App::classname($settings['engine'], 'Utility');
		if ($engineClass) {
			$this->_engine = new $engineClass($settings);
		} else {
			throw new Error\Exception(sprintf('Class for %s could not be found', $settings['engine']));
		}
	}

/**
 * Call methods from Cake\Utility\Time utility class
 * @return mixed Whatever is returned by called method, or false on failure
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->_engine, $method), $params);
	}

/**
 * @see Cake\Utility\Time::convertSpecifiers()
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
 * @see Cake\Utility\Time::convert()
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
 * @see Cake\Utility\Time::serverOffset()
 *
 * @return integer Offset
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function serverOffset() {
		return $this->_engine->serverOffset();
	}

/**
 * @see Cake\Utility\Time::fromString()
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
 * @see Cake\Utility\Time::nice()
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
 * @see Cake\Utility\Time::niceShort()
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
 * @see Cake\Utility\Time::daysAsSql()
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
 * @see Cake\Utility\Time::dayAsSql()
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
 * @see Cake\Utility\Time::isToday()
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
 * @see Cake\Utility\Time::isThisWeek()
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
 * @see Cake\Utility\Time::isThisMonth()
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
 * @see Cake\Utility\Time::isThisYear()
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
 * @see Cake\Utility\Time::wasYesterday()
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
 * @see Cake\Utility\Time::isTomorrow()
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
 * @see Cake\Utility\Time::toQuarter()
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
 * @see Cake\Utility\Time::toUnix()
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
 * @see Cake\Utility\Time::toAtom()
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
 * @see Cake\Utility\Time::toRSS()
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
 * @see Cake\Utility\Time::timeAgoInWords()
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
 * @see Cake\Utility\Time::wasWithinLast()
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
 * @see Cake\Utility\Time::isWithinLast()
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
 * @see Cake\Utility\Time::gmt()
 *
 * @param integer|string|DateTime $string UNIX timestamp, strtotime() valid string or DateTime object
 * @return integer UNIX timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function gmt($string = null) {
		return $this->_engine->gmt($string);
	}

/**
 * @see Cake\Utility\Time::format()
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
 * @see Cake\Utility\Time::i18nFormat()
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
