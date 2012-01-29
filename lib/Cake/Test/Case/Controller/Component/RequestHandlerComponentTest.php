<?php
/**
 * RequestHandlerComponentTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Controller', 'Controller');
App::uses('RequestHandlerComponent', 'Controller/Component');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Router', 'Routing');

/**
 * RequestHandlerTestController class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class RequestHandlerTestController extends Controller {

/**
 * uses property
 *
 * @var mixed null
 */
	public $uses = null;

/**
 * test method for ajax redirection
 *
 * @return void
 */
	public function destination() {
		$this->viewPath = 'Posts';
		$this->render('index');
	}

/**
 * test method for ajax redirection + parameter parsing
 *
 * @return void
 */
	public function param_method($one = null, $two = null) {
		echo "one: $one two: $two";
		$this->autoRender = false;
	}

/**
 * test method for testing layout rendering when isAjax()
 *
 * @return void
 */
	public function ajax2_layout() {
		if ($this->autoLayout) {
			$this->layout = 'ajax2';
		}
		$this->destination();
	}
}


/**
 * RequestHandlerComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class RequestHandlerComponentTest extends CakeTestCase {

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
		$this->_server = $_SERVER;
		$this->_init();
	}

/**
 * init method
 *
 * @return void
 */
	function _init() {
		$request = new CakeRequest('controller_posts/index');
		$response = new CakeResponse();
		$this->Controller = new RequestHandlerTestController($request, $response);
		$this->Controller->constructClasses();
		$this->RequestHandler = new RequestHandlerComponent($this->Controller->Components);
		$this->_extensions = Router::extensions();
	}

/**
 * endTest method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->RequestHandler, $this->Controller);
		if (!headers_sent()) {
			header('Content-type: text/html'); //reset content type.
		}
		$_SERVER = $this->_server;
		call_user_func_array('Router::parseExtensions', $this->_extensions);
	}

/**
 * Test that the constructor sets the settings.
 *
 * @return void
 */
	public function testConstructorSettings() {
		$settings = array(
			'ajaxLayout' => 'test_ajax'
		);
		$Collection = new ComponentCollection();
		$Collection->init($this->Controller);
		$RequestHandler = new RequestHandlerComponent($Collection, $settings);
		$this->assertEquals($RequestHandler->ajaxLayout, 'test_ajax');
	}

/**
 * testInitializeCallback method
 *
 * @return void
 */
	public function testInitializeCallback() {
		$this->assertNull($this->RequestHandler->ext);
		$this->Controller->request->params['ext'] = 'rss';
		$this->RequestHandler->initialize($this->Controller);
		$this->assertEquals($this->RequestHandler->ext, 'rss');
	}

/**
 * test that a mapped Accept-type header will set $this->ext correctly.
 *
 * @return void
 */
	public function testInitializeContentTypeSettingExt() {
		$this->assertNull($this->RequestHandler->ext);

		$_SERVER['HTTP_ACCEPT'] = 'application/json';
		Router::parseExtensions('json');

		$this->RequestHandler->initialize($this->Controller);
		$this->assertEquals('json', $this->RequestHandler->ext);
	}

/**
 * Test that RequestHandler sets $this->ext when jQuery sends its wonky-ish headers.
 *
 * @return void
 */
	public function testInitializeContentTypeWithjQueryAccept() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json, text/javascript, */*; q=0.01';
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('json');

		$this->RequestHandler->initialize($this->Controller);
		$this->assertEquals('json', $this->RequestHandler->ext);
	}

/**
 * Test that RequestHandler sets $this->ext when jQuery sends its wonky-ish headers
 * and the application is configured to handle multiplate extensions
 *
 * @return void
 */
	public function testInitializeContentTypeWithjQueryAcceptAndMultiplesExtensions() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json, text/javascript, */*; q=0.01';
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('rss', 'json');

		$this->RequestHandler->initialize($this->Controller);
		$this->assertEquals('json', $this->RequestHandler->ext);
	}

/**
 * Test that RequestHandler does not set $this->ext when multple accepts are sent.
 *
 * @return void
 */
	public function testInitializeNoContentTypeWithSingleAccept() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json, text/html, */*; q=0.01';
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('json');

		$this->RequestHandler->initialize($this->Controller);
		$this->assertNull($this->RequestHandler->ext);
	}

/**
 * Test that ext is not set with multiple accepted content types.
 *
 * @return void
 */
	public function testInitializeNoContentTypeWithMultipleAcceptedTypes() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json, text/javascript, application/xml, */*; q=0.01';
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('xml', 'json');

		$this->RequestHandler->initialize($this->Controller);
		$this->assertNull($this->RequestHandler->ext);
	}
	
/**
 * Test that ext is not set with confusing android accepts headers.
 *
 * @return void
 */
	public function testInitializeAmbiguousAndroidAccepts() {
		$_SERVER['HTTP_ACCEPT'] = 'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$this->assertNull($this->RequestHandler->ext);
		Router::parseExtensions('html', 'xml');

		$this->RequestHandler->initialize($this->Controller);
		$this->assertNull($this->RequestHandler->ext);
	}

