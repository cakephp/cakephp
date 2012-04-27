<?php
/**
 * BasicAuthenticationTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Network.Http
 * @since         CakePHP(tm) v 2.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('HttpSocket', 'Network/Http');
App::uses('BasicAuthentication', 'Network/Http');

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

}
