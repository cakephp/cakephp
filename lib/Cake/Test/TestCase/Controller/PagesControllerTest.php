<?php
/**
 * PagesControllerTest file
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
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller;

use App\Controller\PagesController;
use Cake\Core\App;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;

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
				CAKE . 'Test/TestApp/View/'
			)
		));
		$Pages = new PagesController(new Request(), new Response());

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
