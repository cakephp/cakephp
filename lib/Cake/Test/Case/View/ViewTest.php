<?php
/**
 * ViewTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('View', 'View');
App::uses('Helper', 'View');
App::uses('Controller', 'Controller');
App::uses('CacheHelper', 'View/Helper');
App::uses('HtmlHelper', 'View/Helper');
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
		$this->set(array(
			'testData' => 'Some test data',
			'test2' => 'more data',
			'test3' => 'even more data',
		));
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
 * ThemePostsController class
 *
 * @package       Cake.Test.Case.View
 */
class ThemePostsController extends Controller {

/**
 * name property
 *
 * @var string 'ThemePosts'
 */
	public $name = 'ThemePosts';

	public $theme = null;

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

}

/**
 * TestThemeView class
 *
 * @package       Cake.Test.Case.View
 */
class TestThemeView extends View {

/**
 * renderElement method
 *
 * @param string $name
 * @param array $params
 * @return void
 */
	public function renderElement($name, $params = array()) {
		return $name;
	}

/**
 * getViewFileName method
 *
 * @param string $name
 * @return void
 */
	public function getViewFileName($name = null) {
		return $this->_getViewFileName($name);
	}

/**
 * getLayoutFileName method
 *
 * @param string $name
 * @return void
 */
	public function getLayoutFileName($name = null) {
		return $this->_getLayoutFileName($name);
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
 * @param string $name
 * @return void
 */
	public function getViewFileName($name = null) {
		return $this->_getViewFileName($name);
	}

/**
 * getLayoutFileName method
 *
 * @param string $name
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

		$themeRequest = new CakeRequest('posts/index');
		$this->ThemeController = new Controller($themeRequest);
		$this->ThemePostsController = new ThemePostsController($themeRequest);
		$this->ThemePostsController->viewPath = 'posts';
		$this->ThemePostsController->index();
		$this->ThemeView = new View($this->ThemePostsController);

		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		), App::RESET);
		App::objects('plugins', null, false);

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
		unset($this->ThemeView);
		unset($this->ThemePostsController);
		unset($this->ThemeController);
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

		$ThemeView = new TestThemeView($this->Controller);
		$ThemeView->theme = 'test_theme';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Pages' . DS . 'home.ctp';
		$result = $ThemeView->getViewFileName('home');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Posts' . DS . 'index.ctp';
		$result = $ThemeView->getViewFileName('/Posts/index');
		$this->assertEquals($expected, $result);

		$ThemeView->theme = 'TestTheme';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Layouts' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertEquals($expected, $result);

		$ThemeView->layoutPath = 'rss';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Layouts' . DS . 'rss' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertEquals($expected, $result);

		$ThemeView->layoutPath = 'Emails' . DS . 'html';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Layouts' . DS . 'Emails' . DS . 'html' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertEquals($expected, $result);
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

		$expected = CakePlugin::path('TestPlugin') . 'View' . DS . 'Tests' . DS . 'index.ctp';
		$result = $View->getViewFileName('index');
		$this->assertEquals($expected, $result);

		$expected = CakePlugin::path('TestPlugin') . 'View' . DS . 'Layouts' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEquals($expected, $result);
	}

/**
 * testPluginGetTemplate method
 *
 * @return void
 */
	public function testPluginThemedGetTemplate() {
		$this->Controller->plugin = 'TestPlugin';
		$this->Controller->name = 'TestPlugin';
		$this->Controller->viewPath = 'Tests';
		$this->Controller->action = 'index';
		$this->Controller->theme = 'TestTheme';

		$ThemeView = new TestThemeView($this->Controller);
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'Tests' . DS . 'index.ctp';
		$result = $ThemeView->getViewFileName('index');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'Layouts' . DS . 'plugin_default.ctp';
		$result = $ThemeView->getLayoutFileName('plugin_default');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Layouts' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName('default');
		$this->assertEquals($expected, $result);
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
		$expected = array_merge(App::path('View'), App::core('View'), App::core('Console/Templates/skel/View'));
		$this->assertEquals($expected, $paths);

		$paths = $View->paths('TestPlugin');
		$pluginPath = CakePlugin::path('TestPlugin');
		$expected = array(
			CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Plugin' . DS . 'TestPlugin' . DS,
			$pluginPath . 'View' . DS,
			CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS,
			CAKE . 'View' . DS,
			CAKE . 'Console' . DS . 'Templates' . DS . 'skel' . DS . 'View' . DS
		);
		$this->assertEquals($expected, $paths);
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
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));

