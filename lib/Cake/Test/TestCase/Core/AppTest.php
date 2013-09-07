<?php
/**
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;

/**
 * AppTest class
 *
 */
class AppTest extends TestCase {

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload();
	}

/**
 * testClassname method
 *
 * @return void
 */
	public function testClassname() {
		$currentApp = Configure::read('App.namespace');
		Configure::write('App.namespace', 'TestApp');

		// Test core
		$this->assertEquals('Cake\Core\App', App::classname('App', 'Core'));
		$this->assertFalse(App::classname('App', 'Core', 'Suffix'));

		// Assert prefix
		$this->assertFalse(App::classname('Auth', 'Controller/Component'));
		$this->assertEquals('Cake\Controller\Component\AuthComponent', App::classname('Auth', 'Controller/Component', 'Component'));

		// Test app
		$this->assertEquals('TestApp\Controller\PagesController', App::classname('Pages', 'Controller', 'Controller'));
		$this->assertFalse(App::classname('Unknown', 'Controller', 'Controller'));

		// Test plugin
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS)
		), App::RESET);
		Plugin::load('TestPlugin');
		$this->assertEquals('TestPlugin\Utility\TestPluginEngine', App::classname('TestPlugin.TestPlugin', 'Utility', 'Engine'));
		$this->assertFalse(App::classname('TestPlugin.Unknown', 'Utility'));

		Plugin::unload('TestPlugin');
		Configure::write('App.namespace', $currentApp);
	}

/**
 * testClassnameUnknownPlugin method
 *
 * @expectedException Cake\Error\MissingPluginException
 * @return void
 */
	public function testClassnameUnknownPlugin() {
		App::classname('UnknownPlugin.Classname', 'Utility');
	}

/**
 * testBuild method
 *
 * @return void
 */
	public function testBuild() {
		$old = App::path('Model');
		$expected = array(
			APP . 'Model' . DS
		);
		$this->assertEquals($expected, $old);

		App::build(array('Model' => array('/path/to/models/')));
		$new = App::path('Model');
		$expected = array(
			'/path/to/models/',
			APP . 'Model' . DS
		);
		$this->assertEquals($expected, $new);

		App::build();
		App::build(array('Model' => array('/path/to/models/')), App::PREPEND);
		$new = App::path('Model');
		$expected = array(
			'/path/to/models/',
			APP . 'Model' . DS
		);
		$this->assertEquals($expected, $new);

		App::build();
		App::build(array('Model' => array('/path/to/models/')), App::APPEND);
		$new = App::path('Model');
		$expected = array(
			APP . 'Model' . DS,
			'/path/to/models/'
		);
		$this->assertEquals($expected, $new);

		App::build();
		App::build(array(
			'Model' => array('/path/to/models/'),
			'Controller' => array('/path/to/controllers/'),
		), App::APPEND);
		$new = App::path('Model');
		$expected = array(
			APP . 'Model' . DS,
			'/path/to/models/'
		);
		$this->assertEquals($expected, $new);
		$new = App::path('Controller');
		$expected = array(
			APP . 'Controller' . DS,
			'/path/to/controllers/'
		);
		$this->assertEquals($expected, $new);

		App::build(); //reset defaults
		$defaults = App::path('Model');
		$this->assertEquals($old, $defaults);
	}

