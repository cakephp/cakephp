<?php
/**
 * ThemeViewTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', array('Theme', 'Controller'));

if (!class_exists('ErrorHandler')) {
	App::import('Core', array('Error'));
}
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}

/**
 * ThemePostsController class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view
 */
class ThemePostsController extends Controller {

/**
 * name property
 *
 * @var string 'ThemePosts'
 * @access public
 */
	var $name = 'ThemePosts';

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
}

/**
 * ThemeViewTestErrorHandler class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view
 */
class ThemeViewTestErrorHandler extends ErrorHandler {

/**
 * stop method
 *
 * @access public
 * @return void
 */
	function _stop() {
		return;
	}
}

/**
 * TestThemeView class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.view
 */
class TestThemeView extends ThemeView {

/**
 * renderElement method
 *
 * @param mixed $name
 * @param array $params
 * @access public
 * @return void
 */
	function renderElement($name, $params = array()) {
		return $name;
	}

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
 * cakeError method
 *
 * @param mixed $method
 * @param mixed $messages
 * @access public
 * @return void
 */
	function cakeError($method, $messages) {
		$error =& new ThemeViewTestErrorHandler($method, $messages);
		return $error;
	}
}

/**
 * ThemeViewTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class ThemeViewTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		Router::reload();
		$this->Controller =& new Controller();
		$this->PostsController =& new ThemePostsController();
		$this->PostsController->viewPath = 'posts';
		$this->PostsController->index();
		$this->ThemeView =& new ThemeView($this->PostsController);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->ThemeView);
		unset($this->PostsController);
		unset($this->Controller);
		ClassRegistry::flush();
	}
/**
 * test that the theme view can be constructed without going into the registry
 *
 * @return void
 */
	function testConstructionNoRegister() {
		ClassRegistry::flush();
		$controller = null;
		$Theme =& new ThemeView($controller, false);
		$ThemeTwo =& ClassRegistry::getObject('view');
		$this->assertFalse($ThemeTwo);
	}

/**
 * startTest
 *
 * @access public
 * @return void
 */
	function startTest() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views'. DS)
		));
	}

/**
 * endTest
 *
 * @access public
 * @return void
 */
	function endTest() {
		App::build();
	}

/**
 * testPluginGetTemplate method
 *
 * @access public
 * @return void
 */
	function testPluginThemedGetTemplate() {
		$this->Controller->plugin = 'test_plugin';
		$this->Controller->name = 'TestPlugin';
		$this->Controller->viewPath = 'tests';
		$this->Controller->action = 'index';
		$this->Controller->theme = 'test_theme';

		$ThemeView =& new TestThemeView($this->Controller);
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'plugins' . DS . 'test_plugin' . DS . 'tests' . DS .'index.ctp';
		$result = $ThemeView->getViewFileName('index');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'plugins' . DS . 'test_plugin' . DS . 'layouts' . DS .'plugin_default.ctp';
		$result = $ThemeView->getLayoutFileName('plugin_default');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'layouts' . DS .'default.ctp';
		$result = $ThemeView->getLayoutFileName('default');
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

		$ThemeView =& new TestThemeView($this->Controller);
		$ThemeView->theme = 'test_theme';
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS .'pages' . DS .'home.ctp';
		$result = $ThemeView->getViewFileName('home');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'posts' . DS .'index.ctp';
		$result = $ThemeView->getViewFileName('/posts/index');
		$this->assertEqual($result, $expected);

		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS . 'layouts' . DS .'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertEqual($result, $expected);

		$ThemeView->layoutPath = 'rss';
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'layouts' . DS . 'rss' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertEqual($result, $expected);

		$ThemeView->layoutPath = 'email' . DS . 'html';
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'layouts' . DS . 'email' . DS . 'html' . DS . 'default.ctp';
		$result = $ThemeView->getLayoutFileName();
		$this->assertEqual($result, $expected);
	}

/**
 * testMissingView method
 *
 * @access public
 * @return void
 */
	function testMissingView() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Pages';
		$this->Controller->viewPath = 'pages';
		$this->Controller->action = 'display';
		$this->Controller->theme = 'my_theme';

		$this->Controller->params['pass'] = array('home');

		restore_error_handler();
		$View =& new TestThemeView($this->Controller);
		ob_start();
		$result = $View->getViewFileName('does_not_exist');
		$expected = str_replace(array("\t", "\r\n", "\n"), "", ob_get_clean());
		set_error_handler('simpleTestErrorHandler');
		$this->assertPattern("/PagesController::/", $expected);
		$this->assertPattern("/views(\/|\\\)themed(\/|\\\)my_theme(\/|\\\)pages(\/|\\\)does_not_exist.ctp/", $expected);
	}

/**
 * testMissingLayout method
 *
 * @access public
 * @return void
 */
	function testMissingLayout() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Posts';
		$this->Controller->viewPath = 'posts';
		$this->Controller->layout = 'whatever';
		$this->Controller->theme = 'my_theme';

		restore_error_handler();
		$View =& new TestThemeView($this->Controller);
		ob_start();
		$result = $View->getLayoutFileName();
		$expected = str_replace(array("\t", "\r\n", "\n"), "", ob_get_clean());
		set_error_handler('simpleTestErrorHandler');
		$this->assertPattern("/Missing Layout/", $expected);
		$this->assertPattern("/views(\/|\\\)themed(\/|\\\)my_theme(\/|\\\)layouts(\/|\\\)whatever.ctp/", $expected);
	}

/**
 * test memory leaks that existed in _paths at one point.
 *
 * @return void
 */
	function testMemoryLeakInPaths() {
		if ($this->skipIf(!function_exists('memory_get_usage'), 'No memory measurement function, cannot test for possible memory leak. %s')) {
			return;
		}
		$this->Controller->plugin = null;
		$this->Controller->name = 'Posts';
		$this->Controller->viewPath = 'posts';
		$this->Controller->layout = 'whatever';
		$this->Controller->theme = 'test_theme';

		$View =& new ThemeView($this->Controller);
		$View->element('test_element');

		$start = memory_get_usage();
		for ($i = 0; $i < 10; $i++) {
			$View->element('test_element');
		}
		$end = memory_get_usage();
		$this->assertWithinMargin($start, $end, 3500);
	}
}
