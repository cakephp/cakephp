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
     * Test fromGlobals reads super globals
     */
    public function testFromGlobalsSuperGlobals(): void
    {
        $post = [
            'title' => 'custom',
        ];
        $files = [
            'image' => [
                'tmp_name' => __FILE__,
                'error' => 0,
                'name' => 'cats.png',
                'type' => 'image/png',
                'size' => 2112,
            ],
        ];
        $cookies = ['key' => 'value'];
        $query = ['query' => 'string'];
        $res = ServerRequestFactory::fromGlobals([], $query, $post, $cookies, $files);
        $this->assertSame($cookies['key'], $res->getCookie('key'));
        $this->assertSame($query['query'], $res->getQuery('query'));
        $this->assertArrayHasKey('title', $res->getData());
        $this->assertArrayHasKey('image', $res->getData());
        $this->assertCount(1, $res->getUploadedFiles());

        /** @var \Psr\Http\Message\UploadedFileInterface $expected */
        $expected = $res->getData('image');
        $this->assertInstanceOf(UploadedFileInterface::class, $expected);
        $this->assertSame($files['image']['size'], $expected->getSize());
        $this->assertSame($files['image']['error'], $expected->getError());
        $this->assertSame($files['image']['name'], $expected->getClientFilename());
        $this->assertSame($files['image']['type'], $expected->getClientMediaType());
    }

    /**
     * Test fromGlobals includes the session
     *
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testFromGlobalsUrlSession(): void
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
     */
    public function testFromGlobalsUrlBaseDefined(): void
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
     */
    public function testFromGlobalsUrlModRewrite(): void
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

        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/cake/repo/branches',
            'PHP_SELF' => '/1.2.x.x/webroot/index.php',
            'PATH_INFO' => '/posts/view/1',
        ]);
        $this->assertSame('/1.2.x.x', $request->getAttribute('base'));
        $this->assertSame('/1.2.x.x/', $request->getAttribute('webroot'));
        $this->assertSame('/posts/view/1', $request->getRequestTarget());

        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/cake/repo/branches/1.2.x.x/test/',
            'PHP_SELF' => '/webroot/index.php',
        ]);

        $this->assertSame('', $request->getAttribute('base'));
        $this->assertSame('/', $request->getAttribute('webroot'));

        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/some/apps/where',
            'PHP_SELF' => '/webroot/index.php',
        ]);

        $this->assertSame('', $request->getAttribute('base'));
        $this->assertSame('/', $request->getAttribute('webroot'));

        Configure::write('App.dir', 'auth');

        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/cake/repo/branches',
            'PHP_SELF' => '/demos/webroot/index.php',
        ]);

        $this->assertSame('/demos', $request->getAttribute('base'));
        $this->assertSame('/demos/', $request->getAttribute('webroot'));

        Configure::write('App.dir', 'code');

        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
            'PHP_SELF' => '/clients/PewterReport/webroot/index.php',
        ]);

        $this->assertSame('/clients/PewterReport', $request->getAttribute('base'));
        $this->assertSame('/clients/PewterReport/', $request->getAttribute('webroot'));
    }

    /**
     * Test baseUrl with ModRewrite alias
     */
    public function testBaseUrlwithModRewriteAlias(): void
    {
        Configure::write('App.base', '/control');

        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/home/aplusnur/public_html',
            'PHP_SELF' => '/control/index.php',
        ]);

        $this->assertSame('/control', $request->getAttribute('base'));
        $this->assertSame('/control/', $request->getAttribute('webroot'));

        Configure::write('App.base', false);
        Configure::write('App.dir', 'affiliate');
        Configure::write('App.webroot', 'newaffiliate');

        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/var/www/abtravaff/html',
            'PHP_SELF' => '/newaffiliate/index.php',
        ]);

        $this->assertSame('', $request->getAttribute('base'));
        $this->assertSame('/', $request->getAttribute('webroot'));
    }

    /**
     * Test base, webroot, URL and here parsing when there is URL rewriting but
     * CakePHP gets called with index.php in URL nonetheless.
     *
     * Tests uri with
     *
     * - index.php/
     * - index.php/
     * - index.php/apples/
     * - index.php/bananas/eat/tasty_banana
     */
    public function testBaseUrlWithModRewriteAndIndexPhp(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/cakephp/webroot/index.php',
            'PHP_SELF' => '/cakephp/webroot/index.php',
        ]);

        $this->assertSame('/cakephp', $request->getAttribute('base'));
        $this->assertSame('/cakephp/', $request->getAttribute('webroot'));
        $this->assertSame('/', $request->getRequestTarget());

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/cakephp/webroot/index.php/',
            'PHP_SELF' => '/cakephp/webroot/index.php/',
            'PATH_INFO' => '/',
        ]);

        $this->assertSame('/cakephp', $request->getAttribute('base'));
        $this->assertSame('/cakephp/', $request->getAttribute('webroot'));
        $this->assertSame('/', $request->getRequestTarget());

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/cakephp/webroot/index.php/apples',
            'PHP_SELF' => '/cakephp/webroot/index.php/apples',
            'PATH_INFO' => '/apples',
        ]);

        $this->assertSame('/cakephp', $request->getAttribute('base'));
        $this->assertSame('/cakephp/', $request->getAttribute('webroot'));
        $this->assertSame('/apples', $request->getRequestTarget());

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/cakephp/webroot/index.php/melons/share/',
            'PHP_SELF' => '/cakephp/webroot/index.php/melons/share/',
            'PATH_INFO' => '/melons/share/',
        ]);

        $this->assertSame('/cakephp', $request->getAttribute('base'));
        $this->assertSame('/cakephp/', $request->getAttribute('webroot'));
        $this->assertSame('/melons/share/', $request->getRequestTarget());

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/cakephp/webroot/index.php/bananas/eat/tasty_banana',
            'PHP_SELF' => '/cakephp/webroot/index.php/bananas/eat/tasty_banana',
            'PATH_INFO' => '/bananas/eat/tasty_banana',
        ]);

        $this->assertSame('/cakephp', $request->getAttribute('base'));
        $this->assertSame('/cakephp/', $request->getAttribute('webroot'));
        $this->assertSame('/bananas/eat/tasty_banana', $request->getRequestTarget());
    }

    /**
     * Test that even if mod_rewrite is on, and the url contains index.php
     * and there are numerous //s that the base/webroot is calculated correctly.
     */
    public function testBaseUrlWithModRewriteAndExtraSlashes(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/cakephp/webroot///index.php/bananas/eat',
            'PHP_SELF' => '/cakephp/webroot///index.php/bananas/eat',
            'PATH_INFO' => '/bananas/eat',
        ]);

        $this->assertSame('/cakephp', $request->getAttribute('base'));
        $this->assertSame('/cakephp/', $request->getAttribute('webroot'));
        $this->assertSame('/bananas/eat', $request->getRequestTarget());
    }

    /**
     * Test fromGlobals with mod-rewrite in the root dir.
     */
    public function testFromGlobalsUrlModRewriteRootDir(): void
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
     */
    public function testFromGlobalsUrlNoModRewriteWebrootDir(): void
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
     */
    public function testFromGlobalsUrlNoModRewrite(): void
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
     */
    public function testFromGlobalsUrlNoModRewriteRootDir(): void
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
     * Check that a sub-directory containing app|webroot doesn't get mishandled when re-writing is off.
     */
    public function testBaseUrlWithAppAndWebrootInDirname(): void
    {
        Configure::write('App.baseUrl', '/approval/index.php');

        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/Users/markstory/Sites/',
            'SCRIPT_FILENAME' => '/Users/markstory/Sites/approval/index.php',
        ]);
        $this->assertSame('/approval/index.php', $request->getAttribute('base'));
        $this->assertSame('/approval/webroot/', $request->getAttribute('webroot'));

        Configure::write('App.baseUrl', '/webrootable/index.php');

        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/Users/markstory/Sites/',
            'SCRIPT_FILENAME' => '/Users/markstory/Sites/webrootable/index.php',
        ]);
        $this->assertSame('/webrootable/index.php', $request->getAttribute('base'));
        $this->assertSame('/webrootable/webroot/', $request->getAttribute('webroot'));
    }

    /**
     * Test baseUrl and webroot with baseUrl
     */
    public function testBaseUrlAndWebrootWithBaseUrl(): void
    {
        Configure::write('App.dir', 'App');
        Configure::write('App.baseUrl', '/App/webroot/index.php');

        $request = ServerRequestFactory::fromGlobals();
        $this->assertSame('/App/webroot/index.php', $request->getAttribute('base'));
        $this->assertSame('/App/webroot/', $request->getAttribute('webroot'));

        Configure::write('App.baseUrl', '/App/webroot/test.php');
        $request = ServerRequestFactory::fromGlobals();
        $this->assertSame('/App/webroot/test.php', $request->getAttribute('base'));
        $this->assertSame('/App/webroot/', $request->getAttribute('webroot'));

        Configure::write('App.baseUrl', '/App/index.php');
        $request = ServerRequestFactory::fromGlobals();
        $this->assertSame('/App/index.php', $request->getAttribute('base'));
        $this->assertSame('/App/webroot/', $request->getAttribute('webroot'));

        Configure::write('App.baseUrl', '/CakeBB/App/webroot/index.php');
        $request = ServerRequestFactory::fromGlobals();
        $this->assertSame('/CakeBB/App/webroot/index.php', $request->getAttribute('base'));
        $this->assertSame('/CakeBB/App/webroot/', $request->getAttribute('webroot'));

        Configure::write('App.baseUrl', '/CakeBB/App/index.php');
        $request = ServerRequestFactory::fromGlobals();

        $this->assertSame('/CakeBB/App/index.php', $request->getAttribute('base'));
        $this->assertSame('/CakeBB/App/webroot/', $request->getAttribute('webroot'));

        Configure::write('App.baseUrl', '/CakeBB/index.php');
        $request = ServerRequestFactory::fromGlobals();

        $this->assertSame('/CakeBB/index.php', $request->getAttribute('base'));
        $this->assertSame('/CakeBB/webroot/', $request->getAttribute('webroot'));

        Configure::write('App.baseUrl', '/dbhauser/index.php');
        $request = ServerRequestFactory::fromGlobals([
            'DOCUMENT_ROOT' => '/kunden/homepages/4/d181710652/htdocs/joomla',
            'SCRIPT_FILENAME' => '/kunden/homepages/4/d181710652/htdocs/joomla/dbhauser/index.php',
        ]);

        $this->assertSame('/dbhauser/index.php', $request->getAttribute('base'));
        $this->assertSame('/dbhauser/webroot/', $request->getAttribute('webroot'));
    }

    /**
     * Test that a request with a . in the main GET parameter is filtered out.
     * PHP changes GET parameter keys containing dots to _.
     */
    public function testGetParamsWithDot(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'PHP_SELF' => '/webroot/index.php',
            'REQUEST_URI' => '/posts/index/add.add',
        ]);
        $this->assertSame('', $request->getAttribute('base'));
        $this->assertEquals([], $request->getQueryParams());

        $request = ServerRequestFactory::fromGlobals([
            'PHP_SELF' => '/cake_dev/webroot/index.php',
            'REQUEST_URI' => '/cake_dev/posts/index/add.add',
        ]);
        $this->assertSame('/cake_dev', $request->getAttribute('base'));
        $this->assertEquals([], $request->getQueryParams());
    }

    /**
     * Test that a request with urlencoded bits in the main GET parameter are filtered out.
     */
    public function testGetParamWithUrlencodedElement(): void
    {
        $request = ServerRequestFactory::fromGlobals([
            'PHP_SELF' => '/webroot/index.php',
            'REQUEST_URI' => '/posts/add/%E2%88%82%E2%88%82',
        ]);
        $this->assertSame('', $request->getAttribute('base'));
        $this->assertEquals([], $request->getQueryParams());

        $request = ServerRequestFactory::fromGlobals([
            'PHP_SELF' => '/cake_dev/webroot/index.php',
            'REQUEST_URI' => '/cake_dev/posts/add/%E2%88%82%E2%88%82',
        ]);
        $this->assertSame('/cake_dev', $request->getAttribute('base'));
        $this->assertEquals([], $request->getQueryParams());
    }

    /**
     * Generator for environment configurations
     *
     * @return array Environment array
     */
    public static function environmentGenerator(): array
    {
        return [
            [
                'IIS - No rewrite base path',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot',
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
                    'url' => '',
                ],
            ],
            [
                'IIS - No rewrite with path, no PHP_SELF',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/index.php?',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot',
                    ],
                    'SERVER' => [
                        'QUERY_STRING' => '/posts/add',
                        'REQUEST_URI' => '/index.php?/posts/add',
                        'PHP_SELF' => '',
                        'URL' => '/index.php?/posts/add',
                        'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot',
                        'argv' => ['/posts/add'],
                        'argc' => 1,
                    ],
                ],
                [
                    'url' => 'posts/add',
                    'base' => '/index.php?',
                    'webroot' => '/webroot/',
                ],
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
                        'argc' => 0,
                    ],
                ],
                [
                    'url' => '',
                    'base' => '/site/index.php',
                    'webroot' => '/site/webroot/',
                ],
            ],
            [
                'IIS - No rewrite sub dir 2 with path',
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
                        'QUERY_STRING' => '/posts/add',
                        'REQUEST_URI' => '/site/index.php/posts/add',
                        'URL' => '/site/index.php/posts/add',
                        'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\site\\index.php',
                        'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot',
                        'PHP_SELF' => '/site/index.php/posts/add',
                        'argv' => ['/posts/add'],
                        'argc' => 1,
                    ],
                ],
                [
                    'url' => 'posts/add',
                    'base' => '/site/index.php',
                    'webroot' => '/site/webroot/',
                ],
            ],
            [
                'Apache - No rewrite, document root set to webroot, requesting path',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot',
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
                    'webroot' => '/',
                ],
            ],
            [
                'Apache - No rewrite, document root set to webroot, requesting root',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot',
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
                    'webroot' => '/',
                ],
            ],
            [
                'Apache - No rewrite, document root set above top level cake dir, requesting path',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => '/site/index.php',
                        'dir' => 'TestApp',
                        'webroot' => 'webroot',
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
                        'webroot' => 'webroot',
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
                        'webroot' => 'webroot',
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
                        'QUERY_STRING' => 'a=b&c=d',
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
                        'webroot' => 'webroot',
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
                        'webroot' => 'webroot',
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
                        'webroot' => 'webroot',
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
                        'webroot' => 'webroot',
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
                        'webroot' => 'webroot',
                    ],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents/site/webroot',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/webroot/index.php',
                        'SCRIPT_NAME' => '/index.php',
                        'PHP_SELF' => '/index.php',
                        'PATH_INFO' => null,
                        'REQUEST_URI' => '/posts/add',
                    ],
                ],
                [
                    'url' => 'posts/add',
                    'base' => '',
                    'webroot' => '/',
                    'urlParams' => [],
                ],
            ],
            [
                'Nginx - w/rewrite, document root set above top level cake dir, request root, no PATH_INFO, base parameter set',
                [
                    'App' => [
                        'base' => false,
                        'baseUrl' => false,
                        'dir' => 'app',
                        'webroot' => 'webroot',
                    ],
                    'SERVER' => [
                        'SERVER_NAME' => 'localhost',
                        'DOCUMENT_ROOT' => '/Library/WebServer/Documents',
                        'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/App/webroot/index.php',
                        'SCRIPT_NAME' => '/site/app/webroot/index.php',
                        'PHP_SELF' => '/site/webroot/index.php',
                        'PATH_INFO' => null,
                        'REQUEST_URI' => '/site/posts/add',
                    ],
                ],
                [
                    'url' => 'posts/add',
                    'base' => '/site',
                    'webroot' => '/site/',
                    'urlParams' => [],
                ],
            ],
        ];
    }

    /**
     * Test environment detection
     *
     * @dataProvider environmentGenerator
     * @param string $name
     * @param array $data
     * @param array $expected
     */
    public function testEnvironmentDetection($name, $data, $expected): void
    {
        if (isset($data['App'])) {
            Configure::write('App', $data['App']);
        }

        $request = ServerRequestFactory::fromGlobals(
            $data['SERVER'] ?? null,
            $data['GET'] ?? null
        );
        $uri = $request->getUri();

        $this->assertSame('/' . $expected['url'], $uri->getPath(), 'Uri->getPath() is incorrect');
        $this->assertEquals($expected['base'], $request->getAttribute('base'), 'base is incorrect');
        $this->assertEquals($expected['webroot'], $request->getAttribute('webroot'), 'webroot is incorrect');

        if (isset($expected['urlParams'])) {
            $this->assertEquals($expected['urlParams'], $request->getQueryParams(), 'GET param mismatch');
        }
    }

    public function testFormUrlEncodedBodyParsing(): void
    {
        $data = [
            'Article' => ['title'],
        ];
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'PUT',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'CAKEPHP_INPUT' => 'Article[]=title',
        ]);
        $this->assertEquals($data, $request->getData());

        $data = ['one' => 1, 'two' => 'three'];
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'PUT',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'CAKEPHP_INPUT' => 'one=1&two=three',
        ]);
        $this->assertEquals($data, $request->getData());

        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'DELETE',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'CAKEPHP_INPUT' => 'Article[title]=Testing&action=update',
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
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_METHOD' => 'PATCH',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'CAKEPHP_INPUT' => 'Article[]=title&Tag[Tag][]=1&Tag[Tag][]=2',
        ]);
        $this->assertEquals($data, $request->getData());
    }

    /**
     * Test method overrides coming in from POST data.
     */
    public function testMethodOverrides(): void
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
     */
    public function testGetServerParams(): void
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
     */
    public function testMethodOverrideEmptyParsedBody(): void
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
     */
    public function testFromGlobalsWithFilesAsObjectsDefault(): void
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
     */
    public function testFromGlobalsWithFilesAsObjectsDisabled(): void
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
     */
    public function testFromGlobalsWithFilesAsObjectsEnabled(): void
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
     */
    public function testFilesNested(): void
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
     */
    public function testFilesFlat(): void
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
        $this->assertSame(123, $uploads['birth_cert']->getSize());
    }

    /**
     * Test that files in the 0th index work.
     */
    public function testFilesZeroithIndex(): void
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
        $this->assertSame($files[0]['name'], $uploads[0]->getClientFilename());
    }

    /**
     * Tests that file uploads are merged into the post data as objects instead of as arrays.
     */
    public function testFilesAsObjectsInRequestData(): void
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
     */
    public function testFilesWithInvalidStructure(): void
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
