<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Network\Exception\MethodNotAllowedException;
use Cake\Network\Session;
use Cake\TestSuite\TestCase;
use Zend\Diactoros\UploadedFile;
use Zend\Diactoros\Uri;

/**
 * ServerRequest Test
 */
class ServerRequestTest extends TestCase
{
    /**
     * SERVER variable backup.
     *
     * @var array
     */
    protected $server = [];

    /**
     * Setup callback
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->server = $_SERVER;

        Configure::write('App.baseUrl', false);
    }

    public function tearDown()
    {
        parent::tearDown();

        $_SERVER = $this->server;
    }

    /**
     * Test custom detector with extra arguments.
     *
     * @return void
     */
    public function testCustomArgsDetector()
    {
        $request = new ServerRequest();
        $request->addDetector('controller', function ($request, $name) {
            return $request->param('controller') === $name;
        });

        $request->params = ['controller' => 'cake'];
        $this->assertTrue($request->is('controller', 'cake'));
        $this->assertFalse($request->is('controller', 'nonExistingController'));
        $this->assertTrue($request->isController('cake'));
        $this->assertFalse($request->isController('nonExistingController'));
    }

    /**
     * Test the header detector.
     *
     * @return void
     */
    public function testHeaderDetector()
    {
        $request = new ServerRequest();
        $request->addDetector('host', ['header' => ['host' => 'cakephp.org']]);

        $request->env('HTTP_HOST', 'cakephp.org');
        $this->assertTrue($request->is('host'));

        $request->env('HTTP_HOST', 'php.net');
        $this->assertFalse($request->is('host'));
    }

    /**
     * Test the accept header detector.
     *
     * @return void
     */
    public function testExtensionDetector()
    {
        $request = new ServerRequest();
        $request->params['_ext'] = 'json';
        $this->assertTrue($request->is('json'));

        $request = new ServerRequest();
        $request->params['_ext'] = 'xml';
        $this->assertFalse($request->is('json'));
    }

    /**
     * Test the accept header detector.
     *
     * @return void
     */
    public function testAcceptHeaderDetector()
    {
        $request = new ServerRequest();
        $request->env('HTTP_ACCEPT', 'application/json, text/plain, */*');
        $this->assertTrue($request->is('json'));

        $request = new ServerRequest();
        $request->env('HTTP_ACCEPT', 'text/plain, */*');
        $this->assertFalse($request->is('json'));
    }

    /**
     * Test that the autoparse = false constructor works.
     *
     * @return void
     */
    public function testNoAutoParseConstruction()
    {
        $_GET = [
            'one' => 'param'
        ];
        $request = new ServerRequest();
        $this->assertFalse(isset($request->query['one']));
    }

    /**
     * Test construction
     *
     * @return void
     */
    public function testConstructionQueryData()
    {
        $data = [
            'query' => [
                'one' => 'param',
                'two' => 'banana'
            ],
            'url' => 'some/path'
        ];
        $request = new ServerRequest($data);
        $this->assertEquals($request->query, $data['query']);
        $this->assertEquals('some/path', $request->url);
    }

    /**
     * Test that querystring args provided in the URL string are parsed.
     *
     * @return void
     */
    public function testQueryStringParsingFromInputUrl()
    {
        $_GET = [];
        $request = new ServerRequest(['url' => 'some/path?one=something&two=else']);
        $expected = ['one' => 'something', 'two' => 'else'];
        $this->assertEquals($expected, $request->query);
        $this->assertEquals('some/path', $request->url);
        $this->assertEquals('one=something&two=else', $request->getUri()->getQuery());
    }

    /**
     * Test that named arguments + querystrings are handled correctly.
     *
     * @return void
     */
    public function testQueryStringAndNamedParams()
    {
        $_SERVER['REQUEST_URI'] = '/tasks/index?ts=123456';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('tasks/index', $request->url);

        $_SERVER['REQUEST_URI'] = '/tasks/index/?ts=123456';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('tasks/index/', $request->url);

        $_SERVER['REQUEST_URI'] = '/some/path?url=http://cakephp.org';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('some/path', $request->url);

        $_SERVER['REQUEST_URI'] = Configure::read('App.fullBaseUrl') . '/other/path?url=http://cakephp.org';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('other/path', $request->url);
    }

    /**
     * Test that URL in path is handled correctly.
     */
    public function testUrlInPath()
    {
        $_SERVER['REQUEST_URI'] = '/jump/http://cakephp.org';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('jump/http://cakephp.org', $request->url);

        $_SERVER['REQUEST_URI'] = Configure::read('App.fullBaseUrl') . '/jump/http://cakephp.org';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('jump/http://cakephp.org', $request->url);
    }

    /**
     * Test addParams() method
     *
     * @return void
     */
    public function testAddParams()
    {
        $request = new ServerRequest();
        $request->params = ['controller' => 'posts', 'action' => 'view'];
        $result = $request->addParams(['plugin' => null, 'action' => 'index']);

        $this->assertSame($result, $request, 'Method did not return itself. %s');

        $this->assertEquals('posts', $request->controller);
        $this->assertEquals('index', $request->action);
        $this->assertEquals(null, $request->plugin);
    }

    /**
     * Test splicing in paths.
     *
     * @return void
     */
    public function testAddPaths()
    {
        $request = new ServerRequest();
        $request->webroot = '/some/path/going/here/';
        $result = $request->addPaths([
            'random' => '/something', 'webroot' => '/', 'here' => '/', 'base' => '/base_dir'
        ]);

        $this->assertSame($result, $request, 'Method did not return itself. %s');

        $this->assertEquals('/', $request->webroot);
        $this->assertEquals('/base_dir', $request->base);
        $this->assertEquals('/', $request->here);
        $this->assertFalse(isset($request->random));
    }

    /**
     * Test parsing POST data into the object.
     *
     * @return void
     */
    public function testPostParsing()
    {
        $post = [
            'Article' => ['title']
        ];
        $request = new ServerRequest(compact('post'));
        $this->assertEquals($post, $request->data);

        $post = ['one' => 1, 'two' => 'three'];
        $request = new ServerRequest(compact('post'));
        $this->assertEquals($post, $request->data);

        $post = [
            'Article' => ['title' => 'Testing'],
            'action' => 'update'
        ];
        $request = new ServerRequest(compact('post'));
        $this->assertEquals($post, $request->data);
    }

    /**
     * Test parsing PUT data into the object.
     *
     * @return void
     */
    public function testPutParsing()
    {
        $data = [
            'Article' => ['title']
        ];
        $request = new ServerRequest([
            'input' => 'Article[]=title',
            'environment' => [
                'REQUEST_METHOD' => 'PUT',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8'
            ]
        ]);
        $this->assertEquals($data, $request->data);

        $data = ['one' => 1, 'two' => 'three'];
        $request = new ServerRequest([
            'input' => 'one=1&two=three',
            'environment' => [
                'REQUEST_METHOD' => 'PUT',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8'
            ]
        ]);
        $this->assertEquals($data, $request->data);

        $request = new ServerRequest([
            'input' => 'Article[title]=Testing&action=update',
            'environment' => [
                'REQUEST_METHOD' => 'DELETE',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8'
            ]
        ]);
        $expected = [
            'Article' => ['title' => 'Testing'],
            'action' => 'update'
        ];
        $this->assertEquals($expected, $request->data);

        $data = [
            'Article' => ['title'],
            'Tag' => ['Tag' => [1, 2]]
        ];
        $request = new ServerRequest([
            'input' => 'Article[]=title&Tag[Tag][]=1&Tag[Tag][]=2',
            'environment' => [
                'REQUEST_METHOD' => 'PATCH',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8'
            ]
        ]);
        $this->assertEquals($data, $request->data);
    }

    /**
     * Test parsing json PUT data into the object.
     *
     * @return void
     */
    public function testPutParsingJSON()
    {
        $data = '{"Article":["title"]}';
        $request = new ServerRequest([
            'input' => $data,
            'environment' => [
                'REQUEST_METHOD' => 'PUT',
                'CONTENT_TYPE' => 'application/json'
            ]
        ]);
        $this->assertEquals([], $request->data);
        $result = $request->input('json_decode', true);
        $this->assertEquals(['title'], $result['Article']);
    }

    /**
     * Test processing files with `file` field names.
     *
     * @return void
     */
    public function testFilesNested()
    {
        $files = [
            'image_main' => [
                'name' => ['file' => 'born on.txt'],
                'type' => ['file' => 'text/plain'],
                'tmp_name' => ['file' => __FILE__],
                'error' => ['file' => 0],
                'size' => ['file' => 17178]
            ],
            0 => [
                'name' => ['image' => 'scratch.text'],
                'type' => ['image' => 'text/plain'],
                'tmp_name' => ['image' => __FILE__],
                'error' => ['image' => 0],
                'size' => ['image' => 1490]
            ],
            'pictures' => [
                'name' => [
                    0 => ['file' => 'a-file.png'],
                    1 => ['file' => 'a-moose.png']
                ],
                'type' => [
                    0 => ['file' => 'image/png'],
                    1 => ['file' => 'image/jpg']
                ],
                'tmp_name' => [
                    0 => ['file' => __FILE__],
                    1 => ['file' => __FILE__]
                ],
                'error' => [
                    0 => ['file' => 0],
                    1 => ['file' => 0]
                ],
                'size' => [
                    0 => ['file' => 17188],
                    1 => ['file' => 2010]
                ],
            ]
        ];
        $post = [
            'pictures' => [
                0 => ['name' => 'A cat'],
                1 => ['name' => 'A moose']
            ],
            0 => [
                'name' => 'A dog'
            ]
        ];
        $request = new ServerRequest(compact('files', 'post'));
        $expected = [
            'image_main' => [
                'file' => [
                    'name' => 'born on.txt',
                    'type' => 'text/plain',
                    'tmp_name' => __FILE__,
                    'error' => 0,
                    'size' => 17178,
                ]
            ],
            'pictures' => [
                0 => [
                    'name' => 'A cat',
                    'file' => [
                        'name' => 'a-file.png',
                        'type' => 'image/png',
                        'tmp_name' => __FILE__,
                        'error' => '0',
                        'size' => 17188,
                    ]
                ],
                1 => [
                    'name' => 'A moose',
                    'file' => [
                        'name' => 'a-moose.png',
                        'type' => 'image/jpg',
                        'tmp_name' => __FILE__,
                        'error' => '0',
                        'size' => 2010,
                    ]
                ]
            ],
            0 => [
                'name' => 'A dog',
                'image' => [
                    'name' => 'scratch.text',
                    'type' => 'text/plain',
                    'tmp_name' => __FILE__,
                    'error' => 0,
                    'size' => 1490
                ]
            ]
        ];
        $this->assertEquals($expected, $request->data);

        $uploads = $request->getUploadedFiles();
        $this->assertCount(3, $uploads);
        $this->assertArrayHasKey(0, $uploads);
        $this->assertEquals('scratch.text', $uploads[0]['image']->getClientFilename());

        $this->assertArrayHasKey('pictures', $uploads);
        $this->assertEquals('a-file.png', $uploads['pictures'][0]['file']->getClientFilename());
        $this->assertEquals('a-moose.png', $uploads['pictures'][1]['file']->getClientFilename());

        $this->assertArrayHasKey('image_main', $uploads);
        $this->assertEquals('born on.txt', $uploads['image_main']['file']->getClientFilename());
    }

