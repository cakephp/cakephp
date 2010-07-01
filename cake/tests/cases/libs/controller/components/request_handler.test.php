<?php
/**
 * RequestHandlerComponentTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Controller', 'Controller', false);
App::import('Component', array('RequestHandler'));

/**
 * RequestHandlerTestController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class RequestHandlerTestController extends Controller {

/**
 * name property
 *
 * @var string
 * @access public
 */
	public $name = 'RequestHandlerTest';

/**
 * uses property
 *
 * @var mixed null
 * @access public
 */
	public $uses = null;

/**
 * construct method
 *
 * @param array $params
 * @access private
 * @return void
 */
	function __construct($request, $params = array()) {
		foreach ($params as $key => $val) {
			$this->{$key} = $val;
		}
		parent::__construct($request);
	}

/**
 * test method for ajax redirection
 *
 * @return void
 */
	function destination() {
		$this->viewPath = 'posts';
		$this->render('index');
	}
/**
 * test method for ajax redirection + parameter parsing
 *
 * @return void
 */
	function param_method($one = null, $two = null) {
		echo "one: $one two: $two";
		$this->autoRender = false;
	}

/**
 * test method for testing layout rendering when isAjax()
 *
 * @return void
 */
	function ajax2_layout() {
		if ($this->autoLayout) {
			$this->layout = 'ajax2';
		}
		$this->destination();
	}
}

/**
 * RequestHandlerTestDisabledController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class RequestHandlerTestDisabledController extends Controller {

/**
 * uses property
 *
 * @var mixed null
 * @access public
 */
	public $uses = null;

/**
 * construct method
 *
 * @param array $params
 * @access private
 * @return void
 */
	function __construct($request, $params = array()) {
		foreach ($params as $key => $val) {
			$this->{$key} = $val;
		}
		parent::__construct($request);
	}

/**
 * beforeFilter method
 *
 * @return void
 */
	public function beforeFilter() {
		$this->RequestHandler->enabled = false;
	}
}

/**
 * RequestHandlerComponentTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class RequestHandlerComponentTest extends CakeTestCase {

/**
 * Controller property
 *
 * @var RequestHandlerTestController
 * @access public
 */
	public $Controller;

/**
 * RequestHandler property
 *
 * @var RequestHandlerComponent
 * @access public
 */
	public $RequestHandler;

/**
 * startTest method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->_server = $_SERVER;
		$this->_init();
	}

/**
 * init method
 *
 * @access protected
 * @return void
 */
	function _init() {
		$request = new CakeRequest('controller_posts/index');
		$this->Controller = new RequestHandlerTestController($request);
		$this->RequestHandler = new RequestHandlerComponent();
		$this->RequestHandler->request = $request;
	}

/**
 * endTest method
 *
 * @access public
 * @return void
 */
	function endTest() {
		unset($this->RequestHandler);
		unset($this->Controller);
		if (!headers_sent()) {
			header('Content-type: text/html'); //reset content type.
		}
		$_SERVER = $this->_server;
		App::build();
	}

/**
 * testInitializeCallback method
 *
 * @access public
 * @return void
 */
	function testInitializeCallback() {
		$this->assertNull($this->RequestHandler->ext);
		$this->Controller->request->params['url']['ext'] = 'rss';
		$this->RequestHandler->initialize($this->Controller);
		$this->assertEqual($this->RequestHandler->ext, 'rss');

		$settings = array(
			'ajaxLayout' => 'test_ajax'
		);
		$this->RequestHandler->initialize($this->Controller, $settings);
		$this->assertEqual($this->RequestHandler->ajaxLayout, 'test_ajax');
	}

/**
 * test that a mapped Accept-type header will set $this->ext correctly.
 *
 * @return void
 */
	function testInitializeContentTypeSettingExt() {
		$this->assertNull($this->RequestHandler->ext);
		$extensions = Router::extensions();
		Router::parseExtensions('json');
		$this->Controller->request = $this->getMock('CakeRequest');
		$this->Controller->request->expects($this->any())->method('accepts')
			->will($this->returnValue(array('application/json')));

		$this->RequestHandler->initialize($this->Controller);
		$this->assertEquals('json', $this->RequestHandler->ext);

		call_user_func_array(array('Router', 'parseExtensions'), $extensions);
	}

