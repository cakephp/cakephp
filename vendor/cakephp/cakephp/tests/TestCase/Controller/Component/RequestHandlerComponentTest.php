<?php
/**
 * RequestHandlerComponentTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\RequestHandlerComponent;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use TestApp\Controller\RequestHandlerTestController;

/**
 * RequestHandlerComponentTest class
 */
class RequestHandlerComponentTest extends TestCase {

/**
 * Controller property
 *
 * @var RequestHandlerTestController
 */
	public $Controller;

/**
 * RequestHandler property
 *
 * @var RequestHandlerComponent
 */
	public $RequestHandler;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('App.namespace', 'TestApp');
		$this->_init();
	}

/**
 * init method
 *
 * @return void
 */
	protected function _init() {
		$request = new Request('controller_posts/index');
		$response = new Response();
		$this->Controller = new RequestHandlerTestController($request, $response);
		$this->Controller->constructClasses();
		$this->RequestHandler = new RequestHandlerComponent($this->Controller->Components);
		$this->_extensions = Router::extensions();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->RequestHandler, $this->Controller);
		if (!headers_sent()) {
			header('Content-type: text/html'); //reset content type.
		}
		call_user_func_array('Cake\Routing\Router::parseExtensions', $this->_extensions);
	}

/**
 * Test that the constructor sets the settings.
 *
 * @return void
 */
	public function testConstructorSettings() {
		$settings = array(
			'ajaxLayout' => 'test_ajax',
			'viewClassMap' => array('json' => 'MyPlugin.MyJson')
		);
		$controller = $this->getMock('Cake\Controller\Controller');
		$collection = new ComponentRegistry($controller);
		$requestHandler = new RequestHandlerComponent($collection, $settings);
		$this->assertEquals('test_ajax', $requestHandler->ajaxLayout);
		$this->assertEquals(array('json' => 'MyPlugin.MyJson'), $requestHandler->settings['viewClassMap']);
	}

/**
 * testInitializeCallback method
 *
 * @return void
 */
	public function testInitializeCallback() {
		$event = new Event('Controller.initialize', $this->Controller);
		$this->assertNull($this->RequestHandler->ext);
		$this->Controller->request->params['_ext'] = 'rss';
		$this->RequestHandler->initialize($event);
		$this->assertEquals('rss', $this->RequestHandler->ext);
	}

/**
 * test that a mapped Accept-type header will set $this->ext correctly.
 *
 * @return void
 */
	public function testInitializeContentTypeSettingExt() {
		$event = new Event('Controller.initialize', $this->Controller);
		$_SERVER['HTTP_ACCEPT'] = 'application/json';
		Router::parseExtensions('json');

		$this->assertNull($this->RequestHandler->ext);

		$this->RequestHandler->initialize($event);
		$this->assertEquals('json', $this->RequestHandler->ext);
	}

/**
 * Test that RequestHandler sets $this->ext when jQuery sends its wonky-ish headers.
 *
 * @return void
 */
	public function testInitializeContentTypeWithjQueryAccept() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json, application/javascript, */*; q=0.01';
		$event = new Event('Controller.initialize', $this->Controller);
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('json');

		$this->RequestHandler->initialize($event);
		$this->assertEquals('json', $this->RequestHandler->ext);
	}

/**
 * Test that RequestHandler does not set extension to csv for text/plain mimetype
 *
 * @return void
 */
	public function testInitializeContentTypeWithjQueryTextPlainAccept() {
		$_SERVER['HTTP_ACCEPT'] = 'text/plain, */*; q=0.01';
		$event = new Event('Controller.initialize', $this->Controller);
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('csv');

		$this->RequestHandler->initialize($event);
		$this->assertNull($this->RequestHandler->ext);
	}

