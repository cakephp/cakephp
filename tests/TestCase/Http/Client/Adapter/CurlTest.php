<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.7.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Client\Adapter;

use Cake\Http\Client\Adapter\Curl;
use Cake\Http\Client\Exception\NetworkException;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;
use Composer\CaBundle\CaBundle;

/**
 * HTTP curl adapter test.
 */
class CurlTest extends TestCase
{
    /**
     * @var \Cake\Http\Client\Adapter\Curl
     */
    protected $curl;

    /**
     * @var string
     */
    protected $caFile;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIf(!function_exists('curl_init'), 'Skipping as ext/curl is not installed.');

        $this->curl = new Curl();
        $this->caFile = CaBundle::getBundledCaBundlePath();
    }

    /**
     * Test the send method
     *
     * @return void
     */
    public function testSendLive()
    {
        $request = new Request('http://localhost', 'GET', [
            'User-Agent' => 'CakePHP TestSuite',
            'Cookie' => 'testing=value',
        ]);
        try {
            $responses = $this->curl->send($request, []);
        } catch (NetworkException $e) {
            $this->markTestSkipped('Could not connect to localhost, skipping');
        }
        $this->assertCount(1, $responses);

        /** @var \Cake\Http\Response $response */
        $response = $responses[0];
        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotEmpty($response->getHeaders());
    }

    /**
     * Test the send method
     *
     * @return void
     */
    public function testSendLiveResponseCheck()
    {
        $request = new Request('https://api.cakephp.org/3.0/', 'GET', [
            'User-Agent' => 'CakePHP TestSuite',
        ]);
        try {
            $responses = $this->curl->send($request, []);
        } catch (NetworkException $e) {
            $this->markTestSkipped('Could not connect to api.cakephp.org, skipping');
        }
        $this->assertCount(1, $responses);

        /** @var \Cake\Http\Response $response */
        $response = $responses[0];
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->hasHeader('Date'));
        $this->assertTrue($response->hasHeader('Content-type'));
        $this->assertStringContainsString('<html', $response->getBody()->getContents());
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsGet()
    {
        $options = [
            'timeout' => 5,
        ];
        $request = new Request(
            'http://localhost/things',
            'GET',
            ['Cookie' => 'testing=value']
        );
        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Cookie: testing=value',
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_HTTPGET => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CAINFO => $this->caFile,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsGetWithBody()
    {
        $options = [
            'timeout' => 5,
        ];
        $request = new Request(
            'http://localhost/things',
            'GET',
            ['Cookie' => 'testing=value'],
            '{"some":"body"}'
        );
        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Cookie: testing=value',
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_HTTPGET => true,
            CURLOPT_POSTFIELDS => '{"some":"body"}',
            CURLOPT_CUSTOMREQUEST => 'get',
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CAINFO => $this->caFile,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsPost()
    {
        $options = [];
        $request = new Request(
            'http://localhost/things',
            'POST',
            ['Cookie' => 'testing=value'],
            ['name' => 'cakephp', 'yes' => 1]
        );
        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Cookie: testing=value',
                'Connection: close',
                'User-Agent: CakePHP',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'name=cakephp&yes=1',
            CURLOPT_CAINFO => $this->caFile,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsPut()
    {
        $options = [];
        $request = new Request(
            'http://localhost/things',
            'PUT',
            ['Cookie' => 'testing=value']
        );
        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Cookie: testing=value',
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_POST => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_CAINFO => $this->caFile,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsJsonPost()
    {
        $options = [];
        $content = json_encode(['a' => 1, 'b' => 2]);
        $request = new Request(
            'http://localhost/things',
            'POST',
            ['Content-type' => 'application/json'],
            $content
        );
        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Content-type: application/json',
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $content,
            CURLOPT_CAINFO => $this->caFile,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsSsl()
    {
        $options = [
            'ssl_verify_host' => true,
            'ssl_verify_peer' => true,
            'ssl_verify_peer_name' => true,
            // These options do nothing in curl.
            'ssl_verify_depth' => 9000,
            'ssl_allow_self_signed' => false,
        ];
        $request = new Request('http://localhost/things', 'GET');
        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_HTTPGET => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO => $this->caFile,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsProxy()
    {
        $options = [
            'proxy' => [
                'proxy' => '127.0.0.1:8080',
                'username' => 'frodo',
                'password' => 'one_ring',
            ],
        ];
        $request = new Request('http://localhost/things', 'GET');
        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_HTTPGET => true,
            CURLOPT_CAINFO => $this->caFile,
            CURLOPT_PROXY => '127.0.0.1:8080',
            CURLOPT_PROXYUSERPWD => 'frodo:one_ring',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsHead()
    {
        $options = [];
        $request = new Request(
            'http://localhost/things',
            'HEAD',
            ['Cookie' => 'testing=value']
        );
        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Cookie: testing=value',
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_NOBODY => true,
            CURLOPT_CAINFO => $this->caFile,
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsCurlOptions()
    {
        $options = [
            'curl' => [
                CURLOPT_USERAGENT => 'Super-secret',
            ],
        ];
        $request = new Request('http://localhost/things', 'GET');
        $request = $request->withProtocolVersion('1.0');

        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_HTTPGET => true,
            CURLOPT_CAINFO => $this->caFile,
            CURLOPT_USERAGENT => 'Super-secret',
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test that an exception is raised when timed out.
     *
     * @return void
     */
    public function testNetworkException()
    {
        $this->expectException(NetworkException::class);
        $this->expectExceptionMessageMatches('/(Could not resolve|Resolving timed out)/');

        $request = new Request('http://dummy/?sleep');
        $options = [
            'timeout' => 2,
        ];

        $this->curl->send($request, $options);
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsProtocolVersion()
    {
        $this->skipIf(!defined('CURL_HTTP_VERSION_2TLS'), 'Requires libcurl 7.42');
        $options = [];
        $request = new Request('http://localhost/things', 'GET');
        $request = $request->withProtocolVersion('2');

        $result = $this->curl->buildOptions($request, $options);
        $this->assertSame(CURL_HTTP_VERSION_2TLS, $result[CURLOPT_HTTP_VERSION]);
    }
}
