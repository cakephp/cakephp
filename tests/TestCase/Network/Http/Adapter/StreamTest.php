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
namespace Cake\Test\TestCase\Network\Http\Adapter;

use Cake\Network\Exception\SocketException;
use Cake\Network\Http\Adapter\Stream;
use Cake\Network\Http\Request;
use Cake\Network\Http\Response;
use Cake\TestSuite\TestCase;

/**
 * HTTP stream adapter test.
 */
class StreamTest extends TestCase
{

    public function setUp()
    {
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
        $this->assertInstanceOf('Cake\Network\Http\Response', $responses[0]);
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
            'Content-Type: multipart/form-data; boundary="',
        ];
        $this->assertStringStartsWith(implode("\r\n", $expected), $result['header']);
        $this->assertContains('Content-Disposition: form-data; name="a"', $result['content']);
        $this->assertContains('my value', $result['content']);
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
            'ssl_verify_depth' => 9000,
            'ssl_allow_self_signed' => false,
        ];

        $this->stream->send($request, $options);
        $result = $this->stream->contextOptions();
        $expected = [
            'peer_name' => 'localhost.com',
            'verify_peer' => true,
            'verify_depth' => 9000,
            'allow_self_signed' => false,
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
}