/**
 * Test that RequestHandler sets $this->ext when jQuery sends its wonky-ish headers
 * and the application is configured to handle multiple extensions
 *
 * @return void
 */
	public function testInitializeContentTypeWithjQueryAcceptAndMultiplesExtensions() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json, application/javascript, */*; q=0.01';
		$event = new Event('Controller.initialize', $this->Controller);
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('rss', 'json');

		$this->RequestHandler->initialize($event);
		$this->assertEquals('json', $this->RequestHandler->ext);
	}

/**
 * Test that RequestHandler does not set $this->ext when multiple accepts are sent.
 *
 * @return void
 */
	public function testInitializeNoContentTypeWithSingleAccept() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json, text/html, */*; q=0.01';
		$event = new Event('Controller.initialize', $this->Controller);
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('json');

		$this->RequestHandler->initialize($event);
		$this->assertNull($this->RequestHandler->ext);
	}

/**
 * Test that ext is set to the first listed extension with multiple accepted
 * content types.
 * Having multiple types accepted with same weight, means the client lets the
 * server choose the returned content type.
 *
 * @return void
 */
	public function testInitializeNoContentTypeWithMultipleAcceptedTypes() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json, application/javascript, application/xml, */*; q=0.01';
		$event = new Event('Controller.initialize', $this->Controller);
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('xml', 'json');

		$this->RequestHandler->initialize($event);
		$this->assertEquals('xml', $this->RequestHandler->ext);

		$this->RequestHandler->ext = null;
		Router::setExtensions(array('json', 'xml'), false);

		$this->RequestHandler->initialize($event);
		$this->assertEquals('json', $this->RequestHandler->ext);
	}

/**
 * Test that ext is set to type with highest weight
 *
 * @return void
 */
	public function testInitializeContentTypeWithMultipleAcceptedTypes() {
		$_SERVER['HTTP_ACCEPT'] = 'text/csv;q=1.0, application/json;q=0.8, application/xml;q=0.7';
		$event = new Event('Controller.initialize', $this->Controller);
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('xml', 'json');

		$this->RequestHandler->initialize($event);
		$this->assertEquals('json', $this->RequestHandler->ext);
	}

/**
 * Test that ext is not set with confusing android accepts headers.
 *
 * @return void
 */
	public function testInitializeAmbiguousAndroidAccepts() {
		$_SERVER['HTTP_ACCEPT'] = 'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$event = new Event('Controller.initialize', $this->Controller);
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('html', 'xml');

		$this->RequestHandler->initialize($event);
		$this->assertNull($this->RequestHandler->ext);
	}

/**
 * Test that the headers sent by firefox are not treated as XML requests.
 *
 * @return void
 */
	public function testInititalizeFirefoxHeaderNotXml() {
		$_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;image/png,image/jpeg,image/*;q=0.9,*/*;q=0.8';
		Router::parseExtensions('xml', 'json');

		$event = new Event('Controller.initialize', $this->Controller);
		$this->RequestHandler->initialize($event);
		$this->assertNull($this->RequestHandler->ext);
	}

/**
 * Test that a type mismatch doesn't incorrectly set the ext
 *
 * @return void
 */
	public function testInitializeContentTypeAndExtensionMismatch() {
		$event = new Event('Controller.initialize', $this->Controller);
		$this->assertNull($this->RequestHandler->ext);
		$extensions = Router::extensions();
		Router::parseExtensions('xml');

		$this->Controller->request = $this->getMock('Cake\Network\Request');
		$this->Controller->request->expects($this->any())
			->method('accepts')
			->will($this->returnValue(array('application/json')));

		$this->RequestHandler->initialize($event);
		$this->assertNull($this->RequestHandler->ext);

		call_user_func_array(array('Cake\Routing\Router', 'parseExtensions'), $extensions);
	}

/**
 * testViewClassMap method
 *
 * @return void
 */
	public function testViewClassMap() {
		$event = new Event('Controller.initialize', $this->Controller);
		$this->RequestHandler->settings = array('viewClassMap' => array('json' => 'CustomJson'));
		$this->RequestHandler->initialize($event);
		$result = $this->RequestHandler->viewClassMap();
		$expected = array(
			'json' => 'CustomJson',
			'xml' => 'Xml'
		);
		$this->assertEquals($expected, $result);

		$result = $this->RequestHandler->viewClassMap('xls', 'Excel.Excel');
		$expected = array(
			'json' => 'CustomJson',
			'xml' => 'Xml',
			'xls' => 'Excel.Excel'
		);
		$this->assertEquals($expected, $result);

		$this->RequestHandler->renderAs($this->Controller, 'json');
		$this->assertEquals('TestApp\View\CustomJsonView', $this->Controller->viewClass);
	}

