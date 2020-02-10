<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Core\Configure;
use Cake\Http\ServerRequestFactory;
use Cake\Http\Session;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Exception\InvalidArgumentException;
use Laminas\Diactoros\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Test case for the server factory.
 */
class ServerRequestFactoryTest extends TestCase
{
    /**
     * @var array|null
     */
    protected $server;

    /**
     * @var array|null
     */
    protected $post;

    /**
     * @var array|null
     */
    protected $files;

    /**
     * @var array|null
     */
    protected $cookies;

    /**
     * @var array|null
     */
    protected $get;

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->get = $_GET;
    }

    /**
     * teardown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $_SERVER = $this->server;
        $_POST = $this->post;
        $_FILES = $this->files;
        $_COOKIE = $this->cookies;
        $_GET = $this->get;
    }

    /**
     * Test fromGlobals reads super globals
     *
     * @return void
     */
    public function testFromGlobalsSuperGlobals()
    {
        $_POST = [
            'title' => 'custom',
        ];
        $_FILES = [
            'image' => [
                'tmp_name' => __FILE__,
                'error' => 0,
                'name' => 'cats.png',
                'type' => 'image/png',
                'size' => 2112,
            ],
        ];
        $_COOKIE = ['key' => 'value'];
        $_GET = ['query' => 'string'];
        $res = ServerRequestFactory::fromGlobals();
        $this->assertSame($_COOKIE['key'], $res->getCookie('key'));
        $this->assertSame($_GET['query'], $res->getQuery('query'));
        $this->assertArrayHasKey('title', $res->getData());
        $this->assertArrayHasKey('image', $res->getData());
        $this->assertCount(1, $res->getUploadedFiles());

        /** @var \Laminas\Diactoros\UploadedFile $expected */
        $expected = $res->getData('image');
        $this->assertInstanceOf(UploadedFileInterface::class, $expected);
        $this->assertSame($_FILES['image']['size'], $expected->getSize());
        $this->assertSame($_FILES['image']['error'], $expected->getError());
        $this->assertSame($_FILES['image']['name'], $expected->getClientFilename());
        $this->assertSame($_FILES['image']['type'], $expected->getClientMediaType());
    }

    /**
     * Test fromGlobals input
     *
     * @return void
     */
    public function testFromGlobalsInput()
    {
        $res = ServerRequestFactory::fromGlobals();
        $this->assertSame('', $res->input());
    }

    /**
     * Test fromGlobals includes the session
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     * @return void
     */
    public function testFromGlobalsUrlSession()
    {
        Configure::write('App.base', '/basedir');
        $server = [
            'DOCUMENT_ROOT' => '/cake/repo/branches/1.2.x.x/webroot',
            'PHP_SELF' => '/index.php',
            'REQUEST_URI' => '/posts/add',
        ];
        $res = ServerRequestFactory::fromGlobals($server);
        $session = $res->getAttribute('session');
        $this->assertInstanceOf(Session::class, $session);
        $this->assertSame('/basedir/', ini_get('session.cookie_path'), 'Needs trailing / for cookie to work');
    }

    /**
     * Test fromGlobals with App.base defined.
     *
     * @return void
     */
    public function testFromGlobalsUrlBaseDefined()
    {
        Configure::write('App.base', 'basedir');
        $server = [
            'DOCUMENT_ROOT' => '/cake/repo/branches/1.2.x.x/webroot',
            'PHP_SELF' => '/index.php',
            'REQUEST_URI' => '/posts/add',
        ];
        $res = ServerRequestFactory::fromGlobals($server);
        $this->assertSame('basedir', $res->getAttribute('base'));
        $this->assertSame('basedir/', $res->getAttribute('webroot'));
        $this->assertSame('/posts/add', $res->getUri()->getPath());
    }

    /**
     * Test fromGlobals with mod-rewrite server configuration.
     *
     * @return void
     */
    public function testFromGlobalsUrlModRewrite()
    {
        Configure::write('App.baseUrl', false);

        $server = [
            'DOCUMENT_ROOT' => '/cake/repo/branches',
            'PHP_SELF' => '/urlencode me/webroot/index.php',
            'REQUEST_URI' => '/posts/view/1',
        ];
        $res = ServerRequestFactory::fromGlobals($server);

        $this->assertSame('/urlencode%20me', $res->getAttribute('base'));
        $this->assertSame('/urlencode%20me/', $res->getAttribute('webroot'));
        $this->assertSame('/posts/view/1', $res->getUri()->getPath());
    }

    /**
     * Test fromGlobals with mod-rewrite in the root dir.
     *
     * @return void
     */
    public function testFromGlobalsUrlModRewriteRootDir()
    {
        $server = [
            'DOCUMENT_ROOT' => '/cake/repo/branches/1.2.x.x/webroot',
            'PHP_SELF' => '/index.php',
            'REQUEST_URI' => '/posts/add',
        ];
        $res = ServerRequestFactory::fromGlobals($server);

        $this->assertSame('', $res->getAttribute('base'));
        $this->assertSame('/', $res->getAttribute('webroot'));
        $this->assertSame('/posts/add', $res->getUri()->getPath());
    }

    /**
     * Test fromGlobals with App.baseUrl defined implying no
     * mod-rewrite and no virtual path.
     *
     * @return void
     */
    public function testFromGlobalsUrlNoModRewriteWebrootDir()
    {
        Configure::write('App', [
            'dir' => 'app',
            'webroot' => 'www',
            'base' => false,
            'baseUrl' => '/cake/index.php',
        ]);
        $server = [
            'DOCUMENT_ROOT' => '/Users/markstory/Sites',
            'SCRIPT_FILENAME' => '/Users/markstory/Sites/cake/www/index.php',
            'PHP_SELF' => '/cake/www/index.php/posts/index',
            'REQUEST_URI' => '/cake/www/index.php',
        ];
        $res = ServerRequestFactory::fromGlobals($server);

        $this->assertSame('/cake/www/', $res->getAttribute('webroot'));
        $this->assertSame('/cake/index.php', $res->getAttribute('base'));
        $this->assertSame('/', $res->getUri()->getPath());
    }

    /**
     * Test fromGlobals with App.baseUrl defined implying no
     * mod-rewrite
     *
     * @return void
     */
    public function testFromGlobalsUrlNoModRewrite()
    {
        Configure::write('App', [
            'dir' => 'app',
            'webroot' => 'webroot',
            'base' => false,
            'baseUrl' => '/cake/index.php',
        ]);
        $server = [
            'DOCUMENT_ROOT' => '/Users/markstory/Sites',
            'SCRIPT_FILENAME' => '/Users/markstory/Sites/cake/index.php',
            'PHP_SELF' => '/cake/index.php/posts/index',
            'REQUEST_URI' => '/cake/index.php/posts/index',
        ];
        $res = ServerRequestFactory::fromGlobals($server);

        $this->assertSame('/cake/webroot/', $res->getAttribute('webroot'));
        $this->assertSame('/cake/index.php', $res->getAttribute('base'));
        $this->assertSame('/posts/index', $res->getUri()->getPath());
    }

    /**
     * Test fromGlobals with App.baseUrl defined implying no
     * mod-rewrite in the root dir.
     *
     * @return void
     */
    public function testFromGlobalsUrlNoModRewriteRootDir()
    {
        Configure::write('App', [
            'dir' => 'cake',
            'webroot' => 'webroot',
            'base' => false,
            'baseUrl' => '/index.php',
        ]);
        $server = [
            'DOCUMENT_ROOT' => '/Users/markstory/Sites/cake',
            'SCRIPT_FILENAME' => '/Users/markstory/Sites/cake/index.php',
            'PHP_SELF' => '/index.php/posts/add',
            'REQUEST_URI' => '/index.php/posts/add',
        ];
        $res = ServerRequestFactory::fromGlobals($server);

        $this->assertSame('/webroot/', $res->getAttribute('webroot'));
        $this->assertSame('/index.php', $res->getAttribute('base'));
        $this->assertSame('/posts/add', $res->getUri()->getPath());
    }

    /**
     * @return void
     */
    public function testFormUrlEncodedBodyParsing()
    {
        $data = [
            'Article' => ['title'],
        ];
        // 'input' => 'Article[]=title',
        $request = ServerRequestFactory::fromGlobals(
            [
                'REQUEST_METHOD' => 'PUT',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
            ]
        );
        $this->assertEquals($data, $request->getData());

        $data = ['one' => 1, 'two' => 'three'];
        // 'input' => 'one=1&two=three',
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'PUT',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ]);
        $this->assertEquals($data, $request->getData());

        // 'input' => 'Article[title]=Testing&action=update',
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'DELETE',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ]);
        $expected = [
            'Article' => ['title' => 'Testing'],
            'action' => 'update',
        ];
        $this->assertEquals($expected, $request->getData());

        $data = [
            'Article' => ['title'],
            'Tag' => ['Tag' => [1, 2]],
        ];
        // 'input' => 'Article[]=title&Tag[Tag][]=1&Tag[Tag][]=2',
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'PATCH',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ]);
        $this->assertEquals($data, $request->getData());
    }

    /**
     * Test method overrides coming in from POST data.
     *
     * @return void
     */
    public function testMethodOverrides()
    {
        $post = ['_method' => 'POST'];
        $request = ServerRequestFactory::fromGlobals([], [], $post);
        $this->assertSame('POST', $request->getEnv('REQUEST_METHOD'));

        $post = ['_method' => 'DELETE'];
        $request = ServerRequestFactory::fromGlobals([], [], $post);
        $this->assertSame('DELETE', $request->getEnv('REQUEST_METHOD'));

        $request = ServerRequestFactory::fromGlobals(['HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT']);
        $this->assertSame('PUT', $request->getEnv('REQUEST_METHOD'));

        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_METHOD' => 'POST'],
            [],
            ['_method' => 'PUT']
        );
        $this->assertSame('PUT', $request->getEnv('REQUEST_METHOD'));
        $this->assertSame('POST', $request->getEnv('ORIGINAL_REQUEST_METHOD'));
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

        $request = ServerRequestFactory::fromGlobals($vars);
        $expected = $vars + [
            'CONTENT_TYPE' => null,
            'HTTP_CONTENT_TYPE' => null,
            'ORIGINAL_REQUEST_METHOD' => 'PUT',
        ];
        $this->assertSame($expected, $request->getServerParams());
    }

    /**
     * Tests that overriding the method to GET will clean all request
     * data, to better simulate a GET request.
     *
     * @return void
     */
    public function testMethodOverrideEmptyParsedBody()
    {
        $body = ['_method' => 'GET', 'foo' => 'bar'];
        $request = ServerRequestFactory::fromGlobals(
            ['REQUEST_METHOD' => 'POST'],
            [],
            $body
        );
        $this->assertEmpty($request->getParsedBody());

        $request = ServerRequestFactory::fromGlobals(
            [
                'REQUEST_METHOD' => 'POST',
                'HTTP_X_HTTP_METHOD_OVERRIDE' => 'GET',
            ],
            [],
            ['foo' => 'bar']
        );
        $this->assertEmpty($request->getParsedBody());
    }

    /**
     * Tests the default file upload merging behavior.
     *
     * @return void
     */
    public function testFromGlobalsWithFilesAsObjectsDefault()
    {
        $this->assertNull(Configure::read('App.uploadedFilesAsObjects'));

        $files = [
            'file' => [
                'name' => 'file.txt',
                'type' => 'text/plain',
                'tmp_name' => __FILE__,
                'error' => 0,
                'size' => 1234,
            ],
        ];
        $request = ServerRequestFactory::fromGlobals(null, null, null, null, $files);

        /** @var \Laminas\Diactoros\UploadedFile $expected */
        $expected = $request->getData('file');
        $this->assertSame($files['file']['size'], $expected->getSize());
        $this->assertSame($files['file']['error'], $expected->getError());
        $this->assertSame($files['file']['name'], $expected->getClientFilename());
        $this->assertSame($files['file']['type'], $expected->getClientMediaType());
    }

    /**
     * Tests the "as arrays" file upload merging behavior.
     *
     * @return void
     */
    public function testFromGlobalsWithFilesAsObjectsDisabled()
    {
        Configure::write('App.uploadedFilesAsObjects', false);

        $files = [
            'file' => [
                'name' => 'file.txt',
                'type' => 'text/plain',
                'tmp_name' => __FILE__,
                'error' => 0,
                'size' => 1234,
            ],
        ];
        $request = ServerRequestFactory::fromGlobals(null, null, null, null, $files);

        $expected = [
            'file' => [
                'tmp_name' => __FILE__,
                'error' => 0,
                'name' => 'file.txt',
                'type' => 'text/plain',
                'size' => 1234,
            ],
        ];
        $this->assertEquals($expected, $request->getData());
    }

    /**
     * Tests the "as objects" file upload merging behavior.
     *
     * @return void
     */
    public function testFromGlobalsWithFilesAsObjectsEnabled()
    {
        Configure::write('App.uploadedFilesAsObjects', true);

        $files = [
            'file' => [
                'name' => 'file.txt',
                'type' => 'text/plain',
                'tmp_name' => __FILE__,
                'error' => 0,
                'size' => 1234,
            ],
        ];
        $request = ServerRequestFactory::fromGlobals(null, null, null, null, $files);

        $expected = [
            'file' => new UploadedFile(
                __FILE__,
                1234,
                0,
                'file.txt',
                'text/plain'
            ),
        ];
        $this->assertEquals($expected, $request->getData());
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
                'size' => ['file' => 17178],
            ],
            0 => [
                'name' => ['image' => 'scratch.text'],
                'type' => ['image' => 'text/plain'],
                'tmp_name' => ['image' => __FILE__],
                'error' => ['image' => 0],
                'size' => ['image' => 1490],
            ],
            'pictures' => [
                'name' => [
                    0 => ['file' => 'a-file.png'],
                    1 => ['file' => 'a-moose.png'],
                ],
                'type' => [
                    0 => ['file' => 'image/png'],
                    1 => ['file' => 'image/jpg'],
                ],
                'tmp_name' => [
                    0 => ['file' => __FILE__],
                    1 => ['file' => __FILE__],
                ],
                'error' => [
                    0 => ['file' => 0],
                    1 => ['file' => 0],
                ],
                'size' => [
                    0 => ['file' => 17188],
                    1 => ['file' => 2010],
                ],
            ],
        ];

        $post = [
            'pictures' => [
                0 => ['name' => 'A cat'],
                1 => ['name' => 'A moose'],
            ],
            0 => [
                'name' => 'A dog',
            ],
        ];

        $request = ServerRequestFactory::fromGlobals(null, null, $post, null, $files);
        $expected = [
            'image_main' => [
                'file' => new UploadedFile(
                    __FILE__,
                    17178,
                    0,
                    'born on.txt',
                    'text/plain'
                ),
            ],
            'pictures' => [
                0 => [
                    'name' => 'A cat',
                    'file' => new UploadedFile(
                        __FILE__,
                        17188,
                        0,
                        'a-file.png',
                        'image/png'
                    ),
                ],
                1 => [
                    'name' => 'A moose',
                    'file' => new UploadedFile(
                        __FILE__,
                        2010,
                        0,
                        'a-moose.png',
                        'image/jpg'
                    ),
                ],
            ],
            0 => [
                'name' => 'A dog',
                'image' => new UploadedFile(
                    __FILE__,
                    1490,
                    0,
                    'scratch.text',
                    'text/plain'
                ),
            ],
        ];
        $this->assertEquals($expected, $request->getData());

        $uploads = $request->getUploadedFiles();
        $this->assertCount(3, $uploads);
        $this->assertArrayHasKey(0, $uploads);
        $this->assertSame('scratch.text', $uploads[0]['image']->getClientFilename());

        $this->assertArrayHasKey('pictures', $uploads);
        $this->assertSame('a-file.png', $uploads['pictures'][0]['file']->getClientFilename());
        $this->assertSame('a-moose.png', $uploads['pictures'][1]['file']->getClientFilename());

        $this->assertArrayHasKey('image_main', $uploads);
        $this->assertSame('born on.txt', $uploads['image_main']['file']->getClientFilename());
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
            ],
        ];

        Configure::write('App.uploadedFilesAsObjects', false);
        $request = ServerRequestFactory::fromGlobals([], [], [], [], $files);
        $this->assertEquals($files, $request->getData());
        Configure::write('App.uploadedFilesAsObjects', true);

        $uploads = $request->getUploadedFiles();
        $this->assertCount(1, $uploads);
        $this->assertArrayHasKey('birth_cert', $uploads);
        $this->assertSame('born on.txt', $uploads['birth_cert']->getClientFilename());
        $this->assertSame(0, $uploads['birth_cert']->getError());
        $this->assertSame('application/octet-stream', $uploads['birth_cert']->getClientMediaType());
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

        Configure::write('App.uploadedFilesAsObjects', false);
        $request = ServerRequestFactory::fromGlobals([], [], [], [], $files);
        $this->assertEquals($files, $request->getData());
        Configure::write('App.uploadedFilesAsObjects', true);

        $uploads = $request->getUploadedFiles();
        $this->assertCount(1, $uploads);
        $this->assertEquals($files[0]['name'], $uploads[0]->getClientFilename());
    }

    /**
     * Tests that file uploads are merged into the post data as objects instead of as arrays.
     *
     * @return void
     */
    public function testFilesAsObjectsInRequestData()
    {
        $files = [
            'flat' => [
                'name' => 'flat.txt',
                'type' => 'text/plain',
                'tmp_name' => __FILE__,
                'error' => 0,
                'size' => 1,
            ],
            'nested' => [
                'name' => ['file' => 'nested.txt'],
                'type' => ['file' => 'text/plain'],
                'tmp_name' => ['file' => __FILE__],
                'error' => ['file' => 0],
                'size' => ['file' => 12],
            ],
            0 => [
                'name' => 'numeric.txt',
                'type' => 'text/plain',
                'tmp_name' => __FILE__,
                'error' => 0,
                'size' => 123,
            ],
            1 => [
                'name' => ['file' => 'numeric-nested.txt'],
                'type' => ['file' => 'text/plain'],
                'tmp_name' => ['file' => __FILE__],
                'error' => ['file' => 0],
                'size' => ['file' => 1234],
            ],
            'deep' => [
                'name' => [
                    0 => ['file' => 'deep-1.txt'],
                    1 => ['file' => 'deep-2.txt'],
                ],
                'type' => [
                    0 => ['file' => 'text/plain'],
                    1 => ['file' => 'text/plain'],
                ],
                'tmp_name' => [
                    0 => ['file' => __FILE__],
                    1 => ['file' => __FILE__],
                ],
                'error' => [
                    0 => ['file' => 0],
                    1 => ['file' => 0],
                ],
                'size' => [
                    0 => ['file' => 12345],
                    1 => ['file' => 123456],
                ],
            ],
        ];

        $post = [
            'flat' => ['existing'],
            'nested' => [
                'name' => 'nested',
                'file' => ['existing'],
            ],
            'deep' => [
                0 => [
                    'name' => 'deep 1',
                    'file' => ['existing'],
                ],
                1 => [
                    'name' => 'deep 2',
                ],
            ],
            1 => [
                'name' => 'numeric nested',
            ],
        ];

        $expected = [
            'flat' => new UploadedFile(
                __FILE__,
                1,
                0,
                'flat.txt',
                'text/plain'
            ),
            'nested' => [
                'name' => 'nested',
                'file' => new UploadedFile(
                    __FILE__,
                    12,
                    0,
                    'nested.txt',
                    'text/plain'
                ),
            ],
            'deep' => [
                0 => [
                    'name' => 'deep 1',
                    'file' => new UploadedFile(
                        __FILE__,
                        12345,
                        0,
                        'deep-1.txt',
                        'text/plain'
                    ),
                ],
                1 => [
                    'name' => 'deep 2',
                    'file' => new UploadedFile(
                        __FILE__,
                        123456,
                        0,
                        'deep-2.txt',
                        'text/plain'
                    ),
                ],
            ],
            0 => new UploadedFile(
                __FILE__,
                123,
                0,
                'numeric.txt',
                'text/plain'
            ),
            1 => [
                'name' => 'numeric nested',
                'file' => new UploadedFile(
                    __FILE__,
                    1234,
                    0,
                    'numeric-nested.txt',
                    'text/plain'
                ),
            ],
        ];

        $request = ServerRequestFactory::fromGlobals([], [], $post, [], $files);

        $this->assertEquals($expected, $request->getData());
    }

    /**
     * Test passing invalid files list structure.
     *
     * @return void
     */
    public function testFilesWithInvalidStructure()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value in files specification');

        ServerRequestFactory::fromGlobals([], [], [], [], [
            [
                'invalid' => [
                    'data',
                ],
            ],
        ]);
    }
}
