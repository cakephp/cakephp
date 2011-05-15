<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.test_app.plugins.test_plugin.views.helpers
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TestsAppsController extends AppController {
	public $name = 'TestsApps';
	public $uses = array();

	function index() {
	}

	function some_method() {
		return 5;
	}

	function set_action() {
		$this->set('var', 'string');
		$this->render('index');
	}

	function redirect_to() {
		$this->redirect('http://cakephp.org');
	}
}
