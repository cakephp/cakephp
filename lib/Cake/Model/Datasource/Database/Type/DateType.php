<?php

namespace Cake\Model\Datasource\Database\Type;

use \DateTime;

class DateType extends \Cake\Model\Datasource\Database\Type {

	public function toDatabase($value, $driver) {
		if (is_string($value)) {
			return $value;
		}
		return $value->format('Y-m-d');
	}

	public function toPHP($value, $driver) {
		if ($value === null) {
			return null;
		}
		$value = DateTime::createFromFormat('Y-m-d', $value);
		return $value;
	}

}

