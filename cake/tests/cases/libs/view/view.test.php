<?php
/**
 * ViewTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Core', array('View', 'Controller'));
App::import('Helper', 'Cache');
App::import('Core', array('ErrorHandler'));


/**
 * ViewPostsController class
 *
 * @package       cake.tests.cases.libs.view
 */
class ViewPostsController extends Controller {

/**
 * name property
 *
 * @var string 'Posts'
 * @access public
 */
	public $name = 'Posts';

/**
 * uses property
 *
 * @var mixed null
 * @access public
 */
	public $uses = null;

/**
 * index method
 *
 * @access public
 * @return void
 */
	function index() {
		$this->set('testData', 'Some test data');
		$test2 = 'more data';
		$test3 = 'even more data';
		$this->set(compact('test2', 'test3'));
	}

/**
 * nocache_tags_with_element method
 *
 * @access public
 * @return void
 */
	function nocache_multiple_element() {
		$this->set('foo', 'this is foo var');
		$this->set('bar', 'this is bar var');
	}
}

/**
 * TestView class
 *
 * @package       cake.tests.cases.libs.view
 */
class TestView extends View {

/**
 * getViewFileName method
 *
 * @param mixed $name
 * @access public
 * @return void
 */
	function getViewFileName($name = null) {
		return $this->_getViewFileName($name);
	}

/**
 * getLayoutFileName method
 *
 * @param mixed $name
 * @access public
 * @return void
 */
	function getLayoutFileName($name = null) {
		return $this->_getLayoutFileName($name);
	}

/**
 * paths method
 *
 * @param string $plugin
 * @param boolean $cached
 * @access public
 * @return void
 */
	function paths($plugin = null, $cached = true) {
		return $this->_paths($plugin, $cached);
	}

/**
 * _render wrapper for testing (temporary).
 *
 * @param string $___viewFn 
 * @param string $___dataForView 
 * @param string $loadHelpers 
 * @param string $cached 
 * @return void
 */
	function render_($___viewFn, $___dataForView, $loadHelpers = true, $cached = false) {
		return $this->_render($___viewFn, $___dataForView, $loadHelpers, $cached);
	}

/**
 * Test only function to return instance scripts.
 *
 * @return array Scripts
 */
	function scripts() {
		return $this->_scripts;
	}
}

/**
 * TestAfterHelper class
 *
 * @package       cake.tests.cases.libs.view
 */
class TestAfterHelper extends Helper {

/**
 * property property
 *
 * @var string ''
 */
	public $property = '';

/**
 * beforeLayout method
 *
 * @access public
 * @return void
 */
	function beforeLayout($viewFile) {
		$this->property = 'Valuation';
	}

/**
 * afterLayout method
 *
 * @access public
 * @return void
 */
	function afterLayout($layoutFile) {
		$this->_View->output .= 'modified in the afterlife';
	}
}


/**
 * ViewTest class
 *
 * @package       cake.tests.cases.libs
 */
class ViewTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setUp();

		$request = $this->getMock('CakeRequest');
		$this->Controller = new Controller($request);
		$this->PostsController = new ViewPostsController($request);
		$this->PostsController->viewPath = 'posts';
		$this->PostsController->index();
		$this->View = new View($this->PostsController);
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'views' => array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS,
				TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS
			)
		), true);

		Configure::write('debug', 2);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		unset($this->View);
		unset($this->PostsController);
		unset($this->Controller);
	}

/**
 * testPluginGetTemplate method
 *
 * @access public
 * @return void
 */
	function testPluginGetTemplate() {
		$this->Controller->plugin = 'test_plugin';
		$this->Controller->name = 'TestPlugin';
		$this->Controller->viewPath = 'tests';
		$this->Controller->action = 'index';

		$View = new TestView($this->Controller);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS .'test_plugin' . DS . 'views' . DS .'tests' . DS .'index.ctp';
		$result = $View->getViewFileName('index');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS .'test_plugin' . DS . 'views' . DS . 'layouts' . DS .'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($result, $expected);
	}

