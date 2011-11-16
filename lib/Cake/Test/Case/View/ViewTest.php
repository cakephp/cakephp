<?php
/**
 * ViewTest file
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
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');
App::uses('Helper', 'View');
App::uses('Controller', 'Controller');
App::uses('CacheHelper', 'View/Helper');
App::uses('ErrorHandler', 'Error');


/**
 * ViewPostsController class
 *
 * @package       Cake.Test.Case.View
 */
class ViewPostsController extends Controller {

/**
 * name property
 *
 * @var string 'Posts'
 */
	public $name = 'Posts';

/**
 * uses property
 *
 * @var mixed null
 */
	public $uses = null;

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->set('testData', 'Some test data');
		$test2 = 'more data';
		$test3 = 'even more data';
		$this->set(compact('test2', 'test3'));
	}

/**
 * nocache_tags_with_element method
 *
 * @return void
 */
	public function nocache_multiple_element() {
		$this->set('foo', 'this is foo var');
		$this->set('bar', 'this is bar var');
	}
}

/**
 * TestView class
 *
 * @package       Cake.Test.Case.View
 */
class TestView extends View {

/**
 * getViewFileName method
 *
 * @param mixed $name
 * @return void
 */
	public function getViewFileName($name = null) {
		return $this->_getViewFileName($name);
	}

/**
 * getLayoutFileName method
 *
 * @param mixed $name
 * @return void
 */
	public function getLayoutFileName($name = null) {
		return $this->_getLayoutFileName($name);
	}

/**
 * paths method
 *
 * @param string $plugin
 * @param boolean $cached
 * @return void
 */
	public function paths($plugin = null, $cached = true) {
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
	public function render_($___viewFn, $___dataForView, $loadHelpers = true, $cached = false) {
		return $this->_render($___viewFn, $___dataForView, $loadHelpers, $cached);
	}

/**
 * Test only function to return instance scripts.
 *
 * @return array Scripts
 */
	public function scripts() {
		return $this->_scripts;
	}
}

/**
 * TestAfterHelper class
 *
 * @package       Cake.Test.Case.View
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
 * @return void
 */
	public function beforeLayout($viewFile) {
		$this->property = 'Valuation';
	}

/**
 * afterLayout method
 *
 * @return void
 */
	public function afterLayout($layoutFile) {
		$this->_View->output .= 'modified in the afterlife';
	}
}


/**
 * ViewTest class
 *
 * @package       Cake.Test.Case.View
 */
class ViewTest extends CakeTestCase {

/**
 * Fixtures used in this test.
 *
 * @var array
 */
	public $fixtures = array('core.user', 'core.post');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$request = $this->getMock('CakeRequest');
		$this->Controller = new Controller($request);
		$this->PostsController = new ViewPostsController($request);
		$this->PostsController->viewPath = 'Posts';
		$this->PostsController->index();
		$this->View = new View($this->PostsController);
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS
			)
		), true);
		CakePlugin::load(array('TestPlugin', 'TestPlugin', 'PluginJs'));
		Configure::write('debug', 2);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		CakePlugin::unload();
		unset($this->View);
		unset($this->PostsController);
		unset($this->Controller);
	}

/**
 * testPluginGetTemplate method
 *
 * @return void
 */
	public function testPluginGetTemplate() {
		$this->Controller->plugin = 'TestPlugin';
		$this->Controller->name = 'TestPlugin';
		$this->Controller->viewPath = 'Tests';
		$this->Controller->action = 'index';

		$View = new TestView($this->Controller);

		$expected = CakePlugin::path('TestPlugin') . 'View' . DS .'Tests' . DS .'index.ctp';
		$result = $View->getViewFileName('index');
		$this->assertEqual($expected, $result);

		$expected = CakePlugin::path('TestPlugin') . 'View' . DS . 'Layouts' . DS .'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($expected, $result);
	}

