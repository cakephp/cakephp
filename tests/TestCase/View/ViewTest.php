<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Cache\Cache;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Network\Request;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper;
use Cake\View\View;

/**
 * ViewPostsController class
 *
 */
class ViewPostsController extends Controller {

/**
 * name property
 *
 * @var string
 */
	public $name = 'Posts';

/**
 * uses property
 *
 * @var mixed
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
 */
class ThemePostsController extends Controller {

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
 */
class TestThemeView extends View {

/**
 * renderElement method
 *
 * @param string $name
 * @param array $params
 * @return string The given name
 */
	public function renderElement($name, $params = array()) {
		return $name;
	}

/**
 * getViewFileName method
 *
 * @param string $name Controller action to find template filename for
 * @return string Template filename
 */
	public function getViewFileName($name = null) {
		return $this->_getViewFileName($name);
	}

/**
 * getLayoutFileName method
 *
 * @param string $name The name of the layout to find.
 * @return string Filename for layout file (.ctp).
 */
	public function getLayoutFileName($name = null) {
		return $this->_getLayoutFileName($name);
	}

}

/**
 * TestView class
 *
 */
class TestView extends View {

/**
 * getViewFileName method
 *
 * @param string $name Controller action to find template filename for
 * @return string Template filename
 */
	public function getViewFileName($name = null) {
		return $this->_getViewFileName($name);
	}

/**
 * getLayoutFileName method
 *
 * @param string $name The name of the layout to find.
 * @return string Filename for layout file (.ctp).
 */
	public function getLayoutFileName($name = null) {
		return $this->_getLayoutFileName($name);
	}

/**
 * paths method
 *
 * @param string $plugin Optional plugin name to scan for view files.
 * @param bool $cached Set to true to force a refresh of view paths.
 * @return array paths
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
 * TestBeforeAfterHelper class
 *
 */
class TestBeforeAfterHelper extends Helper {

/**
 * property property
 *
 * @var string
 */
	public $property = '';

/**
 * beforeLayout method
 *
 * @param string $viewFile
 * @return void
 */
	public function beforeLayout($viewFile) {
		$this->property = 'Valuation';
	}

/**
 * afterLayout method
 *
 * @param string $layoutFile
 * @return void
 */
	public function afterLayout($layoutFile) {
		$this->_View->append('content', 'modified in the afterlife');
	}

}

/**
 * Class TestObjectWithToString
 *
 * An object with the magic method __toString() for testing with view blocks.
 */
class TestObjectWithToString {

	public function __toString() {
		return "I'm ObjectWithToString";
	}

}

/**
 * Class TestObjectWithoutToString
 *
 * An object without the magic method __toString() for testing with view blocks.
 */
class TestObjectWithoutToString {
}

/**
 * ViewTest class
 *
 */
class ViewTest extends TestCase {

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

		$request = new Request();
		$this->Controller = new Controller($request);
		$this->PostsController = new ViewPostsController($request);
		$this->PostsController->viewPath = 'Posts';
		$this->PostsController->index();
		$this->View = $this->PostsController->createView();

		$themeRequest = new Request('posts/index');
		$this->ThemeController = new Controller($themeRequest);
		$this->ThemePostsController = new ThemePostsController($themeRequest);
		$this->ThemePostsController->viewPath = 'Posts';
		$this->ThemePostsController->index();
		$this->ThemeView = $this->ThemePostsController->createView();

		App::objects('Plugin', null, false);

