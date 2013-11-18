<?php
/**
 * DigestAuthenticationTest file
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
 * @package       Cake.Test.Case.Network.Http
 * @since         CakePHP(tm) v 2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('HttpSocket', 'Network/Http');
App::uses('DigestAuthentication', 'Network/Http');

/**
 * Class DigestHttpSocket
 *
 * @package       Cake.Test.Case.Network.Http
 */
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
	public function request($request = array()) {
		if ($request === false) {
			if (isset($this->response['header']['WWW-Authenticate'])) {
				unset($this->response['header']['WWW-Authenticate']);
			}
			return;
		}
		$this->response['header']['WWW-Authenticate'] = $this->nextHeader;
	}

}

/**
 * DigestAuthenticationTest class
 *
 * @package       Cake.Test.Case.Network.Http
 */
class DigestAuthenticationTest extends CakeTestCase {

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
		parent::setUp();
		$this->HttpSocket = new DigestHttpSocket();
		$this->HttpSocket->request['method'] = 'GET';
		$this->HttpSocket->request['uri']['path'] = '/';
	}

/**
 * We use this function to clean up after the test case was executed
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->HttpSocket);
	}

/**
 * testBasic method
 *
 * @return void
 */
	public function testBasic() {
		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51"';
		$this->assertFalse(isset($this->HttpSocket->request['header']['Authorization']));

		$auth = array('user' => 'admin', 'pass' => '1234');
		DigestAuthentication::authentication($this->HttpSocket, $auth);
		$this->assertTrue(isset($this->HttpSocket->request['header']['Authorization']));
		$this->assertEquals('The batcave', $auth['realm']);
		$this->assertEquals('4cded326c6c51', $auth['nonce']);
	}

/**
 * testQop method
 *
 * @return void
 */
	public function testQop() {
		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51"';
		$auth = array('user' => 'admin', 'pass' => '1234');
		DigestAuthentication::authentication($this->HttpSocket, $auth);
		$expected = 'Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/", response="da7e2a46b471d77f70a9bb3698c8902b"';
		$this->assertEquals($expected, $this->HttpSocket->request['header']['Authorization']);
		$this->assertFalse(isset($auth['qop']));
		$this->assertFalse(isset($auth['nc']));

		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51",qop="auth"';
		$auth = array('user' => 'admin', 'pass' => '1234');
		DigestAuthentication::authentication($this->HttpSocket, $auth);
		$expected = '@Digest username="admin", realm="The batcave", nonce="4cded326c6c51", uri="/", response="[a-z0-9]{32}", qop="auth", nc=00000001, cnonce="[a-z0-9]+"@';
		$this->assertRegExp($expected, $this->HttpSocket->request['header']['Authorization']);
		$this->assertEquals('auth', $auth['qop']);
		$this->assertEquals(2, $auth['nc']);
	}

/**
 * testOpaque method
 *
 * @return void
 */
	public function testOpaque() {
		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51"';
		$auth = array('user' => 'admin', 'pass' => '1234');
		DigestAuthentication::authentication($this->HttpSocket, $auth);
		$this->assertFalse(strpos($this->HttpSocket->request['header']['Authorization'], 'opaque="d8ea7aa61a1693024c4cc3a516f49b3c"'));

		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51",opaque="d8ea7aa61a1693024c4cc3a516f49b3c"';
		$auth = array('user' => 'admin', 'pass' => '1234');
		DigestAuthentication::authentication($this->HttpSocket, $auth);
		$this->assertTrue(strpos($this->HttpSocket->request['header']['Authorization'], 'opaque="d8ea7aa61a1693024c4cc3a516f49b3c"') > 0);
	}

/**
 * testMultipleRequest method
 *
 * @return void
 */
	public function testMultipleRequest() {
		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51",qop="auth"';
		$auth = array('user' => 'admin', 'pass' => '1234');
		DigestAuthentication::authentication($this->HttpSocket, $auth);
		$this->assertTrue(strpos($this->HttpSocket->request['header']['Authorization'], 'nc=00000001') > 0);
		$this->assertEquals(2, $auth['nc']);

		DigestAuthentication::authentication($this->HttpSocket, $auth);
		$this->assertTrue(strpos($this->HttpSocket->request['header']['Authorization'], 'nc=00000002') > 0);
		$this->assertEquals(3, $auth['nc']);
		$responsePos = strpos($this->HttpSocket->request['header']['Authorization'], 'response=');
		$response = substr($this->HttpSocket->request['header']['Authorization'], $responsePos + 10, 32);

		$this->HttpSocket->nextHeader = '';
		DigestAuthentication::authentication($this->HttpSocket, $auth);
		$this->assertTrue(strpos($this->HttpSocket->request['header']['Authorization'], 'nc=00000003') > 0);
		$this->assertEquals(4, $auth['nc']);
		$responsePos = strpos($this->HttpSocket->request['header']['Authorization'], 'response=');
		$responseB = substr($this->HttpSocket->request['header']['Authorization'], $responsePos + 10, 32);
		$this->assertNotEquals($response, $responseB);
	}

/**
 * testPathChanged method
 *
 * @return void
 */
	public function testPathChanged() {
		$this->HttpSocket->nextHeader = 'Digest realm="The batcave",nonce="4cded326c6c51"';
		$this->HttpSocket->request['uri']['path'] = '/admin';
		$auth = array('user' => 'admin', 'pass' => '1234');
		DigestAuthentication::authentication($this->HttpSocket, $auth);
		$responsePos = strpos($this->HttpSocket->request['header']['Authorization'], 'response=');
		$response = substr($this->HttpSocket->request['header']['Authorization'], $responsePos + 10, 32);
		$this->assertNotEquals('da7e2a46b471d77f70a9bb3698c8902b', $response);
	}

/**
 * testNoDigestResponse method
 *
 * @return void
 */
	public function testNoDigestResponse() {
		$this->HttpSocket->nextHeader = false;
		$this->HttpSocket->request['uri']['path'] = '/admin';
		$auth = array('user' => 'admin', 'pass' => '1234');
		DigestAuthentication::authentication($this->HttpSocket, $auth);
		$this->assertFalse(isset($this->HttpSocket->request['header']['Authorization']));
	}

}
