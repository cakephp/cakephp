<?php
/**
 * PagesControllerTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
				CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS
			)
		));
		$Pages = new PagesController(new CakeRequest(null, false), new CakeResponse());

		$Pages->viewPath = 'Posts';
		$Pages->display('index');
		$this->assertPattern('/posts index/', $Pages->response->body());
		$this->assertEqual($Pages->viewVars['page'], 'index');

		$Pages->viewPath = 'Themed';
		$Pages->display('TestTheme', 'Posts', 'index');
		$this->assertPattern('/posts index themed view/', $Pages->response->body());
		$this->assertEqual($Pages->viewVars['page'], 'TestTheme');
		$this->assertEqual($Pages->viewVars['subpage'], 'Posts');
	}
}
