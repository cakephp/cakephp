<?php
/**
 * ThemeViewTest file
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
App::uses('ThemeView', 'View');
App::uses('Controller', 'Controller');


/**
 * ThemePosts2Controller class
 *
 * @package       Cake.Test.Case.View
 */
class ThemePosts2Controller extends Controller {

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
 * TestTheme2View class
 *
 * @package       Cake.Test.Case.View
 */
class TestTheme2View extends ThemeView {

/**
 * renderElement method
 *
 * @param mixed $name
 * @param array $params
 * @return void
 */
	public function renderElement($name, $params = array()) {
		return $name;
	}

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

}

/**
 * ThemeViewTest class
 *
 * @package       Cake.Test.Case.View
 */
class ThemeViewTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$request = new CakeRequest('posts/index');
		$this->Controller = new Controller($request);
		$this->PostsController = new ThemePosts2Controller($request);
		$this->PostsController->viewPath = 'posts';
		$this->PostsController->index();
		$this->ThemeView = new ThemeView($this->PostsController);
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View'. DS)
		));
		App::objects('plugins', null, false);
		CakePlugin::loadAll();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->ThemeView);
		unset($this->PostsController);
		unset($this->Controller);
		CakePlugin::unload();
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

		$ThemeView = new TestTheme2View($this->Controller);
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'Tests' . DS .'index.ctp';
		$result = $ThemeView->getViewFileName('index');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Plugin' . DS . 'TestPlugin' . DS . 'Layouts' . DS .'plugin_default.ctp';
		$result = $ThemeView->getLayoutFileName('plugin_default');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Layouts' . DS .'default.ctp';
		$result = $ThemeView->getLayoutFileName('default');
		$this->assertEquals($expected, $result);
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

		$ThemeView = new TestTheme2View($this->Controller);
		$ThemeView->theme = 'TestTheme';
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS .'Pages' . DS .'home.ctp';
		$result = $ThemeView->getViewFileName('home');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Posts' . DS .'index.ctp';
		$result = $ThemeView->getViewFileName('/Posts/index');
		$this->assertEquals($expected, $result);

		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS . 'Layouts' . DS .'default.ctp';
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
		$this->Controller->theme = 'my_theme';

		$this->Controller->params['pass'] = array('home');

		$View = new TestTheme2View($this->Controller);
		ob_start();
		$result = $View->getViewFileName('does_not_exist');
		$expected = str_replace(array("\t", "\r\n", "\n"), "", ob_get_clean());
		$this->assertRegExp("/PagesController::/", $expected);
		$this->assertRegExp("/views(\/|\\\)themed(\/|\\\)my_theme(\/|\\\)pages(\/|\\\)does_not_exist.ctp/", $expected);
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
		$this->Controller->viewPath = 'posts';
		$this->Controller->layout = 'whatever';
		$this->Controller->theme = 'my_theme';

		$View = new TestTheme2View($this->Controller);
		ob_start();
		$result = $View->getLayoutFileName();
		$expected = str_replace(array("\t", "\r\n", "\n"), "", ob_get_clean());
		$this->assertRegExp("/Missing Layout/", $expected);
		$this->assertRegExp("/views(\/|\\\)themed(\/|\\\)my_theme(\/|\\\)layouts(\/|\\\)whatever.ctp/", $expected);
	}

/**
 * test memory leaks that existed in _paths at one point.
 *
 * @return void
 */
	public function testMemoryLeakInPaths() {
		$this->Controller->plugin = null;
		$this->Controller->name = 'Posts';
		$this->Controller->viewPath = 'posts';
		$this->Controller->layout = 'whatever';
		$this->Controller->theme = 'TestTheme';

		$View = new ThemeView($this->Controller);
		$View->element('test_element');

		$start = memory_get_usage();
		for ($i = 0; $i < 10; $i++) {
			$View->element('test_element');
		}
		$end = memory_get_usage();
		$this->assertLessThanOrEqual($start + 5000, $end);
	}
}
