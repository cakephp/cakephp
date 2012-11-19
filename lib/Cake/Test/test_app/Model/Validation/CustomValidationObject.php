<?php
class CustomValidationObject {

	public static function odd($value) {
		return $value & 1;
	}

	public static function myCustomRule($value) {
		return self::odd(rand(2,10));
	}

}
