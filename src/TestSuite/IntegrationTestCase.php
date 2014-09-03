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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Core\Configure;
use Cake\Network\Request;
use Cake\Network\Session;
use Cake\Routing\DispatcherFactory;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\TestSuite\Stub\Response;

/**
 * A test case class intended to make integration tests of
 * your controllers easier.
 *
 * This test class provides a number of helper methods and features
 * that make dispatching requests and checking their responses simpler.
 * It favours full integration tests over mock objects as you can test
 * more of your code easily and avoid some of the maintenance pitfalls
 * that mock objects create.
 */
class IntegrationTestCase extends TestCase {

/**
 * The data used to build the next request.
 *
 * @var array
 */
	protected $_request = [];

/**
 * The response for the most recent request.
 *
 * @var \Cake\Network\Response
 */
	protected $_response;

/**
 * Session data to use in the next request.
 *
 * @var array
 */
	protected $_session = [];

/**
 * Cookie data to use in the next request.
 *
 * @var array
 */
	protected $_cookie = [];

/**
 * Clear the state used for requests.
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		$this->_request = [];
		$this->_session = [];
		$this->_cookie = [];
		$this->_response = null;
	}

/**
 * Configure the data for the *next* request.
 *
 * This data is cleared in the tearDown() method.
 *
 * You can call this method multiple times to append into
 * the current state.
 *
 * @param array $data The request data to use.
 * @return void
 */
	public function configRequest(array $data) {
		$this->_request = $data + $this->_request;
	}

/**
 * Set session data.
 *
 * This method lets you configure the session data
 * you want to be used for requests that follow. The session
 * state is reset in each tearDown().
 *
 * You can call this method multiple times to append into
 * the current state.
 *
 * @param array $data The session data to use.
 * @return void
 */
	public function session(array $data) {
		$this->_session = $data + $this->_session;
	}

/**
 * Set a request cookie for future requests.
 *
 * This method lets you configure the session data
 * you want to be used for requests that follow. The session
 * state is reset in each tearDown().
 *
 * You can call this method multiple times to append into
 * the current state.
 *
 * @param string $name The cookie name to use.
 * @param mixed $value The value of the cookie.
 * @return void
 */
	public function cookie($name, $value) {
		$this->_cookie[$name] = $value;
	}

/**
 * Perform a GET request using the current request data.
 *
 * The response of the dispatched request will be stored as
 * a property. You can use various assert methods to check the
 * response.
 *
 * @param string $url The url to request.
 * @return void
 */
	public function get($url) {
		$this->_sendRequest($url, 'GET');
	}

/**
 * Perform a POST request using the current request data.
 *
 * The response of the dispatched request will be stored as
 * a property. You can use various assert methods to check the
 * response.
 *
 * @param string $url The url to request.
 * @param array $data The data for the request.
 * @return void
 */
	public function post($url, $data = []) {
		$this->_sendRequest($url, 'POST', $data);
	}

/**
 * Perform a PATCH request using the current request data.
 *
 * The response of the dispatched request will be stored as
 * a property. You can use various assert methods to check the
 * response.
 *
 * @param string $url The url to request.
 * @param array $data The data for the request.
 * @return void
 */
	public function patch($url, $data = []) {
		$this->_sendRequest($url, 'PATCH', $data);
	}

/**
 * Perform a PUT request using the current request data.
 *
 * The response of the dispatched request will be stored as
 * a property. You can use various assert methods to check the
 * response.
 *
 * @param string $url The url to request.
 * @param array $data The data for the request.
 * @return void
 */
	public function put($url, $data = []) {
		$this->_sendRequest($url, 'PUT', $data);
	}

/**
 * Perform a DELETE request using the current request data.
 *
 * The response of the dispatched request will be stored as
 * a property. You can use various assert methods to check the
 * response.
 *
 * @param string $url The url to request.
 * @return void
 */
	public function delete($url) {
		$this->_sendRequest($url, 'DELETE');
	}

/**
 * Create and send the request into a Dispatcher instance.
 *
 * Receives and stores the response for future inspection.
 *
 * @param string $url The url
 * @param string $method The HTTP method
 * @param array|null $data The request data.
 * @return void
 */
	protected function _sendRequest($url, $method, $data = null) {
		$request = $this->_buildRequest($url, $method, $data);
		$response = new Response();
		$dispatcher = DispatcherFactory::create();
		$dispatcher->dispatch($request, $response);
		$this->_response = $response;
	}

/**
 * Create a request object with the configured options and parameters.
 *
 * @param string $url The url
 * @param string $method The HTTP method
 * @param array|null $data The request data.
 * @return \Cake\Network\Request The built request.
 */
	protected function _buildRequest($url, $method, $data) {
		$sessionConfig = (array)Configure::read('Session') + [
			'defaults' => 'php',
		];
		$session = Session::create($sessionConfig);
		$session->write($this->_session);

		$props = [
			'url' => $url,
			'post' => $data,
			'cookies' => $this->_cookie,
			'session' => $session,
		];
		if (isset($this->_request['headers'])) {
			$env = [];
			foreach ($this->_request['headers'] as $k => $v) {
				$env['HTTP_' . str_replace('-', '_', strtoupper($k))] = $v;
			}
			$props['environment'] = $env;
			unset($this->_request['headers']);
		}
		$props += $this->_request;
		return new Request($props);
	}

/**
 * Assert that the response status code is in the 2xx range.
 *
 * @return void
 */
	public function assertResponseOk() {
		$this->_assertStatus(200, 204, 'Status code is not between 200 and 204');
	}

/**
 * Assert that the response status code is in the 4xx range.
 *
 * @return void
 */
	public function assertResponseError() {
		$this->_assertStatus(400, 417, 'Status code is not between 400 and 417');
	}

/**
 * Assert that the response status code is in the 5xx range.
 *
 * @return void
 */
	public function assertResponseFailure() {
		$this->_assertStatus(500, 505, 'Status code is not between 500 and 505');
	}

/**
 * Helper method for status assertions.
 *
 * @param int $min Min status code.
 * @param int $max Max status code.
 * @param string $message The error message.
 * @return void
 */
	protected function _assertStatus($min, $max, $message) {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert status code.');
		}
		$status = $this->_response->statusCode();
		$this->assertGreaterThanOrEqual($min, $status, $message);
		$this->assertLessThanOrEqual($max, $status, $message);
	}

