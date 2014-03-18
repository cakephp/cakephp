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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Database\Type;

use Cake\Database\Driver;
use \DateTime;

class DateType extends \Cake\Database\Type {

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
		return $value->format('Y-m-d');
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
		return DateTime::createFromFormat('Y-m-d', $value);
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
				$date->setTime(0, 0, 0);
				return $date;
			}
		} catch (\Exception $e) {
			return $value;
		}

		$date = new DateTime();
		if (
			isset($value['year'], $value['month'], $value['day']) &&
			(is_numeric($value['year']) & is_numeric($value['month']) && is_numeric($value['day']))
		) {
			$date->setDate($value['year'], $value['month'], $value['day']);
		}
		$date->setTime(0, 0, 0);
		return $date;
	}

}