/**
 * testDisabling method
 *
 * @return void
 */
	public function testDisabling() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->_init();
		$event = new Event('Controller.startup', $this->Controller);
		$this->RequestHandler->initialize($event);
		$this->Controller->beforeFilter($event);
		$this->RequestHandler->startup($event);
		$this->assertEquals(true, $this->Controller->request->params['isAjax']);
	}

/**
 * testAutoResponseType method
 *
 * @return void
 */
	public function testAutoResponseType() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->ext = '.thtml';
		$this->Controller->request->params['_ext'] = 'rss';
		$this->RequestHandler->initialize($event);
		$this->RequestHandler->startup($event);
		$this->assertEquals('.ctp', $this->Controller->ext);
	}

/**
 * testAutoAjaxLayout method
 *
 * @return void
 */
	public function testAutoAjaxLayout() {
		$event = new Event('Controller.startup', $this->Controller);
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->RequestHandler->startup($event);
		$this->assertEquals($this->Controller->layout, $this->RequestHandler->ajaxLayout);

		$this->_init();
		$this->Controller->request->params['_ext'] = 'js';
		$this->RequestHandler->initialize($event);
		$this->RequestHandler->startup($event);
		$this->assertNotEquals('ajax', $this->Controller->layout);

		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

/**
 * testStartupCallback method
 *
 * @return void
 */
	public function testStartupCallback() {
		$event = new Event('Controller.startup', $this->Controller);
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/xml';
		$this->Controller->request = $this->getMock('Cake\Network\Request', array('_readInput'));
		$this->RequestHandler->startup($event);
		$this->assertTrue(is_array($this->Controller->request->data));
		$this->assertFalse(is_object($this->Controller->request->data));
	}

/**
 * testStartupCallback with charset.
 *
 * @return void
 */
	public function testStartupCallbackCharset() {
		$event = new Event('Controller.startup', $this->Controller);
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/xml; charset=UTF-8';
		$this->Controller->request = $this->getMock('Cake\Network\Request', array('_readInput'));
		$this->RequestHandler->startup($event);
		$this->assertTrue(is_array($this->Controller->request->data));
		$this->assertFalse(is_object($this->Controller->request->data));
	}

/**
 * Test mapping a new type and having startup process it.
 *
 * @return void
 */
	public function testStartupCustomTypeProcess() {
		if (!function_exists('str_getcsv')) {
			$this->markTestSkipped('Need "str_getcsv" for this test.');
		}
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->request = $this->getMock('Cake\Network\Request', array('_readInput'));
		$this->Controller->request->expects($this->once())
			->method('_readInput')
			->will($this->returnValue('"A","csv","string"'));
		$this->RequestHandler->addInputType('csv', array('str_getcsv'));
		$this->RequestHandler->request->env('REQUEST_METHOD', 'POST');
		$this->RequestHandler->request->env('CONTENT_TYPE', 'text/csv');
		$this->RequestHandler->startup($event);
		$expected = array(
			'A', 'csv', 'string'
		);
		$this->assertEquals($expected, $this->Controller->request->data);
	}

/**
 * testNonAjaxRedirect method
 *
 * @return void
 */
	public function testNonAjaxRedirect() {
		$event = new Event('Controller.startup', $this->Controller);
		$this->RequestHandler->initialize($event);
		$this->RequestHandler->startup($event);
		$this->assertNull($this->RequestHandler->beforeRedirect($event, '/', $this->Controller->response));
	}

/**
 * test that redirects with ajax and no URL don't do anything.
 *
 * @return void
 */
	public function testAjaxRedirectWithNoUrl() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$event = new Event('Controller.startup', $this->Controller);
		$this->Controller->response = $this->getMock('CakeResponse');

		$this->Controller->response->expects($this->never())
			->method('body');

		$this->RequestHandler->initialize($event);
		$this->RequestHandler->startup($event);
		$this->assertNull($this->RequestHandler->beforeRedirect($event, null, $this->Controller->response));
	}