/**
 * Test that a type mismatch doesn't incorrectly set the ext
 *
 * @return void
 */
	public function testInitializeContentTypeAndExtensionMismatch() {
		$this->assertNull($this->RequestHandler->ext);
		$extensions = Router::extensions();
		Router::parseExtensions('xml');

		$this->Controller->request = $this->getMock('CakeRequest');
		$this->Controller->request->expects($this->any())
			->method('accepts')
			->will($this->returnValue(array('application/json')));

		$this->RequestHandler->initialize($this->Controller);
		$this->assertNull($this->RequestHandler->ext);

		call_user_func_array(array('Router', 'parseExtensions'), $extensions);
	}

/**
 * testDisabling method
 *
 * @return void
 */
	public function testDisabling() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->_init();
		$this->RequestHandler->initialize($this->Controller);
		$this->Controller->beforeFilter();
		$this->RequestHandler->startup($this->Controller);
		$this->assertEquals($this->Controller->params['isAjax'], true);
	}

/**
 * testAutoResponseType method
 *
 * @return void
 */
	public function testAutoResponseType() {
		$this->Controller->ext = '.thtml';
		$this->Controller->request->params['ext'] = 'rss';
		$this->RequestHandler->initialize($this->Controller);
		$this->RequestHandler->startup($this->Controller);
		$this->assertEquals($this->Controller->ext, '.ctp');
	}


/**
 * testAutoAjaxLayout method
 *
 * @return void
 */
	public function testAutoAjaxLayout() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->RequestHandler->startup($this->Controller);
		$this->assertEquals($this->Controller->layout, $this->RequestHandler->ajaxLayout);

		$this->_init();
		$this->Controller->request->params['ext'] = 'js';
		$this->RequestHandler->initialize($this->Controller);
		$this->RequestHandler->startup($this->Controller);
		$this->assertNotEquals($this->Controller->layout, 'ajax');

		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

/**
 * testStartupCallback method
 *
 * @return void
 */
	public function testStartupCallback() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/xml';
		$this->Controller->request = $this->getMock('CakeRequest', array('_readInput'));
		$this->RequestHandler->startup($this->Controller);
		$this->assertTrue(is_array($this->Controller->data));
		$this->assertFalse(is_object($this->Controller->data));
	}

/**
 * testStartupCallback with charset.
 *
 * @return void
 */
	public function testStartupCallbackCharset() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/xml; charset=UTF-8';
		$this->Controller->request = $this->getMock('CakeRequest', array('_readInput'));
		$this->RequestHandler->startup($this->Controller);
		$this->assertTrue(is_array($this->Controller->data));
		$this->assertFalse(is_object($this->Controller->data));
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
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'text/csv';
		$this->Controller->request = $this->getMock('CakeRequest', array('_readInput'));
		$this->Controller->request->expects($this->once())
			->method('_readInput')
			->will($this->returnValue('"A","csv","string"'));
		$this->RequestHandler->addInputType('csv', array('str_getcsv'));
		$this->RequestHandler->startup($this->Controller);
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
		$this->RequestHandler->initialize($this->Controller);
		$this->RequestHandler->startup($this->Controller);
		$this->assertNull($this->RequestHandler->beforeRedirect($this->Controller, '/'));
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
		$this->assertEquals($this->Controller->viewPath, 'request_handler_test' . DS . 'js');
	}

