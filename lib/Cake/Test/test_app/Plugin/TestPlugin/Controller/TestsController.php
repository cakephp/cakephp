<?php
/**
 * Short description for file.
 *
 * PHP 5
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
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Controller
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Class TestsController
 *
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Controller
 */
class TestsController extends TestPluginAppController {

	public $name = 'Tests';

	public $uses = array();

	public $helpers = array('TestPlugin.OtherHelper', 'Html');

	public $components = array('TestPlugin.Plugins');

	public function index() {
		$this->set('test_value', 'It is a variable');
	}

	public function some_method() {
		return 25;
	}

}
