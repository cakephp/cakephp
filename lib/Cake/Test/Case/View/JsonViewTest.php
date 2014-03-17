<?php
/**
 * JsonViewTest file
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
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
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

	public function setUp() {
		parent::setUp();
		Configure::write('debug', 0);
	}

/**
 * Generates testRenderWithoutView data.
 *
 * Note: array($data, $serialize, expected)
 *
 * @return void
 */
	public static function renderWithoutViewProvider() {
		return array(
			// Test render with a valid string in _serialize.
			array(
				array('data' => array('user' => 'fake', 'list' => array('item1', 'item2'))),
				'data',
				json_encode(array('user' => 'fake', 'list' => array('item1', 'item2')))
			),

			// Test render with a string with an invalid key in _serialize.
			array(
				array('data' => array('user' => 'fake', 'list' => array('item1', 'item2'))),
				'no_key',
				json_encode(null)
			),

			// Test render with a valid array in _serialize.
			array(
				array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2')),
				array('no', 'user'),
				json_encode(array('no' => 'nope', 'user' => 'fake'))
			),

			// Test render with an empty array in _serialize.
			array(
				array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2')),
				array(),
				json_encode(null)
			),

			// Test render with a valid array with an invalid key in _serialize.
			array(
				array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2')),
				array('no', 'user', 'no_key'),
				json_encode(array('no' => 'nope', 'user' => 'fake'))
			),

			// Test render with a valid array with only an invalid key in _serialize.
			array(
				array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2')),
				array('no_key'),
				json_encode(null)
			),

			// Test render with Null in _serialize (unset).
			array(
				array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2')),
				null,
				null
			),

			// Test render with False in _serialize.
			array(
				array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2')),
				false,
				json_encode(null)
			),

			// Test render with True in _serialize.
			array(
				array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2')),
				true,
				json_encode(null)
			),

			// Test render with empty string in _serialize.
			array(
				array('no' => 'nope', 'user' => 'fake', 'list' => array('item1', 'item2')),
				'',
				json_encode(null)
			),

			// Test render with a valid array in _serialize and alias.
			array(
				array('original_name' => 'my epic name', 'user' => 'fake', 'list' => array('item1', 'item2')),
				array('new_name' => 'original_name', 'user'),
				json_encode(array('new_name' => 'my epic name', 'user' => 'fake'))
			),

			// Test render with an a valid array in _serialize and alias of a null value.
			array(
				array('null' => null),
				array('null'),
				json_encode(array('null' => null))
			),

			// Test render with a False value to be serialized.
			array(
				array('false' => false),
				'false',
				json_encode(false)
			),

			// Test render with a True value to be serialized.
			array(
				array('true' => true),
				'true',
				json_encode(true)
			),

			// Test render with an empty string value to be serialized.
			array(
				array('empty' => ''),
				'empty',
				json_encode('')
			),

			// Test render with a zero value to be serialized.
			array(
				array('zero' => 0),
				'zero',
				json_encode(0)
			),
		);
	}

/**
 * Test render with a valid string in _serialize.
 *
 * @dataProvider renderWithoutViewProvider
 * @return void
 */
	public function testRenderWithoutView($data, $serialize, $expected) {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);

		$Controller->set($data);
		$Controller->set('_serialize', $serialize);
		$View = new JsonView($Controller);
		$output = $View->render(false);

		$this->assertSame($expected, $output);
	}

/**
 * Test that rendering with _serialize does not load helpers.
 *
 * @return void
 */
	public function testRenderSerializeNoHelpers() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);

		$Controller->helpers = array('Html');
		$Controller->set(array(
			'tags' => array('cakephp', 'framework'),
			'_serialize' => 'tags'
		));
		$View = new JsonView($Controller);
		$View->render();

		$this->assertFalse(isset($View->Html), 'No helper loaded.');
	}

/**
 * testJsonpResponse method
 *
 * @return void
 */
	public function testJsonpResponse() {
		$Request = new CakeRequest();
		$Response = new CakeResponse();
		$Controller = new Controller($Request, $Response);

		$data = array('user' => 'fake', 'list' => array('item1', 'item2'));
		$Controller->set(array(
			'data' => $data,
			'_serialize' => 'data',
			'_jsonp' => true
		));
		$View = new JsonView($Controller);
		$output = $View->render(false);

		$this->assertSame(json_encode($data), $output);
		$this->assertSame('application/json', $Response->type());

		$View->request->query = array('callback' => 'jfunc');
		$output = $View->render(false);
		$expected = 'jfunc(' . json_encode($data) . ')';
		$this->assertSame($expected, $output);
		$this->assertSame('application/javascript', $Response->type());

		$View->request->query = array('jsonCallback' => 'jfunc');
		$View->viewVars['_jsonp'] = 'jsonCallback';
		$output = $View->render(false);
		$expected = 'jfunc(' . json_encode($data) . ')';
		$this->assertSame($expected, $output);
	}

/**
 * Test render with a View file specified.
 *
 * @return void
 */
	public function testRenderWithView() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		$Request = new CakeRequest();
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
		$output = $View->render('index');

		$expected = json_encode(array('user' => 'fake', 'list' => array('item1', 'item2'), 'paging' => null));
		$this->assertSame($expected, $output);
		$this->assertSame('application/json', $Response->type());
	}

/**
 * Test render with a View file specified and named parameters.
 *
 * @return void
 */
	public function testRenderWithViewAndNamed() {
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
		$Controller->helpers = array('Paginator');
		$View = new JsonView($Controller);
		$output = $View->render('index');

		$expected = array('user' => 'fake', 'list' => array('item1', 'item2'), 'paging' => array('page' => 2));
		$this->assertSame(json_encode($expected), $output);
		$this->assertSame('application/json', $Response->type());

		$View->request->query = array('jsonCallback' => 'jfunc');
		$Controller->set('_jsonp', 'jsonCallback');
		$View = new JsonView($Controller);
		$View->helpers = array('Paginator');
		$output = $View->render('index');
		$expected['paging']['?']['jsonCallback'] = 'jfunc';
		$expected = 'jfunc(' . json_encode($expected) . ')';
		$this->assertSame($expected, $output);
		$this->assertSame('application/javascript', $Response->type());
	}
}
