<?php
/**
 * PagesControllerTest file
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
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Controller;
use Cake\TestSuite\TestCase,
	Cake\Network\Request,
	Cake\Network\Response,
	Cake\Core\App,
	\App\Controller\PagesController;

/**
 * PagesControllerTest class
 *
 * @package       Cake.Test.Case.Controller
 */
class PagesControllerTest extends TestCase {

/**
 * testDisplay method
 *
 * @return void
 */
	public function testDisplay() {
		App::build(array(
			'View' => array(
				CAKE . 'Test' . DS . 'TestApp' . DS . 'View' . DS
			)
		));
		$Pages = new PagesController(new Request(null, false), new Response());

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
}