/**
 * test that attachment headers work with renderAs
 *
 * @return void
 */
	public function testRenderAsWithAttachment() {
		$this->RequestHandler->request = $this->getMock('CakeRequest');
		$this->RequestHandler->request->expects($this->any())
			->method('parseAccept')
			->will($this->returnValue(array('1.0' => array('application/xml'))));

		$this->RequestHandler->response = $this->getMock('CakeResponse', array('type', 'download', 'charset'));
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

		$this->assertEquals('Xml', $this->Controller->viewClass);
	}

/**
 * test that respondAs works as expected.
 *
 * @return void
 */
	public function testRespondAs() {
		$this->RequestHandler->response = $this->getMock('CakeResponse', array('type'));
		$this->RequestHandler->response->expects($this->at(0))->method('type')
			->with('application/json');
		$this->RequestHandler->response->expects($this->at(1))->method('type')
			->with('text/xml');

		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_header'), array(&$this->Controller->Components));
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
			'RequestHandlerComponent',
			array('_header'),
			array(&$this->Controller->Components)
		);
		$this->RequestHandler->response = $this->getMock('CakeResponse', array('type', 'download'));
		$this->RequestHandler->request = $this->getMock('CakeRequest');

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
		$this->assertTrue(in_array('Js', $this->Controller->helpers));
	}

/**
 * testRequestClientTypes method
 *
 * @return void
 */
	public function testRequestClientTypes() {
		$_SERVER['HTTP_X_PROTOTYPE_VERSION'] = '1.5';
		$this->assertEquals($this->RequestHandler->getAjaxVersion(), '1.5');

		unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['HTTP_X_PROTOTYPE_VERSION']);
		$this->assertFalse($this->RequestHandler->getAjaxVersion());
	}

/**
 * Tests the detection of various Flash versions
 *
 * @return void
 */
	public function testFlashDetection() {
		$request = $this->getMock('CakeRequest');
		$request->expects($this->once())->method('is')
			->with('flash')
			->will($this->returnValue(true));

		$this->RequestHandler->request = $request;
		$this->assertTrue($this->RequestHandler->isFlash());
	}

/**
 * testRequestContentTypes method
 *
 * @return void
 */
	public function testRequestContentTypes() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertNull($this->RequestHandler->requestedWith());

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$this->assertEquals($this->RequestHandler->requestedWith(), 'json');

		$result = $this->RequestHandler->requestedWith(array('json', 'xml'));
		$this->assertEquals($result, 'json');

		$result =$this->RequestHandler->requestedWith(array('rss', 'atom'));
		$this->assertFalse($result);

		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*';
		$this->assertTrue($this->RequestHandler->isXml());
		$this->assertFalse($this->RequestHandler->isAtom());
		$this->assertFalse($this->RequestHandler->isRSS());

		$_SERVER['HTTP_ACCEPT'] = 'application/atom+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*';
		$this->assertTrue($this->RequestHandler->isAtom());
		$this->assertFalse($this->RequestHandler->isRSS());

		$_SERVER['HTTP_ACCEPT'] = 'application/rss+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*';
		$this->assertFalse($this->RequestHandler->isAtom());
		$this->assertTrue($this->RequestHandler->isRSS());

		$this->assertFalse($this->RequestHandler->isWap());
		$_SERVER['HTTP_ACCEPT'] = 'text/vnd.wap.wml,text/html,text/plain,image/png,*/*';
		$this->assertTrue($this->RequestHandler->isWap());

		$_SERVER['HTTP_ACCEPT'] = 'application/rss+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*';
	}

/**
 * testResponseContentType method
 *
 * @return void
 */
	public function testResponseContentType() {
		$this->assertEquals('html', $this->RequestHandler->responseType());
		$this->assertTrue($this->RequestHandler->respondAs('atom'));
		$this->assertEquals($this->RequestHandler->responseType(), 'atom');
	}

/**
 * testMobileDeviceDetection method
 *
 * @return void
 */
	public function testMobileDeviceDetection() {
		$request = $this->getMock('CakeRequest');
		$request->expects($this->once())->method('is')
			->with('mobile')
			->will($this->returnValue(true));

		$this->RequestHandler->request = $request;
		$this->assertTrue($this->RequestHandler->isMobile());
	}

