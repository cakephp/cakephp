<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use TestApp\Core\TestApp;

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
 * testClassname
 *
 * $checkCake and $existsInCake are derived from the input parameters
 *
 * @dataProvider classnameProvider
 * @return void
 */
	public function testClassname($class, $type, $suffix = '', $existsInBase = false, $expected = false) {
		Configure::write('App.namespace', 'TestApp');
		$i = 0;
		TestApp::$existsInBaseCallback = function($name, $namespace) use ($existsInBase, $class, $expected, &$i) {
			if ($i++ === 0) {
				return $existsInBase;
			}
			$checkCake = (!$existsInBase || strpos('.', $class));
			if ($checkCake) {
				return (bool)$expected;
			}
			return false;
		};
		$return = TestApp::classname($class, $type, $suffix);
		$this->assertSame($expected, $return);
	}

/**
 * classnameProvider
 *
 * Return test permutations for testClassname method. Format:
 * 	classname
 *	type
 *	suffix
 *	existsInBase (Base meaning App or plugin namespace)
 * 	expected return value
 *
 * @return void
 */
	public function classnameProvider() {
		return [
			['Does', 'Not', 'Exist'],

			['Exists', 'In', 'App', true, 'TestApp\In\ExistsApp'],
			['Also/Exists', 'In', 'App', true, 'TestApp\In\Also\ExistsApp'],
			['Also', 'Exists/In', 'App', true, 'TestApp\Exists\In\AlsoApp'],
			['Also', 'Exists/In/Subfolder', 'App', true, 'TestApp\Exists\In\Subfolder\AlsoApp'],
			['No', 'Suffix', '', true, 'TestApp\Suffix\No'],

			['MyPlugin.Exists', 'In', 'Suffix', true, 'MyPlugin\In\ExistsSuffix'],
			['MyPlugin.Also/Exists', 'In', 'Suffix', true, 'MyPlugin\In\Also\ExistsSuffix'],
			['MyPlugin.Also', 'Exists/In', 'Suffix', true, 'MyPlugin\Exists\In\AlsoSuffix'],
			['MyPlugin.Also', 'Exists/In/Subfolder', 'Suffix', true, 'MyPlugin\Exists\In\Subfolder\AlsoSuffix'],
			['MyPlugin.No', 'Suffix', '', true, 'MyPlugin\Suffix\No'],

			['Exists', 'In', 'Cake', false, 'Cake\In\ExistsCake'],
			['Also/Exists', 'In', 'Cake', false, 'Cake\In\Also\ExistsCake'],
			['Also', 'Exists/In', 'Cake', false, 'Cake\Exists\In\AlsoCake'],
			['Also', 'Exists/In/Subfolder', 'Cake', false, 'Cake\Exists\In\Subfolder\AlsoCake'],
			['No', 'Suffix', '', false, 'Cake\Suffix\No'],

			// Realistic examples returning nothing
			['App', 'Core', 'Suffix'],
			['Auth', 'Controller/Component'],
			['Unknown', 'Controller', 'Controller'],

			// Real examples returning classnames
			['App', 'Core', '', false, 'Cake\Core\App'],
			['Auth', 'Controller/Component', 'Component', false, 'Cake\Controller\Component\AuthComponent'],
			['File', 'Cache/Engine', 'Engine', false, 'Cake\Cache\Engine\FileEngine'],
			['Command', 'Console/Command/Task', 'Task', false, 'Cake\Console\Command\Task\CommandTask'],
			['Upgrade/Locations', 'Console/Command/Task', 'Task', false, 'Cake\Console\Command\Task\Upgrade\LocationsTask'],
			['Pages', 'Controller', 'Controller', true, 'TestApp\Controller\PagesController'],
		];
	}

/**
 * test path() with a plugin.
 *
 * @return void
 */
	public function testPathWithPlugins() {
		$basepath = TEST_APP . 'Plugin' . DS;
		Plugin::load('TestPlugin');

		$result = App::path('Controller', 'TestPlugin');
		$this->assertPathEquals($basepath . 'TestPlugin' . DS . 'Controller' . DS, $result[0]);
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

		$result = App::objects('Model/Table', null, false);
		$this->assertContains('ArticlesTable', $result);

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
		$path = TEST_APP . 'Plugin/';

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

		$result = App::objects('TestPlugin.Model/Table');
		$this->assertContains('TestPluginCommentsTable', $result);

		$result = App::objects('TestPlugin.Model/Behavior');
		$this->assertTrue(in_array('PersisterOneBehavior', $result));

		$result = App::objects('TestPlugin.View/Helper');
		$expected = array('OtherHelperHelper', 'PluggedHelperHelper', 'TestPluginAppHelper');
		$this->assertEquals($expected, $result);

		$result = App::objects('TestPlugin.Controller/Component');
		$this->assertTrue(in_array('OtherComponent', $result));

		$result = App::objects('TestPluginTwo.Model/Behavior');
		$this->assertSame(array(), $result);

		$result = App::objects('Model/Table', null, false);
		$this->assertContains('PostsTable', $result);
		$this->assertContains('ArticlesTable', $result);
	}

/**
 * test that pluginPath can find paths for plugins.
 *
 * @return void
 */
	public function testPluginPath() {
		Plugin::load(array('TestPlugin', 'TestPluginTwo'));

		$path = App::pluginPath('TestPlugin');
		$expected = TEST_APP . 'Plugin' . DS . 'TestPlugin' . DS;
		$this->assertPathEquals($expected, $path);

		$path = App::pluginPath('TestPluginTwo');
		$expected = TEST_APP . 'Plugin' . DS . 'TestPluginTwo' . DS;
		$this->assertPathEquals($expected, $path);
	}

/**
 * test that themePath can find paths for themes.
 *
 * @return void
 */
	public function testThemePath() {
		$path = App::themePath('test_theme');
		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Themed' . DS . 'TestTheme' . DS;
		$this->assertPathEquals($expected, $path);

		$path = App::themePath('TestTheme');
		$expected = TEST_APP . 'TestApp' . DS . 'Template' . DS . 'Themed' . DS . 'TestTheme' . DS;
		$this->assertPathEquals($expected, $path);
	}

}
