<?php
namespace TestApp\Validation;

class CustomProvider {

	public static function is42($check) {
		return $check == 42;
	}

}