/**
 * test that plugin/$plugin_name is only appended to the paths it should be.
 *
 * @return void
 */
	public function testPluginPathGeneration() {
		$this->Controller->plugin = 'TestPlugin';
		$this->Controller->name = 'TestPlugin';
		$this->Controller->viewPath = 'Tests';
		$this->Controller->action = 'index';

		$View = new TestView($this->Controller);
		$paths = $View->paths();
		$expected = array_merge(App::path('View'), App::core('View'));
		$this->assertEqual($paths, $expected);

		$paths = $View->paths('TestPlugin');
		$pluginPath = CakePlugin::path('TestPlugin');
		$expected = array(
			CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Plugin' . DS . 'TestPlugin' . DS,
			$pluginPath . 'View' . DS,
			$pluginPath . 'views' . DS,
			$pluginPath . 'Lib' . DS . 'View' . DS,
			CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS,
			CAKE . 'View' . DS
		);
		$this->assertEqual($paths, $expected);
	}

/**
 * test that CamelCase plugins still find their view files.
 *
 * @return void
 */
	public function testCamelCasePluginGetTemplate() {
		$this->Controller->plugin = 'TestPlugin';
		$this->Controller->name = 'TestPlugin';
		$this->Controller->viewPath = 'Tests';
		$this->Controller->action = 'index';

		$View = new TestView($this->Controller);
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		));

		$pluginPath = CakePlugin::path('TestPlugin');
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS .'TestPlugin' . DS . 'View' . DS .'Tests' . DS .'index.ctp';
		$result = $View->getViewFileName('index');
		$this->assertEqual($expected, $result);

		$expected = $pluginPath. 'View' . DS . 'Layouts' . DS .'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($expected, $result);
	}

