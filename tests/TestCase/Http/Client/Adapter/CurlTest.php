<?php
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
use Cake\Http\Client\Request;
use Cake\TestSuite\TestCase;

/**
 * HTTP curl adapter test.
 */
class CurlTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->curl = $this->getMockBuilder('Cake\Http\Client\Adapter\Curl')
            ->setMethods(['exec'])
            ->getMock();
    }

    /**
     * Test the send method
     *
     * @return void
     */
    public function testSend()
    {
        $this->markTestIncomplete();
        $request = new Request('http://localhost', 'GET', [
            'User-Agent' => 'CakePHP TestSuite',
            'Cookie' => 'testing=value'
        ]);

        try {
            $responses = $this->curl->send($request, []);
        } catch (\Cake\Core\Exception\Exception $e) {
            $this->markTestSkipped('Could not connect to localhost, skipping');
        }
        $this->assertInstanceOf('Cake\Http\Client\Response', $responses[0]);
    }

    /**
     * Test converting client options into curl ones.
     *
     * @return void
     */
    public function testBuildOptionsGet()
    {
        $options = [
            'timeout' => 5
        ];
        $request = new Request(
            'http://localhost/things',
            'GET',
            ['Cookie' => 'testing=value']
        );
        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => '1.1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Cookie: testing=value',
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_HTTPGET => true,
            CURLOPT_TIMEOUT => 5,
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
            CURLOPT_HTTP_VERSION => '1.1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Cookie: testing=value',
                'Connection: close',
                'User-Agent: CakePHP',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'name=cakephp&yes=1'
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
            CURLOPT_HTTP_VERSION => '1.1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Cookie: testing=value',
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_POST => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
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
            'ssl_verify_depth' => 9000,
            'ssl_allow_self_signed' => false,
        ];
        $request = new Request('http://localhost/things', 'GET');
        $result = $this->curl->buildOptions($request, $options);
        $expected = [
            CURLOPT_URL => 'http://localhost/things',
            CURLOPT_HTTP_VERSION => '1.1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Connection: close',
                'User-Agent: CakePHP',
            ],
            CURLOPT_HTTPGET => true,
            CURLOPT_SSL_VERIFYPEER => true
        ];
        $this->assertSame($expected, $result);
    }

    /**
     * Test the send method by using cakephp:// protocol.
     *
     * @return void
     */
    public function testSendByUsingCakephpProtocol()
    {
        $this->markTestIncomplete();
        $stream = new Stream();
        $request = new Request('http://dummy/');

        $responses = $stream->send($request, []);
        $this->assertInstanceOf('Cake\Http\Client\Response', $responses[0]);

        $this->assertEquals(20000, strlen($responses[0]->body()));
    }

    /**
     * Test stream error_handler cleanup when wrapper throws exception
     *
     * @return void
     */
    public function testSendWrapperException()
    {
        $this->markTestIncomplete();
        $stream = new Stream();
        $request = new Request('http://throw_exception/');

        $currentHandler = set_error_handler(function () {
        });
        restore_error_handler();

        try {
            $stream->send($request, []);
        } catch (\Exception $e) {
        }

        $newHandler = set_error_handler(function () {
        });
        restore_error_handler();

        $this->assertEquals($currentHandler, $newHandler);
    }

    /**
     * Test building the context headers
     *
     * @return void
     */
    public function testBuildingContextHeader()
    {
        $this->markTestIncomplete();
        $request = new Request(
            'http://localhost',
            'GET',
            [
                'User-Agent' => 'CakePHP TestSuite',
                'Content-Type' => 'application/json',
                'Cookie' => 'a=b; c=do%20it'
            ]
        );

        $options = [
            'redirect' => 20,
        ];
        $this->stream->send($request, $options);
        $result = $this->stream->contextOptions();
        $expected = [
            'User-Agent: CakePHP TestSuite',
            'Content-Type: application/json',
            'Cookie: a=b; c=do%20it',
            'Connection: close',
        ];
        $this->assertEquals(implode("\r\n", $expected), $result['header']);
        $this->assertSame(0, $result['max_redirects']);
        $this->assertTrue($result['ignore_errors']);
    }

    /**
     * Test send() + context options with string content.
     *
     * @return void
     */
    public function testSendContextContentString()
    {
        $this->markTestIncomplete();
        $content = json_encode(['a' => 'b']);
        $request = new Request(
            'http://localhost',
            'GET',
            ['Content-Type' => 'application/json'],
            $content
        );

        $options = [
            'redirect' => 20
        ];
        $this->stream->send($request, $options);
        $result = $this->stream->contextOptions();
        $expected = [
            'Content-Type: application/json',
            'Connection: close',
            'User-Agent: CakePHP',
        ];
        $this->assertEquals(implode("\r\n", $expected), $result['header']);
        $this->assertEquals($content, $result['content']);
    }

    /**
     * Test send() + context options with array content.
     *
     * @return void
     */
    public function testSendContextContentArray()
    {
        $this->markTestIncomplete();
        $request = new Request(
            'http://localhost',
            'GET',
            [
                'Content-Type' => 'application/json'
            ],
            ['a' => 'my value']
        );

        $this->stream->send($request, []);
        $result = $this->stream->contextOptions();
        $expected = [
            'Content-Type: application/x-www-form-urlencoded',
            'Connection: close',
            'User-Agent: CakePHP',
        ];
        $this->assertStringStartsWith(implode("\r\n", $expected), $result['header']);
        $this->assertContains('a=my+value', $result['content']);
        $this->assertContains('my+value', $result['content']);
    }

    /**
     * Test send() + context options with array content.
     *
     * @return void
     */
    public function testSendContextContentArrayFiles()
    {
        $this->markTestIncomplete();
        $request = new Request(
            'http://localhost',
            'GET',
            ['Content-Type' => 'application/json'],
            ['upload' => fopen(CORE_PATH . 'VERSION.txt', 'r')]
        );

        $this->stream->send($request, []);
        $result = $this->stream->contextOptions();
        $this->assertContains("Content-Type: multipart/form-data", $result['header']);
        $this->assertContains("Connection: close\r\n", $result['header']);
        $this->assertContains("User-Agent: CakePHP", $result['header']);
        $this->assertContains('name="upload"', $result['content']);
        $this->assertContains('filename="VERSION.txt"', $result['content']);
    }

    /**
     * Test send() + context options for SSL.
     *
     * @return void
     */
    public function testSendContextSsl()
    {
        $this->markTestIncomplete();
        $request = new Request('https://localhost.com/test.html');
        $options = [
            'ssl_verify_host' => true,
            'ssl_verify_peer' => true,
            'ssl_verify_peer_name' => true,
            'ssl_verify_depth' => 9000,
            'ssl_allow_self_signed' => false,
            'proxy' => [
                'proxy' => '127.0.0.1:8080'
            ]
        ];

        $this->stream->send($request, $options);
        $result = $this->stream->contextOptions();
        $expected = [
            'peer_name' => 'localhost.com',
            'verify_peer' => true,
            'verify_peer_name' => true,
            'verify_depth' => 9000,
            'allow_self_signed' => false,
            'proxy' => '127.0.0.1:8080',
        ];
        foreach ($expected as $k => $v) {
            $this->assertEquals($v, $result[$k]);
        }
        $this->assertIsReadable($result['cafile']);
    }

    /**
     * Test send() + context options for SSL.
     *
     * @return void
     */
    public function testSendContextSslNoVerifyPeerName()
    {
        $this->markTestIncomplete();
        $request = new Request('https://localhost.com/test.html');
        $options = [
            'ssl_verify_host' => true,
            'ssl_verify_peer' => true,
            'ssl_verify_peer_name' => false,
            'ssl_verify_depth' => 9000,
            'ssl_allow_self_signed' => false,
            'proxy' => [
                'proxy' => '127.0.0.1:8080'
            ]
        ];

        $this->stream->send($request, $options);
        $result = $this->stream->contextOptions();
        $expected = [
            'peer_name' => 'localhost.com',
            'verify_peer' => true,
            'verify_peer_name' => false,
            'verify_depth' => 9000,
            'allow_self_signed' => false,
            'proxy' => '127.0.0.1:8080',
        ];
        foreach ($expected as $k => $v) {
            $this->assertEquals($v, $result[$k]);
        }
        $this->assertIsReadable($result['cafile']);
    }

    /**
     * The PHP stream API returns ALL the headers for ALL the requests when
     * there are redirects.
     *
     * @return void
     */
    public function testCreateResponseWithRedirects()
    {
        $this->markTestIncomplete();
        $headers = [
            'HTTP/1.1 302 Found',
            'Date: Mon, 31 Dec 2012 16:53:16 GMT',
            'Server: Apache/2.2.22 (Unix) DAV/2 PHP/5.4.9 mod_ssl/2.2.22 OpenSSL/0.9.8r',
            'X-Powered-By: PHP/5.4.9',
            'Location: http://localhost/cake3/tasks/second',
            'Content-Length: 0',
            'Connection: close',
            'Content-Type: text/html; charset=UTF-8',
            'Set-Cookie: first=value',
            'HTTP/1.1 302 Found',
            'Date: Mon, 31 Dec 2012 16:53:16 GMT',
            'Server: Apache/2.2.22 (Unix) DAV/2 PHP/5.4.9 mod_ssl/2.2.22 OpenSSL/0.9.8r',
            'X-Powered-By: PHP/5.4.9',
            'Location: http://localhost/cake3/tasks/third',
            'Content-Length: 0',
            'Connection: close',
            'Content-Type: text/html; charset=UTF-8',
            'Set-Cookie: second=val',
            'HTTP/1.1 200 OK',
            'Date: Mon, 31 Dec 2012 16:53:16 GMT',
            'Server: Apache/2.2.22 (Unix) DAV/2 PHP/5.4.9 mod_ssl/2.2.22 OpenSSL/0.9.8r',
            'X-Powered-By: PHP/5.4.9',
            'Content-Length: 22',
            'Connection: close',
            'Content-Type: text/html; charset=UTF-8',
            'Set-Cookie: third=works',
        ];
        $content = 'This is the third page';

        $responses = $this->stream->createResponses($headers, $content);
        $this->assertCount(3, $responses);
        $this->assertEquals('close', $responses[0]->getHeaderLine('Connection'));
        $this->assertEquals('', (string)$responses[0]->getBody());
        $this->assertEquals('', (string)$responses[1]->getBody());
        $this->assertEquals($content, (string)$responses[2]->getBody());

        $this->assertEquals(302, $responses[0]->getStatusCode());
        $this->assertEquals(302, $responses[1]->getStatusCode());
        $this->assertEquals(200, $responses[2]->getStatusCode());

        $this->assertEquals('value', $responses[0]->getCookie('first'));
        $this->assertNull($responses[0]->getCookie('second'));
        $this->assertNull($responses[0]->getCookie('third'));

        $this->assertNull($responses[1]->getCookie('first'));
        $this->assertEquals('val', $responses[1]->getCookie('second'));
        $this->assertNull($responses[1]->getCookie('third'));

        $this->assertNull($responses[2]->getCookie('first'));
        $this->assertNull($responses[2]->getCookie('second'));
        $this->assertEquals('works', $responses[2]->getCookie('third'));
    }

    /**
     * Test that no exception is radied when not timed out.
     *
     * @return void
     */
    public function testKeepDeadline()
    {
        $this->markTestIncomplete();
        $request = new Request('http://dummy/?sleep');
        $options = [
            'timeout' => 5,
        ];

        $t = microtime(true);
        $stream = new Stream();
        $stream->send($request, $options);
        $this->assertLessThan(5, microtime(true) - $t);
    }

    /**
     * Test that an exception is raised when timed out.
     *
     * @return void
     */
    public function testMissDeadline()
    {
        $this->markTestIncomplete();
        $this->expectException(\Cake\Http\Exception\HttpException::class);
        $this->expectExceptionMessage('Connection timed out http://dummy/?sleep');
        $request = new Request('http://dummy/?sleep');
        $options = [
            'timeout' => 2,
        ];

        $stream = new Stream();
        $stream->send($request, $options);
    }
}
