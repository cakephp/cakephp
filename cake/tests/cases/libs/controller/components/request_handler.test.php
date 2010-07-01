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

Mock::generatePartial('RequestHandlerComponent', 'NoStopRequestHandler', array('_stop', '_header'));
Mock::generatePartial('Controller', 'RequestHandlerMockController', array('header'));

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
	var $name = 'RequestHandlerTest';

/**
 * uses property
 *
 * @var mixed null
 * @access public
 */
	var $uses = null;

/**
 * construct method
 *
 * @param array $params
 * @access private
 * @return void
 */
	function __construct($params = array()) {
		foreach ($params as $key => $val) {
			$this->{$key} = $val;
		}
		parent::__construct();
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
	var $uses = null;

/**
 * construct method
 *
 * @param array $params
 * @access private
 * @return void
 */
	function __construct($params = array()) {
		foreach ($params as $key => $val) {
			$this->{$key} = $val;
		}
		parent::__construct();
	}

/**
 * beforeFilter method
 *
 * @return void
 * @access public
 */
	function beforeFilter() {
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
	var $Controller;

/**
 * RequestHandler property
 *
 * @var RequestHandlerComponent
 * @access public
 */
	var $RequestHandler;

/**
 * startTest method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->_init();
	}

/**
 * init method
 *
 * @access protected
 * @return void
 */
	function _init() {
		$this->Controller = new RequestHandlerTestController(array('components' => array('RequestHandler')));
		$this->Controller->constructClasses();
		$this->RequestHandler =& $this->Controller->RequestHandler;
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
		$this->Controller->params['url']['ext'] = 'rss';
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
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->beforeFilter();
		$this->Controller->Component->startup($this->Controller);
		$this->assertEqual($this->Controller->params, array('isAjax' => true));

		$this->Controller = new RequestHandlerTestDisabledController(array('components' => array('RequestHandler')));
		$this->Controller->constructClasses();
		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->beforeFilter();
		$this->Controller->Component->startup($this->Controller);
		$this->assertEqual($this->Controller->params, array());
		unset($_SERVER['HTTP_X_REQUESTED_WITH']);
	}

/**
 * testAutoResponseType method
 *
 * @access public
 * @return void
 */
	function testAutoResponseType() {
		$this->Controller->ext = '.thtml';
		$this->Controller->params['url']['ext'] = 'rss';
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
		$RequestHandler = new NoStopRequestHandler();
		$RequestHandler->expectAt(0, '_header', array('Content-Type: application/json'));
		$RequestHandler->expectAt(1, '_header', array('Content-Type: text/xml'));

		$result = $RequestHandler->respondAs('json');
		$this->assertTrue($result);

		$result = $RequestHandler->respondAs('text/xml');
		$this->assertTrue($result);
	}

/**
 * test that attachment headers work with respondAs
 *
 * @return void
 */
	function testRespondAsWithAttachment() {
		$RequestHandler = new NoStopRequestHandler();
		$RequestHandler->expectAt(0, '_header', array('Content-Disposition: attachment; filename="myfile.xml"'));
		$RequestHandler->expectAt(1, '_header', array('Content-Type: text/xml'));

		$result = $RequestHandler->respondAs('xml', array('attachment' => 'myfile.xml'));
		$this->assertTrue($result);
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
		$this->assertFalse($this->RequestHandler->isFlash());
		$_SERVER['HTTP_USER_AGENT'] = 'Shockwave Flash';
		$this->assertTrue($this->RequestHandler->isFlash());
		unset($_SERVER['HTTP_USER_AGENT'], $_SERVER['HTTP_X_REQUESTED_WITH']);

		$this->assertFalse($this->RequestHandler->isAjax());
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
		$_SERVER['HTTP_X_PROTOTYPE_VERSION'] = '1.5';
		$this->assertTrue($this->RequestHandler->isAjax());
		$this->assertEqual($this->RequestHandler->getAjaxVersion(), '1.5');

		unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['HTTP_X_PROTOTYPE_VERSION']);
		$this->assertFalse($this->RequestHandler->isAjax());
		$this->assertFalse($this->RequestHandler->getAjaxVersion());
	}

/**
 * Tests the detection of various Flash versions
 *
 * @access public
 * @return void
 */
	function testFlashDetection() {
		$_agent = env('HTTP_USER_AGENT');
		$_SERVER['HTTP_USER_AGENT'] = 'Shockwave Flash';
		$this->assertTrue($this->RequestHandler->isFlash());

		$_SERVER['HTTP_USER_AGENT'] = 'Adobe Flash';
		$this->assertTrue($this->RequestHandler->isFlash());

		$_SERVER['HTTP_USER_AGENT'] = 'Adobe Flash Player 9';
		$this->assertTrue($this->RequestHandler->isFlash());

		$_SERVER['HTTP_USER_AGENT'] = 'Adobe Flash Player 10';
		$this->assertTrue($this->RequestHandler->isFlash());

		$_SERVER['HTTP_USER_AGENT'] = 'Shock Flash';
		$this->assertFalse($this->RequestHandler->isFlash());

		$_SERVER['HTTP_USER_AGENT'] = $_agent;
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
		$this->assertFalse($this->RequestHandler->isMobile());

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; U; CPU like Mac OS X; en) AppleWebKit/420+ (KHTML, like Gecko) Version/3.0 Mobile/1A543a Safari/419.3';
		$this->assertTrue($this->RequestHandler->isMobile());

		$_SERVER['HTTP_USER_AGENT'] = 'Some imaginary UA';
		$this->RequestHandler->mobileUA []= 'imaginary';
		$this->assertTrue($this->RequestHandler->isMobile());
		array_pop($this->RequestHandler->mobileUA);
	}

