<?php
namespace TestApp\Controller;

use Cake\Controller\ErrorController;

class TestAppsErrorController extends ErrorController {

	public $helpers = array(
		'Html',
		'Session',
		'Form',
		'Banana',
	);

}
