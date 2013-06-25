<?php
/**
 * AppTest file.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Core
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * AppTest class
 *
 * @package       Cake.Test.Case.Core
 */
class AppTest extends CakeTestCase {

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		CakePlugin::unload();
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
 * tests that it is possible to set up paths using the CakePHP 1.3 notation for them (models, behaviors, controllers...)
 *
 * @return void
 */
	public function testCompatibleBuild() {
		$old = App::path('models');
		$expected = array(
			APP . 'Model' . DS
		);
		$this->assertEquals($expected, $old);

		App::build(array('models' => array('/path/to/models/')));

		$new = App::path('models');

		$expected = array(
			'/path/to/models/',
			APP . 'Model' . DS
		);
		$this->assertEquals($expected, $new);
		$this->assertEquals($expected, App::path('Model'));

		App::build(array('datasources' => array('/path/to/datasources/')));
		$expected = array(
			'/path/to/datasources/',
			APP . 'Model' . DS . 'Datasource' . DS
		);
		$result = App::path('datasources');
		$this->assertEquals($expected, $result);
		$this->assertEquals($expected, App::path('Model/Datasource'));

		App::build(array('behaviors' => array('/path/to/behaviors/')));
		$expected = array(
			'/path/to/behaviors/',
			APP . 'Model' . DS . 'Behavior' . DS
		);
		$result = App::path('behaviors');
		$this->assertEquals($expected, $result);
		$this->assertEquals($expected, App::path('Model/Behavior'));

		App::build(array('controllers' => array('/path/to/controllers/')));
		$expected = array(
			'/path/to/controllers/',
			APP . 'Controller' . DS
		);
		$result = App::path('controllers');
		$this->assertEquals($expected, $result);
		$this->assertEquals($expected, App::path('Controller'));

		App::build(array('components' => array('/path/to/components/')));
		$expected = array(
			'/path/to/components/',
			APP . 'Controller' . DS . 'Component' . DS
		);
		$result = App::path('components');
		$this->assertEquals($expected, $result);
		$this->assertEquals($expected, App::path('Controller/Component'));

		App::build(array('views' => array('/path/to/views/')));
		$expected = array(
			'/path/to/views/',
			APP . 'View' . DS
		);
		$result = App::path('views');
		$this->assertEquals($expected, $result);
		$this->assertEquals($expected, App::path('View'));

		App::build(array('helpers' => array('/path/to/helpers/')));
		$expected = array(
			'/path/to/helpers/',
			APP . 'View' . DS . 'Helper' . DS
		);
		$result = App::path('helpers');
		$this->assertEquals($expected, $result);
		$this->assertEquals($expected, App::path('View/Helper'));

		App::build(array('shells' => array('/path/to/shells/')));
		$expected = array(
			'/path/to/shells/',
			APP . 'Console' . DS . 'Command' . DS
		);
		$result = App::path('shells');
		$this->assertEquals($expected, $result);
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
			APP . 'Plugin' . DS,
			dirname(dirname(CAKE)) . DS . 'plugins' . DS
		);
		App::build(array(
			'Plugin' => array(
				'/foo/bar'
			)
		));
		$result = App::path('Plugin');
		$this->assertEquals($pluginPaths, $result);

		$paths = App::path('Service');
		$this->assertEquals(array(), $paths);

		App::build(array(
			'Service' => array(
				'%s' . 'Service' . DS,
			),
		), App::REGISTER);

		$expected = array(
			APP . 'Service' . DS,
		);
		$result = App::path('Service');
		$this->assertEquals($expected, $result);

		//Ensure new paths registered for other packages are not affected
		$result = App::path('Plugin');
		$this->assertEquals($pluginPaths, $result);

		App::build();
		$paths = App::path('Service');
		$this->assertEquals(array(), $paths);
	}

/**
 * test path() with a plugin.
 *
 * @return void
 */
	public function testPathWithPlugins() {
		$basepath = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS;
		App::build(array(
			'Plugin' => array($basepath),
		));
		CakePlugin::load('TestPlugin');

		$result = App::path('Vendor', 'TestPlugin');
		$this->assertEquals($basepath . 'TestPlugin' . DS . 'Vendor' . DS, $result[0]);
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
		$result = App::objects('behavior', null, false);
		$this->assertTrue(in_array('TreeBehavior', $result));
		$result = App::objects('Model/Behavior', null, false);
		$this->assertTrue(in_array('TreeBehavior', $result));

		$result = App::objects('component', null, false);
		$this->assertTrue(in_array('AuthComponent', $result));
		$result = App::objects('Controller/Component', null, false);
		$this->assertTrue(in_array('AuthComponent', $result));

		$result = App::objects('view', null, false);
		$this->assertTrue(in_array('MediaView', $result));
		$result = App::objects('View', null, false);
		$this->assertTrue(in_array('MediaView', $result));

		$result = App::objects('helper', null, false);
		$this->assertTrue(in_array('HtmlHelper', $result));
		$result = App::objects('View/Helper', null, false);
		$this->assertTrue(in_array('HtmlHelper', $result));

		$result = App::objects('model', null, false);
		$this->assertTrue(in_array('AcoAction', $result));
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
			'plugins' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'Lib' . DS
			)
		));
		$result = App::objects('plugin', null, false);
		$this->assertTrue(in_array('Cache', $result));
		$this->assertTrue(in_array('Log', $result));

		App::build();
	}

