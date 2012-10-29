<?php
class CustomValidationModel extends AppModel {

	public $useTable = false;

	public $validate = array(
		'name' 		=> 'notEmpty',
		'surname' 	=> array(
			'rule' => 'CustomValidationObject::myCustomRule',
			'message' => 'value not accepted'
		),
		'age' => 'NonExistingObject::nonExistingMethod',
		'odd_number' => array(
			'rule' => 'CustomValidationObject::odd',
			'message' => 'Please insert odd number'
		)
	);

}