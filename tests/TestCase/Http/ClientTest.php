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
namespace Cake\Test\TestCase\Http;

use Cake\Http\Client;
use Cake\Http\Client\Request;
use Cake\Http\Client\Response;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieCollection;
use Cake\TestSuite\TestCase;

/**
 * HTTP client test.
 */
class ClientTest extends TestCase
{

    /**
     * Test storing config options and modifying them.
     *
     * @return void
     */
    public function testConstructConfig()
    {
        $config = [
            'scheme' => 'http',
            'host' => 'example.org',
        ];
        $http = new Client($config);
        $result = $http->getConfig();
        foreach ($config as $key => $val) {
            $this->assertEquals($val, $result[$key]);
        }

        $result = $http->setConfig([
            'auth' => ['username' => 'mark', 'password' => 'secret']
        ]);
        $this->assertSame($result, $http);

        $result = $http->getConfig();
        $expected = [
            'scheme' => 'http',
            'host' => 'example.org',
            'auth' => ['username' => 'mark', 'password' => 'secret']
        ];
        foreach ($expected as $key => $val) {
            $this->assertEquals($val, $result[$key]);
        }
    }

    /**
     * Data provider for buildUrl() tests
     *
     * @return array
     */
    public static function urlProvider()
    {
        return [
            [
                'http://example.com/test.html',
                'http://example.com/test.html',
                [],
                null,
                'Null options'
            ],
            [
                'http://example.com/test.html',
                'http://example.com/test.html',
                [],
                [],
                'Simple string'
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
                'standard port, does not display'
            ],
            [
                'https://example.com/test.html',
                '/test.html',
                [],
                ['host' => 'example.com', 'scheme' => 'https', 'port' => '443'],
                'standard port, does not display'
            ],
            [
                'http://example.com/test.html',
                'http://example.com/test.html',
                [],
                ['host' => 'example.com', 'scheme' => 'https'],
                'options do not duplicate'
            ],
            [
                'http://example.com/search?q=hi+there&cat%5Bid%5D%5B0%5D=2&cat%5Bid%5D%5B1%5D=3',
                'http://example.com/search',
                ['q' => 'hi there', 'cat' => ['id' => [2, 3]]],
                [],
                'query string data.'
            ],
            [
                'http://example.com/search?q=hi+there&id=12',
                'http://example.com/search?q=hi+there',
                ['id' => '12'],
                [],
                'query string data with some already on the url.'
            ],
            [
                'http://example.com/test.html',
                '//test.html',
                [],
                [
                    'scheme' => 'http',
                    'host' => 'example.com',
                    'protocolRelative' => false
                ],
                'url with a double slash',
            ],
            [
                'http://example.com/test.html',
                '//example.com/test.html',
                [],
                [
                    'scheme' => 'http',
                    'protocolRelative' => true
                ],
                'protocol relative url',
            ],
        ];
    }

    /**
     * @dataProvider urlProvider
     */
    public function testBuildUrl($expected, $url, $query, $opts)
    {
        $http = new Client();

        $result = $http->buildUrl($url, $query, $opts);
        $this->assertEquals($expected, $result);
    }

