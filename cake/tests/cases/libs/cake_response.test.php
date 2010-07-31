<?php

App::import('Core', array('CakeResponse', 'CakeRequest'));

class CakeRequestTestCase extends CakeTestCase {


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
}