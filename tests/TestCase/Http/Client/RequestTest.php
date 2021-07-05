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
namespace Cake\Test\TestCase\Http\Client;

use Cake\Http\Client\Request;
use Cake\TestSuite\TestCase;

/**
 * HTTP request test.
 */
class RequestTest extends TestCase
{
    /**
     * test string ata, header and constructor
     */
    public function testConstructorStringData(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer valid-token',
        ];
        $data = ['a' => 'b', 'c' => 'd'];
        $request = new Request('http://example.com', 'POST', $headers, json_encode($data));

        $this->assertSame('http://example.com', (string)$request->getUri());
        $this->assertStringContainsString($request->getMethod(), 'POST');
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame(json_encode($data), $request->getBody()->__toString());
    }

    /**
     * @param array $headers The HTTP headers to set.
     * @param array|string|null $data The request body to use.
     * @param string $method The HTTP method to use.
     * @dataProvider additionProvider
     */
    public function testMethods(array $headers, $data, $method): void
    {
        $request = new Request('http://example.com', $method, $headers, json_encode($data));

        $this->assertSame($request->getMethod(), $method);
        $this->assertSame('http://example.com', (string)$request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame(json_encode($data), $request->getBody()->__toString());
    }

    /**
     * @dataProvider additionProvider
     */
    public function additionProvider(): array
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
     */
    public function testConstructorArrayData(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer valid-token',
        ];
        $data = ['a' => 'b', 'c' => 'd'];
        $request = new Request('http://example.com', 'POST', $headers, $data);

        $this->assertSame('http://example.com', (string)$request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame('a=b&c=d', $request->getBody()->__toString());
    }

    /**
     * test nested array data for encoding of brackets, header and constructor
     */
    public function testConstructorArrayNestedData(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer valid-token',
        ];
        $data = ['a' => 'b', 'c' => ['foo', 'bar']];
        $request = new Request('http://example.com', 'POST', $headers, $data);

        $this->assertSame('http://example.com', (string)$request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame('a=b&c%5B0%5D=foo&c%5B1%5D=bar', $request->getBody()->__toString());
    }

    /**
     * test body method.
     */
    public function testBody(): void
    {
        $data = '{"json":"data"}';
        $request = new Request('', Request::METHOD_GET, [], $data);

        $this->assertSame($data, $request->getBody()->__toString());
    }

    /**
     * test body method with array payload
     */
    public function testBodyArray(): void
    {
        $data = [
            'a' => 'b',
            'c' => 'd',
            'e' => ['f', 'g'],
        ];
        $request = new Request('', Request::METHOD_GET, [], $data);
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('content-type'));
        $this->assertSame(
            'a=b&c=d&e%5B0%5D=f&e%5B1%5D=g',
            $request->getBody()->__toString(),
            'Body should be serialized'
        );
    }

    /**
     * Test that body() modifies the PSR7 stream
     */
    public function testBodyInteroperability(): void
    {
        $request = new Request();
        $this->assertSame('', $request->getBody()->__toString());

        $data = '{"json":"data"}';
        $request = new Request('', Request::METHOD_GET, [], $data);
        $this->assertSame($data, $request->getBody()->__toString());
    }

    /**
     * Test the default headers
     */
    public function testDefaultHeaders(): void
    {
        $request = new Request();
        $this->assertSame('CakePHP', $request->getHeaderLine('User-Agent'));
        $this->assertSame('close', $request->getHeaderLine('Connection'));
    }
}