/**
 * test that plugin/$plugin_name is only appended to the paths it should be.
 *
 * @return void
 */
	function testPluginPathGeneration() {
		$this->Controller->plugin = 'test_plugin';
		$this->Controller->name = 'TestPlugin';
		$this->Controller->viewPath = 'tests';
		$this->Controller->action = 'index';

		$View = new TestView($this->Controller);
		$paths = $View->paths();
		$this->assertEqual($paths, App::path('views'));

		$paths = $View->paths('test_plugin');

		$expected = array(
			TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'plugins' . DS . 'test_plugin' . DS,
			TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' . DS . 'views' . DS,
			TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS,
			TEST_CAKE_CORE_INCLUDE_PATH . 'libs' . DS . 'view' . DS
		);
		$this->assertEqual($paths, $expected);
	}

/**
 * test that CamelCase plugins still find their view files.
 *
 * @return void
 */
	function testCamelCasePluginGetTemplate() {
		$this->Controller->plugin = 'TestPlugin';
		$this->Controller->name = 'TestPlugin';
		$this->Controller->viewPath = 'tests';
		$this->Controller->action = 'index';

		$View = new TestView($this->Controller);
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS .'test_plugin' . DS . 'views' . DS .'tests' . DS .'index.ctp';
		$result = $View->getViewFileName('index');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS .'test_plugin' . DS . 'views' . DS . 'layouts' . DS .'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($result, $expected);
	}

/**
 * testGetTemplate method
 *
 * @access public
 * @return void
 */
	function testGetTemplate() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'pages';
		$this->Controller->action = 'display';
		$this->Controller->params['pass'] = array('home');

		$View = new TestView($this->Controller);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS .'pages' . DS .'home.ctp';
		$result = $View->getViewFileName('home');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS .'posts' . DS .'index.ctp';
		$result = $View->getViewFileName('/posts/index');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS .'posts' . DS .'index.ctp';
		$result = $View->getViewFileName('../posts/index');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'layouts' . DS .'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($result, $expected);

		$View->layoutPath = 'rss';
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'layouts' . DS . 'rss' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($result, $expected);

		$View->layoutPath = 'email' . DS . 'html';
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'layouts' . DS . 'email' . DS . 'html' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();

		$this->assertEqual($result, $expected);
	}

/**
 * testMissingView method
 *
 * @expectedException MissingViewException
 * @access public
 * @return void
 */
	function testMissingView() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'pages';
		$this->Controller->action = 'display';
		$this->Controller->params['pass'] = array('home');

		$View = new TestView($this->Controller);
		ob_start();
		$result = $View->getViewFileName('does_not_exist');
	}

/**
 * testMissingLayout method
 *
 * @expectedException MissingLayoutException
 * @access public
 * @return void
 */
	function testMissingLayout() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Posts';
		$this->Controller->viewPath = 'posts';
		$this->Controller->layout = 'whatever';

		$View = new TestView($this->Controller);
		ob_start();
		$result = $View->getLayoutFileName();
		$expected = str_replace(array("\t", "\r\n", "\n"), "", ob_get_clean());
	}

/**
 * testViewVars method
 *
 * @access public
 * @return void
 */
	function testViewVars() {
		$this->assertEqual($this->View->viewVars, array('testData' => 'Some test data', 'test2' => 'more data', 'test3' => 'even more data'));
	}

/**
 * testUUIDGeneration method
 *
 * @access public
 * @return void
 */
	function testUUIDGeneration() {
		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, 'form5988016017');
		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, 'formc3dc6be854');
		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEqual($result, 'form28f92cc87f');
	}

/**
 * testAddInlineScripts method
 *
 * @access public
 * @return void
 */
	function testAddInlineScripts() {
		$View = new TestView($this->Controller);
		$View->addScript('prototype.js');
		$View->addScript('prototype.js');
		$this->assertEqual($View->scripts(), array('prototype.js'));

		$View->addScript('mainEvent', 'Event.observe(window, "load", function() { doSomething(); }, true);');
		$this->assertEqual($View->scripts(), array('prototype.js', 'mainEvent' => 'Event.observe(window, "load", function() { doSomething(); }, true);'));
	}

