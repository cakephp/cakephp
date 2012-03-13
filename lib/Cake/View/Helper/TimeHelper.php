<?php
/**
 * Time Helper class file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Helper
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
 * @param array $settings Settings array Settings array
 * @throws CakeException When the engine class could not be found.
 */
	public function __construct(View $View, $settings = array()) {
		$settings = Set::merge(array('engine' => 'CakeTime'), $settings);
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
 * @return mixed
 */
	public function __set($name, $value) {
		switch ($name) {
			case 'niceFormat':
				$this->_engine->{$name} = $value;
			break;
			default:
				$this->{$name} = $value;
			break;
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
 */
	public function __call($method, $params) {
		return call_user_func_array(array($this->_engine, $method), $params);
	}

/**
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
 * @see CakeTime::convert()
 *
 * @param string $serverTime UNIX timestamp
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return integer UNIX timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function convert($serverTime, $userOffset) {
		return $this->_engine->convert($serverTime, $userOffset);
	}

/**
 * @see CakeTime::serverOffset()
 *
 * @return integer Offset
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function serverOffset() {
		return $this->_engine->serverOffset();
	}

/**
 * @see CakeTime::fromString()
 *
 * @param string $dateString Datetime string
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return string Parsed timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function fromString($dateString, $userOffset = null) {
		return $this->_engine->fromString($dateString, $userOffset);
	}

/**
 * @see CakeTime::nice()
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param integer $userOffset User's offset from GMT (in hours)
 * @param string $format The format to use. If null, `TimeHelper::$niceFormat` is used
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function nice($dateString = null, $userOffset = null, $format = null) {
		return $this->_engine->nice($dateString, $userOffset, $format);
	}

/**
 * @see CakeTime::niceShort()
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return string Described, relative date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function niceShort($dateString = null, $userOffset = null) {
		return $this->_engine->niceShort($dateString, $userOffset);
	}

/**
 * @see CakeTime::daysAsSql()
 *
 * @param string $begin Datetime string or Unix timestamp
 * @param string $end Datetime string or Unix timestamp
 * @param string $fieldName Name of database field to compare with
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return string Partial SQL string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function daysAsSql($begin, $end, $fieldName, $userOffset = null) {
		return $this->_engine->daysAsSql($begin, $end, $fieldName, $userOffset);
	}

/**
 * @see CakeTime::dayAsSql()
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param string $fieldName Name of database field to compare with
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return string Partial SQL string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function dayAsSql($dateString, $fieldName, $userOffset = null) {
		return $this->_engine->dayAsSql($dateString, $fieldName, $userOffset);
	}

/**
 * @see CakeTime::isToday()
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string is today
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isToday($dateString, $userOffset = null) {
		return $this->_engine->isToday($dateString, $userOffset);
	}

/**
 * @see CakeTime::isThisWeek()
 *
 * @param string $dateString
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string is within current week
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isThisWeek($dateString, $userOffset = null) {
		return $this->_engine->isThisWeek($dateString, $userOffset);
	}

/**
 * @see CakeTime::isThisMonth()
 *
 * @param string $dateString
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string is within current month
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isThisMonth($dateString, $userOffset = null) {
		return $this->_engine->isThisMonth($dateString, $userOffset);
	}

/**
 * @see CakeTime::isThisYear()
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string is within current year
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isThisYear($dateString, $userOffset = null) {
		return $this->_engine->isThisYear($dateString, $userOffset);
	}

/**
 * @see CakeTime::wasYesterday()
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string was yesterday
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 *
 */
	public function wasYesterday($dateString, $userOffset = null) {
		return $this->_engine->wasYesterday($dateString, $userOffset);
	}

/**
 * @see CakeTime::isTomorrow()
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return boolean True if datetime string was yesterday
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function isTomorrow($dateString, $userOffset = null) {
		return $this->_engine->isTomorrow($dateString, $userOffset);
	}

/**
 * @see CakeTime::toQuarter()
 *
 * @param string $dateString
 * @param boolean $range if true returns a range in Y-m-d format
 * @return mixed 1, 2, 3, or 4 quarter of year or array if $range true
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toQuarter($dateString, $range = false) {
		return $this->_engine->toQuarter($dateString, $range);
	}

/**
 * @see CakeTime::toUnix()
 *
 * @param string $dateString Datetime string to be represented as a Unix timestamp
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return integer Unix timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toUnix($dateString, $userOffset = null) {
		return $this->_engine->toUnix($dateString, $userOffset);
	}

/**
 * @see CakeTime::toAtom()
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toAtom($dateString, $userOffset = null) {
		return $this->_engine->toAtom($dateString, $userOffset);
	}

/**
 * @see CakeTime::toRSS()
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function toRSS($dateString, $userOffset = null) {
		return $this->_engine->toRSS($dateString, $userOffset);
	}

/**
 * @see CakeTime::timeAgoInWords()
 *
 * @param string $dateTime Datetime string or Unix timestamp
 * @param array $options Default format if timestamp is used in $dateString
 * @return string Relative time string.
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function timeAgoInWords($dateTime, $options = array()) {
		return $this->_engine->timeAgoInWords($dateTime, $options);
	}

/**
 * @see CakeTime::wasWithinLast()
 *
 * @param mixed $timeInterval the numeric value with space then time type.
 *    Example of valid types: 6 hours, 2 days, 1 minute.
 * @param mixed $dateString the datestring or unix timestamp to compare
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return boolean
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#testing-time
 */
	public function wasWithinLast($timeInterval, $dateString, $userOffset = null) {
		return $this->_engine->wasWithinLast($timeInterval, $dateString, $userOffset);
	}

/**
 * @see CakeTime::gmt()
 *
 * @param string $string UNIX timestamp or a valid strtotime() date string
 * @return integer UNIX timestamp
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function gmt($string = null) {
		return $this->_engine->gmt($string);
	}

/**
 * @see CakeTime::format()
 *
 * @param string $format date format string (or a DateTime string)
 * @param string $date Datetime string (or a date format string)
 * @param boolean $invalid flag to ignore results of fromString == false
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return string Formatted date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function format($format, $date = null, $invalid = false, $userOffset = null) {
		return $this->_engine->format($format, $date, $invalid, $userOffset);
	}

/**
 * @see CakeTime::i18nFormat()
 *
 * @param string $date Datetime string
 * @param string $format strftime format string.
 * @param boolean $invalid flag to ignore results of fromString == false
 * @param integer $userOffset User's offset from GMT (in hours)
 * @return string Formatted and translated date string
 * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#formatting
 */
	public function i18nFormat($date, $format = null, $invalid = false, $userOffset = null) {
		return $this->_engine->i18nFormat($date, $format, $invalid, $userOffset);
	}

}
