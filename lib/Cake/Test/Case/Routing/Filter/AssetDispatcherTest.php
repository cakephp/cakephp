<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Routing.Filter
 * @since         CakePHP(tm) v 2.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AssetDispatcher', 'Routing/Filter');
App::uses('CakeEvent', 'Event');
App::uses('CakeResponse', 'Network');

/**
 * Class AssetDispatcherTest
 *
 * @package       Cake.Test.Case.Routing.Filter
 */
class AssetDispatcherTest extends CakeTestCase {

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Configure::write('Dispatcher.filters', array());
	}

/**
 * test that asset filters work for theme and plugin assets
 *
 * @return void
 * @triggers DispatcherTest $this, compact('request', 'response')
 * @triggers DispatcherTest $this, compact('request', 'response')
 * @triggers DispatcherTest $this, compact('request', 'response')
 * @triggers DispatcherTest $this, compact('request', 'response')
 * @triggers DispatcherTest $this, compact('request', 'response')
 * @triggers DispatcherTest $this, compact('request', 'response')
 */
	public function testAssetFilterForThemeAndPlugins() {
		$filter = new AssetDispatcher();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		Configure::write('Asset.filter', array(
			'js' => '',
			'css' => ''
		));
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		), App::RESET);

		$request = new CakeRequest('theme/test_theme/ccss/cake.generic.css');
		$event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));
		$this->assertSame($response, $filter->beforeDispatch($event));
		$this->assertTrue($event->isStopped());

		$request = new CakeRequest('theme/test_theme/cjs/debug_kit.js');
		$event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));
		$this->assertSame($response, $filter->beforeDispatch($event));
		$this->assertTrue($event->isStopped());

		$request = new CakeRequest('test_plugin/ccss/cake.generic.css');
		$event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));
		$this->assertSame($response, $filter->beforeDispatch($event));
		$this->assertTrue($event->isStopped());

		$request = new CakeRequest('test_plugin/cjs/debug_kit.js');
		$event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));
		$this->assertSame($response, $filter->beforeDispatch($event));
		$this->assertTrue($event->isStopped());

		$request = new CakeRequest('css/ccss/debug_kit.css');
		$event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));
		$this->assertNull($filter->beforeDispatch($event));
		$this->assertFalse($event->isStopped());

		$request = new CakeRequest('js/cjs/debug_kit.js');
		$event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));
		$this->assertNull($filter->beforeDispatch($event));
		$this->assertFalse($event->isStopped());
	}

/**
 * AssetDispatcher should not 404 extensions that could be handled
 * by Routing.
 *
 * @return void
 * @triggers DispatcherTest $this, compact('request', 'response')
 */
	public function testNoHandleRoutedExtension() {
		$filter = new AssetDispatcher();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		Configure::write('Asset.filter', array(
			'js' => '',
			'css' => ''
		));
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		), App::RESET);
		Router::parseExtensions('json');
		Router::connect('/test_plugin/api/v1/:action', array('controller' => 'api'));
		CakePlugin::load('TestPlugin');

		$request = new CakeRequest('test_plugin/api/v1/forwarding.json');
		$event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));
		$this->assertNull($filter->beforeDispatch($event));
		$this->assertFalse($event->isStopped(), 'Events for routed extensions should not be stopped');
	}

