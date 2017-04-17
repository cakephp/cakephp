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
namespace Cake\Test\TestCase\Http\Client\Adapter;

use Cake\Http\Client\Adapter\Stream;
use Cake\Http\Client\Request;
use Cake\TestSuite\TestCase;

/**
 * CakeStreamWrapper class
 */
class CakeStreamWrapper implements \ArrayAccess
{

    private $_stream;

    private $_query = [];

    private $_data = [
        'headers' => [
            'HTTP/1.1 200 OK',
        ],
    ];

    public function stream_open($path, $mode, $options, &$openedPath)
    {
        $query = parse_url($path, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $this->_query);
        }

        $this->_stream = fopen('php://memory', 'rb+');
        fwrite($this->_stream, str_repeat('x', 20000));
        rewind($this->_stream);

        return true;
    }

    public function stream_close()
    {
        return fclose($this->_stream);
    }

    public function stream_read($count)
    {
        if (isset($this->_query['sleep'])) {
            sleep(1);
        }

        return fread($this->_stream, $count);
    }

    public function stream_eof()
    {
        return feof($this->_stream);
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
        return false;
    }

    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->_data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }
}

/**
 * HTTP stream adapter test.
 */
class StreamTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->stream = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['_send'])
            ->getMock();
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', __NAMESPACE__ . '\CakeStreamWrapper');
    }

    public function tearDown()
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
        $request = new Request();
        $request->url('http://localhost')
            ->header('User-Agent', 'CakePHP TestSuite')
            ->cookie('testing', 'value');

        try {
            $responses = $stream->send($request, []);
        } catch (\Cake\Core\Exception\Exception $e) {
            $this->markTestSkipped('Could not connect to localhost, skipping');
        }
        $this->assertInstanceOf('Cake\Http\Client\Response', $responses[0]);
    }

    /**
     * Test the send method by using cakephp:// protocol.
     *
     * @return void
     */
    public function testSendByUsingCakephpProtocol()
    {
        $stream = new Stream();
        $request = new Request();
        $request->url('http://dummy/');

        $responses = $stream->send($request, []);
        $this->assertInstanceOf('Cake\Http\Client\Response', $responses[0]);

        $this->assertEquals(20000, strlen($responses[0]->body()));
    }

    /**
     * Test building the context headers
     *
     * @return void
     */
    public function testBuildingContextHeader()
    {
        $request = new Request();
        $request->url('http://localhost')
            ->header([
                'User-Agent' => 'CakePHP TestSuite',
                'Content-Type' => 'application/json',
                'Cookie' => 'a=b; c=do%20it'
            ]);

        $options = [
            'redirect' => 20,
        ];
        $this->stream->send($request, $options);
        $result = $this->stream->contextOptions();
        $expected = [
            'Connection: close',
            'User-Agent: CakePHP TestSuite',
            'Content-Type: application/json',
            'Cookie: a=b; c=do%20it',
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
    public function testSendContextContentString()
    {
        $content = json_encode(['a' => 'b']);
        $request = new Request();
        $request->url('http://localhost')
            ->header([
                'Content-Type' => 'application/json'
            ])
            ->body($content);

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
    public function testSendContextContentArray()
    {
        $request = new Request();
        $request->url('http://localhost')
            ->header([
                'Content-Type' => 'application/json'
            ])
            ->body(['a' => 'my value']);

        $this->stream->send($request, []);
        $result = $this->stream->contextOptions();
        $expected = [
            'Connection: close',
            'User-Agent: CakePHP',
            'Content-Type: application/x-www-form-urlencoded',
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
        $request = new Request();
        $request->url('http://localhost')
            ->header([
                'Content-Type' => 'application/json'
            ])
            ->body(['upload' => fopen(CORE_PATH . 'VERSION.txt', 'r')]);

        $this->stream->send($request, []);
        $result = $this->stream->contextOptions();
        $expected = [
            'Connection: close',
            'User-Agent: CakePHP',
            'Content-Type: multipart/form-data',
        ];
        $this->assertStringStartsWith(implode("\r\n", $expected), $result['header']);
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
        $request = new Request();
        $request->url('https://localhost.com/test.html');
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
        $this->assertTrue(is_readable($result['cafile']));
    }

    /**
     * Test send() + context options for SSL.
     *
     * @return void
     */
    public function testSendContextSslNoVerifyPeerName()
    {
        $request = new Request();
        $request->url('https://localhost.com/test.html');
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
        $this->assertTrue(is_readable($result['cafile']));
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

        $responses = $this->stream->createResponses($headers, $content);
        $this->assertCount(3, $responses);
        $this->assertEquals('close', $responses[0]->header('Connection'));
        $this->assertEquals('', $responses[0]->body());
        $this->assertEquals('', $responses[1]->body());
        $this->assertEquals($content, $responses[2]->body());

        $this->assertEquals(302, $responses[0]->statusCode());
        $this->assertEquals(302, $responses[1]->statusCode());
        $this->assertEquals(200, $responses[2]->statusCode());

        $this->assertEquals('value', $responses[0]->cookie('first'));
        $this->assertEquals(null, $responses[0]->cookie('second'));
        $this->assertEquals(null, $responses[0]->cookie('third'));

        $this->assertEquals(null, $responses[1]->cookie('first'));
        $this->assertEquals('val', $responses[1]->cookie('second'));
        $this->assertEquals(null, $responses[1]->cookie('third'));

        $this->assertEquals(null, $responses[2]->cookie('first'));
        $this->assertEquals(null, $responses[2]->cookie('second'));
        $this->assertEquals('works', $responses[2]->cookie('third'));
    }

    /**
     * Test that no exception is radied when not timed out.
     *
     * @return void
     */
    public function testKeepDeadline()
    {
        $request = new Request();
        $request->url('http://dummy/?sleep');
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
     * @expectedException \Cake\Core\Exception\Exception
     * @expectedExceptionMessage Connection timed out http://dummy/?sleep
     * @return void
     */
    public function testMissDeadline()
    {
        $request = new Request();
        $request->url('http://dummy/?sleep');
        $options = [
            'timeout' => 2,
        ];

        $stream = new Stream();
        $stream->send($request, $options);
    }
}