/**
 * tests that it is possible to set up paths using the cake 1.3 notation for them (models, behaviors, controllers...)
 *
 * @return void
 */
	public function testCompatibleBuild() {
		$old = App::path('Model');
		$expected = array(
			APP . 'Model' . DS
		);
		$this->assertEquals($expected, $old);

		App::build(array('Model' => array('/path/to/models/')));

		$expected = array(
			'/path/to/models/',
			APP . 'Model' . DS
		);
		$this->assertEquals($expected, App::path('Model'));

		App::build(array('Model/Datasource' => array('/path/to/datasources/')));
		$expected = array(
			'/path/to/datasources/',
			APP . 'Model' . DS . 'Datasource' . DS
		);
		$this->assertEquals($expected, App::path('Model/Datasource'));

		App::build(array('Model/Behavior' => array('/path/to/behaviors/')));
		$expected = array(
			'/path/to/behaviors/',
			APP . 'Model' . DS . 'Behavior' . DS
		);
		$this->assertEquals($expected, App::path('Model/Behavior'));

		App::build(array('Controller' => array('/path/to/controllers/')));
		$expected = array(
			'/path/to/controllers/',
			APP . 'Controller' . DS
		);
		$this->assertEquals($expected, App::path('Controller'));

		App::build(array('Controller/Component' => array('/path/to/components/')));
		$expected = array(
			'/path/to/components/',
			APP . 'Controller' . DS . 'Component' . DS
		);
		$this->assertEquals($expected, App::path('Controller/Component'));

		App::build(array('View' => array('/path/to/views/')));
		$expected = array(
			'/path/to/views/',
			APP . 'View' . DS
		);
		$this->assertEquals($expected, App::path('View'));

		App::build(array('View/Helper' => array('/path/to/helpers/')));
		$expected = array(
			'/path/to/helpers/',
			APP . 'View' . DS . 'Helper' . DS
		);
		$this->assertEquals($expected, App::path('View/Helper'));

		App::build(array('Console/Command' => array('/path/to/shells/')));
		$expected = array(
			'/path/to/shells/',
			APP . 'Console' . DS . 'Command' . DS
		);
		$this->assertEquals($expected, App::path('Console/Command'));

		App::build(); //reset defaults
		$defaults = App::path('Model');
		$this->assertEquals($old, $defaults);
	}

/**
 * test package build() with App::REGISTER.
 *
 * @return void
 */
	public function testBuildPackage() {
		$pluginPaths = array(
			'/foo/bar',
			ROOT . DS . 'Plugin' . DS,
		);
		App::build(array(
			'Plugin' => array(
				'/foo/bar'
			)
		));
		$result = App::path('Plugin');
		$this->assertEquals($pluginPaths, $result);

		$paths = App::path('Service');
		$this->assertSame(array(), $paths);

		App::build(array(
			'Service' => array(
				'%s' . 'Service' . DS
			),
		), App::REGISTER);

		$expected = array(
			APP . 'Service' . DS
		);
		$result = App::path('Service');
		$this->assertEquals($expected, $result);

		//Ensure new paths registered for other packages are not affected
		$result = App::path('Plugin');
		$this->assertEquals($pluginPaths, $result);

		App::build();
		$paths = App::path('Service');
		$this->assertSame(array(), $paths);
	}

/**
 * test path() with a plugin.
 *
 * @return void
 */
	public function testPathWithPlugins() {
		$basepath = CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS;
		App::build(array(
			'Plugin' => array($basepath),
		));
		Plugin::load('TestPlugin');

		$result = App::path('Controller', 'TestPlugin');
		$this->assertEquals($basepath . 'TestPlugin' . DS . 'Controller' . DS, $result[0]);
	}

/**
 * testBuildWithReset method
 *
 * @return void
 */
	public function testBuildWithReset() {
		$old = App::path('Model');
		$expected = array(
			APP . 'Model' . DS
		);
		$this->assertEquals($expected, $old);

		App::build(array('Model' => array('/path/to/models/')), App::RESET);

		$new = App::path('Model');

		$expected = array(
			'/path/to/models/'
		);
		$this->assertEquals($expected, $new);

		App::build(); //reset defaults
		$defaults = App::path('Model');
		$this->assertEquals($old, $defaults);
	}

/**
 * testCore method
 *
 * @return void
 */
	public function testCore() {
		$model = App::core('Model');
		$this->assertEquals(array(CAKE . 'Model' . DS), $model);

		$view = App::core('View');
		$this->assertEquals(array(CAKE . 'View' . DS), $view);

		$controller = App::core('Controller');
		$this->assertEquals(array(CAKE . 'Controller' . DS), $controller);

		$component = App::core('Controller/Component');
		$this->assertEquals(array(CAKE . 'Controller' . DS . 'Component' . DS), str_replace('/', DS, $component));

		$auth = App::core('Controller/Component/Auth');
		$this->assertEquals(array(CAKE . 'Controller' . DS . 'Component' . DS . 'Auth' . DS), str_replace('/', DS, $auth));

		$datasource = App::core('Model/Datasource');
		$this->assertEquals(array(CAKE . 'Model' . DS . 'Datasource' . DS), str_replace('/', DS, $datasource));
	}

