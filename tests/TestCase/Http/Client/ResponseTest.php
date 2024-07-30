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

use Cake\Http\Client\Response;
use Cake\Http\Cookie\CookieCollection;
use Cake\TestSuite\TestCase;
use Iterator;

/**
 * HTTP response test.
 */
class ResponseTest extends TestCase
{
    /**
     * Test parsing headers and reading with PSR7 methods.
     */
    public function testHeaderParsingPsr7(): void
    {
        $headers = [
            'HTTP/1.0 200 OK',
            'Content-Type : text/html;charset="UTF-8"',
            'date: Tue, 25 Dec 2012 04:43:47 GMT',
        ];
        $response = new Response($headers, 'winner!');

        $this->assertSame('1.0', $response->getProtocolVersion());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame(
            'text/html;charset="UTF-8"',
            $response->getHeaderLine('content-type')
        );
        $this->assertSame(
            'Tue, 25 Dec 2012 04:43:47 GMT',
            $response->getHeaderLine('Date')
        );
        $this->assertSame('winner!', '' . $response->getBody());
    }

    /**
     * Test parsing headers and capturing content
     */
    public function testHeaderParsing(): void
    {
        $headers = [
            'HTTP/1.0 200 OK',
            'Content-Type : text/html;charset="UTF-8"',
            'date: Tue, 25 Dec 2012 04:43:47 GMT',
        ];
        $response = new Response($headers, 'ok');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('1.0', $response->getProtocolVersion());
        $this->assertSame(
            'text/html;charset="UTF-8"',
            $response->getHeaderLine('content-type')
        );
        $this->assertSame(
            'Tue, 25 Dec 2012 04:43:47 GMT',
            $response->getHeaderLine('Date')
        );

        $this->assertSame(
            'text/html;charset="UTF-8"',
            $response->getHeaderLine('Content-Type')
        );

        $headers = [
            'HTTP/1.0 200',
        ];
        $response = new Response($headers, 'ok');

        $this->assertSame('1.0', $response->getProtocolVersion());
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Test getStringBody()
     */
    public function getStringBody(): void
    {
        $response = new Response([], 'string');

        $this->assertSame('string', $response->getStringBody());
    }

    /**
     * Test accessor for JSON
     */
    public function testBodyJson(): void
    {
        $data = [
            'property' => 'value',
        ];
        $encoded = json_encode($data);
        $response = new Response([], $encoded);
        $this->assertSame($data['property'], $response->getJson()['property']);

        $data = '';
        $response = new Response([], $data);
        $this->assertNull($response->getJson());

        $data = json_encode([]);
        $response = new Response([], $data);
        $this->assertIsArray($response->getJson());

        $data = json_encode(null);
        $response = new Response([], $data);
        $this->assertNull($response->getJson());

        $data = json_encode(false);
        $response = new Response([], $data);
        $this->assertFalse($response->getJson());

        $data = json_encode('');
        $response = new Response([], $data);
        $this->assertSame('', $response->getJson());
    }

    /**
     * Test accessor for JSON when set with PSR7 methods.
     */
    public function testBodyJsonPsr7(): void
    {
        $data = [
            'property' => 'value',
        ];
        $encoded = json_encode($data);
        $response = new Response([], '');
        $response->getBody()->write($encoded);
        $this->assertSame($data, $response->getJson());
    }

    /**
     * Test accessor for XML
     */
    public function testBodyXml(): void
    {
        $data = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
	<test>Test</test>
</root>
XML;
        $response = new Response([], $data);
        $this->assertSame('Test', (string)$response->getXml()->test);

        $data = '';
        $response = new Response([], $data);
        $this->assertNull($response->getXml());
    }

    /**
     * Test isOk()
     */
    public function testIsOk(): void
    {
        $headers = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, 'ok');
        $this->assertTrue($response->isOk());

        $headers = [
            'HTTP/1.1 201 Created',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, 'ok');
        $this->assertTrue($response->isOk());

        $headers = [
            'HTTP/1.1 202 Accepted',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, 'ok');
        $this->assertTrue($response->isOk());

        $headers = [
            'HTTP/1.1 203 Non-Authoritative Information',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, 'ok');
        $this->assertTrue($response->isOk());

        $headers = [
            'HTTP/1.1 204 No Content',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, 'ok');
        $this->assertTrue($response->isOk());

        $headers = [
            'HTTP/1.1 301 Moved Permanently',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, '');
        $this->assertTrue($response->isOk());

        $headers = [
            'HTTP/1.0 404 Not Found',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, '');
        $this->assertFalse($response->isOk());
    }