/**
 * testRenderAs method
 *
 * @return void
 */
	public function testRenderAs() {
		$this->assertFalse(in_array('Rss', $this->Controller->helpers));
		$this->RequestHandler->renderAs($this->Controller, 'rss');
		$this->assertTrue(in_array('Rss', $this->Controller->helpers));

		$this->Controller->viewPath = 'request_handler_test\\rss';
		$this->RequestHandler->renderAs($this->Controller, 'js');
		$this->assertEquals('request_handler_test' . DS . 'js', $this->Controller->viewPath);
	}

/**
 * test that attachment headers work with renderAs
 *
 * @return void
 */
	public function testRenderAsWithAttachment() {
		$this->RequestHandler->request = $this->getMock('Cake\Network\Request');
		$this->RequestHandler->request->expects($this->any())
			->method('parseAccept')
			->will($this->returnValue(array('1.0' => array('application/xml'))));

		$this->RequestHandler->response = $this->getMock('Cake\Network\Response', array('type', 'download', 'charset'));
		$this->RequestHandler->response->expects($this->at(0))
			->method('type')
			->with('application/xml');
		$this->RequestHandler->response->expects($this->at(1))
			->method('charset')
			->with('UTF-8');
		$this->RequestHandler->response->expects($this->at(2))
			->method('download')
			->with('myfile.xml');

		$this->RequestHandler->renderAs($this->Controller, 'xml', array('attachment' => 'myfile.xml'));

		$this->assertEquals('Cake\View\XmlView', $this->Controller->viewClass);
	}

/**
 * test that respondAs works as expected.
 *
 * @return void
 */
	public function testRespondAs() {
		$this->RequestHandler->response = $this->getMock('Cake\Network\Response', array('type'));
		$this->RequestHandler->response->expects($this->at(0))->method('type')
			->with('application/json');
		$this->RequestHandler->response->expects($this->at(1))->method('type')
			->with('text/xml');

		$result = $this->RequestHandler->respondAs('json');
		$this->assertTrue($result);
		$result = $this->RequestHandler->respondAs('text/xml');
		$this->assertTrue($result);
	}

/**
 * test that attachment headers work with respondAs
 *
 * @return void
 */
	public function testRespondAsWithAttachment() {
		$this->RequestHandler = $this->getMock(
			'Cake\Controller\Component\RequestHandlerComponent',
			array('_header'),
			array(&$this->Controller->Components)
		);
		$this->RequestHandler->response = $this->getMock('Cake\Network\Response', array('type', 'download'));
		$this->RequestHandler->request = $this->getMock('Cake\Network\Request');

		$this->RequestHandler->request->expects($this->once())
			->method('parseAccept')
			->will($this->returnValue(array('1.0' => array('application/xml'))));

		$this->RequestHandler->response->expects($this->once())->method('download')
			->with('myfile.xml');
		$this->RequestHandler->response->expects($this->once())->method('type')
			->with('application/xml');

		$result = $this->RequestHandler->respondAs('xml', array('attachment' => 'myfile.xml'));
		$this->assertTrue($result);
	}

/**
 * test that calling renderAs() more than once continues to work.
 *
 * @link #6466
 * @return void
 */
	public function testRenderAsCalledTwice() {
		$this->RequestHandler->renderAs($this->Controller, 'print');
		$this->assertEquals('RequestHandlerTest' . DS . 'print', $this->Controller->viewPath);
		$this->assertEquals('print', $this->Controller->layoutPath);

		$this->RequestHandler->renderAs($this->Controller, 'js');
		$this->assertEquals('RequestHandlerTest' . DS . 'js', $this->Controller->viewPath);
		$this->assertEquals('js', $this->Controller->layoutPath);
	}

