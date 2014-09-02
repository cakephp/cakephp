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
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\Core\Configure;
use Cake\Network\Response;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Self test of the IntegrationTestCase
 */
class IntegrationTestCaseTest extends IntegrationTestCase {

/**
 * Setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('App.namespace', 'TestApp');

		Router::connect('/:controller/:action/*', [], ['routeClass' => 'InflectedRoute']);
		DispatcherFactory::clear();
		DispatcherFactory::add('Routing');
		DispatcherFactory::add('ControllerFactory');
	}

/**
 * Test building a request.
 *
 * @return void
 */
	public function testRequestBuilding() {
		$this->configRequest([
			'headers' => ['X-CSRF-Token' => 'abc123'],
			'base' => '',
			'webroot' => '/'
		]);
		$this->cookie('split_token', 'def345');
		$this->session(['User' => ['id' => 1, 'username' => 'mark']]);
		$request = $this->_buildRequest('/tasks/add', 'POST', ['title' => 'First post']);

		$this->assertEquals('abc123', $request->header('X-CSRF-Token'));
		$this->assertEquals('tasks/add', $request->url);
		$this->assertEquals(['split_token' => 'def345'], $request->cookies);
		$this->assertEquals(['id' => '1', 'username' => 'mark'], $request->session()->read('User'));
	}

/**
 * Test sending get requests.
 *
 * @return void
 */
	public function testSendingGet() {
		$this->assertNull($this->_response);

		$this->get('/request_action/test_request_action');
		$this->assertNotEmpty($this->_response);
		$this->assertInstanceOf('Cake\Network\Response', $this->_response);
		$this->assertEquals('This is a test', $this->_response->body());
	}

/**
 * Test the responseOk status assertion
 *
 * @return void
 */
	public function testAssertResponseStatusCodes() {
		$this->_response = new Response();

		$this->_response->statusCode(200);
		$this->assertResponseOk();

		$this->_response->statusCode(201);
		$this->assertResponseOk();

		$this->_response->statusCode(204);
		$this->assertResponseOk();

		$this->_response->statusCode(400);
		$this->assertResponseError();

		$this->_response->statusCode(417);
		$this->assertResponseError();

		$this->_response->statusCode(500);
		$this->assertResponseFailure();

		$this->_response->statusCode(505);
		$this->assertResponseFailure();
	}

/**
 * Test the location header assertion.
 *
 * @return void
 */
	public function testAssertRedirect() {
		$this->_response = new Response();
		$this->_response->header('Location', 'http://localhost/tasks/index');

		$this->assertRedirect('/tasks/index');
		$this->assertRedirect(['controller' => 'Tasks', 'action' => 'index']);
	}

}
