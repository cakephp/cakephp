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
class RequestTest extends TestCase
{

    /**
     * test url method
     *
     * @return void
     */
    public function testUrl()
    {
        $request = new Request();
        $this->assertSame($request, $request->url('http://example.com'));

        $this->assertEquals('http://example.com', $request->url());
    }

    /**
     * test method method.
     *
     * @return void
     */
    public function testMethod()
    {
        $request = new Request();
        $this->assertSame($request, $request->method(Request::METHOD_GET));

        $this->assertEquals(Request::METHOD_GET, $request->method());
    }

    /**
     * test method interop.
     *
     * @return void
     */
    public function testMethodInteroperability()
    {
        $request = new Request();
        $this->assertSame($request, $request->method(Request::METHOD_GET));
        $this->assertEquals(Request::METHOD_GET, $request->method());
        $this->assertEquals(Request::METHOD_GET, $request->getMethod());

        $request = $request->withMethod(Request::METHOD_GET);
        $this->assertEquals(Request::METHOD_GET, $request->method());
        $this->assertEquals(Request::METHOD_GET, $request->getMethod());
    }

    /**
     * test invalid method.
     *
     * @expectedException \Cake\Core\Exception\Exception
     * @return void
     */
    public function testMethodInvalid()
    {
        $request = new Request();
        $request->method('set on fire');
    }

    /**
     * test body method.
     *
     * @return void
     */
    public function testBody()
    {
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
    public function testHeader()
    {
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
     * Test the default headers
     *
     * @return void
     */
    public function testDefaultHeaders()
    {
        $request = new Request();
        $this->assertEquals('CakePHP', $request->getHeaderLine('User-Agent'));
        $this->assertEquals('close', $request->getHeaderLine('Connection'));
    }

    /**
     * Test that header() and PSR7 methods play nice.
     *
     * @return void
     */
    public function testHeaderMethodInteroperability()
    {
        $request = new Request();
        $request->header('Content-Type', 'application/json');
        $this->assertEquals('application/json', $request->header('Content-Type'), 'Old getter should work');

        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'), 'getHeaderLine works');
        $this->assertEquals('application/json', $request->getHeaderLine('content-type'), 'getHeaderLine works');
        $this->assertEquals(['application/json'], $request->getHeader('Content-Type'), 'getHeader works');
        $this->assertEquals(['application/json'], $request->getHeader('content-type'), 'getHeader works');
    }

    /**
     * test cookie method.
     *
     * @return void
     */
    public function testCookie()
    {
        $request = new Request();
        $result = $request->cookie('session', '123456');
        $this->assertSame($result, $request, 'Should return self');

        $this->assertNull($request->cookie('not set'));

        $result = $request->cookie('session');
        $this->assertEquals('123456', $result);
    }

    /**
     * test version method.
     *
     * @return void
     */
    public function testVersion()
    {
        $request = new Request();
        $result = $request->version('1.0');
        $this->assertSame($request, $request, 'Should return self');

        $this->assertSame('1.0', $request->version());
    }

    /**
     * test version interop.
     *
     * @return void
     */
    public function testVersionInteroperability()
    {
        $request = new Request();
        $this->assertEquals('1.1', $request->version());
        $this->assertEquals('1.1', $request->getProtocolVersion());

        $request = $request->withProtocolVersion('1.0');
        $this->assertEquals('1.0', $request->version());
        $this->assertEquals('1.0', $request->getProtocolVersion());
    }
}
