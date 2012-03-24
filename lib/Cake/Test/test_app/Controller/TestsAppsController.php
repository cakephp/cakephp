<?php
/**
 * TestsAppsController file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.test_app.Controller
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TestsAppsController extends AppController {

	public $name = 'TestsApps';

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
		return 5;
	}

	public function set_action() {
		$this->set('var', 'string');
		$this->render('index');
	}

	public function redirect_to() {
		$this->redirect('http://cakephp.org');
	}

}
