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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('controller' . DS . 'controller', 'view'.DS.'view');

class ViewPostsController extends Controller {
	var $name = 'Posts';
	function index() {
		$this->set('testData', 'Some test data');
		$test2 = 'more data';
		$test3 = 'even more data';
		$this->set(compact('test2', 'test3'));
	}
}

class TestView extends View {

	function renderElement($name, $params = array()) {
		return $name;
	}

	function getViewFileName($name = null) {
		return $this->_getViewFileName($name);
	}
	function getLayoutFileName($name = null) {
		return $this->_getLayoutFileName($name);
	}

	function loadHelpers(&$loaded, $helpers, $parent = null) {
		return $this->_loadHelpers($loaded, $helpers, $parent);
	}

	function cakeError($name, $params) {
		return $name;
	}
}

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class ViewTest extends UnitTestCase {

	function setUp() {
		Router::reload();
		$this->Controller = new Controller();
		$this->PostsController = new ViewPostsController();
		$this->PostsController->viewPath = 'posts';
		$this->PostsController->index();
		$this->View = new View($this->PostsController);
	}

	function testPluginGetTemplate() {
		$this->Controller->plugin = 'test_plugin';
		$this->Controller->name = 'TestPlugin';
		$this->Controller->viewPath = 'test_plugin';
		$this->Controller->action = 'index';

		$View = new TestView($this->Controller);
		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));
		Configure::write('viewPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS));

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS .'test_plugin' . DS . 'views' . DS .'test_plugin' . DS .'index.ctp';
		$result = $View->getViewFileName('index');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS .'test_plugin' . DS . 'views' . DS . 'layouts' . DS .'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($result, $expected);
	}

	function testGetTemplate() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'pages';
		$this->Controller->action = 'display';
		$this->Controller->params['pass'] = array('home');

		$View = new TestView($this->Controller);
		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));
		Configure::write('viewPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS, TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS));

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS .'pages' . DS .'home.ctp';
		$result = $View->getViewFileName('home');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS .'posts' . DS .'index.ctp';
		$result = $View->getViewFileName('/posts/index');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'layouts' . DS .'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($result, $expected);

		$View->layoutPath = 'rss';
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'layouts' . DS . 'rss' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($result, $expected);

		$View->layoutPath = 'email' . DS . 'html';
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS . 'layouts' . DS . 'email' . DS . 'html' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($result, $expected);
	}

	function testMissingView() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'pages';
		$this->Controller->action = 'display';
		$this->Controller->params['pass'] = array('home');

		$View = new TestView($this->Controller);

		$expected = 'missingView';
		$result = $View->getViewFileName('does_not_exist');
		$this->assertEqual($result, $expected);

	}

	function testMissingLayout() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Posts';
		$this->Controller->viewPath = 'posts';
		$this->Controller->layout = 'whatever';

		$View = new TestView($this->Controller);
		$expected = 'missingLayout';
		$result = $View->getLayoutFileName();
		$this->assertEqual($result, $expected);
	}

	function testViewVars() {
		$this->assertEqual($this->View->viewVars, array('testData' => 'Some test data', 'test2' => 'more data', 'test3' => 'even more data'));
	}

	function testUUIDGeneration() {
		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, 'form0425fe3bad');
		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, 'forma9918342a7');
		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, 'form3ecf2e3e96');
	}

	function testAddInlineScripts() {
		$this->View->addScript('prototype.js');
		$this->View->addScript('prototype.js');
		$this->assertEqual($this->View->__scripts, array('prototype.js'));

		$this->View->addScript('mainEvent', 'Event.observe(window, "load", function() { doSomething(); }, true);');
		$this->assertEqual($this->View->__scripts, array('prototype.js', 'mainEvent' => 'Event.observe(window, "load", function() { doSomething(); }, true);'));
	}

	function testElementCache() {
		$View = new TestView($this->PostsController);
		$element = 'element_name';
		$result = $View->element($element);
		$this->assertEqual($result, $element);

		$cached = false;
		$result = $View->element($element, array('cache'=>'+1 second'));
		if(file_exists(CACHE . 'views' . DS . 'element_cache_'.$element)) {
			$cached = true;
			unlink(CACHE . 'views' . DS . 'element_cache_'.$element);
		}
		$this->assertTrue($cached);

		$cached = false;
		$result = $View->element($element, array('cache'=>'+1 second', 'other_param'=> true, 'anotherParam'=> true));
		if(file_exists(CACHE . 'views' . DS . 'element_cache_other_param_anotherParam_'.$element)) {
			$cached = true;
			unlink(CACHE . 'views' . DS . 'element_cache_other_param_anotherParam_'.$element);
		}
		$this->assertTrue($cached);

		$cached = false;
		$result = $View->element($element, array('cache'=>array('time'=>'+1 second', 'key'=>'/whatever/here')));
		if(file_exists(CACHE . 'views' . DS . 'element_'.Inflector::slug('/whatever/here').'_'.$element)) {
			$cached = true;
			unlink(CACHE . 'views' . DS . 'element_'.Inflector::slug('/whatever/here').'_'.$element);
		}
		$this->assertTrue($cached);

		$cached = false;
		$result = $View->element($element, array('cache'=>array('time'=>'+1 second', 'key'=>'whatever_here')));
		if(file_exists(CACHE . 'views' . DS . 'element_whatever_here_'.$element)) {
			$cached = true;
			unlink(CACHE . 'views' . DS . 'element_whatever_here_'.$element);
		}
		$this->assertTrue($cached);
		$this->assertEqual($result, $element);

	}

	function testLoadHelpers() {
		$View = new TestView($this->PostsController);

		$loaded = array();
		$result = $View->loadHelpers($loaded, array('Html', 'Form', 'Ajax'));
		$this->assertTrue(is_object($result['Html']));
		$this->assertTrue(is_object($result['Form']));
		$this->assertTrue(is_object($result['Form']->Html));
		$this->assertTrue(is_object($result['Ajax']->Html));

		$View->plugin = 'test_plugin';
		$result = $View->loadHelpers($loaded, array('TestPlugin.TestPluginHelper'));
		$this->assertTrue(is_object($result['TestPluginHelper']));
		$this->assertTrue(is_object($result['TestPluginHelper']->TestPluginOtherHelper));
	}

	function testRenderLoadHelper() {
		$this->PostsController->helpers = array('Html', 'Form', 'Ajax');
		$View = new TestView($this->PostsController);

		$result = $View->_render($View->getViewFileName('index'), array());
		$this->assertEqual($result, 'posts index');

		$helpers = $View->loaded;
		$this->assertTrue(is_object($helpers['html']));
		$this->assertTrue(is_object($helpers['form']));
		$this->assertTrue(is_object($helpers['form']->Html));
		$this->assertTrue(is_object($helpers['ajax']->Html));

		$this->PostsController->helpers = array('Html', 'Form', 'Ajax', 'TestPlugin.TestPluginHelper');
		$View = new TestView($this->PostsController);

		$result = $View->_render($View->getViewFileName('index'), array());
		$this->assertEqual($result, 'posts index');

		$helpers = $View->loaded;
		$this->assertTrue(is_object($helpers['html']));
		$this->assertTrue(is_object($helpers['form']));
		$this->assertTrue(is_object($helpers['form']->Html));
		$this->assertTrue(is_object($helpers['ajax']->Html));
		$this->assertTrue(is_object($helpers['testPluginHelper']->TestPluginOtherHelper));
	}

	function testRender() {
		$View = new TestView($this->PostsController);
		ob_start();
		$View->render('index');
		$result = ob_get_clean();
		//pr($result);
		$expected = '
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<title>
				CakePHP: the rapid development php framework:		Posts	</title>

			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<link rel="icon" href="favicon.ico" type="image/x-icon" />
			<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
			<link rel="stylesheet" type="text/css" href="css/cake.generic.css" />	</head>
		<body>
			<div id="container">
				<div id="header">
					<h1><a href="http://cakephp.org">CakePHP: the rapid development php framework</a></h1>
				</div>
				<div id="content">

					posts index
				</div>
				<div id="footer">
					<a href="http://www.cakephp.org/" target="_new"><img src="img/cake.power.gif" alt="CakePHP: the rapid development php framework" border="0" /></a>		</div>
			</div>
			</body>
		</html>
		';
		$result = str_replace(array("\t", "\r\n", "\n"), "", $result);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEqual($result, $expected);


		$this->PostsController->set('url', 'flash');
		$this->PostsController->set('message', 'yo what up');
		$this->PostsController->set('pause', 3);
		$this->PostsController->set('page_title', 'yo what up');

		$View = new TestView($this->PostsController);

		ob_start();
		$View->render(false, 'flash');
		$result = ob_get_clean();
		
		$expected = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		<title>yo what up</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style><!--
		P { text-align:center; font:bold 1.1em sans-serif }
		A { color:#444; text-decoration:none }
		A:HOVER { text-decoration: underline; color:#44E }
		--></style>
		</head>
		<body>
		<p><a href="flash">yo what up</a></p>
		</body>
		</html>';
 		$result = str_replace(array("\t", "\r\n", "\n"), "", $result);
		$expected =  str_replace(array("\t", "\r\n", "\n"), "", $expected);
		$this->assertEqual($result, $expected);
	}

	function tearDown() {
		unset($this->View);
		unset($this->PostsController);
		unset($this->Controller);

	}
}
?>