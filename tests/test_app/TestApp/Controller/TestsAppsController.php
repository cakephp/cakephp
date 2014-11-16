<?php
/**
 * TestsAppsController file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class TestsAppsController
 *
 */
namespace TestApp\Controller;

class TestsAppsController extends AppController {

	public $uses = array();

	public $components = array('RequestHandler');

	public function index() {
		$var = '';
		if (isset($this->request->query['var'])) {
			$var = $this->request->query['var'];
		}
		$this->set('var', $var);
	}

	public function some_method() {
		$this->response->body(5);
		return $this->response;
	}

	public function set_action() {
		$this->set('var', 'string');
		$this->render('index');
	}

	public function redirect_to() {
		return $this->redirect('http://cakephp.org');
	}

}
