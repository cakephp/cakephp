<?php
/**
 * BasicAuthenticationTest file
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
App::uses('BasicAuthentication', 'Network/Http');

/**
 * class TestSslHttpSocket
 *
 * @package       Cake.Test.Case.Network.Http
 */
class TestSslHttpSocket extends HttpSocket {

/**
 * testSetProxy method
 *
 * @return void
 */
	public function testSetProxy($proxy = null) {
		$this->_proxy = $proxy;
		$this->_setProxy();
	}

}

/**
 * BasicMethodTest class
 *
 * @package       Cake.Test.Case.Network.Http
 */
class BasicAuthenticationTest extends CakeTestCase {

/**
 * testAuthentication method
 *
 * @return void
 */
	public function testAuthentication() {
		$http = new HttpSocket();
		$auth = array(
			'method' => 'Basic',
			'user' => 'mark',
			'pass' => 'secret'
		);

		BasicAuthentication::authentication($http, $auth);
		$this->assertEquals('Basic bWFyazpzZWNyZXQ=', $http->request['header']['Authorization']);
	}

/**
 * testProxyAuthentication method
 *
 * @return void
 */
	public function testProxyAuthentication() {
		$http = new HttpSocket();
		$proxy = array(
			'method' => 'Basic',
			'user' => 'mark',
			'pass' => 'secret'
		);

		BasicAuthentication::proxyAuthentication($http, $proxy);
		$this->assertEquals('Basic bWFyazpzZWNyZXQ=', $http->request['header']['Proxy-Authorization']);
	}

/**
 * testProxyAuthenticationSsl method
 *
 * @return void
 */
	public function testProxyAuthenticationSsl() {
		$http = new TestSslHttpSocket();
		$http->request['uri']['scheme'] = 'https';
		$proxy = array(
			'host' => 'localhost',
			'port' => 3128,
			'method' => 'Basic',
			'user' => 'mark',
			'pass' => 'secret'
		);

		$http->testSetProxy($proxy);

		$this->assertEquals('Basic bWFyazpzZWNyZXQ=', $http->config['proxyauth']);
		$this->assertFalse(isset($http->request['header']['Proxy-Authorization']));
	}

}