/**
 * testRequestClientTypes method
 *
 * @return void
 */
	public function testRequestClientTypes() {
		$this->RequestHandler->request->env('HTTP_X_PROTOTYPE_VERSION', '1.5');
		$this->assertEquals('1.5', $this->RequestHandler->getAjaxVersion());

		$this->RequestHandler->request->env('HTTP_X_REQUESTED_WITH', false);
		$this->RequestHandler->request->env('HTTP_X_PROTOTYPE_VERSION', false);
		$this->assertFalse($this->RequestHandler->getAjaxVersion());
	}

/**
 * testRequestContentTypes method
 *
 * @return void
 */
	public function testRequestContentTypes() {
		$this->RequestHandler->request->env('REQUEST_METHOD', 'GET');
		$this->assertNull($this->RequestHandler->requestedWith());

		$this->RequestHandler->request->env('REQUEST_METHOD', 'POST');
		$this->RequestHandler->request->env('CONTENT_TYPE', 'application/json');
		$this->assertEquals('json', $this->RequestHandler->requestedWith());

		$result = $this->RequestHandler->requestedWith(array('json', 'xml'));
		$this->assertEquals('json', $result);

		$result = $this->RequestHandler->requestedWith(array('rss', 'atom'));
		$this->assertFalse($result);

		$this->RequestHandler->request->env('REQUEST_METHOD', 'POST');
		$this->RequestHandler->request->env('CONTENT_TYPE', '');
		$this->RequestHandler->request->env('HTTP_CONTENT_TYPE', 'application/json');

		$result = $this->RequestHandler->requestedWith(array('json', 'xml'));
		$this->assertEquals('json', $result);

		$result = $this->RequestHandler->requestedWith(array('rss', 'atom'));
		$this->assertFalse($result);

		$this->RequestHandler->request->env('HTTP_ACCEPT', 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*');
		$this->assertTrue($this->RequestHandler->isXml());
		$this->assertFalse($this->RequestHandler->isAtom());
		$this->assertFalse($this->RequestHandler->isRSS());

		$this->RequestHandler->request->env('HTTP_ACCEPT', 'application/atom+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*');
		$this->assertTrue($this->RequestHandler->isAtom());
		$this->assertFalse($this->RequestHandler->isRSS());

		$this->RequestHandler->request->env('HTTP_ACCEPT', 'application/rss+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*');
		$this->assertFalse($this->RequestHandler->isAtom());
		$this->assertTrue($this->RequestHandler->isRSS());

		$this->assertFalse($this->RequestHandler->isWap());
		$this->RequestHandler->request->env('HTTP_ACCEPT', 'text/vnd.wap.wml,text/html,text/plain,image/png,*/*');
		$this->assertTrue($this->RequestHandler->isWap());
	}

/**
 * testResponseContentType method
 *
 * @return void
 */
	public function testResponseContentType() {
		$this->assertEquals('html', $this->RequestHandler->responseType());
		$this->assertTrue($this->RequestHandler->respondAs('atom'));
		$this->assertEquals('atom', $this->RequestHandler->responseType());
	}

/**
 * testMobileDeviceDetection method
 *
 * @return void
 */
	public function testMobileDeviceDetection() {
		$request = $this->getMock('Cake\Network\Request');
		$request->expects($this->once())->method('is')
			->with('mobile')
			->will($this->returnValue(true));

		$this->RequestHandler->request = $request;
		$this->assertTrue($this->RequestHandler->isMobile());
	}

/**
 * test that map alias converts aliases to content types.
 *
 * @return void
 */
	public function testMapAlias() {
		$result = $this->RequestHandler->mapAlias('xml');
		$this->assertEquals('application/xml', $result);

		$result = $this->RequestHandler->mapAlias('text/html');
		$this->assertNull($result);

		$result = $this->RequestHandler->mapAlias('wap');
		$this->assertEquals('text/vnd.wap.wml', $result);

		$result = $this->RequestHandler->mapAlias(array('xml', 'js', 'json'));
		$expected = array('application/xml', 'application/javascript', 'application/json');
		$this->assertEquals($expected, $result);
	}

/**
 * test accepts() on the component
 *
 * @return void
 */
	public function testAccepts() {
		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$this->assertTrue($this->RequestHandler->accepts(array('js', 'xml', 'html')));
		$this->assertFalse($this->RequestHandler->accepts(array('gif', 'jpeg', 'foo')));

		$_SERVER['HTTP_ACCEPT'] = '*/*;q=0.5';
		$this->assertFalse($this->RequestHandler->accepts('rss'));
	}

/**
 * test accepts and prefers methods.
 *
 * @return void
 */
	public function testPrefers() {
		$this->RequestHandler->request->env(
			'HTTP_ACCEPT',
			'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*'
		);
		$this->assertNotEquals('rss', $this->RequestHandler->prefers());
		$this->RequestHandler->ext = 'rss';
		$this->assertEquals('rss', $this->RequestHandler->prefers());
		$this->assertFalse($this->RequestHandler->prefers('xml'));
		$this->assertEquals('xml', $this->RequestHandler->prefers(array('js', 'xml', 'xhtml')));
		$this->assertFalse($this->RequestHandler->prefers(array('red', 'blue')));
		$this->assertEquals('xhtml', $this->RequestHandler->prefers(array('js', 'json', 'xhtml')));
		$this->assertTrue($this->RequestHandler->prefers(array('rss')), 'Should return true if input matches ext.');
		$this->assertFalse($this->RequestHandler->prefers(array('html')), 'No match with ext, return false.');

		$this->_init();
		$this->RequestHandler->request->env(
			'HTTP_ACCEPT',
			'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5'
		);
		$this->assertEquals('xml', $this->RequestHandler->prefers());

		$this->RequestHandler->request->env('HTTP_ACCEPT', '*/*;q=0.5');
		$this->assertEquals('html', $this->RequestHandler->prefers());
		$this->assertFalse($this->RequestHandler->prefers('rss'));
	}

/**
 * test that ajax requests involving redirects trigger requestAction instead.
 *
 * @return void
 */
	public function testAjaxRedirectAsRequestAction() {
		Configure::write('App.namespace', 'TestApp');
		Router::connect('/:controller/:action');
		$event = new Event('Controller.beforeRedirect', $this->Controller);

		$this->Controller->RequestHandler = $this->getMock(
			'Cake\Controller\Component\RequestHandlerComponent',
			array('_stop'),
			array(&$this->Controller->Components)
		);
		$this->Controller->request = $this->getMock('Cake\Network\Request');
		$this->Controller->response = $this->getMock('Cake\Network\Response', array('_sendHeader'));
		$this->Controller->RequestHandler->request = $this->Controller->request;
		$this->Controller->RequestHandler->response = $this->Controller->response;
		$this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));
		$this->Controller->RequestHandler->expects($this->once())->method('_stop');

		ob_start();
		$this->Controller->RequestHandler->beforeRedirect(
			$event,
			array('controller' => 'request_handler_test', 'action' => 'destination'),
			$this->Controller->response
		);
		$result = ob_get_clean();
		$this->assertRegExp('/posts index/', $result, 'RequestAction redirect failed.');
	}