    /**
     * Test processing a file input with no .'s in it.
     *
     * @return void
     */
    public function testFilesFlat()
    {
        $files = [
            'birth_cert' => [
                'name' => 'born on.txt',
                'type' => 'application/octet-stream',
                'tmp_name' => __FILE__,
                'error' => 0,
                'size' => 123,
            ]
        ];

        $request = new ServerRequest(compact('files'));
        $expected = [
            'birth_cert' => [
                'name' => 'born on.txt',
                'type' => 'application/octet-stream',
                'tmp_name' => __FILE__,
                'error' => 0,
                'size' => 123
            ]
        ];
        $this->assertEquals($expected, $request->data);

        $uploads = $request->getUploadedFiles();
        $this->assertCount(1, $uploads);
        $this->assertArrayHasKey('birth_cert', $uploads);
        $this->assertEquals('born on.txt', $uploads['birth_cert']->getClientFilename());
        $this->assertEquals(0, $uploads['birth_cert']->getError());
        $this->assertEquals('application/octet-stream', $uploads['birth_cert']->getClientMediaType());
        $this->assertEquals(123, $uploads['birth_cert']->getSize());
    }

    /**
     * Test that files in the 0th index work.
     *
     * @return void
     */
    public function testFilesZeroithIndex()
    {
        $files = [
            0 => [
                'name' => 'cake_sqlserver_patch.patch',
                'type' => 'text/plain',
                'tmp_name' => __FILE__,
                'error' => 0,
                'size' => 6271,
            ],
        ];

        $request = new ServerRequest([
            'files' => $files
        ]);
        $this->assertEquals($files, $request->data);

        $uploads = $request->getUploadedFiles();
        $this->assertCount(1, $uploads);
        $this->assertEquals($files[0]['name'], $uploads[0]->getClientFilename());
    }

    /**
     * Test that the constructor uses uploaded file objects
     * if they are present. This could happen in test scenarios.
     *
     * @return void
     */
    public function testFilesObject()
    {
        $file = new UploadedFile(
            __FILE__,
            123,
            UPLOAD_ERR_OK,
            'test.php',
            'text/plain'
        );
        $request = new ServerRequest(['files' => ['avatar' => $file]]);
        $this->assertSame(['avatar' => $file], $request->getUploadedFiles());
    }

    /**
     * Test replacing files.
     *
     * @return void
     */
    public function testWithUploadedFiles()
    {
        $file = new UploadedFile(
            __FILE__,
            123,
            UPLOAD_ERR_OK,
            'test.php',
            'text/plain'
        );
        $request = new ServerRequest();
        $new = $request->withUploadedFiles(['picture' => $file]);

        $this->assertSame([], $request->getUploadedFiles());
        $this->assertNotSame($new, $request);
        $this->assertSame(['picture' => $file], $new->getUploadedFiles());
    }

    /**
     * Test getting a single file
     *
     * @return void
     */
    public function testGetUploadedFile()
    {
        $file = new UploadedFile(
            __FILE__,
            123,
            UPLOAD_ERR_OK,
            'test.php',
            'text/plain'
        );
        $request = new ServerRequest();
        $new = $request->withUploadedFiles(['picture' => $file]);
        $this->assertNull($new->getUploadedFile(''));
        $this->assertSame($file, $new->getUploadedFile('picture'));

        $new = $request->withUploadedFiles([
            'pictures' => [
                [
                    'image' => $file
                ]
            ]
        ]);
        $this->assertNull($new->getUploadedFile('pictures'));
        $this->assertNull($new->getUploadedFile('pictures.0'));
        $this->assertNull($new->getUploadedFile('pictures.1'));
        $this->assertSame($file, $new->getUploadedFile('pictures.0.image'));
    }

    /**
     * Test replacing files with an invalid file
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid file at 'avatar'
     * @return void
     */
    public function testWithUploadedFilesInvalidFile()
    {
        $request = new ServerRequest();
        $request->withUploadedFiles(['avatar' => 'not a file']);
    }

    /**
     * Test replacing files with an invalid file
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid file at 'user.avatar'
     * @return void
     */
    public function testWithUploadedFilesInvalidFileNested()
    {
        $request = new ServerRequest();
        $request->withUploadedFiles(['user' => ['avatar' => 'not a file']]);
    }

