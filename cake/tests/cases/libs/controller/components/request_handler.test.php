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

		$this->_init();
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
		$request = new RequestHandlerMockCakeRequest();
		$request->setReturnValue('is', array(true), array('flash'));
		$request->expectOnce('is', array('flash'));
	
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
		$this->_init();
		$this->assertTrue($this->RequestHandler->isXml());
		$this->assertFalse($this->RequestHandler->isAtom());
		$this->assertFalse($this->RequestHandler->isRSS());

		$_SERVER['HTTP_ACCEPT'] = 'application/atom+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*';
		$this->_init();
		$this->assertTrue($this->RequestHandler->isAtom());
		$this->assertFalse($this->RequestHandler->isRSS());

		$_SERVER['HTTP_ACCEPT'] = 'application/rss+xml,text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*';
		$this->_init();
		$this->assertFalse($this->RequestHandler->isAtom());
		$this->assertTrue($this->RequestHandler->isRSS());

		$this->assertFalse($this->RequestHandler->isWap());
		$_SERVER['HTTP_ACCEPT'] = 'text/vnd.wap.wml,text/html,text/plain,image/png,*/*';
		$this->_init();
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
		$request = new RequestHandlerMockCakeRequest();
		$request->setReturnValue('is', array(true), array('mobile'));
		$request->expectOnce('is', array('mobile'));
	
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
		$request = new RequestHandlerMockCakeRequest();
		$request->setReturnValue('is', array(true), array('ssl'));
		$request->expectOnce('is', array('ssl'));
	
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
		$request = new RequestHandlerMockCakeRequest();
		$request->setReturnValue('is', array(true), array('get'));
		$request->setReturnValue('is', array(false), array('post'));
		$request->setReturnValue('is', array(true), array('delete'));
		$request->setReturnValue('is', array(false), array('put'));
		$request->expectCallCount('is', 4);
		
		$this->RequestHandler->request = $request;
		$this->assertTrue($this->RequestHandler->isGet());
		$this->assertTrue($this->RequestHandler->isPost());
		$this->assertTrue($this->RequestHandler->isPut());
		$this->assertTrue($this->RequestHandler->isDelete());
	}

/**
 * testClientContentPreference method
 *
 * @access public
 * @return void
 */
	function testClientContentPreference() {
		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,*/*';
		$this->_init();
		$this->assertNotEqual($this->RequestHandler->prefers(), 'rss');
		$this->RequestHandler->ext = 'rss';
		$this->assertEqual($this->RequestHandler->prefers(), 'rss');
		$this->assertFalse($this->RequestHandler->prefers('xml'));
		$this->assertEqual($this->RequestHandler->prefers(array('js', 'xml', 'xhtml')), 'xml');
		$this->assertTrue($this->RequestHandler->accepts('xml'));

		$_SERVER['HTTP_ACCEPT'] = 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$this->_init();
		$this->assertEqual($this->RequestHandler->prefers(), 'xml');
		$this->assertEqual($this->RequestHandler->accepts(array('js', 'xml', 'html')), 'xml');
		$this->assertFalse($this->RequestHandler->accepts(array('gif', 'jpeg', 'foo')));

		$_SERVER['HTTP_ACCEPT'] = '*/*;q=0.5';
		$this->_init();
		$this->assertEqual($this->RequestHandler->prefers(), 'html');
		$this->assertFalse($this->RequestHandler->prefers('rss'));
		$this->assertFalse($this->RequestHandler->accepts('rss'));
	}

/**
 * testCustomContent method
 *
 * @access public
 * @return void
 */
	function testCustomContent() {
		$_SERVER['HTTP_ACCEPT'] = 'text/x-mobile,text/html;q=0.9,text/plain;q=0.8,*/*;q=0.5';
		$this->_init();
		$this->RequestHandler->setContent('mobile', 'text/x-mobile');
		$this->RequestHandler->startup($this->Controller);
		$this->assertEqual($this->RequestHandler->prefers(), 'mobile');

		$this->_init();
		$this->RequestHandler->setContent(array('mobile' => 'text/x-mobile'));
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
		$request = new RequestHandlerMockCakeRequest();
		$request->expectOnce('referer');
		$request->expectOnce('clientIp', array(false));
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
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->_init();
		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		), true);

		$this->Controller->request = new CakeRequest('posts/index');
		$this->Controller->RequestHandler = $this->getMock('RequestHandlerComponent', array('_stop'));
		$this->Controller->RequestHandler->request = $this->Controller->request;
		$this->Controller->RequestHandler->expects($this->once())->method('_stop');

		ob_start();
		$this->Controller->RequestHandler->beforeRedirect(
			$this->Controller, array('controller' => 'request_handler_test', 'action' => 'destination')
		);
		$result = ob_get_clean();
		$this->assertPattern('/posts index/', $result, 'RequestAction redirect failed.');

		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
		App::build();
	}

/**
 * test that ajax requests involving redirects don't force no layout
 * this would cause the ajax layout to not be rendered.
 *
 * @return void
 */
	function testAjaxRedirectAsRequestActionStillRenderingLayout() {
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$this->_init();
		App::build(array(
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		), true);

		$this->Controller->RequestHandler = new NoStopRequestHandler($this);
		$this->Controller->RequestHandler->request = $this->Controller->request;
		$this->Controller->RequestHandler->expectOnce('_stop');

		ob_start();
		$this->Controller->RequestHandler->beforeRedirect(
			$this->Controller, array('controller' => 'request_handler_test', 'action' => 'ajax2_layout')
		);
		$result = ob_get_clean();
		$this->assertPattern('/posts index/', $result, 'RequestAction redirect failed.');
		$this->assertPattern('/Ajax!/', $result, 'Layout was not rendered.');

		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
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
