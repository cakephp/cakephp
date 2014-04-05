<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('DebugPanel', 'DebugKit.Lib');

/**
 * Provides debug information on the Current request params.
 *
 */
class RequestPanel extends DebugPanel {

/**
 * beforeRender callback - grabs request params
 *
 * @param Controller $controller
 * @return array
 */
	public function beforeRender(Controller $controller) {
		$out = array();
		$out['params'] = $controller->request->params;
		$out['url'] = $controller->request->url;
		$out['query'] = $controller->request->query;
		$out['data'] = $controller->request->data;
		if (isset($controller->Cookie)) {
			$out['cookie'] = $controller->Cookie->read();
		}
		$out['get'] = $_GET;
		$out['currentRoute'] = Router::currentRoute();
		return $out;
	}
}
