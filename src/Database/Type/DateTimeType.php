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
namespace Cake\Database\Type;

use Cake\Database\Driver;

/**
 * Datetime type converter.
 *
 * Use to convert datetime instances to strings & back.
 */
class DateTimeType extends \Cake\Database\Type {

/**
 * The class to use for representing date objects
 *
 * @var string
 */
	public static $dateTimeClass = 'Cake\Utility\Time';

/**
 * String format to use for DateTime parsing
 *
 * @var string
 */
	protected $_format = 'Y-m-d H:i:s';

/**
 * {@inheritDoc}
 */
	public function __construct($name = null) {
		parent::__construct($name);

		if (!class_exists(static::$dateTimeClass)) {
			static::$dateTimeClass = 'DateTime';
		}
	}

/**
 * Convert DateTime instance into strings.
 *
 * @param string|DateTime $value The value to convert.
 * @param Driver $driver The driver instance to convert with.
 * @return string
 */
	public function toDatabase($value, Driver $driver) {
		if ($value === null || is_string($value)) {
			return $value;
		}
		return $value->format($this->_format);
	}

/**
 * Convert strings into DateTime instances.
 *
 * @param string $value The value to convert.
 * @param Driver $driver The driver instance to convert with.
 * @return \Carbon\Carbon
 */
	public function toPHP($value, Driver $driver) {
		if ($value === null) {
			return null;
		}
		list($value) = explode('.', $value);
		$class = static::$dateTimeClass;
		return $class::createFromFormat($this->_format, $value);
	}

/**
 * Convert request data into a datetime object.
 *
 * @param mixed $value Request data
 * @return \Carbon\Carbon
 */
	public function marshal($value) {
		$class = static::$dateTimeClass;
		try {
			if ($value === '' || $value === null || $value === false || $value === true) {
				return $value;
			} elseif (is_numeric($value)) {
				$date = new $class('@' . $value);
			} elseif (is_string($value)) {
				$date = new $class($value);
			}
			if (isset($date)) {
				return $date;
			}
		} catch (\Exception $e) {
			return $value;
		}

		$value += ['hour' => 0, 'minute' => 0, 'second' => 0];

		$format = '';
		if (
			isset($value['year'], $value['month'], $value['day']) &&
			(is_numeric($value['year']) & is_numeric($value['month']) && is_numeric($value['day']))
		) {
			$format .= sprintf('%d-%02d-%02d', $value['year'], $value['month'], $value['day']);
		}

		if (isset($value['meridian'])) {
			$value['hour'] = strtolower($value['meridian']) === 'am' ? $value['hour'] : $value['hour'] + 12;
		}
		$format .= sprintf('%02d:%02d:%02d', $value['hour'], $value['minute'], $value['second']);

		return new $class($format);
	}

}
