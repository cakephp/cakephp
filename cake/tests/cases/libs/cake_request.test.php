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
		$this->assertEqual($request->params['url'], $_GET);
	}
}