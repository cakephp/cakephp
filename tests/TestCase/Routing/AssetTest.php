<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Asset;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * AssetTest class
 */
class AssetTest extends TestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Router::reload();
        $request = new ServerRequest([
            'webroot' => '/',
        ]);
        Router::setRequest($request);

        static::setAppNamespace();
        $this->loadPlugins(['TestTheme']);
        Router::scope('/', function (RouteBuilder $routes) {
            $routes->fallbacks();
        });
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $this->clearPlugins();
    }

    /**
     * test assetTimestamp application
     *
     * @return void
     */
    public function testAssetTimestamp()
    {
        Configure::write('Foo.bar', 'test');
        Configure::write('Asset.timestamp', false);
        $result = Asset::assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertSame(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $result);

        Configure::write('Asset.timestamp', true);
        Configure::write('debug', false);

        $result = Asset::assetTimestamp('/%3Cb%3E/cake.generic.css');
        $this->assertSame('/%3Cb%3E/cake.generic.css', $result);

        $result = Asset::assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertSame(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $result);

        Configure::write('Asset.timestamp', true);
        Configure::write('debug', true);
        $result = Asset::assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertMatchesRegularExpression('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);

        Configure::write('Asset.timestamp', 'force');
        Configure::write('debug', false);
        $result = Asset::assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertMatchesRegularExpression('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);

        $result = Asset::assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css?someparam');
        $this->assertSame(Configure::read('App.cssBaseUrl') . 'cake.generic.css?someparam', $result);

        $request = Router::getRequest()->withAttribute('webroot', '/some/dir/');
        Router::setRequest($request);
        $result = Asset::assetTimestamp('/some/dir/' . Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertMatchesRegularExpression('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);
    }

    /**
     * test assetUrl application
     *
     * @return void
     */
    public function testAssetUrl()
    {
        Router::connect('/:controller/:action/*');

        $result = Asset::url('js/post.js', ['fullBase' => true]);
        $this->assertSame(Router::fullBaseUrl() . '/js/post.js', $result);

        $result = Asset::url('foo.jpg', ['pathPrefix' => 'img/']);
        $this->assertSame('/img/foo.jpg', $result);

        $result = Asset::url('foo.jpg', ['fullBase' => true]);
        $this->assertSame(Router::fullBaseUrl() . '/foo.jpg', $result);

        $result = Asset::url('style', ['ext' => '.css']);
        $this->assertSame('/style.css', $result);

        $result = Asset::url('dir/sub dir/my image', ['ext' => '.jpg']);
        $this->assertSame('/dir/sub%20dir/my%20image.jpg', $result);

        $result = Asset::url('foo.jpg?one=two&three=four');
        $this->assertSame('/foo.jpg?one=two&three=four', $result);

        // No HTML entities encoding is done
        $result = Asset::url('x:"><script>alert(1)</script>');
        $this->assertSame('x:"><script>alert(1)</script>', $result);

        // URL encoding is done
        $result = Asset::url('dir/big+tall/image', ['ext' => '.jpg']);
        $this->assertSame('/dir/big%2Btall/image.jpg', $result);

        $result = Asset::url('/posts/index/adbirawwy/page:6/sort:type/');
        $this->assertSame('/posts/index/adbirawwy/page%3A6/sort%3Atype/', $result);
    }

    /**
     * Test assetUrl and data uris
     *
     * @return void
     */
    public function testAssetUrlDataUri()
    {
        $request = Router::getRequest()
            ->withAttribute('base', 'subdir')
            ->withAttribute('webroot', 'subdir/');
        Router::setRequest($request);

        $data = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4' .
            '/8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==';
        $result = Asset::url($data);
        $this->assertSame($data, $result);

        $data = 'data:image/png;base64,<evil>';
        $result = Asset::url($data);
        $this->assertSame($data, $result);
    }

    /**
     * Test assetUrl with no rewriting.
     *
     * @return void
     */
    public function testAssetUrlNoRewrite()
    {
        $request = Router::getRequest()
            ->withAttribute('base', '/cake_dev/index.php')
            ->withAttribute('webroot', '/cake_dev/app/webroot/')
            ->withRequestTarget('/cake_dev/index.php/tasks');
        Router::setRequest($request);

        $result = Asset::url('img/cake.icon.png', ['fullBase' => true]);
        $expected = Configure::read('App.fullBaseUrl') . '/cake_dev/app/webroot/img/cake.icon.png';
        $this->assertSame($expected, $result);
    }

    /**
     * Test assetUrl with plugins.
     *
     * @return void
     */
    public function testAssetUrlPlugin()
    {
        $this->loadPlugins(['TestPlugin']);

        $result = Asset::url('TestPlugin.style', ['ext' => '.css']);
        $this->assertSame('/test_plugin/style.css', $result);

        $result = Asset::url('TestPlugin.style', ['ext' => '.css', 'plugin' => false]);
        $this->assertSame('/TestPlugin.style.css', $result);

        $this->removePlugins(['TestPlugin']);
    }

    /**
     * Tests assetUrl() with full base URL.
     *
     * @return void
     */
    public function testAssetUrlFullBase()
    {
        $result = Asset::url('img/foo.jpg', ['fullBase' => true]);
        $this->assertSame(Router::fullBaseUrl() . '/img/foo.jpg', $result);

        $result = Asset::url('img/foo.jpg', ['fullBase' => 'https://xyz/']);
        $this->assertSame('https://xyz/img/foo.jpg', $result);
    }

    /**
     * test assetUrl and Asset.timestamp = force
     *
     * @return void
     */
    public function testAssetUrlTimestampForce()
    {
        Configure::write('Asset.timestamp', 'force');

        $result = Asset::url('cake.generic.css', ['pathPrefix' => Configure::read('App.cssBaseUrl')]);
        $this->assertMatchesRegularExpression('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);
    }

    /**
     * Test assetTimestamp with timestamp option overriding `Asset.timestamp` in Configure.
     *
     * @return void
     */
    public function testAssetTimestampConfigureOverride()
    {
        Configure::write('Asset.timestamp', 'force');
        $timestamp = false;

        $result = Asset::assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $timestamp);
        $this->assertSame(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $result);
    }

    /**
     * test assetTimestamp with plugins and themes
     *
     * @return void
     */
    public function testAssetTimestampPluginsAndThemes()
    {
        Configure::write('Asset.timestamp', 'force');
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        $result = Asset::assetTimestamp('/test_plugin/css/test_plugin_asset.css');
        $this->assertMatchesRegularExpression('#/test_plugin/css/test_plugin_asset.css\?[0-9]+$#', $result, 'Missing timestamp plugin');

        $result = Asset::assetTimestamp('/company/test_plugin_three/css/company.css');
        $this->assertMatchesRegularExpression('#/company/test_plugin_three/css/company.css\?[0-9]+$#', $result, 'Missing timestamp plugin');

        $result = Asset::assetTimestamp('/test_plugin/css/i_dont_exist.css');
        $this->assertMatchesRegularExpression('#/test_plugin/css/i_dont_exist.css$#', $result, 'No error on missing file');

        $result = Asset::assetTimestamp('/test_theme/js/theme.js');
        $this->assertMatchesRegularExpression('#/test_theme/js/theme.js\?[0-9]+$#', $result, 'Missing timestamp theme');

        $result = Asset::assetTimestamp('/test_theme/js/nonexistent.js');
        $this->assertMatchesRegularExpression('#/test_theme/js/nonexistent.js$#', $result, 'No error on missing file');
    }

    /**
     * test script()
     *
     * @return void
     */
    public function testScript()
    {
        Router::connect('/:controller/:action/*');

        $result = Asset::scriptUrl('post.js', ['fullBase' => true]);
        $this->assertSame(Router::fullBaseUrl() . '/js/post.js', $result);
    }

    /**
     * Test script and Asset.timestamp = force
     *
     * @return void
     */
    public function testScriptTimestampForce()
    {
        Configure::write('Asset.timestamp', 'force');

        $result = Asset::scriptUrl('script.js');
        $this->assertMatchesRegularExpression('/' . preg_quote(Configure::read('App.jsBaseUrl') . 'script.js?', '/') . '[0-9]+/', $result);
    }

    /**
     * Test script with timestamp option overriding `Asset.timestamp` in Configure
     *
     * @return void
     */
    public function testScriptTimestampConfigureOverride()
    {
        Configure::write('Asset.timestamp', 'force');
        $timestamp = false;

        $result = Asset::scriptUrl('script.js', ['timestamp' => $timestamp]);
        $this->assertSame('/' . Configure::read('App.jsBaseUrl') . 'script.js', $result);
    }

    /**
     * test image()
     *
     * @return void
     */
    public function testImage()
    {
        $result = Asset::imageUrl('foo.jpg');
        $this->assertSame('/img/foo.jpg', $result);

        $result = Asset::imageUrl('foo.jpg', ['fullBase' => true]);
        $this->assertSame(Router::fullBaseUrl() . '/img/foo.jpg', $result);

        $result = Asset::imageUrl('dir/sub dir/my image.jpg');
        $this->assertSame('/img/dir/sub%20dir/my%20image.jpg', $result);

        $result = Asset::imageUrl('foo.jpg?one=two&three=four');
        $this->assertSame('/img/foo.jpg?one=two&three=four', $result);

        $result = Asset::imageUrl('dir/big+tall/image.jpg');
        $this->assertSame('/img/dir/big%2Btall/image.jpg', $result);

        $result = Asset::imageUrl('cid:foo.jpg');
        $this->assertSame('cid:foo.jpg', $result);

        $result = Asset::imageUrl('CID:foo.jpg');
        $this->assertSame('CID:foo.jpg', $result);
    }

    /**
     * Test image with `Asset.timestamp` = force
     *
     * @return void
     */
    public function testImageTimestampForce()
    {
        Configure::write('Asset.timestamp', 'force');

        $result = Asset::imageUrl('cake.icon.png');
        $this->assertMatchesRegularExpression('/' . preg_quote('img/cake.icon.png?', '/') . '[0-9]+/', $result);
    }

    /**
     * Test image with timestamp option overriding `Asset.timestamp` in Configure
     *
     * @return void
     */
    public function testImageTimestampConfigureOverride()
    {
        Configure::write('Asset.timestamp', 'force');
        $timestamp = false;

        $result = Asset::imageUrl('cake.icon.png', ['timestamp' => $timestamp]);
        $this->assertSame('/img/cake.icon.png', $result);
    }

    /**
     * test css
     *
     * @return void
     */
    public function testCss()
    {
        $result = Asset::cssUrl('style');
        $this->assertSame('/css/style.css', $result);
    }

    /**
     * Test css with `Asset.timestamp` = force
     *
     * @return void
     */
    public function testCssTimestampForce()
    {
        Configure::write('Asset.timestamp', 'force');

        $result = Asset::cssUrl('cake.generic');
        $this->assertMatchesRegularExpression('/' . preg_quote('css/cake.generic.css?', '/') . '[0-9]+/', $result);
    }

    /**
     * Test image with timestamp option overriding `Asset.timestamp` in Configure
     *
     * @return void
     */
    public function testCssTimestampConfigureOverride()
    {
        Configure::write('Asset.timestamp', 'force');
        $timestamp = false;

        $result = Asset::cssUrl('cake.generic', ['timestamp' => $timestamp]);
        $this->assertSame('/css/cake.generic.css', $result);
    }

    /**
     * Test generating paths with webroot().
     *
     * @return void
     */
    public function testWebrootPaths()
    {
        $result = Asset::webroot('/img/cake.power.gif');
        $expected = '/img/cake.power.gif';
        $this->assertSame($expected, $result);

        $result = Asset::webroot('/img/cake.power.gif', ['theme' => 'TestTheme']);
        $expected = '/test_theme/img/cake.power.gif';
        $this->assertSame($expected, $result);

        Asset::setInflectionType('dasherize');
        $result = Asset::webroot('/img/test.jpg', ['theme' => 'TestTheme']);
        $expected = '/test-theme/img/test.jpg';
        $this->assertSame($expected, $result);
        Asset::setInflectionType('underscore');

        $webRoot = Configure::read('App.wwwRoot');
        Configure::write('App.wwwRoot', TEST_APP . 'TestApp/webroot/');

        $result = Asset::webroot('/img/cake.power.gif', ['theme' => 'TestTheme']);
        $expected = '/test_theme/img/cake.power.gif';
        $this->assertSame($expected, $result);

        $result = Asset::webroot('/img/test.jpg', ['theme' => 'TestTheme']);
        $expected = '/test_theme/img/test.jpg';
        $this->assertSame($expected, $result);

        $result = Asset::webroot('/img/cake.icon.gif', ['theme' => 'TestTheme']);
        $expected = '/img/cake.icon.gif';
        $this->assertSame($expected, $result);

        $result = Asset::webroot('/img/cake.icon.gif?some=param', ['theme' => 'TestTheme']);
        $expected = '/img/cake.icon.gif?some=param';
        $this->assertSame($expected, $result);

        Configure::write('App.wwwRoot', $webRoot);
    }

    /**
     * Test plugin based assets will NOT use the plugin name
     *
     * @return void
     */
    public function testPluginAssetsPrependImageBaseUrl()
    {
        $cdnPrefix = 'https://cdn.example.com/';
        $imageBaseUrl = Configure::read('App.imageBaseUrl');
        $jsBaseUrl = Configure::read('App.jsBaseUrl');
        $cssBaseUrl = Configure::read('App.cssBaseUrl');
        Configure::write('App.imageBaseUrl', $cdnPrefix . '{plugin}img/');
        $result = Asset::imageUrl('TestTheme.text.jpg');
        $expected = $cdnPrefix . 'test_theme/img/text.jpg';
        $this->assertSame($expected, $result);

        Configure::write('App.jsBaseUrl', $cdnPrefix . '{plugin}js/');
        $result = Asset::scriptUrl('TestTheme.app.js');
        $expected = $cdnPrefix . 'test_theme/js/app.js';
        $this->assertSame($expected, $result);

        Configure::write('App.cssBaseUrl', $cdnPrefix);
        $result = Asset::cssUrl('TestTheme.app.css');
        $expected = $cdnPrefix . 'app.css';
        $this->assertSame($expected, $result);
    }
}