/**
 * Make sure that .svn and friends are excluded from App::objects('plugin')
 */
	public function testListObjectsIgnoreDotDirectories() {
		$path = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS;

		$this->skipIf(!is_writable($path), $path . ' is not writable.');

		App::build(array(
			'plugins' => array($path)
		), App::RESET);
		mkdir($path . '.svn');
		$result = App::objects('plugin', null, false);
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
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS),
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));

		$result = App::objects('TestPlugin.model');
		$this->assertTrue(in_array('TestPluginPost', $result));
		$result = App::objects('TestPlugin.Model');
		$this->assertTrue(in_array('TestPluginPost', $result));

		$result = App::objects('TestPlugin.behavior');
		$this->assertTrue(in_array('TestPluginPersisterOneBehavior', $result));
		$result = App::objects('TestPlugin.Model/Behavior');
		$this->assertTrue(in_array('TestPluginPersisterOneBehavior', $result));

		$result = App::objects('TestPlugin.helper');
		$expected = array('OtherHelperHelper', 'PluggedHelperHelper', 'TestPluginAppHelper');
		$this->assertEquals($expected, $result);
		$result = App::objects('TestPlugin.View/Helper');
		$expected = array('OtherHelperHelper', 'PluggedHelperHelper', 'TestPluginAppHelper');
		$this->assertEquals($expected, $result);

		$result = App::objects('TestPlugin.component');
		$this->assertTrue(in_array('OtherComponent', $result));
		$result = App::objects('TestPlugin.Controller/Component');
		$this->assertTrue(in_array('OtherComponent', $result));

		$result = App::objects('TestPluginTwo.behavior');
		$this->assertSame(array(), $result);
		$result = App::objects('TestPluginTwo.Model/Behavior');
		$this->assertSame(array(), $result);

		$result = App::objects('model', null, false);
		$this->assertTrue(in_array('Comment', $result));
		$this->assertTrue(in_array('Post', $result));

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
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));

		$path = App::pluginPath('TestPlugin');
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin' . DS;
		$this->assertEquals($expected, $path);

		$path = App::pluginPath('TestPluginTwo');
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPluginTwo' . DS;
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
			'View' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS)
		));
		$path = App::themePath('test_theme');
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS;
		$this->assertEquals($expected, $path);

		$path = App::themePath('TestTheme');
		$expected = CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Themed' . DS . 'TestTheme' . DS;
		$this->assertEquals($expected, $path);

		App::build();
	}

/**
 * testClassLoading method
 *
 * @return void
 */
	public function testClassLoading() {
		$file = App::import('Model', 'Model', false);
		$this->assertTrue($file);
		$this->assertTrue(class_exists('Model'));

		$file = App::import('Controller', 'Controller', false);
		$this->assertTrue($file);
		$this->assertTrue(class_exists('Controller'));

		$file = App::import('Component', 'Auth', false);
		$this->assertTrue($file);
		$this->assertTrue(class_exists('AuthComponent'));

		$file = App::import('Shell', 'Shell', false);
		$this->assertTrue($file);
		$this->assertTrue(class_exists('Shell'));

		$file = App::import('Configure', 'PhpReader');
		$this->assertTrue($file);
		$this->assertTrue(class_exists('PhpReader'));

		$file = App::import('Model', 'SomeRandomModelThatDoesNotExist', false);
		$this->assertFalse($file);

		$file = App::import('Model', 'AppModel', false);
		$this->assertTrue($file);
		$this->assertTrue(class_exists('AppModel'));

		$file = App::import('WrongType', null, true, array(), '');
		$this->assertFalse($file);

		$file = App::import('Model', 'NonExistingPlugin.NonExistingModel', false);
		$this->assertFalse($file);

		$file = App::import('Model', array('NonExistingPlugin.NonExistingModel'), false);
		$this->assertFalse($file);

		if (!class_exists('AppController', false)) {
			$classes = array_flip(get_declared_classes());

			$this->assertFalse(isset($classes['PagesController']));
			$this->assertFalse(isset($classes['AppController']));

			$file = App::import('Controller', 'Pages');
			$this->assertTrue($file);
			$this->assertTrue(class_exists('PagesController'));

			$classes = array_flip(get_declared_classes());

			$this->assertTrue(isset($classes['PagesController']));
			$this->assertTrue(isset($classes['AppController']));

			$file = App::import('Behavior', 'Containable');
			$this->assertTrue($file);
			$this->assertTrue(class_exists('ContainableBehavior'));

			$file = App::import('Component', 'RequestHandler');
			$this->assertTrue($file);
			$this->assertTrue(class_exists('RequestHandlerComponent'));

			$file = App::import('Helper', 'Form');
			$this->assertTrue($file);
			$this->assertTrue(class_exists('FormHelper'));

			$file = App::import('Model', 'NonExistingModel');
			$this->assertFalse($file);

			$file = App::import('Datasource', 'DboSource');
			$this->assertTrue($file);
			$this->assertTrue(class_exists('DboSource'));
		}
		App::build();
	}

