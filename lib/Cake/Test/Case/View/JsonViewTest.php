<?php
/**
 * JsonViewTest file
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
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 2.1.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('JsonView', 'View');

/**
 * JsonViewTest
 *
 * @package       Cake.Test.Case.View
 */
class JsonViewTest extends CakeTestCase {

/**
 * testRenderWithoutView method
 *
 * @return void
 */
	public function testRenderWithoutView() {
		$request = new CakeRequest();
		$response = new CakeResponse();
		$controller = new Controller($request, $response);
		$data = array('user' => 'fake', 'list' => array('item1', 'item2'));
		$controller->set('serialize', $data);
		$view = new JsonView($controller);
		$output = $view->render(false);

		$this->assertIdentical(json_encode($data), $output);
		$this->assertIdentical('application/json', $response->type());
	}

/**
 * testRenderWithView method
 *
 * @return void
 */
	public function testRenderWithView() {
		App::build(array('View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Json')));
		$request = new CakeRequest();
		$response = new CakeResponse();
		$controller = new Controller($request, $response);
		$data = array(
			'User' => array(
				'username' => 'fake'
			),
			'Item' => array(
				array('name' => 'item1'),
				array('name' => 'item2')
			)
		);
		$controller->set('user', $data);
		$view = new JsonView($controller);
		$output = $view->render('index');

		$expected = json_encode(array('user' => 'fake', 'list' => array('item1', 'item2')));
		$this->assertIdentical($expected, $output);
		$this->assertIdentical('application/json', $response->type());
	}

}
