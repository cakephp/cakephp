<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\UrlHelper;
use Cake\View\View;

/**
 * UrlHelperTest class
 */
class UrlHelperTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Router::reload();
        $this->View = new View();
        $this->Helper = new UrlHelper($this->View);
        $this->Helper->request = new Request();

        Configure::write('App.namespace', 'TestApp');
        Plugin::load(['TestTheme']);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Configure::delete('Asset');

        Plugin::unload();
        unset($this->Helper, $this->View);
    }

    /**
     * Ensure HTML escaping of URL params. So link addresses are valid and not exploited
     *
     * @return void
     */
    public function testUrlConversion()
    {
        Router::connect('/:controller/:action/*');

        $result = $this->Helper->build('/controller/action/1');
        $this->assertEquals('/controller/action/1', $result);

        $result = $this->Helper->build('/controller/action/1?one=1&two=2');
        $this->assertEquals('/controller/action/1?one=1&amp;two=2', $result);

        $result = $this->Helper->build(['controller' => 'posts', 'action' => 'index', 'page' => '1" onclick="alert(\'XSS\');"']);
        $this->assertEquals("/posts/index?page=1%22+onclick%3D%22alert%28%27XSS%27%29%3B%22", $result);

        $result = $this->Helper->build('/controller/action/1/param:this+one+more');
        $this->assertEquals('/controller/action/1/param:this+one+more', $result);

        $result = $this->Helper->build('/controller/action/1/param:this%20one%20more');
        $this->assertEquals('/controller/action/1/param:this%20one%20more', $result);

        $result = $this->Helper->build('/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24');
        $this->assertEquals('/controller/action/1/param:%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24', $result);

        $result = $this->Helper->build([
            'controller' => 'posts', 'action' => 'index', 'param' => '%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24'
        ]);
        $this->assertEquals("/posts/index?param=%257Baround%2520here%257D%255Bthings%255D%255Bare%255D%2524%2524", $result);

        $result = $this->Helper->build([
            'controller' => 'posts', 'action' => 'index', 'page' => '1',
            '?' => ['one' => 'value', 'two' => 'value', 'three' => 'purple']
        ]);
        $this->assertEquals("/posts/index?one=value&amp;two=value&amp;three=purple&amp;page=1", $result);
    }

    /**
     * @return void
     */
    public function testUrlConversionUnescaped()
    {
        $result = $this->Helper->build('/controller/action/1?one=1&two=2', ['escape' => false]);
        $this->assertEquals('/controller/action/1?one=1&two=2', $result);

        $result = $this->Helper->build([
            'controller' => 'posts',
            'action' => 'view',
            'param' => '%7Baround%20here%7D%5Bthings%5D%5Bare%5D%24%24',
            '?' => [
                'k' => 'v',
                '1' => '2'
            ]
        ], ['escape' => false]);
        $this->assertEquals("/posts/view?k=v&1=2&param=%257Baround%2520here%257D%255Bthings%255D%255Bare%255D%2524%2524", $result);
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
        $this->assertEquals(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $result);

        Configure::write('Asset.timestamp', true);
        Configure::write('debug', false);

        $result = $this->Helper->assetTimestamp('/%3Cb%3E/cake.generic.css');
        $this->assertEquals('/%3Cb%3E/cake.generic.css', $result);

        $result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertEquals(Configure::read('App.cssBaseUrl') . 'cake.generic.css', $result);

        Configure::write('Asset.timestamp', true);
        Configure::write('debug', true);
        $result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertRegExp('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);

        Configure::write('Asset.timestamp', 'force');
        Configure::write('debug', false);
        $result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertRegExp('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);

        $result = $this->Helper->assetTimestamp(Configure::read('App.cssBaseUrl') . 'cake.generic.css?someparam');
        $this->assertEquals(Configure::read('App.cssBaseUrl') . 'cake.generic.css?someparam', $result);

        $this->Helper->request->webroot = '/some/dir/';
        $result = $this->Helper->assetTimestamp('/some/dir/' . Configure::read('App.cssBaseUrl') . 'cake.generic.css');
        $this->assertRegExp('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);
    }

    /**
     * test assetUrl application
     *
     * @return void
     */
    public function testAssetUrl()
    {
        Router::connect('/:controller/:action/*');

        $this->Helper->webroot = '';
        $result = $this->Helper->assetUrl(
            [
                'controller' => 'js',
                'action' => 'post',
                '_ext' => 'js'
            ],
            ['fullBase' => true]
        );
        $this->assertEquals(Router::fullBaseUrl() . '/js/post.js', $result);

        $result = $this->Helper->assetUrl('foo.jpg', ['pathPrefix' => 'img/']);
        $this->assertEquals('img/foo.jpg', $result);

        $result = $this->Helper->assetUrl('foo.jpg', ['fullBase' => true]);
        $this->assertEquals(Router::fullBaseUrl() . '/foo.jpg', $result);

        $result = $this->Helper->assetUrl('style', ['ext' => '.css']);
        $this->assertEquals('style.css', $result);

        $result = $this->Helper->assetUrl('dir/sub dir/my image', ['ext' => '.jpg']);
        $this->assertEquals('dir/sub%20dir/my%20image.jpg', $result);

        $result = $this->Helper->assetUrl('foo.jpg?one=two&three=four');
        $this->assertEquals('foo.jpg?one=two&amp;three=four', $result);

        $result = $this->Helper->assetUrl('dir/big+tall/image', ['ext' => '.jpg']);
        $this->assertEquals('dir/big%2Btall/image.jpg', $result);
    }

    /**
     * Test assetUrl with no rewriting.
     *
     * @return void
     */
    public function testAssetUrlNoRewrite()
    {
        $this->Helper->request->addPaths([
            'base' => '/cake_dev/index.php',
            'webroot' => '/cake_dev/app/webroot/',
            'here' => '/cake_dev/index.php/tasks',
        ]);
        $result = $this->Helper->assetUrl('img/cake.icon.png', ['fullBase' => true]);
        $expected = Configure::read('App.fullBaseUrl') . '/cake_dev/app/webroot/img/cake.icon.png';
        $this->assertEquals($expected, $result);
    }

    /**
     * Test assetUrl with plugins.
     *
     * @return void
     */
    public function testAssetUrlPlugin()
    {
        $this->Helper->webroot = '';
        Plugin::load('TestPlugin');

        $result = $this->Helper->assetUrl('TestPlugin.style', ['ext' => '.css']);
        $this->assertEquals('test_plugin/style.css', $result);

        $result = $this->Helper->assetUrl('TestPlugin.style', ['ext' => '.css', 'plugin' => false]);
        $this->assertEquals('TestPlugin.style.css', $result);

        Plugin::unload('TestPlugin');
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
        $this->assertRegExp('/' . preg_quote(Configure::read('App.cssBaseUrl') . 'cake.generic.css?', '/') . '[0-9]+/', $result);
    }

    /**
     * test assetTimestamp with plugins and themes
     *
     * @return void
     */
    public function testAssetTimestampPluginsAndThemes()
    {
        Configure::write('Asset.timestamp', 'force');
        Plugin::load(['TestPlugin']);

        $result = $this->Helper->assetTimestamp('/test_plugin/css/test_plugin_asset.css');
        $this->assertRegExp('#/test_plugin/css/test_plugin_asset.css\?[0-9]+$#', $result, 'Missing timestamp plugin');

        $result = $this->Helper->assetTimestamp('/test_plugin/css/i_dont_exist.css');
        $this->assertRegExp('#/test_plugin/css/i_dont_exist.css\?$#', $result, 'No error on missing file');

        $result = $this->Helper->assetTimestamp('/test_theme/js/theme.js');
        $this->assertRegExp('#/test_theme/js/theme.js\?[0-9]+$#', $result, 'Missing timestamp theme');

        $result = $this->Helper->assetTimestamp('/test_theme/js/non_existant.js');
        $this->assertRegExp('#/test_theme/js/non_existant.js\?$#', $result, 'No error on missing file');
    }

    /**
     * test script()
     *
     * @return void
     */
    public function testScript()
    {
        Router::connect('/:controller/:action/*');

        $this->Helper->webroot = '';
        $result = $this->Helper->script(
            [
                'controller' => 'js',
                'action' => 'post',
                '_ext' => 'js'
            ],
            ['fullBase' => true]
        );
        $this->assertEquals(Router::fullBaseUrl() . '/js/post.js', $result);
    }

    /**
     * test image()
     *
     * @return void
     */
    public function testImage()
    {
        $result = $this->Helper->image('foo.jpg');
        $this->assertEquals('img/foo.jpg', $result);

        $result = $this->Helper->image('foo.jpg', ['fullBase' => true]);
        $this->assertEquals(Router::fullBaseUrl() . '/img/foo.jpg', $result);

        $result = $this->Helper->image('dir/sub dir/my image.jpg');
        $this->assertEquals('img/dir/sub%20dir/my%20image.jpg', $result);

        $result = $this->Helper->image('foo.jpg?one=two&three=four');
        $this->assertEquals('img/foo.jpg?one=two&amp;three=four', $result);

        $result = $this->Helper->image('dir/big+tall/image.jpg');
        $this->assertEquals('img/dir/big%2Btall/image.jpg', $result);

        $result = $this->Helper->image('cid:foo.jpg');
        $this->assertEquals('cid:foo.jpg', $result);

        $result = $this->Helper->image('CID:foo.jpg');
        $this->assertEquals('CID:foo.jpg', $result);
    }

    /**
     * test css
     *
     * @return void
     */
    public function testCss()
    {
        $result = $this->Helper->css('style');
        $this->assertEquals('css/style.css', $result);
    }

    /**
     * Test generating paths with webroot().
     *
     * @return void
     */
    public function testWebrootPaths()
    {
        $this->Helper->request->webroot = '/';
        $result = $this->Helper->webroot('/img/cake.power.gif');
        $expected = '/img/cake.power.gif';
        $this->assertEquals($expected, $result);

        $this->Helper->theme = 'TestTheme';

        $result = $this->Helper->webroot('/img/cake.power.gif');
        $expected = '/test_theme/img/cake.power.gif';
        $this->assertEquals($expected, $result);

        $result = $this->Helper->webroot('/img/test.jpg');
        $expected = '/test_theme/img/test.jpg';
        $this->assertEquals($expected, $result);

        $webRoot = Configure::read('App.wwwRoot');
        Configure::write('App.wwwRoot', TEST_APP . 'TestApp/webroot/');

        $result = $this->Helper->webroot('/img/cake.power.gif');
        $expected = '/test_theme/img/cake.power.gif';
        $this->assertEquals($expected, $result);

        $result = $this->Helper->webroot('/img/test.jpg');
        $expected = '/test_theme/img/test.jpg';
        $this->assertEquals($expected, $result);

        $result = $this->Helper->webroot('/img/cake.icon.gif');
        $expected = '/img/cake.icon.gif';
        $this->assertEquals($expected, $result);

        $result = $this->Helper->webroot('/img/cake.icon.gif?some=param');
        $expected = '/img/cake.icon.gif?some=param';
        $this->assertEquals($expected, $result);

        Configure::write('App.wwwRoot', $webRoot);
    }
}
