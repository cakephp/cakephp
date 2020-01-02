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
}