/**
 * testRequestProperties method
 *
 * @return void
 */
	public function testRequestProperties() {
		$request = $this->getMock('CakeRequest');
		$request->expects($this->once())->method('is')
			->with('ssl')
			->will($this->returnValue(true));

		$this->RequestHandler->request = $request;
		$this->assertTrue($this->RequestHandler->isSsl());
	}

/**
 * testRequestMethod method
 *
 * @return void
 */
	public function testRequestMethod() {
		$request = $this->getMock('CakeRequest');
		$request->expects($this->at(0))->method('is')
			->with('get')
			->will($this->returnValue(true));

		$request->expects($this->at(1))->method('is')
			->with('post')
			->will($this->returnValue(false));

		$request->expects($this->at(2))->method('is')
			->with('delete')
			->will($this->returnValue(true));

		$request->expects($this->at(3))->method('is')
			->with('put')
			->will($this->returnValue(false));

		$this->RequestHandler->request = $request;
		$this->assertTrue($this->RequestHandler->isGet());
		$this->assertFalse($this->RequestHandler->isPost());
		$this->assertTrue($this->RequestHandler->isDelete());
		$this->assertFalse($this->RequestHandler->isPut());
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
		$expected = array('application/xml', 'text/javascript', 'application/json');
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
		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*';
		$this->assertNotEquals($this->RequestHandler->prefers(), 'rss');
		$this->RequestHandler->ext = 'rss';
		$this->assertEquals($this->RequestHandler->prefers(), 'rss');
		$this->assertFalse($this->RequestHandler->prefers('xml'));
		$this->assertEquals($this->RequestHandler->prefers(array('js', 'xml', 'xhtml')), 'xml');
		$this->assertFalse($this->RequestHandler->prefers(array('red', 'blue')));
		$this->assertEquals($this->RequestHandler->prefers(array('js', 'json', 'xhtml')), 'xhtml');
		$this->assertTrue($this->RequestHandler->prefers(array('rss')), 'Should return true if input matches ext.');
		$this->assertFalse($this->RequestHandler->prefers(array('html')), 'No match with ext, return false.');

		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$this->_init();
		$this->assertEquals($this->RequestHandler->prefers(), 'xml');

		$_SERVER['HTTP_ACCEPT'] = '*/*;q=0.5';
		$this->assertEquals($this->RequestHandler->prefers(), 'html');
		$this->assertFalse($this->RequestHandler->prefers('rss'));
	}

/**
 * testCustomContent method
 *
 * @return void
 */
	public function testCustomContent() {
		$_SERVER['HTTP_ACCEPT'] = 'text/x-mobile,text/html;q=0.9,text/plain;q=0.8,*/*;q=0.5';
		$this->RequestHandler->setContent('mobile', 'text/x-mobile');
		$this->RequestHandler->startup($this->Controller);
		$this->assertEquals($this->RequestHandler->prefers(), 'mobile');
	}

/**
 * testClientProperties method
 *
 * @return void
 */
	public function testClientProperties() {
		$request = $this->getMock('CakeRequest');
		$request->expects($this->once())->method('referer');
		$request->expects($this->once())->method('clientIp')->will($this->returnValue(false));

		$this->RequestHandler->request = $request;

		$this->RequestHandler->getReferer();
		$this->RequestHandler->getClientIP(false);
	}

/**
 * test that ajax requests involving redirects trigger requestAction instead.
 *
 * @return void
 */
	public function testAjaxRedirectAsRequestAction() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		), true);

		$this->Controller->RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$this->Controller->request = $this->getMock('CakeRequest');
		$this->Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$this->Controller->RequestHandler->request = $this->Controller->request;
		$this->Controller->RequestHandler->response = $this->Controller->response;
		$this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));
		$this->Controller->RequestHandler->expects($this->once())->method('_stop');

		ob_start();
		$this->Controller->RequestHandler->beforeRedirect(
			$this->Controller, array('controller' => 'request_handler_test', 'action' => 'destination')
		);
		$result = ob_get_clean();
		$this->assertRegExp('/posts index/', $result, 'RequestAction redirect failed.');

		App::build();
	}