    /**
     * test simple get request with headers & cookies.
     *
     * @return void
     */
    public function testGetSimpleWithHeadersAndCookies()
    {
        $response = new Response();

        $headers = [
            'User-Agent' => 'Cake',
            'Connection' => 'close',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $cookies = [
            'split' => 'value'
        ];

        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($cookies, $headers) {
                $this->assertInstanceOf('Cake\Http\Client\Request', $request);
                $this->assertEquals(Request::METHOD_GET, $request->getMethod());
                $this->assertEquals('http://cakephp.org/test.html', $request->getUri() . '');
                $this->assertEquals('split=value', $request->getHeaderLine('Cookie'));
                $this->assertEquals($headers['Content-Type'], $request->getHeaderLine('content-type'));
                $this->assertEquals($headers['Connection'], $request->getHeaderLine('connection'));

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client(['adapter' => $mock]);
        $result = $http->get('http://cakephp.org/test.html', [], [
            'headers' => $headers,
            'cookies' => $cookies,
        ]);
        $this->assertSame($result, $response);
    }

    /**
     * test get request with no data
     *
     * @return void
     */
    public function testGetNoData()
    {
        $response = new Response();

        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertEquals(Request::METHOD_GET, $request->getMethod());
                $this->assertEmpty($request->getHeaderLine('Content-Type'), 'Should have no content-type set');
                $this->assertEquals(
                    'http://cakephp.org/search',
                    $request->getUri() . ''
                );

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $result = $http->get('/search');
        $this->assertSame($result, $response);
    }

    /**
     * test get request with querystring data
     *
     * @return void
     */
    public function testGetQuerystring()
    {
        $response = new Response();

        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertEquals(Request::METHOD_GET, $request->getMethod());
                $this->assertEquals(
                    'http://cakephp.org/search?q=hi+there&Category%5Bid%5D%5B0%5D=2&Category%5Bid%5D%5B1%5D=3',
                    $request->getUri() . ''
                );

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $result = $http->get('/search', [
            'q' => 'hi there',
            'Category' => ['id' => [2, 3]]
        ]);
        $this->assertSame($result, $response);
    }

    /**
     * test get request with string of query data.
     *
     * @return void
     */
    public function testGetQuerystringString()
    {
        $response = new Response();

        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertEquals(
                    'http://cakephp.org/search?q=hi+there&Category%5Bid%5D%5B0%5D=2&Category%5Bid%5D%5B1%5D=3',
                    $request->getUri() . ''
                );

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $data = [
            'q' => 'hi there',
            'Category' => ['id' => [2, 3]]
        ];
        $result = $http->get('/search', http_build_query($data));
        $this->assertSame($response, $result);
    }

    /**
     * Test a GET with a request body. Services like
     * elasticsearch use this feature.
     *
     * @return void
     */
    public function testGetWithContent()
    {
        $response = new Response();

        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertEquals(Request::METHOD_GET, $request->getMethod());
                $this->assertEquals('http://cakephp.org/search', '' . $request->getUri());
                $this->assertEquals('some data', '' . $request->getBody());

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $result = $http->get('/search', [
            '_content' => 'some data'
        ]);
        $this->assertSame($result, $response);
    }

    /**
     * Test invalid authentication types throw exceptions.
     *
     * @return void
     */
    public function testInvalidAuthenticationType()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->never())
            ->method('send');

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $result = $http->get('/', [], [
            'auth' => ['type' => 'horribly broken']
        ]);
    }

