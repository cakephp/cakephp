<?php
/**
 * XmlViewTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
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
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$data = array('users' => array('user' => array('user1', 'user2')));
		$Controller->set(array('users' => $data, '_serialize' => 'users'));
		$View = new XmlView($Controller);
		$output = $View->render(false);

		$expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<users><user>user1</user><user>user2</user></users>';
		$this->assertTextEquals($expected, trim($output));
		$this->assertIdentical('application/xml', $Response->type());
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
		$View = new XmlView($Controller);
		$output = $View->render(false);

		$expected = array(
			'response' => array('no' => $data['no'], 'user' => $data['user'])
		);
		$this->assertIdentical(Xml::build($expected)->asXML(), $output);
		$this->assertIdentical('application/xml', $Response->type());
	}

/**
 * testRenderWithView method
 *
 * @return void
 */
	public function testRenderWithView() {
		App::build(array('View' => array(
			CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS
		)));
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);
		$Controller->name = $Controller->viewPath = 'Posts';

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
		$Controller->set('users', $data);
		$View = new XmlView($Controller);
		$output = $View->render('index');

		$expected = '<?xml version="1.0" encoding="UTF-8"?><users><user>user1</user><user>user2</user></users>';
		$this->assertIdentical($expected, str_replace(array("\r", "\n"), '', $output));
		$this->assertIdentical('application/xml', $Response->type());
		$this->assertInstanceOf('HelperCollection', $View->Helpers);
	}

}
