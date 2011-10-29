<?php
/**
 * XmlViewTest file
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
App::uses('XmlView', 'View');

/**
 * XmlViewTest
 *
 * @package       Cake.Test.Case.View
 */
class XmlViewTest extends CakeTestCase {

/**
 * testRenderWithoutView method
 *
 * @return void
 */
	public function testRenderWithoutView() {
		$request = new CakeRequest();
		$response = new CakeResponse();
		$controller = new Controller($request, $response);
		$data = array('users' => array('user' => array('user1', 'user2')));
		$controller->set('serialize', $data);
		$view = new XmlView($controller);
		$output = $view->render(false);

		$expected = '<?xml version="1.0" encoding="UTF-8"?><users><user>user1</user><user>user2</user></users>';
		$this->assertIdentical($expected, str_replace(array("\r", "\n"), '', $output));
		$this->assertIdentical('application/xml', $response->type());
	}

/**
 * testRenderWithView method
 *
 * @return void
 */
	public function testRenderWithView() {
		App::build(array('View' => array(
			CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Xml' . DS,
			CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS
		)));
		$request = new CakeRequest();
		$response = new CakeResponse();
		$controller = new Controller($request, $response);
		$data = array(
			array(
				'User' => array(
					'username' => 'user1'
				)
			),
			array(
				'User' => array(
					'username' => 'user2'
				)
			)
		);
		$controller->set('users', $data);
		$view = new XmlView($controller);
		$output = $view->render('index', 'xml/xml_view');

		$expected = '<?xml version="1.0" encoding="UTF-8"?><users><user>user1</user><user>user2</user></users>';
		$this->assertIdentical($expected, str_replace(array("\r", "\n"), '', $output));
		$this->assertIdentical('application/xml', $response->type());
		$this->assertInstanceOf('HelperCollection', $view->Helpers);
	}

}
