<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testMethodInvalid() {
		$request = new Request();
		$request->method('set on fire');
	}

/**
 * test content method.
 *
 * @return void
 */
	public function testContent() {
		$data = '{"json":"data"}';
		$request = new Request();
		$this->assertSame($request, $request->content($data));

		$this->assertEquals($data, $request->content());
	}

/**
 * test header method.
 *
 * @return void
 */
	public function testHeader() {
		$this->markTestIncomplete();
	}

/**
 * test headers being case insenstive
 *
 * @return void
 */
	public function testHeaderInsensitive() {
		$this->markTestIncomplete();
	}

}