		Plugin::load(array('TestPlugin', 'TestPlugin', 'PluginJs'));
		Configure::write('debug', true);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload();
		unset($this->View);
		unset($this->PostsController);
		unset($this->Controller);
		unset($this->ThemeView);
		unset($this->ThemePostsController);
		unset($this->ThemeController);
	}

/**
 * Test getViewFileName method
 *
 * @return void
 */
	public function testGetTemplate() {
		$request = $this->getMock('Cake\Network\Request');
		$response = $this->getMock('Cake\Network\Response');

		$viewOptions = [ 'plugin' => null,
			'name' => 'Pages',
			'viewPath' => 'Pages'
		];
		$request->action = 'display';
		$request->params['pass'] = array('home');

		$ThemeView = new TestThemeView(null, null, null, $viewOptions);
		$ThemeView->theme = 'TestTheme';
		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Pages' . DS . 'home.ctp';
		$result = $ThemeView->getViewFileName('home');
		$this->assertPathEquals($expected, $result);

		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Posts' . DS . 'index.ctp';
		$result = $ThemeView->getViewFileName('/Posts/index');
		$this->assertPathEquals($expected, $result);

		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Layout' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertPathEquals($expected, $result);

		$ThemeView->layoutPath = 'rss';
		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Layout' . DS . 'rss' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertPathEquals($expected, $result);

		$ThemeView->layoutPath = 'Email' . DS . 'html';
		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Layout' . DS . 'Email' . DS . 'html' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertPathEquals($expected, $result);
	}

/**
 * Test getLayoutFileName method on plugin
 *
 * @return void
 */
	public function testPluginGetTemplate() {
		$viewOptions = ['plugin' => 'TestPlugin',
			'name' => 'TestPlugin',
			'viewPath' => 'Tests',
			'view' => 'index'
		];

		$View = new TestView(null, null, null, $viewOptions);

		$expected = Plugin::path('TestPlugin') . 'Template' . DS . 'Tests' . DS . 'index.ctp';
		$result = $View->getViewFileName('index');
		$this->assertEquals($expected, $result);

		$expected = Plugin::path('TestPlugin') . 'Template' . DS . 'Layout' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertEquals($expected, $result);
	}

/**
 * Test getViewFileName method on plugin
 *
 * @return void
 */
	public function testPluginThemedGetTemplate() {
		$viewOptions = ['plugin' => 'TestPlugin',
			'name' => 'TestPlugin',
			'viewPath' => 'Tests',
			'view' => 'index',
			'theme' => 'TestTheme'
		];

		$ThemeView = new TestThemeView(null, null, null, $viewOptions);
		$themePath = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Themed' . DS . 'TestTheme' . DS;

		$expected = $themePath . 'Plugin' . DS . 'TestPlugin' . DS . 'Tests' . DS . 'index.ctp';
		$result = $ThemeView->getViewFileName('index');
		$this->assertPathEquals($expected, $result);

		$expected = $themePath . 'Plugin' . DS . 'TestPlugin' . DS . 'Layout' . DS . 'plugin_default.ctp';
		$result = $ThemeView->getLayoutFileName('plugin_default');
		$this->assertPathEquals($expected, $result);

		$expected = $themePath . 'Layout' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName('default');
		$this->assertPathEquals($expected, $result);
	}

/**
 * Test that plugin/$plugin_name is only appended to the paths it should be.
 *
 * @return void
 */
	public function testPluginPathGeneration() {
		$viewOptions = ['plugin' => 'TestPlugin',
			'name' => 'TestPlugin',
			'viewPath' => 'Tests',
			'view' => 'index'
		];

		$View = new TestView(null, null, null, $viewOptions);
		$paths = $View->paths();
		$expected = array_merge(App::path('Template'), App::core('Template'));
		$this->assertEquals($expected, $paths);

		$paths = $View->paths('TestPlugin');
		$pluginPath = Plugin::path('TestPlugin');
		$expected = array(
			TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Plugin' . DS . 'TestPlugin' . DS,
			$pluginPath . 'Template' . DS,
			TEST_APP . 'TestApp' . DS . 'Template' . DS,
			CAKE . 'Template' . DS,
		);
		$this->assertPathEquals($expected, $paths);
	}

/**
 * Test that CamelCase'd plugins still find their view files.
 *
 * @return void
 */
	public function testCamelCasePluginGetTemplate() {
		$viewOptions = ['plugin' => 'TestPlugin',
			'name' => 'TestPlugin',
			'viewPath' => 'Tests',
			'view' => 'index'
		];

		$View = new TestView(null, null, null, $viewOptions);

		$pluginPath = Plugin::path('TestPlugin');
		$expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'Template' . DS . 'Tests' . DS . 'index.ctp';
		$result = $View->getViewFileName('index');
		$this->assertPathEquals($expected, $result);

		$expected = $pluginPath . 'Template' . DS . 'Layout' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertPathEquals($expected, $result);
	}

/**
 * Test getViewFileName method
 *
 * @return void
 */
	public function testGetViewFileNames() {
		$viewOptions = ['plugin' => null,
			'name' => 'Pages',
			'viewPath' => 'Pages'
		];
		$request = $this->getMock('Cake\Network\Request');
		$response = $this->getMock('Cake\Network\Response');

		$View = new TestView(null, null, null, $viewOptions);

		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Pages' . DS . 'home.ctp';
		$result = $View->getViewFileName('home');
		$this->assertPathEquals($expected, $result);

		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Posts' . DS . 'index.ctp';
		$result = $View->getViewFileName('/Posts/index');
		$this->assertPathEquals($expected, $result);

		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Posts' . DS . 'index.ctp';
		$result = $View->getViewFileName('../Posts/index');
		$this->assertPathEquals($expected, $result);

		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Pages' . DS . 'page.home.ctp';
		$result = $View->getViewFileName('page.home');
		$this->assertPathEquals($expected, $result, 'Should not ruin files with dots.');

		Plugin::load('TestPlugin');
		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Pages' . DS . 'home.ctp';
		$result = $View->getViewFileName('TestPlugin.home');
		$this->assertPathEquals($expected, $result, 'Plugin is missing the view, cascade to app.');

		$View->viewPath = 'Tests';
		$expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'Template' . DS . 'Tests' . DS . 'index.ctp';
		$result = $View->getViewFileName('TestPlugin.index');
		$this->assertPathEquals($expected, $result);
	}

/**
 * Test getting layout filenames
 *
 * @return void
 */
	public function testGetLayoutFileName() {
		$viewOptions = ['plugin' => null,
			'name' => 'Pages',
			'viewPath' => 'Pages',
			'action' => 'display'
		];

		$View = new TestView(null, null, null, $viewOptions);

		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Layout' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertPathEquals($expected, $result);

		$View->layoutPath = 'rss';
		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Layout' . DS . 'rss' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertPathEquals($expected, $result);

		$View->layoutPath = 'Email' . DS . 'html';
		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Layout' . DS . 'Email' . DS . 'html' . DS . 'default.ctp';
		$result = $View->getLayoutFileName();
		$this->assertPathEquals($expected, $result);
	}

/**
 * Test getting layout filenames for plugins.
 *
 * @return void
 */
	public function testGetLayoutFileNamePlugin() {
		$viewOptions = ['plugin' => null,
			'name' => 'Pages',
			'viewPath' => 'Pages',
			'action' => 'display'
		];

		$View = new TestView(null, null, null, $viewOptions);
		Plugin::load('TestPlugin');

		$expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'Template' . DS . 'Layout' . DS . 'default.ctp';
		$result = $View->getLayoutFileName('TestPlugin.default');
		$this->assertPathEquals($expected, $result);

		$View->plugin = 'TestPlugin';
		$expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS . 'Template' . DS . 'Layout' . DS . 'default.ctp';
		$result = $View->getLayoutFileName('default');
		$this->assertPathEquals($expected, $result);
	}

/**
 * Test for missing views
 *
 * @expectedException \Cake\View\Error\MissingViewException
 * @return void
 */
	public function testMissingView() {
		$viewOptions = ['plugin' => null,
			'name' => 'Pages',
			'viewPath' => 'Pages'
		];
		$request = $this->getMock('Cake\Network\Request');
		$response = $this->getMock('Cake\Network\Response');

		$View = new TestView($request, $response, null, $viewOptions);
		ob_start();
		$View->getViewFileName('does_not_exist');
		ob_get_clean();
	}

/**
 * Test for missing layouts
 *
 * @expectedException \Cake\View\Error\MissingLayoutException
 * @return void
 */
	public function testMissingLayout() {
		$viewOptions = ['plugin' => null,
			'name' => 'Pages',
			'viewPath' => 'Pages',
			'layout' => 'whatever'
		];
		$View = new TestView(null, null, null, $viewOptions);
		ob_start();
		$View->getLayoutFileName();
		ob_get_clean();
	}

/**
 * Test viewVars method
 *
 * @return void
 */
	public function testViewVars() {
		$this->assertEquals(array('testData' => 'Some test data', 'test2' => 'more data', 'test3' => 'even more data'), $this->View->viewVars);
	}

/**
 * Test generation of UUIDs method
 *
 * @return void
 */
	public function testUUIDGeneration() {
		Router::connect('/:controller', ['action' => 'index']);
		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEquals('form5988016017', $result);

		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEquals('formc3dc6be854', $result);

		$result = $this->View->uuid('form', array('controller' => 'posts', 'action' => 'index'));
		$this->assertEquals('form28f92cc87f', $result);
	}

/**
 * Test elementExists method
 *
 * @return void
 */
	public function testElementExists() {
		$result = $this->View->elementExists('test_element');
		$this->assertTrue($result);

		$result = $this->View->elementExists('TestPlugin.plugin_element');
		$this->assertTrue($result);

		$result = $this->View->elementExists('non_existent_element');
		$this->assertFalse($result);

		$result = $this->View->elementExists('TestPlugin.element');
		$this->assertFalse($result);

		$this->View->plugin = 'TestPlugin';
		$result = $this->View->elementExists('test_plugin_element');
		$this->assertTrue($result);
	}

/**
 * Test element method
 *
 * @return void
 */
	public function testElement() {
		$result = $this->View->element('test_element');
		$this->assertEquals('this is the test element', $result);

		$result = $this->View->element('TestPlugin.plugin_element');
		$this->assertEquals('this is the plugin element using params[plugin]', $result);

		$this->View->plugin = 'TestPlugin';
		$result = $this->View->element('test_plugin_element');
		$this->assertEquals('this is the test set using View::$plugin plugin element', $result);
	}

/**
 * Test elementInexistent method
 *
 * @expectedException PHPUnit_Framework_Error_Notice
 * @return void
 */
	public function testElementInexistent() {
		$this->View->element('non_existent_element');
	}

/**
 * Test elementInexistent3 method
 *
 * @expectedException PHPUnit_Framework_Error_Notice
 * @return void
 */
	public function testElementInexistent3() {
		$this->View->element('test_plugin.plugin_element');
	}

/**
 * Test that elements can have callbacks
 *
 * @return void
 */
	public function testElementCallbacks() {
		$count = 0;
		$callback = function ($event, $file) use (&$count) {
			$count++;
		};
		$events = $this->View->getEventManager();
		$events->attach($callback, 'View.beforeRender');
		$events->attach($callback, 'View.afterRender');

		$this->View->element('test_element', array(), array('callbacks' => true));
		$this->assertEquals(2, $count);
	}

/**
 * Test that additional element viewVars don't get overwritten with helpers.
 *
 * @return void
 */
	public function testElementParamsDontOverwriteHelpers() {
		$Controller = new ViewPostsController();
		$Controller->helpers = array('Form');

		$View = $Controller->createView();
		$result = $View->element('type_check', array('form' => 'string'), array('callbacks' => true));
		$this->assertEquals('string', $result);

		$View->set('form', 'string');
		$result = $View->element('type_check', array(), array('callbacks' => true));
		$this->assertEquals('string', $result);
	}

/**
 * Test elementCacheHelperNoCache method
 *
 * @return void
 */
	public function testElementCacheHelperNoCache() {
		$Controller = new ViewPostsController();
		$View = $Controller->createView();
		$View->loadHelpers();
		$result = $View->element('test_element', array('ram' => 'val', 'test' => array('foo', 'bar')));
		$this->assertEquals('this is the test element', $result);
	}

/**
 * Test elementCache method
 *
 * @return void
 */
	public function testElementCache() {
		Cache::drop('test_view');
		Cache::config('test_view', [
			'engine' => 'File',
			'duration' => '+1 day',
			'path' => CACHE . 'views/',
			'prefix' => ''
		]);
		Cache::clear(true, 'test_view');

		$View = $this->PostsController->createView();
		$View->elementCache = 'test_view';

		$result = $View->element('test_element', array(), array('cache' => true));
		$expected = 'this is the test element';
		$this->assertEquals($expected, $result);

		$result = Cache::read('element__test_element_cache_callbacks', 'test_view');
		$this->assertEquals($expected, $result);

		$result = $View->element('test_element', array('param' => 'one', 'foo' => 'two'), array('cache' => true));
		$this->assertEquals($expected, $result);

		$result = Cache::read('element__test_element_cache_callbacks_param_foo', 'test_view');
		$this->assertEquals($expected, $result);

		$View->element('test_element', array(
			'param' => 'one',
			'foo' => 'two'
		), array(
			'cache' => array('key' => 'custom_key')
		));
		$result = Cache::read('element_custom_key', 'test_view');
		$this->assertEquals($expected, $result);

		$View->elementCache = 'default';
		$View->element('test_element', array(
			'param' => 'one',
			'foo' => 'two'
		), array(
			'cache' => array('config' => 'test_view'),
		));
		$result = Cache::read('element__test_element_cache_callbacks_param_foo', 'test_view');
		$this->assertEquals($expected, $result);

		Cache::clear(true, 'test_view');
		Cache::drop('test_view');
	}

/**
 * Test __get allowing access to helpers.
 *
 * @return void
 */
	public function testMagicGetAndAddHelper() {
		$View = new View();
		$View->addHelper('Html');
		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $View->Html);
	}

/**
 * Test loadHelpers method
 *
 * @return void
 */
	public function testLoadHelpers() {
		$View = new View();

		$View->helpers = array('Html', 'Form');
		$View->loadHelpers();

		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $View->Html, 'Object type is wrong.');
		$this->assertInstanceOf('Cake\View\Helper\FormHelper', $View->Form, 'Object type is wrong.');
	}

/**
 * Test lazy loading helpers
 *
 * @return void
 */
	public function testLazyLoadHelpers() {
		$View = new View();

		$View->helpers = array();
		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $View->Html, 'Object type is wrong.');
		$this->assertInstanceOf('Cake\View\Helper\FormHelper', $View->Form, 'Object type is wrong.');
	}

