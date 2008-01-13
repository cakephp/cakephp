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
 * @subpackage		cake.tests.cases
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once CAKE.'dispatcher.php';
App::import('Core', 'AppController');

class TestDispatcher extends Dispatcher {

	function _invoke(&$controller, $params, $missingAction) {
		$controller->params =& $params;
		$classVars = get_object_vars($controller);
		if ($missingAction && in_array('scaffold', array_keys($classVars))) {
			uses('controller'. DS . 'scaffold');
			return new Scaffold($controller, $params);
		} elseif ($missingAction && !in_array('scaffold', array_keys($classVars))) {
				return $this->cakeError('missingAction', array(
					array(
						'className' => Inflector::camelize($params['controller']."Controller"),
						'action' => $params['action'],
						'webroot' => $this->webroot,
						'url' => $this->here,
						'base' => $this->base
					)
				));
		}
		return $controller;
	}

	function cakeError($filename) {
		return $filename;
	}

}

class MyPluginAppController extends AppController {

}

class MyPluginController extends MyPluginAppController {

	var $name = 'MyPlugin';
	var $uses = array();

	function index() {
		return true;
	}

	function add() {
		return true;
	}

	function admin_add($id = null) {
		return $id;
	}
}

class SomePagesController extends AppController {

	var $name = 'SomePages';
	var $uses = array();

	function display($page = null) {
		return $page;
	}

	function index() {
		return true;
	}
}

class OtherPagesController extends MyPluginAppController {

	var $name = 'OtherPages';
	var $uses = array();

	function display($page = null) {
		return $page;
	}

	function index() {
		return true;
	}
}

class TestDispatchPagesController extends AppController {

	var $name = 'TestDispatchPages';
	var $uses = array();

	function admin_index() {
		return true;
	}
}

class ArticlesTestAppController extends AppController {

}

class ArticlesTestController extends ArticlesTestAppController {

	var $name = 'ArticlesTest';
	var $uses = array();

	function admin_index() {
		return true;
	}
}

class SomePostsController extends AppController {

	var $name = 'SomePosts';
	var $uses = array();
	var $autoRender = false;

	function beforeFilter() {
		$this->params['action'] = 'view';
		$this->params['pass'] = array('changed');
	}

	function index() {
		return true;
	}
}
class TestCachedPagesController extends AppController {

	var $name = 'TestCachedPages';
	var $uses = array();

	var $helpers = array('Cache');

	var $cacheAction = array('index'=> '+2 sec', 'test_nocache_tags'=>'+2 sec');

	var $viewPath = 'posts';

	function index() {
		$this->render();
	}

	function test_nocache_tags() {
		//$this->cacheAction = '+2 sec';
		$this->render();
	}
}
class TimesheetsController extends AppController {

	var $name = 'Timesheets';

	var $uses = array();

	function index() {
		return true;
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases
 */
class DispatcherTest extends UnitTestCase {

	function setUp() {
		$this->_get = $_GET;
		$_GET = array();
		Configure::write('App.base', false);
		Configure::write('App.baseUrl', false);
		Configure::write('App.dir', 'app');
		Configure::write('App.webroot', 'webroot');
		Configure::write('Cache.disable', true);
	}

	function testParseParamsWithoutZerosAndEmptyPost() {
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/testcontroller/testaction/params1/params2/params3");
		$this->assertIdentical($test['controller'], 'testcontroller');
		$this->assertIdentical($test['action'], 'testaction');
		$this->assertIdentical($test['pass'][0], 'params1');
		$this->assertIdentical($test['pass'][1], 'params2');
		$this->assertIdentical($test['pass'][2], 'params3');
		$this->assertFalse(!empty($test['form']));
	}

	function testParseParamsReturnsPostedData() {
		$_POST['testdata'] = "My Posted Content";
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/");
		$this->assertTrue($test['form'], "Parsed URL not returning post data");
		$this->assertIdentical($test['form']['testdata'], "My Posted Content");
	}

	function testParseParamsWithSingleZero() {
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/testcontroller/testaction/1/0/23");
		$this->assertIdentical($test['controller'], 'testcontroller');
		$this->assertIdentical($test['action'], 'testaction');
		$this->assertIdentical($test['pass'][0], '1');
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][1]);
		$this->assertIdentical($test['pass'][2], '23');
	}

