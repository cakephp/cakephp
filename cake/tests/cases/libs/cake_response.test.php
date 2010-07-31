<?php

App::import('Core', array('CakeResponse', 'CakeRequest'));

class CakeRequestTestCase extends CakeTestCase {


/**
* Tests the request object constructor
*
*/
	public function testConstruct() {
		$response = new CakeResponse();
		$this->assertNull($response->body());
		$this->assertEquals($response->encoding(), 'UTF-8');
		$this->assertEquals($response->type(), 'text/html');
		$this->assertEquals($response->statusCode(), 200);

		$options = array(
			'body' => 'This is the body',
			'encoding' => 'my-custom-encoding',
			'type' => 'mp3',
			'status' => '203'
		);
		$response = new CakeResponse($options);
		$this->assertEquals($response->body(), 'This is the body');
		$this->assertEquals($response->encoding(), 'my-custom-encoding');
		$this->assertEquals($response->type(), 'audio/mpeg');
		$this->assertEquals($response->statusCode(), 203);	
	}

/**
* Tests the body method
*
*/
	public function testBody() {
		$response = new CakeResponse();
		$this->assertNull($response->body());
		$response->body('Response body');
		$this->assertEquals($response->body(), 'Response body');
		$this->assertEquals($response->body('Changed Body'), 'Changed Body');
	}

/**
* Tests the encoding method
*
*/
	public function testEncoding() {
		$response = new CakeResponse();
		$this->assertEquals($response->encoding(), 'UTF-8');
		$response->encoding('iso-8859-1');
		$this->assertEquals($response->encoding(), 'iso-8859-1');
		$this->assertEquals($response->encoding('UTF-16'), 'UTF-16');
	}

/**
* Tests the statusCode method
*
* @expectedException OutOfRangeException
*/
	public function testStatusCode() {
		$response = new CakeResponse();
		$this->assertEquals($response->statusCode(), 200);
		$response->statusCode(404);
		$this->assertEquals($response->statusCode(), 404);
		$this->assertEquals($response->statusCode(500), 500);

		//Throws exception
		$response->statusCode(1001);
	}

/**
* Tests the type method
*
*/
	public function testType() {
		$response = new CakeResponse();
		$this->assertEquals($response->type(), 'text/html');
		$response->type('pdf');
		$this->assertEquals($response->type(), 'application/pdf');
		$this->assertEquals($response->type('application/crazy-mime'), 'application/crazy-mime');
		$this->assertEquals($response->type('json'), 'application/json');
		$this->assertEquals($response->type('wap'), 'text/vnd.wap.wml');
		$this->assertEquals($response->type('xhtml-mobile'), 'application/vnd.wap.xhtml+xml');
		$this->assertEquals($response->type('csv'), 'text/csv');
	}

/**
* Tests the header method
*
*/
	public function testHeader() {
		$response = new CakeResponse();
		$headers = array();
		$this->assertEquals($response->header(), $headers);

		$response->header('Location', 'http://example.com');
		$headers += array('Location' => 'http://example.com');
		$this->assertEquals($response->header(), $headers);

		//Headers with the same name are overwritten
		$response->header('Location', 'http://example2.com');
		$headers = array('Location' => 'http://example2.com');
		$this->assertEquals($response->header(), $headers);

		$response->header(array('WWW-Authenticate' => 'Negotiate'));
		$headers += array('WWW-Authenticate' => 'Negotiate');
		$this->assertEquals($response->header(), $headers);

		$response->header(array('WWW-Authenticate' => 'Not-Negotiate'));
		$headers['WWW-Authenticate'] = 'Not-Negotiate';
		$this->assertEquals($response->header(), $headers);

		$response->header(array('Age' => 12, 'Allow' => 'GET, HEAD'));
		$headers += array('Age' => 12, 'Allow' => 'GET, HEAD');
		$this->assertEquals($response->header(), $headers);

		// String headers are allowed
		$response->header('Content-Language: da');
		$headers += array('Content-Language' => 'da');
		$this->assertEquals($response->header(), $headers);

		$response->header('Content-Language: da');
		$headers += array('Content-Language' => 'da');
		$this->assertEquals($response->header(), $headers);

		$response->header(array('Content-Encoding: gzip', 'Vary: *', 'Pragma' => 'no-cache'));
		$headers += array('Content-Encoding' => 'gzip', 'Vary' => '*', 'Pragma' => 'no-cache');
		$this->assertEquals($response->header(), $headers);
	}
}