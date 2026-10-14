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
 *
 * Parts of this file are derived from laminas-diactoros
 * @copyright Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC. (https://getlaminas.org/)
 * @license https://github.com/laminas/laminas-diactoros/blob/3.9.x/LICENSE.md New BSD License
 */
namespace Cake\Test\TestCase\Http\Client;

use Cake\Http\Client\Request;
use Cake\Http\Stream;
use Cake\Http\Uri;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Uri\Rfc3986\Uri as Rfc3986Uri;

/**
 * HTTP request test.
 */
class RequestTest extends TestCase
{
    public function testConstructorAcceptsNativeUri(): void
    {
        $request = new Request(new Rfc3986Uri('https://example.com/a%2fb?x=%7e'));

        $this->assertInstanceOf(UriInterface::class, $request->getUri());
        $this->assertSame('https://example.com/a%2fb?x=%7e', (string)$request->getUri());
    }

    public function testConstructorNormalizesHeaderValues(): void
    {
        $request = new Request('/', headers: ['X-Tag' => [' first ', 'second'], 'X-Empty' => '']);

        $this->assertSame(['first', 'second'], $request->getHeader('X-Tag'));
        $this->assertSame([''], $request->getHeader('X-Empty'));
    }

    public function testConstructorRejectsHeaderInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Request('/', headers: ['X-Tag' => "value\r\nInjected: value"]);
    }

    public function testConstructorRejectsInvalidHeaderName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Request('/', headers: ['Invalid Name' => 'value']);
    }

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
     */
    #[DataProvider('additionProvider')]
    public function testMethods(array $headers, $data, $method): void
    {
        $request = new Request('http://example.com', $method, $headers, $data);

        $this->assertSame($request->getMethod(), $method);
        $this->assertSame('http://example.com', (string)$request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame(json_encode($data), $request->getBody()->__toString());
    }

    public static function additionProvider(): array
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
            'Authorization' => 'Bearer valid-token',
        ];
        $data = ['a' => 'b', 'c' => 'd'];
        $request = new Request('http://example.com', 'POST', $headers, $data);

        $this->assertSame('http://example.com', (string)$request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertSame('a=b&c=d', $request->getBody()->__toString());

        $headers = [
            'Content-Type' => 'application/xml',
        ];
        $data = ['tag' => 'value'];
        $request = new Request('http://example.com', 'POST', $headers, $data);

        $this->assertSame('application/xml', $request->getHeaderLine('Content-Type'));
        $this->assertSame('value', $request->getBody()->__toString());
    }

    /**
     * test nested array data for encoding of brackets, header and constructor
     */
    public function testConstructorArrayNestedData(): void
    {
        $headers = [
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
            'Body should be serialized',
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

    public function testMethodIsGetByDefault(): void
    {
        $request = new Request();
        $this->assertSame('GET', $request->getMethod());
    }

    public function testMethodMutatorReturnsCloneWithChangedMethod(): void
    {
        $request = new Request();
        $new = $request->withMethod('POST');
        $this->assertNotSame($request, $new);
        $this->assertSame('POST', $new->getMethod());
        $this->assertSame('GET', $request->getMethod());
    }

    public function testMethodIsCaseSensitive(): void
    {
        $request = new Request()->withMethod('post');
        $this->assertSame('post', $request->getMethod());
    }

    public static function invalidMethodProvider(): array
    {
        return [
            'empty' => [''],
            'with-space' => ['BOGUS METHOD'],
            'with-newline' => ["GET\n"],
            'with-crlf' => ["GET\r\nX-Foo: bar"],
            'with-slash' => ['GET/POST'],
            'with-colon' => ['GET:'],
        ];
    }

    #[DataProvider('invalidMethodProvider')]
    public function testWithInvalidMethod(string $method): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported HTTP method');

        new Request()->withMethod($method);
    }

    #[DataProvider('invalidMethodProvider')]
    public function testConstructorRaisesExceptionForInvalidMethod(string $method): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported HTTP method');

        new Request('', $method);
    }

    public static function customRequestMethods(): array
    {
        return [
            /* WebDAV methods */
            'TRACE' => ['TRACE'],
            'PROPFIND' => ['PROPFIND'],
            'PROPPATCH' => ['PROPPATCH'],
            'MKCOL' => ['MKCOL'],
            'COPY' => ['COPY'],
            'MOVE' => ['MOVE'],
            'LOCK' => ['LOCK'],
            'UNLOCK' => ['UNLOCK'],
            /* Arbitrary methods */
            '#!ALPHA-1234&%' => ['#!ALPHA-1234&%'],
        ];
    }

    #[DataProvider('customRequestMethods')]
    public function testAllowsCustomRequestMethodsThatFollowSpec(string $method): void
    {
        $request = new Request('', $method);
        $this->assertSame($method, $request->getMethod());

        $request = new Request()->withMethod($method);
        $this->assertSame($method, $request->getMethod());
    }

    public function testReturnsUnpopulatedUriByDefault(): void
    {
        $uri = new Request()->getUri();
        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
    }

    public function testConstructorAcceptsUriInstance(): void
    {
        $uri = new Uri('http://example.com/');
        $request = new Request($uri, 'POST', ['x-foo' => ['bar']]);

        $this->assertSame($uri, $request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame(['bar'], $request->getHeaders()['x-foo']);
    }

    public function testWithUriReturnsNewInstanceWithNewUri(): void
    {
        $request = new Request();
        $new = $request->withUri(new Uri('https://example.com:10082/foo/bar?baz=bat'));
        $this->assertNotSame($request, $new);
        $new2 = $new->withUri(new Uri('/baz/bat?foo=bar'));
        $this->assertNotSame($request, $new2);
        $this->assertNotSame($new, $new2);
        $this->assertSame('/baz/bat?foo=bar', (string)$new2->getUri());
        $this->assertSame('https://example.com:10082/foo/bar?baz=bat', (string)$new->getUri());
    }

    public function testDefaultStreamIsWritable(): void
    {
        $request = new Request();
        $request->getBody()->write('test');

        $this->assertSame('test', (string)$request->getBody());
    }

    public function testWithBodyReplacesStream(): void
    {
        $request = new Request();
        $stream = new Stream('php://memory', 'rw');
        $new = $request->withBody($stream);
        $this->assertNotSame($request, $new);
        $this->assertSame($stream, $new->getBody());
    }

    public function testRequestTargetIsSlashWhenNoUriPresent(): void
    {
        $request = new Request();
        $this->assertSame('/', $request->getRequestTarget());
    }

    public function testRequestTargetIsSlashWhenUriHasNoPathOrQuery(): void
    {
        $request = new Request()->withUri(new Uri('http://example.com'));
        $this->assertSame('/', $request->getRequestTarget());

        $request = new Request('http://example.com');
        $this->assertSame('/', $request->getRequestTarget());
    }

    public static function requestsWithUri(): array
    {
        return [
            'absolute-uri' => [
                new Request()
                    ->withUri(new Uri('https://api.example.com/user'))
                    ->withMethod('POST'),
                '/user',
            ],
            'absolute-uri-with-query' => [
                new Request()
                    ->withUri(new Uri('https://api.example.com/user?foo=bar'))
                    ->withMethod('POST'),
                '/user?foo=bar',
            ],
            'relative-uri' => [
                new Request()
                    ->withUri(new Uri('/user'))
                    ->withMethod('GET'),
                '/user',
            ],
            'relative-uri-with-query' => [
                new Request()
                    ->withUri(new Uri('/user?foo=bar'))
                    ->withMethod('GET'),
                '/user?foo=bar',
            ],
            'constructor-uri-with-query' => [
                new Request('https://api.example.com/user?foo=bar#fragment'),
                '/user?foo=bar',
            ],
        ];
    }

    #[DataProvider('requestsWithUri')]
    public function testReturnsRequestTargetWhenUriIsPresent(RequestInterface $request, string $expected): void
    {
        $this->assertSame($expected, $request->getRequestTarget());
    }

    public static function validRequestTargets(): array
    {
        return [
            'asterisk-form' => ['*'],
            'authority-form' => ['api.example.com'],
            'absolute-form' => ['https://api.example.com/users'],
            'absolute-form-query' => ['https://api.example.com/users?foo=bar'],
            'origin-form-path-only' => ['/users'],
            'origin-form' => ['/users?id=foo'],
        ];
    }

    #[DataProvider('validRequestTargets')]
    public function testCanProvideARequestTarget(string $requestTarget): void
    {
        $request = new Request('http://example.com/other');
        $new = $request->withRequestTarget($requestTarget);
        $this->assertNotSame($request, $new);
        $this->assertSame($requestTarget, $new->getRequestTarget());
        $this->assertSame('/other', $request->getRequestTarget());
    }

    public static function invalidRequestTargets(): array
    {
        return [
            'space' => ['foo bar baz'],
            'tab' => ["/foo\tbar"],
            'newline' => ["/foo\nbar"],
            'crlf' => ["/foo\r\nHost: evil.com"],
        ];
    }

    #[DataProvider('invalidRequestTargets')]
    public function testRequestTargetCannotContainWhitespace(string $requestTarget): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid request target');

        new Request()->withRequestTarget($requestTarget);
    }

    public function testRequestTargetDoesNotCacheBetweenInstances(): void
    {
        $request = new Request()->withUri(new Uri('https://example.com/foo/bar'));
        $original = $request->getRequestTarget();
        $newRequest = $request->withUri(new Uri('http://mwop.net/bar/baz'));
        $this->assertNotSame($original, $newRequest->getRequestTarget());
    }

    public function testSettingNewUriResetsRequestTarget(): void
    {
        $request = new Request()->withUri(new Uri('https://example.com/foo/bar'));
        $newRequest = $request->withUri(new Uri('http://mwop.net/bar/baz'));

        $this->assertNotSame($request->getRequestTarget(), $newRequest->getRequestTarget());
        $this->assertSame('/bar/baz', $newRequest->getRequestTarget());
    }

    public function testConstructorDoesNotSetHostHeaderFromUri(): void
    {
        $request = new Request('http://example.com');
        $this->assertFalse($request->hasHeader('Host'));
        $this->assertSame([], $request->getHeader('host'));
        $this->assertSame('', $request->getHeaderLine('host'));
        $this->assertArrayNotHasKey('Host', $request->getHeaders());
    }

    public function testConstructorKeepsExplicitHostHeader(): void
    {
        $request = new Request('http://www.example.com', 'GET', ['Host' => 'www.test.com']);
        $this->assertSame('www.test.com', $request->getHeaderLine('host'));
    }

    public function testWithUriSetsHostHeaderFromUri(): void
    {
        $request = new Request()->withUri(new Uri('http://www.example.com'));
        $this->assertTrue($request->hasHeader('Host'));
        $this->assertSame(['www.example.com'], $request->getHeader('host'));
        $this->assertSame(['www.example.com'], $request->getHeaders()['Host']);
    }

    public function testWithUriWithoutHostDoesNotAddHostHeader(): void
    {
        $request = new Request()->withUri(new Uri('/foo'));
        $this->assertFalse($request->hasHeader('Host'));
        $this->assertArrayNotHasKey('Host', $request->getHeaders());
    }

    public function testWithoutHeaderRemovesHostHeader(): void
    {
        $request = new Request()
            ->withUri(new Uri('http://example.com'))
            ->withoutHeader('host');
        $this->assertFalse($request->hasHeader('Host'));
        $this->assertSame([], $request->getHeader('host'));
    }

    public function testPassingPreserveHostFlagWhenUpdatingUriDoesNotUpdateHostHeader(): void
    {
        $request = new Request()->withAddedHeader('Host', 'example.com');

        $uri = new Uri()->withHost('www.example.com');
        $new = $request->withUri($uri, true);

        $this->assertSame('example.com', $new->getHeaderLine('Host'));
    }

    public function testPassingPreserveHostFlagSetsHostHeaderWhenMissing(): void
    {
        $request = new Request();

        $uri = new Uri()->withHost('www.example.com');
        $new = $request->withUri($uri, true);

        $this->assertSame('www.example.com', $new->getHeaderLine('Host'));
    }

    public function testNotPassingPreserveHostFlagWhenUpdatingUriWithoutHostDoesNotUpdateHostHeader(): void
    {
        $request = new Request()->withAddedHeader('Host', 'example.com');

        $new = $request->withUri(new Uri());

        $this->assertSame('example.com', $new->getHeaderLine('Host'));
    }

    public function testHostHeaderUpdatesToUriHostAndPortWhenPreserveHostDisabledAndNonStandardPort(): void
    {
        $request = new Request()->withAddedHeader('Host', 'example.com');

        $uri = new Uri()
            ->withHost('www.example.com')
            ->withPort(10081);
        $new = $request->withUri($uri);

        $this->assertSame('www.example.com:10081', $new->getHeaderLine('Host'));
    }

    public function testHostHeaderOmitsStandardPort(): void
    {
        $new = new Request()->withUri(new Uri('https://www.example.com:443/foo'));

        $this->assertSame('www.example.com', $new->getHeaderLine('Host'));
    }

    public static function hostHeaderKeys(): array
    {
        return [
            'lowercase' => ['host'],
            'mixed-4' => ['hosT'],
            'mixed-3-4' => ['hoST'],
            'reverse-titlecase' => ['hOST'],
            'uppercase' => ['HOST'],
            'mixed-1-2-3' => ['HOSt'],
            'mixed-1-2' => ['HOst'],
            'titlecase' => ['Host'],
            'mixed-1-4' => ['HosT'],
            'mixed-1-2-4' => ['HOsT'],
            'mixed-1-3-4' => ['HoST'],
            'mixed-1-3' => ['HoSt'],
            'mixed-2-3' => ['hOSt'],
            'mixed-2-4' => ['hOsT'],
            'mixed-2' => ['hOst'],
            'mixed-3' => ['hoSt'],
        ];
    }

    #[DataProvider('hostHeaderKeys')]
    public function testWithUriAndNoPreserveHostWillOverwriteHostHeaderRegardlessOfOriginalCase(string $hostKey): void
    {
        $request = new Request()->withHeader($hostKey, 'example.com');

        $new = $request->withUri(new Uri('http://example.org/foo/bar'));
        $this->assertSame('example.org', $new->getHeaderLine('host'));
        $headers = $new->getHeaders();
        $this->assertArrayHasKey('Host', $headers);
        if ($hostKey !== 'Host') {
            $this->assertArrayNotHasKey($hostKey, $headers);
        }
        $this->assertSame('example.com', $request->getHeaderLine('host'));
    }

    #[DataProvider('hostHeaderKeys')]
    public function testWithUriAndPreserveHostKeepsHostHeaderRegardlessOfOriginalCase(string $hostKey): void
    {
        $request = new Request()->withHeader($hostKey, 'example.com');

        $new = $request->withUri(new Uri('http://example.org/foo/bar'), true);
        $this->assertSame('example.com', $new->getHeaderLine('host'));
        $this->assertSame([$hostKey => ['example.com']], array_intersect_key($new->getHeaders(), [$hostKey => true]));
    }
}
