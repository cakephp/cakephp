<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/view/1196/Testing>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The Open Group Test Suite License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @since         2.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Routing\Filter;

use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\Routing\Filter\AssetFilter;
use Cake\TestSuite\TestCase;

/**
 * Asset filter test case.
 */
class AssetFilterTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
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
        Plugin::unload();
    }

    /**
     * Tests that $response->checkNotModified() is called and bypasses
     * file dispatching
     *
     * @return void
     * @triggers DispatcherTest $this, compact('request', 'response')
     * @triggers DispatcherTest $this, compact('request', 'response')
     */
    public function testNotModified()
    {
        $filter = new AssetFilter();
        $time = filemtime(Plugin::path('TestTheme') . 'webroot/img/cake.power.gif');
        $time = new \DateTime('@' . $time);

        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['send', 'checkNotModified'])
            ->getMock();
        $request = new ServerRequest('test_theme/img/cake.power.gif');

        $response->expects($this->once())->method('checkNotModified')
            ->with($request)
            ->will($this->returnValue(true));
        $event = new Event('DispatcherTest', $this, compact('request', 'response'));

        ob_start();
        $response = $filter->beforeDispatch($event);
        ob_end_clean();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($time->format('D, j M Y H:i:s') . ' GMT', $response->getHeaderLine('Last-Modified'));

        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader', 'checkNotModified', 'send'])
            ->getMock();
        $request = new ServerRequest('test_theme/img/cake.power.gif');

        $response->expects($this->once())->method('checkNotModified')
            ->with($request)
            ->will($this->returnValue(true));
        $response->expects($this->never())->method('send');
        $event = new Event('DispatcherTest', $this, compact('request', 'response'));

        $response = $filter->beforeDispatch($event);
        $this->assertEquals($time->format('D, j M Y H:i:s') . ' GMT', $response->getHeaderLine('Last-Modified'));
    }

    /**
     * Test that no exceptions are thrown for //index.php type URLs.
     *
     * @return void
     * @triggers Dispatcher.beforeRequest $this, compact('request', 'response')
     */
    public function test404OnDoubleSlash()
    {
        $filter = new AssetFilter();

        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader'])
            ->getMock();
        $request = new ServerRequest('//index.php');
        $event = new Event('Dispatcher.beforeRequest', $this, compact('request', 'response'));

        $this->assertNull($filter->beforeDispatch($event));
        $this->assertFalse($event->isStopped());
    }

    /**
     * Test that 404's are returned when .. is in the URL
     *
     * @return void
     * @triggers Dispatcher.beforeRequest $this, compact('request', 'response')
     * @triggers Dispatcher.beforeRequest $this, compact('request', 'response')
     */
    public function test404OnDoubleDot()
    {
        $filter = new AssetFilter();

        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader'])
            ->getMock();
        $request = new ServerRequest('test_theme/../webroot/css/test_asset.css');
        $event = new Event('Dispatcher.beforeRequest', $this, compact('request', 'response'));

        $this->assertNull($filter->beforeDispatch($event));
        $this->assertFalse($event->isStopped());

        $request = new ServerRequest('test_theme/%3e./webroot/css/test_asset.css');
        $event = new Event('Dispatcher.beforeRequest', $this, compact('request', 'response'));

        $this->assertNull($filter->beforeDispatch($event));
        $this->assertFalse($event->isStopped());
    }

    /**
     * Data provider for asset filter
     *
     * - theme assets.
     * - plugin assets.
     * - plugin assets in sub directories.
     * - unknown plugin assets.
     *
     * @return array
     */
    public static function assetProvider()
    {
        return [
            [
                'test_theme/flash/theme_test.swf',
                'Plugin/TestTheme/webroot/flash/theme_test.swf'
            ],
            [
                'test_theme/pdfs/theme_test.pdf',
                'Plugin/TestTheme/webroot/pdfs/theme_test.pdf'
            ],
            [
                'test_theme/img/test.jpg',
                'Plugin/TestTheme/webroot/img/test.jpg'
            ],
            [
                'test_theme/css/test_asset.css',
                'Plugin/TestTheme/webroot/css/test_asset.css'
            ],
            [
                'test_theme/js/theme.js',
                'Plugin/TestTheme/webroot/js/theme.js'
            ],
            [
                'test_theme/js/one/theme_one.js',
                'Plugin/TestTheme/webroot/js/one/theme_one.js'
            ],
            [
                'test_theme/space%20image.text',
                'Plugin/TestTheme/webroot/space image.text'
            ],
            [
                'test_plugin/root.js',
                'Plugin/TestPlugin/webroot/root.js'
            ],
            [
                'test_plugin/flash/plugin_test.swf',
                'Plugin/TestPlugin/webroot/flash/plugin_test.swf'
            ],
            [
                'test_plugin/pdfs/plugin_test.pdf',
                'Plugin/TestPlugin/webroot/pdfs/plugin_test.pdf'
            ],
            [
                'test_plugin/js/test_plugin/test.js',
                'Plugin/TestPlugin/webroot/js/test_plugin/test.js'
            ],
            [
                'test_plugin/css/test_plugin_asset.css',
                'Plugin/TestPlugin/webroot/css/test_plugin_asset.css'
            ],
            [
                'test_plugin/img/cake.icon.gif',
                'Plugin/TestPlugin/webroot/img/cake.icon.gif'
            ],
            [
                'plugin_js/js/plugin_js.js',
                'Plugin/PluginJs/webroot/js/plugin_js.js'
            ],
            [
                'plugin_js/js/one/plugin_one.js',
                'Plugin/PluginJs/webroot/js/one/plugin_one.js'
            ],
            [
                'test_plugin/css/unknown.extension',
                'Plugin/TestPlugin/webroot/css/unknown.extension'
            ],
            [
                'test_plugin/css/theme_one.htc',
                'Plugin/TestPlugin/webroot/css/theme_one.htc'
            ],
            [
                'company/test_plugin_three/css/company.css',
                'Plugin/Company/TestPluginThree/webroot/css/company.css'
            ],
        ];
    }

    /**
     * Test assets
     *
     * @dataProvider assetProvider
     * @return void
     * @triggers Dispatcher.beforeDispatch $this, compact('request', 'response')
     */
    public function testAsset($url, $file)
    {
        Plugin::load(['Company/TestPluginThree', 'TestPlugin', 'PluginJs']);

        $filter = new AssetFilter();
        $response = $this->getMockBuilder('Cake\Http\Response')
            ->setMethods(['_sendHeader'])
            ->getMock();
        $request = new ServerRequest($url);
        $event = new Event('Dispatcher.beforeDispatch', $this, compact('request', 'response'));

        $response = $filter->beforeDispatch($event);
        $result = $response->getFile();

        $path = TEST_APP . str_replace('/', DS, $file);
        $file = file_get_contents($path);
        $this->assertEquals($file, $result->read());

        $expected = filesize($path);
        $this->assertEquals($expected, $response->getHeaderLine('Content-Length'));
    }
}
