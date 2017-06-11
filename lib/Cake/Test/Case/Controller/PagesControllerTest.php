<?php
/**
 * PagesControllerTest file
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
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('PagesController', 'Controller');

/**
 * PagesControllerTest class
 *
 * @package       Cake.Test.Case.Controller
 */
class PagesControllerTest extends CakeTestCase {

/**
 * testDisplay method
 *
 * @return void
 */
	public function testDisplay() {
		App::build(array(
			'View' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS
			)
		));
		$Pages = new PagesController(new CakeRequest(null, false), new CakeResponse());

		$Pages->viewPath = 'Posts';
		$Pages->display('index');
		$this->assertRegExp('/posts index/', $Pages->response->body());
		$this->assertEquals('index', $Pages->viewVars['page']);

		$Pages->viewPath = 'Themed';
		$Pages->display('TestTheme', 'Posts', 'index');
		$this->assertRegExp('/posts index themed view/', $Pages->response->body());
		$this->assertEquals('TestTheme', $Pages->viewVars['page']);
		$this->assertEquals('Posts', $Pages->viewVars['subpage']);
	}

/**
 * Test that missing view renders 404 page in production
 *
 * @expectedException NotFoundException
 * @expectedExceptionCode 404
 * @return void
 */
	public function testMissingView() {
		Configure::write('debug', 0);
		$Pages = new PagesController(new CakeRequest(null, false), new CakeResponse());
		$Pages->display('non_existing_page');
	}

/**
 * Test that missing view in debug mode renders missing_view error page
 *
 * @expectedException MissingViewException
 * @expectedExceptionCode 500
 * @return void
 */
	public function testMissingViewInDebug() {
		Configure::write('debug', 1);
		$Pages = new PagesController(new CakeRequest(null, false), new CakeResponse());
		$Pages->display('non_existing_page');
	}

/**
 * Test directory traversal protection
 *
 * @expectedException ForbiddenException
 * @expectedExceptionCode 403
 * @return void
 */
	public function testDirectoryTraversalProtection() {
		App::build(array(
			'View' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS
			)
		));
		$Pages = new PagesController(new CakeRequest(null, false), new CakeResponse());
		$Pages->display('..', 'Posts', 'index');
	}
}
