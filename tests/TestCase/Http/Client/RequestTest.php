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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Client;

use Cake\Http\Client\Request;
use Cake\TestSuite\TestCase;
use Zend\Diactoros\Uri;

/**
 * HTTP request test.
 */
class RequestTest extends TestCase
{

    /**
     * test string ata, header and constructor
     *
     * @return void
     */
    public function testConstructorStringData()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer valid-token',
        ];
        $data = ['a' => 'b', 'c' => 'd'];
        $request = new Request('http://example.com', 'POST', $headers, json_encode($data));

        $this->assertEquals('http://example.com', (string)$request->getUri());
        $this->assertContains($request->getMethod(), 'POST');
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals(json_encode($data), $request->body());
    }
    /**
     * @param array $headers The HTTP headers to set.
     * @param array|string|null $data The request body to use.
     * @param string $method The HTTP method to use.
     *
     * @dataProvider additionProvider
     */
    public function testMethods(array $headers, $data, $method)
    {
        $request = new Request('http://example.com', $method, $headers, json_encode($data));

        $this->assertEquals($request->getMethod(), $method);
        $this->assertEquals('http://example.com', (string)$request->getUri());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals(json_encode($data), $request->body());
    }
    /**
     * @dataProvider additionProvider
     */
    public function additionProvider()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer valid-token',
        ];
        $data = ['a' => 'b', 'c' => 'd'];

        return [
            [$headers, $data, Request::METHOD_POST],
            [$headers, $data, Request::METHOD_GET],
            [$headers, $data, Request::METHOD_PUT],
            [$headers, $data, Request::METHOD_DELETE],
        ];
    }

    /**
     * test array data, header and constructor
     *
     * @return void
     */
    public function testConstructorArrayData()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer valid-token',
        ];
        $data = ['a' => 'b', 'c' => 'd'];
        $request = new Request('http://example.com', 'POST', $headers, $data);

        $this->assertEquals('http://example.com', (string)$request->getUri());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('a=b&c=d', $request->body());
    }

    /**
     * test nested array data for encoding of brackets, header and constructor
     *
     * @return void
     */
    public function testConstructorArrayNestedData()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer valid-token',
        ];
        $data = ['a' => 'b', 'c' => ['foo', 'bar']];
        $request = new Request('http://example.com', 'POST', $headers, $data);

        $this->assertEquals('http://example.com', (string)$request->getUri());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('a=b&c%5B0%5D=foo&c%5B1%5D=bar', $request->body());
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
     * test body method with array payload
     *
     * @return void
     */
    public function testBodyArray()
    {
        $request = new Request();
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => ['f', 'g']
        ];
        $request->body($data);
        $this->assertEquals('application/x-www-form-urlencoded', $request->getHeaderLine('content-type'));
        $this->assertEquals(
            'a=b&c=d&e%5B0%5D=f&e%5B1%5D=g',
            $request->body(),
            'Body should be serialized'
        );
    }

    /**
     * Test that body() modifies the PSR7 stream
     *
     * @return void
     */
    public function testBodyInteroperability()
    {
        $request = new Request();
        $this->assertSame('', $request->body());

        $data = '{"json":"data"}';
        $request = new Request();
        $request->body($data);
        $this->assertSame($data, $request->body());
        $this->assertSame($data, '' . $request->getBody());
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
}
