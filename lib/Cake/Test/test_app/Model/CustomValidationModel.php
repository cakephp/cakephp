<?php
/**
 * CustomValidationModel
 *
 * It uses core "notEmpty" validation.
 * 
 * a custom validation "CustomValidationObject::myCustomRule" loaded
 * from /Model/Validation/CustomValidationObject
 *
 * and a non-existing validation from "NonExistingObject::nonExistingMethod".
 * this rule will trigger a notice
 */
class CustomValidationModel extends AppModel {

	public $useTable = false;

	public $validate = array(
		'name' => 'notEmpty',
		'surname' => array(
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