/**
 * Assert that the Location header is correct.
 *
 * @param string|array $url The url you expected the client to go to. This
 *   can either be a string URL or an array compatible with Router::url()
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertRedirect($url, $message = '') {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert location header. ' . $message);
		}
		$result = $this->_response->header();
		if (empty($result['Location'])) {
			$this->fail('No location header set. ' . $message);
		}
		$this->assertEquals(Router::url($url, ['_full' => true]), $result['Location'], $message);
	}

/**
 * Assert response headers
 *
 * @param string $header The header to check
 * @param string $content The content to check for.
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertHeader($header, $content, $message = '') {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert headers. ' . $message);
		}
		$headers = $this->_response->header();
		if (!isset($headers[$header])) {
			$this->fail("The '$header' header is not set. " . $message);
		}
		$this->assertEquals($headers[$header], $content, $message);
	}

/**
 * Assert content type
 *
 * @param string $type The content-type to check for.
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertContentType($type, $message = '') {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert content-type. ' . $message);
		}
		$alias = $this->_response->getMimeType($type);
		if ($alias !== false) {
			$type = $alias;
		}
		$result = $this->_response->type();
		$this->assertEquals($type, $result, $message);
	}

/**
 * Assert content exists in the response body.
 *
 * @param string $content The content to check for.
 * @param string $message The failure message that will be appended to the generated message.
 * @return void
 */
	public function assertResponseContains($content, $message = '') {
		if (!$this->_response) {
			$this->fail('No response set, cannot assert content. ' . $message);
		}
		$this->assertContains($content, $this->_response->body(), $message);
	}

}
