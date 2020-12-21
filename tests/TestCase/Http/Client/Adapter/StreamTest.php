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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Client\Adapter;

use Cake\Http\Client\Adapter\Stream;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;
use Cake\TestSuite\TestCase;
use TestApp\Http\Client\Adapter\CakeStreamWrapper;

/**
 * HTTP stream adapter test.
 */
class StreamTest extends TestCase
{
    /**
     * @var \Cake\Http\Client\Adapter\Stream|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $stream;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->stream = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['_send'])
            ->getMock();
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', CakeStreamWrapper::class);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        stream_wrapper_restore('http');
    }

    /**
     * Test the send method
     *
     * @return void
     */
    public function testSend()
    {
        $stream = new Stream();
        $request = new Request('http://localhost', 'GET', [
            'User-Agent' => 'CakePHP TestSuite',
            'Cookie' => 'testing=value',
        ]);

        try {
            $responses = $stream->send($request, []);
        } catch (\Cake\Core\Exception\CakeException $e) {
            $this->markTestSkipped('Could not connect to localhost, skipping');
        }
        $this->assertInstanceOf(Response::class, $responses[0]);
    }

    /**
     * Test the send method by using cakephp:// protocol.
     *
     * @return void
     */
    public function testSendByUsingCakephpProtocol()
    {
        $stream = new Stream();
        $request = new Request('http://dummy/');

        /** @var \Cake\Http\Client\Response[] $responses */
        $responses = $stream->send($request, []);
        $this->assertInstanceOf(Response::class, $responses[0]);

        $this->assertSame(20000, strlen($responses[0]->getStringBody()));
    }

    /**
     * Test stream error_handler cleanup when wrapper throws exception
     *
     * @return void
     */
    public function testSendWrapperException()
    {
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
        $request = new Request(
            'http://localhost',
            'GET',
            [
                'User-Agent' => 'CakePHP TestSuite',
                'Content-Type' => 'application/json',
                'Cookie' => 'a=b; c=do%20it',
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
        $this->assertSame(implode("\r\n", $expected), $result['header']);
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
        $content = json_encode(['a' => 'b']);
        $request = new Request(
            'http://localhost',
            'GET',
            ['Content-Type' => 'application/json'],
            $content
        );

        $options = [
            'redirect' => 20,
        ];
        $this->stream->send($request, $options);
        $result = $this->stream->contextOptions();
        $expected = [
            'Content-Type: application/json',
            'Connection: close',
            'User-Agent: CakePHP',
        ];
        $this->assertSame(implode("\r\n", $expected), $result['header']);
        $this->assertEquals($content, $result['content']);
    }

    /**
     * Test send() + context options with array content.
     *
     * @return void
     */
    public function testSendContextContentArray()
    {
        $request = new Request(
            'http://localhost',
            'GET',
            [
                'Content-Type' => 'application/json',
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
        $this->assertStringContainsString('a=my+value', $result['content']);
        $this->assertStringContainsString('my+value', $result['content']);
    }

    /**
     * Test send() + context options with array content.
     *
     * @return void
     */
    public function testSendContextContentArrayFiles()
    {
        $request = new Request(
            'http://localhost',
            'GET',
            ['Content-Type' => 'application/json'],
            ['upload' => fopen(CORE_PATH . 'VERSION.txt', 'r')]
        );

        $this->stream->send($request, []);
        $result = $this->stream->contextOptions();
        $this->assertStringContainsString('Content-Type: multipart/form-data', $result['header']);
        $this->assertStringContainsString("Connection: close\r\n", $result['header']);
        $this->assertStringContainsString('User-Agent: CakePHP', $result['header']);
        $this->assertStringContainsString('name="upload"', $result['content']);
        $this->assertStringContainsString('filename="VERSION.txt"', $result['content']);
    }

    /**
     * Test send() + context options for SSL.
     *
     * @return void
     */
    public function testSendContextSsl()
    {
        $request = new Request('https://localhost.com/test.html');
        $options = [
            'ssl_verify_host' => true,
            'ssl_verify_peer' => true,
            'ssl_verify_peer_name' => true,
            'ssl_verify_depth' => 9000,
            'ssl_allow_self_signed' => false,
            'proxy' => [
                'proxy' => '127.0.0.1:8080',
            ],
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
        $request = new Request('https://localhost.com/test.html');
        $options = [
            'ssl_verify_host' => true,
            'ssl_verify_peer' => true,
            'ssl_verify_peer_name' => false,
            'ssl_verify_depth' => 9000,
            'ssl_allow_self_signed' => false,
            'proxy' => [
                'proxy' => '127.0.0.1:8080',
            ],
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

        /** @var \Cake\Http\Client\Response[] $responses */
        $responses = $this->stream->createResponses($headers, $content);
        $this->assertCount(3, $responses);
        $this->assertSame('close', $responses[0]->getHeaderLine('Connection'));
        $this->assertSame('', (string)$responses[0]->getBody());
        $this->assertSame('', (string)$responses[1]->getBody());
        $this->assertSame($content, (string)$responses[2]->getBody());

        $this->assertSame(302, $responses[0]->getStatusCode());
        $this->assertSame(302, $responses[1]->getStatusCode());
        $this->assertSame(200, $responses[2]->getStatusCode());

        $this->assertSame('value', $responses[0]->getCookie('first'));
        $this->assertNull($responses[0]->getCookie('second'));
        $this->assertNull($responses[0]->getCookie('third'));

        $this->assertNull($responses[1]->getCookie('first'));
        $this->assertSame('val', $responses[1]->getCookie('second'));
        $this->assertNull($responses[1]->getCookie('third'));

        $this->assertNull($responses[2]->getCookie('first'));
        $this->assertNull($responses[2]->getCookie('second'));
        $this->assertSame('works', $responses[2]->getCookie('third'));
    }

    /**
     * Test that no exception is radied when not timed out.
     *
     * @return void
     */
    public function testKeepDeadline()
    {
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
        $request = new Request('http://dummy/?sleep');
        $options = [
            'timeout' => 2,
        ];

        $stream = new Stream();

        $this->expectException(\Cake\Http\Client\Exception\NetworkException::class);
        $this->expectExceptionMessage('Connection timed out http://dummy/?sleep');

        $stream->send($request, $options);
    }
}