    /**
     * Test setting basic authentication with get
     *
     * @return void
     */
    public function testGetWithAuthenticationAndProxy()
    {
        $response = new Response();

        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $headers = [
            'Authorization' => 'Basic ' . base64_encode('mark:secret'),
            'Proxy-Authorization' => 'Basic ' . base64_encode('mark:pass'),
        ];
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($headers) {
                $this->assertEquals(Request::METHOD_GET, $request->getMethod());
                $this->assertEquals('http://cakephp.org/', '' . $request->getUri());
                $this->assertEquals($headers['Authorization'], $request->getHeaderLine('Authorization'));
                $this->assertEquals($headers['Proxy-Authorization'], $request->getHeaderLine('Proxy-Authorization'));

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $result = $http->get('/', [], [
            'auth' => ['username' => 'mark', 'password' => 'secret'],
            'proxy' => ['username' => 'mark', 'password' => 'pass'],
        ]);
        $this->assertSame($result, $response);
    }

    /**
     * Test authentication adapter that mutates request.
     *
     * @group deprecated
     * @return void
     */
    public function testAuthenticationWithMutation()
    {
        $this->deprecated(function () {
            static::setAppNamespace();
            $response = new Response();
            $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
                ->setMethods(['send'])
                ->getMock();
            $headers = [
                'Authorization' => 'Bearer abc123',
                'Proxy-Authorization' => 'Bearer abc123',
            ];
            $mock->expects($this->once())
                ->method('send')
                ->with($this->callback(function ($request) use ($headers) {
                    $this->assertEquals(Request::METHOD_GET, $request->getMethod());
                    $this->assertEquals('http://cakephp.org/', '' . $request->getUri());
                    $this->assertEquals($headers['Authorization'], $request->getHeaderLine('Authorization'));
                    $this->assertEquals($headers['Proxy-Authorization'], $request->getHeaderLine('Proxy-Authorization'));

                    return true;
                }))
                ->will($this->returnValue([$response]));

            $http = new Client([
                'host' => 'cakephp.org',
                'adapter' => $mock
            ]);
            $result = $http->get('/', [], [
                'auth' => ['type' => 'TestApp\Http\CompatAuth'],
                'proxy' => ['type' => 'TestApp\Http\CompatAuth'],
            ]);
            $this->assertSame($result, $response);
        });
    }

    /**
     * Return a list of HTTP methods.
     *
     * @return array
     */
    public static function methodProvider()
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
     * @return void
     */
    public function testMethodsSimple($method)
    {
        $response = new Response();

        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($method) {
                $this->assertInstanceOf('Cake\Http\Client\Request', $request);
                $this->assertEquals($method, $request->getMethod());
                $this->assertEquals('http://cakephp.org/projects/add', '' . $request->getUri());

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $result = $http->{$method}('/projects/add');
        $this->assertSame($result, $response);
    }

    /**
     * Provider for testing the type option.
     *
     * @return array
     */
    public static function typeProvider()
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
     * @return void
     */
    public function testPostWithTypeKey($type, $mime)
    {
        $response = new Response();
        $data = 'some data';
        $headers = [
            'Content-Type' => $mime,
            'Accept' => $mime,
        ];

        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($headers) {
                $this->assertEquals(Request::METHOD_POST, $request->getMethod());
                $this->assertEquals($headers['Content-Type'], $request->getHeaderLine('Content-Type'));
                $this->assertEquals($headers['Accept'], $request->getHeaderLine('Accept'));

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $http->post('/projects/add', $data, ['type' => $type]);
    }

    /**
     * Test that string payloads with no content type have a default content-type set.
     *
     * @return void
     */
    public function testPostWithStringDataDefaultsToFormEncoding()
    {
        $response = new Response();
        $data = 'some=value&more=data';

        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->any())
            ->method('send')
            ->with($this->callback(function ($request) use ($data) {
                $this->assertEquals($data, '' . $request->getBody());
                $this->assertEquals('application/x-www-form-urlencoded', $request->getHeaderLine('content-type'));

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $http->post('/projects/add', $data);
        $http->put('/projects/add', $data);
        $http->delete('/projects/add', $data);
    }

    /**
     * Test that exceptions are raised on invalid types.
     *
     * @return void
     */
    public function testExceptionOnUnknownType()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->never())
            ->method('send');

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $http->post('/projects/add', 'it works', ['type' => 'invalid']);
    }

    /**
     * Test that Client stores cookies
     *
     * @return void
     */
    public function testCookieStorage()
    {
        $adapter = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();

        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1',
            'Set-Cookie: expiring=now; Expires=Wed, 09-Jun-1999 10:18:14 GMT'
        ];
        $response = new Response($headers, '');
        $adapter->expects($this->at(0))
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
     *
     * @return void
     */
    public function testCookieJar()
    {
        $jar = new CookieCollection();
        $http = new Client([
            'cookieJar' => $jar
        ]);

        $this->assertSame($jar, $http->cookies());
    }

    /**
     * Test addCookie() method.
     *
     * @return void
     */
    public function testAddCookie()
    {
        $client = new Client();
        $cookie = new Cookie('foo', '', null, '/', 'example.com');

        $this->assertFalse($client->cookies()->has('foo'));

        $client->addCookie($cookie);
        $this->assertTrue($client->cookies()->has('foo'));
    }

    /**
     * Test addCookie() method without a domain.
     *
     * @return void
     */
    public function testAddCookieWithoutDomain()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cookie must have a domain and a path set.');
        $client = new Client();
        $cookie = new Cookie('foo', '', null, '/', '');

        $this->assertFalse($client->cookies()->has('foo'));

        $client->addCookie($cookie);
        $this->assertTrue($client->cookies()->has('foo'));
    }

    /**
     * Test addCookie() method without a path.
     *
     * @return void
     */
    public function testAddCookieWithoutPath()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cookie must have a domain and a path set.');
        $client = new Client();
        $cookie = new Cookie('foo', '', null, '', 'example.com');

        $this->assertFalse($client->cookies()->has('foo'));

        $client->addCookie($cookie);
        $this->assertTrue($client->cookies()->has('foo'));
    }

    /**
     * test head request with querystring data
     *
     * @return void
     */
    public function testHeadQuerystring()
    {
        $response = new Response();

        $mock = $this->getMockBuilder('Cake\Http\Client\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertInstanceOf('Cake\Http\Client\Request', $request);
                $this->assertEquals(Request::METHOD_HEAD, $request->getMethod());
                $this->assertEquals('http://cakephp.org/search?q=hi+there', '' . $request->getUri());

                return true;
            }))
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $mock
        ]);
        $result = $http->head('/search', [
            'q' => 'hi there',
        ]);
        $this->assertSame($result, $response);
    }

    /**
     * test redirects
     *
     * @return void
     */
    public function testRedirects()
    {
        $url = 'http://cakephp.org';

        $adapter = $this->getMockBuilder(Client\Adapter\Stream::class)
            ->setMethods(['send'])
            ->getMock();

        $redirect = new Response([
            'HTTP/1.0 301',
            'Location: http://cakephp.org/redirect1?foo=bar',
            'Set-Cookie: redirect1=true;path=/',
        ]);
        $adapter->expects($this->at(0))
            ->method('send')
            ->with(
                $this->callback(function ($request) use ($url) {
                    $this->assertInstanceOf(Request::class, $request);
                    $this->assertEquals($url, $request->getUri());

                    return true;
                }),
                $this->callback(function ($options) {
                    $this->assertArrayNotHasKey('redirect', $options);

                    return true;
                })
            )
            ->willReturn([$redirect]);

        $redirect2 = new Response([
            'HTTP/1.0 301',
            'Location: /redirect2#foo',
            'Set-Cookie: redirect2=true;path=/',
        ]);
        $adapter->expects($this->at(1))
            ->method('send')
            ->with(
                $this->callback(function ($request) use ($url) {
                    $this->assertInstanceOf(Request::class, $request);
                    $this->assertEquals($url . '/redirect1?foo=bar', $request->getUri());

                    return true;
                }),
                $this->callback(function ($options) {
                    $this->assertArrayNotHasKey('redirect', $options);

                    return true;
                })
            )
            ->willReturn([$redirect2]);

        $response = new Response([
            'HTTP/1.0 200'
        ]);
        $adapter->expects($this->at(2))
            ->method('send')
            ->with($this->callback(function ($request) use ($url) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertEquals($url . '/redirect2#foo', $request->getUri());

                return true;
            }))
            ->willReturn([$response]);

        $client = new Client([
            'adapter' => $adapter
        ]);

        $result = $client->send(new Request($url), [
            'redirect' => 10
        ]);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertTrue($result->isOk());
        $cookies = $client->cookies();

        $this->assertTrue($cookies->has('redirect1'));
        $this->assertTrue($cookies->has('redirect2'));
    }
}
