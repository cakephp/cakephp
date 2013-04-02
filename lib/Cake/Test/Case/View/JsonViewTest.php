<?php
/**
 * JsonViewTest file
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
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array('user' => 'fake', 'list' => array('item1', 'item2'));
		$Controller->set(array('data' => $data, '_serialize' => 'data'));
		$View = new JsonView($Controller);
		$output = $View->render(false);

		$this->assertSame(json_encode($data), $output);
		$this->assertSame('application/json', $Response->type());
	}

/**
 * Test render with an array in _serialize
 *
 * @return void
 */
	public function testRenderWithoutViewMultiple() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2'));
		$Controller->set($data);
		$Controller->set('_serialize', array('no', 'user'));
		$View = new JsonView($Controller);
		$output = $View->render(false);

		$this->assertSame(json_encode(array('no' => $data['no'], 'user' => $data['user'])), $output);
		$this->assertSame('application/json', $Response->type());
	}

/**
 * testRenderWithView method
 *
 * @return void
 */
	public function testRenderWithView() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		$Request = new CakeRequest(null, false);
		$Request->params['named'] = array('page' => 2);
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$Controller->name = $Controller->viewPath = 'Posts';

		$data = array(
			'User' => array(
				'username' => 'fake'
			),
			'Item' => array(
				array('name' => 'item1'),
				array('name' => 'item2')
			)
		);
		$Controller->set('user', $data);
		$View = new JsonView($Controller);
		$View->helpers = array('Paginator');
		$output = $View->render('index');

		$expected = json_encode(array('user' => 'fake', 'list' => array('item1', 'item2'), 'paging' => array('page' => 2)));
		$this->assertSame($expected, $output);
		$this->assertSame('application/json', $Response->type());
	}

}