		$pluginPath = CakePlugin::path('TestPlugin');
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'View' . DS . 'Tests' . DS . 'index.ctp';
		$result = $View->getViewFileName('index');
		$this->assertEquals($expected, $result);

		$expected = $pluginPath . 'View' . DS . 'Layouts' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEquals($expected, $result);
	}

/**
 * testGetTemplate method
 *
 * @return void
 */
	public function testGetViewFileNames() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'Pages';
		$this->Controller->action = 'display';
		$this->Controller->params['pass'] = array('home');

		$View = new TestView($this->Controller);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Pages' . DS . 'home.ctp';
		$result = $View->getViewFileName('home');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Posts' . DS . 'index.ctp';
		$result = $View->getViewFileName('/Posts/index');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Posts' . DS . 'index.ctp';
		$result = $View->getViewFileName('../Posts/index');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Pages' . DS . 'page.home.ctp';
		$result = $View->getViewFileName('page.home');
		$this->assertEquals($expected, $result, 'Should not ruin files with dots.');

		CakePlugin::load('TestPlugin');
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Pages' . DS . 'home.ctp';
		$result = $View->getViewFileName('TestPlugin.home');
		$this->assertEquals($expected, $result, 'Plugin is missing the view, cascade to app.');

		$View->viewPath = 'Tests';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'View' . DS . 'Tests' . DS . 'index.ctp';
		$result = $View->getViewFileName('TestPlugin.index');
		$this->assertEquals($expected, $result);
	}

/**
 * Test getting layout filenames
 *
 * @return void
 */
	public function testGetLayoutFileName() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'Pages';
		$this->Controller->action = 'display';

		$View = new TestView($this->Controller);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Layouts' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEquals($expected, $result);

		$View->layoutPath = 'rss';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Layouts' . DS . 'rss' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEquals($expected, $result);

		$View->layoutPath = 'Emails' . DS . 'html';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Layouts' . DS . 'Emails' . DS . 'html' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEquals($expected, $result);
	}

/**
 * Test getting layout filenames for plugins.
 *
 * @return void
 */
	public function testGetLayoutFileNamePlugin() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'Pages';
		$this->Controller->action = 'display';

		$View = new TestView($this->Controller);
		CakePlugin::load('TestPlugin');

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'View' . DS . 'Layouts' . DS . 'default.ctp';
		$result = $View->getLayoutFileName('TestPlugin.default');
		$this->assertEquals($expected, $result);

		$View->plugin = 'TestPlugin';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'View' . DS . 'Layouts' . DS . 'default.ctp';
		$result = $View->getLayoutFileName('default');
		$this->assertEquals($expected, $result);
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

		$this->ThemeController->plugin = null;
		$this->ThemeController->name = 'Pages';
		$this->ThemeController->viewPath = 'Pages';
		$this->ThemeController->action = 'display';
		$this->ThemeController->theme = 'my_theme';

		$this->ThemeController->params['pass'] = array('home');

		$View = new TestThemeView($this->ThemeController);
		$View->getViewFileName('does_not_exist');
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
		$expected = ob_get_clean();

		$this->ThemeController->plugin = null;
		$this->ThemeController->name = 'Posts';
		$this->ThemeController->viewPath = 'posts';
		$this->ThemeController->layout = 'whatever';
		$this->ThemeController->theme = 'my_theme';

		$View = new TestThemeView($this->ThemeController);
		$result = $View->getLayoutFileName();
	}

