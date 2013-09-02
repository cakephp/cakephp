<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 2.1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\TestSuite\TestCase;
use Cake\View\JsonView;

/**
 * JsonViewTest
 *
 * @package Cake.Test.Case.View
 */
class JsonViewTest extends TestCase {

	public function setUp() {
		parent::setUp();
		Configure::write('debug', 0);
	}

/**
 * testRenderWithoutView method
 *
 * @return void
 */
	public function testRenderWithoutView() {
		$Request = new Request();
		$Response = new Response();
		$Controller = new Controller($Request, $Response);
		$data = array('user' => 'fake', 'list' => array('item1', 'item2'));
		$Controller->set(array('data' => $data, '_serialize' => 'data'));
		$View = new JsonView($Controller);
		$output = $View->render(false);

		$this->assertSame(json_encode($data), $output);
		$this->assertSame('application/json', $Response->type());
	}

/**
 * Test that rendering with _serialize does not load helpers
 *
 * @return void
 */
	public function testRenderSerializeNoHelpers() {
		$Request = new Request();
		$Response = new Response();
		$Controller = new Controller($Request, $Response);
		$Controller->helpers = array('Html');
		$Controller->set(array(
			'_serialize' => 'tags',
			'tags' => array('cakephp', 'framework')
		));
		$View = new JsonView($Controller);
		$View->render();
		$this->assertFalse(isset($View->Html), 'No helper loaded.');
	}

/**
 * Test render with an array in _serialize
 *
 * @return void
 */
	public function testRenderWithoutViewMultiple() {
		$Request = new Request();
		$Response = new Response();
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
 * Test render with an array in _serialize and alias
 *
 * @return void
 */
	public function testRenderWithoutViewMultipleAndAlias() {
		$Request = new Request();
		$Response = new Response();
		$Controller = new Controller($Request, $Response);
		$data = array('original_name' => 'my epic name', 'user' => 'fake', 'list' => array('item1', 'item2'));
		$Controller->set($data);
		$Controller->set('_serialize', array('new_name' => 'original_name', 'user'));
		$View = new JsonView($Controller);
		$output = $View->render(false);

		$this->assertSame(json_encode(array('new_name' => $data['original_name'], 'user' => $data['user'])), $output);
		$this->assertSame('application/json', $Response->type());
	}

/**
 * testJsonpResponse method
 *
 * @return void
 */
	public function testJsonpResponse() {
		$Request = new Request();
		$Response = new Response();
		$Controller = new Controller($Request, $Response);
		$data = array('user' => 'fake', 'list' => array('item1', 'item2'));
		$Controller->set(array('data' => $data, '_serialize' => 'data', '_jsonp' => true));
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
 * testRenderWithView method
 *
 * @return void
 */
	public function testRenderWithView() {
		App::build([
			'View' => [CAKE . 'Test/TestApp/View/']
		]);
		$Request = new Request();
		$Response = new Response();
		$Controller = new Controller($Request, $Response);
		$Controller->name = $Controller->viewPath = 'Posts';

		$data = [
			'User' => [
				'username' => 'fake'
			],
			'Item' => [
				['name' => 'item1'],
				['name' => 'item2']
			]
		];
		$Controller->set('user', $data);
		$Controller->helpers = ['Paginator'];
		$View = new JsonView($Controller);
		$output = $View->render('index');

		$expected = json_encode(['user' => 'fake', 'list' => ['item1', 'item2'], 'paging' => []]);
		$this->assertSame($expected, $output);
		$this->assertSame('application/json', $Response->type());

		$View->request->query = array('jsonCallback' => 'jfunc');
		$Controller->set('_jsonp', 'jsonCallback');
		$View = new JsonView($Controller);
		$View->helpers = array('Paginator');
		$output = $View->render('index');

		$expected = json_encode([
			'user' => 'fake',
			'list' => ['item1', 'item2'],
			'paging' => ['?' => ['jsonCallback' => 'jfunc']]
		]);
		$expected = 'jfunc(' . $expected . ')';
		$this->assertSame($expected, $output);
		$this->assertSame('application/javascript', $Response->type());
	}

}