	function testParseParamsWithManySingleZeros() {
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/testcontroller/testaction/0/0/0/0/0/0");
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][0]);
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][1]);
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][2]);
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][3]);
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][4]);
		$this->assertPattern('/\\A(?:0)\\z/', $test['pass'][5]);
	}

	function testParseParamsWithManyZerosInEachSectionOfUrl() {
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/testcontroller/testaction/000/0000/00000/000000/000000/0000000");
		$this->assertPattern('/\\A(?:000)\\z/', $test['pass'][0]);
		$this->assertPattern('/\\A(?:0000)\\z/', $test['pass'][1]);
		$this->assertPattern('/\\A(?:00000)\\z/', $test['pass'][2]);
		$this->assertPattern('/\\A(?:000000)\\z/', $test['pass'][3]);
		$this->assertPattern('/\\A(?:000000)\\z/', $test['pass'][4]);
		$this->assertPattern('/\\A(?:0000000)\\z/', $test['pass'][5]);
	}

	function testParseParamsWithMixedOneToManyZerosInEachSectionOfUrl() {
		$Dispatcher =& new Dispatcher();
		$test = $Dispatcher->parseParams("/testcontroller/testaction/01/0403/04010/000002/000030/0000400");
		$this->assertPattern('/\\A(?:01)\\z/', $test['pass'][0]);
		$this->assertPattern('/\\A(?:0403)\\z/', $test['pass'][1]);
		$this->assertPattern('/\\A(?:04010)\\z/', $test['pass'][2]);
		$this->assertPattern('/\\A(?:000002)\\z/', $test['pass'][3]);
		$this->assertPattern('/\\A(?:000030)\\z/', $test['pass'][4]);
		$this->assertPattern('/\\A(?:0000400)\\z/', $test['pass'][5]);
	}

	function testGetUrl() {
		$Dispatcher =& new Dispatcher();
		$Dispatcher->base = '/app/webroot/index.php';
		$uri = '/app/webroot/index.php/posts/add';
		$result = $Dispatcher->getUrl($uri);
		$expected = 'posts/add';
		$this->assertEqual($expected, $result);

		Configure::write('App.baseUrl', '/app/webroot/index.php');

		$uri = '/posts/add';
		$result = $Dispatcher->getUrl($uri);
		$expected = 'posts/add';
		$this->assertEqual($expected, $result);

		$_GET['url'] = array();
		Configure::write('App.base', '/control');
		$Dispatcher =& new Dispatcher();
		$uri = '/control/students/browse';
		$result = $Dispatcher->getUrl($uri);
		$expected = 'students/browse';
		$this->assertEqual($expected, $result);
	}

	function testBaseUrlAndWebrootWithModRewrite() {
		$Dispatcher =& new Dispatcher();

		$Dispatcher->base = false;
		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/1.2.x.x/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/1.2.x.x/app/webroot/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '/1.2.x.x';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/1.2.x.x/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		$Dispatcher->base = false;
		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/app/webroot';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/1.2.x.x/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		$Dispatcher->base = false;
		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches/1.2.x.x/test/';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/1.2.x.x/test/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/webroot/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		$Dispatcher->base = false;;
		$_SERVER['DOCUMENT_ROOT'] = '/some/apps/where';
		$_SERVER['SCRIPT_FILENAME'] = '/some/apps/where/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/some/apps/where/app/webroot/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '/some/apps/where';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/some/apps/where/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);


		Configure::write('App.dir', 'auth');

		$Dispatcher->base = false;;
		$_SERVER['DOCUMENT_ROOT'] = '/cake/repo/branches';
		$_SERVER['SCRIPT_FILENAME'] = '/cake/repo/branches/demos/auth/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/demos/auth/webroot/index.php';

		$result = $Dispatcher->baseUrl();
		$expected = '/demos/auth';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/demos/auth/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.dir', 'code');

		$Dispatcher->base = false;;
		$_SERVER['DOCUMENT_ROOT'] = '/Library/WebServer/Documents';
		$_SERVER['SCRIPT_FILENAME'] = '/Library/WebServer/Documents/clients/PewterReport/code/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/clients/PewterReport/code/webroot/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '/clients/PewterReport/code';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/clients/PewterReport/code/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);
	}

	function testBaseUrlwithModRewriteAlias() {
		$_SERVER['DOCUMENT_ROOT'] = '/home/aplusnur/public_html';
		$_SERVER['SCRIPT_FILENAME'] = '/home/aplusnur/cake2/app/webroot/index.php';
		$_SERVER['PHP_SELF'] = '/control/index.php';

		Configure::write('App.base', '/control');

		$Dispatcher =& new Dispatcher();
		$result = $Dispatcher->baseUrl();
		$expected = '/control';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/control/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.base', false);
		Configure::write('App.dir', 'affiliate');
		Configure::write('App.webroot', 'newaffiliate');

		$_SERVER['DOCUMENT_ROOT'] = '/var/www/abtravaff/html';
		$_SERVER['SCRIPT_FILENAME'] = '/var/www/abtravaff/html/newaffiliate/index.php';
		$_SERVER['PHP_SELF'] = '/newaffiliate/index.php';
		$Dispatcher =& new Dispatcher();
		$result = $Dispatcher->baseUrl();
		$expected = '/newaffiliate';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/newaffiliate/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);
	}

	function testBaseUrlAndWebrootWithBaseUrl() {
		$Dispatcher =& new Dispatcher();

		Configure::write('App.dir', 'app');

		Configure::write('App.baseUrl', '/app/webroot/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/app/webroot/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/app/webroot/test.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/app/webroot/test.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/app/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/app/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/CakeBB/app/webroot/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/CakeBB/app/webroot/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/CakeBB/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/CakeBB/app/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/CakeBB/app/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/CakeBB/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/CakeBB/index.php');
		$result = $Dispatcher->baseUrl();
		$expected = '/CakeBB/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/CakeBB/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.baseUrl', '/dbhauser/index.php');
		$_SERVER['DOCUMENT_ROOT'] = '/kunden/homepages/4/d181710652/htdocs/joomla';
		$_SERVER['SCRIPT_FILENAME'] = '/kunden/homepages/4/d181710652/htdocs/joomla/dbhauser/index.php';
		$result = $Dispatcher->baseUrl();
		$expected = '/dbhauser/index.php';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/dbhauser/app/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);
	}

	function testBaseUrlAndWebrootWithBase() {
		$Dispatcher =& new Dispatcher();
		$Dispatcher->base = '/app';
		$result = $Dispatcher->baseUrl();
		$expected = '/app';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/app/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		$Dispatcher->base = '';
		$result = $Dispatcher->baseUrl();
		$expected = '';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);

		Configure::write('App.dir', 'testbed');
		$Dispatcher->base = '/cake/testbed/webroot';
		$result = $Dispatcher->baseUrl();
		$expected = '/cake/testbed/webroot';
		$this->assertEqual($expected, $result);
		$expectedWebroot = '/cake/testbed/webroot/';
		$this->assertEqual($expectedWebroot, $Dispatcher->webroot);
	}

	function testMissingController() {
		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl','/index.php');
		$url = 'some_controller/home/param:value/param2:value2';
		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'missingController';
		$this->assertEqual($expected, $controller);
	}

	function testPrivate() {
		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl','/index.php');
		$url = 'some_pages/redirect/param:value/param2:value2';

		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'privateAction';
		$this->assertEqual($expected, $controller);
	}

	function testMissingAction() {
		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl','/index.php');
		$url = 'some_pages/home/param:value/param2:value2';

		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return'=> 1));
		set_error_handler('simpleTestErrorHandler');
		$expected = 'missingAction';
		$this->assertEqual($expected, $controller);
	}

	function testDispatch() {
		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl','/index.php');
		$url = 'pages/home/param:value/param2:value2';

		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'Pages';
		$this->assertEqual($expected, $controller->name);

		$expected = array('0' => 'home', 'param' => 'value', 'param2' => 'value2');
		$this->assertIdentical($expected, $controller->passedArgs);


		Configure::write('App.baseUrl','/pages/index.php');

		$url = 'pages/home';
		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'Pages';
		$this->assertEqual($expected, $controller->name);

		$url = 'pages/home/';
		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'Pages';
		$this->assertEqual($expected, $controller->name);


		unset($Dispatcher);
		$Dispatcher =& new TestDispatcher();
		Configure::write('App.baseUrl','/timesheets/index.php');

		$url = 'timesheets';
		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'Timesheets';
		$this->assertEqual($expected, $controller->name);

		$url = 'timesheets/';
		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$this->assertEqual('Timesheets', $controller->name);
		$this->assertEqual('/timesheets/index.php', $Dispatcher->base);
	}

	function testAdminDispatch() {
		$_POST = array();
		$Dispatcher =& new TestDispatcher();
		Configure::write('Routing.admin', 'admin');
		Configure::write('App.baseUrl','/cake/repo/branches/1.2.x.x/index.php');
		$url = 'admin/test_dispatch_pages/index/param:value/param2:value2';

		Router::reload();
		$Router =& Router::getInstance();

		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'TestDispatchPages';
		$this->assertEqual($expected, $controller->name);

		$expected = array('param' => 'value', 'param2' => 'value2');
		$this->assertIdentical($expected, $controller->passedArgs);
		$this->assertTrue($controller->params['admin']);

		$expected = '/cake/repo/branches/1.2.x.x/index.php/admin/test_dispatch_pages/index/param:value/param2:value2';
		$this->assertIdentical($expected, $controller->here);

		$expected = '/cake/repo/branches/1.2.x.x/index.php';
		$this->assertIdentical($expected, $controller->base);
	}

	function testPluginDispatch() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		Router::connect('/my_plugin/:controller/*', array('plugin'=>'my_plugin', 'controller'=>'pages', 'action'=>'display'));

		$Dispatcher->base = false;
		$url = 'my_plugin/some_pages/home/param:value/param2:value2';

		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');


		$result = $Dispatcher->parseParams($url);
		$expected = array('pass' => array('home'),
						'named' => array('param'=> 'value', 'param2'=> 'value2'),
							'plugin'=> 'my_plugin', 'controller'=> 'some_pages', 'action'=> 'display',
							'form'=> null, //array('testdata'=> 'My Posted Data'),
							'url'=> array('url'=> 'my_plugin/some_pages/home/param:value/param2:value2'),
							'bare'=> 0, 'webservices'=> '');
		ksort($expected);
		ksort($result);

		$this->assertEqual($expected, $result);

		$expected = 'my_plugin';
		$this->assertIdentical($expected, $controller->plugin);

		$expected = 'SomePages';
		$this->assertIdentical($expected, $controller->name);

		$expected = array('0' => 'home', 'param'=>'value', 'param2'=>'value2');
		$this->assertIdentical($expected, $controller->passedArgs);

		$expected = '/cake/repo/branches/1.2.x.x/my_plugin/some_pages/home/param:value/param2:value2';
		$this->assertIdentical($expected, $controller->here);

		$expected = '/cake/repo/branches/1.2.x.x';
		$this->assertIdentical($expected, $controller->base);
	}

	function testAutomaticPluginDispatch() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		Router::connect('/my_plugin/:controller/:action/*', array('plugin'=>'my_plugin', 'controller'=>'pages', 'action'=>'display'));

		$Dispatcher->base = false;

		$url = 'my_plugin/other_pages/index/param:value/param2:value2';

		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return'=> 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'my_plugin';
		$this->assertIdentical($expected, $controller->plugin);

		$expected = 'OtherPages';
		$this->assertIdentical($expected, $controller->name);

		$expected = 'index';
		$this->assertIdentical($expected, $controller->action);

		$expected = array('param'=>'value', 'param2'=>'value2');
		$this->assertIdentical($expected, $controller->passedArgs);

		$expected = '/cake/repo/branches/1.2.x.x/my_plugin/other_pages/index/param:value/param2:value2';
		$this->assertIdentical($expected, $controller->here);

		$expected = '/cake/repo/branches/1.2.x.x';
		$this->assertIdentical($expected, $controller->base);
	}

	function testAutomaticPluginControllerDispatch() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'my_plugin/add/param:value/param2:value2';

		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'my_plugin';
		$this->assertIdentical($controller->plugin, $expected);

		$expected = 'MyPlugin';
		$this->assertIdentical($controller->name, $expected);

		$expected = 'add';
		$this->assertIdentical($controller->action, $expected);

		$expected = array('param' => 'value', 'param2' => 'value2');
		$this->assertEqual($controller->params['named'], $expected);


		Configure::write('Routing.admin', 'admin');

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'admin/my_plugin/add/5/param:value/param2:value2';

		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'my_plugin';
		$this->assertIdentical($controller->plugin, $expected);

		$expected = 'MyPlugin';
		$this->assertIdentical($controller->name, $expected);

		$expected = 'admin_add';
		$this->assertIdentical($controller->action, $expected);

		$expected = array(0 => 5, 'param'=>'value', 'param2'=>'value2');
		$this->assertEqual($controller->passedArgs, $expected);

		Router::reload();

		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'admin/articles_test';

		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'articles_test';
		$this->assertIdentical($controller->plugin, $expected);

		$expected = 'ArticlesTest';
		$this->assertIdentical($controller->name, $expected);

		$expected = 'admin_index';
		$this->assertIdentical($controller->action, $expected);

		$expected = array('pass'=> array(), 'named' => array(), 'controller' => 'articles_test', 'plugin' => 'articles_test', 'action' => 'admin_index',
							'prefix' => 'admin', 'admin' =>  true, 'form' => array(), 'url' => array('url' => 'admin/articles_test'),
							'bare' => 0, 'webservices' => null, 'return' => 1
						);
		$this->assertEqual($controller->params, $expected);
	}

	function testAutomaticPluginControllerMissingActionDispatch() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'my_plugin/param:value/param2:value2';
		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return'=> 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'missingAction';
		$this->assertIdentical($expected, $controller);
	}

	function testPrefixProtection() {
		$_POST = array();
		$_SERVER['PHP_SELF'] = '/cake/repo/branches/1.2.x.x/index.php';

		Router::reload();
		Router::connect('/admin/:controller/:action/*', array('prefix'=>'admin'), array('controller', 'action'));

		$Dispatcher =& new TestDispatcher();
		$Dispatcher->base = false;

		$url = 'test_dispatch_pages/admin_index/param:value/param2:value2';
		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'privateAction';
		$this->assertIdentical($expected, $controller);
	}

	function testChangingParamsFromBeforeFilter() {
		$Dispatcher =& new TestDispatcher();
		$url = 'some_posts/index/param:value/param2:value2';

		restore_error_handler();
		$controller = $Dispatcher->dispatch($url, array('return' => 1));
		set_error_handler('simpleTestErrorHandler');

		$expected = 'SomePosts';
		$this->assertEqual($expected, $controller->name);

		$expected = 'view';
		$this->assertEqual($expected, $controller->action);

		$expected = array('changed');
		$this->assertIdentical($expected, $controller->params['pass']);
	}

	function testStaticAssets() {
		Router::reload();
		$Configure = Configure::getInstance();
		$Configure->__objects = null;

		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));
		Configure::write('vendorPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors'. DS));

		$Dispatcher =& new TestDispatcher();

		Configure::write('debug', 0);
		$Dispatcher->params = $Dispatcher->parseParams('css/test_asset.css');

		restore_error_handler();
		ob_start();
		$Dispatcher->cached('css/test_asset.css');
		$result = ob_get_clean();
		set_error_handler('simpleTestErrorHandler');
		$this->assertEqual('this is the test asset css file', $result);

		Configure::write('debug', 0);
		$Dispatcher->params = $Dispatcher->parseParams('test_plugin/css/test_plugin_asset.css');
		restore_error_handler();
		ob_start();
		$Dispatcher->cached('test_plugin/css/test_plugin_asset.css');
		$result = ob_get_clean();
		set_error_handler('simpleTestErrorHandler');
		$this->assertEqual('this is the test plugin asset css file', $result);
	}

	function testFullPageCachingDispatch() {
		Configure::write('Cache.disable', false);
		Configure::write('Cache.check', true);
		Configure::write('debug', 2);

		$_POST = array();
		$_SERVER['PHP_SELF'] = '/';

		Router::reload();
		Router::connect('/', array('controller' => 'test_cached_pages', 'action' => 'index'));

		Configure::write('viewPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS));

		$dispatcher =& new Dispatcher();
		$dispatcher->base = false;

		$url = '/';

		restore_error_handler();
		ob_start();
		$dispatcher->dispatch($url);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($url);
		$cached = ob_get_clean();

		set_error_handler('simpleTestErrorHandler');

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($result, $expected);
		$filename = CACHE . 'views' . DS . Inflector::slug($dispatcher->here) . '.php';
		unlink($filename);


		$dispatcher->base = false;

		$url = 'test_cached_pages/index';

		restore_error_handler();
		ob_start();
		$dispatcher->dispatch($url);
		$out = ob_get_clean();

		ob_start();
		$dispatcher->cached($url);
		$cached = ob_get_clean();

		set_error_handler('simpleTestErrorHandler');

		$result = str_replace(array("\t", "\r\n", "\n"), "", $out);
		$cached = preg_replace('/<!--+[^<>]+-->/', '', $cached);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $cached);

		$this->assertEqual($result, $expected);
		$filename = CACHE . 'views' . DS . Inflector::slug($dispatcher->here) . '.php';
		unlink($filename);
	}

	function testEnvironmentDetection() {
		$dispatcher =& new Dispatcher();

		$environments = array(
			'IIS' => array(
				'No rewrite base path' => array(
					'App' => array('base' => false, 'baseUrl' => '/index.php?', 'server' => 'IIS'),
					'GET' => array(),
					'POST' => array(),
					'SERVER' => array('HTTPS' => 'off', 'SCRIPT_NAME' => '/index.php', 'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot', 'QUERY_STRING' => '', 'REMOTE_ADDR' => '127.0.0.1', 'REMOTE_HOST' => '127.0.0.1', 'REQUEST_METHOD' => 'GET', 'SERVER_NAME' => 'localhost', 'SERVER_PORT' => '80', 'SERVER_PROTOCOL' => 'HTTP/1.1', 'SERVER_SOFTWARE' => 'Microsoft-IIS/5.1', 'APPL_PHYSICAL_PATH' => 'C:\\Inetpub\\wwwroot\\', 'REQUEST_URI' => '/index.php', 'URL' => '/index.php', 'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php', 'ORIG_PATH_INFO' => '/index.php', 'PATH_INFO' => '', 'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\index.php', 'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 'PHP_SELF' => '/index.php', 'HTTP_ACCEPT' => '*/*', 'HTTP_ACCEPT_LANGUAGE' => 'en-us', 'HTTP_CONNECTION' => 'Keep-Alive', 'HTTP_HOST' => 'localhost', 'HTTP_USER_AGENT' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)', 'HTTP_ACCEPT_ENCODING' => 'gzip, deflate', 'argv' => array(), 'argc' => 0),
					'reload' => true,
					'path' => ''
				),
				'No rewrite with path' => array(
					'SERVER' => array('QUERY_STRING' => '/posts/add', 'REQUEST_URI' => '/index.php?/posts/add', 'URL' => '/index.php?/posts/add', 'argv' => array('/posts/add'), 'argc' => 1),
					'reload' => false,
					'path' => '/posts/add'
				),
				'No rewrite sub dir 1' => array(
					'GET' => array(),
					'POST' => array(),
					'SERVER' => array('QUERY_STRING' => '',  'REQUEST_URI' => '/index.php', 'URL' => '/index.php', 'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php', 'ORIG_PATH_INFO' => '/index.php', 'PATH_INFO' => '', 'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\index.php', 'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 'PHP_SELF' => '/index.php', 'argv' => array(), 'argc' => 0),
					'reload' => false,
					'path' => ''
				),
				'No rewrite sub dir 1 with path' => array(
					'GET' => array('/posts/add' => ''),
					'POST' => array(),
					'SERVER' => array('QUERY_STRING' => '/posts/add', 'REQUEST_URI' => '/index.php?/posts/add', 'URL' => '/index.php?/posts/add', 'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\index.php', 'argv' => array ('/posts/add'), 'argc' => 1),
					'reload' => false,
					'path' => '/posts/add'
				),
				'No rewrite sub dir 2' => array(
					'App' => array('base' => false, 'baseUrl' => '/site/index.php?', 'dir' => 'app', 'webroot' => 'webroot', 'server' => 'IIS'),
					'GET' => array(),
					'POST' => array(),
					'SERVER' => array('SCRIPT_NAME' => '/site/index.php', 'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot', 'QUERY_STRING' => '', 'REQUEST_URI' => '/site/index.php', 'URL' => '/site/index.php', 'SCRIPT_FILENAME' => 'C:\\Inetpub\\wwwroot\\site\\index.php', 'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 'PHP_SELF' => '/site/index.php', 'argv' => array(), 'argc' => 0),
					'reload' => false,
					'path' => ''
				),
				'No rewrite sub dir 2 with path' => array(
					'GET' => array('/posts/add' => ''),
					'POST' => array(),
					'SERVER' => array('SCRIPT_NAME' => '/site/index.php', 'PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot', 'QUERY_STRING' => '/posts/add', 'REQUEST_URI' => '/site/index.php?/posts/add', 'URL' => '/site/index.php?/posts/add', 'ORIG_PATH_TRANSLATED' => 'C:\\Inetpub\\wwwroot\\site\\index.php', 'DOCUMENT_ROOT' => 'C:\\Inetpub\\wwwroot', 'PHP_SELF' => '/site/index.php', 'argv' => array ('/posts/add'), 'argc' => 1),
					'reload' => false,
					'path' => '/posts/add'
				)
			),
			'Apache' => array(
				'No rewrite base path' => array(
					'App' => array('base' => false, 'baseUrl' => '/index.php', 'dir' => 'app', 'webroot' => 'webroot'),
				    'GET' => array(),
					'POST' => array(),
					'SERVER' => array('SERVER_NAME' => 'localhost', 'SERVER_ADDR' => '::1', 'SERVER_PORT' => '80', 'REMOTE_ADDR' => '::1', 'DOCUMENT_ROOT' => '/Library/WebServer/Documents/officespace/app/webroot', 'SCRIPT_FILENAME' => '/Library/WebServer/Documents/site/app/webroot/index.php', 'REQUEST_METHOD' => 'GET', 'QUERY_STRING' => '', 'REQUEST_URI' => '/', 'SCRIPT_NAME' => '/index.php', 'PHP_SELF' => '/index.php', 'argv' => array(), 'argc' => 0),
					'reload' => true,
					'path' => ''
				),
				'No rewrite with path' => array(
					'GET' => array(),
					'POST' => array(),
					'SERVER' => array ( 'UNIQUE_ID' => 'VardGqn@17IAAAu7LY8AAAAK', 'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-us) AppleWebKit/523.10.5 (KHTML, like Gecko) Version/3.0.4 Safari/523.10.6', 'HTTP_ACCEPT' => 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5', 'HTTP_ACCEPT_LANGUAGE' => 'en-us', 'HTTP_ACCEPT_ENCODING' => 'gzip, deflate', 'HTTP_CONNECTION' => 'keep-alive', 'HTTP_HOST' => 'localhost', 'DOCUMENT_ROOT' => '/Library/WebServer/Documents/officespace/app/webroot', 'SCRIPT_FILENAME' => '/Library/WebServer/Documents/officespace/app/webroot/index.php', 'QUERY_STRING' => '', 'REQUEST_URI' => '/index.php/posts/add', 'SCRIPT_NAME' => '/index.php', 'PATH_INFO' => '/posts/add', 'PHP_SELF' => '/index.php/posts/add', 'argv' => array(), 'argc' => 0),
					'reload' => false,
					'path' => '/posts/add'
				)
			)
		);
		$backup = $this->__backupEnvironment();

		foreach ($environments as $name => $env) {
			foreach ($env as $descrip => $settings) {
				if ($settings['reload']) {
					$this->__reloadEnvironment();
				}
				$this->__loadEnvironment($settings);
				$this->assertEqual($dispatcher->uri(), $settings['path'], "%s on environment: {$name}, on setting: {$descrip}");
			}
		}
		$this->__loadEnvironment(array_merge(array('reload' => true), $backup));
	}

	function __backupEnvironment() {
		return array(
			'App'	=> Configure::read('App'),
			'GET'	=> $_GET,
			'POST'	=> $_POST,
			'SERVER'=> $_SERVER
		);
	}

	function __reloadEnvironment() {
		foreach ($_GET as $key => $val) {
			unset($_GET[$key]);
		}
		foreach ($_POST as $key => $val) {
			unset($_POST[$key]);
		}
		foreach ($_SERVER as $key => $val) {
			unset($_SERVER[$key]);
		}
		Configure::write('App', array());
	}

	function __loadEnvironment($env) {
		if ($env['reload']) {
			$this->__reloadEnvironment();
		}

		if (isset($env['App'])) {
			Configure::write('App', $env['App']);
		}

		if (isset($env['GET'])) {
			foreach ($env['GET'] as $key => $val) {
				$_GET[$key] = $val;
			}
		}

		if (isset($env['POST'])) {
			foreach ($env['POST'] as $key => $val) {
				$_POST[$key] = $val;
			}
		}

		if (isset($env['SERVER'])) {
			foreach ($env['SERVER'] as $key => $val) {
				$_SERVER[$key] = $val;
			}
		}
	}

	function tearDown() {
		$_GET = $this->_get;
	}
}
?>