/**
 * test that ajax requests involving redirects don't force no layout
 * this would cause the ajax layout to not be rendered.
 *
 * @return void
 */
	public function testAjaxRedirectAsRequestActionStillRenderingLayout() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		), true);

		$this->Controller->RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$this->Controller->request = $this->getMock('CakeRequest');
		$this->Controller->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$this->Controller->RequestHandler->request = $this->Controller->request;
		$this->Controller->RequestHandler->response = $this->Controller->response;
		$this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));
		$this->Controller->RequestHandler->expects($this->once())->method('_stop');

		ob_start();
		$this->Controller->RequestHandler->beforeRedirect(
			$this->Controller, array('controller' => 'request_handler_test', 'action' => 'ajax2_layout')
		);
		$result = ob_get_clean();
		$this->assertRegExp('/posts index/', $result, 'RequestAction redirect failed.');
		$this->assertRegExp('/Ajax!/', $result, 'Layout was not rendered.');

		App::build();
	}

/**
 * test that the beforeRedirect callback properly converts
 * array urls into their correct string ones, and adds base => false so
 * the correct urls are generated.
 *
 * @link http://cakephp.lighthouseapp.com/projects/42648-cakephp-1x/tickets/276
 * @return void
 */
	public function testBeforeRedirectCallbackWithArrayUrl() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(), 'named' => array(), 'form' => array(), 'url' => array('url' => 'accounts/')),
			array('base' => '/officespace', 'here' => '/officespace/accounts/', 'webroot' => '/officespace/')
		));

		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$RequestHandler->response = $this->getMock('CakeResponse', array('_sendHeader'));
		$RequestHandler->request = new CakeRequest('posts/index');
		$RequestHandler->response = $this->getMock('CakeResponse', array('_sendHeader'));

		ob_start();
		$RequestHandler->beforeRedirect(
			$this->Controller,
			array('controller' => 'request_handler_test', 'action' => 'param_method', 'first', 'second')
		);
		$result = ob_get_clean();
		$this->assertEquals($result, 'one: first two: second');
	}

/**
 * assure that beforeRedirect with a status code will correctly set the status header
 *
 * @return void
 */
	public function testBeforeRedirectCallingHeader() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$controller = $this->getMock('Controller', array('header'));
		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$RequestHandler->response = $this->getMock('CakeResponse', array('_sendHeader','statusCode'));
		$RequestHandler->request = $this->getMock('CakeRequest');
		$RequestHandler->request->expects($this->once())->method('is')
			->with('ajax')
			->will($this->returnValue(true));

		$RequestHandler->response->expects($this->once())->method('statusCode')->with(403);

		ob_start();
		$RequestHandler->beforeRedirect($controller, 'request_handler_test/param_method/first/second', 403);
		$result = ob_get_clean();
	}

/**
 * @expectedException CakeException
 * @return void
 */
	public function testAddInputTypeException() {
		$this->RequestHandler->addInputType('csv', array('I am not callable'));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedByEtagStar() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = '*';
		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$RequestHandler->response =  $this->getMock('CakeResponse', array('notModified'));
		$RequestHandler->response->etag('something');
		$RequestHandler->response->expects($this->once())->method('notModified');
		$this->assertFalse($RequestHandler->beforeRender($this->Controller));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedByEtagExact() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$RequestHandler->response =  $this->getMock('CakeResponse', array('notModified'));
		$RequestHandler->response->etag('something', true);
		$RequestHandler->response->expects($this->once())->method('notModified');
		$this->assertFalse($RequestHandler->beforeRender($this->Controller));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedByEtagAndTime() {
		$_SERVER['HTTP_IF_NONE_MATCH'] = 'W/"something", "other"';
		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = '2012-01-01 00:00:00';
		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$RequestHandler->response =  $this->getMock('CakeResponse', array('notModified'));
		$RequestHandler->response->etag('something', true);
		$RequestHandler->response->modified('2012-01-01 00:00:00');
		$RequestHandler->response->expects($this->once())->method('notModified');
		$this->assertFalse($RequestHandler->beforeRender($this->Controller));
	}

/**
 * Test checkNotModified method
 *
 * @return void
 **/
	public function testCheckNotModifiedNoInfo() {
		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'), array(&$this->Controller->Components));
		$RequestHandler->response =  $this->getMock('CakeResponse', array('notModified'));
		$RequestHandler->response->expects($this->never())->method('notModified');
		$this->assertNull($RequestHandler->beforeRender($this->Controller));
	}
}
