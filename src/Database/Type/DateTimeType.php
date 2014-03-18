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
 * @since         3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Type;

use Cake\Database\Driver;
use \DateTime;

/**
 * Datetime type converter.
 *
 * Use to convert datetime instances to strings & back.
 */
class DateTimeType extends \Cake\Database\Type {

/**
 * Convert DateTime instance into strings.
 *
 * @param string|Datetime $value The value to convert.
 * @param Driver $driver The driver instance to convert with.
 * @return string
 */
	public function toDatabase($value, Driver $driver) {
		if (is_string($value)) {
			return $value;
		}
		return $value->format('Y-m-d H:i:s');
	}

/**
 * Convert strings into DateTime instances.
 *
 * @param string $value The value to convert.
 * @param Driver $driver The driver instance to convert with.
 * @return Datetime
 */
	public function toPHP($value, Driver $driver) {
		if ($value === null) {
			return null;
		}
		$value = DateTime::createFromFormat('Y-m-d H:i:s', $value);
		return $value;
	}

/**
 * Convert request data into a datetime object.
 *
 * @param mixed $value Request data
 * @return \DateTime
 */
	public function marshal($value) {
		try {
			if ($value === '' || $value === null || $value === false || $value === true) {
				return $value;
			} elseif (is_numeric($value)) {
				$date = new DateTime('@' . $value);
			} elseif (is_string($value)) {
				$date = new DateTime($value);
			}
			if (isset($date)) {
				return $date;
			}
		} catch (\Exception $e) {
			return $value;
		}

		$value += ['second' => 0];

		$date = new DateTime();
		$date->setTime(0, 0, 0);
		if (
			isset($value['year'], $value['month'], $value['day']) &&
			(is_numeric($value['year']) & is_numeric($value['month']) && is_numeric($value['day']))
		) {
			$date->setDate($value['year'], $value['month'], $value['day']);
		}
		if (isset($value['hour'], $value['minute'])) {
			if (isset($value['meridian'])) {
				$value['hour'] = strtolower($value['meridian']) === 'am' ? $value['hour'] : $value['hour'] + 12;
			}
			$date->setTime((int)$value['hour'], (int)$value['minute'], (int)$value['second']);
		}
		return $date;
	}

}
