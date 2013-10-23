<?php

class TestAppsExceptionRenderer extends ExceptionRenderer {

	protected function _getController($exception) {
		App::uses('TestAppsErrorController', 'Controller');
		if (!$request = Router::getRequest(true)) {
			$request = new CakeRequest();
		}
		$response = new CakeResponse();
		try {
			$controller = new TestAppsErrorController($request, $response);
			$controller->layout = 'banana';
		} catch (Exception $e) {
			$controller = new Controller($request, $response);
			$controller->viewPath = 'Errors';
		}
		return $controller;
	}

}