/**
 * test import() with plugins
 *
 * @return void
 */
	public function testPluginImporting() {
		App::build(array(
			'Lib' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Lib' . DS),
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		));
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));

		$result = App::import('Controller', 'TestPlugin.Tests');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('TestPluginAppController'));
		$this->assertTrue(class_exists('TestsController'));

		$result = App::import('Lib', 'TestPlugin.TestPluginLibrary');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('TestPluginLibrary'));

		$result = App::import('Lib', 'Library');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('Library'));

		$result = App::import('Helper', 'TestPlugin.OtherHelper');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('OtherHelperHelper'));

		$result = App::import('Helper', 'TestPlugin.TestPluginApp');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('TestPluginAppHelper'));

		$result = App::import('Datasource', 'TestPlugin.TestSource');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('TestSource'));

		App::uses('ExampleExample', 'TestPlugin.Vendor/Example');
		$this->assertTrue(class_exists('ExampleExample'));

		App::build();
	}

/**
 * test that building helper paths actually works.
 *
 * @return void
 * @link https://cakephp.lighthouseapp.com/projects/42648/tickets/410
 */
	public function testImportingHelpersFromAlternatePaths() {
		$this->assertFalse(class_exists('BananaHelper', false), 'BananaHelper exists, cannot test importing it.');
		App::build(array(
			'View/Helper' => array(
				CAKE . 'Test' . DS . 'test_app' . DS . 'View' . DS . 'Helper' . DS
			)
		));
		$this->assertFalse(class_exists('BananaHelper', false), 'BananaHelper exists, cannot test importing it.');
		App::import('Helper', 'Banana');
		$this->assertTrue(class_exists('BananaHelper', false), 'BananaHelper was not loaded.');

		App::build();
	}

/**
 * testFileLoading method
 *
 * @return void
 */
	public function testFileLoading() {
		$file = App::import('File', 'RealFile', false, array(), CAKE . 'Config' . DS . 'config.php');
		$this->assertTrue($file);

		$file = App::import('File', 'NoFile', false, array(), CAKE . 'Config' . DS . 'cake' . DS . 'config.php');
		$this->assertFalse($file);
	}

/**
 * testFileLoadingWithArray method
 *
 * @return void
 */
	public function testFileLoadingWithArray() {
		$type = array(
			'type' => 'File',
			'name' => 'SomeName',
			'parent' => false,
			'file' => CAKE . DS . 'Config' . DS . 'config.php'
		);
		$file = App::import($type);
		$this->assertTrue($file);

		$type = array(
			'type' => 'File',
			'name' => 'NoFile',
			'parent' => false,
			'file' => CAKE . 'Config' . DS . 'cake' . DS . 'config.php'
		);
		$file = App::import($type);
		$this->assertFalse($file);
	}

/**
 * testFileLoadingReturnValue method
 *
 * @return void
 */
	public function testFileLoadingReturnValue() {
		$file = App::import('File', 'Name', false, array(), CAKE . 'Config' . DS . 'config.php', true);
		$this->assertTrue(!empty($file));

		$this->assertTrue(isset($file['Cake.version']));

		$type = array(
			'type' => 'File',
			'name' => 'OtherName',
			'parent' => false,
			'file' => CAKE . 'Config' . DS . 'config.php', 'return' => true
		);
		$file = App::import($type);
		$this->assertTrue(!empty($file));

		$this->assertTrue(isset($file['Cake.version']));
	}