/**
 * test that ajax requests involving redirects don't force no layout
 * this would cause the ajax layout to not be rendered.
 *
 * @return void
 */
	public function testAjaxRedirectAsRequestActionStillRenderingLayout() {
		Configure::write('App.namespace', 'TestApp');
		Router::connect('/:controller/:action');
		$event = new Event('Controller.beforeRedirect', $this->Controller);

		$this->Controller->RequestHandler = $this->getMock(
			'Cake\Controller\Component\RequestHandlerComponent',
			array('_stop'),
			array(&$this->Controller->Components)
		);
		$this->Controller->request = $this->getMock('Cake\Network\Request');
		$this->Controller->response = $this->getMock('Cake\Network\Response', array('_sendHeader'));
		$this->Controller->RequestHandler->request = $this->Controller->request;
		$this->Controller->RequestHandler->response = $this->Controller->response;
		$this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));
		$this->Controller->RequestHandler->expects($this->once())->method('_stop');

		ob_start();
		$this->Controller->RequestHandler->beforeRedirect(
			$event,
			array('controller' => 'request_handler_test', 'action' => 'ajax2_layout'),
			$this->Controller->response
		);
		$result = ob_get_clean();
		$this->assertRegExp('/posts index/', $result, 'RequestAction redirect failed.');
		$this->assertRegExp('/Ajax!/', $result, 'Layout was not rendered.');
	}

