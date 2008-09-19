<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.controller.components
 * @since			CakePHP(tm) v 1.2.0.5435
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('Controller'));
App::import('Component', array('RequestHandler'));
/**
 * RequestHandlerTestController class
 *
 * @package              cake
 * @subpackage           cake.tests.cases.libs.controller.components
 */
class RequestHandlerTestController extends Controller {
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
}
/**
 * RequestHandlerTestDisabledController class
 *
 * @package              cake
 * @subpackage           cake.tests.cases.libs.controller.components
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

	function beforeFilter() {
		$this->RequestHandler->enabled = false;
	}
}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller.components
 */
class RequestHandlerComponentTest extends CakeTestCase {
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
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
		$this->assertTrue(is_object($this->Controller->data));
		$this->assertEqual(strtolower(get_class($this->Controller->data)), 'xml');
	}
/**
 * testStartupCallback with charset.
 *
 * @return void
 **/
	function testStartupCallbackCharset() {
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['CONTENT_TYPE'] = 'application/xml; charset=UTF-8';
		$this->RequestHandler->startup($this->Controller);
		$this->assertTrue(is_object($this->Controller->data));
		$this->assertEqual(strtolower(get_class($this->Controller->data)), 'xml');		
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
		$this->assertEqual($this->RequestHandler->getReferrer(), 'localhost');
		$_SERVER['HTTP_HOST'] = null;
		$_SERVER['HTTP_X_FORWARDED_HOST'] = 'cakephp.org';
		$this->assertEqual($this->RequestHandler->getReferrer(), 'cakephp.org');

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.5, 10.0.1.1, proxy.com';
		$_SERVER['HTTP_CLIENT_IP'] = '192.168.1.2';
		$_SERVER['REMOTE_ADDR'] = '192.168.1.3';
		$this->assertEqual($this->RequestHandler->getClientIP(), '192.168.1.5');

		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
		$this->assertEqual($this->RequestHandler->getClientIP(), '192.168.1.2');

		unset($_SERVER['HTTP_CLIENT_IP']);
		$this->assertEqual($this->RequestHandler->getClientIP(), '192.168.1.3');

		$_SERVER['HTTP_CLIENTADDRESS'] = '10.0.1.2, 10.0.1.1';
		$this->assertEqual($this->RequestHandler->getClientIP(), '10.0.1.2');
	}
/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->RequestHandler);
		unset($this->Controller);
		if (!headers_sent()) {
			header('Content-type: text/html'); //reset content type.
		}
	}
}
?>