/**
 * testLoadingWithSearch method
 *
 * @return void
 */
	public function testLoadingWithSearch() {
		$file = App::import('File', 'NewName', false, array(CAKE . 'Config' . DS), 'config.php');
		$this->assertTrue($file);

		$file = App::import('File', 'AnotherNewName', false, array(CAKE), 'config.php');
		$this->assertFalse($file);
	}

/**
 * testLoadingWithSearchArray method
 *
 * @return void
 */
	public function testLoadingWithSearchArray() {
		$type = array(
			'type' => 'File',
			'name' => 'RandomName',
			'parent' => false,
			'file' => 'config.php',
			'search' => array(CAKE . 'Config' . DS)
		);
		$file = App::import($type);
		$this->assertTrue($file);

		$type = array(
			'type' => 'File',
			'name' => 'AnotherRandomName',
			'parent' => false,
			'file' => 'config.php',
			'search' => array(CAKE)
		);
		$file = App::import($type);
		$this->assertFalse($file);
	}

/**
 * testMultipleLoading method
 *
 * @return void
 */
	public function testMultipleLoading() {
		if (class_exists('PersisterOne', false) || class_exists('PersisterTwo', false)) {
			$this->markTestSkipped('Cannot test loading of classes that exist.');
		}
		App::build(array(
			'Model' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Model' . DS)
		));
		$toLoad = array('PersisterOne', 'PersisterTwo');
		$load = App::import('Model', $toLoad);
		$this->assertTrue($load);

		$classes = array_flip(get_declared_classes());

		$this->assertTrue(isset($classes['PersisterOne']));
		$this->assertTrue(isset($classes['PersisterTwo']));

		$load = App::import('Model', array('PersisterOne', 'SomeNotFoundClass', 'PersisterTwo'));
		$this->assertFalse($load);
	}

	public function testLoadingVendor() {
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
			'vendors' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Vendor' . DS),
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));

		ob_start();
		$result = App::import('Vendor', 'css/TestAsset', array('ext' => 'css'));
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEquals('/* this is the test asset css file */', trim($text));

		$result = App::import('Vendor', 'TestPlugin.sample/SamplePlugin');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('SamplePluginClassTestName'));

		$result = App::import('Vendor', 'sample/ConfigureTestVendorSample');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('ConfigureTestVendorSample'));

		ob_start();
		$result = App::import('Vendor', 'SomeNameInSubfolder', array('file' => 'somename/some.name.php'));
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEquals('This is a file with dot in file name', $text);

		ob_start();
		$result = App::import('Vendor', 'TestHello', array('file' => 'Test' . DS . 'hello.php'));
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEquals('This is the hello.php file in Test directory', $text);

		ob_start();
		$result = App::import('Vendor', 'MyTest', array('file' => 'Test' . DS . 'MyTest.php'));
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEquals('This is the MyTest.php file', $text);

		ob_start();
		$result = App::import('Vendor', 'Welcome');
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEquals('This is the welcome.php file in vendors directory', $text);

		ob_start();
		$result = App::import('Vendor', 'TestPlugin.Welcome');
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEquals('This is the welcome.php file in test_plugin/vendors directory', $text);
	}

/**
 * Tests that the automatic class loader will also find in "libs" folder for both
 * app and plugins if it does not find the class in other configured paths
 *
 */
	public function testLoadClassInLibs() {
		App::build(array(
			'libs' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Lib' . DS),
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));

		$this->assertFalse(class_exists('CustomLibClass', false));
		App::uses('CustomLibClass', 'TestPlugin.Custom/Package');
		$this->assertTrue(class_exists('CustomLibClass'));

		$this->assertFalse(class_exists('TestUtilityClass', false));
		App::uses('TestUtilityClass', 'Utility');
		$this->assertTrue(class_exists('TestUtilityClass'));
	}

/**
 * Tests that  App::location() returns the defined path for a class
 *
 * @return void
 */
	public function testClassLocation() {
		App::uses('MyCustomClass', 'MyPackage/Name');
		$this->assertEquals('MyPackage/Name', App::location('MyCustomClass'));
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

/**
 * Proves that it is possible to load plugin libraries in top
 * level Lib dir for plugins
 *
 * @return void
 */
	public function testPluginLibClasses() {
		App::build(array(
			'plugins' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)
		), App::RESET);
		CakePlugin::load(array('TestPlugin', 'TestPluginTwo'));
		$this->assertFalse(class_exists('TestPluginOtherLibrary', false));
		App::uses('TestPluginOtherLibrary', 'TestPlugin.Lib');
		$this->assertTrue(class_exists('TestPluginOtherLibrary'));
	}
}