/**
 * Test the correct triggering of helper callbacks
 *
 * @return void
 */
	public function testHelperCallbackTriggering() {
		$View = $this->PostsController->createView();

		$manager = $this->getMock('Cake\Event\EventManager');
		$View->setEventManager($manager);

		$manager->expects($this->at(0))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'View.beforeRender'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$manager->expects($this->at(1))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'View.beforeRenderFile'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$manager->expects($this->at(2))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'View.afterRenderFile'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$manager->expects($this->at(3))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'View.afterRender'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$manager->expects($this->at(4))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'View.beforeLayout'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$manager->expects($this->at(5))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'View.beforeRenderFile'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$manager->expects($this->at(6))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'View.afterRenderFile'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$manager->expects($this->at(7))->method('dispatch')
			->with(
				$this->logicalAnd(
					$this->isInstanceOf('Cake\Event\Event'),
					$this->attributeEqualTo('_name', 'View.afterLayout'),
					$this->attributeEqualTo('_subject', $View)
				)
			);

		$View->render('index');
	}

/**
 * Test beforeLayout method
 *
 * @return void
 */
	public function testBeforeLayout() {
		$this->PostsController->helpers = array(
			'Session',
			'TestBeforeAfter' => array('className' => __NAMESPACE__ . '\TestBeforeAfterHelper'),
			'Html'
		);
		$View = $this->PostsController->createView();
		$View->render('index');
		$this->assertEquals('Valuation', $View->helpers()->TestBeforeAfter->property);
	}

