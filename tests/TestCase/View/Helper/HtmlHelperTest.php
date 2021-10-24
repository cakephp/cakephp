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
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Filesystem;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenDate;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;

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
    protected $cDataStart = 'preg:/^\/\/<!\[CDATA\[[\n\r]*/';

    /**
     * Regexp for CDATA end block
     *
     * @var string
     */
    protected $cDataEnd = 'preg:/[^\]]*\]\]\>[\s\r\n]*/';

    /**
     * Helper to be tested
     *
     * @var \Cake\View\Helper\HtmlHelper
     */
    protected $Html;

    /**
     * Mocked view
     *
     * @var \Cake\View\View|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $View;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        $request = new ServerRequest([
            'webroot' => '',
        ]);
        Router::reload();
        Router::setRequest($request);

        $this->View = $this->getMockBuilder(View::class)
            ->onlyMethods(['append'])
            ->setConstructorArgs([$request])
            ->getMock();
        $this->Html = new HtmlHelper($this->View);

        $this->loadPlugins(['TestTheme']);
        static::setAppNamespace();
        Configure::write('Asset.timestamp', false);

        $builder = Router::createRouteBuilder('/');
        $builder->fallbacks(DashedRoute::class);
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        unset($this->Html, $this->View);
    }

    /**
     * testLink method
     */
    public function testLink(): void
    {
        Router::reload();
        $builder = Router::createRouteBuilder('/');
        $builder->connect('/{controller}', ['action' => 'index']);
        $builder->connect('/{controller}/{action}/*');
        Router::setRequest(new ServerRequest());

        $this->View->setRequest($this->View->getRequest()->withAttribute('webroot', ''));

        $result = $this->Html->link('/home');
        $expected = ['a' => ['href' => '/home'], 'preg:/\/home/', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link(['controller' => 'Users', 'action' => 'login', '<[You]>']);
        $expected = [
            'a' => ['href' => '/Users/login/%3C%5BYou%5D%3E'],
            'preg:/\/Users\/login\/&lt;\[You\]&gt;/',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Posts', ['controller' => 'Posts', 'action' => 'index', '_full' => true]);
        $expected = ['a' => ['href' => Router::fullBaseUrl() . '/Posts'], 'Posts', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Home', '/home', ['confirm' => 'Are you sure you want to do this?']);
        $expected = [
            'a' => [
                'href' => '/home',
                'data-confirm-message' => 'Are you sure you want to do this?',
                'onclick' => 'if (confirm(this.dataset.confirmMessage)) { return true; } return false;',
            ],
            'Home',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $this->Html->setTemplates(['confirmJs' => 'if (confirm(this.dataset.confirmMessage)) { window.location="/";};']);
        $result = $this->Html->link('Home', '/home', ['confirm' => 'Are you sure you want to do this?']);
        $expected = [
            'a' => [
                'href' => '/home',
                'data-confirm-message' => 'Are you sure you want to do this?',
                'onclick' => 'preg:/if \(confirm\(this.dataset.confirmMessage\)\) \{ window\.location=&quot;\/&quot;;\};/',
            ],
            'Home',
            '/a',
        ];

        $this->assertHtml($expected, $result);
        $this->Html->setTemplates(['confirmJs' => '{{confirm}}']);

        $result = $this->Html->link('Home', '/home', ['confirm' => 'Confirm\'s "nightmares"']);
        $expected = [
            'a' => [
                'href' => '/home',
                'data-confirm-message' => 'Confirm&#039;s &quot;nightmares&quot;',
                'onclick' => 'if (confirm(this.dataset.confirmMessage)) { return true; } return false;',
            ],
            'Home',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Home', '/home', ['onclick' => 'someFunction();']);
        $expected = [
            'a' => ['href' => '/home', 'onclick' => 'someFunction();'],
            'Home',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#');
        $expected = [
            'a' => ['href' => '#'],
            'Next &gt;',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', ['escape' => true]);
        $expected = [
            'a' => ['href' => '#'],
            'Next &gt;',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', ['escape' => 'utf-8']);
        $expected = [
            'a' => ['href' => '#'],
            'Next &gt;',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', ['escape' => false]);
        $expected = [
            'a' => ['href' => '#'],
            'Next >',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', [
            'title' => 'to escape &#8230; or not escape?',
            'escape' => false,
        ]);
        $expected = [
            'a' => ['href' => '#', 'title' => 'to escape &#8230; or not escape?'],
            'Next >',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', [
            'title' => 'to escape &#8230; or not escape?',
            'escape' => true,
        ]);
        $expected = [
            'a' => ['href' => '#', 'title' => 'to escape &amp;#8230; or not escape?'],
            'Next &gt;',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Next >', '#', [
            'title' => 'Next >',
            'escapeTitle' => false,
        ]);
        $expected = [
            'a' => ['href' => '#', 'title' => 'Next &gt;'],
            'Next >',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Original size', [
            'controller' => 'Images', 'action' => 'view', 3, '?' => ['height' => 100, 'width' => 200],
        ]);
        $expected = [
            'a' => ['href' => '/Images/view/3?height=100&amp;width=200'],
            'Original size',
            '/a',
        ];
        $this->assertHtml($expected, $result);

        Configure::write('Asset.timestamp', false);

        $result = $this->Html->link($this->Html->image('test.gif'), '#', ['escape' => false]);
        $expected = [
            'a' => ['href' => '#'],
            'img' => ['src' => 'img/test.gif', 'alt' => ''],
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link($this->Html->image('test.gif'), '#', [
            'title' => 'hey "howdy"',
            'escapeTitle' => false,
        ]);
        $expected = [
            'a' => ['href' => '#', 'title' => 'hey &quot;howdy&quot;'],
            'img' => ['src' => 'img/test.gif', 'alt' => ''],
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('test.gif', ['url' => '#']);
        $expected = [
            'a' => ['href' => '#'],
            'img' => ['src' => 'img/test.gif', 'alt' => ''],
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link($this->Html->image('../favicon.ico'), '#', ['escape' => false]);
        $expected = [
            'a' => ['href' => '#'],
            'img' => ['src' => 'img/../favicon.ico', 'alt' => ''],
            '/a',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('../favicon.ico', ['url' => '#']);
        $expected = [
            'a' => ['href' => '#'],
            'img' => ['src' => 'img/../favicon.ico', 'alt' => ''],
            '/a',
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

        $result = $this->Html->link('Home', '/', ['fullBase' => true]);
        $expected = ['a' => ['href' => 'http://localhost/'], 'Home', '/a'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->link('Home', '/', ['fullBase' => false]);
        $expected = ['a' => ['href' => '/'], 'Home', '/a'];
        $this->assertHtml($expected, $result);
    }

    public function testLinkFromPath(): void
    {
        $result = $this->Html->linkFromPath('Index', 'Articles::index');
        $expected = '<a href="/articles">Index</a>';
        $this->assertSame($result, $expected);

        $result = $this->Html->linkFromPath('View', 'Articles::view', [3]);
        $expected = '<a href="/articles/view/3">View</a>';
        $this->assertSame($result, $expected);
    }

    /**
     * testImageTag method
     */
    public function testImageTag(): void
    {
        $builder = Router::createRouteBuilder('/');
        $builder->connect('/:controller', ['action' => 'index']);
        $builder->connect('/:controller/:action/*');

        $result = $this->Html->image('test.gif');
        $expected = ['img' => ['src' => 'img/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('http://google.com/logo.gif');
        $expected = ['img' => ['src' => 'http://google.com/logo.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('//google.com/logo.gif');
        $expected = ['img' => ['src' => '//google.com/logo.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image(['controller' => 'Test', 'action' => 'view', 1, '_ext' => 'gif']);
        $expected = ['img' => ['src' => '/test/view/1.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('/test/view/1.gif');
        $expected = ['img' => ['src' => '/test/view/1.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('cid:cakephp_logo');
        $expected = ['img' => ['src' => 'cid:cakephp_logo', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('x:"><script>alert(1)</script>');
        $expected = ['img' => ['src' => 'x:&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('//google.com/"><script>alert(1)</script>');
        $expected = ['img' => ['src' => '//google.com/&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image([
            'controller' => 'Images',
            'action' => 'display',
            'test',
            '?' => ['one' => 'two', 'three' => 'four'],
        ]);
        $expected = ['img' => ['src' => '/images/display/test?one=two&amp;three=four', 'alt' => '']];
        $this->assertHtml($expected, $result);
    }

    /**
     * Ensure that data URIs don't get base paths set.
     */
    public function testImageDataUriBaseDir(): void
    {
        $request = $this->View->getRequest()
            ->withAttribute('base', 'subdir')
            ->withAttribute('webroot', 'subdir/');
        $this->View->setRequest($request);
        Router::setRequest($request);

        $data = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4' .
            '/8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==';
        $result = $this->Html->image($data);
        $expected = ['img' => ['src' => $data, 'alt' => '']];
        $this->assertHtml($expected, $result);

        $data = 'data:image/png;base64,<evil>';
        $result = $this->Html->image($data);
        $expected = ['img' => ['src' => h($data), 'alt' => '']];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test image() with query strings.
     */
    public function testImageQueryString(): void
    {
        $result = $this->Html->image('test.gif?one=two&three=four');
        $expected = ['img' => ['src' => 'img/test.gif?one=two&amp;three=four', 'alt' => '']];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that image works with pathPrefix.
     */
    public function testImagePathPrefix(): void
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
     */
    public function testImageWithFullBase(): void
    {
        $result = $this->Html->image('test.gif', ['fullBase' => true]);
        $here = $this->Html->Url->build('/', ['fullBase' => true]);
        $expected = ['img' => ['src' => $here . 'img/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->image('sub/test.gif', ['fullBase' => true]);
        $here = $this->Html->Url->build('/', ['fullBase' => true]);
        $expected = ['img' => ['src' => $here . 'img/sub/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $request = Router::getRequest()
            ->withAttribute('webroot', '/myproject/')
            ->withAttribute('base', '/myproject');
        Router::setRequest($request);

        $result = $this->Html->image('sub/test.gif', ['fullBase' => true]);
        $expected = ['img' => ['src' => 'http://localhost/myproject/img/sub/test.gif', 'alt' => '']];
        $this->assertHtml($expected, $result);
    }

    /**
     * test image() with Asset.timestamp
     */
    public function testImageWithTimestampping(): void
    {
        Configure::write('Asset.timestamp', 'force');

        $request = Router::getRequest()->withAttribute('webroot', '/');
        Router::setRequest($request);
        $result = $this->Html->image('cake.icon.png');
        $expected = ['img' => ['src' => 'preg:/\/img\/cake\.icon\.png\?\d+/', 'alt' => '']];
        $this->assertHtml($expected, $result);

        Configure::write('debug', false);
        Configure::write('Asset.timestamp', 'force');

        $result = $this->Html->image('cake.icon.png');
        $expected = ['img' => ['src' => 'preg:/\/img\/cake\.icon\.png\?\d+/', 'alt' => '']];
        $this->assertHtml($expected, $result);

        $request = Router::getRequest()->withAttribute('webroot', '/testing/longer/');
        Router::setRequest($request);
        $result = $this->Html->image('cake.icon.png');
        $expected = [
            'img' => ['src' => 'preg:/\/testing\/longer\/img\/cake\.icon\.png\?[0-9]+/', 'alt' => ''],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests creation of an image tag using a theme and asset timestamping
     */
    public function testImageTagWithTheme(): void
    {
        $this->skipIf(!is_writable(WWW_ROOT), 'Cannot write to webroot.');

        $testfile = WWW_ROOT . 'test_theme/img/__cake_test_image.gif';
        $fs = new Filesystem();
        $fs->dumpFile($testfile, '');

        Configure::write('Asset.timestamp', true);
        Configure::write('debug', true);

        $request = Router::getRequest()->withAttribute('webroot', '/');
        Router::setRequest($request);
        $this->Html->Url->getView()->setTheme('TestTheme');
        $result = $this->Html->image('__cake_test_image.gif');
        $expected = [
            'img' => [
                'src' => 'preg:/\/test_theme\/img\/__cake_test_image\.gif\?\d+/',
                'alt' => '',
            ],
        ];
        $this->assertHtml($expected, $result);

        $request = Router::getRequest()->withAttribute('webroot', '/testing/');
        Router::setRequest($request);
        $result = $this->Html->image('__cake_test_image.gif');
        $expected = [
            'img' => [
                'src' => 'preg:/\/testing\/test_theme\/img\/__cake_test_image\.gif\?\d+/',
                'alt' => '',
            ],
        ];
        $this->assertHtml($expected, $result);

        // phpcs:ignore
        @unlink($testfile);
    }

    /**
     * test theme assets in main webroot path
     */
    public function testThemeAssetsInMainWebrootPath(): void
    {
        Configure::write('App.wwwRoot', TEST_APP . 'webroot/');

        $this->Html->Url->getView()->setTheme('TestTheme');
        $result = $this->Html->css('webroot_test');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*test_theme\/css\/webroot_test\.css/'],
        ];
        $this->assertHtml($expected, $result);

        $this->Html->getView()->setTheme('TestTheme');
        $result = $this->Html->css('theme_webroot');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*test_theme\/css\/theme_webroot\.css/'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testStyle method
     */
    public function testStyle(): void
    {
        $result = $this->Html->style(['display' => 'none', 'margin' => '10px']);
        $this->assertSame('display:none; margin:10px;', $result);

        $result = $this->Html->style(['display' => 'none', 'margin' => '10px'], false);
        $this->assertSame("display:none;\nmargin:10px;", $result);
    }

    /**
     * testCssLink method
     */
    public function testCssLink(): void
    {
        $result = $this->Html->css('screen');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*css\/screen\.css/'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('screen.css', ['once' => false]);
        $this->assertHtml($expected, $result);

        $this->loadPlugins(['TestPlugin']);
        $result = $this->Html->css('TestPlugin.style', ['plugin' => false]);
        $expected['link']['href'] = 'preg:/.*css\/TestPlugin\.style\.css/';
        $this->assertHtml($expected, $result);
        $this->removePlugins(['TestPlugin']);

        $result = $this->Html->css('my.css.library');
        $expected['link']['href'] = 'preg:/.*css\/my\.css\.library\.css/';
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('screen.css?1234');
        $expected['link']['href'] = 'preg:/.*css\/screen\.css\?1234/';
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('screen.css?with=param&other=param');
        $expected['link']['href'] = 'css/screen.css?with=param&amp;other=param';
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('x:"><script>alert(1)</script>');
        $expected['link']['href'] = 'x:&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;';
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('http://whatever.com/screen.css?1234&a=b');
        $expected['link']['href'] = 'http://whatever.com/screen.css?1234&amp;a=b';
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

        $this->View->expects($this->exactly(2))
            ->method('append')
            ->withConsecutive(
                ['css', $this->matchesRegularExpression('/css_in_head.css/')],
                ['css', $this->matchesRegularExpression('/more_css_in_head.css/')]
            );

        $result = $this->Html->css('css_in_head', ['block' => true]);
        $this->assertNull($result);

        $result = $this->Html->css('more_css_in_head', ['block' => true]);
        $this->assertNull($result);

        $result = $this->Html->css('import-screen', ['rel' => 'import']);
        $expected = [
            '<style',
            'preg:/@import url\(.*css\/import-screen\.css\);/',
            '/style',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that css() includes CSP nonces if available.
     */
    public function testCssWithCspNonce(): void
    {
        $nonce = 'r@nd0mV4lue';
        $request = $this->View->getRequest()->withAttribute('cspStyleNonce', $nonce);
        $this->View->setRequest($request);

        $result = $this->Html->css('app');
        $expected = [
           'link' => ['rel' => 'stylesheet', 'href' => 'css/app.css', 'nonce' => $nonce],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test css() with once option.
     */
    public function testCssLinkOnce(): void
    {
        $result = $this->Html->css('screen', ['once' => true]);
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*css\/screen\.css/'],
        ];
        $this->assertHtml($expected, $result);

        // Default is once=true
        $result = $this->Html->css('screen');
        $this->assertNull($result);

        $result = $this->Html->css('screen', ['once' => false]);
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*css\/screen\.css/'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCssWithFullBase method
     */
    public function testCssWithFullBase(): void
    {
        Configure::write('Asset.filter.css', false);
        $here = $this->Html->Url->build('/', ['fullBase' => true]);

        $result = $this->Html->css('screen', ['fullBase' => true]);
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => $here . 'css/screen.css'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testPluginCssLink method
     */
    public function testPluginCssLink(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $result = $this->Html->css('TestPlugin.test_plugin_asset');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*test_plugin\/css\/test_plugin_asset\.css/'],
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

        $this->removePlugins(['TestPlugin']);
    }

    /**
     * test use of css() and timestamping
     */
    public function testCssTimestamping(): void
    {
        Configure::write('debug', true);
        Configure::write('Asset.timestamp', true);

        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => ''],
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

        $request = Router::getRequest()->withAttribute('webroot', '/testing/');
        Router::setRequest($request);
        $result = $this->Html->css('cake.generic', ['once' => false]);
        $expected['link']['href'] = 'preg:/\/testing\/css\/cake\.generic\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        $request = Router::getRequest()->withAttribute('webroot', '/testing/longer/');
        Router::setRequest($request);
        $result = $this->Html->css('cake.generic', ['once' => false]);
        $expected['link']['href'] = 'preg:/\/testing\/longer\/css\/cake\.generic\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);
    }

    /**
     * test use of css() and timestamping with plugin syntax
     */
    public function testPluginCssTimestamping(): void
    {
        $this->loadPlugins(['TestPlugin', 'Company/TestPluginThree']);

        Configure::write('debug', true);
        Configure::write('Asset.timestamp', true);

        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => ''],
        ];

        $result = $this->Html->css('TestPlugin.test_plugin_asset', ['once' => false]);
        $expected['link']['href'] = 'preg:/.*test_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        $result = $this->Html->css('Company/TestPluginThree.company', ['once' => false]);
        $expected['link']['href'] = 'preg:/.*company\/test_plugin_three\/css\/company\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        Configure::write('debug', false);

        $result = $this->Html->css('TestPlugin.test_plugin_asset', ['once' => false]);
        $expected['link']['href'] = 'preg:/.*test_plugin\/css\/test_plugin_asset\.css/';
        $this->assertHtml($expected, $result);

        Configure::write('Asset.timestamp', 'force');

        $result = $this->Html->css('TestPlugin.test_plugin_asset', ['once' => false]);
        $expected['link']['href'] = 'preg:/.*test_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        $request = Router::getRequest()->withAttribute('webroot', '/testing/');
        Router::setRequest($request);
        $result = $this->Html->css('TestPlugin.test_plugin_asset', ['once' => false]);
        $expected['link']['href'] = 'preg:/\/testing\/test_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        $request = Router::getRequest()->withAttribute('webroot', '/testing/longer/');
        Router::setRequest($request);
        $result = $this->Html->css('TestPlugin.test_plugin_asset', ['once' => false]);
        $expected['link']['href'] = 'preg:/\/testing\/longer\/test_plugin\/css\/test_plugin_asset\.css\?[0-9]+/';
        $this->assertHtml($expected, $result);

        $this->removePlugins(['TestPlugin', 'Company/TestPluginThree']);
    }

    /**
     * Resource names must be treated differently for css() and script()
     */
    public function testBufferedCssAndScriptWithIdenticalResourceName(): void
    {
        $this->View->expects($this->exactly(2))
            ->method('append')
            ->withConsecutive(
                ['css', $this->stringContains('test.min.css')],
                ['script', $this->stringContains('test.min.js')]
            );
        $this->Html->css('test.min', ['block' => true]);
        $this->Html->script('test.min', ['block' => true]);
    }

    /**
     * test timestamp enforcement for script tags.
     */
    public function testScriptTimestamping(): void
    {
        $this->skipIf(!is_writable(WWW_ROOT . 'js'), 'webroot/js is not Writable, timestamp testing has been skipped.');
        Configure::write('debug', true);
        Configure::write('Asset.timestamp', true);

        touch(WWW_ROOT . 'js/__cake_js_test.js');
        $timestamp = substr((string)strtotime('now'), 0, 8);

        $result = $this->Html->script('__cake_js_test', ['once' => false]);
        $this->assertMatchesRegularExpression('/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"/', $result, 'Timestamp value not found %s');

        Configure::write('debug', false);
        Configure::write('Asset.timestamp', 'force');
        $result = $this->Html->script('__cake_js_test', ['once' => false]);
        $this->assertMatchesRegularExpression('/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"/', $result, 'Timestamp value not found %s');
        unlink(WWW_ROOT . 'js/__cake_js_test.js');
        Configure::write('Asset.timestamp', false);
    }

    /**
     * test timestamp enforcement for script tags with plugin syntax.
     */
    public function testPluginScriptTimestamping(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $pluginPath = Plugin::path('TestPlugin');
        $pluginJsPath = $pluginPath . 'webroot/js';
        $this->skipIf(!is_writable($pluginJsPath), $pluginJsPath . ' is not Writable, timestamp testing has been skipped.');

        Configure::write('debug', true);
        Configure::write('Asset.timestamp', true);

        touch($pluginJsPath . DS . '__cake_js_test.js');
        $timestamp = substr((string)strtotime('now'), 0, 8);

        $result = $this->Html->script('TestPlugin.__cake_js_test', ['once' => false]);
        $this->assertMatchesRegularExpression('/test_plugin\/js\/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"/', $result, 'Timestamp value not found %s');

        Configure::write('debug', false);
        Configure::write('Asset.timestamp', 'force');
        $result = $this->Html->script('TestPlugin.__cake_js_test', ['once' => false]);
        $this->assertMatchesRegularExpression('/test_plugin\/js\/__cake_js_test.js\?' . $timestamp . '[0-9]{2}"/', $result, 'Timestamp value not found %s');
        unlink($pluginJsPath . DS . '__cake_js_test.js');
        Configure::write('Asset.timestamp', false);

        $this->removePlugins(['TestPlugin']);
    }

    /**
     * test that scripts added with uses() are only ever included once.
     * test script tag generation
     */
    public function testScript(): void
    {
        $result = $this->Html->script('foo');
        $expected = [
            'script' => ['src' => 'js/foo.js'],
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
            'script' => ['src' => 'js/jquery-1.3.js'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('test.json');
        $expected = [
            'script' => ['src' => 'js/test.json.js'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('http://example.com/test.json');
        $expected = [
            'script' => ['src' => 'http://example.com/test.json'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('/plugin/js/jquery-1.3.2.js?someparam=foo');
        $expected = [
            'script' => ['src' => '/plugin/js/jquery-1.3.2.js?someparam=foo'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('test.json.js?foo=bar');
        $expected = [
            'script' => ['src' => 'js/test.json.js?foo=bar'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('test.json.js?foo=bar&other=test');
        $expected = [
            'script' => ['src' => 'js/test.json.js?foo=bar&amp;other=test'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('//domain.com/test.json.js?foo=bar&other=test');
        $expected = [
            'script' => ['src' => '//domain.com/test.json.js?foo=bar&amp;other=test'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('https://domain.com/test.json.js?foo=bar&other=test');
        $expected = [
            'script' => ['src' => 'https://domain.com/test.json.js?foo=bar&amp;other=test'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('x:"><script>alert(1)</script>');
        $expected = [
            'script' => ['src' => 'x:&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('foo2', ['pathPrefix' => '/my/custom/path/']);
        $expected = [
            'script' => ['src' => '/my/custom/path/foo2.js'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('foo3', ['pathPrefix' => 'http://cakephp.org/assets/js/']);
        $expected = [
            'script' => ['src' => 'http://cakephp.org/assets/js/foo3.js'],
        ];
        $this->assertHtml($expected, $result);

        $previousConfig = Configure::read('App.jsBaseUrl');
        Configure::write('App.jsBaseUrl', '//cdn.cakephp.org/js/');
        $result = $this->Html->script('foo4');
        $expected = [
            'script' => ['src' => '//cdn.cakephp.org/js/foo4.js'],
        ];
        $this->assertHtml($expected, $result);
        Configure::write('App.jsBaseUrl', $previousConfig);

        $result = $this->Html->script('foo');
        $this->assertNull($result, 'Script returned upon duplicate inclusion %s');

        $result = $this->Html->script(['foo', 'bar', 'baz']);
        $this->assertDoesNotMatchRegularExpression('/foo.js/', $result);

        $result = $this->Html->script('foo', ['once' => false]);
        $this->assertNotNull($result);

        $result = $this->Html->script('jquery-1.3.2', ['defer' => true, 'encoding' => 'utf-8']);
        $expected = [
            'script' => ['src' => 'js/jquery-1.3.2.js', 'defer' => 'defer', 'encoding' => 'utf-8'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test that content-security-policy nonces are applied if the request attribute
     * is present.
     */
    public function testScriptCspNonce(): void
    {
        $nonce = 'r@ndomV4lue';
        $request = $this->View->getRequest()
            ->withAttribute('cspScriptNonce', $nonce);
        $this->View->setRequest($request);

        $result = $this->Html->script('app.js', ['defer' => true, 'encoding' => 'utf-8']);
        $expected = [
            'script' => ['src' => 'js/app.js', 'defer' => 'defer', 'encoding' => 'utf-8', 'nonce' => $nonce],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test that plugin scripts added with uses() are only ever included once.
     * test script tag generation with plugin syntax
     */
    public function testPluginScript(): void
    {
        $this->loadPlugins(['TestPlugin']);

        $result = $this->Html->script('TestPlugin.foo');
        $expected = [
            'script' => ['src' => 'test_plugin/js/foo.js'],
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
            'script' => ['src' => 'test_plugin/js/jquery-1.3.js'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('TestPlugin.test.json');
        $expected = [
            'script' => ['src' => 'test_plugin/js/test.json.js'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('TestPlugin./jquery-1.3.2.js?someparam=foo');
        $expected = [
            'script' => ['src' => 'test_plugin/jquery-1.3.2.js?someparam=foo'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('TestPlugin.test.json.js?foo=bar');
        $expected = [
            'script' => ['src' => 'test_plugin/js/test.json.js?foo=bar'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('TestPlugin.foo');
        $this->assertNull($result, 'Script returned upon duplicate inclusion %s');

        $result = $this->Html->script(['TestPlugin.foo', 'TestPlugin.bar', 'TestPlugin.baz']);
        $this->assertDoesNotMatchRegularExpression('/test_plugin\/js\/foo.js/', $result);

        $result = $this->Html->script('TestPlugin.foo', ['once' => false]);
        $this->assertNotNull($result);

        $result = $this->Html->script('TestPlugin.jquery-1.3.2', ['defer' => true, 'encoding' => 'utf-8']);
        $expected = [
            'script' => ['src' => 'test_plugin/js/jquery-1.3.2.js', 'defer' => 'defer', 'encoding' => 'utf-8'],
        ];
        $this->assertHtml($expected, $result);

        $this->removePlugins(['TestPlugin']);
    }

    /**
     * test that script() works with blocks.
     */
    public function testScriptWithBlocks(): void
    {
        $this->View->expects($this->exactly(2))
            ->method('append')
            ->withConsecutive(
                ['script', $this->matchesRegularExpression('/script_in_head.js/')],
                ['headScripts', $this->matchesRegularExpression('/second_script.js/')]
            );

        $result = $this->Html->script('script_in_head', ['block' => true]);
        $this->assertNull($result);

        $result = $this->Html->script('second_script', ['block' => 'headScripts']);
        $this->assertNull($result);
    }

    /**
     * testScriptWithFullBase method
     */
    public function testScriptWithFullBase(): void
    {
        $here = $this->Html->Url->build('/', ['fullBase' => true]);

        $result = $this->Html->script('foo', ['fullBase' => true]);
        $expected = [
            'script' => ['src' => $here . 'js/foo.js'],
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
     */
    public function testScriptInTheme(): void
    {
        $this->skipIf(!is_writable(WWW_ROOT), 'Cannot write to webroot.');

        $testfile = WWW_ROOT . 'test_theme/js/__test_js.js';
        $fs = new Filesystem();
        $fs->dumpFile($testfile, '');

        $request = Router::getRequest()->withAttribute('webroot', '/');
        Router::setRequest($request);
        $this->Html->Url->getView()->setTheme('TestTheme');
        $result = $this->Html->script('__test_js.js');
        $expected = [
            'script' => ['src' => '/test_theme/js/__test_js.js'],
        ];
        $this->assertHtml($expected, $result);

        // phpcs:ignore
        @unlink($testfile);
    }

    /**
     * test Script block generation
     */
    public function testScriptBlock(): void
    {
        $result = $this->Html->scriptBlock('window.foo = 2;');
        $expected = [
            '<script',
            'window.foo = 2;',
            '/script',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->scriptBlock('window.foo = 2;', ['type' => 'text/x-handlebars-template']);
        $expected = [
            'script' => ['type' => 'text/x-handlebars-template'],
            'window.foo = 2;',
            '/script',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->scriptBlock('window.foo = 2;');
        $expected = [
            '<script',
            'window.foo = 2;',
            '/script',
        ];
        $this->assertHtml($expected, $result);

        $this->View->expects($this->exactly(2))
            ->method('append')
            ->withConsecutive(
                ['script', $this->matchesRegularExpression('/window\.foo\s\=\s2;/')],
                ['scriptTop', $this->stringContains('alert(')]
            );

        $result = $this->Html->scriptBlock('window.foo = 2;', ['block' => true]);
        $this->assertNull($result);

        $result = $this->Html->scriptBlock('alert("hi")', ['block' => 'scriptTop']);
        $this->assertNull($result);

        $result = $this->Html->scriptBlock('window.foo = 2;', ['encoding' => 'utf-8']);
        $expected = [
            'script' => ['encoding' => 'utf-8'],
            'window.foo = 2;',
            '/script',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Ensure that scriptBlock() uses CSP nonces.
     */
    public function testScriptBlockCspNonce(): void
    {
        $nonce = 'r@ndomV4lue';
        $request = $this->View->getRequest()
            ->withAttribute('cspScriptNonce', $nonce);
        $this->View->setRequest($request);

        $result = $this->Html->scriptBlock('window.foo = 2;');
        $expected = [
            'script' => ['nonce' => $nonce],
            'window.foo = 2;',
            '/script',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * test script tag output buffering when using scriptStart() and scriptEnd();
     */
    public function testScriptStartAndScriptEnd(): void
    {
        $this->Html->scriptStart();
        echo 'this is some javascript';
        $result = $this->Html->scriptEnd();

        $expected = [
            '<script',
            'this is some javascript',
            '/script',
        ];
        $this->assertHtml($expected, $result);

        $this->Html->scriptStart();
        echo 'this is some javascript';
        $result = $this->Html->scriptEnd();

        $expected = [
            '<script',
            'this is some javascript',
            '/script',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testCharsetTag method
     */
    public function testCharsetTag(): void
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
     * testNestedList method
     */
    public function testNestedList(): void
    {
        $list = [
            'Item 1',
            'Item 2' => [
                'Item 2.1',
            ],
            'Item 3',
            'Item 4' => [
                'Item 4.1',
                'Item 4.2',
                'Item 4.3' => [
                    'Item 4.3.1',
                    'Item 4.3.2',
                ],
            ],
            'Item 5' => [
                'Item 5.1',
                'Item 5.2',
            ],
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
            '/ul',
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
            '/ol',
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
            '/ul',
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
            '/ul',
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
            '/ul',
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
            '/ul',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testMeta method
     */
    public function testMeta(): void
    {
        Router::createRouteBuilder('/')->connect('/:controller', ['action' => 'index']);

        $result = $this->Html->meta('this is an rss feed', ['controller' => 'Posts', '_ext' => 'rss']);
        $expected = ['link' => ['href' => 'preg:/.*\/posts\.rss/', 'type' => 'application/rss+xml', 'rel' => 'alternate', 'title' => 'this is an rss feed']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('rss', ['controller' => 'Posts', '_ext' => 'rss'], ['title' => 'this is an rss feed']);
        $expected = ['link' => ['href' => 'preg:/.*\/posts\.rss/', 'type' => 'application/rss+xml', 'rel' => 'alternate', 'title' => 'this is an rss feed']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('atom', ['controller' => 'Posts', '_ext' => 'xml']);
        $expected = ['link' => ['href' => 'preg:/.*\/posts\.xml/', 'type' => 'application/atom+xml', 'title' => 'atom']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('nonexistent');
        $expected = ['<meta'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('nonexistent', 'some content');
        $expected = ['meta' => ['name' => 'nonexistent', 'content' => 'some content']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('nonexistent', '/posts.xpp', ['type' => 'atom']);
        $expected = ['link' => ['href' => 'preg:/.*\/posts\.xpp/', 'type' => 'application/atom+xml', 'title' => 'nonexistent']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('atom', ['controller' => 'Posts', '_ext' => 'xml'], ['link' => '/articles.rss']);
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
            'meta' => ['name' => 'viewport', 'content' => 'width=device-width'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta(['property' => 'og:site_name', 'content' => 'CakePHP']);
        $expected = [
            'meta' => ['property' => 'og:site_name', 'content' => 'CakePHP'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta(['link' => 'http://example.com/manifest', 'rel' => 'manifest']);
        $expected = [
            'link' => ['href' => 'http://example.com/manifest', 'rel' => 'manifest'],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * @return array
     */
    public function dataMetaLinksProvider(): array
    {
        return [
            ['canonical', ['controller' => 'Posts', 'action' => 'show'], '/posts/show'],
            ['first', ['controller' => 'Posts', 'action' => 'index'], '/posts'],
            ['last', ['controller' => 'Posts', 'action' => 'index', '?' => ['page' => 10]], '/posts?page=10'],
            ['prev', ['controller' => 'Posts', 'action' => 'index', '?' => ['page' => 4]], '/posts?page=4'],
            ['next', ['controller' => 'Posts', 'action' => 'index', '?' => ['page' => 6]], '/posts?page=6'],
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
    public function testMetaLinks($type, array $url, $expectedUrl): void
    {
        $result = $this->Html->meta($type, $url);
        $expected = ['link' => ['href' => $expectedUrl, 'rel' => $type]];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test generating favicon's with meta()
     */
    public function testMetaIcon(): void
    {
        $result = $this->Html->meta('icon', 'favicon.ico');
        $expected = [
            'link' => ['href' => 'preg:/.*favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => 'preg:/.*favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('icon');
        $expected = [
            'link' => ['href' => 'preg:/.*favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => 'preg:/.*favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('icon', '/favicon.png?one=two&three=four');
        $url = '/favicon.png?one=two&amp;three=four';
        $expected = [
            'link' => [
                'href' => $url,
                'type' => 'image/x-icon',
                'rel' => 'icon',
            ],
            [
                'link' => [
                    'href' => $url,
                    'type' => 'image/x-icon',
                    'rel' => 'shortcut icon',
                ],
            ],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('icon', 'x:"><script>alert(1)</script>');
        $url = 'x:&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;';
        $expected = [
            'link' => [
                'href' => $url,
                'type' => 'image/x-icon',
                'rel' => 'icon',
            ],
            [
                'link' => [
                    'href' => $url,
                    'type' => 'image/x-icon',
                    'rel' => 'shortcut icon',
                ],
            ],
        ];
        $this->assertHtml($expected, $result);

        $request = Router::getRequest()->withAttribute('webroot', '/testing/');
        Router::setRequest($request);
        $result = $this->Html->meta('icon');
        $expected = [
            'link' => ['href' => '/testing/favicon.ico', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => '/testing/favicon.ico', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test generating favicon's with meta() with theme
     */
    public function testMetaIconWithTheme(): void
    {
        $this->Html->Url->getView()->setTheme('TestTheme');

        $result = $this->Html->meta('icon', 'favicon.ico');
        $expected = [
            'link' => ['href' => 'preg:/.*test_theme\/favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => 'preg:/.*test_theme\/favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->meta('icon');
        $expected = [
            'link' => ['href' => 'preg:/.*test_theme\/favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => 'preg:/.*test_theme\/favicon\.ico/', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']],
        ];
        $this->assertHtml($expected, $result);

        $request = Router::getRequest()->withAttribute('webroot', '/testing/');
        Router::setRequest($request);
        $result = $this->Html->meta('icon');
        $expected = [
            'link' => ['href' => '/testing/test_theme/favicon.ico', 'type' => 'image/x-icon', 'rel' => 'icon'],
            ['link' => ['href' => '/testing/test_theme/favicon.ico', 'type' => 'image/x-icon', 'rel' => 'shortcut icon']],
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Test the inline and block options for meta()
     */
    public function testMetaWithBlocks(): void
    {
        $this->View->expects($this->exactly(2))
            ->method('append')
            ->withConsecutive(
                ['meta', $this->stringContains('robots')],
                ['metaTags', $this->stringContains('favicon.ico')]
            );

        $result = $this->Html->meta('robots', 'ALL', ['block' => true]);
        $this->assertNull($result);

        $result = $this->Html->meta('icon', 'favicon.ico', ['block' => 'metaTags']);
        $this->assertNull($result);
    }

    /**
     * Test meta() with custom tag and block argument
     */
    public function testMetaCustomWithBlock(): void
    {
        $this->View->expects($this->exactly(2))
            ->method('append')
            ->withConsecutive(
                ['meta', $this->stringContains('og:site_name')],
                ['meta', $this->stringContains('og:description')]
            );
        $result = $this->Html->meta(['property' => 'og:site_name', 'content' => 'CakePHP', 'block' => true]);
        $this->assertNull($result, 'compact style should work');

        $result = $this->Html->meta(['property' => 'og:description', 'content' => 'CakePHP'], null, ['block' => true]);
        $this->assertNull($result, 'backwards compat style should work.');
    }

    /**
     * testTableHeaders method
     */
    public function testTableHeaders(): void
    {
        $result = $this->Html->tableHeaders(['ID', 'Name', 'Date']);
        $expected = ['<tr', '<th', 'ID', '/th', '<th', 'Name', '/th', '<th', 'Date', '/th', '/tr'];
        $this->assertHtml($expected, $result);

        $expected = ['<tr', '<th', 'ID', '/th', '<th class="highlight"', 'Name', '/th', '<th', 'Date', '/th', '/tr'];
        $resultComma = $this->Html->tableHeaders(['ID', ['Name', ['class' => 'highlight']], 'Date']);
        $resultAssoc = $this->Html->tableHeaders(['ID', ['Name' => ['class' => 'highlight']], 'Date']);
        $this->assertHtml($expected, $resultComma);
        $this->assertHtml($expected, $resultAssoc);

        $expected = ['<tr', '<th', 'ID', '/th', '<th class="highlight" width="120px"', 'Name', '/th', '<th', 'Date', '/th', '/tr'];
        $resultComma = $this->Html->tableHeaders(['ID', ['Name', ['class' => 'highlight', 'width' => '120px']], 'Date']);
        $resultAssoc = $this->Html->tableHeaders(['ID', ['Name' => ['class' => 'highlight', 'width' => '120px']], 'Date']);
        $this->assertHtml($expected, $resultComma);
        $this->assertHtml($expected, $resultAssoc);

        $expected = ['<tr', '<th', 'ID', '/th', '<th', 'Name', '/th', '<th', 'Date', '/th', '/tr'];
        $resultComma = $this->Html->tableHeaders(['ID', ['Name', []], 'Date']);
        $resultAssoc = $this->Html->tableHeaders(['ID', ['Name' => []], 'Date']);
        $this->assertHtml($expected, $resultComma);
        $this->assertHtml($expected, $resultAssoc);

        $expected = ['<tr', '<th', 'ID', '/th', '<th class="highlight"', '0', '/th', '<th', 'Date', '/th', '/tr'];
        $resultAssoc = $this->Html->tableHeaders(['ID', ['0' => ['class' => 'highlight']], 'Date']);
        $this->assertHtml($expected, $resultAssoc);

        $expected = ['<tr', '<th', 'ID', '/th', '<th class="highlight" width="120px"', '0', '/th', '<th', 'Date', '/th', '/tr'];
        $resultAssoc = $this->Html->tableHeaders(['ID', ['0' => ['class' => 'highlight', 'width' => '120px']], 'Date']);
        $this->assertHtml($expected, $resultAssoc);

        $expected = ['<tr', '<th', 'ID', '/th', '<th', '0', '/th', '<th', 'Date', '/th', '/tr'];
        $resultAssoc = $this->Html->tableHeaders(['ID', ['0' => []], 'Date']);
        $this->assertHtml($expected, $resultAssoc);
    }

    /**
     * testTableCells method
     */
    public function testTableCells(): void
    {
        $tr = [
            'td content 1',
            ['td content 2', ['width' => '100px']],
            ['td content 3', ['width' => '100px']],
        ];
        $result = $this->Html->tableCells($tr);
        $expected = [
            '<tr',
            '<td', 'td content 1', '/td',
            ['td' => ['width' => '100px']], 'td content 2', '/td',
            ['td' => ['width' => 'preg:/100px/']], 'td content 3', '/td',
            '/tr',
        ];
        $this->assertHtml($expected, $result);

        $tr = ['td content 1', 'td content 2', 'td content 3'];
        $result = $this->Html->tableCells($tr, null, null, true);
        $expected = [
            '<tr',
            ['td' => ['class' => 'column-1']], 'td content 1', '/td',
            ['td' => ['class' => 'column-2']], 'td content 2', '/td',
            ['td' => ['class' => 'column-3']], 'td content 3', '/td',
            '/tr',
        ];
        $this->assertHtml($expected, $result);

        $tr = ['td content 1', 'td content 2', 'td content 3'];
        $result = $this->Html->tableCells($tr, true);
        $expected = [
            '<tr',
            ['td' => ['class' => 'column-1']], 'td content 1', '/td',
            ['td' => ['class' => 'column-2']], 'td content 2', '/td',
            ['td' => ['class' => 'column-3']], 'td content 3', '/td',
            '/tr',
        ];
        $this->assertHtml($expected, $result);

        $tr = [
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
        ];
        $result = $this->Html->tableCells($tr, ['class' => 'odd'], ['class' => 'even']);
        $expected = "<tr class=\"even\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"odd\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"even\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>";
        $this->assertSame($expected, $result);

        $tr = [
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
        ];
        $result = $this->Html->tableCells($tr, ['class' => 'odd'], ['class' => 'even']);
        $expected = "<tr class=\"odd\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"even\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"odd\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"even\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>";
        $this->assertSame($expected, $result);

        $tr = [
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
            ['td content 1', 'td content 2', 'td content 3'],
        ];
        $this->Html->tableCells($tr, ['class' => 'odd'], ['class' => 'even']);
        $result = $this->Html->tableCells($tr, ['class' => 'odd'], ['class' => 'even'], false, false);
        $expected = "<tr class=\"odd\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"even\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>\n<tr class=\"odd\"><td>td content 1</td> <td>td content 2</td> <td>td content 3</td></tr>";
        $this->assertSame($expected, $result);

        $tr = [
            'td content 1',
            'td content 2',
            ['td content 3', ['class' => 'foo']],
        ];
        $result = $this->Html->tableCells($tr, null, null, true);
        $expected = [
            '<tr',
            ['td' => ['class' => 'column-1']], 'td content 1', '/td',
            ['td' => ['class' => 'column-2']], 'td content 2', '/td',
            ['td' => ['class' => 'foo column-3']], 'td content 3', '/td',
            '/tr',
        ];
        $this->assertHtml($expected, $result);

        $tr = [
            new FrozenDate('2020-08-27'),
        ];
        $result = $this->Html->tableCells($tr);
        $expected = [
            '<tr',
            '<td', '8/27/20', '/td',
            '/tr',
        ];
        $this->assertHtml($expected, $result);

        $tr = 'string';
        $result = $this->Html->tableCells($tr);
        $expected = [
            '<tr',
            '<td', 'string', '/td',
            '/tr',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * testTag method
     */
    public function testTag(): void
    {
        $result = $this->Html->tag('div', 'text');
        $this->assertHtml(['<div', 'text', '/div'], $result);

        $result = $this->Html->tag('div', '<text>', ['class' => 'class-name', 'escape' => true]);
        $expected = ['div' => ['class' => 'class-name'], '&lt;text&gt;', '/div'];
        $this->assertHtml($expected, $result);
    }

    /**
     * testDiv method
     */
    public function testDiv(): void
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

        $evilKey = '><script>alert(1)</script>';
        $options = [$evilKey => 'some value'];
        $result = $this->Html->div('class-name', '', $options);
        $expected = '<div &gt;&lt;script&gt;alert(1)&lt;/script&gt;="some value" class="class-name"></div>';
        $this->assertSame($expected, $result);
    }

    /**
     * testPara method
     */
    public function testPara(): void
    {
        $result = $this->Html->para('class-name', null);
        $expected = ['p' => ['class' => 'class-name']];
        $this->assertHtml($expected, $result);

        $result = $this->Html->para('class-name', '');
        $expected = ['p' => ['class' => 'class-name'], '/p'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->para('class-name', 'text');
        $expected = ['p' => ['class' => 'class-name'], 'text', '/p'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->para('class-name', '<text>', ['escape' => true]);
        $expected = ['p' => ['class' => 'class-name'], '&lt;text&gt;', '/p'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->para('class-name', 'text"', ['escape' => false]);
        $expected = ['p' => ['class' => 'class-name'], 'text"', '/p'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->para(null, null);
        $expected = ['p' => []];
        $this->assertHtml($expected, $result);

        $result = $this->Html->para(null, 'text');
        $expected = ['p' => [], 'text', '/p'];
        $this->assertHtml($expected, $result);
    }

    /**
     * testMedia method
     */
    public function testMedia(): void
    {
        $result = $this->Html->media('video.webm');
        $expected = ['video' => ['src' => 'files/video.webm'], '/video'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->media('video.webm', [
            'text' => 'Your browser does not support the HTML5 Video element.',
        ]);
        $expected = ['video' => ['src' => 'files/video.webm'], 'Your browser does not support the HTML5 Video element.', '/video'];
        $this->assertHtml($expected, $result);

        $result = $this->Html->media('video.webm', ['autoload', 'muted' => 'muted']);
        $expected = [
            'video' => [
                'src' => 'files/video.webm',
                'autoload' => 'autoload',
                'muted' => 'muted',
            ],
            '/video',
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
            '/video',
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
            '/video',
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->media(null, ['src' => 'video.webm']);
        $expected = [
            'video' => ['src' => 'files/video.webm'],
            '/video',
        ];
        $this->assertHtml($expected, $result);
    }

    /**
     * Tests that CSS and Javascript files of the same name don't conflict with the 'once' test
     */
    public function testCssAndScriptWithSameName(): void
    {
        $result = $this->Html->css('foo');
        $expected = [
            'link' => ['rel' => 'stylesheet', 'href' => 'preg:/.*css\/foo\.css/'],
        ];
        $this->assertHtml($expected, $result);

        $result = $this->Html->script('foo');
        $expected = [
            'script' => ['src' => 'js/foo.js'],
        ];
        $this->assertHtml($expected, $result);
    }
}