    /**
     * Test method overrides coming in from POST data.
     *
     * @return void
     */
    public function testMethodOverrides()
    {
        $post = ['_method' => 'POST'];
        $request = new ServerRequest(compact('post'));
        $this->assertEquals('POST', $request->env('REQUEST_METHOD'));

        $post = ['_method' => 'DELETE'];
        $request = new ServerRequest(compact('post'));
        $this->assertEquals('DELETE', $request->env('REQUEST_METHOD'));

        $request = new ServerRequest(['environment' => ['HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT']]);
        $this->assertEquals('PUT', $request->env('REQUEST_METHOD'));

        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'POST'],
            'post' => ['_method' => 'PUT']
        ]);
        $this->assertEquals('PUT', $request->env('REQUEST_METHOD'));
        $this->assertEquals('POST', $request->env('ORIGINAL_REQUEST_METHOD'));
    }

    /**
     * Tests the env() method returning a default value in case the requested environment variable is not set.
     */
    public function testDefaultEnvValue()
    {
        $_ENV['DOES_NOT_EXIST'] = null;
        $request = new ServerRequest();
        $this->assertNull($request->env('DOES_NOT_EXIST'));
        $this->assertEquals('default', $request->env('DOES_NOT_EXIST', null, 'default'));

        $_ENV['DOES_EXIST'] = 'some value';
        $request = new ServerRequest();
        $this->assertEquals('some value', $request->env('DOES_EXIST'));
        $this->assertEquals('some value', $request->env('DOES_EXIST', null, 'default'));

        $_ENV['EMPTY_VALUE'] = '';
        $request = new ServerRequest();
        $this->assertEquals('', $request->env('EMPTY_VALUE'));
        $this->assertEquals('', $request->env('EMPTY_VALUE', null, 'default'));

        $_ENV['ZERO'] = '0';
        $request = new ServerRequest();
        $this->assertEquals('0', $request->env('ZERO'));
        $this->assertEquals('0', $request->env('ZERO', null, 'default'));
    }

    /**
     * Test the clientIp method.
     *
     * @return void
     */
    public function testClientIp()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_X_FORWARDED_FOR' => '192.168.1.5, 10.0.1.1, proxy.com',
            'HTTP_CLIENT_IP' => '192.168.1.2',
            'REMOTE_ADDR' => '192.168.1.3'
        ]]);

        $request->trustProxy = true;
        $this->assertEquals('192.168.1.5', $request->clientIp());

        $request->env('HTTP_X_FORWARDED_FOR', '');
        $this->assertEquals('192.168.1.2', $request->clientIp());

        $request->trustProxy = false;
        $this->assertEquals('192.168.1.3', $request->clientIp());

        $request->env('HTTP_X_FORWARDED_FOR', '');
        $this->assertEquals('192.168.1.3', $request->clientIp());

        $request->env('HTTP_CLIENT_IP', '');
        $this->assertEquals('192.168.1.3', $request->clientIp());
    }

    /**
     * Test the referrer function.
     *
     * @return void
     */
    public function testReferer()
    {
        $request = new ServerRequest();
        $request->webroot = '/';

        $request->env('HTTP_REFERER', 'http://cakephp.org');
        $result = $request->referer();
        $this->assertSame('http://cakephp.org', $result);

        $request->env('HTTP_REFERER', '');
        $result = $request->referer();
        $this->assertSame('/', $result);

        $request->env('HTTP_REFERER', Configure::read('App.fullBaseUrl') . '/some/path');
        $result = $request->referer(true);
        $this->assertSame('/some/path', $result);

        $request->env('HTTP_REFERER', Configure::read('App.fullBaseUrl') . '/0');
        $result = $request->referer(true);
        $this->assertSame('/0', $result);

        $request->env('HTTP_REFERER', Configure::read('App.fullBaseUrl') . '/');
        $result = $request->referer(true);
        $this->assertSame('/', $result);

        $request->env('HTTP_REFERER', Configure::read('App.fullBaseUrl') . '/some/path');
        $result = $request->referer(false);
        $this->assertSame(Configure::read('App.fullBaseUrl') . '/some/path', $result);
    }

    /**
     * Test referer() with a base path that duplicates the
     * first segment.
     *
     * @return void
     */
    public function testRefererBasePath()
    {
        $request = new ServerRequest('some/path');
        $request->url = 'users/login';
        $request->webroot = '/waves/';
        $request->base = '/waves';
        $request->here = '/waves/users/login';

        $request->env('HTTP_REFERER', Configure::read('App.fullBaseUrl') . '/waves/waves/add');

        $result = $request->referer(true);
        $this->assertSame('/waves/add', $result);
    }

    /**
     * test the simple uses of is()
     *
     * @return void
     */
    public function testIsHttpMethods()
    {
        $request = new ServerRequest();

        $this->assertFalse($request->is('undefined-behavior'));

        $request->env('REQUEST_METHOD', 'GET');
        $this->assertTrue($request->is('get'));

        $request->env('REQUEST_METHOD', 'POST');
        $this->assertTrue($request->is('POST'));

        $request->env('REQUEST_METHOD', 'PUT');
        $this->assertTrue($request->is('put'));
        $this->assertFalse($request->is('get'));

        $request->env('REQUEST_METHOD', 'DELETE');
        $this->assertTrue($request->is('delete'));
        $this->assertTrue($request->isDelete());

        $request->env('REQUEST_METHOD', 'delete');
        $this->assertFalse($request->is('delete'));
    }

    /**
     * Test is() with json and xml.
     *
     * @return void
     */
    public function testIsJsonAndXml()
    {
        $request = new ServerRequest();
        $request->env('HTTP_ACCEPT', 'application/json, text/plain, */*');
        $this->assertTrue($request->is('json'));

        $request = new ServerRequest();
        $request->env('HTTP_ACCEPT', 'application/xml, text/plain, */*');
        $this->assertTrue($request->is('xml'));

        $request = new ServerRequest();
        $request->env('HTTP_ACCEPT', 'text/xml, */*');
        $this->assertTrue($request->is('xml'));
    }

    /**
     * Test is() with multiple types.
     *
     * @return void
     */
    public function testIsMultiple()
    {
        $request = new ServerRequest();

        $request->env('REQUEST_METHOD', 'GET');
        $this->assertTrue($request->is(['get', 'post']));

        $request->env('REQUEST_METHOD', 'POST');
        $this->assertTrue($request->is(['get', 'post']));

        $request->env('REQUEST_METHOD', 'PUT');
        $this->assertFalse($request->is(['get', 'post']));
    }

    /**
     * Test isAll()
     *
     * @return void
     */
    public function testIsAll()
    {
        $request = new ServerRequest();

        $request->env('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        $request->env('REQUEST_METHOD', 'GET');

        $this->assertTrue($request->isAll(['ajax', 'get']));
        $this->assertFalse($request->isAll(['post', 'get']));
        $this->assertFalse($request->isAll(['ajax', 'post']));
    }

    /**
     * Test the method() method.
     *
     * @return void
     * @deprecated
     */
    public function testMethod()
    {
        $request = new ServerRequest(['environment' => ['REQUEST_METHOD' => 'delete']]);

        $this->assertEquals('delete', $request->method());
    }

    /**
     * Test getMethod()
     *
     * @return void
     */
    public function testGetMethod()
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'delete']
        ]);
        $this->assertEquals('delete', $request->getMethod());
    }

    /**
     * Test withMethod()
     *
     * @return void
     */
    public function testWithMethod()
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'delete']
        ]);
        $new = $request->withMethod('put');
        $this->assertNotSame($new, $request);
        $this->assertEquals('delete', $request->getMethod());
        $this->assertEquals('put', $new->getMethod());
    }

    /**
     * Test withMethod() and invalid data
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unsupported HTTP method "no good" provided
     * @return void
     */
    public function testWithMethodInvalid()
    {
        $request = new ServerRequest([
            'environment' => ['REQUEST_METHOD' => 'delete']
        ]);
        $request->withMethod('no good');
    }

    /**
     * Test getProtocolVersion()
     *
     * @return void
     */
    public function testGetProtocolVersion()
    {
        $request = new ServerRequest();
        $this->assertEquals('1.1', $request->getProtocolVersion());

        // SERVER var.
        $request = new ServerRequest([
            'environment' => ['SERVER_PROTOCOL' => 'HTTP/1.0']
        ]);
        $this->assertEquals('1.0', $request->getProtocolVersion());
    }

    /**
     * Test withProtocolVersion()
     *
     * @return void
     */
    public function testWithProtocolVersion()
    {
        $request = new ServerRequest();
        $new = $request->withProtocolVersion('1.0');
        $this->assertNotSame($new, $request);
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('1.0', $new->getProtocolVersion());
    }

    /**
     * Test withProtocolVersion() and invalid data
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unsupported protocol version 'no good' provided
     * @return void
     */
    public function testWithProtocolVersionInvalid()
    {
        $request = new ServerRequest();
        $request->withProtocolVersion('no good');
    }

    /**
     * Test host retrieval.
     *
     * @return void
     */
    public function testHost()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_HOST' => 'localhost',
            'HTTP_X_FORWARDED_HOST' => 'cakephp.org',
        ]]);
        $this->assertEquals('localhost', $request->host());

        $request->trustProxy = true;
        $this->assertEquals('cakephp.org', $request->host());
    }

    /**
     * test port retrieval.
     *
     * @return void
     */
    public function testPort()
    {
        $request = new ServerRequest(['environment' => ['SERVER_PORT' => '80']]);

        $this->assertEquals('80', $request->port());

        $request->env('SERVER_PORT', '443');
        $request->env('HTTP_X_FORWARDED_PORT', '80');
        $this->assertEquals('443', $request->port());

        $request->trustProxy = true;
        $this->assertEquals('80', $request->port());
    }

    /**
     * test domain retrieval.
     *
     * @return void
     */
    public function testDomain()
    {
        $request = new ServerRequest(['environment' => ['HTTP_HOST' => 'something.example.com']]);

        $this->assertEquals('example.com', $request->domain());

        $request->env('HTTP_HOST', 'something.example.co.uk');
        $this->assertEquals('example.co.uk', $request->domain(2));
    }

    /**
     * Test scheme() method.
     *
     * @return void
     */
    public function testScheme()
    {
        $request = new ServerRequest(['environment' => ['HTTPS' => 'on']]);

        $this->assertEquals('https', $request->scheme());

        $request->env('HTTPS', '');
        $this->assertEquals('http', $request->scheme());

        $request->env('HTTP_X_FORWARDED_PROTO', 'https');
        $request->trustProxy = true;
        $this->assertEquals('https', $request->scheme());
    }

    /**
     * test getting subdomains for a host.
     *
     * @return void
     */
    public function testSubdomain()
    {
        $request = new ServerRequest(['environment' => ['HTTP_HOST' => 'something.example.com']]);

        $this->assertEquals(['something'], $request->subdomains());

        $request->env('HTTP_HOST', 'www.something.example.com');
        $this->assertEquals(['www', 'something'], $request->subdomains());

        $request->env('HTTP_HOST', 'www.something.example.co.uk');
        $this->assertEquals(['www', 'something'], $request->subdomains(2));

        $request->env('HTTP_HOST', 'example.co.uk');
        $this->assertEquals([], $request->subdomains(2));
    }

    /**
     * Test ajax, flash and friends
     *
     * @return void
     */
    public function testisAjaxFlashAndFriends()
    {
        $request = new ServerRequest();

        $request->env('HTTP_USER_AGENT', 'Shockwave Flash');
        $this->assertTrue($request->is('flash'));

        $request->env('HTTP_USER_AGENT', 'Adobe Flash');
        $this->assertTrue($request->is('flash'));

        $request->env('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->assertTrue($request->is('ajax'));

        $request->env('HTTP_X_REQUESTED_WITH', 'XMLHTTPREQUEST');
        $this->assertFalse($request->is('ajax'));
        $this->assertFalse($request->isAjax());
    }

    /**
     * Test __call exceptions
     *
     * @expectedException \BadMethodCallException
     * @return void
     */
    public function testMagicCallExceptionOnUnknownMethod()
    {
        $request = new ServerRequest();
        $request->IamABanana();
    }

    /**
     * Test is(ssl)
     *
     * @return void
     */
    public function testIsSsl()
    {
        $request = new ServerRequest();

        $request->env('HTTPS', 1);
        $this->assertTrue($request->is('ssl'));

        $request->env('HTTPS', 'on');
        $this->assertTrue($request->is('ssl'));

        $request->env('HTTPS', '1');
        $this->assertTrue($request->is('ssl'));

        $request->env('HTTPS', 'I am not empty');
        $this->assertFalse($request->is('ssl'));

        $request->env('HTTPS', 'off');
        $this->assertFalse($request->is('ssl'));

        $request->env('HTTPS', false);
        $this->assertFalse($request->is('ssl'));

        $request->env('HTTPS', '');
        $this->assertFalse($request->is('ssl'));
    }

    /**
     * Test getting request params with object properties.
     *
     * @return void
     */
    public function testMagicget()
    {
        $request = new ServerRequest();
        $request->params = ['controller' => 'posts', 'action' => 'view', 'plugin' => 'blogs'];

        $this->assertEquals('posts', $request->controller);
        $this->assertEquals('view', $request->action);
        $this->assertEquals('blogs', $request->plugin);
        $this->assertNull($request->banana);
    }

    /**
     * Test isset()/empty() with overloaded properties.
     *
     * @return void
     */
    public function testMagicisset()
    {
        $request = new ServerRequest();
        $request->params = [
            'controller' => 'posts',
            'action' => 'view',
            'plugin' => 'blogs',
        ];

        $this->assertTrue(isset($request->controller));
        $this->assertFalse(isset($request->notthere));
        $this->assertFalse(empty($request->controller));
    }

    /**
     * Test the array access implementation
     *
     * @return void
     */
    public function testArrayAccess()
    {
        $request = new ServerRequest();
        $request->params = ['controller' => 'posts', 'action' => 'view', 'plugin' => 'blogs'];

        $this->assertEquals('posts', $request['controller']);

        $request['slug'] = 'speedy-slug';
        $this->assertEquals('speedy-slug', $request->slug);
        $this->assertEquals('speedy-slug', $request['slug']);

        $this->assertTrue(isset($request['action']));
        $this->assertFalse(isset($request['wrong-param']));

        $this->assertTrue(isset($request['plugin']));
        unset($request['plugin']);
        $this->assertFalse(isset($request['plugin']));
        $this->assertNull($request['plugin']);
        $this->assertNull($request->plugin);

        $request = new ServerRequest(['url' => 'some/path?one=something&two=else']);
        $this->assertTrue(isset($request['url']['one']));

        $request->data = ['Post' => ['title' => 'something']];
        $this->assertEquals('something', $request['data']['Post']['title']);
    }

    /**
     * Test adding detectors and having them work.
     *
     * @return void
     */
    public function testAddDetector()
    {
        $request = new ServerRequest();

        ServerRequest::addDetector('closure', function ($request) {
            return true;
        });
        $this->assertTrue($request->is('closure'));

        ServerRequest::addDetector('get', function ($request) {
            return $request->env('REQUEST_METHOD') === 'GET';
        });
        $request->env('REQUEST_METHOD', 'GET');
        $this->assertTrue($request->is('get'));

        ServerRequest::addDetector('compare', ['env' => 'TEST_VAR', 'value' => 'something']);

        $request->env('TEST_VAR', 'something');
        $this->assertTrue($request->is('compare'), 'Value match failed.');

        $request->env('TEST_VAR', 'wrong');
        $this->assertFalse($request->is('compare'), 'Value mis-match failed.');

        ServerRequest::addDetector('compareCamelCase', ['env' => 'TEST_VAR', 'value' => 'foo']);

        $request->env('TEST_VAR', 'foo');
        $this->assertTrue($request->is('compareCamelCase'), 'Value match failed.');
        $this->assertTrue($request->is('comparecamelcase'), 'detectors should be case insensitive');
        $this->assertTrue($request->is('COMPARECAMELCASE'), 'detectors should be case insensitive');

        $request->env('TEST_VAR', 'not foo');
        $this->assertFalse($request->is('compareCamelCase'), 'Value match failed.');
        $this->assertFalse($request->is('comparecamelcase'), 'detectors should be case insensitive');
        $this->assertFalse($request->is('COMPARECAMELCASE'), 'detectors should be case insensitive');

        ServerRequest::addDetector('banana', ['env' => 'TEST_VAR', 'pattern' => '/^ban.*$/']);
        $request->env('TEST_VAR', 'banana');
        $this->assertTrue($request->isBanana());

        $request->env('TEST_VAR', 'wrong value');
        $this->assertFalse($request->isBanana());

        ServerRequest::addDetector('mobile', ['env' => 'HTTP_USER_AGENT', 'options' => ['Imagination']]);
        $request->env('HTTP_USER_AGENT', 'Imagination land');
        $this->assertTrue($request->isMobile());

        ServerRequest::addDetector('index', ['param' => 'action', 'value' => 'index']);

        $request->params['action'] = 'index';
        $request->clearDetectorCache();
        $this->assertTrue($request->isIndex());

        $request->params['action'] = 'add';
        $request->clearDetectorCache();
        $this->assertFalse($request->isIndex());

        ServerRequest::addDetector('callme', [$this, 'detectCallback']);
        $request->return = true;
        $request->clearDetectorCache();
        $this->assertTrue($request->isCallMe());

        ServerRequest::addDetector('extension', ['param' => '_ext', 'options' => ['pdf', 'png', 'txt']]);
        $request->params['_ext'] = 'pdf';
        $request->clearDetectorCache();
        $this->assertTrue($request->is('extension'));

        $request->params['_ext'] = 'exe';
        $request->clearDetectorCache();
        $this->assertFalse($request->isExtension());
    }

    /**
     * Helper function for testing callbacks.
     *
     * @param $request
     * @return bool
     */
    public function detectCallback($request)
    {
        return (bool)$request->return;
    }

    /**
     * Test getting headers
     *
     * @return void
     */
    public function testHeader()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-ca) AppleWebKit/534.8+ (KHTML, like Gecko) Version/5.0 Safari/533.16',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 1337,
            'HTTP_CONTENT_MD5' => 'abc123'
        ]]);

        $this->assertEquals($request->env('HTTP_HOST'), $request->header('host'));
        $this->assertEquals($request->env('HTTP_USER_AGENT'), $request->header('User-Agent'));
        $this->assertEquals($request->env('CONTENT_LENGTH'), $request->header('content-length'));
        $this->assertEquals($request->env('CONTENT_TYPE'), $request->header('content-type'));
        $this->assertEquals($request->env('HTTP_CONTENT_MD5'), $request->header('content-md5'));
    }

    /**
     * Test getting headers with psr7 methods
     *
     * @return void
     */
    public function testGetHeaders()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_HOST' => 'localhost',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 1337,
            'HTTP_CONTENT_MD5' => 'abc123',
            'HTTP_DOUBLE' => ['a', 'b']
        ]]);
        $headers = $request->getHeaders();
        $expected = [
            'Host' => ['localhost'],
            'Content-Type' => ['application/json'],
            'Content-Length' => [1337],
            'Content-Md5' => ['abc123'],
            'Double' => ['a', 'b'],
        ];
        $this->assertEquals($expected, $headers);
    }

    /**
     * Test hasHeader
     *
     * @return void
     */
    public function testHasHeader()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_HOST' => 'localhost',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 1337,
            'HTTP_CONTENT_MD5' => 'abc123',
            'HTTP_DOUBLE' => ['a', 'b']
        ]]);
        $this->assertTrue($request->hasHeader('Host'));
        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertTrue($request->hasHeader('Content-MD5'));
        $this->assertTrue($request->hasHeader('Double'));
        $this->assertFalse($request->hasHeader('Authorization'));
    }

    /**
     * Test getting headers with psr7 methods
     *
     * @return void
     */
    public function testGetHeader()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_HOST' => 'localhost',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 1337,
            'HTTP_CONTENT_MD5' => 'abc123',
            'HTTP_DOUBLE' => ['a', 'b']
        ]]);
        $this->assertEquals([], $request->getHeader('Not-there'));

        $expected = [$request->env('HTTP_HOST')];
        $this->assertEquals($expected, $request->getHeader('Host'));
        $this->assertEquals($expected, $request->getHeader('host'));
        $this->assertEquals($expected, $request->getHeader('HOST'));
        $this->assertEquals(['a', 'b'], $request->getHeader('Double'));
    }

    /**
     * Test getting headers with psr7 methods
     *
     * @return void
     */
    public function testGetHeaderLine()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_HOST' => 'localhost',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 1337,
            'HTTP_CONTENT_MD5' => 'abc123',
            'HTTP_DOUBLE' => ['a', 'b']
        ]]);
        $this->assertEquals('', $request->getHeaderLine('Authorization'));

        $expected = $request->env('CONTENT_LENGTH');
        $this->assertEquals($expected, $request->getHeaderLine('Content-Length'));
        $this->assertEquals($expected, $request->getHeaderLine('content-Length'));
        $this->assertEquals($expected, $request->getHeaderLine('ConTent-LenGth'));
        $this->assertEquals('a, b', $request->getHeaderLine('Double'));
    }

    /**
     * Test setting a header.
     *
     * @return void
     */
    public function testWithHeader()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_HOST' => 'localhost',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 1337,
            'HTTP_CONTENT_MD5' => 'abc123',
            'HTTP_DOUBLE' => ['a', 'b']
        ]]);
        $new = $request->withHeader('Content-Length', 999);
        $this->assertNotSame($new, $request);

        $this->assertEquals(1337, $request->getHeaderLine('Content-length'), 'old request is unchanged');
        $this->assertEquals(999, $new->getHeaderLine('Content-length'), 'new request is correct');
        $this->assertEquals(999, $new->header('Content-Length'));

        $new = $request->withHeader('Double', ['a']);
        $this->assertEquals(['a'], $new->getHeader('Double'), 'List values are overwritten');
        $this->assertEquals(['a'], $new->header('Double'), 'headers written in bc way.');
    }

    /**
     * Test adding a header.
     *
     * @return void
     */
    public function testWithAddedHeader()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_HOST' => 'localhost',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 1337,
            'HTTP_CONTENT_MD5' => 'abc123',
            'HTTP_DOUBLE' => ['a', 'b']
        ]]);
        $new = $request->withAddedHeader('Double', 'c');
        $this->assertNotSame($new, $request);

        $this->assertEquals('a, b', $request->getHeaderLine('Double'), 'old request is unchanged');
        $this->assertEquals('a, b, c', $new->getHeaderLine('Double'), 'new request is correct');
        $this->assertEquals(['a', 'b', 'c'], $new->header('Double'));

        $new = $request->withAddedHeader('Content-Length', 777);
        $this->assertEquals([1337, 777], $new->getHeader('Content-Length'), 'scalar values are appended');

        $new = $request->withAddedHeader('Content-Length', [123, 456]);
        $this->assertEquals([1337, 123, 456], $new->getHeader('Content-Length'), 'List values are merged');
    }

    /**
     * Test removing a header.
     *
     * @return void
     */
    public function testWithoutHeader()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_HOST' => 'localhost',
            'CONTENT_TYPE' => 'application/json',
            'CONTENT_LENGTH' => 1337,
            'HTTP_CONTENT_MD5' => 'abc123',
            'HTTP_DOUBLE' => ['a', 'b']
        ]]);
        $new = $request->withoutHeader('Content-Length', 999);
        $this->assertNotSame($new, $request);

        $this->assertEquals(1337, $request->getHeaderLine('Content-length'), 'old request is unchanged');
        $this->assertEquals('', $new->getHeaderLine('Content-length'), 'new request is correct');
        $this->assertNull($new->header('Content-Length'));
    }

    /**
     * Test accepts() with and without parameters
     *
     * @return void
     */
    public function testAccepts()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_ACCEPT' => 'text/xml,application/xml;q=0.9,application/xhtml+xml,text/html,text/plain,image/png'
        ]]);

        $result = $request->accepts();
        $expected = [
            'text/xml', 'application/xhtml+xml', 'text/html', 'text/plain', 'image/png', 'application/xml'
        ];
        $this->assertEquals($expected, $result, 'Content types differ.');

        $result = $request->accepts('text/html');
        $this->assertTrue($result);

        $result = $request->accepts('image/gif');
        $this->assertFalse($result);
    }

    /**
     * Test that accept header types are trimmed for comparisons.
     *
     * @return void
     */
    public function testAcceptWithWhitespace()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_ACCEPT' => 'text/xml  ,  text/html ,  text/plain,image/png'
        ]]);
        $result = $request->accepts();
        $expected = [
            'text/xml', 'text/html', 'text/plain', 'image/png'
        ];
        $this->assertEquals($expected, $result, 'Content types differ.');

        $this->assertTrue($request->accepts('text/html'));
    }

    /**
     * Content types from accepts() should respect the client's q preference values.
     *
     * @return void
     */
    public function testAcceptWithQvalueSorting()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_ACCEPT' => 'text/html;q=0.8,application/json;q=0.7,application/xml;q=1.0'
        ]]);
        $result = $request->accepts();
        $expected = ['application/xml', 'text/html', 'application/json'];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the raw parsing of accept headers into the q value formatting.
     *
     * @return void
     */
    public function testParseAcceptWithQValue()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_ACCEPT' => 'text/html;q=0.8,application/json;q=0.7,application/xml;q=1.0,image/png'
        ]]);
        $result = $request->parseAccept();
        $expected = [
            '1.0' => ['application/xml', 'image/png'],
            '0.8' => ['text/html'],
            '0.7' => ['application/json'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test parsing accept with a confusing accept value.
     *
     * @return void
     */
    public function testParseAcceptNoQValues()
    {
        $request = new ServerRequest(['environment' => [
            'HTTP_ACCEPT' => 'application/json, text/plain, */*'
        ]]);
        $result = $request->parseAccept();
        $expected = [
            '1.0' => ['application/json', 'text/plain', '*/*'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test parsing accept ignores index param
     *
     * @return void
     */
    public function testParseAcceptIgnoreAcceptExtensions()
    {
        $request = new ServerRequest(['environment' => [
            'url' => '/',
            'HTTP_ACCEPT' => 'application/json;level=1, text/plain, */*'
        ]], false);

        $result = $request->parseAccept();
        $expected = [
            '1.0' => ['application/json', 'text/plain', '*/*'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test that parsing accept headers with invalid syntax works.
     *
     * The header used is missing a q value for application/xml.
     *
     * @return void
     */
    public function testParseAcceptInvalidSyntax()
    {
        $request = new ServerRequest(['environment' => [
            'url' => '/',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;image/png,image/jpeg,image/*;q=0.9,*/*;q=0.8'
        ]], false);
        $result = $request->parseAccept();
        $expected = [
            '1.0' => ['text/html', 'application/xhtml+xml', 'application/xml', 'image/jpeg'],
            '0.9' => ['image/*'],
            '0.8' => ['*/*'],
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Test baseUrl and webroot with ModRewrite
     *
     * @return void
     */
    public function testBaseUrlAndWebrootWithModRewrite()
    {
        Configure::write('App.baseUrl', false);

        $_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
        $_SERVER['PHP_SELF'] = '/urlencode me/webroot/index.php';
        $_SERVER['PATH_INFO'] = '/posts/view/1';

        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/urlencode%20me', $request->base);
        $this->assertEquals('/urlencode%20me/', $request->webroot);
        $this->assertEquals('posts/view/1', $request->url);

        $_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
        $_SERVER['PHP_SELF'] = '/1.2.x.x/webroot/index.php';
        $_SERVER['PATH_INFO'] = '/posts/view/1';

        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/1.2.x.x', $request->base);
        $this->assertEquals('/1.2.x.x/', $request->webroot);
        $this->assertEquals('posts/view/1', $request->url);

        $_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/webroot';
        $_SERVER['PHP_SELF'] = '/index.php';
        $_SERVER['PATH_INFO'] = '/posts/add';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('', $request->base);
        $this->assertEquals('/', $request->webroot);
        $this->assertEquals('posts/add', $request->url);

        $_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/test/';
        $_SERVER['PHP_SELF'] = '/webroot/index.php';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('', $request->base);
        $this->assertEquals('/', $request->webroot);

        $_SERVER['DOCUMENT_ROOT'] = '/some/apps/where';
        $_SERVER['PHP_SELF'] = '/webroot/index.php';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('', $request->base);
        $this->assertEquals('/', $request->webroot);

        Configure::write('App.dir', 'auth');

        $_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
        $_SERVER['PHP_SELF'] = '/demos/webroot/index.php';

        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/demos', $request->base);
        $this->assertEquals('/demos/', $request->webroot);

        Configure::write('App.dir', 'code');

        $_SERVER['DOCUMENT_ROOT'] = '/Library/WebServer/Documents';
        $_SERVER['PHP_SELF'] = '/clients/PewterReport/webroot/index.php';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/clients/PewterReport', $request->base);
        $this->assertEquals('/clients/PewterReport/', $request->webroot);
    }

    /**
     * Test baseUrl with ModRewrite alias
     *
     * @return void
     */
    public function testBaseUrlwithModRewriteAlias()
    {
        $_SERVER['DOCUMENT_ROOT'] = '/home/aplusnur/public_html';
        $_SERVER['PHP_SELF'] = '/control/index.php';

        Configure::write('App.base', '/control');

        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/control', $request->base);
        $this->assertEquals('/control/', $request->webroot);

        Configure::write('App.base', false);
        Configure::write('App.dir', 'affiliate');
        Configure::write('App.webroot', 'newaffiliate');

        $_SERVER['DOCUMENT_ROOT'] = '/var/www/abtravaff/html';
        $_SERVER['PHP_SELF'] = '/newaffiliate/index.php';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('', $request->base);
        $this->assertEquals('/', $request->webroot);
    }

    /**
     * Test base, webroot, URL and here parsing when there is URL rewriting but
     * CakePHP gets called with index.php in URL nonetheless.
     *
     * Tests uri with
     * - index.php/
     * - index.php/
     * - index.php/apples/
     * - index.php/bananas/eat/tasty_banana
     *
     * @return void
     */
    public function testBaseUrlWithModRewriteAndIndexPhp()
    {
        $_SERVER['REQUEST_URI'] = '/cakephp/webroot/index.php';
        $_SERVER['PHP_SELF'] = '/cakephp/webroot/index.php';
        unset($_SERVER['PATH_INFO']);
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/cakephp', $request->base);
        $this->assertEquals('/cakephp/', $request->webroot);
        $this->assertEquals('', $request->url);
        $this->assertEquals('/cakephp/', $request->here);

        $_SERVER['REQUEST_URI'] = '/cakephp/webroot/index.php/';
        $_SERVER['PHP_SELF'] = '/cakephp/webroot/index.php/';
        $_SERVER['PATH_INFO'] = '/';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/cakephp', $request->base);
        $this->assertEquals('/cakephp/', $request->webroot);
        $this->assertEquals('', $request->url);
        $this->assertEquals('/cakephp/', $request->here);

        $_SERVER['REQUEST_URI'] = '/cakephp/webroot/index.php/apples';
        $_SERVER['PHP_SELF'] = '/cakephp/webroot/index.php/apples';
        $_SERVER['PATH_INFO'] = '/apples';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/cakephp', $request->base);
        $this->assertEquals('/cakephp/', $request->webroot);
        $this->assertEquals('apples', $request->url);
        $this->assertEquals('/cakephp/apples', $request->here);

        $_SERVER['REQUEST_URI'] = '/cakephp/webroot/index.php/melons/share/';
        $_SERVER['PHP_SELF'] = '/cakephp/webroot/index.php/melons/share/';
        $_SERVER['PATH_INFO'] = '/melons/share/';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/cakephp', $request->base);
        $this->assertEquals('/cakephp/', $request->webroot);
        $this->assertEquals('melons/share/', $request->url);
        $this->assertEquals('/cakephp/melons/share/', $request->here);

        $_SERVER['REQUEST_URI'] = '/cakephp/webroot/index.php/bananas/eat/tasty_banana';
        $_SERVER['PHP_SELF'] = '/cakephp/webroot/index.php/bananas/eat/tasty_banana';
        $_SERVER['PATH_INFO'] = '/bananas/eat/tasty_banana';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/cakephp', $request->base);
        $this->assertEquals('/cakephp/', $request->webroot);
        $this->assertEquals('bananas/eat/tasty_banana', $request->url);
        $this->assertEquals('/cakephp/bananas/eat/tasty_banana', $request->here);
    }

    /**
     * Test that even if mod_rewrite is on, and the url contains index.php
     * and there are numerous //s that the base/webroot is calculated correctly.
     *
     * @return void
     */
    public function testBaseUrlWithModRewriteAndExtraSlashes()
    {
        $_SERVER['REQUEST_URI'] = '/cakephp/webroot///index.php/bananas/eat';
        $_SERVER['PHP_SELF'] = '/cakephp/webroot///index.php/bananas/eat';
        $_SERVER['PATH_INFO'] = '/bananas/eat';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/cakephp', $request->base);
        $this->assertEquals('/cakephp/', $request->webroot);
        $this->assertEquals('bananas/eat', $request->url);
        $this->assertEquals('/cakephp/bananas/eat', $request->here);
    }

    /**
     * Test base, webroot, and URL parsing when there is no URL rewriting
     *
     * @return void
     */
    public function testBaseUrlWithNoModRewrite()
    {
        $_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites';
        $_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake/index.php';
        $_SERVER['PHP_SELF'] = '/cake/index.php/posts/index';
        $_SERVER['REQUEST_URI'] = '/cake/index.php/posts/index';

        Configure::write('App', [
            'dir' => APP_DIR,
            'webroot' => 'webroot',
            'base' => false,
            'baseUrl' => '/cake/index.php'
        ]);

        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/cake/index.php', $request->base);
        $this->assertEquals('/cake/webroot/', $request->webroot);
        $this->assertEquals('posts/index', $request->url);
    }

    /**
     * Test baseUrl and webroot with baseUrl
     *
     * @return void
     */
    public function testBaseUrlAndWebrootWithBaseUrl()
    {
        Configure::write('App.dir', 'App');
        Configure::write('App.baseUrl', '/App/webroot/index.php');

        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/App/webroot/index.php', $request->base);
        $this->assertEquals('/App/webroot/', $request->webroot);

        Configure::write('App.baseUrl', '/App/webroot/test.php');
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/App/webroot/test.php', $request->base);
        $this->assertEquals('/App/webroot/', $request->webroot);

        Configure::write('App.baseUrl', '/App/index.php');
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/App/index.php', $request->base);
        $this->assertEquals('/App/webroot/', $request->webroot);

        Configure::write('App.baseUrl', '/CakeBB/App/webroot/index.php');
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/CakeBB/App/webroot/index.php', $request->base);
        $this->assertEquals('/CakeBB/App/webroot/', $request->webroot);

        Configure::write('App.baseUrl', '/CakeBB/App/index.php');
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/CakeBB/App/index.php', $request->base);
        $this->assertEquals('/CakeBB/App/webroot/', $request->webroot);

        Configure::write('App.baseUrl', '/CakeBB/index.php');
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/CakeBB/index.php', $request->base);
        $this->assertEquals('/CakeBB/webroot/', $request->webroot);

        Configure::write('App.baseUrl', '/dbhauser/index.php');
        $_SERVER['DOCUMENT_ROOT'] = '/kunden/homepages/4/d181710652/htdocs/joomla';
        $_SERVER['SCRIPT_FILENAME'] = '/kunden/homepages/4/d181710652/htdocs/joomla/dbhauser/index.php';
        $request = ServerRequestFactory::fromGlobals();

        $this->assertEquals('/dbhauser/index.php', $request->base);
        $this->assertEquals('/dbhauser/webroot/', $request->webroot);
    }

    /**
     * Test baseUrl with no rewrite and using the top level index.php.
     *
     * @return void
     */
    public function testBaseUrlNoRewriteTopLevelIndex()
    {
        Configure::write('App.baseUrl', '/index.php');
        $_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/cake_dev';
        $_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake_dev/index.php';

        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/index.php', $request->base);
        $this->assertEquals('/webroot/', $request->webroot);
    }

    /**
     * Check that a sub-directory containing app|webroot doesn't get mishandled when re-writing is off.
     *
     * @return void
     */
    public function testBaseUrlWithAppAndWebrootInDirname()
    {
        Configure::write('App.baseUrl', '/approval/index.php');
        $_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/';
        $_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/approval/index.php';

        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/approval/index.php', $request->base);
        $this->assertEquals('/approval/webroot/', $request->webroot);

        Configure::write('App.baseUrl', '/webrootable/index.php');
        $_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/';
        $_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/webrootable/index.php';

        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/webrootable/index.php', $request->base);
        $this->assertEquals('/webrootable/webroot/', $request->webroot);
    }

    /**
     * Test baseUrl with no rewrite, and using the app/webroot/index.php file as is normal with virtual hosts.
     *
     * @return void
     */
    public function testBaseUrlNoRewriteWebrootIndex()
    {
        Configure::write('App.baseUrl', '/index.php');
        $_SERVER['DOCUMENT_ROOT'] = '/Users/markstory/Sites/cake_dev/webroot';
        $_SERVER['SCRIPT_FILENAME'] = '/Users/markstory/Sites/cake_dev/webroot/index.php';

        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/index.php', $request->base);
        $this->assertEquals('/', $request->webroot);
    }

    /**
     * Test that a request with a . in the main GET parameter is filtered out.
     * PHP changes GET parameter keys containing dots to _.
     *
     * @return void
     */
    public function testGetParamsWithDot()
    {
        $_GET = [];
        $_GET['/posts/index/add_add'] = '';
        $_SERVER['PHP_SELF'] = '/webroot/index.php';
        $_SERVER['REQUEST_URI'] = '/posts/index/add.add';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('', $request->base);
        $this->assertEquals([], $request->query);

        $_GET = [];
        $_GET['/cake_dev/posts/index/add_add'] = '';
        $_SERVER['PHP_SELF'] = '/cake_dev/webroot/index.php';
        $_SERVER['REQUEST_URI'] = '/cake_dev/posts/index/add.add';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/cake_dev', $request->base);
        $this->assertEquals([], $request->query);
    }

    /**
     * Test that a request with urlencoded bits in the main GET parameter are filtered out.
     *
     * @return void
     */
    public function testGetParamWithUrlencodedElement()
    {
        $_GET = [];
        $_GET['/posts/add/∂∂'] = '';
        $_SERVER['PHP_SELF'] = '/webroot/index.php';
        $_SERVER['REQUEST_URI'] = '/posts/add/%E2%88%82%E2%88%82';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('', $request->base);
        $this->assertEquals([], $request->query);

        $_GET = [];
        $_GET['/cake_dev/posts/add/∂∂'] = '';
        $_SERVER['PHP_SELF'] = '/cake_dev/webroot/index.php';
        $_SERVER['REQUEST_URI'] = '/cake_dev/posts/add/%E2%88%82%E2%88%82';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('/cake_dev', $request->base);
        $this->assertEquals([], $request->query);
    }

    /**
     * Generator for environment configurations
     *
     * @return array Environment array
     */
    public static function environmentGenerator()
    {
        return [
            [
                'IIS - No rewrite base path',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'SERVER' => [
                        'SCRIPT_NAME' => '/index.php',
                        'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot',
                        'QUERY_STRING' => '',
                        'REQUEST_URI' => '/index.php',
                        'URL' => '/index.php',
                        'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php',
                        'ORIG_PATH_INFO' => '/index.php',
                        'PATH_INFO' => '',
                        'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\index.php',
                        'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot',
                        'PHP_SELF' => '/index.php',
                    ],
                ],
                [
                    'base' => '/index.php',
                    'webroot' => '/webroot/',
                    'url' => ''
                ],
            ],
            [
                'IIS - No rewrite with path, no PHP_SELF',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/index.php?',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'SERVER' => [
                        'QUERY_STRING' => '/posts/add',
                        'REQUEST_URI' => '/index.php?/posts/add',
                        'PHP_SELF' => '',
                        'URL' => '/index.php?/posts/add',
                        'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot',
                        'argv' => ['/posts/add'],
                        'argc' => 1
                    ],
                ],
                [
                    'url' => 'posts/add',
                    'base' => '/index.php?',
                    'webroot' => '/webroot/'
                ]
            ],
            [
                'IIS - No rewrite sub dir 2',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/site/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot',
                    ],
                    'SERVER' => [
                        'SCRIPT_NAME' => '/site/index.php',
                        'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot',
                        'QUERY_STRING' => '',
                        'REQUEST_URI' => '/site/index.php',
                        'URL' => '/site/index.php',
                        'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\site\\index.php',
                        'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot',
                        'PHP_SELF' => '/site/index.php',
                        'argv' => [],
                        'argc' => 0
                    ],
                ],
                [
                    'url' => '',
                    'base' => '/site/index.php',
                    'webroot' => '/site/webroot/'
                ],
            ],
            [
                'IIS - No rewrite sub dir 2 with path',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/site/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'GET' => ['/posts/add' => ''],
                    'SERVER' => [
                        'SCRIPT_NAME' => '/site/index.php',
                        'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot',
                        'QUERY_STRING' => '/posts/add',
                        'REQUEST_URI' => '/site/index.php/posts/add',
                        'URL' => '/site/index.php/posts/add',
                        'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\site\\index.php',
                        'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot',
                        'PHP_SELF' => '/site/index.php/posts/add',
                        'argv' => ['/posts/add'],
                        'argc' => 1
                    ],
                ],
                [
                    'url' => 'posts/add',
                    'base' => '/site/index.php',
                    'webroot' => '/site/webroot/'
                ]
            ],
            [
                'Apache - No rewrite, document root set to webroot, requesting path',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'SERVER' => [
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/App/webroot',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/App/webroot/index.php',
                        'QUERY_STRING' => '',
                        'REQUEST_URI' => '/index.php/posts/index',
                        'SCRIPT_NAME' => '/index.php',
                        'PATH_INFO' => '/posts/index',
                        'PHP_SELF' => '/index.php/posts/index',
                    ],
                ],
                [
                    'url' => 'posts/index',
                    'base' => '/index.php',
                    'webroot' => '/'
                ],
            ],
            [
                'Apache - No rewrite, document root set to webroot, requesting root',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'SERVER' => [
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/App/webroot',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/App/webroot/index.php',
                        'QUERY_STRING' => '',
                        'REQUEST_URI' => '/index.php',
                        'SCRIPT_NAME' => '/index.php',
                        'PATH_INFO' => '',
                        'PHP_SELF' => '/index.php',
                    ],
                ],
                [
                    'url' => '',
                    'base' => '/index.php',
                    'webroot' => '/'
                ],
            ],
            [
                'Apache - No rewrite, document root set above top level cake dir, requesting path',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/site/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
                        'REQUEST_URI' => '/site/index.php/posts/index',
                        'SCRIPT_NAME' => '/site/index.php',
                        'PATH_INFO' => '/posts/index',
                        'PHP_SELF' => '/site/index.php/posts/index',
                    ],
                ],
                [
                    'url' => 'posts/index',
                    'base' => '/site/index.php',
                    'webroot' => '/site/webroot/',
                ],
            ],
            [
                'Apache - No rewrite, document root set above top level cake dir, request root, no PATH_INFO',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/site/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
                        'REQUEST_URI' => '/site/index.php/',
                        'SCRIPT_NAME' => '/site/index.php',
                        'PHP_SELF' => '/site/index.php/',
                    ],
                ],
                [
                    'url' => '',
                    'base' => '/site/index.php',
                    'webroot' => '/site/webroot/',
                ],
            ],
            [
                'Apache - No rewrite, document root set above top level cake dir, request path, with GET',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/site/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'GET' => ['a' => 'b', 'c' => 'd'],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
                        'REQUEST_URI' => '/site/index.php/posts/index?a=b&c=d',
                        'SCRIPT_NAME' => '/site/index.php',
                        'PATH_INFO' => '/posts/index',
                        'PHP_SELF' => '/site/index.php/posts/index',
                        'QUERY_STRING' => 'a=b&c=d'
                    ],
                ],
                [
                    'urlParams' => ['a' => 'b', 'c' => 'd'],
                    'url' => 'posts/index',
                    'base' => '/site/index.php',
                    'webroot' => '/site/webroot/',
                ],
            ],
            [
                'Apache - w/rewrite, document root set above top level cake dir, request root, no PATH_INFO',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => false,
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
                        'REQUEST_URI' => '/site/',
                        'SCRIPT_NAME' => '/site/webroot/index.php',
                        'PHP_SELF' => '/site/webroot/index.php',
                    ],
                ],
                [
                    'url' => '',
                    'base' => '/site',
                    'webroot' => '/site/',
                ],
            ],
            [
                'Apache - w/rewrite, document root above top level cake dir, request root, no PATH_INFO/REQUEST_URI',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => false,
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
                        'SCRIPT_NAME' => '/site/webroot/index.php',
                        'PHP_SELF' => '/site/webroot/index.php',
                        'PATH_INFO' => null,
                        'REQUEST_URI' => null,
                    ],
                ],
                [
                    'url' => '',
                    'base' => '/site',
                    'webroot' => '/site/',
                ],
            ],
            [
                'Apache - w/rewrite, document root set to webroot, request root, no PATH_INFO/REQUEST_URI',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => false,
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/webroot',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/webroot/index.php',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php',
                        'PATH_INFO' => null,
                        'REQUEST_URI' => null,
                    ],
                ],
                [
                    'url' => '',
                    'base' => '',
                    'webroot' => '/',
                ],
            ],
            [
                'Apache - w/rewrite, document root set above top level cake dir, request root, absolute REQUEST_URI',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => false,
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/index.php',
                        'REQUEST_URI' => '/site/posts/index',
                        'SCRIPT_NAME' => '/site/webroot/index.php',
                        'PHP_SELF' => '/site/webroot/index.php',
                    ],
                ],
                [
                    'url' => 'posts/index',
                    'base' => '/site',
                    'webroot' => '/site/',
                ],
            ],
            [
                'Nginx - w/rewrite, document root set to webroot, request root, no PATH_INFO',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => false,
                        'dir' => 'TestApp',
                        'webroot' => 'webroot'
                    ],
                    'GET' => ['/posts/add' => ''],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/webroot',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/webroot/index.php',
                        'SCRIPT_NAME' => '/index.php',
                        'QUERY_STRING' => '/posts/add&',
                        'PHP_SELF' => '/index.php',
                        'PATH_INFO' => null,
                        'REQUEST_URI' => '/posts/add',
                    ],
                ],
                [
                    'url' => 'posts/add',
                    'base' => '',
                    'webroot' => '/',
                    'urlParams' => []
                ],
            ],
            [
                'Nginx - w/rewrite, document root set above top level cake dir, request root, no PATH_INFO, base parameter set',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => false,
                        'dir' => 'app',
                        'webroot' => 'webroot'
                    ],
                    'GET' => ['/site/posts/add' => ''],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/App/webroot/index.php',
                        'SCRIPT_NAME' => '/site/app/webroot/index.php',
                        'QUERY_STRING' => '/site/posts/add&',
                        'PHP_SELF' => '/site/webroot/index.php',
                        'PATH_INFO' => null,
                        'REQUEST_URI' => '/site/posts/add',
                    ],
                ],
                [
                    'url' => 'posts/add',
                    'base' => '/site',
                    'webroot' => '/site/',
                    'urlParams' => []
                ],
            ],
        ];
    }

    /**
     * Test environment detection
     *
     * @dataProvider environmentGenerator
     * @param $name
     * @param $env
     * @param $expected
     * @return void
     */
    public function testEnvironmentDetection($name, $env, $expected)
    {
        $_GET = [];
        $this->_loadEnvironment($env);

        $request = ServerRequestFactory::fromGlobals();
        $uri = $request->getUri();

        $this->assertEquals($expected['url'], $request->url, "URL is incorrect");
        $this->assertEquals('/' . $expected['url'], $uri->getPath(), 'Uri->getPath() is incorrect');

        $this->assertEquals($expected['base'], $request->base, "base is incorrect");
        $this->assertEquals($expected['base'], $request->getAttribute('base'), "base is incorrect");

        $this->assertEquals($expected['webroot'], $request->webroot, "webroot error");
        $this->assertEquals($expected['webroot'], $request->getAttribute('webroot'), "webroot is incorrect");

        if (isset($expected['urlParams'])) {
            $this->assertEquals($expected['urlParams'], $request->query, "GET param mismatch");
        }
    }

    /**
     * Test the query() method
     *
     * @return void
     */
    public function testQuery()
    {
        $array = [
            'query' => ['foo' => 'bar', 'zero' => '0']
        ];
        $request = new ServerRequest($array);

        $this->assertSame('bar', $request->query('foo'));
        $this->assertSame('0', $request->query('zero'));
        $this->assertNull($request->query('imaginary'));
        $this->assertSame($array['query'], $request->query());
    }

    /**
     * Test the getQuery() method
     *
     * @return void
     */
    public function testGetQuery()
    {
        $array = [
            'query' => [
                'foo' => 'bar',
                'zero' => '0',
                'test' => [
                    'foo', 'bar'
                ]
            ]
        ];
        $request = new ServerRequest($array);

        $this->assertEquals([
            'foo' => 'bar',
            'zero' => '0',
            'test' => [
                'foo', 'bar'
            ]
        ], $request->getQuery());

        $this->assertSame('bar', $request->getQuery('foo'));
        $this->assertSame('0', $request->getQuery('zero'));
        $this->assertNull($request->getQuery('imaginary'));
        $this->assertSame('default', $request->getQuery('imaginary', 'default'));
        $this->assertFalse($request->getQuery('imaginary', false));

        $this->assertSame(['foo', 'bar'], $request->getQuery('test'));
        $this->assertSame('bar', $request->getQuery('test.1'));
        $this->assertNull($request->getQuery('test.2'));
        $this->assertSame('default', $request->getQuery('test.2', 'default'));
    }

    /**
     * Test getQueryParams
     *
     * @return void
     */
    public function testGetQueryParams()
    {
        $get = [
            'test' => ['foo', 'bar'],
            'key' => 'value'
        ];

        $request = new ServerRequest([
            'query' => $get
        ]);
        $this->assertSame($get, $request->getQueryParams());
    }

    /**
     * Test withQueryParams and immutability
     *
     * @return void
     */
    public function testWithQueryParams()
    {
        $get = [
            'test' => ['foo', 'bar'],
            'key' => 'value'
        ];

        $request = new ServerRequest([
            'query' => $get
        ]);
        $new = $request->withQueryParams(['new' => 'data']);
        $this->assertSame($get, $request->getQueryParams());
        $this->assertSame(['new' => 'data'], $new->getQueryParams());
    }

    /**
     * Test getServerParams
     *
     * @return void
     */
    public function testGetServerParams()
    {
        $vars = [
            'REQUEST_METHOD' => 'PUT',
            'HTTPS' => 'on',
        ];

        $request = new ServerRequest([
            'environment' => $vars
        ]);
        $expected = $vars + [
            'CONTENT_TYPE' => null,
            'HTTP_CONTENT_TYPE' => null,
            'ORIGINAL_REQUEST_METHOD' => 'PUT',
        ];
        $this->assertSame($expected, $request->getServerParams());
    }

    /**
     * Test using param()
     *
     * @return void
     */
    public function testReadingParams()
    {
        $request = new ServerRequest();
        $request->addParams([
            'controller' => 'posts',
            'admin' => true,
            'truthy' => 1,
            'zero' => '0',
        ]);
        $this->assertFalse($request->param('not_set'));
        $this->assertTrue($request->param('admin'));
        $this->assertSame(1, $request->param('truthy'));
        $this->assertSame('posts', $request->param('controller'));
        $this->assertSame('0', $request->param('zero'));
    }

    /**
     * Test the data() method reading
     *
     * @return void
     */
    public function testGetData()
    {
        $post = [
            'Model' => [
                'field' => 'value'
            ]
        ];
        $request = new ServerRequest(compact('post'));
        $this->assertEquals($post['Model'], $request->data('Model'));
        $this->assertEquals($post['Model'], $request->getData('Model'));

        $this->assertEquals($post, $request->data());
        $this->assertEquals($post, $request->getData());

        $this->assertNull($request->data('Model.imaginary'));
        $this->assertNull($request->getData('Model.imaginary'));

        $this->assertSame('value', $request->getData('Model.field', 'default'));
        $this->assertSame('default', $request->getData('Model.imaginary', 'default'));
    }

    /**
     * Test that getData() doesn't fail on scalar data.
     *
     * @return void
     */
    public function testGetDataOnStringData()
    {
        $post = 'strange, but could happen';
        $request = new ServerRequest(compact('post'));
        $this->assertNull($request->getData('Model'));
        $this->assertNull($request->getData('Model.field'));
    }

    /**
     * Test writing with data()
     *
     * @return void
     */
    public function testDataWriting()
    {
        $_POST['data'] = [
            'Model' => [
                'field' => 'value'
            ]
        ];
        $request = new ServerRequest();
        $result = $request->data('Model.new_value', 'new value');
        $this->assertSame($result, $request, 'Return was not $this');

        $this->assertEquals('new value', $request->data['Model']['new_value']);

        $request->data('Post.title', 'New post')->data('Comment.1.author', 'Mark');
        $this->assertEquals('New post', $request->data['Post']['title']);
        $this->assertEquals('Mark', $request->data['Comment']['1']['author']);
    }

    /**
     * Test writing falsey values.
     *
     * @return void
     */
    public function testDataWritingFalsey()
    {
        $request = new ServerRequest();

        $request->data('Post.null', null);
        $this->assertNull($request->data['Post']['null']);

        $request->data('Post.false', false);
        $this->assertFalse($request->data['Post']['false']);

        $request->data('Post.zero', 0);
        $this->assertSame(0, $request->data['Post']['zero']);

        $request->data('Post.empty', '');
        $this->assertSame('', $request->data['Post']['empty']);
    }

    /**
     * Test reading params
     *
     * @dataProvider paramReadingDataProvider
     */
    public function testGetParam($toRead, $expected)
    {
        $request = new ServerRequest('/');
        $request->addParams([
            'action' => 'index',
            'foo' => 'bar',
            'baz' => [
                'a' => [
                    'b' => 'c',
                ],
            ],
            'admin' => true,
            'truthy' => 1,
            'zero' => '0',
        ]);
        $this->assertSame($expected, $request->param($toRead));
        $this->assertSame($expected, $request->getParam($toRead));
    }

    /**
     * Test getParam returning a default value.
     *
     * @return void
     */
    public function testGetParamDefault()
    {
        $request = new ServerRequest([
            'params' => [
                'controller' => 'Articles',
                'null' => null,
            ]
        ]);
        $this->assertSame('Articles', $request->getParam('controller', 'default'));
        $this->assertSame('default', $request->getParam('null', 'default'));
        $this->assertNull($request->getParam('unset', null));
        $this->assertFalse($request->getParam('unset'));
    }

    /**
     * Data provider for testing reading values with ServerRequestFactory::param()
     *
     * @return array
     */
    public function paramReadingDataProvider()
    {
        return [
            [
                'action',
                'index',
            ],
            [
                'baz',
                [
                    'a' => [
                        'b' => 'c',
                    ],
                ],
            ],
            [
                'baz.a.b',
                'c',
            ],
            [
                'does_not_exist',
                false,
            ],
            [
                'admin',
                true,
            ],
            [
                'truthy',
                1,
            ],
            [
                'zero',
                '0',
            ],
        ];
    }

    /**
     * test writing request params with param()
     *
     * @return void
     */
    public function testParamWriting()
    {
        $request = new ServerRequest('/');
        $request->addParams([
            'action' => 'index',
        ]);

        $this->assertInstanceOf('Cake\Network\Request', $request->param('some', 'thing'), 'Method has not returned $this');

        $request->param('Post.null', null);
        $this->assertNull($request->params['Post']['null']);

        $request->param('Post.false', false);
        $this->assertFalse($request->params['Post']['false']);

        $request->param('Post.zero', 0);
        $this->assertSame(0, $request->params['Post']['zero']);

        $request->param('Post.empty', '');
        $this->assertSame('', $request->params['Post']['empty']);

        $this->assertSame('index', $request->action);
        $request->param('action', 'edit');
        $this->assertSame('edit', $request->action);
    }

    /**
     * Test accept language
     *
     * @return void
     */
    public function testAcceptLanguage()
    {
        $request = new ServerRequest();

        // Weird language
        $request->env('HTTP_ACCEPT_LANGUAGE', 'inexistent,en-ca');
        $result = $request->acceptLanguage();
        $this->assertEquals(['inexistent', 'en-ca'], $result, 'Languages do not match');

        // No qualifier
        $request->env('HTTP_ACCEPT_LANGUAGE', 'es_mx,en_ca');
        $result = $request->acceptLanguage();
        $this->assertEquals(['es-mx', 'en-ca'], $result, 'Languages do not match');

        // With qualifier
        $request->env('HTTP_ACCEPT_LANGUAGE', 'en-US,en;q=0.8,pt-BR;q=0.6,pt;q=0.4');
        $result = $request->acceptLanguage();
        $this->assertEquals(['en-us', 'en', 'pt-br', 'pt'], $result, 'Languages do not match');

        // With spaces
        $request->env('HTTP_ACCEPT_LANGUAGE', 'da, en-gb;q=0.8, en;q=0.7');
        $result = $request->acceptLanguage();
        $this->assertEquals(['da', 'en-gb', 'en'], $result, 'Languages do not match');

        // Checking if requested
        $request->env('HTTP_ACCEPT_LANGUAGE', 'es_mx,en_ca');
        $result = $request->acceptLanguage();

        $result = $request->acceptLanguage('en-ca');
        $this->assertTrue($result);

        $result = $request->acceptLanguage('en-CA');
        $this->assertTrue($result);

        $result = $request->acceptLanguage('en-us');
        $this->assertFalse($result);

        $result = $request->acceptLanguage('en-US');
        $this->assertFalse($result);
    }

    /**
     * Test the here() method
     *
     * @return void
     */
    public function testHere()
    {
        Configure::write('App.base', '/base_path');
        $q = ['test' => 'value'];
        $request = new ServerRequest([
            'query' => $q,
            'url' => '/posts/add/1/value',
            'base' => '/base_path'
        ]);

        $result = $request->here();
        $this->assertEquals('/base_path/posts/add/1/value?test=value', $result);

        $result = $request->here(false);
        $this->assertEquals('/posts/add/1/value?test=value', $result);

        $request = new ServerRequest([
            'url' => '/posts/base_path/1/value',
            'query' => ['test' => 'value'],
            'base' => '/base_path'
        ]);
        $result = $request->here();
        $this->assertEquals('/base_path/posts/base_path/1/value?test=value', $result);

        $result = $request->here(false);
        $this->assertEquals('/posts/base_path/1/value?test=value', $result);
    }

    /**
     * Test the here() with space in URL
     *
     * @return void
     */
    public function testHereWithSpaceInUrl()
    {
        Configure::write('App.base', '');
        $_GET = ['/admin/settings/settings/prefix/Access_Control' => ''];
        $request = new ServerRequest('/admin/settings/settings/prefix/Access%20Control');

        $result = $request->here();
        $this->assertEquals('/admin/settings/settings/prefix/Access%20Control', $result);
    }

    /**
     * Test the input() method.
     *
     * @return void
     */
    public function testSetInput()
    {
        $request = new ServerRequest();

        $request->setInput('I came from setInput');
        $result = $request->input();
        $this->assertEquals('I came from setInput', $result);
    }

    /**
     * Test the input() method.
     *
     * @return void
     */
    public function testInput()
    {
        $request = new ServerRequest([
            'input' => 'I came from stdin'
        ]);
        $result = $request->input();
        $this->assertEquals('I came from stdin', $result);
    }

    /**
     * Test input() decoding.
     *
     * @return void
     */
    public function testInputDecode()
    {
        $request = new ServerRequest([
            'input' => '{"name":"value"}'
        ]);

        $result = $request->input('json_decode');
        $this->assertEquals(['name' => 'value'], (array)$result);
    }

    /**
     * Test input() decoding with additional arguments.
     *
     * @return void
     */
    public function testInputDecodeExtraParams()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<post>
	<title id="title">Test</title>
