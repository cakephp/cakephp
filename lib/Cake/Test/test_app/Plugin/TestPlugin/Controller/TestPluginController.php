<?php
/**
 * TestPluginController used by Dispatcher test to test plugin shortcut urls.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.test_app.Plugin.TestPlugin.Controller
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TestPluginController extends TestPluginAppController {

	public $uses = array();

	public function index() {
		$this->autoRender = false;
	}

	public function add() {
		$this->autoRender = false;
	}

}
