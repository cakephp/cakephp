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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\File;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\HtmlHelper;

/**
 * HtmlHelperTest class
 */
class HtmlHelperTest extends TestCase
{

    /**
     * Regexp for CDATA start block
     *
     * @var string
     */
    public $cDataStart = 'preg:/^\/\/<!\[CDATA\[[\n\r]*/';

    /**
     * Regexp for CDATA end block
     *
     * @var string
     */
    public $cDataEnd = 'preg:/[^\]]*\]\]\>[\s\r\n]*/';

    /**
     * Helper to be tested
     *
     * @var \Cake\View\Helper\HtmlHelper
     */
    public $Html;

    /**
     * Mocked view
     *
     * @var \Cake\View\View|\PHPUnit_Framework_MockObject_MockObject
     */
    public $View;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->View = $this->getMockBuilder('Cake\View\View')
            ->setMethods(['append'])
            ->getMock();
        $this->Html = new HtmlHelper($this->View);
        $this->Html->request = new Request();
        $this->Html->request->webroot = '';
        $this->Html->Url->request = $this->Html->request;

        Configure::write('App.namespace', 'TestApp');
        Plugin::load(['TestTheme']);
        Configure::write('Asset.timestamp', false);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        Plugin::unload('TestTheme');
        unset($this->Html, $this->View);
    }

    /**
     * testDocType method
     *
     * @return void
     */
    public function testDocType()
    {
        $result = $this->Html->docType();
        $expected = '<!DOCTYPE html>';
        $this->assertEquals($expected, $result);

        $result = $this->Html->docType('html4-strict');
        $expected = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        $this->assertEquals($expected, $result);

        $this->assertNull($this->Html->docType('non-existing-doctype'));
    }

    /**
     * testLink method
     *
     * @return void
     */
    public function testLink()
    {
        Router::connect('/:controller/:action/*');

        $this->Html->request->webroot = '';

        $result = $this->Html->link('/home');
        $expected = ['a' => ['href' => '/home'], 'preg:/\/home/', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link(['action' => 'login', '<[You]>']);
        $expected = [
            'a' => ['href' => '/login/%3C%5BYou%5D%3E'],
            'preg:/\/login\/&lt;\[You\]&gt;/',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        Router::reload();
        Router::connect('/:controller', ['action' => 'index']);
        Router::connect('/:controller/:action/*');

        $result = $this->Html->link('Posts', ['controller' => 'posts', 'action' => 'index', '_full' => true]);
        $expected = ['a' => ['href' => Router::fullBaseUrl() . '/posts'], 'Posts', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Home', '/home', ['confirm' => 'Are you sure you want to do this?']);
        $expected = [
            'a' => ['href' => '/home', 'onclick' => 'if (confirm(&quot;Are you sure you want to do this?&quot;)) { return true; } return false;'],
            'Home',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Home', '/home', ['escape' => false, 'confirm' => 'Confirm\'s "nightmares"']);
        $expected = [
            'a' => ['href' => '/home', 'onclick' => 'if (confirm(&quot;Confirm&#039;s \&quot;nightmares\&quot;&quot;)) { return true; } return false;'],
            'Home',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Home', '/home', ['onclick' => 'someFunction();']);
        $expected = [
            'a' => ['href' => '/home', 'onclick' => 'someFunction();'],
            'Home',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#');
        $expected = [
            'a' => ['href' => '#'],
            'Next &gt;',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', ['escape' => true]);
        $expected = [
            'a' => ['href' => '#'],
            'Next &gt;',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', ['escape' => 'utf-8']);
        $expected = [
            'a' => ['href' => '#'],
            'Next &gt;',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', ['escape' => false]);
        $expected = [
            'a' => ['href' => '#'],
            'Next >',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', [
            'title' => 'to escape &#8230; or not escape?',
            'escape' => false
        ]);
        $expected = [
            'a' => ['href' => '#', 'title' => 'to escape &#8230; or not escape?'],
            'Next >',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', [
            'title' => 'to escape &#8230; or not escape?',
            'escape' => true
        ]);
        $expected = [
            'a' => ['href' => '#', 'title' => 'to escape &amp;#8230; or not escape?'],
            'Next &gt;',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', [
            'title' => 'Next >',
            'escapeTitle' => false
        ]);
        $expected = [
            'a' => ['href' => '#', 'title' => 'Next &gt;'],
            'Next >',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Original size', [
            'controller' => 'images', 'action' => 'view', 3, '?' => ['height' => 100, 'width' => 200]
        ]);
        $expected = [
            'a' => ['href' => '/images/view/3?height=100&amp;width=200'],
            'Original size',
            '/a'
        ];
        $this->assertHtml($expected, $result);

        Configure::write('Asset.timestamp', false);

        $result = $this->Html->link($this->Html->image('test.gif'), '#', ['escape' => false]);
        $expected = [
            'a' => ['href' => '#'],
            'img' => ['src' => 'img/test.gif', 'alt' => ''],
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link($this->Html->image('test.gif'), '#', [
            'title' => 'hey "howdy"',
            'escapeTitle' => false
        ]);
        $expected = [
            'a' => ['href' => '#', 'title' => 'hey &quot;howdy&quot;'],
            'img' => ['src' => 'img/test.gif', 'alt' => ''],
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('test.gif', ['url' => '#']);
        $expected = [
            'a' => ['href' => '#'],
            'img' => ['src' => 'img/test.gif', 'alt' => ''],
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link($this->Html->image('../favicon.ico'), '#', ['escape' => false]);
        $expected = [
            'a' => ['href' => '#'],
            'img' => ['src' => 'img/../favicon.ico', 'alt' => ''],
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('../favicon.ico', ['url' => '#']);
        $expected = [
            'a' => ['href' => '#'],
            'img' => ['src' => 'img/../favicon.ico', 'alt' => ''],
            '/a'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('http://www.example.org?param1=value1&param2=value2');
        $expected = ['a' => ['href' => 'http://www.example.org?param1=value1&amp;param2=value2'], 'http://www.example.org?param1=value1&amp;param2=value2', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('alert', 'javascript:alert(\'cakephp\');');
        $expected = ['a' => ['href' => 'javascript:alert(&#039;cakephp&#039;);'], 'alert', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('write me', 'mailto:example@cakephp.org');
        $expected = ['a' => ['href' => 'mailto:example@cakephp.org'], 'write me', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('call me on 0123465-798', 'tel:0123465-798');
        $expected = ['a' => ['href' => 'tel:0123465-798'], 'call me on 0123465-798', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('text me on 0123465-798', 'sms:0123465-798');
        $expected = ['a' => ['href' => 'sms:0123465-798'], 'text me on 0123465-798', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('say hello to 0123465-798', 'sms:0123465-798?body=hello there');
        $expected = ['a' => ['href' => 'sms:0123465-798?body=hello there'], 'say hello to 0123465-798', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('say hello to 0123465-798', 'sms:0123465-798?body=hello "cakephp"');
        $expected = ['a' => ['href' => 'sms:0123465-798?body=hello &quot;cakephp&quot;'], 'say hello to 0123465-798', '/a'];
        $this->assertHtml($expected, $result);
    }

    /**
     * testImageTag method
     *
     * @return void
     */
    public function testImageTag()
    {
        Router::connect('/:controller', ['action' => 'index']);
        Router::connect('/:controller/:action/*');

        $this->Html->request->webroot = '';

        $result = $this->Html->image('test.gif');
        $expected = ['img' => ['src' => 'img/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('http://google.com/logo.gif');
        $expected = ['img' => ['src' => 'http://google.com/logo.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('//google.com/logo.gif');
        $expected = ['img' => ['src' => '//google.com/logo.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image(['controller' => 'test', 'action' => 'view', 1, '_ext' => 'gif']);
        $expected = ['img' => ['src' => '/test/view/1.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('/test/view/1.gif');
        $expected = ['img' => ['src' => '/test/view/1.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('cid:cakephp_logo');
        $expected = ['img' => ['src' => 'cid:cakephp_logo', 'alt' => '']];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test image() with query strings.
     *
     * @return void
     */
    public function testImageQueryString()
    {
        $result = $this->Html->image('test.gif?one=two&three=four');
        $expected = ['img' => ['src' => 'img/test.gif?one=two&amp;three=four', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image([
            'controller' => 'images',
            'action' => 'display',
            'test',
            '?' => ['one' => 'two', 'three' => 'four']
        ]);
        $expected = ['img' => ['src' => '/images/display/test?one=two&amp;three=four', 'alt' => '']];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that image works with pathPrefix.
     *
     * @return void
     */
    public function testImagePathPrefix()
    {
        $result = $this->Html->image('test.gif', ['pathPrefix' => '/my/custom/path/']);
        $expected = ['img' => ['src' => '/my/custom/path/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('test.gif', ['pathPrefix' => 'http://cakephp.org/assets/img/']);
        $expected = ['img' => ['src' => 'http://cakephp.org/assets/img/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('test.gif', ['pathPrefix' => '//cakephp.org/assets/img/']);
        $expected = ['img' => ['src' => '//cakephp.org/assets/img/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $previousConfig = Configure::read('App.imageBaseUrl');
        Configure::write('App.imageBaseUrl', '//cdn.cakephp.org/img/');
        $result = $this->Html->image('test.gif');
        $expected = ['img' => ['src' => '//cdn.cakephp.org/img/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);
        Configure::write('App.imageBaseUrl', $previousConfig);
    }

    /**
     * Test that image() works with fullBase and a webroot not equal to /
     *
     * @return void
     */
    public function testImageWithFullBase()
    {
        $result = $this->Html->image('test.gif', ['fullBase' => true]);
        $here = $this->Html->Url->build('/', true);
        $expected = ['img' => ['src' => $here . 'img/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('sub/test.gif', ['fullBase' => true]);
        $here = $this->Html->Url->build('/', true);
        $expected = ['img' => ['src' => $here . 'img/sub/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $request = $this->Html->request;
        $request->webroot = '/myproject/';
        $request->base = '/myproject';
        Router::pushRequest($request);

        $result = $this->Html->image('sub/test.gif', ['fullBase' => true]);
        $here = $this->Html->Url->build('/', true);
        $expected = ['img' => ['src' => $here . 'img/sub/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);
    }

    /**
     * test image() with Asset.timestamp
     *
     * @return void
     */
    public function testImageWithTimestampping()
    {
        Configure::write('Asset.timestamp', 'force');

        $this->Html->request->webroot = '/';
        $result = $this->Html->image('cake.icon.png');
        $expected = ['img' => ['src' => 'preg:/\/img\/cake\.icon\.png\?\d+/', 'alt' => '']];
        $this->assertHtml($expected, $result);

        Configure::write('debug', false);
        Configure::write('Asset.timestamp', 'force');

        $result = $this->Html->image('cake.icon.png');
        $expected = ['img' => ['src' => 'preg:/\/img\/cake\.icon\.png\?\d+/', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $this->Html->request->webroot = '/testing/longer/';
        $result = $this->Html->image('cake.icon.png');
        $expected = [
            'img' => ['src' => 'preg:/\/testing\/longer\/img\/cake\.icon\.png\?[0-9]+/', 'alt' => '']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests creation of an image tag using a theme and asset timestamping
     *
     * @return void
     */
    public function testImageTagWithTheme()
    {
        $this->skipIf(!is_writable(WWW_ROOT), 'Cannot write to webroot.');

        $testfile = WWW_ROOT . 'test_theme/img/__cake_test_image.gif';
        $File = new File($testfile, true);

        Configure::write('Asset.timestamp', true);
        Configure::write('debug', true);

        $this->Html->Url->request->webroot = '/';
        $this->Html->Url->theme = 'TestTheme';
        $result = $this->Html->image('__cake_test_image.gif');
        $expected = [
            'img' => [
                'src' => 'preg:/\/test_theme\/img\/__cake_test_image\.gif\?\d+/',
                'alt' => ''
            ]];
            $this->assertHtml($expected, $result);

            $this->Html->Url->request->webroot = '/testing/';
            $result = $this->Html->image('__cake_test_image.gif');
            $expected = [
            'img' => [
                'src' => 'preg:/\/testing\/test_theme\/img\/__cake_test_image\.gif\?\d+/',
                'alt' => ''
            ]];
            $this->assertHtml($expected, $result);
            $File->delete();
    }

    /**
     * test theme assets in main webroot path
     *
     * @return void
     */
    public function testThemeAssetsInMainWebrootPath()
    {
        Configure::write('App.wwwRoot', TEST_APP . 'webroot/');

        $this->Html->Url->theme = 'TestTheme';
        $result = $this->Html->css('webroot_test');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*test_theme\/css\/webroot_test\.css/']
        ];
        $this->assertHtml($expected, $result);

        $this->Html->theme = 'TestTheme';
        $result = $this->Html->css('theme_webroot');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*test_theme\/css\/theme_webroot\.css/']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testStyle method
     *
     * @return void
     */
    public function testStyle()
    {
        $result = $this->Html->style(['display' => 'none', 'margin' => '10px']);
        $this->assertEquals('display:none; margin:10px;', $result);

        $result = $this->Html->style(['display' => 'none', 'margin' => '10px'], false);
        $this->assertEquals("display:none;\nmargin:10px;", $result);
    }

    /**
     * testCssLink method
     *
     * @return void
     */
    public function testCssLink()
    {
        $result = $this->Html->css('screen');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*css\/screen\.css/']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('screen.css', ['once' => false]);
        $this->assertHtml($expected, $result);

        Plugin::load('TestPlugin');
        $result = $this->Html->css('TestPlugin.style', ['plugin' => false]);
        $expected['link']['href'] = 'preg:/.*css\/TestPlugin\.style\.css/';
        $this->assertHtml($expected, $result);
        Plugin::unload('TestPlugin');

        $result = $this->Html->css('my.css.library');
        $expected['link']['href'] = 'preg:/.*css\/my\.css\.library\.css/';
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('screen.css?1234');
        $expected['link']['href'] = 'preg:/.*css\/screen\.css\?1234/';
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('screen.css?with=param&other=param');
        $expected['link']['href'] = 'css/screen.css?with=param&amp;other=param';
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('http://whatever.com/screen.css?1234');
        $expected['link']['href'] = 'preg:/http:\/\/.*\/screen\.css\?1234/';
        $this->assertHtml($expected, $result);

        Configure::write('App.cssBaseUrl', '//cdn.cakephp.org/css/');
        $result = $this->Html->css('cake.generic');
        $expected['link']['href'] = '//cdn.cakephp.org/css/cake.generic.css';
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('//example.com/css/cake.generic.css');
        $expected['link']['href'] = 'preg:/.*example\.com\/css\/cake\.generic\.css/';
        $this->assertHtml($expected, $result);

        $result = explode("\n", trim($this->Html->css(['cake', 'vendor.generic'])));
        $expected['link']['href'] = 'preg:/.*css\/cake\.css/';
        $this->assertHtml($expected, $result[0]);
        $expected['link']['href'] = 'preg:/.*css\/vendor\.generic\.css/';
        $this->assertHtml($expected, $result[1]);
        $this->assertCount(2, $result);

        $this->View->expects($this->at(0))
            ->method('append')
            ->with('css', $this->matchesRegularExpression('/css_in_head.css/'));

        $this->View->expects($this->at(1))
            ->method('append')
            ->with('css', $this->matchesRegularExpression('/more_css_in_head.css/'));

        $result = $this->Html->css('css_in_head', ['block' => true]);
        $this->assertNull($result);

        $result = $this->Html->css('more_css_in_head', ['block' => true]);
        $this->assertNull($result);

        $result = $this->Html->css('import-screen', ['rel' => 'import']);
        $expected = [
            '<style',
            'preg:/@import url\(.*css\/import-screen\.css\);/',
            '/style'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test css() with once option.
     *
     * @return void
     */
    public function testCssLinkOnce()
    {
        $result = $this->Html->css('screen', ['once' => true]);
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*css\/screen\.css/']
        ];
        $this->assertHtml($expected, $result);

        // Default is once=true
        $result = $this->Html->css('screen');
        $this->assertEquals('', $result);

        $result = $this->Html->css('screen', ['once' => false]);
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*css\/screen\.css/']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCssWithFullBase method
     *
     * @return void
     */
    public function testCssWithFullBase()
    {
        Configure::write('Asset.filter.css', false);
        $here = $this->Html->Url->build('/', true);

        $result = $this->Html->css('screen', ['fullBase' => true]);
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => $here . 'css/screen.css']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPluginCssLink method
     *
     * @return void
     */
    public function testPluginCssLink()
    {
        Plugin::load('TestPlugin');

        $result = $this->Html->css('TestPlugin.test_plugin_asset');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*test_plugin\/css\/test_plugin_asset\.css/']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('TestPlugin.test_plugin_asset.css', ['once' => false]);
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('TestPlugin.my.css.library');
        $expected['link']['href'] = 'preg:/.*test_plugin\/css\/my\.css\.library\.css/';
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('TestPlugin.test_plugin_asset.css?1234');
        $expected['link']['href'] = 'preg:/.*test_plugin\/css\/test_plugin_asset\.css\?1234/';
        $this->assertHtml($expected, $result);

        $result = explode("\n", trim($this->Html->css(
            ['TestPlugin.test_plugin_asset', 'TestPlugin.vendor.generic'],
            ['once' => false]
        )));
        $expected['link']['href'] = 'preg:/.*test_plugin\/css\/test_plugin_asset\.css/';
        $this->assertHtml($expected, $result[0]);
        $expected['link']['href'] = 'preg:/.*test_plugin\/css\/vendor\.generic\.css/';
        $this->assertHtml($expected, $result[1]);
        $this->assertCount(2, $result);

        Plugin::unload('TestPlugin');
    }

    /**
     * test use of css() and timestamping
     *
     * @return void
     */
    public function testCssTimestamping()
    {
        Configure::write('debug', true);
        Configure::write('Asset.timestamp', true);

        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => '']
        ];

        $result = $this->Html->css('cake.generic', ['once' => false]);
        $expected['link']['href'] = 'preg:/.*css\/cake\.generic\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        Configure::write('debug', false);

        $result = $this->Html->css('cake.generic', ['once' => false]);
        $expected['link']['href'] = 'preg:/.*css\/cake\.generic\.css/';
        $this->assertHtml($expected, $result);

        Configure::write('Asset.timestamp', 'force');

        $result = $this->Html->css('cake.generic', ['once' => false]);
        $expected['link']['href'] = 'preg:/.*css\/cake\.generic\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        $this->Html->request->webroot = '/testing/';
        $result = $this->Html->css('cake.generic', ['once' => false]);
        $expected['link']['href'] = 'preg:/\/testing\/css\/cake\.generic\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        $this->Html->request->webroot = '/testing/longer/';
        $result = $this->Html->css('cake.generic', ['once' => false]);
        $expected['link']['href'] = 'preg:/\/testing\/longer\/css\/cake\.generic\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);
    }

    /**
     * test use of css() and timestamping with plugin syntax
     *
     * @return void
     */
    public function testPluginCssTimestamping()
    {
        Plugin::load('TestPlugin');

        Configure::write('debug', true);
        Configure::write('Asset.timestamp', true);

        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => '']
        ];

        $result = $this->Html->css('TestPlugin.test_plugin_asset', ['once' => false]);
        $expected['link']['href'] = 'preg:/.*test_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        Configure::write('debug', false);

        $result = $this->Html->css('TestPlugin.test_plugin_asset', ['once' => false]);
        $expected['link']['href'] = 'preg:/.*test_plugin\/css\/test_plugin_asset\.css/';
        $this->assertHtml($expected, $result);

        Configure::write('Asset.timestamp', 'force');

        $result = $this->Html->css('TestPlugin.test_plugin_asset', ['once' => false]);
        $expected['link']['href'] = 'preg:/.*test_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        $this->Html->request->webroot = '/testing/';
        $result = $this->Html->css('TestPlugin.test_plugin_asset', ['once' => false]);
        $expected['link']['href'] = 'preg:/\/testing\/test_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        $this->Html->request->webroot = '/testing/longer/';
        $result = $this->Html->css('TestPlugin.test_plugin_asset', ['once' => false]);
        $expected['link']['href'] = 'preg:/\/testing\/longer\/test_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        Plugin::unload('TestPlugin');
    }

    /**
     * Resource names must be treated differently for css() and script()
     *
     * @return void
     */
    public function testBufferedCssAndScriptWithIdenticalResourceName()
    {
        $this->View->expects($this->at(0))
            ->method('append')
            ->with('css', $this->stringContains('test.min.css'));
        $this->View->expects($this->at(1))
            ->method('append')
            ->with('script', $this->stringContains('test.min.js'));
        $this->Html->css('test.min', ['block' => true]);
        $this->Html->script('test.min', ['block' => true]);
    }

    /**
     * test timestamp enforcement for script tags.
     *
     * @return void
     */
    public function testScriptTimestamping()
    {
        $this->skipIf(!is_writable(WWW_ROOT . 'js'), 'webroot/js is not Writable, timestamp testing has been skipped.');
        Configure::write('debug', true);
        Configure::write('Asset.timestamp', true);

        touch(WWW_ROOT . 'js/__cake_js_test.js');
        $timestamp = substr(strtotime('now'), 0, 8);

        $result = $this->Html->script('__cake_js_test', ['once' => false]);
        $this->assertRegExp('/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"/', $result, 'Timestamp value not found %s');

        Configure::write('debug', false);
        Configure::write('Asset.timestamp', 'force');
        $result = $this->Html->script('__cake_js_test', ['once' => false]);
        $this->assertRegExp('/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"/', $result, 'Timestamp value not found %s');
        unlink(WWW_ROOT . 'js/__cake_js_test.js');
        Configure::write('Asset.timestamp', false);
    }

    /**
     * test timestamp enforcement for script tags with plugin syntax.
     *
     * @return void
     */
    public function testPluginScriptTimestamping()
    {
        Plugin::load('TestPlugin');

        $pluginPath = Plugin::path('TestPlugin');
        $pluginJsPath = $pluginPath . 'webroot/js';
        $this->skipIf(!is_writable($pluginJsPath), $pluginJsPath . ' is not Writable, timestamp testing has been skipped.');

        Configure::write('debug', true);
        Configure::write('Asset.timestamp', true);

        touch($pluginJsPath . DS . '__cake_js_test.js');
        $timestamp = substr(strtotime('now'), 0, 8);

        $result = $this->Html->script('TestPlugin.__cake_js_test', ['once' => false]);
        $this->assertRegExp('/test_plugin\/js\/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"/', $result, 'Timestamp value not found %s');

        Configure::write('debug', false);
        Configure::write('Asset.timestamp', 'force');
        $result = $this->Html->script('TestPlugin.__cake_js_test', ['once' => false]);
        $this->assertRegExp('/test_plugin\/js\/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"/', $result, 'Timestamp value not found %s');
        unlink($pluginJsPath . DS . '__cake_js_test.js');
        Configure::write('Asset.timestamp', false);

        Plugin::unload('TestPlugin');
    }

    /**
     * test that scripts added with uses() are only ever included once.
     * test script tag generation
     *
     * @return void
     */
    public function testScript()
    {
        $result = $this->Html->script('foo');
        $expected = [
            'script' => ['src' => 'js/foo.js']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script(['foobar', 'bar']);
        $expected = [
            ['script' => ['src' => 'js/foobar.js']],
            '/script',
            ['script' => ['src' => 'js/bar.js']],
            '/script',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('jquery-1.3');
        $expected = [
            'script' => ['src' => 'js/jquery-1.3.js']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('test.json');
        $expected = [
            'script' => ['src' => 'js/test.json.js']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('http://example.com/test.json');
        $expected = [
            'script' => ['src' => 'http://example.com/test.json']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('/plugin/js/jquery-1.3.2.js?someparam=foo');
        $expected = [
            'script' => ['src' => '/plugin/js/jquery-1.3.2.js?someparam=foo']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('test.json.js?foo=bar');
        $expected = [
            'script' => ['src' => 'js/test.json.js?foo=bar']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('test.json.js?foo=bar&other=test');
        $expected = [
            'script' => ['src' => 'js/test.json.js?foo=bar&amp;other=test']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('foo2', ['pathPrefix' => '/my/custom/path/']);
        $expected = [
            'script' => ['src' => '/my/custom/path/foo2.js']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('foo3', ['pathPrefix' => 'http://cakephp.org/assets/js/']);
        $expected = [
            'script' => ['src' => 'http://cakephp.org/assets/js/foo3.js']
        ];
        $this->assertHtml($expected, $result);

        $previousConfig = Configure::read('App.jsBaseUrl');
        Configure::write('App.jsBaseUrl', '//cdn.cakephp.org/js/');
        $result = $this->Html->script('foo4');
        $expected = [
            'script' => ['src' => '//cdn.cakephp.org/js/foo4.js']
        ];
        $this->assertHtml($expected, $result);
        Configure::write('App.jsBaseUrl', $previousConfig);

        $result = $this->Html->script('foo');
        $this->assertNull($result, 'Script returned upon duplicate inclusion %s');

        $result = $this->Html->script(['foo', 'bar', 'baz']);
        $this->assertNotRegExp('/foo.js/', $result);

        $result = $this->Html->script('foo', ['once' => false]);
        $this->assertNotNull($result);

        $result = $this->Html->script('jquery-1.3.2', ['defer' => true, 'encoding' => 'utf-8']);
        $expected = [
            'script' => ['src' => 'js/jquery-1.3.2.js', 'defer' => 'defer', 'encoding' => 'utf-8']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test that plugin scripts added with uses() are only ever included once.
     * test script tag generation with plugin syntax
     *
     * @return void
     */
    public function testPluginScript()
    {
        Plugin::load('TestPlugin');

        $result = $this->Html->script('TestPlugin.foo');
        $expected = [
            'script' => ['src' => 'test_plugin/js/foo.js']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script(['TestPlugin.foobar', 'TestPlugin.bar']);
        $expected = [
            ['script' => ['src' => 'test_plugin/js/foobar.js']],
            '/script',
            ['script' => ['src' => 'test_plugin/js/bar.js']],
            '/script',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('TestPlugin.jquery-1.3');
        $expected = [
            'script' => ['src' => 'test_plugin/js/jquery-1.3.js']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('TestPlugin.test.json');
        $expected = [
            'script' => ['src' => 'test_plugin/js/test.json.js']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('TestPlugin./jquery-1.3.2.js?someparam=foo');
        $expected = [
            'script' => ['src' => 'test_plugin/jquery-1.3.2.js?someparam=foo']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('TestPlugin.test.json.js?foo=bar');
        $expected = [
            'script' => ['src' => 'test_plugin/js/test.json.js?foo=bar']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('TestPlugin.foo');
        $this->assertNull($result, 'Script returned upon duplicate inclusion %s');

        $result = $this->Html->script(['TestPlugin.foo', 'TestPlugin.bar', 'TestPlugin.baz']);
        $this->assertNotRegExp('/test_plugin\/js\/foo.js/', $result);

        $result = $this->Html->script('TestPlugin.foo', ['once' => false]);
        $this->assertNotNull($result);

        $result = $this->Html->script('TestPlugin.jquery-1.3.2', ['defer' => true, 'encoding' => 'utf-8']);
        $expected = [
            'script' => ['src' => 'test_plugin/js/jquery-1.3.2.js', 'defer' => 'defer', 'encoding' => 'utf-8']
        ];
        $this->assertHtml($expected, $result);

        Plugin::unload('TestPlugin');
    }

    /**
     * test that script() works with blocks.
     *
     * @return void
     */
    public function testScriptWithBlocks()
    {
        $this->View->expects($this->at(0))
            ->method('append')
            ->with('script', $this->matchesRegularExpression('/script_in_head.js/'));

        $this->View->expects($this->at(1))
            ->method('append')
            ->with('headScripts', $this->matchesRegularExpression('/second_script.js/'));

        $result = $this->Html->script('script_in_head', ['block' => true]);
        $this->assertNull($result);

        $result = $this->Html->script('second_script', ['block' => 'headScripts']);
        $this->assertNull($result);
    }

    /**
     * testScriptWithFullBase method
     *
     * @return void
     */
    public function testScriptWithFullBase()
    {
        $here = $this->Html->Url->build('/', true);

        $result = $this->Html->script('foo', ['fullBase' => true]);
        $expected = [
            'script' => ['src' => $here . 'js/foo.js']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script(['foobar', 'bar'], ['fullBase' => true]);
        $expected = [
            ['script' => ['src' => $here . 'js/foobar.js']],
            '/script',
            ['script' => ['src' => $here . 'js/bar.js']],
            '/script',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test a script file in the webroot/theme dir.
     *
     * @return void
     */
    public function testScriptInTheme()
    {
        $this->skipIf(!is_writable(WWW_ROOT), 'Cannot write to webroot.');

        $testfile = WWW_ROOT . '/test_theme/js/__test_js.js';
        $File = new File($testfile, true);

        $this->Html->Url->request->webroot = '/';
        $this->Html->Url->theme = 'TestTheme';
        $result = $this->Html->script('__test_js.js');
        $expected = [
            'script' => ['src' => '/test_theme/js/__test_js.js']
        ];
        $this->assertHtml($expected, $result);
        $File->delete();
    }

    /**
     * test Script block generation
     *
     * @return void
     */
    public function testScriptBlock()
    {
        $result = $this->Html->scriptBlock('window.foo = 2;');
        $expected = [
            '<script',
            $this->cDataStart,
            'window.foo = 2;',
            $this->cDataEnd,
            '/script',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->scriptBlock('window.foo = 2;', ['type' => 'text/x-handlebars-template']);
        $expected = [
            'script' => ['type' => 'text/x-handlebars-template'],
            $this->cDataStart,
            'window.foo = 2;',
            $this->cDataEnd,
            '/script',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->scriptBlock('window.foo = 2;', ['safe' => false]);
        $expected = [
            '<script',
            'window.foo = 2;',
            '/script',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->scriptBlock('window.foo = 2;', ['safe' => true]);
        $expected = [
            '<script',
            $this->cDataStart,
            'window.foo = 2;',
            $this->cDataEnd,
            '/script',
        ];
        $this->assertHtml($expected, $result);

        $this->View->expects($this->at(0))
            ->method('append')
            ->with('script', $this->matchesRegularExpression('/window\.foo\s\=\s2;/'));

        $this->View->expects($this->at(1))
            ->method('append')
            ->with('scriptTop', $this->stringContains('alert('));

        $result = $this->Html->scriptBlock('window.foo = 2;', ['block' => true]);
        $this->assertNull($result);

        $result = $this->Html->scriptBlock('alert("hi")', ['block' => 'scriptTop']);
        $this->assertNull($result);

        $result = $this->Html->scriptBlock('window.foo = 2;', ['safe' => false, 'encoding' => 'utf-8']);
        $expected = [
            'script' => ['encoding' => 'utf-8'],
            'window.foo = 2;',
            '/script',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test script tag output buffering when using scriptStart() and scriptEnd();
     *
     * @return void
     */
    public function testScriptStartAndScriptEnd()
    {
        $result = $this->Html->scriptStart(['safe' => true]);
        $this->assertNull($result);
        echo 'this is some javascript';

        $result = $this->Html->scriptEnd();
        $expected = [
            '<script',
            $this->cDataStart,
            'this is some javascript',
            $this->cDataEnd,
            '/script'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->scriptStart(['safe' => false]);
        $this->assertNull($result);
        echo 'this is some javascript';

        $result = $this->Html->scriptEnd();
        $expected = [
            '<script',
            'this is some javascript',
            '/script'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->scriptStart(['safe' => true, 'type' => 'text/x-handlebars-template']);
        $this->assertNull($result);
        echo 'this is some template';

        $result = $this->Html->scriptEnd();
        $expected = [
            'script' => ['type' => 'text/x-handlebars-template'],
            $this->cDataStart,
            'this is some template',
            $this->cDataEnd,
            '/script'
        ];
        $this->assertHtml($expected, $result);

        $this->View->expects($this->once())
            ->method('append');
        $result = $this->Html->scriptStart(['safe' => false, 'block' => true]);
        $this->assertNull($result);
        echo 'this is some javascript';

        $result = $this->Html->scriptEnd();
        $this->assertNull($result);
    }

    /**
     * testCharsetTag method
     *
     * @return void
     */
    public function testCharsetTag()
    {
        Configure::write('App.encoding', null);
        $result = $this->Html->charset();
        $expected = ['meta' => ['charset' => 'utf-8']];
        $this->assertHtml($expected, $result);

        Configure::write('App.encoding', 'ISO-8859-1');
        $result = $this->Html->charset();
        $expected = ['meta' => ['charset' => 'iso-8859-1']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->charset('UTF-7');
        $expected = ['meta' => ['charset' => 'UTF-7']];
        $this->assertHtml($expected, $result);
    }

    /**
     * testGetCrumb and addCrumb method
     *
     * @return void
     */
    public function testBreadcrumb()
    {
        $this->assertNull($this->Html->getCrumbs());

        $this->Html->addCrumb('First', '#first');
        $this->Html->addCrumb('Second', '#second');
        $this->Html->addCrumb('Third', '#third');

        $result = $this->Html->getCrumbs();
        $expected = [
            ['a' => ['href' => '#first']],
            'First',
            '/a',
            '&raquo;',
            ['a' => ['href' => '#second']],
            'Second',
            '/a',
            '&raquo;',
            ['a' => ['href' => '#third']],
            'Third',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->getCrumbs(' &gt; ');
        $expected = [
            ['a' => ['href' => '#first']],
            'First',
            '/a',
            ' &gt; ',
            ['a' => ['href' => '#second']],
            'Second',
            '/a',
            ' &gt; ',
            ['a' => ['href' => '#third']],
            'Third',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $this->Html->addCrumb('Fourth', null);

        $result = $this->Html->getCrumbs();
        $expected = [
            ['a' => ['href' => '#first']],
            'First',
            '/a',
            '&raquo;',
            ['a' => ['href' => '#second']],
            'Second',
            '/a',
            '&raquo;',
            ['a' => ['href' => '#third']],
            'Third',
            '/a',
            '&raquo;',
            'Fourth'
        ];
        $this->assertHtml($expected, $result);

        $this->Html->addCrumb('Fifth', [
            'plugin' => false,
            'controller' => 'controller',
            'action' => 'action',
        ]);
        $result = $this->Html->getCrumbs('-', 'Start');
        $expected = [
            ['a' => ['href' => '/']],
            'Start',
            '/a',
            '-',
            ['a' => ['href' => '#first']],
            'First',
            '/a',
            '-',
            ['a' => ['href' => '#second']],
            'Second',
            '/a',
            '-',
            ['a' => ['href' => '#third']],
            'Third',
            '/a',
            '-',
            'Fourth',
            '-',
            ['a' => ['href' => '/controller/action']],
            'Fifth',
            '/a',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test the array form of $startText
     *
     * @return void
     */
    public function testGetCrumbFirstLink()
    {
        $result = $this->Html->getCrumbList([], 'Home');
        $expected = [
            '<ul',
            ['li' => ['class' => 'first']],
            ['a' => ['href' => '/']], 'Home', '/a',
            '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result);

        $this->Html->addCrumb('First', '#first');
        $this->Html->addCrumb('Second', '#second');

        $result = $this->Html->getCrumbs(' - ', ['url' => '/home', 'text' => '<img src="/home.png" />', 'escape' => false]);
        $expected = [
            ['a' => ['href' => '/home']],
            'img' => ['src' => '/home.png'],
            '/a',
            ' - ',
            ['a' => ['href' => '#first']],
            'First',
            '/a',
            ' - ',
            ['a' => ['href' => '#second']],
            'Second',
            '/a',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testNestedList method
     *
     * @return void
     */
    public function testNestedList()
    {
        $list = [
            'Item 1',
            'Item 2' => [
                'Item 2.1'
            ],
            'Item 3',
            'Item 4' => [
                'Item 4.1',
                'Item 4.2',
                'Item 4.3' => [
                    'Item 4.3.1',
                    'Item 4.3.2'
                ]
            ],
            'Item 5' => [
                'Item 5.1',
                'Item 5.2'
            ]
        ];

        $result = $this->Html->nestedList($list);
        $expected = [
            '<ul',
            '<li', 'Item 1', '/li',
            '<li', 'Item 2',
            '<ul', '<li', 'Item 2.1', '/li', '/ul',
            '/li',
            '<li', 'Item 3', '/li',
            '<li', 'Item 4',
            '<ul',
            '<li', 'Item 4.1', '/li',
            '<li', 'Item 4.2', '/li',
            '<li', 'Item 4.3',
            '<ul',
            '<li', 'Item 4.3.1', '/li',
            '<li', 'Item 4.3.2', '/li',
            '/ul',
            '/li',
            '/ul',
            '/li',
            '<li', 'Item 5',
            '<ul',
            '<li', 'Item 5.1', '/li',
            '<li', 'Item 5.2', '/li',
            '/ul',
            '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->nestedList($list);
        $this->assertHtml($expected, $result);

        $result = $this->Html->nestedList($list, ['tag' => 'ol']);
        $expected = [
            '<ol',
            '<li', 'Item 1', '/li',
            '<li', 'Item 2',
            '<ol', '<li', 'Item 2.1', '/li', '/ol',
            '/li',
            '<li', 'Item 3', '/li',
            '<li', 'Item 4',
            '<ol',
            '<li', 'Item 4.1', '/li',
            '<li', 'Item 4.2', '/li',
            '<li', 'Item 4.3',
            '<ol',
            '<li', 'Item 4.3.1', '/li',
            '<li', 'Item 4.3.2', '/li',
            '/ol',
            '/li',
            '/ol',
            '/li',
            '<li', 'Item 5',
            '<ol',
            '<li', 'Item 5.1', '/li',
            '<li', 'Item 5.2', '/li',
            '/ol',
            '/li',
            '/ol'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->nestedList($list, ['tag' => 'ol']);
        $this->assertHtml($expected, $result);

        $result = $this->Html->nestedList($list, ['class' => 'list']);
        $expected = [
            ['ul' => ['class' => 'list']],
            '<li', 'Item 1', '/li',
            '<li', 'Item 2',
            ['ul' => ['class' => 'list']], '<li', 'Item 2.1', '/li', '/ul',
            '/li',
            '<li', 'Item 3', '/li',
            '<li', 'Item 4',
            ['ul' => ['class' => 'list']],
            '<li', 'Item 4.1', '/li',
            '<li', 'Item 4.2', '/li',
            '<li', 'Item 4.3',
            ['ul' => ['class' => 'list']],
            '<li', 'Item 4.3.1', '/li',
            '<li', 'Item 4.3.2', '/li',
            '/ul',
            '/li',
            '/ul',
            '/li',
            '<li', 'Item 5',
            ['ul' => ['class' => 'list']],
            '<li', 'Item 5.1', '/li',
            '<li', 'Item 5.2', '/li',
            '/ul',
            '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->nestedList($list, [], ['class' => 'item']);
        $expected = [
            '<ul',
            ['li' => ['class' => 'item']], 'Item 1', '/li',
            ['li' => ['class' => 'item']], 'Item 2',
            '<ul', ['li' => ['class' => 'item']], 'Item 2.1', '/li', '/ul',
            '/li',
            ['li' => ['class' => 'item']], 'Item 3', '/li',
            ['li' => ['class' => 'item']], 'Item 4',
            '<ul',
            ['li' => ['class' => 'item']], 'Item 4.1', '/li',
            ['li' => ['class' => 'item']], 'Item 4.2', '/li',
            ['li' => ['class' => 'item']], 'Item 4.3',
            '<ul',
            ['li' => ['class' => 'item']], 'Item 4.3.1', '/li',
            ['li' => ['class' => 'item']], 'Item 4.3.2', '/li',
            '/ul',
            '/li',
            '/ul',
            '/li',
            ['li' => ['class' => 'item']], 'Item 5',
            '<ul',
            ['li' => ['class' => 'item']], 'Item 5.1', '/li',
            ['li' => ['class' => 'item']], 'Item 5.2', '/li',
            '/ul',
            '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->nestedList($list, [], ['even' => 'even', 'odd' => 'odd']);
        $expected = [
            '<ul',
            ['li' => ['class' => 'odd']], 'Item 1', '/li',
            ['li' => ['class' => 'even']], 'Item 2',
            '<ul', ['li' => ['class' => 'odd']], 'Item 2.1', '/li', '/ul',
            '/li',
            ['li' => ['class' => 'odd']], 'Item 3', '/li',
            ['li' => ['class' => 'even']], 'Item 4',
            '<ul',
            ['li' => ['class' => 'odd']], 'Item 4.1', '/li',
            ['li' => ['class' => 'even']], 'Item 4.2', '/li',
            ['li' => ['class' => 'odd']], 'Item 4.3',
            '<ul',
            ['li' => ['class' => 'odd']], 'Item 4.3.1', '/li',
            ['li' => ['class' => 'even']], 'Item 4.3.2', '/li',
            '/ul',
            '/li',
            '/ul',
            '/li',
            ['li' => ['class' => 'odd']], 'Item 5',
            '<ul',
            ['li' => ['class' => 'odd']], 'Item 5.1', '/li',
            ['li' => ['class' => 'even']], 'Item 5.2', '/li',
            '/ul',
            '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->nestedList($list, ['class' => 'list'], ['class' => 'item']);
        $expected = [
            ['ul' => ['class' => 'list']],
            ['li' => ['class' => 'item']], 'Item 1', '/li',
            ['li' => ['class' => 'item']], 'Item 2',
            ['ul' => ['class' => 'list']], ['li' => ['class' => 'item']], 'Item 2.1', '/li', '/ul',
            '/li',
            ['li' => ['class' => 'item']], 'Item 3', '/li',
            ['li' => ['class' => 'item']], 'Item 4',
            ['ul' => ['class' => 'list']],
            ['li' => ['class' => 'item']], 'Item 4.1', '/li',
            ['li' => ['class' => 'item']], 'Item 4.2', '/li',
            ['li' => ['class' => 'item']], 'Item 4.3',
            ['ul' => ['class' => 'list']],
            ['li' => ['class' => 'item']], 'Item 4.3.1', '/li',
            ['li' => ['class' => 'item']], 'Item 4.3.2', '/li',
            '/ul',
            '/li',
            '/ul',
            '/li',
            ['li' => ['class' => 'item']], 'Item 5',
            ['ul' => ['class' => 'list']],
            ['li' => ['class' => 'item']], 'Item 5.1', '/li',
            ['li' => ['class' => 'item']], 'Item 5.2', '/li',
            '/ul',
            '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testMeta method
     *
     * @return void
     */
    public function testMeta()
    {
        Router::connect('/:controller', ['action' => 'index']);

        $result = $this->Html->meta('this is an rss feed', ['controller' => 'posts', '_ext' => 'rss']);
        $expected = ['link' => ['href' => 'preg:/.*\/posts\.rss/', 'type' => 'application/rss+xml', 'rel' => 'alternate', 'title' => 'this is an rss feed']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('rss', ['controller' => 'posts', '_ext' => 'rss'], ['title' => 'this is an rss feed']);
        $expected = ['link' => ['href' => 'preg:/.*\/posts\.rss/', 'type' => 'application/rss+xml', 'rel' => 'alternate', 'title' => 'this is an rss feed']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('atom', ['controller' => 'posts', '_ext' => 'xml']);
        $expected = ['link' => ['href' => 'preg:/.*\/posts\.xml/', 'type' => 'application/atom+xml', 'title' => 'atom']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('non-existing');
        $expected = ['<meta'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('non-existing', 'some content');
        $expected = ['meta' => ['name' => 'non-existing', 'content' => 'some content']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('non-existing', '/posts.xpp', ['type' => 'atom']);
        $expected = ['link' => ['href' => 'preg:/.*\/posts\.xpp/', 'type' => 'application/atom+xml', 'title' => 'non-existing']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('atom', ['controller' => 'posts', '_ext' => 'xml'], ['link' => '/articles.rss']);
        $expected = ['link' => ['href' => 'preg:/.*\/articles\.rss/', 'type' => 'application/atom+xml', 'title' => 'atom']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('keywords', 'these, are, some, meta, keywords');
        $expected = ['meta' => ['name' => 'keywords', 'content' => 'these, are, some, meta, keywords']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('description', 'this is the meta description');
        $expected = ['meta' => ['name' => 'description', 'content' => 'this is the meta description']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('robots', 'ALL');
        $expected = ['meta' => ['name' => 'robots', 'content' => 'ALL']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('viewport', 'width=device-width');
        $expected = [
            'meta' => ['name' => 'viewport', 'content' => 'width=device-width']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta(['property' => 'og:site_name', 'content' => 'CakePHP']);
        $expected = [
            'meta' => ['property' => 'og:site_name', 'content' => 'CakePHP']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta(['link' => 'http://example.com/manifest', 'rel' => 'manifest']);
        $expected = [
            'link' => ['href' => 'http://example.com/manifest', 'rel' => 'manifest']
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * @return array
     */
    public function dataMetaLinksProvider()
    {
        return [
            ['canonical', ['controller' => 'posts', 'action' => 'show'], '/posts/show'],
            ['first', ['controller' => 'posts', 'action' => 'index'], '/posts'],
            ['last', ['controller' => 'posts', 'action' => 'index', '?' => ['page' => 10]], '/posts?page=10'],
            ['prev', ['controller' => 'posts', 'action' => 'index', '?' => ['page' => 4]], '/posts?page=4'],
            ['next', ['controller' => 'posts', 'action' => 'index', '?' => ['page' => 6]], '/posts?page=6']
        ];
    }

    /**
     * test canonical and pagination meta links
     *
     * @param string $type
     * @param array $url
     * @param string $expectedUrl
     * @dataProvider dataMetaLinksProvider
     */
    public function testMetaLinks($type, array $url, $expectedUrl)
    {
        $result = $this->Html->meta($type, $url);
        $expected = ['link' => ['href' => $expectedUrl, 'rel' => $type]];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test generating favicon's with meta()
     *
     * @return void
     */
    public function testMetaIcon()
    {
        $result = $this->Html->meta('icon', 'favicon.ico');
        $expected = [
            'link' => ['href' => 'preg:/.*favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => 'preg:/.*favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']]
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('icon');
        $expected = [
            'link' => ['href' => 'preg:/.*favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => 'preg:/.*favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']]
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('icon', '/favicon.png?one=two&three=four');
        $url = '/favicon.png?one=two&amp;three=four';
        $expected = [
            'link' => [
                'href' => $url,
                'type' => 'image/x-icon',
                'rel' => 'icon'
            ],
            [
                'link' => [
                    'href' => $url,
                    'type' => 'image/x-icon',
                    'rel' => 'shortcut icon'
                ]
            ]
        ];
        $this->assertHtml($expected, $result);

        $this->Html->request->webroot = '/testing/';
        $result = $this->Html->meta('icon');
        $expected = [
            'link' => ['href' => '/testing/favicon.ico', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => '/testing/favicon.ico', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test generating favicon's with meta() with theme
     *
     * @return void
     */
    public function testMetaIconWithTheme()
    {
        $this->Html->Url->theme = 'TestTheme';

        $result = $this->Html->meta('icon', 'favicon.ico');
        $expected = [
            'link' => ['href' => 'preg:/.*test_theme\/favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => 'preg:/.*test_theme\/favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']]
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('icon');
        $expected = [
            'link' => ['href' => 'preg:/.*test_theme\/favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => 'preg:/.*test_theme\/favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']]
        ];
        $this->assertHtml($expected, $result);

        $this->Html->request->webroot = '/testing/';
        $result = $this->Html->meta('icon');
        $expected = [
            'link' => ['href' => '/testing/test_theme/favicon.ico', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => '/testing/test_theme/favicon.ico', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']]
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test the inline and block options for meta()
     *
     * @return void
     */
    public function testMetaWithBlocks()
    {
        $this->View->expects($this->at(0))
            ->method('append')
            ->with('meta', $this->stringContains('robots'));

        $this->View->expects($this->at(1))
            ->method('append')
            ->with('metaTags', $this->stringContains('favicon.ico'));

        $result = $this->Html->meta('robots', 'ALL', ['block' => true]);
        $this->assertNull($result);

        $result = $this->Html->meta('icon', 'favicon.ico', ['block' => 'metaTags']);
        $this->assertNull($result);
    }

    /**
     * testTableHeaders method
     *
     * @return void
     */
    public function testTableHeaders()
    {
        $result = $this->Html->tableHeaders(['ID', 'Name', 'Date']);
        $expected = ['<tr', '<th', 'ID', '/th', '<th', 'Name', '/th', '<th', 'Date', '/th', '/tr'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->tableHeaders(['ID', ['Name' => ['class' => 'highlight']], 'Date']);
        $expected = ['<tr', '<th', 'ID', '/th', '<th class="highlight"', 'Name', '/th', '<th', 'Date', '/th', '/tr'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->tableHeaders(['ID', ['Name' => ['class' => 'highlight', 'width' => '120px']], 'Date']);
        $expected = ['<tr', '<th', 'ID', '/th', '<th class="highlight" width="120px"', 'Name', '/th', '<th', 'Date', '/th', '/tr'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->tableHeaders(['ID', ['Name' => []], 'Date']);
        $expected = ['<tr', '<th', 'ID', '/th', '<th', 'Name', '/th', '<th', 'Date', '/th', '/tr'];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTableCells method
     *
     * @return void
     */
    public function testTableCells()
    {
        $tr = [
            'td content 1',
            ['td content 2', ["width" => "100px"]],
            ['td content 3', ['width' => '100px']]
        ];
        $result = $this->Html->tableCells($tr);
        $expected = [
            '<tr',
            '<td', 'td content 1', '/td',
            ['td' => ['width' => '100px']], 'td content 2', '/td',
            ['td' => ['width' => 'preg:/100px/']], 'td content 3', '/td',
            '/tr'
        ];
        $this->assertHtml($expected, $result);

        $tr = ['td content 1', 'td content 2', 'td content 3'];
        $result = $this->Html->tableCells($tr, null, null, true);
        $expected = [
            '<tr',
            ['td' => ['class' => 'column-1']], 'td content 1', '/td',
            ['td' => ['class' => 'column-2']], 'td content 2', '/td',
            ['td' => ['class' => 'column-3']], 'td content 3', '/td',
            '/tr'
        ];
        $this->assertHtml($expected, $result);

        $tr = ['td content 1', 'td content 2', 'td content 3'];
        $result = $this->Html->tableCells($tr, true);
        $expected = [
            '<tr',
            ['td' => ['class' => 'column-1']], 'td content 1', '/td',
            ['td' => ['class' => 'column-2']], 'td content 2', '/td',
            ['td' => ['class' => 'column-3']], 'td content 3', '/td',
            '/tr'
        ];
        $this->assertHtml($expected, $result);

        $tr = [
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3']
        ];
        $result = $this->Html->tableCells($tr, ['class' => 'odd'], ['class' => 'even']);
        $expected = "<tr class=\"even\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"odd\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"even\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>";
        $this->assertEquals($expected, $result);

        $tr = [
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3']
        ];
        $result = $this->Html->tableCells($tr, ['class' => 'odd'], ['class' => 'even']);
        $expected = "<tr class=\"odd\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"even\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"odd\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"even\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>";
        $this->assertEquals($expected, $result);

        $tr = [
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3']
        ];
        $this->Html->tableCells($tr, ['class' => 'odd'], ['class' => 'even']);
        $result = $this->Html->tableCells($tr, ['class' => 'odd'], ['class' => 'even'], false, false);
        $expected = "<tr class=\"odd\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"even\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"odd\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>";
        $this->assertEquals($expected, $result);

        $tr = [
            'td content 1',
            'td content 2',
            ['td content 3', ['class' => 'foo']]
        ];
        $result = $this->Html->tableCells($tr, null, null, true);
        $expected = [
            '<tr',
            ['td' => ['class' => 'column-1']], 'td content 1', '/td',
            ['td' => ['class' => 'column-2']], 'td content 2', '/td',
            ['td' => ['class' => 'foo column-3']], 'td content 3', '/td',
            '/tr'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTag method
     *
     * @return void
     */
    public function testTag()
    {
        $result = $this->Html->tag('div');
        $this->assertHtml('<div', $result);

        $result = $this->Html->tag('div', 'text');
        $this->assertHtml(['<div', 'text', '/div'], $result);

        $result = $this->Html->tag('div', '<text>', ['class' => 'class-name', 'escape' => true]);
        $expected = ['div' => ['class' => 'class-name'], '&lt;text&gt;', '/div'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->tag(false, '<em>stuff</em>');
        $this->assertEquals('<em>stuff</em>', $result);

        $result = $this->Html->tag(null, '<em>stuff</em>');
        $this->assertEquals('<em>stuff</em>', $result);

        $result = $this->Html->tag('', '<em>stuff</em>');
        $this->assertEquals('<em>stuff</em>', $result);
    }

    /**
     * testDiv method
     *
     * @return void
     */
    public function testDiv()
    {
        $result = $this->Html->div('class-name');
        $expected = ['div' => ['class' => 'class-name']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->div('class-name', 'text');
        $expected = ['div' => ['class' => 'class-name'], 'text', '/div'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->div('class-name', '<text>', ['escape' => true]);
        $expected = ['div' => ['class' => 'class-name'], '&lt;text&gt;', '/div'];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPara method
     *
     * @return void
     */
    public function testPara()
    {
        $result = $this->Html->para('class-name', '');
        $expected = ['p' => ['class' => 'class-name']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->para('class-name', 'text');
        $expected = ['p' => ['class' => 'class-name'], 'text', '/p'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->para('class-name', '<text>', ['escape' => true]);
        $expected = ['p' => ['class' => 'class-name'], '&lt;text&gt;', '/p'];
        $this->assertHtml($expected, $result);
    }

    /**
     * testMedia method
     *
     * @return void
     */
    public function testMedia()
    {
        $result = $this->Html->media('video.webm');
        $expected = ['video' => ['src' => 'files/video.webm'], '/video'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->media('video.webm', [
            'text' => 'Your browser does not support the HTML5 Video element.'
        ]);
        $expected = ['video' => ['src' => 'files/video.webm'], 'Your browser does not support the HTML5 Video element.', '/video'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->media('video.webm', ['autoload', 'muted' => 'muted']);
        $expected = [
            'video' => [
                'src' => 'files/video.webm',
                'autoload' => 'autoload',
                'muted' => 'muted'
            ],
            '/video'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->media(
            ['video.webm', ['src' => 'video.ogv', 'type' => "video/ogg; codecs='theora, vorbis'"]],
            ['pathPrefix' => 'videos/', 'poster' => 'poster.jpg', 'text' => 'Your browser does not support the HTML5 Video element.']
        );
        $expected = [
            'video' => ['poster' => Configure::read('App.imageBaseUrl') . 'poster.jpg'],
                ['source' => ['src' => 'videos/video.webm', 'type' => 'video/webm']],
                ['source' => ['src' => 'videos/video.ogv', 'type' => 'video/ogg; codecs=&#039;theora, vorbis&#039;']],
                'Your browser does not support the HTML5 Video element.',
            '/video'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->media('video.ogv', ['tag' => 'video']);
        $expected = ['video' => ['src' => 'files/video.ogv'], '/video'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->media('audio.mp3');
        $expected = ['audio' => ['src' => 'files/audio.mp3'], '/audio'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->media(
            [['src' => 'video.mov', 'type' => 'video/mp4'], 'video.webm']
        );
        $expected = [
            '<video',
                ['source' => ['src' => 'files/video.mov', 'type' => 'video/mp4']],
                ['source' => ['src' => 'files/video.webm', 'type' => 'video/webm']],
            '/video'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->media(null, ['src' => 'video.webm']);
        $expected = [
            'video' => ['src' => 'files/video.webm'],
            '/video'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCrumbList method
     *
     * @return void
     */
    public function testCrumbList()
    {
        $this->assertNull($this->Html->getCrumbList());

        $this->Html->addCrumb('Home', '/', ['class' => 'home']);
        $this->Html->addCrumb('Some page', '/some_page');
        $this->Html->addCrumb('Another page');
        $result = $this->Html->getCrumbList(
            ['class' => 'breadcrumbs']
        );
        $expected = [
            ['ul' => ['class' => 'breadcrumbs']],
            ['li' => ['class' => 'first']],
            ['a' => ['class' => 'home', 'href' => '/']], 'Home', '/a',
            '/li',
            '<li',
            ['a' => ['href' => '/some_page']], 'Some page', '/a',
            '/li',
            ['li' => ['class' => 'last']],
            'Another page',
            '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test getCrumbList startText
     *
     * @return void
     */
    public function testCrumbListFirstLink()
    {
        $this->Html->addCrumb('First', '#first');
        $this->Html->addCrumb('Second', '#second');

        $result = $this->Html->getCrumbList([], 'Home');
        $expected = [
            '<ul',
            ['li' => ['class' => 'first']],
            ['a' => ['href' => '/']], 'Home', '/a',
            '/li',
            '<li',
            ['a' => ['href' => '#first']], 'First', '/a',
            '/li',
            ['li' => ['class' => 'last']],
            ['a' => ['href' => '#second']], 'Second', '/a',
            '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->getCrumbList([], ['url' => '/home', 'text' => '<img src="/home.png" />', 'escape' => false]);
        $expected = [
            '<ul',
            ['li' => ['class' => 'first']],
            ['a' => ['href' => '/home']], 'img' => ['src' => '/home.png'], '/a',
            '/li',
            '<li',
            ['a' => ['href' => '#first']], 'First', '/a',
            '/li',
            ['li' => ['class' => 'last']],
            ['a' => ['href' => '#second']], 'Second', '/a',
            '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test getCrumbList() in Twitter Bootstrap style.
     *
     * @return void
     */
    public function testCrumbListBootstrapStyle()
    {
        $this->Html->addCrumb('Home', '/', ['class' => 'home']);
        $this->Html->addCrumb('Library', '/lib');
        $this->Html->addCrumb('Data');
        $result = $this->Html->getCrumbList([
            'class' => 'breadcrumb',
            'separator' => '<span class="divider">-</span>',
            'firstClass' => false,
            'lastClass' => 'active'
        ]);
        $expected = [
            ['ul' => ['class' => 'breadcrumb']],
            '<li',
            ['a' => ['class' => 'home', 'href' => '/']], 'Home', '/a',
            ['span' => ['class' => 'divider']], '-', '/span',
            '/li',
            '<li',
            ['a' => ['href' => '/lib']], 'Library', '/a',
            ['span' => ['class' => 'divider']], '-', '/span',
            '/li',
            ['li' => ['class' => 'active']], 'Data', '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test GetCrumbList using style of Zurb Foundation.
     *
     * @return void
     */
    public function testCrumbListZurbStyle()
    {
        $this->Html->addCrumb('Home', '#');
        $this->Html->addCrumb('Features', '#');
        $this->Html->addCrumb('Gene Splicing', '#');
        $this->Html->addCrumb('Home', '#');
        $result = $this->Html->getCrumbList(
            ['class' => 'breadcrumbs', 'firstClass' => false, 'lastClass' => 'current']
        );
        $expected = [
            ['ul' => ['class' => 'breadcrumbs']],
            '<li',
            ['a' => ['href' => '#']], 'Home', '/a',
            '/li',
            '<li',
            ['a' => ['href' => '#']], 'Features', '/a',
            '/li',
            '<li',
            ['a' => ['href' => '#']], 'Gene Splicing', '/a',
            '/li',
            ['li' => ['class' => 'current']],
            ['a' => ['href' => '#']], 'Home', '/a',
            '/li',
            '/ul'
        ];
        $this->assertHtml($expected, $result, true);
    }

    /**
     * Tests that CSS and Javascript files of the same name don't conflict with the 'once' test
     *
     * @return void
     */
    public function testCssAndScriptWithSameName()
    {
        $result = $this->Html->css('foo');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*css\/foo\.css/']
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('foo');
        $expected = [
            'script' => ['src' => 'js/foo.js']
        ];
        $this->assertHtml($expected, $result);
    }
}