/**
 * testElement method
 *
 * @access public
 * @return void
 */
	function testElement() {
		$result = $this->View->element('test_element');
		$this->assertEqual($result, 'this is the test element');
		
		$result = $this->View->element('plugin_element', array('plugin' => 'test_plugin'));
		$this->assertEqual($result, 'this is the plugin element using params[plugin]');
		
		$this->View->plugin = 'test_plugin';
		$result = $this->View->element('test_plugin_element');
		$this->assertEqual($result, 'this is the test set using View::$plugin plugin element');

		$result = $this->View->element('non_existant_element');
		$this->assertPattern('/Not Found:/', $result);
		$this->assertPattern('/non_existant_element/', $result);
	}

/**
 * test that elements can have callbacks
 *
 */
	function testElementCallbacks() {
		$this->getMock('HtmlHelper', array(), array($this->View), 'ElementCallbackMockHtmlHelper');
		$this->View->helpers = array('ElementCallbackMockHtml');
		$this->View->loadHelpers();

		$this->View->ElementCallbackMockHtml->expects($this->at(0))->method('beforeRender');
		$this->View->ElementCallbackMockHtml->expects($this->at(1))->method('afterRender');

		$this->View->element('test_element', array(), true);
		$this->mockObjects[] = $this->View->ElementCallbackMockHtml;
	}
/**
 * test that additional element viewVars don't get overwritten with helpers.
 *
 * @return void
 */
	function testElementParamsDontOverwriteHelpers() {
		$Controller = new ViewPostsController();
		$Controller->helpers = array('Form');

		$View = new View($Controller);
		$result = $View->element('type_check', array('form' => 'string'), true);
		$this->assertEqual('string', $result);

		$View->set('form', 'string');
		$result = $View->element('type_check', array(), true);
		$this->assertEqual('string', $result);
	}

/**
 * testElementCacheHelperNoCache method
 *
 * @access public
 * @return void
 */
	function testElementCacheHelperNoCache() {
		$Controller = new ViewPostsController();
		$View = new TestView($Controller);
		$helpers = $View->loadHelpers();
		$result = $View->element('test_element', array('ram' => 'val', 'test' => array('foo', 'bar')));
		$this->assertEqual($result, 'this is the test element');
	}

/**
 * testElementCache method
 *
 * @access public
 * @return void
 */
	function testElementCache() {
		Cache::drop('test_view');
		Cache::config('test_view', array(
			'engine' => 'File',
			'duration' => '+1 day',
			'path' => CACHE . 'views' . DS,
			'prefix' => ''
		));
		Cache::clear('test_view');

		$View = new TestView($this->PostsController);
		$View->elementCache = 'test_view';

		$result = $View->element('test_element', array('cache' => true));
		$expected = 'this is the test element';
		$this->assertEquals($expected, $result);

		$result = Cache::read('element__test_element_cache', 'test_view');
		$this->assertEquals($expected, $result);
		
		$result = $View->element('test_element', array('cache' => true, 'param' => 'one', 'foo' => 'two'));
		$this->assertEquals($expected, $result);

		$result = Cache::read('element__test_element_cache_param_foo', 'test_view');
		$this->assertEquals($expected, $result);
		
		$result = $View->element('test_element', array(
			'cache' => array('key' => 'custom_key'),
			'param' => 'one',
			'foo' => 'two'
		));
		$result = Cache::read('element_custom_key', 'test_view');
		$this->assertEquals($expected, $result);
		
		$View->elementCache = 'default';
		$result = $View->element('test_element', array(
			'cache' => array('config' => 'test_view'),
			'param' => 'one',
			'foo' => 'two'
		));
		$result = Cache::read('element__test_element_cache_param_foo', 'test_view');
		$this->assertEquals($expected, $result);

		Cache::drop('test_view');
	}

/**
 * test __get allowing access to helpers.
 *
 * @return void
 */
	function test__get() {
		$View = new View($this->PostsController);
		$View->loadHelper('Html');
		$this->assertInstanceOf('HtmlHelper', $View->Html);
	}

/**
 * testLoadHelpers method
 *
 * @access public
 * @return void
 */
	function testLoadHelpers() {
		$View = new View($this->PostsController);

		$View->helpers = array('Html', 'Form');
		$View->loadHelpers();

		$this->assertInstanceOf('HtmlHelper', $View->Html, 'Object type is wrong.');
		$this->assertInstanceOf('FormHelper', $View->Form, 'Object type is wrong.');
	}