/**
 * testRequestProperties method
 *
 * @access public
 * @return void
 */
	function testRequestProperties() {
		$_SERVER['HTTPS'] = 'on';
		$this->assertTrue($this->RequestHandler->isSSL());

		unset($_SERVER['HTTPS']);
		$this->assertFalse($this->RequestHandler->isSSL());

		$_ENV['SCRIPT_URI'] = 'https://localhost/';
		$s = $_SERVER;
		$_SERVER = array();
		$this->assertTrue($this->RequestHandler->isSSL());
		$_SERVER = $s;
	}

/**
 * testRequestMethod method
 *
 * @access public
 * @return void
 */
	function testRequestMethod() {
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertTrue($this->RequestHandler->isGet());
		$this->assertFalse($this->RequestHandler->isPost());
		$this->assertFalse($this->RequestHandler->isPut());
		$this->assertFalse($this->RequestHandler->isDelete());

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertFalse($this->RequestHandler->isGet());
		$this->assertTrue($this->RequestHandler->isPost());
		$this->assertFalse($this->RequestHandler->isPut());
		$this->assertFalse($this->RequestHandler->isDelete());

		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$this->assertFalse($this->RequestHandler->isGet());
		$this->assertFalse($this->RequestHandler->isPost());
		$this->assertTrue($this->RequestHandler->isPut());
		$this->assertFalse($this->RequestHandler->isDelete());

		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$this->assertFalse($this->RequestHandler->isGet());
		$this->assertFalse($this->RequestHandler->isPost());
		$this->assertFalse($this->RequestHandler->isPut());
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
		$_SERVER['HTTP_HOST'] = 'localhost:80';
		$this->assertEqual($this->RequestHandler->getReferer(), 'localhost');
		$_SERVER['HTTP_HOST'] = null;
		$_SERVER['HTTP_X_FORWARDED_HOST'] = 'cakephp.org';
		$this->assertEqual($this->RequestHandler->getReferer(), 'cakephp.org');

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.5, 10.0.1.1, proxy.com';
		$_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.3';
		$this->assertEqual($this->RequestHandler->getClientIP(false), '192.168.1.5');
		$this->assertEqual($this->RequestHandler->getClientIP(), '192.168.1.2');

		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
		$this->assertEqual($this->RequestHandler->getClientIP(), '192.168.1.2');

		unset($_SERVER['HTTP_CLIENT_IP']);
		$this->assertEqual($this->RequestHandler->getClientIP(), '192.168.1.3');

		$_SERVER['HTTP_CLIENTADDRESS'] = '10.0.1.2, 10.0.1.1';
		$this->assertEqual($this->RequestHandler->getClientIP(), '10.0.1.2');
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

		$this->Controller->RequestHandler = new NoStopRequestHandler($this);
		$this->Controller->RequestHandler->expectOnce('_stop');

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

		$RequestHandler =& new NoStopRequestHandler();

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
		$controller =& new RequestHandlerMockController();
		$RequestHandler =& new NoStopRequestHandler();

		$controller->expectOnce('header', array('HTTP/1.1 403 Forbidden'));

		ob_start();
		$RequestHandler->beforeRedirect($controller, 'request_handler_test/param_method/first/second', 403);
		$result = ob_get_clean();
	}

}
