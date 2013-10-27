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
		Plugin::load('TestPlugin');
		$this->assertEquals('TestPlugin\Utility\TestPluginEngine', App::classname('TestPlugin.TestPlugin', 'Utility', 'Engine'));
		$this->assertFalse(App::classname('TestPlugin.Unknown', 'Utility'));

		$this->assertFalse(Plugin::loaded('TestPluginTwo'), 'TestPluginTwo should not be loaded.');
		// Test unknown plugin
		$this->assertEquals(
			'TestPluginTwo\Console\Command\ExampleShell',
			App::classname('TestPluginTwo.Example', 'Console/Command', 'Shell')
		);
	}

/**
 * test path() with a plugin.
 *
 * @return void
 */
	public function testPathWithPlugins() {
		$basepath = CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS;
		Plugin::load('TestPlugin');

		$result = App::path('Controller', 'TestPlugin');
		$this->assertEquals($basepath . 'TestPlugin' . DS . 'Controller' . DS, $result[0]);
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

		$result = App::objects('Model/Behavior', null, false);
		$this->assertContains('SluggableBehavior', $result);

		$result = App::objects('Controller/Component', null, false);
		$this->assertContains('AppleComponent', $result);

		$result = App::objects('View', null, false);
		$this->assertContains('CustomJsonView', $result);

		$result = App::objects('View/Helper', null, false);
		$this->assertContains('BananaHelper', $result);

		$result = App::objects('Model', null, false);
		$this->assertContains('Article', $result);

		$result = App::objects('file');
		$this->assertFalse($result);

		$result = App::objects('file', 'non_existing_configure');
		$expected = array();
		$this->assertEquals($expected, $result);

		$result = App::objects('NonExistingType');
		$this->assertSame(array(), $result);

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
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));

		$result = App::objects('TestPlugin.Model');
		$this->assertTrue(in_array('TestPluginPost', $result));

		$result = App::objects('TestPlugin.Model/Behavior');
		$this->assertTrue(in_array('PersisterOneBehavior', $result));

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
	}

/**
 * test that pluginPath can find paths for plugins.
 *
 * @return void
 */
	public function testPluginPath() {
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));

		$path = App::pluginPath('TestPlugin');
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS . 'TestPlugin' . DS;
		$this->assertEquals($expected, $path);

		$path = App::pluginPath('TestPluginTwo');
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS . 'TestPluginTwo' . DS;
		$this->assertEquals($expected, $path);
	}

/**
 * test that themePath can find paths for themes.
 *
 * @return void
 */
	public function testThemePath() {
		$path = App::themePath('test_theme');
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS;
		$this->assertEquals($expected, $path);

		$path = App::themePath('TestTheme');
		$expected = CAKE . 'Test' . DS . 'TestApp' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS;
		$this->assertEquals($expected, $path);
	}

}
