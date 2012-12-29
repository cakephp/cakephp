<?php

namespace Cake\Model\Datasource\Database\Type;

use Cake\Model\Datasource\Database\Driver;
use \DateTime;

class DateTimeType extends \Cake\Model\Datasource\Database\Type {

	public function toDatabase($value, Driver $driver) {
		if (is_string($value)) {
			return $value;
		}
		return $value->format('Y-m-d H:i:s');
	}

	public function toPHP($value, Driver $driver) {
		if ($value === null) {
			return null;
		}
		$value = DateTime::createFromFormat('Y-m-d H:i:s', $value);
		return $value;
	}

}