/**
 * Tests that $response->checkNotModified() is called and bypasses
 * file dispatching
 *
 * @return void
 * @triggers DispatcherTest $this, compact('request', 'response')
 * @triggers DispatcherTest $this, compact('request', 'response')
 */
	public function testNotModified() {
		$filter = new AssetDispatcher();
		Configure::write('Asset.filter', array(
			'js' => '',
			'css' => ''
		));
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		$time = filemtime(App::themePath('TestTheme') . 'webroot' . DS . 'img' . DS . 'cake.power.gif');
		$time = new DateTime('@' . $time);

		$response = $this->getMock('CakeResponse', array('send', 'checkNotModified'));
		$request = new CakeRequest('theme/test_theme/img/cake.power.gif');

		$response->expects($this->once())->method('checkNotModified')
			->with($request)
			->will($this->returnValue(true));
		$event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));

		ob_start();
		$this->assertSame($response, $filter->beforeDispatch($event));
		ob_end_clean();
		$this->assertEquals(200, $response->statusCode());
		$this->assertEquals($time->format('D, j M Y H:i:s') . ' GMT', $response->modified());

		$response = $this->getMock('CakeResponse', array('_sendHeader', 'checkNotModified'));
		$request = new CakeRequest('theme/test_theme/img/cake.power.gif');

		$response->expects($this->once())->method('checkNotModified')
			->with($request)
			->will($this->returnValue(true));
		$response->expects($this->never())->method('send');
		$event = new CakeEvent('DispatcherTest', $this, compact('request', 'response'));

		$this->assertSame($response, $filter->beforeDispatch($event));
		$this->assertEquals($time->format('D, j M Y H:i:s') . ' GMT', $response->modified());
	}

/**
 * Test that no exceptions are thrown for //index.php type URLs.
 *
 * @return void
 * @triggers Dispatcher.beforeRequest $this, compact('request', 'response')
 */
	public function test404OnDoubleSlash() {
		$filter = new AssetDispatcher();

		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		$request = new CakeRequest('//index.php');
		$event = new CakeEvent('Dispatcher.beforeRequest', $this, compact('request', 'response'));

		$this->assertNull($filter->beforeDispatch($event));
		$this->assertFalse($event->isStopped());
	}

/**
 * Test that attempts to traverse directories are prevented.
 *
 * @return void
 * @triggers Dispatcher.beforeRequest $this, compact('request', 'response')
 */
	public function test404OnDoubleDot() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		), App::RESET);

		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		$request = new CakeRequest('theme/test_theme/../../../../../../VERSION.txt');
		$event = new CakeEvent('Dispatcher.beforeRequest', $this, compact('request', 'response'));

		$response->expects($this->never())->method('send');

		$filter = new AssetDispatcher();
		$this->assertNull($filter->beforeDispatch($event));
		$this->assertFalse($event->isStopped());
	}

/**
 * Test that attempts to traverse directories with urlencoded paths fail.
 *
 * @return void
 * @triggers Dispatcher.beforeRequest $this, compact('request', 'response')
 */
	public function test404OnDoubleDotEncoded() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		), App::RESET);

		$response = $this->getMock('CakeResponse', array('_sendHeader', 'send'));
		$request = new CakeRequest('theme/test_theme/%2e./%2e./%2e./%2e./%2e./%2e./VERSION.txt');
		$event = new CakeEvent('Dispatcher.beforeRequest', $this, compact('request', 'response'));

		$response->expects($this->never())->method('send');

		$filter = new AssetDispatcher();
		$this->assertNull($filter->beforeDispatch($event));
		$this->assertFalse($event->isStopped());
	}

/**
 * Test asset content length is unset
 *
 * If content length is unset, then the webserver can figure it out.
 *
 * @outputBuffering enabled
 * @return void
 */
	public function testAssetContentLength() {
		Router::reload();
		Configure::write('Dispatcher.filters', array('AssetDispatcher'));
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));

		$url = 'theme/test_theme/css/test_asset.css';
		$file = 'View/Themed/TestTheme/webroot/css/test_asset.css';

		$request = new CakeRequest($url);
		$response = $this->getMock('CakeResponse', array('_sendHeader', 'send'));
		$event = new CakeEvent('Dispatcher.beforeRequest', $this, compact('request', 'response'));

		$filter = new AssetDispatcher();
		$filter->beforeDispatch($event);
		$result = ob_get_clean();

		$path = CAKE . 'Test' . DS . 'test_app' . DS . str_replace('/', DS, $file);
		$file = file_get_contents($path);
		$this->assertEquals($file, $result);

		$expected = filesize($path);
		$headers = $response->header();
		$this->assertFalse($headers['Content-Length']);
	}

}
