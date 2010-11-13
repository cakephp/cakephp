<?php
/**
 * DigestMethodTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.http
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Core', 'HttpSocket');
App::import('Lib', 'http/DigestMethod');

class DigestHttpSocket extends HttpSocket {

/**
 * nextHeader attribute
 *
 * @var string
 */
	public $nextHeader = '';

/**
 * request method
 *
 * @param mixed $request
 * @return void
 */
	public function request($request) {
		$this->response['header']['Www-Authenticate'] = $this->nextHeader;
	}

}

/**
 * DigestMethodTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.http
 */
class DigestMethodTest extends CakeTestCase {

/**
 * Socket property
 *
 * @var mixed null
 */
	public $HttpSocket = null;

/**
 * This function sets up a HttpSocket instance we are going to use for testing
 *
 * @return void
 */
	public function setUp() {
		$this->HttpSocket = new DigestHttpSocket();
		$this->HttpSocket->request['method'] = 'GET';
		$this->HttpSocket->request['uri']['path'] = '/';
		$this->HttpSocket->request['auth'] = array(
			'method' => 'Digest',
			'user' => 'admin',
			'pass' => '1234'
		);
	}

/**
 * We use this function to clean up after the test case was executed
 *
 * @return void
 */
	function tearDown() {
		unset($this->HttpSocket);
	}

/**
 * testBasic method
 *
 * @return void
 */
	public function testBasic() {
		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51"';
		$this->HttpSocket->config['request']['auth'] = array();
		$this->assertFalse(isset($this->HttpSocket->request['header']['Authorization']));
		DigestMethod::authentication($this->HttpSocket);
		$this->assertTrue(isset($this->HttpSocket->request['header']['Authorization']));
		$this->assertEqual($this->HttpSocket->config['request']['auth']['realm'], 'The batcave');
		$this->assertEqual($this->HttpSocket->config['request']['auth']['nonce'], '4cded326c6c51');
	}

/**
 * testQop method
 *
 * @return void
 */
	public function testQop() {
		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51"';
		$this->HttpSocket->config['request']['auth'] = array();
		DigestMethod::authentication($this->HttpSocket);
		$expected = 'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/", response="da7e2a46b471d77f70a9bb3698c8902b"';
		$this->assertEqual($expected, $this->HttpSocket->request['header']['Authorization']);
		$this->assertFalse(isset($this->HttpSocket->config['request']['auth']['qop']));
		$this->assertFalse(isset($this->HttpSocket->config['request']['auth']['nc']));

		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51",qop="auth"';
		$this->HttpSocket->config['request']['auth'] = array();
		DigestMethod::authentication($this->HttpSocket);
		$expected = '@Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/", response="[a-z0-9]{32}", qop="auth", nc=00000001, cnonce="[a-z0-9]+"@';
		$this->assertPattern($expected, $this->HttpSocket->request['header']['Authorization']);
		$this->assertEqual($this->HttpSocket->config['request']['auth']['qop'], 'auth');
		$this->assertEqual($this->HttpSocket->config['request']['auth']['nc'], 2);
	}

/**
 * testOpaque method
 *
 * @return void
 */
	public function testOpaque() {
		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51"';
		$this->HttpSocket->config['request']['auth'] = array();
		DigestMethod::authentication($this->HttpSocket);
		$this->assertFalse(strpos($this->HttpSocket->request['header']['Authorization'], 'opaque="d8ea7aa61a1693024c4cc3a516f49b3c"'));

		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c"';
		$this->HttpSocket->config['request']['auth'] = array();
		DigestMethod::authentication($this->HttpSocket);
		$this->assertTrue(strpos($this->HttpSocket->request['header']['Authorization'], 'opaque="d8ea7aa61a1693024c4cc3a516f49b3c"') > 0);
	}

/**
 * testMultipleRequest method
 *
 * @return void
 */
	public function testMultipleRequest() {
		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51",qop="auth"';
		$this->HttpSocket->config['request']['auth'] = array();
		DigestMethod::authentication($this->HttpSocket);
		$this->assertTrue(strpos($this->HttpSocket->request['header']['Authorization'], 'nc=00000001') > 0);
		$this->assertEqual($this->HttpSocket->config['request']['auth']['nc'], 2);

		DigestMethod::authentication($this->HttpSocket);
		$this->assertTrue(strpos($this->HttpSocket->request['header']['Authorization'], 'nc=00000002') > 0);
		$this->assertEqual($this->HttpSocket->config['request']['auth']['nc'], 3);
		$responsePos = strpos($this->HttpSocket->request['header']['Authorization'], 'response=');
		$response = substr($this->HttpSocket->request['header']['Authorization'], $responsePos + 10, 32);

		$this->HttpSocket->nextHeader = '';
		DigestMethod::authentication($this->HttpSocket);
		$this->assertTrue(strpos($this->HttpSocket->request['header']['Authorization'], 'nc=00000003') > 0);
		$this->assertEqual($this->HttpSocket->config['request']['auth']['nc'], 4);
		$responsePos = strpos($this->HttpSocket->request['header']['Authorization'], 'response=');
		$response2 = substr($this->HttpSocket->request['header']['Authorization'], $responsePos + 10, 32);
		$this->assertNotEqual($response, $response2);
	}

/**
 * testPathChanged method
 *
 * @return void
 */
	public function testPathChanged() {
		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51"';
		$this->HttpSocket->request['uri']['path'] = '/admin';
		$this->HttpSocket->config['request']['auth'] = array();
		DigestMethod::authentication($this->HttpSocket);
		$responsePos = strpos($this->HttpSocket->request['header']['Authorization'], 'response=');
		$response = substr($this->HttpSocket->request['header']['Authorization'], $responsePos + 10, 32);
		$this->assertNotEqual($response, 'da7e2a46b471d77f70a9bb3698c8902b');
	}

}