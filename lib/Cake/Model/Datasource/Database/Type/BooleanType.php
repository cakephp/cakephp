<?php

namespace Cake\Model\Datasource\Database\Type;

use Cake\Model\Datasource\Database\Driver;
use PDO;

class BooleanType extends \Cake\Model\Datasource\Database\Type {

/**
 * Casts given value to an acceptable boolean representation for the passed
 * driver
 *
 * @param mixed $value value to be converted to database boolean
 * @param Driver $driver Driver to be used for getting boolean representation
 * @todo Needs to actually ask the driver for conversion
 * @return mixed
 **/
	public function toDatabase($value, Driver $driver) {
		if ($value === null) {
			return null;
		}
		return (bool)$value;
	}

/**
 * Casts given value to boolean
 *
 * @param mixed $value value to be converted to PHP boolean
 * @param Driver $driver object from which database preferences and configuration will be extracted
 * @todo Needs to actually ask the driver for conversion
 * @return boolean
 **/
	public function toPHP($value, Driver $driver) {
		if ($value === null) {
			return null;
		}
		return (bool)$value;
	}

/**
 * Casts give value to Statement equivalent
 *
 * @param mixed $value value to be bound in a prepared statement as boolean
 * @param Driver $driver object from which database preferences and configuration will be extracted
 * @return mixed
 **/
	public function toStatement($value, Driver $driver) {
		return PDO::PARAM_BOOL;
	}

}
