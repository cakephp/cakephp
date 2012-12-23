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

	public function setUp() {
		parent::setUp();
		$this->stream = $this->getMock(
			'Cake\Network\Http\Adapter\Stream',
			['_send']
		);
	}

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

		$options = [
			'redirect' => 20
		];
		$this->stream->send($request, $options);
		$result = $this->stream->contextOptions();
		$expected = [
			'Connection: close',
			'User-Agent: CakePHP TestSuite',
			'Content-Type: application/json',
			'Cookie: testing=value; utm_src=awesome',
		];
		$this->assertEquals(implode("\r\n", $expected), $result['header']);
		$this->assertEquals($options['redirect'], $result['max_redirects']);
		$this->assertTrue($result['ignore_errors']);
	}

/**
 * Test send() + context options with string content.
 *
 * @return void
 */
	public function testSendContextContentString() {
		$content = json_encode(['a' => 'b']);
		$request = new Request();
		$request->url('http://localhost')
			->header([
				'Content-Type' => 'application/json'
			])
			->content($content);

		$options = [
			'redirect' => 20
		];
		$this->stream->send($request, $options);
		$result = $this->stream->contextOptions();
		$expected = [
			'Connection: close',
			'User-Agent: CakePHP',
			'Content-Type: application/json',
		];
		$this->assertEquals(implode("\r\n", $expected), $result['header']);
		$this->assertEquals($content, $result['content']);
	}

/**
 * Test send() + context options with array content.
 *
 * @return void
 */
	public function testSendContextContentArray() {
		$request = new Request();
		$request->url('http://localhost')
			->header([
				'Content-Type' => 'application/json'
			])
			->content(['a' => 'my value']);

		$this->stream->send($request, []);
		$result = $this->stream->contextOptions();
		$expected = [
			'Connection: close',
			'User-Agent: CakePHP',
			'Content-Type: multipart/form-data; boundary="',
		];
		$this->assertStringStartsWith(implode("\r\n", $expected), $result['header']);
		$this->assertContains('Content-Disposition: form-data; name="a"', $result['content']);
		$this->assertContains('my value', $result['content']);
	}

}
