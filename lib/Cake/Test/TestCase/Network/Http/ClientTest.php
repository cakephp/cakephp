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

use Cake\Network\Http\Client;
use Cake\Network\Http\Request;
use Cake\Network\Http\Response;
use Cake\TestSuite\TestCase;

/**
 * HTTP client test.
 */
class ClientTest extends TestCase {

/**
 * Test storing config options and modifying them.
 *
 * @return void
 */
	public function testConstructConfig() {
		$config = [
			'scheme' => 'http',
			'host' => 'example.org',
		];
		$http = new Client($config);
		$this->assertEquals($config, $http->config());

		$result = $http->config([
			'auth' => ['username' => 'mark', 'password' => 'secret']
		]);
		$this->assertSame($result, $http);

		$result = $http->config();
		$expected = [
			'scheme' => 'http',
			'host' => 'example.org',
			'auth' => ['username' => 'mark', 'password' => 'secret']
		];
		$this->assertEquals($expected, $result);
	}

	public static function urlProvider() {
		return [
			[
				// simple
				'http://example.com/test.html',
				'http://example.com/test.html',
				null
			],
			[
				// simple array opts
				'http://example.com/test.html',
				'http://example.com/test.html',
				[]
			],
			[
				'http://example.com/test.html',
				'/test.html',
				['host' => 'example.com']
			],
			[
				'https://example.com/test.html',
				'/test.html',
				['host' => 'example.com', 'scheme' => 'https']
			],
			[
				'http://example.com:8080/test.html',
				'/test.html',
				['host' => 'example.com', 'port' => '8080']
			],
			[
				'http://example.com/test.html',
				'/test.html',
				['host' => 'example.com', 'port' => '80']
			],
			[
				'https://example.com/test.html',
				'/test.html',
				['host' => 'example.com', 'scheme' => 'https', 'port' => '443']
			],
		];
	}

	/**
	 * @dataProvider urlProvider
	 */
	public function testBuildUrl($expected, $url, $opts) {
		$http = new Client();

		$result = $http->buildUrl($url, $opts);
		$this->assertEquals($expected, $result);
	}

	public function testGet() {
		$response = new Response();

		$mock = $this->getMock('Cake\Network\Http\Adapter\Stream', ['send']);
		$mock->expects($this->once())
			->method('send')
			->with($this->logicalAnd(
				$this->isInstanceOf('Cake\Network\Http\Request')
			))
			->will($this->returnValue($response));

		$http = new Client([
			'host' => 'cakephp.org',
			'adapter' => $mock
		]);
		$result = $http->get('/test.html');
		$this->assertSame($result, $response);
	}

}
