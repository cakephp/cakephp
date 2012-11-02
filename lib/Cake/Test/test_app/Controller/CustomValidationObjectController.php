<?php
/**
 * CustomValidationObjectController
 *
 * This controller tryies to validate a model dataset with
 * standard CakePHP model's validation rules.
 *
 * Output is created throught debug() method
 */
class CustomValidationObjectController extends AppController {

	public $autoRender = false;

	public $uses = array('CustomValidationModel');

	public function index() {
		$data = array('CustomValidationModel' => array(
			'name' => '',
			'surname' => '',
			'age' => '',
			'odd_number' => 23
		));
		$this->CustomValidationModel->set($data);
		$this->CustomValidationModel->validates();
		debug($this->CustomValidationModel->validationErrors);
	}

}