/**
 * test that the beforeRedirect callback properly converts
 * array URLs into their correct string ones, and adds base => false so
 * the correct URLs are generated.
 *
 * @link https://cakephp.lighthouseapp.com/projects/42648-cakephp-1x/tickets/276
 * @return void
 */
	public function testBeforeRedirectCallbackWithArrayUrl() {
		Configure::write('App.namespace', 'TestApp');
		Router::connect('/:controller/:action/*');
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$event = new Event('Controller.beforeRender', $this->Controller);

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array()),
			array('base' => '', 'here' => '/accounts/', 'webroot' => '/')
		));

		$RequestHandler = $this->getMock(
			'Cake\Controller\Component\RequestHandlerComponent',
			array('_stop'),
			array(&$this->Controller->Components)
		);
		$RequestHandler->response = $this->getMock('Cake\Network\Response', array('_sendHeader'));
		$RequestHandler->request = new Request('posts/index');
		$RequestHandler->response = $this->getMock('Cake\Network\Response', array('_sendHeader'));

		ob_start();
		$RequestHandler->beforeRedirect(
			$event,
			array('controller' => 'request_handler_test', 'action' => 'param_method', 'first', 'second'),
			$this->Controller->response
		);
		$result = ob_get_clean();
		$this->assertEquals('one: first two: second', $result);
	}

/**
 * @expectedException Cake\Error\Exception
 * @return void
 */
	public function testAddInputTypeException() {
		$this->RequestHandler->addInputType('csv', array('I am not callable'));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 */
	public function testCheckNotModifiedByEtagStar() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = '*';
		$event = new Event('Controller.beforeRender', $this->Controller);
		$RequestHandler = $this->getMock('Cake\Controller\Component\RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$RequestHandler->response = $this->getMock('Cake\Network\Response', array('notModified'));
		$RequestHandler->response->etag('something');
		$RequestHandler->response->expects($this->once())->method('notModified');
		$this->assertFalse($RequestHandler->beforeRender($event));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 */
	public function testCheckNotModifiedByEtagExact() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
		$event = new Event('Controller.beforeRender');
		$RequestHandler = $this->getMock('Cake\Controller\Component\RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$RequestHandler->response = $this->getMock('Cake\Network\Response', array('notModified'));
		$RequestHandler->response->etag('something', true);
		$RequestHandler->response->expects($this->once())->method('notModified');
		$this->assertFalse($RequestHandler->beforeRender($event));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 */
	public function testCheckNotModifiedByEtagAndTime() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
		$event = new Event('Controller.beforeRender', $this->Controller);
		$RequestHandler = $this->getMock('Cake\Controller\Component\RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$RequestHandler->response = $this->getMock('Cake\Network\Response', array('notModified'));
		$RequestHandler->response->etag('something', true);
		$RequestHandler->response->modified('2012-01-01 00:00:00');
		$RequestHandler->response->expects($this->once())->method('notModified');
		$this->assertFalse($RequestHandler->beforeRender($event));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 */
	public function testCheckNotModifiedNoInfo() {
		$event = new Event('Controller.beforeRender', $this->Controller);
		$RequestHandler = $this->getMock('Cake\Controller\Component\RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$RequestHandler->response = $this->getMock('Cake\Network\Response', array('notModified'));
		$RequestHandler->response->expects($this->never())->method('notModified');
		$this->assertNull($RequestHandler->beforeRender($event, '', $RequestHandler->response));
	}
}
