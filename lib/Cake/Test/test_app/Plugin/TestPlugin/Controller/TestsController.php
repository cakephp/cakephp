<?php
/**
 * Short description for file.
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Controller
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestsController
 *
 * @package       Cake.Test.TestApp.Plugin.TestPlugin.Controller
 */
class TestsController extends TestPluginAppController {

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