/**
 * Test afterLayout method
 *
 * @return void
 */
	public function testAfterLayout() {
		$this->PostsController->helpers = array(
			'Session',
			'TestBeforeAfter' => array('className' => __NAMESPACE__ . '\TestBeforeAfterHelper'),
			'Html'
		);
		$this->PostsController->set('variable', 'values');

		$View = $this->PostsController->createView();

		$content = 'This is my view output';
		$result = $View->renderLayout($content, 'default');
		$this->assertRegExp('/modified in the afterlife/', $result);
		$this->assertRegExp('/This is my view output/', $result);
	}

/**
 * Test renderLoadHelper method
 *
 * @return void
 */
	public function testRenderLoadHelper() {
		$this->PostsController->helpers = array('Session', 'Html', 'Form', 'Number');
		$View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');

		$result = $View->render('index', false);
		$this->assertEquals('posts index', $result);

		$attached = $View->helpers()->loaded();
		$this->assertEquals(array('Session', 'Html', 'Form', 'Number'), $attached);

		$this->PostsController->helpers = array('Html', 'Form', 'Number', 'TestPlugin.PluggedHelper');
		$View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');

		$result = $View->render('index', false);
		$this->assertEquals('posts index', $result);

		$attached = $View->helpers()->loaded();
		$expected = array('Html', 'Form', 'Number', 'PluggedHelper');
		$this->assertEquals($expected, $attached, 'Attached helpers are wrong.');
	}