/**
 * test the correct triggering of helper callbacks
 *
 * @return void
 */
	function testHelperCallbackTriggering() {
		$View = new View($this->PostsController);
		$View->helpers = array('Html', 'Session');
		$View->Helpers = $this->getMock('HelperCollection', array('trigger'), array($View));

		$View->Helpers->expects($this->at(0))->method('trigger')
			->with('beforeRender', new PHPUnit_Framework_Constraint_IsAnything());
		$View->Helpers->expects($this->at(1))->method('trigger')
			->with('afterRender', new PHPUnit_Framework_Constraint_IsAnything());

		$View->Helpers->expects($this->at(2))->method('trigger')
			->with('beforeLayout', new PHPUnit_Framework_Constraint_IsAnything());
		$View->Helpers->expects($this->at(3))->method('trigger')
			->with('afterLayout', new PHPUnit_Framework_Constraint_IsAnything());

		$View->render('index');
	}

/**
 * testBeforeLayout method
 *
 * @access public
 * @return void
 */
	function testBeforeLayout() {
		$this->PostsController->helpers = array('Session', 'TestAfter', 'Html');
		$View = new View($this->PostsController);
		$View->render('index');
		$this->assertEqual($View->Helpers->TestAfter->property, 'Valuation');
	}

/**
 * testAfterLayout method
 *
 * @access public
 * @return void
 */
	function testAfterLayout() {
		$this->PostsController->helpers = array('Session', 'TestAfter', 'Html');
		$this->PostsController->set('variable', 'values');

		$View = new View($this->PostsController);
		ClassRegistry::addObject('afterView', $View);

		$content = 'This is my view output';
		$result = $View->renderLayout($content, 'default');
		$this->assertPattern('/modified in the afterlife/', $result);
		$this->assertPattern('/This is my view output/', $result);
	}

/**
 * testRenderLoadHelper method
 *
 * @access public
 * @return void
 */
	function testRenderLoadHelper() {
		$this->PostsController->helpers = array('Session', 'Html', 'Form', 'Number');
		$View = new TestView($this->PostsController);

		$result = $View->render('index', false);
		$this->assertEqual($result, 'posts index');

		$attached = $View->Helpers->attached();
		$this->assertEquals($attached, array('Session', 'Html', 'Form', 'Number'));

		$this->PostsController->helpers = array('Html', 'Form', 'Number', 'TestPlugin.PluggedHelper');
		$View = new TestView($this->PostsController);

		$result = $View->render('index', false);
		$this->assertEqual($result, 'posts index');

		$attached = $View->Helpers->attached();
		$expected = array('Html', 'Form', 'Number', 'PluggedHelper');
		$this->assertEquals($expected, $attached, 'Attached helpers are wrong.');
	}

/**
 * testRender method
 *
 * @access public
 * @return void
 */
	function testRender() {
		$View = new TestView($this->PostsController);
		$result = str_replace(array("\t", "\r\n", "\n"), "", $View->render('index'));

		$this->assertPattern("/<meta http-equiv=\"Content-Type\" content=\"text\/html; charset=utf-8\" \/><title>/", $result);
		$this->assertPattern("/<div id=\"content\">posts index<\/div>/", $result);
		$this->assertPattern("/<div id=\"content\">posts index<\/div>/", $result);
		
		$this->assertTrue(isset($View->viewVars['content_for_layout']), 'content_for_layout should be a view var');
		$this->assertTrue(isset($View->viewVars['scripts_for_layout']), 'scripts_for_layout should be a view var');

		$this->PostsController->set('url', 'flash');
		$this->PostsController->set('message', 'yo what up');
		$this->PostsController->set('pause', 3);
		$this->PostsController->set('page_title', 'yo what up');

		$View = new TestView($this->PostsController);
		$result = str_replace(array("\t", "\r\n", "\n"), "", $View->render(false, 'flash'));

		$this->assertPattern("/<title>yo what up<\/title>/", $result);
		$this->assertPattern("/<p><a href=\"flash\">yo what up<\/a><\/p>/", $result);

		$this->assertTrue($View->render(false, 'flash'));

		$this->PostsController->helpers = array('Session', 'Cache', 'Html');
		$this->PostsController->constructClasses();
		$this->PostsController->cacheAction = array('index' => 3600);
		$this->PostsController->request->params['action'] = 'index';
		Configure::write('Cache.check', true);

		$View = new TestView($this->PostsController);
		$result = str_replace(array("\t", "\r\n", "\n"), "", $View->render('index'));

		$this->assertPattern("/<meta http-equiv=\"Content-Type\" content=\"text\/html; charset=utf-8\" \/><title>/", $result);
		$this->assertPattern("/<div id=\"content\">posts index<\/div>/", $result);
		$this->assertPattern("/<div id=\"content\">posts index<\/div>/", $result);
	}

