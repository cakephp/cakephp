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
namespace Cake\Test\TestCase\Http;

use Cake\Core\Exception\CakeException;
use Cake\Http\Client;
use Cake\Http\Client\Adapter\Stream;
use Cake\Http\Client\Exception\MissingResponseException;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieCollection;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Laminas\Diactoros\Request as LaminasRequest;

/**
 * HTTP client test.
 */
class ClientTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        Client::clearMockResponses();
    }

    /**
     * Test storing config options and modifying them.
     */
    public function testConstructConfig(): void
    {
        $config = [
            'scheme' => 'http',
            'host' => 'example.org',
            'basePath' => '/api/v1',
        ];
        $http = new Client($config);
        $result = $http->getConfig();
        foreach ($config as $key => $val) {
            $this->assertEquals($val, $result[$key]);
        }

        $result = $http->setConfig([
            'auth' => ['username' => 'mark', 'password' => 'secret'],
        ]);
        $this->assertSame($result, $http);

        $result = $http->getConfig();
        $expected = [
            'scheme' => 'http',
            'host' => 'example.org',
            'auth' => ['username' => 'mark', 'password' => 'secret'],
            'protocolVersion' => '1.1',
        ];
        foreach ($expected as $key => $val) {
            $this->assertEquals($val, $result[$key]);
        }
    }

    /**
     * testAdapterInstanceCheck
     */
    public function testAdapterInstanceCheck(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Adapter must be an instance of Cake\Http\Client\AdapterInterface');

        new Client(['adapter' => 'stdClass']);
    }

    /**
     * Data provider for buildUrl() tests
     *
     * @return array
     */
    public static function urlProvider(): array
    {
        return [
            [
                'http://example.com/test.html',
                'http://example.com/test.html',
                [],
                null,
                'Null options',
            ],
            [
                'http://example.com/test.html',
                'http://example.com/test.html',
                [],
                [],
                'Simple string',
            ],
            [
                'http://example.com/test.html',
                '/test.html',
                [],
                ['host' => 'example.com'],
                'host name option',
            ],
            [
                'https://example.com/test.html',
                '/test.html',
                [],
                ['host' => 'example.com', 'scheme' => 'https'],
                'HTTPS',
            ],
            [
                'https://example.com/api/v1/foo/test.html',
                '/foo/test.html',
                [],
                ['host' => 'example.com', 'scheme' => 'https', 'basePath' => '/api/v1'],
                'Base path included',
            ],
            [
                'https://example.com/api/v1/foo/test.html',
                '/foo/test.html',
                [],
                ['host' => 'example.com', 'scheme' => 'https', 'basePath' => '/api/v1/'],
                'Base path with trailing forward slash',
            ],
            [
                'https://example.com/api/v1/foo/test.html',
                '/foo/test.html',
                [],
                ['host' => 'example.com', 'scheme' => 'https', 'basePath' => 'api/v1/'],
                'Base path with no prepended forward slash',
            ],
            [
                'http://example.com:8080/test.html',
                '/test.html',
                [],
                ['host' => 'example.com', 'port' => '8080'],
                'Non standard port',
            ],
            [
                'http://example.com/test.html',
                '/test.html',
                [],
                ['host' => 'example.com', 'port' => '80'],
                'standard port, does not display',
            ],
            [
                'https://example.com/test.html',
                '/test.html',
                [],
                ['host' => 'example.com', 'scheme' => 'https', 'port' => '443'],
                'standard port, does not display',
            ],
            [
                'http://example.com/test.html',
                'http://example.com/test.html',
                [],
                ['host' => 'example.com', 'scheme' => 'https'],
                'options do not duplicate',
            ],
            [
                'http://example.com/search?q=hi%20there&cat%5Bid%5D%5B0%5D=2&cat%5Bid%5D%5B1%5D=3',
                'http://example.com/search',
                ['q' => 'hi there', 'cat' => ['id' => [2, 3]]],
                [],
                'query string data.',
            ],
            [
                'http://example.com/search?q=hi+there&id=12',
                'http://example.com/search?q=hi+there',
                ['id' => '12'],
                [],
                'query string data with some already on the url.',
            ],
            [
                'http://example.com/test.html',
                '//test.html',
                [],
                [
                    'scheme' => 'http',
                    'host' => 'example.com',
                    'protocolRelative' => false,
                ],
                'url with a double slash',
            ],
            [
                'http://example.com/test.html',
                '//example.com/test.html',
                [],
                [
                    'scheme' => 'http',
                    'protocolRelative' => true,
                ],
                'protocol relative url',
            ],
            [
                'https://example.com/operations?%24filter=operation_id%20eq%2012',
                'https://example.com/operations',
                ['$filter' => 'operation_id eq 12'],
                [],
                'check the RFC 3986 query encoding',
            ],
        ];
    }

    /**
     * @dataProvider urlProvider
     */
    public function testBuildUrl(string $expected, string $url, array $query, ?array $opts): void
    {
        $http = new Client();

        $result = $http->buildUrl($url, $query, (array)$opts);
        $this->assertEquals($expected, $result);
    }

    /**
     * test simple get request with headers & cookies.
     */
    public function testGetSimpleWithHeadersAndCookies(): void
    {
        $response = new Response();

        $headers = [
            'User-Agent' => 'Cake',
            'Connection' => 'close',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $cookies = [
            'split' => 'value',
        ];

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($headers) {
                $this->assertInstanceOf('Cake\Http\Client\Request', $request);
                $this->assertSame(Request::METHOD_GET, $request->getMethod());
                $this->assertSame('2', $request->getProtocolVersion());
                $this->assertSame('http://cakephp.org/test.html', $request->getUri() . '');
                $this->assertSame('split=value', $request->getHeaderLine('Cookie'));
                $this->assertSame($headers['Content-Type'], $request->getHeaderLine('content-type'));
                $this->assertSame($headers['Connection'], $request->getHeaderLine('connection'));

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client(['adapter' => $mock, 'protocolVersion' => '2']);
        $result = $http->get('http://cakephp.org/test.html', [], [
            'headers' => $headers,
            'cookies' => $cookies,
        ]);
        $this->assertSame($result, $response);
    }

    /**
     * test get request with no data
     */
    public function testGetNoData(): void
    {
        $response = new Response();

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertSame(Request::METHOD_GET, $request->getMethod());
                $this->assertEmpty($request->getHeaderLine('Content-Type'), 'Should have no content-type set');
                $this->assertSame(
                    'http://cakephp.org/search',
                    $request->getUri() . ''
                );

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $result = $http->get('/search');
        $this->assertSame($result, $response);
    }

    /**
     * test get request with querystring data
     */
    public function testGetQuerystring(): void
    {
        $response = new Response();

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertSame(Request::METHOD_GET, $request->getMethod());
                $this->assertSame(
                    'http://cakephp.org/search?q=hi%20there&Category%5Bid%5D%5B0%5D=2&Category%5Bid%5D%5B1%5D=3',
                    $request->getUri() . ''
                );

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $result = $http->get('/search', [
            'q' => 'hi there',
            'Category' => ['id' => [2, 3]],
        ]);
        $this->assertSame($result, $response);
    }

    /**
     * test get request with string of query data.
     */
    public function testGetQuerystringString(): void
    {
        $response = new Response();

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertSame(
                    'http://cakephp.org/search?q=hi+there&Category%5Bid%5D%5B0%5D=2&Category%5Bid%5D%5B1%5D=3',
                    $request->getUri() . ''
                );

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $data = [
            'q' => 'hi there',
            'Category' => ['id' => [2, 3]],
        ];
        $result = $http->get('/search', http_build_query($data));
        $this->assertSame($response, $result);
    }

    /**
     * Test a GET with a request body. Services like
     * elasticsearch use this feature.
     */
    public function testGetWithContent(): void
    {
        $response = new Response();

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertSame(Request::METHOD_GET, $request->getMethod());
                $this->assertSame('http://cakephp.org/search', '' . $request->getUri());
                $this->assertSame('some data', '' . $request->getBody());

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $result = $http->get('/search', [
            '_content' => 'some data',
        ]);
        $this->assertSame($result, $response);
    }

    /**
     * Test invalid authentication types throw exceptions.
     */
    public function testInvalidAuthenticationType(): void
    {
        $this->expectException(CakeException::class);
        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->never())
            ->method('send');

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $result = $http->get('/', [], [
            'auth' => ['type' => 'horribly broken'],
        ]);
    }

    /**
     * Test setting basic authentication with get
     */
    public function testGetWithAuthenticationAndProxy(): void
    {
        $response = new Response();

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $headers = [
            'Authorization' => 'Basic ' . base64_encode('mark:secret'),
            'Proxy-Authorization' => 'Basic ' . base64_encode('mark:pass'),
        ];
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($headers) {
                $this->assertSame(Request::METHOD_GET, $request->getMethod());
                $this->assertSame('http://cakephp.org/', '' . $request->getUri());
                $this->assertSame($headers['Authorization'], $request->getHeaderLine('Authorization'));
                $this->assertSame($headers['Proxy-Authorization'], $request->getHeaderLine('Proxy-Authorization'));

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $result = $http->get('/', [], [
            'auth' => ['username' => 'mark', 'password' => 'secret'],
            'proxy' => ['username' => 'mark', 'password' => 'pass'],
        ]);
        $this->assertSame($result, $response);
    }

    /**
     * Return a list of HTTP methods.
     *
     * @return array
     */
    public static function methodProvider(): array
    {
        return [
            [Request::METHOD_GET],
            [Request::METHOD_POST],
            [Request::METHOD_PUT],
            [Request::METHOD_DELETE],
            [Request::METHOD_PATCH],
            [Request::METHOD_OPTIONS],
            [Request::METHOD_TRACE],
        ];
    }

    /**
     * test simple POST request.
     *
     * @dataProvider methodProvider
     */
    public function testMethodsSimple(string $method): void
    {
        $response = new Response();

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($method) {
                $this->assertInstanceOf('Cake\Http\Client\Request', $request);
                $this->assertEquals($method, $request->getMethod());
                $this->assertSame('http://cakephp.org/projects/add', '' . $request->getUri());

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $result = $http->{$method}('/projects/add');
        $this->assertSame($result, $response);
    }

    /**
     * Provider for testing the type option.
     *
     * @return array
     */
    public static function typeProvider(): array
    {
        return [
            ['application/json', 'application/json'],
            ['json', 'application/json'],
            ['xml', 'application/xml'],
            ['application/xml', 'application/xml'],
        ];
    }

    /**
     * Test that using the 'type' option sets the correct headers
     *
     * @dataProvider typeProvider
     */
    public function testPostWithTypeKey(string $type, string $mime): void
    {
        $response = new Response();
        $data = 'some data';
        $headers = [
            'Content-Type' => $mime,
            'Accept' => $mime,
        ];

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($headers) {
                $this->assertSame(Request::METHOD_POST, $request->getMethod());
                $this->assertEquals($headers['Content-Type'], $request->getHeaderLine('Content-Type'));
                $this->assertEquals($headers['Accept'], $request->getHeaderLine('Accept'));

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $http->post('/projects/add', $data, ['type' => $type]);
    }

    /**
     * Test that string payloads with no content type have a default content-type set.
     */
    public function testPostWithStringDataDefaultsToFormEncoding(): void
    {
        $response = new Response();
        $data = 'some=value&more=data';

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->any())
            ->method('send')
            ->with($this->callback(function ($request) use ($data) {
                $this->assertSame($data, '' . $request->getBody());
                $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('content-type'));

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $http->post('/projects/add', $data);
        $http->put('/projects/add', $data);
        $http->delete('/projects/add', $data);
    }

    /**
     * Test that exceptions are raised on invalid types.
     */
    public function testExceptionOnUnknownType(): void
    {
        $this->expectException(CakeException::class);
        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->never())
            ->method('send');

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $http->post('/projects/add', 'it works', ['type' => 'invalid']);
    }

    /**
     * Test that Client stores cookies
     */
    public function testCookieStorage(): void
    {
        $adapter = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();

        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1',
            'Set-Cookie: expiring=now; Expires=Wed, 09-Jun-1999 10:18:14 GMT',
        ];
        $response = new Response($headers, '');
        $adapter->expects($this->once())
            ->method('send')
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $adapter,
        ]);

        $http->get('/projects');
        $cookies = $http->cookies();
        $this->assertCount(1, $cookies);
        $this->assertTrue($cookies->has('first'));
        $this->assertFalse($cookies->has('expiring'));
    }

    /**
     * Test cookieJar config option.
     */
    public function testCookieJar(): void
    {
        $jar = new CookieCollection();
        $http = new Client([
            'cookieJar' => $jar,
        ]);

        $this->assertSame($jar, $http->cookies());
    }

    /**
     * Test addCookie() method.
     */
    public function testAddCookie(): void
    {
        $client = new Client();
        $cookie = new Cookie('foo', '', null, '/', 'example.com');

        $this->assertFalse($client->cookies()->has('foo'));

        $client->addCookie($cookie);
        $this->assertTrue($client->cookies()->has('foo'));
    }

    /**
     * Test addCookie() method without a domain.
     */
    public function testAddCookieWithoutDomain(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cookie must have a domain and a path set.');
        $client = new Client();
        $cookie = new Cookie('foo', '', null, '/', '');

        $this->assertFalse($client->cookies()->has('foo'));

        $client->addCookie($cookie);
        $this->assertTrue($client->cookies()->has('foo'));
    }

    /**
     * Test addCookie() method without a path.
     */
    public function testAddCookieWithoutPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cookie must have a domain and a path set.');
        $client = new Client();
        $cookie = new Cookie('foo', '', null, '', 'example.com');

        $this->assertFalse($client->cookies()->has('foo'));

        $client->addCookie($cookie);
        $this->assertTrue($client->cookies()->has('foo'));
    }

    /**
     * test head request with querystring data
     */
    public function testHeadQuerystring(): void
    {
        $response = new Response();

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertInstanceOf('Cake\Http\Client\Request', $request);
                $this->assertSame(Request::METHOD_HEAD, $request->getMethod());
                $this->assertSame('http://cakephp.org/search?q=hi%20there', '' . $request->getUri());

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock,
        ]);
        $result = $http->head('/search', [
            'q' => 'hi there',
        ]);
        $this->assertSame($result, $response);
    }

    /**
     * test redirects
     */
    public function testRedirects(): void
    {
        $url = 'http://cakephp.org';

        $adapter = $this->getMockBuilder(Client\Adapter\Stream::class)
            ->onlyMethods(['send'])
            ->getMock();

        $redirect = new Response([
            'HTTP/1.0 301',
            'Location: http://cakephp.org/redirect1?foo=bar',
            'Set-Cookie: redirect1=true;path=/',
        ]);

        $redirect2 = new Response([
            'HTTP/1.0 301',
            'Location: /redirect2#foo',
            'Set-Cookie: redirect2=true;path=/',
        ]);

        $response = new Response([
            'HTTP/1.0 200',
        ]);

        $adapter->expects($this->exactly(3))
            ->method('send')
            ->withConsecutive(
                [
                    $this->callback(function (Request $request) use ($url) {
                        $this->assertInstanceOf(Request::class, $request);
                        $this->assertSame($url, (string)$request->getUri());

                        return true;
                    }),
                    $this->callback(function ($options) {
                        $this->assertArrayNotHasKey('redirect', $options);

                        return true;
                    }),
                ],
                [
                    $this->callback(function (Request $request) use ($url) {
                        $this->assertInstanceOf(Request::class, $request);
                        $this->assertSame($url . '/redirect1?foo=bar', (string)$request->getUri());

                        return true;
                    }),
                    $this->callback(function ($options) {
                        $this->assertArrayNotHasKey('redirect', $options);

                        return true;
                    }),
                ],
                [
                    $this->callback(function (Request $request) use ($url) {
                        $this->assertInstanceOf(Request::class, $request);
                        $this->assertSame($url . '/redirect2#foo', (string)$request->getUri());

                        return true;
                    }),
                ]
            )
            ->will($this->onConsecutiveCalls([$redirect], [$redirect2], [$response]));

        $client = new Client([
            'adapter' => $adapter,
        ]);

        $result = $client->send(new Request($url), [
            'redirect' => 10,
        ]);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($result->isOk());
        $cookies = $client->cookies();

        $this->assertTrue($cookies->has('redirect1'));
        $this->assertTrue($cookies->has('redirect2'));
    }

    /**
     * testSendRequest
     */
    public function testSendRequest(): void
    {
        $response = new Response();

        $headers = [
            'User-Agent' => 'Cake',
            'Connection' => 'close',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $mock = $this->getMockBuilder(Stream::class)
            ->onlyMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($headers) {
                $this->assertInstanceOf('Laminas\Diactoros\Request', $request);
                $this->assertSame(Request::METHOD_GET, $request->getMethod());
                $this->assertSame('http://cakephp.org/test.html', $request->getUri() . '');
                $this->assertSame($headers['Content-Type'], $request->getHeaderLine('content-type'));
                $this->assertSame($headers['Connection'], $request->getHeaderLine('connection'));

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client(['adapter' => $mock]);
        $request = new LaminasRequest(
            'http://cakephp.org/test.html',
            Request::METHOD_GET,
            'php://temp',
            $headers
        );
        $result = $http->sendRequest($request);

        $this->assertSame($result, $response);
    }

    /**
     * test redirect across sub domains
     */
    public function testRedirectDifferentSubDomains(): void
    {
        $adapter = $this->getMockBuilder(Client\Adapter\Stream::class)
            ->onlyMethods(['send'])
            ->getMock();

        $url = 'http://auth.example.org';

        $redirect = new Response([
            'HTTP/1.0 301',
            'Location: http://backstage.example.org',
        ]);
        $response = new Response([
            'HTTP/1.0 200',
        ]);
        $adapter->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [$this->anything()],
                [
                    $this->callback(function ($request) {
                        $this->assertSame('http://backstage.example.org', (string)$request->getUri());
                        $this->assertSame('session=backend', $request->getHeaderLine('Cookie'));

                        return true;
                    }),
                ]
            )
            ->will($this->OnConsecutiveCalls([$redirect], [$response]));

        $client = new Client([
            'adapter' => $adapter,
        ]);
        $client->addCookie(new Cookie('session', 'backend', null, '/', 'backstage.example.org'));
        $client->addCookie(new Cookie('session', 'authz', null, '/', 'auth.example.org'));

        $result = $client->send(new Request($url), [
            'redirect' => 10,
        ]);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame($response, $result);
    }

    /**
     * Scheme is set when passed to client in string
     */
    public function testCreateFromUrlSetsScheme(): void
    {
        $client = Client::createFromUrl('https://example.co/');
        $this->assertSame('https', $client->getConfig('scheme'));
    }

    /**
     * Host is set when passed to client in string
     */
    public function testCreateFromUrlSetsHost(): void
    {
        $client = Client::createFromUrl('https://example.co/');
        $this->assertSame('example.co', $client->getConfig('host'));
    }

    /**
     * basePath is set when passed to client in string
     */
    public function testCreateFromUrlSetsBasePath(): void
    {
        $client = Client::createFromUrl('https://example.co/api/v1');
        $this->assertSame('/api/v1', $client->getConfig('basePath'));
    }

    /**
     * Test exception is thrown when URL cannot be parsed
     */
    public function testCreateFromUrlThrowsInvalidExceptionWhenUrlCannotBeParsed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::createFromUrl('htps://');
        $message = $this->getExpectedExceptionMessage();
        $this->assertTextContains('did not parse', $message);
    }

    /**
     * Port is set when passed to client in string
     */
    public function testCreateFromUrlSetsPort(): void
    {
        $client = Client::createFromUrl('https://example.co:8765/');
        $this->assertSame(8765, $client->getConfig('port'));
    }

    /**
     * Test exception is throw when no scheme is provided.
     */
    public function testCreateFromUrlThrowsInvalidArgumentExceptionWhenNoSchemeProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::createFromUrl('example.co');
        $message = $this->getExpectedExceptionMessage();
        $this->assertSame('The URL was parsed but did not contain a scheme or host', $message);
    }

    /**
     * Test exception is thrown if passed URL has no domain
     */
    public function testCreateFromUrlThrowsInvalidArgumentExceptionWhenNoDomainProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::createFromUrl('/api/v1');
        $message = $this->getExpectedExceptionMessage();
        $this->assertSame('The URL was parsed but did not contain a scheme or host', $message);
    }

    /**
     * Test that the passed parsed URL parts won't override other constructor defaults
     * or add undefined configuration
     */
    public function testCreateFromUrlOnlySetSchemePortHostBasePath(): void
    {
        $client = Client::createFromUrl('http://example.co:80/some/uri/?foo=bar');
        $config = $client->getConfig();
        $expected = [
            'auth' => null,
            'adapter' => null,
            'host' => 'example.co',
            'port' => 80,
            'scheme' => 'http',
            'basePath' => '/some/uri/',
            'timeout' => 30,
            'ssl_verify_peer' => true,
            'ssl_verify_peer_name' => true,
            'ssl_verify_depth' => 5,
            'ssl_verify_host' => true,
            'redirect' => false,
            'protocolVersion' => '1.1',
        ];
        $this->assertSame($expected, $config);
    }

    /**
     * Test adding and sending to a mocked URL.
     */
    public function testAddMockResponseSimpleMatch(): void
    {
        $stub = new Response(['HTTP/1.0 200'], 'hello world');
        Client::addMockResponse('POST', 'http://example.com/path', $stub);

        $client = new Client();
        $response = $client->post('http://example.com/path');
        $this->assertSame($stub, $response);
    }

    /**
     * When there are multiple matches for a URL the responses should
     * be used in a cycle.
     */
    public function testAddMockResponseMultipleMatches(): void
    {
        $one = new Response(['HTTP/1.0 200'], 'one');
        Client::addMockResponse('GET', 'http://example.com/info', $one);

        $two = new Response(['HTTP/1.0 200'], 'two');
        Client::addMockResponse('GET', 'http://example.com/info', $two);

        $client = new Client();

        $response = $client->get('http://example.com/info');
        $this->assertSame($one, $response);

        $response = $client->get('http://example.com/info');
        $this->assertSame($two, $response);

        $response = $client->get('http://example.com/info');
        $this->assertSame($one, $response);
    }

    /**
     * When there are multiple matches with custom match functions
     */
    public function testAddMockResponseMultipleMatchesCustom(): void
    {
        $one = new Response(['HTTP/1.0 200'], 'one');
        Client::addMockResponse('GET', 'http://example.com/info', $one, [
            'match' => function ($request) {
                return false;
            },
        ]);

        $two = new Response(['HTTP/1.0 200'], 'two');
        Client::addMockResponse('GET', 'http://example.com/info', $two);

        $client = new Client();

        $response = $client->get('http://example.com/info');
        $this->assertSame($two, $response);

        $response = $client->get('http://example.com/info');
        $this->assertSame($two, $response);
    }

    /**
     * Mock match failures should result in the request being sent
     */
    public function testAddMockResponseMethodMatchFailure(): void
    {
        $stub = new Response(['HTTP/1.0 200'], 'hello world');
        Client::addMockResponse('POST', 'http://example.com/path', $stub);

        $client = new Client();
        $this->expectException(MissingResponseException::class);
        $this->expectExceptionMessage('Unable to find a mock');

        $client->get('http://example.com/path');
    }

    /**
     * Trailing /* patterns should work
     */
    public function testAddMockResponseGlobMatch(): void
    {
        $stub = new Response(['HTTP/1.0 200'], 'hello world');
        Client::addMockResponse('POST', 'http://example.com/path/*', $stub);

        $client = new Client();
        $response = $client->post('http://example.com/path/more/thing');
        $this->assertSame($stub, $response);

        $client = new Client();
        $response = $client->post('http://example.com/path/?query=value');
        $this->assertSame($stub, $response);
    }

    /**
     * Custom match methods must be closures
     */
    public function testAddMockResponseInvalidMatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The `match` option must be a `Closure`.');

        $stub = new Response(['HTTP/1.0 200'], 'hello world');
        Client::addMockResponse('POST', 'http://example.com/path', $stub, [
            'match' => 'oops',
        ]);
    }

    /**
     * Custom matchers should get a request.
     */
    public function testAddMockResponseCustomMatch(): void
    {
        $stub = new Response(['HTTP/1.0 200'], 'hello world');
        Client::addMockResponse('POST', 'http://example.com/path', $stub, [
            'match' => function ($request) {
                $this->assertInstanceOf(Request::class, $request);
                $uri = $request->getUri();
                $this->assertEquals('/path', $uri->getPath());
                $this->assertEquals('example.com', $uri->getHost());

                return true;
            },
        ]);

        $client = new Client();
        $response = $client->post('http://example.com/path');

        $this->assertSame($stub, $response);
    }

    /**
     * Custom matchers can fail the match
     */
    public function testAddMockResponseCustomNoMatch(): void
    {
        $stub = new Response(['HTTP/1.0 200'], 'hello world');
        Client::addMockResponse('POST', 'http://example.com/path', $stub, [
            'match' => function () {
                return false;
            },
        ]);

        $client = new Client();
        $this->expectException(MissingResponseException::class);
        $this->expectExceptionMessage('Unable to find a mock');

        $client->post('http://example.com/path');
    }

    /**
     * Custom matchers must return a boolean
     */
    public function testAddMockResponseCustomInvalidDecision(): void
    {
        $stub = new Response(['HTTP/1.0 200'], 'hello world');
        Client::addMockResponse('POST', 'http://example.com/path', $stub, [
            'match' => function ($request) {
                return 'invalid';
            },
        ]);

        $client = new Client();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Match callback must');

        $client->post('http://example.com/path');
    }
}