/**
 * Test that a type mismatch doesn't incorrectly set the ext
 *
 * @return void
 */
	function testInitializeContentTypeAndExtensionMismatch() {
		$this->assertNull($this->RequestHandler->ext);
		$extensions = Router::extensions();
		Router::parseExtensions('xml');

		$this->Controller->request = $this->getMock('CakeRequest');
		$this->Controller->request->expects($this->any())->method('accepts')
			->will($this->returnValue(array('application/json')));

		$this->RequestHandler->initialize($this->Controller);
		$this->assertNull($this->RequestHandler->ext);

		call_user_func_array(array('Router', 'parseExtensions'), $extensions);
	}

/**
 * testDisabling method
 *
 * @access public
 * @return void
 */
	function testDisabling() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->_init();
		$this->RequestHandler->initialize($this->Controller);
		$this->Controller->beforeFilter();
		$this->RequestHandler->startup($this->Controller);
		$this->assertEqual($this->Controller->params['isAjax'], true);
	}

/**
 * testAutoResponseType method
 *
 * @access public
 * @return void
 */
	function testAutoResponseType() {
		$this->Controller->ext = '.thtml';
		$this->Controller->request->params['url']['ext'] = 'rss';
		$this->RequestHandler->initialize($this->Controller);
		$this->RequestHandler->startup($this->Controller);
		$this->assertEqual($this->Controller->ext, '.ctp');
	}

/**
 * testStartupCallback method
 *
 * @access public
 * @return void
 */
	function testStartupCallback() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/xml';
		$this->RequestHandler->startup($this->Controller);
		$this->assertTrue(is_array($this->Controller->data));
		$this->assertFalse(is_object($this->Controller->data));
	}

/**
 * testStartupCallback with charset.
 *
 * @return void
 */
	function testStartupCallbackCharset() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/xml; charset=UTF-8';
		$this->RequestHandler->startup($this->Controller);
		$this->assertTrue(is_array($this->Controller->data));
		$this->assertFalse(is_object($this->Controller->data));
	}

/**
 * testNonAjaxRedirect method
 *
 * @access public
 * @return void
 */
	function testNonAjaxRedirect() {
		$this->RequestHandler->initialize($this->Controller);
		$this->RequestHandler->startup($this->Controller);
		$this->assertNull($this->RequestHandler->beforeRedirect($this->Controller, '/'));
	}

/**
 * testRenderAs method
 *
 * @access public
 * @return void
 */
	function testRenderAs() {
		$this->assertFalse(in_array('Xml', $this->Controller->helpers));
		$this->RequestHandler->renderAs($this->Controller, 'xml');
		$this->assertTrue(in_array('Xml', $this->Controller->helpers));

		$this->Controller->viewPath = 'request_handler_test\\xml';
		$this->RequestHandler->renderAs($this->Controller, 'js');
		$this->assertEqual($this->Controller->viewPath, 'request_handler_test' . DS . 'js');
	}

/**
 * test that respondAs works as expected.
 *
 * @return void
 */
	function testRespondAs() {
		$debug = Configure::read('debug');
		Configure::write('debug', 0);

		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_header'));
		$RequestHandler->expects($this->at(0))->method('_header')
			->with('Content-type: application/json');
		$RequestHandler->expects($this->at(1))->method('_header')
			->with('Content-type: text/xml');

		$result = $RequestHandler->respondAs('json');
		$this->assertTrue($result);

		$result = $RequestHandler->respondAs('text/xml');
		$this->assertTrue($result);

		Configure::write('debug', $debug);
	}