</post>
XML;

        $request = new ServerRequest([
            'input' => $xml
        ]);

        $result = $request->input('Cake\Utility\Xml::build', ['return' => 'domdocument']);
        $this->assertInstanceOf('DOMDocument', $result);
        $this->assertEquals(
            'Test',
            $result->getElementsByTagName('title')->item(0)->childNodes->item(0)->wholeText
        );
    }

    /**
     * Test getBody
     *
     * @return void
     */
    public function testGetBody()
    {
        $request = new ServerRequest([
            'input' => 'key=val&some=data'
        ]);
        $result = $request->getBody();
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $result);
        $this->assertEquals('key=val&some=data', $result->getContents());
    }

    /**
     * Test withBody
     *
     * @return void
     */
    public function testWithBody()
    {
        $request = new ServerRequest([
            'input' => 'key=val&some=data'
        ]);
        $body = $this->getMockBuilder('Psr\Http\Message\StreamInterface')->getMock();
        $new = $request->withBody($body);
        $this->assertNotSame($new, $request);
        $this->assertNotSame($body, $request->getBody());
        $this->assertSame($body, $new->getBody());
    }

    /**
     * Test getUri
     *
     * @return void
     */
    public function testGetUri()
    {
        $request = new ServerRequest(['url' => 'articles/view/3']);
        $this->assertEquals('articles/view/3', $request->url);

        $result = $request->getUri();
        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $result);
        $this->assertEquals('/articles/view/3', $result->getPath());
    }

    /**
     * Test withUri
     *
     * @return void
     */
    public function testWithUri()
    {
        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'example.com',
            ],
            'url' => 'articles/view/3'
        ]);
        $uri = $this->getMockBuilder('Psr\Http\Message\UriInterface')->getMock();
        $new = $request->withUri($uri);
        $this->assertNotSame($new, $request);
        $this->assertNotSame($uri, $request->getUri());
        $this->assertSame($uri, $new->getUri());
        $this->assertSame('articles/view/3', $new->url);
        $this->assertSame('articles/view/3', $request->url);
        $this->assertSame('example.com', $new->getHeaderLine('Host'));
    }

    /**
     * Test withUri() and preserveHost
     *
     * @return void
     */
    public function testWithUriPreserveHost()
    {
        $request = new ServerRequest([
            'environment' => [
                'HTTP_HOST' => 'localhost'
            ],
            'url' => 'articles/view/3'
        ]);
        $uri = new Uri();
        $uri = $uri->withHost('example.com')
            ->withPort(123)
            ->withPath('articles/view/3');
        $new = $request->withUri($uri, false);

        $this->assertNotSame($new, $request);
        $this->assertSame('example.com:123', $new->getHeaderLine('Host'));
    }

    /**
     * Test withUri() and preserveHost missing the host header
     *
     * @return void
     */
    public function testWithUriPreserveHostNoHostHeader()
    {
        $request = new ServerRequest([
            'url' => 'articles/view/3'
        ]);
        $uri = new Uri();
        $uri = $uri->withHost('example.com')
            ->withPort(123)
            ->withPath('articles/view/3');
        $new = $request->withUri($uri, false);

        $this->assertSame('example.com:123', $new->getHeaderLine('Host'));
    }

    /**
     * Test is('requested') and isRequested()
     *
     * @return void
     */
    public function testIsRequested()
    {
        $request = new ServerRequest();
        $request->addParams([
            'controller' => 'posts',
            'action' => 'index',
            'plugin' => null,
            'requested' => 1
        ]);
        $this->assertTrue($request->is('requested'));
        $this->assertTrue($request->isRequested());

        $request = new ServerRequest();
        $request->addParams([
            'controller' => 'posts',
            'action' => 'index',
            'plugin' => null,
        ]);
        $this->assertFalse($request->is('requested'));
        $this->assertFalse($request->isRequested());
    }

    /**
     * Test the cookie() method.
     *
     * @return void
     */
    public function testGetCookie()
    {
        $request = new ServerRequest([
            'cookies' => [
                'testing' => 'A value in the cookie',
                'user' => [
                    'remember' => '1'
                ]
            ]
        ]);
        $this->assertEquals('A value in the cookie', $request->cookie('testing'));
        $this->assertEquals('A value in the cookie', $request->getCookie('testing'));

        $this->assertNull($request->cookie('not there'));
        $this->assertNull($request->getCookie('not there'));
        $this->assertSame('default', $request->getCookie('not there', 'default'));

        $this->assertSame('1', $request->getCookie('user.remember'));
        $this->assertSame('1', $request->getCookie('user.remember', 'default'));
        $this->assertSame('default', $request->getCookie('user.not there', 'default'));
    }

    /**
     * Test getCookieParams()
     *
     * @return void
     */
    public function testGetCookieParams()
    {
        $cookies = [
            'testing' => 'A value in the cookie'
        ];
        $request = new ServerRequest(['cookies' => $cookies]);
        $this->assertSame($cookies, $request->getCookieParams());
    }

    /**
     * Test withCookieParams()
     *
     * @return void
     */
    public function testWithCookieParams()
    {
        $cookies = [
            'testing' => 'A value in the cookie'
        ];
        $request = new ServerRequest(['cookies' => $cookies]);
        $new = $request->withCookieParams(['remember_me' => 1]);
        $this->assertNotSame($new, $request);
        $this->assertSame($cookies, $request->getCookieParams());
        $this->assertSame(['remember_me' => 1], $new->getCookieParams());
    }

    /**
     * TestAllowMethod
     *
     * @return void
     */
    public function testAllowMethod()
    {
        $request = new ServerRequest(['environment' => [
            'url' => '/posts/edit/1',
            'REQUEST_METHOD' => 'PUT'
        ]]);

        $this->assertTrue($request->allowMethod('put'));

        $request->env('REQUEST_METHOD', 'DELETE');
        $this->assertTrue($request->allowMethod(['post', 'delete']));
    }

    /**
     * Test allowMethod throwing exception
     *
     * @return void
     */
    public function testAllowMethodException()
    {
        $request = new ServerRequest([
            'url' => '/posts/edit/1',
            'environment' => ['REQUEST_METHOD' => 'PUT']
        ]);

        try {
            $request->allowMethod(['POST', 'DELETE']);
            $this->fail('An expected exception has not been raised.');
        } catch (MethodNotAllowedException $e) {
            $this->assertEquals(['Allow' => 'POST, DELETE'], $e->responseHeader());
        }

        $this->expectException(MethodNotAllowedException::class);

        $request->allowMethod('POST');
    }

    /**
     * Tests getting the sessions from the request
     *
     * @return void
     */
    public function testSession()
    {
        $session = new Session;
        $request = new ServerRequest(['session' => $session]);
        $this->assertSame($session, $request->session());

        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals($session, $request->session());
    }

    /**
     * Test the content type method.
     *
     * @return void
     */
    public function testContentType()
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('application/json', $request->contentType());

        $_SERVER['CONTENT_TYPE'] = 'application/xml';
        $request = ServerRequestFactory::fromGlobals();
        $this->assertEquals('application/xml', $request->contentType(), 'prefer non http header.');
    }

    /**
     * Tests that overriding the method to GET will clean all request
     * data, to better simulate a GET request.
     *
     * @return void
     */
    public function testMethodOverrideEmptyData()
    {
        $post = ['_method' => 'GET', 'foo' => 'bar'];
        $request = new ServerRequest([
            'post' => $post,
            'environment' => ['REQUEST_METHOD' => 'POST']
        ]);
        $this->assertEmpty($request->data);

        $post = ['_method' => 'GET', 'foo' => 'bar'];
        $request = new ServerRequest([
            'post' => ['foo' => 'bar'],
            'environment' => [
                'REQUEST_METHOD' => 'POST',
                'HTTP_X_HTTP_METHOD_OVERRIDE' => 'GET'
            ]
        ]);
        $this->assertEmpty($request->data);
    }

    /**
     * Test updating params in a psr7 fashion.
     *
     * @return void
     */
    public function testWithParam()
    {
        $request = new ServerRequest([
            'params' => ['controller' => 'Articles']
        ]);
        $result = $request->withParam('action', 'view');
        $this->assertNotSame($result, $request, 'New instance should be made');
        $this->assertFalse($request->param('action'), 'No side-effect on original');
        $this->assertSame('view', $result->param('action'));

        $result = $request->withParam('action', 'index')
            ->withParam('plugin', 'DebugKit')
            ->withParam('prefix', 'Admin');
        $this->assertNotSame($result, $request, 'New instance should be made');
        $this->assertFalse($request->param('action'), 'No side-effect on original');
        $this->assertSame('index', $result->param('action'));
        $this->assertSame('DebugKit', $result->param('plugin'));
        $this->assertSame('Admin', $result->param('prefix'));
    }

    /**
     * Test getting the parsed body parameters.
     *
     * @return void
     */
    public function testGetParsedBody()
    {
        $data = ['title' => 'First', 'body' => 'Best Article!'];
        $request = new ServerRequest(['post' => $data]);
        $this->assertSame($data, $request->getParsedBody());

        $request = new ServerRequest();
        $this->assertSame([], $request->getParsedBody());
    }

    /**
     * Test replacing the parsed body parameters.
     *
     * @return void
     */
    public function testWithParsedBody()
    {
        $data = ['title' => 'First', 'body' => 'Best Article!'];
        $request = new ServerRequest([]);
        $new = $request->withParsedBody($data);

        $this->assertNotSame($request, $new);
        $this->assertSame([], $request->getParsedBody());
        $this->assertSame($data, $new->getParsedBody());
    }

    /**
     * Test updating POST data in a psr7 fashion.
     *
     * @return void
     */
    public function testWithData()
    {
        $request = new ServerRequest([
            'post' => [
                'Model' => [
                    'field' => 'value'
                ]
            ]
        ]);
        $result = $request->withData('Model.new_value', 'new value');
        $this->assertNull($request->data('Model.new_value'), 'Original request should not change.');
        $this->assertNotSame($result, $request);
        $this->assertEquals('new value', $result->data('Model.new_value'));
        $this->assertEquals('new value', $result->data['Model']['new_value']);
        $this->assertEquals('value', $result->data('Model.field'));
    }

    /**
     * Test updating POST data when keys don't exist
     *
     * @return void
     */
    public function testWithDataMissingIntermediaryKeys()
    {
        $request = new ServerRequest([
            'post' => [
                'Model' => [
                    'field' => 'value'
                ]
            ]
        ]);
        $result = $request->withData('Model.field.new_value', 'new value');
        $this->assertEquals(
            'new value',
            $result->data('Model.field.new_value')
        );
        $this->assertEquals(
            'new value',
            $result->data['Model']['field']['new_value']
        );
    }

    /**
     * Test updating POST data with falsey values
     *
     * @return void
     */
    public function testWithDataFalseyValues()
    {
        $request = new ServerRequest([
            'post' => []
        ]);
        $result = $request->withData('false', false)
            ->withData('null', null)
            ->withData('empty_string', '')
            ->withData('zero', 0)
            ->withData('zero_string', '0');
        $expected = [
            'false' => false,
            'null' => null,
            'empty_string' => '',
            'zero' => 0,
            'zero_string' => '0'
        ];
        $this->assertSame($expected, $result->data());
    }

    /**
     * Test setting attributes.
     *
     * @return void
     */
    public function testWithAttribute()
    {
        $request = new ServerRequest([]);
        $this->assertNull($request->getAttribute('key'));
        $this->assertSame('default', $request->getAttribute('key', 'default'));

        $new = $request->withAttribute('key', 'value');
        $this->assertNotEquals($new, $request, 'Should be different');
        $this->assertNull($request->getAttribute('key'), 'Old instance not modified');
        $this->assertSame('value', $new->getAttribute('key'));

        $update = $new->withAttribute('key', ['complex']);
        $this->assertNotEquals($update, $new, 'Should be different');
        $this->assertSame(['complex'], $update->getAttribute('key'));
    }

    /**
     * Test that withAttribute() can modify the deprecated public properties.
     *
     * @return void
     */
    public function testWithAttributesCompatibility()
    {
        $request = new ServerRequest([
            'params' => [
                'controller' => 'Articles',
                'action' => 'index'
            ],
            'base' => '/cakeapp',
            'webroot' => '/cakeapp/'
        ]);

        $new = $request->withAttribute('base', '/replace')
            ->withAttribute('webroot', '/replace/')
            ->withAttribute('params', ['controller' => 'Tags']);

        // Original request should not change.
        $this->assertSame('/cakeapp', $request->getAttribute('base'));
        $this->assertSame('/cakeapp/', $request->getAttribute('webroot'));
        $this->assertSame(
            ['controller' => 'Articles', 'action' => 'index'],
            $request->getAttribute('params')
        );

        $this->assertSame('/replace', $new->getAttribute('base'));
        $this->assertSame('/replace', $new->base);
        $this->assertSame('/replace/', $new->getAttribute('webroot'));
        $this->assertSame('/replace/', $new->webroot);

        $this->assertSame(['controller' => 'Tags'], $new->getAttribute('params'));
        $this->assertSame(['controller' => 'Tags'], $new->params);
    }

    /**
     * Test that getAttribute() can read deprecated public properties.
     *
     * @dataProvider emulatedPropertyProvider
     * @return void
     */
    public function testGetAttributesCompatibility($prop)
    {
        $request = new ServerRequest([
            'params' => [
                'controller' => 'Articles',
                'action' => 'index'
            ],
            'base' => '/cakeapp',
            'webroot' => '/cakeapp/'
        ]);

        if ($prop === 'session') {
            $this->assertSame($request->session(), $request->getAttribute($prop));
        } else {
            $this->assertSame($request->{$prop}, $request->getAttribute($prop));
        }
    }

    /**
     * Test getting all attributes.
     *
     * @return void
     */
    public function testGetAttributes()
    {
        $request = new ServerRequest([]);
        $new = $request->withAttribute('key', 'value')
            ->withAttribute('nully', null)
            ->withAttribute('falsey', false);

        $this->assertFalse($new->getAttribute('falsey'));
        $this->assertNull($new->getAttribute('nully'));
        $expected = [
            'key' => 'value',
            'nully' => null,
            'falsey' => false,
            'params' => [
                'plugin' => null,
                'controller' => null,
                'action' => null,
                '_ext' => null,
                'pass' => [],
            ],
            'webroot' => '',
            'base' => ''
        ];
        $this->assertEquals($expected, $new->getAttributes());
    }

    /**
     * Test unsetting attributes.
     *
     * @return void
     */
    public function testWithoutAttribute()
    {
        $request = new ServerRequest([]);
        $new = $request->withAttribute('key', 'value');
        $update = $request->withoutAttribute('key');

        $this->assertNotEquals($update, $new, 'Should be different');
        $this->assertNull($update->getAttribute('key'));
    }

    /**
     * Test that withoutAttribute() cannot remove deprecated public properties.
     *
     * @dataProvider emulatedPropertyProvider
     * @expectedException InvalidArgumentException
     * @return void
     */
    public function testWithoutAttributesDenyEmulatedProperties($prop)
    {
        $request = new ServerRequest([]);
        $request->withoutAttribute($prop);
    }

    /**
     * Test the requestTarget methods.
     *
     * @return void
     */
    public function testWithRequestTarget()
    {
        $request = new ServerRequest([
            'environment' => [
                'REQUEST_URI' => '/articles/view/1',
                'QUERY_STRING' => 'comments=1&open=0'
            ],
            'base' => '/basedir'
        ]);
        $this->assertEquals(
            '/articles/view/1?comments=1&open=0',
            $request->getRequestTarget(),
            'Should not include basedir.'
        );

        $new = $request->withRequestTarget('/articles/view/3');
        $this->assertNotSame($new, $request);
        $this->assertEquals(
            '/articles/view/1?comments=1&open=0',
            $request->getRequestTarget(),
            'should be unchanged.'
        );
        $this->assertEquals('/articles/view/3', $new->getRequestTarget(), 'reflects method call');
    }

    /**
     * Data provider for emulated property tests.
     *
     * @return array
     */
    public function emulatedPropertyProvider()
    {
        return [
            ['params'],
            ['base'],
            ['webroot'],
            ['session']
        ];
    }

    /**
     * loadEnvironment method
     *
     * @param array $env
     * @return void
     */
    protected function _loadEnvironment($env)
    {
        if (isset($env['App'])) {
            Configure::write('App', $env['App']);
        }

        if (isset($env['GET'])) {
            foreach ($env['GET'] as $key => $val) {
                $_GET[$key] = $val;
            }
        }

        if (isset($env['POST'])) {
            foreach ($env['POST'] as $key => $val) {
                $_POST[$key] = $val;
            }
        }

        if (isset($env['SERVER'])) {
            foreach ($env['SERVER'] as $key => $val) {
                $_SERVER[$key] = $val;
            }
        }
    }
}
