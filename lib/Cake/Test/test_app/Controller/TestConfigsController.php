<?php

App::uses('CakeErrorController', 'Controller');

class TestConfigsController extends CakeErrorController {

	public $components = array(
		'RequestHandler' => array(
			'some' => 'config'
		)
	);

}
