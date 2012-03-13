<?php
/**
 * PagesControllerTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!class_exists('AppController')) {
	require_once LIBS . 'controller' . DS . 'app_controller.php';
} elseif (!defined('APP_CONTROLLER_EXISTS')) {
	define('APP_CONTROLLER_EXISTS', true);
}
App::import('Controller', 'Pages');

/**
 * PagesControllerTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller
 */
class PagesControllerTest extends CakeTestCase {

/**
 * endTest method
 *
 * @access public
 * @return void
 */
	function endTest() {
		App::build();
	}

/**
 * testDisplay method
 *
 * @access public
 * @return void
 */
	function testDisplay() {
		if ($this->skipIf(defined('APP_CONTROLLER_EXISTS'), '%s Need a non-existent AppController')) {
			return;
		}

		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS, TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS)
		));
		$Pages =& new PagesController();

		$Pages->viewPath = 'posts';
		$Pages->display('index');
		$this->assertPattern('/posts index/', $Pages->output);
		$this->assertEqual($Pages->viewVars['page'], 'index');

		$Pages->viewPath = 'themed';
		$Pages->display('test_theme', 'posts', 'index');
		$this->assertPattern('/posts index themed view/', $Pages->output);
		$this->assertEqual($Pages->viewVars['page'], 'test_theme');
		$this->assertEqual($Pages->viewVars['subpage'], 'posts');
	}
}
