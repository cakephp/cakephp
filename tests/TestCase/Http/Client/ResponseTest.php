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
use Cake\Http\Stream;
use Cake\TestSuite\TestCase;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

/**
 * HTTP response test.
 */
class ResponseTest extends TestCase
{
    /**
     * Cached JSON must survive a second read of a non-seekable body.
     *
     * @param string $json The encoded response body.
     * @param array|null $expected The decoded value.
     */
    #[DataProvider('cachedJsonProvider')]
    public function testJsonIsCachedForLiveBody(string $json, ?array $expected): void
    {
        $domain = PHP_OS_FAMILY === 'Windows' ? STREAM_PF_INET : STREAM_PF_UNIX;
        $pair = stream_socket_pair($domain, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $this->assertIsArray($pair);
        fwrite($pair[0], $json);
        fclose($pair[0]);
        $body = new Stream($pair[1]);
        try {
            $response = new Response([], $body);
            $this->assertSame($expected, $response->getJson());
            $this->assertSame($expected, $response->getJsonArray());
        } finally {
            $body->close();
        }
    }

    /**
     * @return array<string, array{string, array|null}>
     */
    public static function cachedJsonProvider(): array
    {
        return [
            'empty array' => ['[]', []],
            'null' => ['null', null],
            'empty object' => ['{}', []],
            'object' => ['{"id":42}', ['id' => 42]],
        ];
    }

    public function testWithBodyDiscardsDecodedValues(): void
    {
        $response = new Response([], '{"id":1}');
        $this->assertSame(['id' => 1], $response->getJson());
        $replacement = $response->withBody(new Stream('data://text/plain,{"id":2}'));

        $this->assertSame(['id' => 2], $replacement->getJson());
        $this->assertSame(['id' => 1], $response->getJson());

        $response = new Response([], '<item>first</item>');
        $this->assertSame('first', (string)$response->getXml());
        $replacement = $response->withBody(new Stream('data://text/plain,<item>second</item>'));
        $this->assertSame('second', (string)$replacement->getXml());
        $this->assertSame('first', (string)$response->getXml());
    }

    public function testGetJsonAsUsesDecoder(): void
    {
        $response = new Response([], '{"id":42}');
        $decoder = static fn(?array $data): int => $data['id'];

        $this->assertSame(42, $response->getJsonAs($decoder));
        $this->assertSame(['id' => 42], $response->getJson());
    }

    public function testParsedHeadersAreNormalizedCaseInsensitively(): void
    {
        $response = new Response(['X-Tag: first', 'x-tag: second']);

        $this->assertSame(['first', 'second'], $response->getHeader('X-Tag'));
        $this->assertSame(['x-tag' => ['first', 'second']], $response->getHeaders());
    }

    /**
     * Stream bodies are preserved and non-seekable string reads consume only remaining bytes.
     */
    public function testLiveBody(): void
    {
        $domain = PHP_OS_FAMILY === 'Windows' ? STREAM_PF_INET : STREAM_PF_UNIX;
        $pair = stream_socket_pair($domain, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $this->assertIsArray($pair);
        fwrite($pair[0], 'first second');
        fclose($pair[0]);
        $body = new Stream($pair[1]);
        $response = new Response(['HTTP/1.1 200 OK'], $body);

        $this->assertSame($body, $response->getBody());
        $this->assertFalse($body->isSeekable());
        $this->assertSame('first ', $body->read(6));
        $this->assertSame('second', $response->getStringBody());
        $this->assertSame('', $response->getStringBody());
        $body->close();
    }

    /**
     * A response built without a status line still has a usable reason phrase.
     *
     * `_parseHeaders()` only assigns the reason phrase when it encounters an
     * `HTTP/x.y NNN` status line, so without one the property stayed
     * uninitialized and `getReasonPhrase()` raised an Error despite its
     * `string` return type.
     */
    public function testGetReasonPhraseWithoutStatusLine(): void
    {
        $response = new Response([], 'Okay body.');

        $this->assertSame('', $response->getReasonPhrase());
    }

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
            $response->getHeaderLine('content-type'),
        );
        $this->assertSame(
            'Tue, 25 Dec 2012 04:43:47 GMT',
            $response->getHeaderLine('Date'),
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
            $response->getHeaderLine('content-type'),
        );
        $this->assertSame(
            'Tue, 25 Dec 2012 04:43:47 GMT',
            $response->getHeaderLine('Date'),
        );

        $this->assertSame(
            'text/html;charset="UTF-8"',
            $response->getHeaderLine('Content-Type'),
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
        $data = ['property' => 'value'];
        $response = new Response([], json_encode($data));
        $this->assertSame($data, $response->getJson());

        $response = new Response([], '[]');
        $this->assertSame([], $response->getJson());

        $response = new Response([], 'null');
        $this->assertNull($response->getJson());
    }

    /**
     * @param string $json Invalid JSON body.
     */
    #[DataProvider('invalidJsonProvider')]
    public function testInvalidJsonThrows(string $json): void
    {
        $response = new Response([], $json);
        $this->expectException(JsonException::class);
        $response->getJsonArray();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidJsonProvider(): array
    {
        return [
            'empty body' => [''],
            'malformed JSON' => ['invalid'],
            'truncated object' => ['{"id":'],
            'invalid UTF-8' => ["\"\xFF\""],
        ];
    }

    /**
     * @param string $json A scalar JSON value.
     */
    #[DataProvider('scalarJsonProvider')]
    public function testScalarJsonThrows(string $json): void
    {
        $response = new Response([], $json);
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('JSON response body must decode to an array or null.');
        $response->getJsonArray();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function scalarJsonProvider(): array
    {
        return [
            'false' => ['false'],
            'true' => ['true'],
            'zero' => ['0'],
            'integer' => ['42'],
            'float' => ['1.5'],
            'empty string' => ['""'],
            'string' => ['"value"'],
        ];
    }

    public function testGetJsonAsRejectsInvalidJsonBeforeCallingDecoder(): void
    {
        $response = new Response([], 'invalid');
        $decoder = function (): never {
            $this->fail('Decoder must not be called for invalid JSON.');
        };
        $this->expectException(JsonException::class);
        $response->getJsonAs($decoder);
    }

    #[DataProvider('scalarJsonProvider')]
    public function testGetJsonAcceptsScalars(string $json): void
    {
        $response = new Response([], $json);
        $this->assertSame(json_decode($json, true), $response->getJson());
        $this->assertSame(json_decode($json, true), $response->getJson());
    }

    #[DataProvider('invalidJsonProvider')]
    public function testGetJsonReturnsNullForInvalidJson(string $json): void
    {
        $response = new Response([], $json);
        $this->assertNull($response->getJson());
        $this->assertNull($response->getJson());
        $this->expectException(JsonException::class);
        $response->getJsonArray();
    }

    #[DataProvider('scalarJsonProvider')]
    public function testGetJsonArrayRejectsCachedScalars(string $json): void
    {
        $response = new Response([], $json);
        $response->getJson();
        $this->expectException(UnexpectedValueException::class);
        $response->getJsonArray();
    }

    public function testWithBodyDiscardsJsonError(): void
    {
        $response = new Response([], 'invalid');
        $this->assertNull($response->getJson());
        $replacement = $response->withBody(new Stream('data://text/plain,{"id":2}'));
        $this->assertSame(['id' => 2], $replacement->getJsonArray());
        $this->expectException(JsonException::class);
        $response->getJsonArray();
    }

    #[DataProvider('cachedJsonProvider')]
    public function testGetJsonArraySharesDecodedData(string $json, ?array $expected): void
    {
        $response = new Response([], $json);
        $this->assertSame($expected, $response->getJsonArray());
        $this->assertSame($expected, $response->getJson());
        $this->assertSame($expected, $response->getJsonArray());
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
        $this->assertEquals($data, $response->getJson());
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
    public static function isSuccessProvider(): array
    {
        return [
            [
                true,
                new Response([
                    'HTTP/1.1 200 OK',
                    'Content-Type: text/html',
                ], 'ok'),
            ],
            [
                true,
                new Response([
                    'HTTP/1.1 201 Created',
                    'Content-Type: text/html',
                ], 'ok'),
            ],
            [
                true,
                new Response([
                    'HTTP/1.1 202 Accepted',
                    'Content-Type: text/html',
                ], 'ok'),
            ],
            [
                true,
                new Response([
                    'HTTP/1.1 203 Non-Authoritative Information',
                    'Content-Type: text/html',
                ], 'ok'),
            ],
            [
                true,
                new Response([
                    'HTTP/1.1 204 No Content',
                    'Content-Type: text/html',
                ], ''),
            ],
            [
                false,
                new Response([
                    'HTTP/1.1 301 Moved Permanently',
                    'Content-Type: text/html',
                ], ''),
            ],
            [
                false,
                new Response([
                    'HTTP/1.0 404 Not Found',
                    'Content-Type: text/html',
                ], ''),
            ],
        ];
    }

    /**
     * Test isSuccess()
     */
    #[DataProvider('isSuccessProvider')]
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
            $result['expires'],
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
     * Test updating the status preserves the original response and its other contents.
     */
    public function testWithStatus(): void
    {
        $response = new Response(['HTTP/1.0 200 OK', 'Content-Type: text/plain'], 'response body');

        $updated = $response->withStatus(202, 'Accepted');

        $this->assertNotSame($response, $updated);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame(202, $updated->getStatusCode());
        $this->assertSame('Accepted', $updated->getReasonPhrase());
        $this->assertSame($response->getHeaders(), $updated->getHeaders());
        $this->assertSame($response->getBody(), $updated->getBody());
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
