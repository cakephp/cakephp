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

use Cake\Core\Configure;
use Cake\Network\Http\Client;
use Cake\Network\Http\Request;
use Cake\Network\Http\Response;
use Cake\TestSuite\TestCase;
use Zend\Diactoros\Uri;

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
        $result = $http->config();
        foreach ($config as $key => $val) {
            $this->assertEquals($val, $result[$key]);
        }

        $result = $http->config([
            'auth' => ['username' => 'mark', 'password' => 'secret']
        ]);
        $this->assertSame($result, $http);

        $result = $http->config();
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

        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($cookies, $headers) {
                $this->assertInstanceOf('Cake\Network\Http\Request', $request);
                $this->assertEquals(Request::METHOD_GET, $request->getMethod());
                $this->assertEquals('http://cakephp.org/test.html', $request->getUri() . '');
                $this->assertEquals($cookies, $request->cookies());
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
     * test get request with querystring data
     *
     * @return void
     */
    public function testGetQuerystring()
    {
        $response = new Response();

        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
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

        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
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

        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
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
     * @expectedException \Cake\Core\Exception\Exception
     * @return void
     */
    public function testInvalidAuthenticationType()
    {
        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
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

        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
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
     * @return void
     */
    public function testAuthenticationWithMutation()
    {
        Configure::write('App.namespace', 'TestApp');
        $response = new Response();
        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
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

        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) use ($method) {
                $this->assertInstanceOf('Cake\Network\Http\Request', $request);
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

        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
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

        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
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
     * @expectedException \Cake\Core\Exception\Exception
     * @return void
     */
    public function testExceptionOnUnknownType()
    {
        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
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
        $adapter = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $cookieJar = $this->getMockBuilder('Cake\Network\Http\CookieCollection')->getMock();

        $headers = [
            'HTTP/1.0 200 Ok',
            'Set-Cookie: first=1',
            'Set-Cookie: expiring=now; Expires=Wed, 09-Jun-1999 10:18:14 GMT'
        ];
        $response = new Response($headers, '');

        $cookieJar->expects($this->at(0))
            ->method('get')
            ->with('http://cakephp.org/projects')
            ->will($this->returnValue([]));

        $cookieJar->expects($this->at(1))
            ->method('store')
            ->with($response);

        $adapter->expects($this->at(0))
            ->method('send')
            ->will($this->returnValue([$response]));

        $http = new Client([
            'host' => 'cakephp.org',
            'adapter' => $adapter,
            'cookieJar' => $cookieJar
        ]);

        $http->get('/projects');
    }

    /**
     * test head request with querystring data
     *
     * @return void
     */
    public function testHeadQuerystring()
    {
        $response = new Response();

        $mock = $this->getMockBuilder('Cake\Network\Http\Adapter\Stream')
            ->setMethods(['send'])
            ->getMock();
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($request) {
                $this->assertInstanceOf('Cake\Network\Http\Request', $request);
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
}
