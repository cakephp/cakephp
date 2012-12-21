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
namespace Cake\Test\TestCase\Network\Http\Adapter;

use Cake\Network\Http\Adapter\Stream;
use Cake\Network\Http\Request;
use Cake\Network\Http\Response;
use Cake\TestSuite\TestCase;

/**
 * HTTP stream adapter test.
 */
class StreamTest extends TestCase {

/**
 * Test the send method
 *
 * @return void
 */
	public function testSend() {
		$stream = new Stream();
		$request = new Request();
		$request->url('http://localhost')
			->header('User-Agent', 'CakePHP TestSuite')
			->cookie('testing', 'value');

		$response = $stream->send($request, []);
		$this->assertInstanceOf('Cake\Network\Http\Response', $response);
	}

/**
 * Test building the context headers
 *
 * @return void
 */
	public function testBuildingContextHeader() {
		$stream = $this->getMock(
			'Cake\Network\Http\Adapter\Stream',
			['_send']
		);
		$request = new Request();
		$request->url('http://localhost')
			->header([
				'User-Agent' => 'CakePHP TestSuite',
				'Content-Type' => 'application/json'
			])
			->cookie([
				'testing' => 'value',
				'utm_src' => 'awesome',
			]);

		$stream->send($request, []);
		$result = $stream->contextOptions();
		$expected = [
			'Connection: close',
			'User-Agent: CakePHP TestSuite',
			'Content-Type: application/json',
			'Cookie: testing=value; utm_src=awesome',
		];
		$this->assertEquals(implode("\r\n", $expected), $result['header']);
	}

}