/**
 * test that attachment headers work with respondAs
 *
 * @return void
 */
	function testRespondAsWithAttachment() {
		$debug = Configure::read('debug');
		Configure::write('debug', 0);

		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_header'));
		$RequestHandler->expects($this->at(0))->method('_header')
			->with('Content-Disposition: attachment; filename="myfile.xml"');
		$RequestHandler->expects($this->at(1))->method('_header')
			->with('Content-type: application/xml');

		$result = $RequestHandler->respondAs('xml', array('attachment' => 'myfile.xml'));
		$this->assertTrue($result);

		Configure::write('debug', $debug);
	}

/**
 * test that calling renderAs() more than once continues to work.
 *
 * @link #6466
 * @return void
 */
	function testRenderAsCalledTwice() {
		$this->RequestHandler->renderAs($this->Controller, 'xml');
		$this->assertEqual($this->Controller->viewPath, 'request_handler_test' . DS . 'xml');
		$this->assertEqual($this->Controller->layoutPath, 'xml');

		$this->assertTrue(in_array('Xml', $this->Controller->helpers));

		$this->RequestHandler->renderAs($this->Controller, 'js');
		$this->assertEqual($this->Controller->viewPath, 'request_handler_test' . DS . 'js');
		$this->assertEqual($this->Controller->layoutPath, 'js');
		$this->assertTrue(in_array('Js', $this->Controller->helpers));
	}

/**
 * testRequestClientTypes method
 *
 * @access public
 * @return void
 */
	function testRequestClientTypes() {
		$_SERVER['HTTP_X_PROTOTYPE_VERSION'] = '1.5';
		$this->assertEqual($this->RequestHandler->getAjaxVersion(), '1.5');

		unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['HTTP_X_PROTOTYPE_VERSION']);
		$this->assertFalse($this->RequestHandler->getAjaxVersion());
	}