/**
 * testListObjects method
 *
 * @return void
 */
	public function testListObjects() {
		$result = App::objects('class', CAKE . 'Routing', false);
		$this->assertTrue(in_array('Dispatcher', $result));
		$this->assertTrue(in_array('Router', $result));

		App::build(array(
			'Model/Behavior' => App::core('Model/Behavior'),
			'Controller' => App::core('Controller'),
			'Controller/Component' => App::core('Controller/Component'),
			'View' => App::core('View'),
			'Model' => App::core('Model'),
			'View/Helper' => App::core('View/Helper'),
		), App::RESET);
		$result = App::objects('Model/Behavior', null, false);
		$this->assertTrue(in_array('TreeBehavior', $result));

		$result = App::objects('Controller/Component', null, false);
		$this->assertTrue(in_array('AuthComponent', $result));

		$result = App::objects('View', null, false);
		$this->assertTrue(in_array('JsonView', $result));

		$result = App::objects('View/Helper', null, false);
		$this->assertTrue(in_array('HtmlHelper', $result));

		$result = App::objects('Model', null, false);
		$this->assertTrue(in_array('AcoAction', $result));

		$result = App::objects('file');
		$this->assertFalse($result);

		$result = App::objects('file', 'non_existing_configure');
		$expected = array();
		$this->assertEquals($expected, $result);

		$result = App::objects('NonExistingType');
		$this->assertSame(array(), $result);

		App::build(array(
			'Plugin' => array(
				CAKE . 'Test/TestApp/Plugin/'
			)
		));
		$result = App::objects('Plugin', null, false);
		$this->assertContains('TestPlugin', $result);
		$this->assertContains('TestPluginTwo', $result);
	}

/**
 * Make sure that .svn and friends are excluded from App::objects('Plugin')
 */
	public function testListObjectsIgnoreDotDirectories() {
		$path = CAKE . 'Test/TestApp/Plugin/';

		$this->skipIf(!is_writable($path), $path . ' is not writable.');

		App::build(array(
			'Plugin' => array($path)
		), App::RESET);
		mkdir($path . '.svn');
		$result = App::objects('Plugin', null, false);
		rmdir($path . '.svn');

		$this->assertNotContains('.svn', $result);
	}

/**
 * Tests listing objects within a plugin
 *
 * @return void
 */
	public function testListObjectsInPlugin() {
		App::build(array(
			'Model' => array(CAKE . 'Test/TestApp/Model/'),
			'Plugin' => array(CAKE . 'Test/TestApp/Plugin/')
		), App::RESET);
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));

		$result = App::objects('TestPlugin.Model');
		$this->assertTrue(in_array('TestPluginPost', $result));

		$result = App::objects('TestPlugin.Model/Behavior');
		$this->assertTrue(in_array('TestPluginPersisterOneBehavior', $result));

		$result = App::objects('TestPlugin.View/Helper');
		$expected = array('OtherHelperHelper', 'PluggedHelperHelper', 'TestPluginAppHelper');
		$this->assertEquals($expected, $result);

		$result = App::objects('TestPlugin.Controller/Component');
		$this->assertTrue(in_array('OtherComponent', $result));

		$result = App::objects('TestPluginTwo.Model/Behavior');
		$this->assertSame(array(), $result);

		$result = App::objects('Model', null, false);
		$this->assertTrue(in_array('Comment', $result));
		$this->assertTrue(in_array('Post', $result));

		App::build();
	}

/**
 * test that pluginPath can find paths for plugins.
 *
 * @return void
 */
	public function testPluginPath() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS)
		));
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));

		$path = App::pluginPath('TestPlugin');
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS . 'TestPlugin' . DS;
		$this->assertEquals($expected, $path);

		$path = App::pluginPath('TestPluginTwo');
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS . 'TestPluginTwo' . DS;
		$this->assertEquals($expected, $path);
		App::build();
	}

/**
 * test that themePath can find paths for themes.
 *
 * @return void
 */
	public function testThemePath() {
		App::build(array(
			'View' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'View' . DS)
		));
		$path = App::themePath('test_theme');
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS;
		$this->assertEquals($expected, $path);

		$path = App::themePath('TestTheme');
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS;
		$this->assertEquals($expected, $path);

		App::build();
	}

/**
 * Test that paths() works.
 *
 * @return void
 */
	public function testPaths() {
		$result = App::paths();
		$this->assertArrayHasKey('Plugin', $result);
		$this->assertArrayHasKey('Controller', $result);
		$this->assertArrayHasKey('Controller/Component', $result);
	}

}