    /**
     * provider for isSuccess.
     *
     * @return array
     */
    public static function isSuccessProvider(): Iterator
    {
        yield [
            true,
            new Response([
                'HTTP/1.1 200 OK',
                'Content-Type: text/html',
            ], 'ok'),
        ];
        yield [
            true,
            new Response([
                'HTTP/1.1 201 Created',
                'Content-Type: text/html',
            ], 'ok'),
        ];
        yield [
            true,
            new Response([
                'HTTP/1.1 202 Accepted',
                'Content-Type: text/html',
            ], 'ok'),
        ];
        yield [
            true,
            new Response([
                'HTTP/1.1 203 Non-Authoritative Information',
                'Content-Type: text/html',
            ], 'ok'),
        ];
        yield [
            true,
            new Response([
                'HTTP/1.1 204 No Content',
                'Content-Type: text/html',
            ], ''),
        ];
        yield [
            false,
            new Response([
                'HTTP/1.1 301 Moved Permanently',
                'Content-Type: text/html',
            ], ''),
        ];
        yield [
            false,
            new Response([
                'HTTP/1.0 404 Not Found',
                'Content-Type: text/html',
            ], ''),
        ];
    }

    /**
     * Test isSuccess()
     *
     * @dataProvider isSuccessProvider
     */
    public function testIsSuccess(bool $expected, Response $response): void
    {
        $this->assertEquals($expected, $response->isSuccess());
    }

    /**
     * Test isRedirect()
     */
    public function testIsRedirect(): void
    {
        $headers = [
            'HTTP/1.1 200 OK',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, 'ok');
        $this->assertFalse($response->isRedirect());

        $headers = [
            'HTTP/1.1 301 Moved Permanently',
            'Location: /',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, '');
        $this->assertTrue($response->isRedirect());

        $headers = [
            'HTTP/1.0 404 Not Found',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, '');
        $this->assertFalse($response->isRedirect());
    }

    /**
     * Test accessing cookies through the PSR7-like methods
     */
    public function testGetCookies(): void
    {
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: test=value',
            'Set-Cookie: session=123abc',
            'Set-Cookie: expiring=soon; Expires=Wed, 09-Jun-2021 10:18:14 GMT; Path=/; HttpOnly; Secure;',
        ];
        $response = new Response($headers, '');

        $this->assertNull($response->getCookie('undef'));
        $this->assertSame('value', $response->getCookie('test'));
        $this->assertSame('soon', $response->getCookie('expiring'));

        $result = $response->getCookieData('expiring');
        $this->assertSame('soon', $result['value']);
        $this->assertTrue($result['httponly']);
        $this->assertTrue($result['secure']);
        $this->assertSame(
            strtotime('Wed, 09-Jun-2021 10:18:14 GMT'),
            $result['expires']
        );
        $this->assertSame('/', $result['path']);

        $result = $response->getCookies();
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('test', $result);
        $this->assertArrayHasKey('session', $result);
        $this->assertArrayHasKey('expiring', $result);
    }

    /**
     * Test accessing cookie collection
     */
    public function testGetCookieCollection(): void
    {
        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: test=value',
            'Set-Cookie: session=123abc',
            'Set-Cookie: expiring=soon; Expires=Wed, 09-Jun-2021 10:18:14 GMT; Path=/; HttpOnly; Secure;',
        ];
        $response = new Response($headers, '');

        $cookies = $response->getCookieCollection();
        $this->assertInstanceOf(CookieCollection::class, $cookies);
        $this->assertTrue($cookies->has('test'));
        $this->assertTrue($cookies->has('session'));
        $this->assertTrue($cookies->has('expiring'));
        $this->assertSame('123abc', $cookies->get('session')->getValue());
    }

    /**
     * Test statusCode()
     */
    public function testGetStatusCode(): void
    {
        $headers = [
            'HTTP/1.0 404 Not Found',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, '');
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * Test reading the encoding out.
     */
    public function testGetEncoding(): void
    {
        $headers = [
            'HTTP/1.0 200 Ok',
        ];
        $response = new Response($headers, '');
        $this->assertNull($response->getEncoding());

        $headers = [
            'HTTP/1.0 200 Ok',
            'Content-Type: text/html',
        ];
        $response = new Response($headers, '');
        $this->assertNull($response->getEncoding());

        $headers = [
            'HTTP/1.0 200 Ok',
            'Content-Type: text/html; charset="UTF-8"',
        ];
        $response = new Response($headers, '');
        $this->assertSame('UTF-8', $response->getEncoding());

        $headers = [
            'HTTP/1.0 200 Ok',
            "Content-Type: text/html; charset='ISO-8859-1'",
        ];
        $response = new Response($headers, '');
        $this->assertSame('ISO-8859-1', $response->getEncoding());
    }

    /**
     * Test that gzip responses are automatically decompressed.
     */
    public function testAutoDecodeGzipBody(): void
    {
        $headers = [
            'HTTP/1.0 200 OK',
            'Content-Encoding: gzip',
            'Content-Length: 32',
            'Content-Type: text/html; charset=UTF-8',
        ];
        $body = base64_decode('H4sIAAAAAAAAA/NIzcnJVyjPL8pJUQQAlRmFGwwAAAA=');
        $response = new Response($headers, $body);
        $this->assertSame('Hello world!', $response->getBody()->getContents());
    }
}