/**
 * test that view vars can replace the local helper variables
 * and not overwrite the $this->Helper references
 *
 * @return void
 */
	function testViewVarOverwritingLocalHelperVar() {
		$Controller = new ViewPostsController();
		$Controller->helpers = array('Session', 'Html');
		$Controller->set('html', 'I am some test html');
		$View = new View($Controller);
		$result = $View->render('helper_overwrite', false);

		$this->assertPattern('/I am some test html/', $result);
		$this->assertPattern('/Test link/', $result);
	}

/**
 * testGetViewFileName method
 *
 * @access public
 * @return void
 */
	function testViewFileName() {
		$View = new TestView($this->PostsController);

		$result = $View->getViewFileName('index');
		$this->assertPattern('/posts(\/|\\\)index.ctp/', $result);

		$result = $View->getViewFileName('/pages/home');
		$this->assertPattern('/pages(\/|\\\)home.ctp/', $result);

		$result = $View->getViewFileName('../elements/test_element');
		$this->assertPattern('/elements(\/|\\\)test_element.ctp/', $result);

		$result = $View->getViewFileName('../themed/test_theme/posts/index');
		$this->assertPattern('/themed(\/|\\\)test_theme(\/|\\\)posts(\/|\\\)index.ctp/', $result);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS .'posts' . DS .'index.ctp';
		$result = $View->getViewFileName('../posts/index');
		$this->assertEqual($result, $expected);

	}

/**
 * testRenderCache method
 *
 * @access public
 * @return void
 */
	function testRenderCache() {
		$writable = is_writable(CACHE . 'views' . DS);
		if ($this->skipIf(!$writable, 'CACHE/views dir is not writable, cannot test renderCache. %s')) {
			return;
		}
		$view = 'test_view';
		$View = new View($this->PostsController);
		$path = CACHE . 'views' . DS . 'view_cache_' . $view;

		$cacheText = '<!--cachetime:' . time() . '-->some cacheText';
		$f = fopen($path, 'w+');
		fwrite($f, $cacheText);
		fclose($f);

		$result = $View->renderCache($path, '+1 second');
		$this->assertFalse($result);
		@unlink($path);

		$cacheText = '<!--cachetime:' . (time() + 10) . '-->some cacheText';
		$f = fopen($path, 'w+');
		fwrite($f, $cacheText);
		fclose($f);
		ob_start();
		$View->renderCache($path, '+1 second');
		$result = ob_get_clean();

		$expected = 'some cacheText';
		$this->assertPattern('/^some cacheText/', $result);

		@unlink($path);
	}

/**
 * Test that render() will remove the cake:nocache tags when only the cachehelper is present.
 *
 * @return void
 */
	function testRenderStrippingNoCacheTagsOnlyCacheHelper() {
		Configure::write('Cache.check', false);
		$View = new View($this->PostsController);
		$View->set(array('superman' => 'clark', 'variable' => 'var'));
		$View->helpers = array('Html', 'Form', 'Cache');
		$View->layout = 'cache_layout';
		$result = $View->render('index');
		$this->assertNoPattern('/cake:nocache/', $result);
	}

/**
 * Test that render() will remove the cake:nocache tags when only the Cache.check is true.
 *
 * @return void
 */
	function testRenderStrippingNoCacheTagsOnlyCacheCheck() {
		Configure::write('Cache.check', true);
		$View = new View($this->PostsController);
		$View->set(array('superman' => 'clark', 'variable' => 'var'));
		$View->helpers = array('Html', 'Form');
		$View->layout = 'cache_layout';
		$result = $View->render('index');
		$this->assertNoPattern('/cake:nocache/', $result);
	}

