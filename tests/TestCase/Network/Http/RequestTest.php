<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Network\Http;

use Cake\Network\Http\Request;
use Cake\TestSuite\TestCase;

/**
 * HTTP request test.
 */
class RequestTest extends TestCase {

/**
 * test url method
 *
 * @return void
 */
	public function testUrl() {
		$request = new Request();
		$this->assertSame($request, $request->url('http://example.com'));

		$this->assertEquals('http://example.com', $request->url());
	}

/**
 * test method method.
 *
 * @return void
 */
	public function testMethod() {
		$request = new Request();
		$this->assertSame($request, $request->method(Request::METHOD_GET));

		$this->assertEquals(Request::METHOD_GET, $request->method());
	}

/**
 * test invalid method.
 *
 * @expectedException \Cake\Core\Exception\Exception
 * @return void
 */
	public function testMethodInvalid() {
		$request = new Request();
		$request->method('set on fire');
	}

/**
 * test body method.
 *
 * @return void
 */
	public function testBody() {
		$data = '{"json":"data"}';
		$request = new Request();
		$this->assertSame($request, $request->body($data));

		$this->assertEquals($data, $request->body());
	}

/**
 * test header method.
 *
 * @return void
 */
	public function testHeader() {
		$request = new Request();
		$type = 'application/json';
		$result = $request->header('Content-Type', $type);
		$this->assertSame($result, $request, 'Should return self');

		$result = $request->header('content-type');
		$this->assertEquals($type, $result, 'lowercase does not work');

		$result = $request->header('ConTent-typE');
		$this->assertEquals($type, $result, 'Funny casing does not work');

		$result = $request->header([
			'Connection' => 'close',
			'user-agent' => 'CakePHP'
		]);
		$this->assertSame($result, $request, 'Should return self');

		$this->assertEquals('close', $request->header('connection'));
		$this->assertEquals('CakePHP', $request->header('USER-AGENT'));
		$this->assertNull($request->header('not set'));
	}

/**
 * test cookie method.
 *
 * @return void
 */
	public function testCookie() {
		$request = new Request();
		$result = $request->cookie('session', '123456');
		$this->assertSame($result, $request, 'Should return self');

		$this->assertNull($request->cookie('not set'));

		$result = $request->cookie('session');
		$this->assertEquals('123456', $result);
	}

}