/**
 * testGetTemplate method
 *
 * @return void
 */
	public function testGetTemplate() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'Pages';
		$this->Controller->action = 'display';
		$this->Controller->params['pass'] = array('home');

		$View = new TestView($this->Controller);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS .'Pages' . DS .'home.ctp';
		$result = $View->getViewFileName('home');
		$this->assertEqual($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS .'Posts' . DS .'index.ctp';
		$result = $View->getViewFileName('/Posts/index');
		$this->assertEqual($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS .'Posts' . DS .'index.ctp';
		$result = $View->getViewFileName('../Posts/index');
		$this->assertEqual($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Layouts' . DS .'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($expected, $result);

		$View->layoutPath = 'rss';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Layouts' . DS . 'rss' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEqual($expected, $result);

		$View->layoutPath = 'Emails' . DS . 'html';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Layouts' . DS . 'Emails' . DS . 'html' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();

		$this->assertEqual($expected, $result);
	}

/**
 * testMissingView method
 *
 * @expectedException MissingViewException
 * @return void
 */
	public function testMissingView() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'Pages';
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
 * @return void
 */
	public function testMissingLayout() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Posts';
		$this->Controller->viewPath = 'Posts';
		$this->Controller->layout = 'whatever';

		$View = new TestView($this->Controller);
		ob_start();
		$result = $View->getLayoutFileName();
		$expected = str_replace(array("\t", "\r\n", "\n"), "", ob_get_clean());
	}

/**
 * testViewVars method
 *
 * @return void
 */
	public function testViewVars() {
		$this->assertEqual($this->View->viewVars, array('testData' => 'Some test data', 'test2' => 'more data', 'test3' => 'even more data'));
	}

/**
 * testUUIDGeneration method
 *
 * @return void
 */
	public function testUUIDGeneration() {
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
 * @return void
 */
	public function testAddInlineScripts() {
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
 * @return void
 */
	public function testElement() {
		$result = $this->View->element('test_element');
		$this->assertEqual($result, 'this is the test element');

		$result = $this->View->element('plugin_element', array(), array('plugin' => 'TestPlugin'));
		$this->assertEqual($result, 'this is the plugin element using params[plugin]');

		$result = $this->View->element('plugin_element', array(), array('plugin' => 'test_plugin'));
		$this->assertEqual($result, 'this is the plugin element using params[plugin]');

		$this->View->plugin = 'TestPlugin';
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
	public function testElementCallbacks() {
		$this->getMock('HtmlHelper', array(), array($this->View), 'ElementCallbackMockHtmlHelper');
		$this->View->helpers = array('ElementCallbackMockHtml');
		$this->View->loadHelpers();

		$this->View->ElementCallbackMockHtml->expects($this->at(0))->method('beforeRender');
		$this->View->ElementCallbackMockHtml->expects($this->at(1))->method('afterRender');

		$this->View->element('test_element', array(), array('callbacks' => true));
		$this->mockObjects[] = $this->View->ElementCallbackMockHtml;
	}
/**
 * test that additional element viewVars don't get overwritten with helpers.
 *
 * @return void
 */
	public function testElementParamsDontOverwriteHelpers() {
		$Controller = new ViewPostsController();
		$Controller->helpers = array('Form');

		$View = new View($Controller);
		$result = $View->element('type_check', array('form' => 'string'), array('callbacks' => true));
		$this->assertEqual('string', $result);

		$View->set('form', 'string');
		$result = $View->element('type_check', array(), array('callbacks' => true));
		$this->assertEqual('string', $result);
	}

/**
 * testElementCacheHelperNoCache method
 *
 * @return void
 */
	public function testElementCacheHelperNoCache() {
		$Controller = new ViewPostsController();
		$View = new TestView($Controller);
		$helpers = $View->loadHelpers();
		$result = $View->element('test_element', array('ram' => 'val', 'test' => array('foo', 'bar')));
		$this->assertEqual($result, 'this is the test element');
	}

/**
 * testElementCache method
 *
 * @return void
 */
	public function testElementCache() {
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

		$result = $View->element('test_element', array(), array('cache' => true));
		$expected = 'this is the test element';
		$this->assertEquals($expected, $result);

		$result = Cache::read('element__test_element_cache', 'test_view');
		$this->assertEquals($expected, $result);

		$result = $View->element('test_element', array('param' => 'one', 'foo' => 'two'), array('cache' => true));
		$this->assertEquals($expected, $result);

		$result = Cache::read('element__test_element_cache_param_foo', 'test_view');
		$this->assertEquals($expected, $result);

		$result = $View->element('test_element', array(
			'param' => 'one',
			'foo' => 'two'
		), array(
			'cache' => array('key' => 'custom_key')
		));
		$result = Cache::read('element_custom_key', 'test_view');
		$this->assertEquals($expected, $result);

		$View->elementCache = 'default';
		$result = $View->element('test_element', array(
			'param' => 'one',
			'foo' => 'two'
		), array(
			'cache' => array('config' => 'test_view'),
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
	public function test__get() {
		$View = new View($this->PostsController);
		$View->loadHelper('Html');
		$this->assertInstanceOf('HtmlHelper', $View->Html);
	}

/**
 * test that ctp is used as a fallback file extension for elements
 *
 * @return void
 */
	public function testElementCtpFallback() {
		$View = new TestView($this->PostsController);
		$View->ext = '.missing';
		$element = 'test_element';
		$expected = 'this is the test element';
		$result = $View->element($element);

		$this->assertEqual($expected, $result);
	}

/**
 * testLoadHelpers method
 *
 * @return void
 */
	public function testLoadHelpers() {
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
	public function testHelperCallbackTriggering() {
		$View = new View($this->PostsController);
		$View->helpers = array('Html', 'Session');
		$View->Helpers = $this->getMock('HelperCollection', array('trigger'), array($View));

		$View->Helpers->expects($this->at(0))->method('trigger')
			->with('beforeRender', $this->anything());
		$View->Helpers->expects($this->at(1))->method('trigger')
			->with('afterRender', $this->anything());

		$View->Helpers->expects($this->at(2))->method('trigger')
			->with('beforeLayout', $this->anything());
		$View->Helpers->expects($this->at(3))->method('trigger')
			->with('afterLayout', $this->anything());

		$View->render('index');
	}

/**
 * testBeforeLayout method
 *
 * @return void
 */
	public function testBeforeLayout() {
		$this->PostsController->helpers = array('Session', 'TestAfter', 'Html');
		$View = new View($this->PostsController);
		$View->render('index');
		$this->assertEqual($View->Helpers->TestAfter->property, 'Valuation');
	}

/**
 * testAfterLayout method
 *
 * @return void
 */
	public function testAfterLayout() {
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
 * @return void
 */
	public function testRenderLoadHelper() {
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
 * @return void
 */
	public function testRender() {
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
 * test that View::$view works
 *
 * @return void
 */
	public function testRenderUsingViewProperty() {
		$this->PostsController->view = 'cache_form';
		$View = new TestView($this->PostsController);

		$this->assertEquals('cache_form', $View->view);
		$result = $View->render();
		$this->assertRegExp('/Add User/', $result);
	}

/**
 * test that view vars can replace the local helper variables
 * and not overwrite the $this->Helper references
 *
 * @return void
 */
	public function testViewVarOverwritingLocalHelperVar() {
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
 * @return void
 */
	public function testViewFileName() {
		$View = new TestView($this->PostsController);

		$result = $View->getViewFileName('index');
		$this->assertPattern('/Posts(\/|\\\)index.ctp/', $result);

		$result = $View->getViewFileName('/Pages/home');
		$this->assertPattern('/Pages(\/|\\\)home.ctp/', $result);

		$result = $View->getViewFileName('../Elements/test_element');
		$this->assertPattern('/Elements(\/|\\\)test_element.ctp/', $result);

		$result = $View->getViewFileName('../Themed/TestTheme/Posts/index');
		$this->assertPattern('/Themed(\/|\\\)TestTheme(\/|\\\)Posts(\/|\\\)index.ctp/', $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS .'Posts' . DS .'index.ctp';
		$result = $View->getViewFileName('../Posts/index');
		$this->assertEqual($expected, $result);

	}

/**
 * testRenderCache method
 *
 * @return void
 */
	public function testRenderCache() {
		$this->skipIf(!is_writable(CACHE . 'views' . DS), 'CACHE/views dir is not writable, cannot test renderCache.');

		$view = 'test_view';
		$View = new View($this->PostsController);
		$path = CACHE . 'views' . DS . 'view_cache_' . $view;

		$cacheText = '<!--cachetime:' . time() . '-->some cacheText';
		$f = fopen($path, 'w+');
		fwrite($f, $cacheText);
		fclose($f);

		$result = $View->renderCache($path, '+1 second');
		$this->assertFalse($result);
		if (file_exists($path)) {
			unlink($path);
		}

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
	public function testRenderStrippingNoCacheTagsOnlyCacheHelper() {
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
	public function testRenderStrippingNoCacheTagsOnlyCacheCheck() {
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
 * @return void
 */

/* This is a new test case for a pending enhancement
	public function testRenderNocache() {
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
 * @return void
 */
	public function testSet() {
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
 * testBadExt method
 *
 * @expectedException MissingViewException
 * @return void
 */
	public function testBadExt() {
		$this->PostsController->action = 'something';
		$this->PostsController->ext = '.whatever';

		$View = new TestView($this->PostsController);
		$View->render('this_is_missing');
		$result = str_replace(array("\t", "\r\n", "\n"), "", ob_get_clean());
	}

/**
 * testAltExt method
 *
 * @return void
 */
	public function testAltExt() {
		$this->PostsController->ext = '.alt';
		$View = new TestView($this->PostsController);
		$result = $View->render('alt_ext', false);
		$this->assertEqual($result, 'alt ext');
	}

/**
 * testAltBadExt method
 *
 * @expectedException MissingViewException
 * @return void
 */
	public function testAltBadExt() {
		$View = new TestView($this->PostsController);
		$View->render('alt_ext');
		$result = str_replace(array("\t", "\r\n", "\n"), "", ob_get_clean());
	}
}