/**
 * testViewVars method
 *
 * @return void
 */
	public function testViewVars() {
		$this->assertEquals(array('testData' => 'Some test data', 'test2' => 'more data', 'test3' => 'even more data'), $this->View->viewVars);
	}

/**
 * testUUIDGeneration method
 *
 * @return void
 */
	public function testUUIDGeneration() {
		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEquals('form5988016017', $result);
		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEquals('formc3dc6be854', $result);
		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEquals('form28f92cc87f', $result);
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
		$this->assertEquals(array('prototype.js'), $View->scripts());

		$View->addScript('mainEvent', 'Event.observe(window, "load", function() { doSomething(); }, true);');
		$this->assertEquals(array('prototype.js', 'mainEvent' => 'Event.observe(window, "load", function() { doSomething(); }, true);'), $View->scripts());
	}

/**
 * testElement method
 *
 * @return void
 */
	public function testElement() {
		$result = $this->View->element('test_element');
		$this->assertEquals('this is the test element', $result);

		$result = $this->View->element('plugin_element', array(), array('plugin' => 'TestPlugin'));
		$this->assertEquals('this is the plugin element using params[plugin]', $result);

		$result = $this->View->element('plugin_element', array(), array('plugin' => 'test_plugin'));
		$this->assertEquals('this is the plugin element using params[plugin]', $result);

		$result = $this->View->element('TestPlugin.plugin_element');
		$this->assertEquals('this is the plugin element using params[plugin]', $result);

		$result = $this->View->element('test_plugin.plugin_element');
		$this->assertRegExp('/Not Found:/', $result);
		$this->assertRegExp('/test_plugin.plugin_element/', $result);

		$this->View->plugin = 'TestPlugin';
		$result = $this->View->element('test_plugin_element');
		$this->assertEquals('this is the test set using View::$plugin plugin element', $result);

		$result = $this->View->element('non_existent_element');
		$this->assertRegExp('/Not Found:/', $result);
		$this->assertRegExp('/non_existent_element/', $result);

		$result = $this->View->element('TestPlugin.plugin_element', array(), array('plugin' => 'test_plugin'));
		$this->assertRegExp('/Not Found:/', $result);
		$this->assertRegExp('/TestPlugin.plugin_element/', $result);
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
		$this->assertEquals('string', $result);

		$View->set('form', 'string');
		$result = $View->element('type_check', array(), array('callbacks' => true));
		$this->assertEquals('string', $result);
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
		$this->assertEquals('this is the test element', $result);
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
		Cache::clear(true, 'test_view');

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

		Cache::clear(true, 'test_view');
		Cache::drop('test_view');
	}

