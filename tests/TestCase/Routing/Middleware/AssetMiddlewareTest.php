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
namespace Cake\Test\TestCase\Routing\Middleware;

use Cake\Http\ServerRequestFactory;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\TestSuite\TestCase;
use TestApp\Http\TestRequestHandler;

/**
 * Test for AssetMiddleware
 */
class AssetMiddlewareTest extends TestCase
{
    /**
     * setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        $this->clearPlugins();
        parent::tearDown();
    }

    /**
     * test that the if modified since header generates 304 responses
     */
    public function testCheckIfModifiedHeader(): void
    {
        $modified = filemtime(TEST_APP . 'Plugin/TestPlugin/webroot/root.js');
        $request = ServerRequestFactory::fromGlobals([
            'REQUEST_URI' => '/test_plugin/root.js',
            'HTTP_IF_MODIFIED_SINCE' => date(DATE_RFC7231, $modified),
        ]);
        $handler = new TestRequestHandler();
        $middleware = new AssetMiddleware();
        $res = $middleware->process($request, $handler);

        $body = $res->getBody()->getContents();
        $this->assertSame('', $body);
        $this->assertSame(304, $res->getStatusCode());
        $this->assertNotEmpty($res->getHeaderLine('Last-Modified'));
    }

    /**
     * test missing plugin assets.
     */
    public function testMissingPluginAsset(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/test_plugin/not_found.js']);
        $handler = new TestRequestHandler();

        $middleware = new AssetMiddleware();
        $res = $middleware->process($request, $handler);

        $body = $res->getBody()->getContents();
        $this->assertSame('', $body);
    }

    /**
     * Data provider for assets.
     *
     * @return array
     */
    public function assetProvider(): array
    {
        return [
            // In plugin root.
            [
                '/test_plugin/root.js',
                TEST_APP . 'Plugin/TestPlugin/webroot/root.js',
            ],
            // Subdirectory
            [
                '/test_plugin/js/alert.js',
                TEST_APP . 'Plugin/TestPlugin/webroot/js/alert.js',
            ],
            // In path that matches the plugin name
            [
                '/test_plugin/js/test_plugin/test.js',
                TEST_APP . 'Plugin/TestPlugin/webroot/js/test_plugin/test.js',
            ],
            // In vendored plugin
            [
                '/company/test_plugin_three/css/company.css',
                TEST_APP . 'Plugin/Company/TestPluginThree/webroot/css/company.css',
            ],
        ];
    }

    /**
     * Test assets in a plugin.
     *
     * @dataProvider assetProvider
     */
    public function testPluginAsset(string $url, string $expectedFile): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => $url]);
        $handler = new TestRequestHandler();

        $middleware = new AssetMiddleware();
        $res = $middleware->process($request, $handler);

        $body = $res->getBody()->getContents();
        $this->assertStringEqualsFile($expectedFile, $body);
    }

    /**
     * Test headers with plugin assets
     */
    public function testPluginAssetHeaders(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/test_plugin/root.js']);
        $handler = new TestRequestHandler();

        $modified = filemtime(TEST_APP . 'Plugin/TestPlugin/webroot/root.js');
        $expires = strtotime('+4 hours');
        $time = time();

        $middleware = new AssetMiddleware(['cacheTime' => '+4 hours']);
        $res = $middleware->process($request, $handler);

        $this->assertSame(
            'application/javascript',
            $res->getHeaderLine('Content-Type')
        );
        $this->assertSame(
            gmdate(DATE_RFC7231, $time),
            $res->getHeaderLine('Date')
        );
        $this->assertSame(
            'public,max-age=' . ($expires - $time),
            $res->getHeaderLine('Cache-Control')
        );
        $this->assertSame(
            gmdate(DATE_RFC7231, $modified),
            $res->getHeaderLine('Last-Modified')
        );
        $this->assertSame(
            gmdate(DATE_RFC7231, $expires),
            $res->getHeaderLine('Expires')
        );
    }

    /**
     * Test that // results in a 404
     */
    public function test404OnDoubleSlash(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '//index.php']);
        $handler = new TestRequestHandler();

        $middleware = new AssetMiddleware();
        $res = $middleware->process($request, $handler);
        $this->assertEmpty($res->getBody()->getContents());
    }

    /**
     * Test that .. results in a 404
     */
    public function test404OnDoubleDot(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/test_plugin/../webroot/root.js']);
        $handler = new TestRequestHandler();

        $middleware = new AssetMiddleware();
        $res = $middleware->process($request, $handler);
        $this->assertEmpty($res->getBody()->getContents());
    }

    /**
     * Test that hidden filenames result in a 404
     */
    public function test404OnHiddenFile(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/test_plugin/.hiddenfile']);
        $handler = new TestRequestHandler();

        $middleware = new AssetMiddleware();
        $res = $middleware->process($request, $handler);
        $this->assertEmpty($res->getBody()->getContents());
    }

    /**
     * Test that hidden filenames result in a 404
     */
    public function test404OnHiddenFolder(): void
    {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/test_plugin/.hiddenfolder/some.js']);
        $handler = new TestRequestHandler();

        $middleware = new AssetMiddleware();
        $res = $middleware->process($request, $handler);
        $this->assertEmpty($res->getBody()->getContents());
    }
}
