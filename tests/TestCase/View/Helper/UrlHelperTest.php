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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\UrlHelper;
use Cake\View\View;
use TestApp\Routing\Asset;

/**
 * UrlHelperTest class
 */
class UrlHelperTest extends TestCase
{
    /**
     * @var \Cake\View\Helper\UrlHelper
     */
    protected $Helper;

    /**
     * @var \Cake\View\View
     */
    protected $View;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Router::reload();
        $request = new ServerRequest();
        Router::setRequest($request);

        $this->View = new View($request);
        $this->Helper = new UrlHelper($this->View);

        static::setAppNamespace();
        $this->loadPlugins(['TestTheme']);
        Router::scope('/', function (RouteBuilder $routes) {
            $routes->fallbacks(DashedRoute::class);
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
        unset($this->Helper, $this->View);
    }

    /**
     * Ensure HTML escaping of URL params. So link addresses are valid and not exploited
     *
     * @return void
     */
    public function testBuildUrlConversion()
    {
        Router::connect('/:controller/:action/*');

        $result = $this->Helper->build('/controller/action/1');
        $this->assertSame('/controller/action/1', $result);

        $result = $this->Helper->build('/controller/action/1?one=1&two=2');
        $this->assertSame('/controller/action/1?one=1&amp;two=2', $result);

        $result = $this->Helper->build(['controller' => 'Posts', 'action' => 'index', '?' => ['page' => '1" onclick="alert(\'XSS\');"']]);
        $this->assertSame('/posts?page=1%22+onclick%3D%22alert%28%27XSS%27%29%3B%22', $result);

        $result = $this->Helper->build('/controller/action/1/param:this+one+more');
        $this->assertSame('/controller/action/1/param:this+one+more', $result);

        $result = $this->Helper->build('/controller/action/1/param:this%20one%20more');
        $this->assertSame('/controller/action/1/param:this%20one%20more', $result);

        $result = $this->Helper->build('/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24');
        $this->assertSame('/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24', $result);

        $result = $this->Helper->build([
            'controller' => 'Posts', 'action' => 'index',
            '?' => ['param' => '%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24'],
        ]);
        $this->assertSame('/posts?param=%257Baround%2520here%257D%255Bthings%255D%255Bare%255D%2524%2524', $result);

        $result = $this->Helper->build([
            'controller' => 'Posts', 'action' => 'index',
            '?' => ['one' => 'value', 'two' => 'value', 'three' => 'purple', 'page' => '1'],
        ]);
        $this->assertSame('/posts?one=value&amp;two=value&amp;three=purple&amp;page=1', $result);
    }

    /**
     * ensure that build factors in base paths.
     *
     * @return void
     */
    public function testBuildBasePath()
    {
        Router::connect('/:controller/:action/*');
        $request = new ServerRequest([
            'params' => [
                'action' => 'index',
                'plugin' => null,
                'controller' => 'Subscribe',
            ],
            'url' => '/subscribe',
            'base' => '/magazine',
            'webroot' => '/magazine/',
        ]);
        Router::setRequest($request);

        $this->assertSame('/magazine/subscribe', $this->Helper->build());
        $this->assertSame(
            '/magazine/articles/add',
            $this->Helper->build(['controller' => 'Articles', 'action' => 'add'])
        );
    }

    /**
     * @return void
     */
    public function testBuildUrlConversionUnescaped()
    {
        $result = $this->Helper->build('/controller/action/1?one=1&two=2', ['escape' => false]);
        $this->assertSame('/controller/action/1?one=1&two=2', $result);

        $result = $this->Helper->build([
            'controller' => 'Posts',
            'action' => 'view',
            '?' => [
                'k' => 'v',
                '1' => '2',
                'param' => '%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24',
            ],
        ], ['escape' => false]);
        $this->assertSame('/posts/view?k=v&1=2&param=%257Baround%2520here%257D%255Bthings%255D%255Bare%255D%2524%2524', $result);
    }

    /**
     * @return void
     */
    public function testBuildFromPath(): void
    {
        $result = $this->Helper->buildFromPath('Articles::index');
        $expected = '/articles';
        $this->assertSame($result, $expected);

        $result = $this->Helper->buildFromPath('Articles::view', [3]);
        $expected = '/articles/view/3';
        $this->assertSame($result, $expected);
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
        $result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertSame(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $result);

        Configure::write('Asset.timestamp', true);
        Configure::write('debug', false);

        $result = $this->Helper->assetTimestamp('/%3Cb%3E/cake.generic.css');
        $this->assertSame('/%3Cb%3E/cake.generic.css', $result);

        $result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertSame(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $result);

        Configure::write('Asset.timestamp', true);
        Configure::write('debug', true);
        $result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertMatchesRegularExpression('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);

        Configure::write('Asset.timestamp', 'force');
        Configure::write('debug', false);
        $result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertMatchesRegularExpression('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);

        $result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css?someparam');
        $this->assertSame(Configure::read('App.cssBaseUrl') . 'cake.generic.css?someparam', $result);

        $request = $this->View->getRequest()->withAttribute('webroot', '/some/dir/');
        $this->View->setRequest($request);
        Router::setRequest($request);
        $result = $this->Helper->assetTimestamp('/some/dir/' . Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertMatchesRegularExpression('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);
    }

    /**
     * test assetUrl application
     *
     * @return void
     */
    public function testAssetUrl()
    {
        $this->Helper->webroot = '';
        $result = $this->Helper->assetUrl('js/post.js', ['fullBase' => true]);
        $this->assertSame(Router::fullBaseUrl() . '/js/post.js', $result);

        $result = $this->Helper->assetUrl('foo.jpg', ['pathPrefix' => 'img/']);
        $this->assertSame('img/foo.jpg', $result);

        $result = $this->Helper->assetUrl('foo.jpg', ['fullBase' => true]);
        $this->assertSame(Router::fullBaseUrl() . '/foo.jpg', $result);

        $result = $this->Helper->assetUrl('style', ['ext' => '.css']);
        $this->assertSame('style.css', $result);

        $result = $this->Helper->assetUrl('dir/sub dir/my image', ['ext' => '.jpg']);
        $this->assertSame('dir/sub%20dir/my%20image.jpg', $result);

        $result = $this->Helper->assetUrl('foo.jpg?one=two&three=four');
        $this->assertSame('foo.jpg?one=two&amp;three=four', $result);

        $result = $this->Helper->assetUrl('x:"><script>alert(1)</script>');
        $this->assertSame('x:&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;', $result);

        $result = $this->Helper->assetUrl('dir/big+tall/image', ['ext' => '.jpg']);
        $this->assertSame('dir/big%2Btall/image.jpg', $result);
    }

    /**
     * Test assetUrl and data uris
     *
     * @return void
     */
    public function testAssetUrlDataUri()
    {
        $request = $this->View->getRequest()
            ->withAttribute('base', 'subdir')
            ->withAttribute('webroot', 'subdir/');

        $this->View->setRequest($request);
        Router::setRequest($request);

        $data = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4' .
            '/8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==';
        $result = $this->Helper->assetUrl($data);
        $this->assertSame($data, $result);

        $data = 'data:image/png;base64,<evil>';
        $result = $this->Helper->assetUrl($data);
        $this->assertSame(h($data), $result);
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

        $result = $this->Helper->assetUrl('img/cake.icon.png', ['fullBase' => true]);
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
        $this->Helper->webroot = '';
        $this->loadPlugins(['TestPlugin']);

        $result = $this->Helper->assetUrl('TestPlugin.style', ['ext' => '.css']);
        $this->assertSame('test_plugin/style.css', $result);

        $result = $this->Helper->assetUrl('TestPlugin.style', ['ext' => '.css', 'plugin' => false]);
        $this->assertSame('TestPlugin.style.css', $result);

        $this->removePlugins(['TestPlugin']);
    }

    /**
     * Tests assetUrl() with full base URL.
     *
     * @return void
     */
    public function testAssetUrlFullBase()
    {
        $result = $this->Helper->assetUrl('img/foo.jpg', ['fullBase' => true]);
        $this->assertSame(Router::fullBaseUrl() . '/img/foo.jpg', $result);

        $result = $this->Helper->assetUrl('img/foo.jpg', ['fullBase' => 'https://xyz/']);
        $this->assertSame('https://xyz/img/foo.jpg', $result);
    }

    /**
     * test assetUrl and Asset.timestamp = force
     *
     * @return void
     */
    public function testAssetUrlTimestampForce()
    {
        $this->Helper->webroot = '';
        Configure::write('Asset.timestamp', 'force');

        $result = $this->Helper->assetUrl('cake.generic.css', ['pathPrefix' => Configure::read('App.cssBaseUrl')]);
        $this->assertMatchesRegularExpression('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);
    }

    /**
     * Test assetTimestamp with timestamp option overriding `Asset.timestamp` in Configure.
     *
     * @return void
     */
    public function testAssetTimestampConfigureOverride()
    {
        $this->Helper->webroot = '';
        Configure::write('Asset.timestamp', 'force');
        $timestamp = false;

        $result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $timestamp);
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
        $this->loadPlugins(['TestPlugin']);

        $result = $this->Helper->assetTimestamp('/test_plugin/css/test_plugin_asset.css');
        $this->assertMatchesRegularExpression('#/test_plugin/css/test_plugin_asset.css\?[0-9]+$#', $result, 'Missing timestamp plugin');

        $result = $this->Helper->assetTimestamp('/test_plugin/css/i_dont_exist.css');
        $this->assertMatchesRegularExpression('#/test_plugin/css/i_dont_exist.css$#', $result, 'No error on missing file');

        $result = $this->Helper->assetTimestamp('/test_theme/js/theme.js');
        $this->assertMatchesRegularExpression('#/test_theme/js/theme.js\?[0-9]+$#', $result, 'Missing timestamp theme');

        $result = $this->Helper->assetTimestamp('/test_theme/js/nonexistent.js');
        $this->assertMatchesRegularExpression('#/test_theme/js/nonexistent.js$#', $result, 'No error on missing file');
    }

    /**
     * test script()
     *
     * @return void
     */
    public function testScript()
    {
        $this->Helper->webroot = '';
        $result = $this->Helper->script(
            'post.js',
            ['fullBase' => true]
        );
        $this->assertSame(Router::fullBaseUrl() . '/js/post.js', $result);
    }

    /**
     * Test script and Asset.timestamp = force
     *
     * @return void
     */
    public function testScriptTimestampForce()
    {
        $this->Helper->webroot = '';
        Configure::write('Asset.timestamp', 'force');

        $result = $this->Helper->script('script.js');
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

        $result = $this->Helper->script('script.js', ['timestamp' => $timestamp]);
        $this->assertSame(Configure::read('App.jsBaseUrl') . 'script.js', $result);
    }

    /**
     * test image()
     *
     * @return void
     */
    public function testImage()
    {
        $result = $this->Helper->image('foo.jpg');
        $this->assertSame('img/foo.jpg', $result);

        $result = $this->Helper->image('foo.jpg', ['fullBase' => true]);
        $this->assertSame(Router::fullBaseUrl() . '/img/foo.jpg', $result);

        $result = $this->Helper->image('dir/sub dir/my image.jpg');
        $this->assertSame('img/dir/sub%20dir/my%20image.jpg', $result);

        $result = $this->Helper->image('foo.jpg?one=two&three=four');
        $this->assertSame('img/foo.jpg?one=two&amp;three=four', $result);

        $result = $this->Helper->image('dir/big+tall/image.jpg');
        $this->assertSame('img/dir/big%2Btall/image.jpg', $result);

        $result = $this->Helper->image('cid:foo.jpg');
        $this->assertSame('cid:foo.jpg', $result);

        $result = $this->Helper->image('CID:foo.jpg');
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

        $result = $this->Helper->image('cake.icon.png');
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

        $result = $this->Helper->image('cake.icon.png', ['timestamp' => $timestamp]);
        $this->assertSame('img/cake.icon.png', $result);
    }

    /**
     * test css
     *
     * @return void
     */
    public function testCss()
    {
        $result = $this->Helper->css('style');
        $this->assertSame('css/style.css', $result);
    }

    /**
     * Test css with `Asset.timestamp` = force
     *
     * @return void
     */
    public function testCssTimestampForce()
    {
        Configure::write('Asset.timestamp', 'force');

        $result = $this->Helper->css('cake.generic');
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

        $result = $this->Helper->css('cake.generic', ['timestamp' => $timestamp]);
        $this->assertSame('css/cake.generic.css', $result);
    }

    /**
     * Test generating paths with webroot().
     *
     * @return void
     */
    public function testWebrootPaths()
    {
        $request = $this->View->getRequest()->withAttribute('webroot', '/');
        $this->View->setRequest(
            $request
        );
        Router::setRequest($request);
        $result = $this->Helper->webroot('/img/cake.power.gif');
        $expected = '/img/cake.power.gif';
        $this->assertSame($expected, $result);

        $this->Helper->getView()->setTheme('TestTheme');

        $result = $this->Helper->webroot('/img/cake.power.gif');
        $expected = '/test_theme/img/cake.power.gif';
        $this->assertSame($expected, $result);

        $result = $this->Helper->webroot('/img/test.jpg');
        $expected = '/test_theme/img/test.jpg';
        $this->assertSame($expected, $result);

        $webRoot = Configure::read('App.wwwRoot');
        Configure::write('App.wwwRoot', TEST_APP . 'TestApp/webroot/');

        $result = $this->Helper->webroot('/img/cake.power.gif');
        $expected = '/test_theme/img/cake.power.gif';
        $this->assertSame($expected, $result);

        $result = $this->Helper->webroot('/img/test.jpg');
        $expected = '/test_theme/img/test.jpg';
        $this->assertSame($expected, $result);

        $result = $this->Helper->webroot('/img/cake.icon.gif');
        $expected = '/img/cake.icon.gif';
        $this->assertSame($expected, $result);

        $result = $this->Helper->webroot('/img/cake.icon.gif?some=param');
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
        Configure::write('App.imageBaseUrl', $cdnPrefix);
        $result = $this->Helper->image('TestTheme.text.jpg');
        $expected = $cdnPrefix . 'text.jpg';
        $this->assertSame($expected, $result);

        Configure::write('App.jsBaseUrl', $cdnPrefix);
        $result = $this->Helper->script('TestTheme.app.js');
        $expected = $cdnPrefix . 'app.js';
        $this->assertSame($expected, $result);

        Configure::write('App.cssBaseUrl', $cdnPrefix);
        $result = $this->Helper->css('TestTheme.app.css');
        $expected = $cdnPrefix . 'app.css';
        $this->assertSame($expected, $result);
    }

    /**
     * Test if an app Asset class is being loaded
     *
     * @return void
     */
    public function testAppAssetPresent()
    {
        $Url = new UrlHelper($this->View, ['assetUrlClassName' => Asset::class]);
        $Url->webroot = '';

        $result = $Url->assetUrl('cake.generic.css', ['pathPrefix' => '/']);
        $this->assertSame('/cake.generic.css?appHash', $result);

        $result = $Url->css('cake.generic', ['pathPrefix' => '/']);
        $this->assertSame('/cake.generic.css?appHash', $result);

        $result = $Url->script('cake.generic', ['pathPrefix' => '/']);
        $this->assertSame('/cake.generic.js?appHash', $result);

        $result = $Url->image('cake.generic.png', ['pathPrefix' => '/']);
        $this->assertSame('/cake.generic.png?appHash', $result);
    }

    /**
     * Test if UrlHelper fails to load with wrong asset URL class name
     *
     * @return void
     */
    public function testAppAssetPresentWrong()
    {
        $this->expectException(CakeException::class);
        new UrlHelper($this->View, ['assetUrlClassName' => 'InexistentClass']);
    }
}