/**
 * test __get allowing access to helpers.
 *
 * @return void
 */
	public function testMagicGet() {
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

		$this->assertEquals($expected, $result);
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
 * test lazy loading helpers
 *
 * @return void
 */
	public function testLazyLoadHelpers() {
		$View = new View($this->PostsController);

		$View->helpers = array();
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
		$View->helpers = array();
		$View->Helpers = $this->getMock('HelperCollection', array('trigger'), array($View));

		$View->Helpers->expects($this->at(0))->method('trigger')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('CakeEvent'),
					$this->attributeEqualTo('_name', 'View.beforeRender'),
					$this->attributeEqualTo('_subject', $View)
				)
			);
		$View->Helpers->expects($this->at(1))->method('trigger')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('CakeEvent'),
					$this->attributeEqualTo('_name', 'View.beforeRenderFile'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$View->Helpers->expects($this->at(2))->method('trigger')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('CakeEvent'),
					$this->attributeEqualTo('_name', 'View.afterRenderFile'),
					$this->attributeEqualTo('_subject', $View)
				)
			);
		$View->Helpers->expects($this->at(3))->method('trigger')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('CakeEvent'),
					$this->attributeEqualTo('_name', 'View.afterRender'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$View->Helpers->expects($this->at(4))->method('trigger')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('CakeEvent'),
					$this->attributeEqualTo('_name', 'View.beforeLayout'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$View->Helpers->expects($this->at(5))->method('trigger')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('CakeEvent'),
					$this->attributeEqualTo('_name', 'View.beforeRenderFile'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$View->Helpers->expects($this->at(6))->method('trigger')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('CakeEvent'),
					$this->attributeEqualTo('_name', 'View.afterRenderFile'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$View->Helpers->expects($this->at(7))->method('trigger')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('CakeEvent'),
					$this->attributeEqualTo('_name', 'View.afterLayout'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

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
		$this->assertEquals('Valuation', $View->Helpers->TestAfter->property);
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
		$this->assertRegExp('/modified in the afterlife/', $result);
		$this->assertRegExp('/This is my view output/', $result);
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
		$this->assertEquals('posts index', $result);

		$attached = $View->Helpers->attached();
		$this->assertEquals(array('Session', 'Html', 'Form', 'Number'), $attached);

		$this->PostsController->helpers = array('Html', 'Form', 'Number', 'TestPlugin.PluggedHelper');
		$View = new TestView($this->PostsController);

		$result = $View->render('index', false);
		$this->assertEquals('posts index', $result);

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
		$result = $View->render('index');

		$this->assertRegExp("/<meta http-equiv=\"Content-Type\" content=\"text\/html; charset=utf-8\" \/>\s*<title>/", $result);
		$this->assertRegExp("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
		$this->assertRegExp("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);

		$this->assertTrue(isset($View->viewVars['content_for_layout']), 'content_for_layout should be a view var');
		$this->assertTrue(isset($View->viewVars['scripts_for_layout']), 'scripts_for_layout should be a view var');

		$this->PostsController->set('url', 'flash');
		$this->PostsController->set('message', 'yo what up');
		$this->PostsController->set('pause', 3);
		$this->PostsController->set('page_title', 'yo what up');

		$View = new TestView($this->PostsController);
		$result = $View->render(false, 'flash');

		$this->assertRegExp("/<title>yo what up<\/title>/", $result);
		$this->assertRegExp("/<p><a href=\"flash\">yo what up<\/a><\/p>/", $result);

		$this->assertTrue($View->render(false, 'flash'));

		$this->PostsController->helpers = array('Session', 'Cache', 'Html');
		$this->PostsController->constructClasses();
		$this->PostsController->cacheAction = array('index' => 3600);
		$this->PostsController->request->params['action'] = 'index';
		Configure::write('Cache.check', true);

		$View = new TestView($this->PostsController);
		$result = $View->render('index');

		$this->assertRegExp("/<meta http-equiv=\"Content-Type\" content=\"text\/html; charset=utf-8\" \/>\s*<title>/", $result);
		$this->assertRegExp("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
		$this->assertRegExp("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
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
 * Test render()ing a file in a subdir from a custom viewPath
 * in a plugin.
 *
 * @return void
 */
	public function testGetViewFileNameSubdirWithPluginAndViewPath() {
		$this->PostsController->plugin = 'TestPlugin';
		$this->PostsController->viewPath = 'Elements';
		$this->PostsController->name = 'Posts';
		$View = new TestView($this->PostsController);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' .
			DS . 'View' . DS . 'Elements' . DS . 'sub_dir' . DS . 'sub_element.ctp';
		$this->assertEquals($expected, $View->getViewFileName('sub_dir/sub_element'));
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

		$this->assertRegExp('/I am some test html/', $result);
		$this->assertRegExp('/Test link/', $result);
	}

/**
 * testGetViewFileName method
 *
 * @return void
 */
	public function testViewFileName() {
		$View = new TestView($this->PostsController);

		$result = $View->getViewFileName('index');
		$this->assertRegExp('/Posts(\/|\\\)index.ctp/', $result);

		$result = $View->getViewFileName('TestPlugin.index');
		$this->assertRegExp('/Posts(\/|\\\)index.ctp/', $result);

		$result = $View->getViewFileName('/Pages/home');
		$this->assertRegExp('/Pages(\/|\\\)home.ctp/', $result);

		$result = $View->getViewFileName('../Elements/test_element');
		$this->assertRegExp('/Elements(\/|\\\)test_element.ctp/', $result);

		$result = $View->getViewFileName('../Themed/TestTheme/Posts/index');
		$this->assertRegExp('/Themed(\/|\\\)TestTheme(\/|\\\)Posts(\/|\\\)index.ctp/', $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Posts' . DS . 'index.ctp';
		$result = $View->getViewFileName('../Posts/index');
		$this->assertEquals($expected, $result);
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
		$result = $View->renderCache($path, '+1 second');

		$this->assertRegExp('/^some cacheText/', $result);

		if (file_exists($path)) {
			unlink($path);
		}
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
		$this->assertNotRegExp('/cake:nocache/', $result);
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
		$this->assertNotRegExp('/cake:nocache/', $result);
	}

/**
 * testSet method
 *
 * @return void
 */
	public function testSet() {
		$View = new TestView($this->PostsController);
		$View->viewVars = array();
		$View->set('somekey', 'someValue');
		$this->assertSame($View->viewVars, array('somekey' => 'someValue'));
		$this->assertSame($View->getVars(), array('somekey'));

		$View->viewVars = array();
		$keys = array('key1', 'key2');
		$values = array('value1', 'value2');
		$View->set($keys, $values);
		$this->assertSame($View->viewVars, array('key1' => 'value1', 'key2' => 'value2'));
		$this->assertSame($View->getVars(), array('key1', 'key2'));
		$this->assertSame($View->getVar('key1'), 'value1');
		$this->assertNull($View->getVar('key3'));

		$View->set(array('key3' => 'value3'));
		$this->assertSame($View->getVar('key3'), 'value3');

		$View->viewVars = array();
		$View->set(array(3 => 'three', 4 => 'four'));
		$View->set(array(1 => 'one', 2 => 'two'));
		$expected = array(3 => 'three', 4 => 'four', 1 => 'one', 2 => 'two');
		$this->assertEquals($expected, $View->viewVars);
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
		$this->assertEquals('alt ext', $result);
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
	}

/**
 * Test creating a block with capturing output.
 *
 * @return void
 */
	public function testBlockCapture() {
		$this->View->start('test');
		echo 'Block content';
		$this->View->end();

		$result = $this->View->fetch('test');
		$this->assertEquals('Block content', $result);
	}

/**
 * Test appending to a block with capturing output.
 *
 * @return void
 */
	public function testBlockCaptureAppend() {
		$this->View->start('test');
		echo 'Block';
		$this->View->end();

		$this->View->append('test');
		echo ' content';
		$this->View->end();

		$result = $this->View->fetch('test');
		$this->assertEquals('Block content', $result);
	}

/**
 * Test setting a block's content.
 *
 * @return void
 */
	public function testBlockSet() {
		$this->View->assign('test', 'Block content');
		$result = $this->View->fetch('test');
		$this->assertEquals('Block content', $result);
	}

/**
 * Test appending to a block with append.
 *
 * @return void
 */
	public function testBlockAppend() {
		$this->View->assign('test', 'Block');
		$this->View->append('test', ' content');

		$result = $this->View->fetch('test');
		$this->assertEquals('Block content', $result);
	}

/**
 * You should be able to append to undefined blocks.
 *
 * @return void
 */
	public function testBlockAppendUndefined() {
		$this->View->append('test', 'Unknown');
		$result = $this->View->fetch('test');
		$this->assertEquals('Unknown', $result);
	}

/**
 * setting an array should cause an exception.
 *
 * @expectedException CakeException
 * @return void
 */
	public function testBlockSetArrayException() {
		$this->View->assign('test', array(1, 2, 3));
	}

/**
 * Appending an array should cause an exception.
 *
 * @expectedException CakeException
 * @return void
 */
	public function testBlockAppendArrayException() {
		$this->View->append('test', array(1, 2, 3));
	}

/**
 * Test getting block names
 *
 * @return void
 */
	public function testBlocks() {
		$this->View->append('test', 'one');
		$this->View->assign('test1', 'one');

		$this->assertEquals(array('test', 'test1'), $this->View->blocks());
	}

/**
 * Test that blocks can be nested.
 *
 * @return void
 */
	public function testNestedBlocks() {
		$this->View->start('first');
		echo 'In first ';
		$this->View->start('second');
		echo 'In second';
		$this->View->end();
		echo 'In first';
		$this->View->end();

		$this->assertEquals('In first In first', $this->View->fetch('first'));
		$this->assertEquals('In second', $this->View->fetch('second'));
	}

/**
 * Test that an exception gets thrown when you leave a block open at the end
 * of a view.
 *
 * @expectedException CakeException
 * @return void
 */
	public function testExceptionOnOpenBlock() {
		$this->View->render('open_block');
	}

/**
 * Test nested extended views.
 *
 * @return void
 */
	public function testExtendNested() {
		$this->View->layout = false;
		$content = $this->View->render('nested_extends');
		$expected = <<<TEXT
This is the second parent.
This is the first parent.
This is the first template.
Sidebar Content.
TEXT;
		$this->assertEquals($expected, $content);
	}

/**
 * Make sure that extending the current view with itself causes an exception
 *
 * @expectedException LogicException
 * @return void
 */
	public function testExtendSelf() {
		$this->View->layout = false;
		$this->View->render('extend_self');
	}

/**
 * Make sure that extending in a loop causes an exception
 *
 * @expectedException LogicException
 * @return void
 */
	public function testExtendLoop() {
		$this->View->layout = false;
		$this->View->render('extend_loop');
	}

/**
 * Test extend() in an element and a view.
 *
 * @return void
 */
	public function testExtendElement() {
		$this->View->layout = false;
		$content = $this->View->render('extend_element');
		$expected = <<<TEXT
Parent View.
View content.
Parent Element.
Element content.

TEXT;
		$this->assertEquals($expected, $content);
	}

/**
 * Extending an element which doesn't exist should throw a missing view exception
 *
 * @expectedException LogicException
 * @return void
 */
	public function testExtendMissingElement() {
		$this->View->layout = false;
		$this->View->render('extend_missing_element');
	}

/**
 * Test extend() preceeded by an element()
 *
 * @return void
 */
	public function testExtendWithElementBeforeExtend() {
		$this->View->layout = false;
		$result = $this->View->render('extend_with_element');
		$expected = <<<TEXT
Parent View.
this is the test elementThe view

TEXT;
		$this->assertEquals($expected, $result);
	}

/**
 * Test that setting arbitrary properties still works.
 *
 * @return void
 */
	public function testPropertySetting() {
		$this->assertFalse(isset($this->View->pageTitle));
		$this->View->pageTitle = 'test';
		$this->assertTrue(isset($this->View->pageTitle));
		$this->assertTrue(!empty($this->View->pageTitle));
		$this->assertEquals('test', $this->View->pageTitle);
	}

/**
 * Test that setting arbitrary properties still works.
 *
 * @return void
 */
	public function testPropertySettingMagicGet() {
		$this->assertFalse(isset($this->View->action));
		$this->View->request->params['action'] = 'login';
		$this->assertEquals('login', $this->View->action);
		$this->assertTrue(isset($this->View->action));
		$this->assertTrue(!empty($this->View->action));
	}

/**
 * test memory leaks that existed in _paths at one point.
 *
 * @return void
 */
	public function testMemoryLeakInPaths() {
		$this->ThemeController->plugin = null;
		$this->ThemeController->name = 'Posts';
		$this->ThemeController->viewPath = 'posts';
		$this->ThemeController->layout = 'whatever';
		$this->ThemeController->theme = 'TestTheme';

		$View = new View($this->ThemeController);
		$View->element('test_element');

		$start = memory_get_usage();
		for ($i = 0; $i < 10; $i++) {
			$View->element('test_element');
		}
		$end = memory_get_usage();
		$this->assertLessThanOrEqual($start + 5000, $end);
	}
}
