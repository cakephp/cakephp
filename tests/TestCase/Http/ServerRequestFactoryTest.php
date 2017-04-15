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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Core\Configure;
use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;

/**
 * Test case for the server factory.
 */
class ServerRequestFactoryTest extends TestCase
{
    /**
     * @var array|null
     */
    protected $server = null;

    /**
     * @var array|null
     */
    protected $post = null;

    /**
     * @var array|null
     */
    protected $files = null;

    /**
     * @var array|null
     */
    protected $cookies = null;

    /**
     * @var array|null
     */
    protected $get = null;

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
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
    public function tearDown()
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
            'title' => 'custom'
        ];
        $_FILES = [
            'image' => [
                'tmp_name' => __FILE__,
                'error' => 0,
                'name' => 'cats.png',
                'type' => 'image/png',
                'size' => 2112
            ]
        ];
        $_COOKIE = ['key' => 'value'];
        $_GET = ['query' => 'string'];
        $res = ServerRequestFactory::fromGlobals();
        $this->assertSame($_COOKIE['key'], $res->getCookie('key'));
        $this->assertSame($_GET['query'], $res->getQuery('query'));
        $this->assertArrayHasKey('title', $res->getData());
        $this->assertArrayHasKey('image', $res->getData());
        $this->assertSame($_FILES['image'], $res->getData('image'));
        $this->assertCount(1, $res->getUploadedFiles());
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
        $this->assertInstanceOf('Cake\Network\Session', $session);
        $this->assertEquals('/basedir/', ini_get('session.cookie_path'), 'Needs trailing / for cookie to work');
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
        $this->assertEquals('basedir', $res->getAttribute('base'));
        $this->assertEquals('basedir/', $res->getAttribute('webroot'));
        $this->assertEquals('/posts/add', $res->getUri()->getPath());
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

        $this->assertEquals('/urlencode%20me', $res->getAttribute('base'));
        $this->assertEquals('/urlencode%20me/', $res->getAttribute('webroot'));
        $this->assertEquals('/posts/view/1', $res->getUri()->getPath());
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

        $this->assertEquals('', $res->getAttribute('base'));
        $this->assertEquals('/', $res->getAttribute('webroot'));
        $this->assertEquals('/posts/add', $res->getUri()->getPath());
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
            'webroot' => 'webroot',
            'base' => false,
            'baseUrl' => '/cake/index.php'
        ]);
        $server = [
            'DOCUMENT_ROOT' => '/Users/markstory/Sites',
            'SCRIPT_FILENAME' => '/Users/markstory/Sites/cake/webroot/index.php',
            'PHP_SELF' => '/cake/webroot/index.php/posts/index',
            'REQUEST_URI' => '/cake/webroot/index.php',
        ];
        $res = ServerRequestFactory::fromGlobals($server);

        $this->assertSame('/cake/webroot/', $res->getAttribute('webroot'));
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
            'baseUrl' => '/cake/index.php'
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
            'baseUrl' => '/index.php'
        ]);
        $server = [
            'DOCUMENT_ROOT' => '/Users/markstory/Sites/cake',
            'SCRIPT_FILENAME' => '/Users/markstory/Sites/cake/index.php',
            'PHP_SELF' => '/index.php/posts/add',
            'REQUEST_URI' => '/index.php/posts/add',
        ];
        $res = ServerRequestFactory::fromGlobals($server);

        $this->assertEquals('/webroot/', $res->getAttribute('webroot'));
        $this->assertEquals('/index.php', $res->getAttribute('base'));
        $this->assertEquals('/posts/add', $res->getUri()->getPath());
    }
}