/**
 * Test render method
 *
 * @return void
 */
	public function testRender() {
		$View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
		$result = $View->render('index');

		$this->assertRegExp("/<meta http-equiv=\"Content-Type\" content=\"text\/html; charset=utf-8\" \/>\s*<title>/", $result);
		$this->assertRegExp("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
		$this->assertRegExp("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);

		$this->assertTrue(isset($View->viewVars['content_for_layout']), 'content_for_layout should be a view var');
		$this->assertTrue(isset($View->viewVars['scripts_for_layout']), 'scripts_for_layout should be a view var');

		$View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
		$result = $View->render(false, 'ajax2');

		$this->assertRegExp('/Ajax\!/', $result);

		$this->assertNull($View->render(false, 'ajax2'));

		$this->PostsController->helpers = array('Session', 'Cache', 'Html');
		$this->PostsController->constructClasses();
		$this->PostsController->cacheAction = array('index' => 3600);
		$this->PostsController->request->params['action'] = 'index';
		Configure::write('Cache.check', true);

		$View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
		$result = $View->render('index');

		$this->assertRegExp("/<meta http-equiv=\"Content-Type\" content=\"text\/html; charset=utf-8\" \/>\s*<title>/", $result);
		$this->assertRegExp("/<div id=\"content\">\s*posts index\s*<\/div>/", $result);
	}

/**
 * Test that View::$view works
 *
 * @return void
 */
	public function testRenderUsingViewProperty() {
		$this->PostsController->view = 'cache_form';
		$View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');

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
		$this->PostsController->name = 'Posts';
		$this->PostsController->viewPath = 'Element';
		$View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');
		$pluginPath = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS;
		$result = $View->getViewFileName('sub_dir/sub_element');
		$expected = $pluginPath . 'Template' . DS . 'Element' . DS . 'sub_dir' . DS . 'sub_element.ctp';
		$this->assertPathEquals($expected, $result);
	}

/**
 * Test that view vars can replace the local helper variables
 * and not overwrite the $this->Helper references
 *
 * @return void
 */
	public function testViewVarOverwritingLocalHelperVar() {
		$Controller = new ViewPostsController();
		$Controller->helpers = array('Session', 'Html');
		$Controller->set('html', 'I am some test html');
		$View = $Controller->createView();
		$result = $View->render('helper_overwrite', false);

		$this->assertRegExp('/I am some test html/', $result);
		$this->assertRegExp('/Test link/', $result);
	}

/**
 * Test getViewFileName method
 *
 * @return void
 */
	public function testViewFileName() {
		$View = $this->PostsController->createView('Cake\Test\TestCase\View\TestView');

		$result = $View->getViewFileName('index');
		$this->assertRegExp('/Posts(\/|\\\)index.ctp/', $result);

		$result = $View->getViewFileName('TestPlugin.index');
		$this->assertRegExp('/Posts(\/|\\\)index.ctp/', $result);

		$result = $View->getViewFileName('/Pages/home');
		$this->assertRegExp('/Pages(\/|\\\)home.ctp/', $result);

		$result = $View->getViewFileName('../Element/test_element');
		$this->assertRegExp('/Element(\/|\\\)test_element.ctp/', $result);

		$result = $View->getViewFileName('../Themed/TestTheme/Posts/index');
		$this->assertRegExp('/Themed(\/|\\\)TestTheme(\/|\\\)Posts(\/|\\\)index.ctp/', $result);

		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Posts' . DS . 'index.ctp';
		$result = $View->getViewFileName('../Posts/index');
		$this->assertPathEquals($expected, $result);
	}

/**
 * Test renderCache method
 *
 * @return void
 */
	public function testRenderCache() {
		$this->skipIf(!is_writable(CACHE . 'views/'), 'CACHE/views dir is not writable, cannot test renderCache.');

		$view = 'test_view';
		$View = $this->PostsController->createView();
		$path = CACHE . 'views/view_cache_' . $view;

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
		$View = $this->PostsController->createView();
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
		$View = $this->PostsController->createView();
		$View->set(array('superman' => 'clark', 'variable' => 'var'));
		$View->helpers = array('Html', 'Form');
		$View->layout = 'cache_layout';
		$result = $View->render('index');
		$this->assertNotRegExp('/cake:nocache/', $result);
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
 * Test block with startIfEmpty
 *
 * @return void
 */
	public function testBlockCaptureStartIfEmpty() {
		$this->View->startIfEmpty('test');
		echo "Block content 1";
		$this->View->end();

		$this->View->startIfEmpty('test');
		echo "Block content 2";
		$this->View->end();

		$result = $this->View->fetch('test');
		$this->assertEquals('Block content 1', $result);
	}

/**
 * Test block with startIfEmpty
 *
 * @return void
 */
	public function testBlockCaptureStartStartIfEmpty() {
		$this->View->start('test');
		echo "Block content 1";
		$this->View->end();

		$this->View->startIfEmpty('test');
		echo "Block content 2";
		$this->View->end();

		$result = $this->View->fetch('test');
		$this->assertEquals('Block content 1', $result);
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
 * Test resetting a block's content.
 *
 * @return void
 */
	public function testBlockReset() {
		$this->View->assign('test', '');
		$result = $this->View->fetch('test', 'This should not be returned');
		$this->assertSame('', $result);
	}

/**
 * Test setting a block's content to null
 *
 * @return void
 * @link https://cakephp.lighthouseapp.com/projects/42648/tickets/3938-this-redirectthis-auth-redirecturl-broken
 */
	public function testBlockSetNull() {
		$this->View->assign('testWithNull', null);
		$result = $this->View->fetch('testWithNull');
		$this->assertSame('', $result);
	}

/**
 * Test setting a block's content to an object with __toString magic method
 *
 * @return void
 */
	public function testBlockSetObjectWithToString() {
		$objectWithToString = new TestObjectWithToString();
		$this->View->assign('testWithObjectWithToString', $objectWithToString);
		$result = $this->View->fetch('testWithObjectWithToString');
		$this->assertSame("I'm ObjectWithToString", $result);
	}

/**
 * Test setting a block's content to an object without __toString magic method
 *
 * This should produce a "Object of class TestObjectWithoutToString could not be converted to string" error
 * which gets thrown as a PHPUnit_Framework_Error Exception by PHPUnit.
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testBlockSetObjectWithoutToString() {
		$objectWithToString = new TestObjectWithoutToString();
		$this->View->assign('testWithObjectWithoutToString', $objectWithToString);
	}

/**
 * Test setting a block's content to a decimal
 *
 * @return void
 */
	public function testBlockSetDecimal() {
		$this->View->assign('testWithDecimal', 1.23456789);
		$result = $this->View->fetch('testWithDecimal');
		$this->assertEquals('1.23456789', $result);
	}

/**
 * Data provider for block related tests.
 *
 * @return array
 */
	public static function blockValueProvider() {
		return array(
			'string' => array('A string value'),
			'null' => array(null),
			'decimal' => array(1.23456),
			'object with __toString' => array(new TestObjectWithToString()),
		);
	}

/**
 * Test appending to a block with append.
 *
 * @dataProvider blockValueProvider
 * @return void
 */
	public function testBlockAppend($value) {
		$this->View->assign('testBlock', 'Block');
		$this->View->append('testBlock', $value);

		$result = $this->View->fetch('testBlock');
		$this->assertSame('Block' . $value, $result);
	}

/**
 * Test appending an object without __toString magic method to a block with append.
 *
 * This should produce a "Object of class TestObjectWithoutToString could not be converted to string" error
 * which gets thrown as a PHPUnit_Framework_Error Exception by PHPUnit.
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testBlockAppendObjectWithoutToString() {
		$object = new TestObjectWithoutToString();
		$this->View->assign('testBlock', 'Block ');
		$this->View->append('testBlock', $object);
	}

/**
 * Test prepending to a block with prepend.
 *
 * @dataProvider blockValueProvider
 * @return void
 */
	public function testBlockPrepend($value) {
		$this->View->assign('test', 'Block');
		$this->View->prepend('test', $value);

		$result = $this->View->fetch('test');
		$this->assertEquals($value . 'Block', $result);
	}

/**
 * Test prepending an object without __toString magic method to a block with prepend.
 *
 * This should produce a "Object of class TestObjectWithoutToString could not be converted to string" error
 * which gets thrown as a PHPUnit_Framework_Error Exception by PHPUnit.
 *
 * @expectedException PHPUnit_Framework_Error
 * @return void
 */
	public function testBlockPrependObjectWithoutToString() {
		$object = new TestObjectWithoutToString();
		$this->View->assign('test', 'Block ');
		$this->View->prepend('test', $object);
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
 * You should be able to prepend to undefined blocks.
 *
 * @return void
 */
	public function testBlockPrependUndefined() {
		$this->View->prepend('test', 'Unknown');
		$result = $this->View->fetch('test');
		$this->assertEquals('Unknown', $result);
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
 * Test that starting the same block twice throws an exception
 *
 * @expectedException \Cake\Error\Exception
 * @return void
 */
	public function testStartBlocksTwice() {
		$this->View->start('first');
		$this->View->start('first');
	}

/**
 * Test that an exception gets thrown when you leave a block open at the end
 * of a view.
 *
 * @expectedException \Cake\Error\Exception
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
 * Test memory leaks that existed in _paths at one point.
 *
 * @return void
 */
	public function testMemoryLeakInPaths() {
		$this->ThemeController->plugin = null;
		$this->ThemeController->name = 'Posts';
		$this->ThemeController->viewPath = 'Posts';
		$this->ThemeController->layout = 'whatever';
		$this->ThemeController->theme = 'TestTheme';

		$View = $this->ThemeController->createView();
		$View->element('test_element');

		$start = memory_get_usage();
		for ($i = 0; $i < 10; $i++) {
			$View->element('test_element');
		}
		$end = memory_get_usage();
		$this->assertLessThanOrEqual($start + 5000, $end);
	}

/**
 * Tests that a view block uses default value when not assigned and uses assigned value when it is
 *
 * @return void
 */
	public function testBlockDefaultValue() {
		$default = 'Default';
		$result = $this->View->fetch('title', $default);
		$this->assertEquals($default, $result);

		$expected = 'My Title';
		$this->View->assign('title', $expected);
		$result = $this->View->fetch('title', $default);
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that a view variable uses default value when not assigned and uses assigned value when it is
 *
 * @return void
 */
	public function testViewVarDefaultValue() {
		$default = 'Default';
		$result = $this->View->get('title', $default);
		$this->assertEquals($default, $result);

		$expected = 'Back to the Future';
		$this->View->set('title', $expected);
		$result = $this->View->get('title', $default);
		$this->assertEquals($expected, $result);
	}

/**
 * Test the helpers() method.
 *
 * @return void
 */
	public function testHelpers() {
		$this->assertInstanceOf('Cake\View\HelperRegistry', $this->View->helpers());

		$result = $this->View->helpers();
		$this->assertSame($result, $this->View->helpers());
	}

}