/**
 * Tests the detection of various Flash versions
 *
 * @access public
 * @return void
 */
	function testFlashDetection() {
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
 * @access public
 * @return void
 */
	function testRequestContentTypes() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertNull($this->RequestHandler->requestedWith());

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['CONTENT_TYPE'] = 'application/json';
		$this->assertEqual($this->RequestHandler->requestedWith(), 'json');

		$result = $this->RequestHandler->requestedWith(array('json', 'xml'));
		$this->assertEqual($result, 'json');

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
 * @access public
 * @return void
 */
	function testResponseContentType() {
		$this->assertNull($this->RequestHandler->responseType());
		$this->assertTrue($this->RequestHandler->respondAs('atom'));
		$this->assertEqual($this->RequestHandler->responseType(), 'atom');
	}

/**
 * testMobileDeviceDetection method
 *
 * @access public
 * @return void
 */
	function testMobileDeviceDetection() {
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
 * @access public
 * @return void
 */
	function testRequestProperties() {
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
 * @access public
 * @return void
 */
	function testRequestMethod() {
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
	function testMapAlias() {
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
	function testAccepts() {
		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$this->assertEqual($this->RequestHandler->accepts(array('js', 'xml', 'html')), 'xml');
		$this->assertFalse($this->RequestHandler->accepts(array('gif', 'jpeg', 'foo')));

		$_SERVER['HTTP_ACCEPT'] = '*/*;q=0.5';
		$this->assertFalse($this->RequestHandler->accepts('rss'));
	}

/**
 * test accepts and prefers methods.
 *
 * @access public
 * @return void
 */
	function testPrefers() {
		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*';
		$this->assertNotEqual($this->RequestHandler->prefers(), 'rss');
		$this->RequestHandler->ext = 'rss';
		$this->assertEqual($this->RequestHandler->prefers(), 'rss');
		$this->assertFalse($this->RequestHandler->prefers('xml'));
		$this->assertEqual($this->RequestHandler->prefers(array('js', 'xml', 'xhtml')), 'xml');
		$this->assertFalse($this->RequestHandler->prefers(array('red', 'blue')));
		$this->assertEqual($this->RequestHandler->prefers(array('js', 'json', 'xhtml')), 'xhtml');

		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$this->_init();
		$this->assertEqual($this->RequestHandler->prefers(), 'xml');

		$_SERVER['HTTP_ACCEPT'] = '*/*;q=0.5';
		$this->assertEqual($this->RequestHandler->prefers(), 'html');
		$this->assertFalse($this->RequestHandler->prefers('rss'));
	}

/**
 * testCustomContent method
 *
 * @access public
 * @return void
 */
	function testCustomContent() {
		$_SERVER['HTTP_ACCEPT'] = 'text/x-mobile,text/html;q=0.9,text/plain;q=0.8,*/*;q=0.5';
		$this->RequestHandler->setContent('mobile', 'text/x-mobile');
		$this->RequestHandler->startup($this->Controller);
		$this->assertEqual($this->RequestHandler->prefers(), 'mobile');
	}

/**
 * testClientProperties method
 *
 * @access public
 * @return void
 */
	function testClientProperties() {
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
	function testAjaxRedirectAsRequestAction() {
		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		), true);

		$this->Controller->request = $this->getMock('CakeRequest');
		$this->Controller->RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'));
		$this->Controller->RequestHandler->request = $this->Controller->request;
		
		$this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));
		$this->Controller->RequestHandler->expects($this->once())->method('_stop');

		ob_start();
		$this->Controller->RequestHandler->beforeRedirect(
			$this->Controller, array('controller' => 'request_handler_test', 'action' => 'destination')
		);
		$result = ob_get_clean();
		$this->assertPattern('/posts index/', $result, 'RequestAction redirect failed.');

		App::build();
	}

/**
 * test that ajax requests involving redirects don't force no layout
 * this would cause the ajax layout to not be rendered.
 *
 * @return void
 */
	function testAjaxRedirectAsRequestActionStillRenderingLayout() {
		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		), true);

		$this->Controller->request = $this->getMock('CakeRequest');
		$this->Controller->RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'));
		$this->Controller->RequestHandler->request = $this->Controller->request;

		$this->Controller->request->expects($this->any())->method('is')->will($this->returnValue(true));
		$this->Controller->RequestHandler->expects($this->once())->method('_stop');

		ob_start();
		$this->Controller->RequestHandler->beforeRedirect(
			$this->Controller, array('controller' => 'request_handler_test', 'action' => 'ajax2_layout')
		);
		$result = ob_get_clean();
		$this->assertPattern('/posts index/', $result, 'RequestAction redirect failed.');
		$this->assertPattern('/Ajax!/', $result, 'Layout was not rendered.');

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
	function testBeforeRedirectCallbackWithArrayUrl() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		Router::setRequestInfo(array(
			array('plugin' => null, 'controller' => 'accounts', 'action' => 'index', 'pass' => array(), 'named' => array(), 'form' => array(), 'url' => array('url' => 'accounts/')),
			array('base' => '/officespace', 'here' => '/officespace/accounts/', 'webroot' => '/officespace/')
		));

		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'));
		$RequestHandler->request = new CakeRequest('posts/index');

		ob_start();
		$RequestHandler->beforeRedirect(
			$this->Controller,
			array('controller' => 'request_handler_test', 'action' => 'param_method', 'first', 'second')
		);
		$result = ob_get_clean();
		$this->assertEqual($result, 'one: first two: second');
	}

/**
 * assure that beforeRedirect with a status code will correctly set the status header
 *
 * @return void
 */
	function testBeforeRedirectCallingHeader() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

		$controller = $this->getMock('Controller', array('header'));
		$RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'));
		$RequestHandler->request = $this->getMock('CakeRequest');
		$RequestHandler->request->expects($this->once())->method('is')
			->with('ajax')
			->will($this->returnValue(true));

		$controller->expects($this->once())->method('header')->with('HTTP/1.1 403 Forbidden');

		ob_start();
		$RequestHandler->beforeRedirect($controller, 'request_handler_test/param_method/first/second', 403);
		$result = ob_get_clean();
	}

}
