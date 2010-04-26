<?php

App::import('Core', 'CakeRequest');

class CakeRequestTestCase extends CakeTestCase {
/**
 * setup callback
 *
 * @return void
 */
	function startTest() {
		$this->_server = $_SERVER;
		$this->_get = $_GET;
		$this->_post = $_POST;
		$this->_files = $_FILES;
	}

/**
 * end test
 *
 * @return void
 */
	function endTest() {
		$_SERVER = $this->_server;
		$_GET = $this->_get;
		$_POST = $this->_post;
		$_FILES = $this->_files;
	}

/**
 * test construction
 *
 * @return void
 */
	function testConstructionGetParsing() {
		$GET = array(
			'one' => 'param',
			'two' => 'banana'
		);
		$request = new CakeRequest();
		$this->assertEqual($request->url, $_GET);
	}

/**
 * test parsing POST data into the object.
 *
 * @return void
 */
	function testPostParsing() {
		$_POST = array('data' => array(
			'Article' => array('title')
		));
		$request = new CakeRequest();
		$this->assertEqual($request->data, $_POST['data']);

		$_POST = array('one' => 1, 'two' => 'three');
		$request = new CakeRequest();
		$this->assertEqual($request->params['form'], $_POST);
	}

/**
 * test method overrides coming in from POST data.
 *
 * @return void
 */
	function testMethodOverrides() {
		$_POST = array('_method' => 'POST');
		$request = new CakeRequest();
		$this->assertEqual(env('REQUEST_METHOD'), 'POST');

		$_POST = array('_method' => 'DELETE');
		$request = new CakeRequest();
		$this->assertEqual(env('REQUEST_METHOD'), 'DELETE');
	}

/**
 * test the getClientIp method.
 *
 * @return void
 */
	function testGetClientIp() {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.5, 10.0.1.1, proxy.com';
		$_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.3';
		$request = new CakeRequest();
		$this->assertEqual($request->getClientIP(false), '192.168.1.5');
		$this->assertEqual($request->getClientIP(), '192.168.1.2');

		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
		$this->assertEqual($request->getClientIP(), '192.168.1.2');

		unset($_SERVER['HTTP_CLIENT_IP']);
		$this->assertEqual($request->getClientIP(), '192.168.1.3');

		$_SERVER['HTTP_CLIENTADDRESS'] = '10.0.1.2, 10.0.1.1';
		$this->assertEqual($request->getClientIP(), '10.0.1.2');
	}
}