/**
 * testRenderNocache method
 *
 * @access public
 * @return void
 */

/* This is a new test case for a pending enhancement
	function testRenderNocache() {
		$this->PostsController->helpers = array('Cache', 'Html');
		$this->PostsController->constructClasses();
		$this->PostsController->cacheAction = 21600;
		$this->PostsController->here = '/posts/nocache_multiple_element';
		$this->PostsController->action = 'nocache_multiple_element';
		$this->PostsController->nocache_multiple_element();
		Configure::write('Cache.check', true);
		Configure::write('Cache.disable', false);

		$filename = CACHE . 'views' . DS . 'posts_nocache_multiple_element.php';

		$View = new TestView($this->PostsController);
		$View->render();

		ob_start();
		$View->renderCache($filename, getMicroTime());
		$result = ob_get_clean();
		@unlink($filename);

		$this->assertPattern('/php echo \$foo;/', $result);
		$this->assertPattern('/php echo \$bar;/', $result);
		$this->assertPattern('/php \$barfoo = \'in sub2\';/', $result);
		$this->assertPattern('/php echo \$barfoo;/', $result);
		$this->assertPattern('/printing: "in sub2"/', $result);
		$this->assertPattern('/php \$foobar = \'in sub1\';/', $result);
		$this->assertPattern('/php echo \$foobar;/', $result);
		$this->assertPattern('/printing: "in sub1"/', $result);
	}
*/

/**
 * testSet method
 *
 * @access public
 * @return void
 */
	function testSet() {
		$View = new TestView($this->PostsController);
		$View->viewVars = array();
		$View->set('somekey', 'someValue');
		$this->assertIdentical($View->viewVars, array('somekey' => 'someValue'));
		$this->assertIdentical($View->getVars(), array('somekey'));

		$View->viewVars = array();
		$keys = array('key1', 'key2');
		$values = array('value1', 'value2');
		$View->set($keys, $values);
		$this->assertIdentical($View->viewVars, array('key1' => 'value1', 'key2' => 'value2'));
		$this->assertIdentical($View->getVars(), array('key1', 'key2'));
		$this->assertIdentical($View->getVar('key1'), 'value1');
		$this->assertNull($View->getVar('key3'));

		$View->set(array('key3' => 'value3'));
		$this->assertIdentical($View->getVar('key3'), 'value3');
		
		$View->viewVars = array();
		$View->set(array(3 => 'three', 4 => 'four'));
		$View->set(array(1 => 'one', 2 => 'two'));
		$expected = array(3 => 'three', 4 => 'four', 1 => 'one', 2 => 'two');
		$this->assertEqual($View->viewVars, $expected);
	}

/**
 * testEntityReference method
 *
 * @access public
 * @return void
 */
	function testEntityReference() {
		$View = new TestView($this->PostsController);
		$View->model = 'Post';
		$View->field = 'title';
		$this->assertEqual($View->entity(), array('Post', 'title'));

		$View->association = 'Comment';
		$View->field = 'user_id';
		$this->assertEqual($View->entity(), array('Comment', 'user_id'));
		
		$View->model = 0;
		$View->association = null;
		$View->field = 'Node';
		$View->fieldSuffix = 'title';
		$View->entityPath = '0.Node.title';
		$expected = array(0, 'Node', 'title');
		$this->assertEqual($View->entity(), $expected);
		
		$View->model = 'HelperTestTag';
		$View->field = 'HelperTestTag';
		$View->modelId = null;
		$View->association = null;
		$View->fieldSuffix = null;
		$View->entityPath = 'HelperTestTag';
		$expected = array('HelperTestTag', 'HelperTestTag');
		$this->assertEqual($View->entity(), $expected);
	}

/**
 * testBadExt method
 *
 * @expectedException MissingViewException
 * @access public
 * @return void
 */
	function testBadExt() {
		$this->PostsController->action = 'something';
		$this->PostsController->ext = '.whatever';

		$View = new TestView($this->PostsController);
		$View->render('this_is_missing');
		$result = str_replace(array("\t", "\r\n", "\n"), "", ob_get_clean());
	}
}
