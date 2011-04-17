<?php

/**
 * AppImportTest class
 *
 * @package       cake.tests.cases.libs
 */
class AppImportTest extends CakeTestCase {

/**
 * testBuild method
 *
 * @access public
 * @return void
 */
	function testBuild() {
		$old = App::path('Model');
		$expected = array(
			APP . 'Model' . DS,
			APP . 'models' . DS
		);
		$this->assertEqual($expected, $old);

		App::build(array('Model' => array('/path/to/models/')));

		$new = App::path('Model');

		$expected = array(
			'/path/to/models/',
			APP . 'Model' . DS,
			APP . 'models' . DS
		);
		$this->assertEqual($expected, $new);

		App::build(); //reset defaults
		$defaults = App::path('Model');
		$this->assertEqual($old, $defaults);
	}

/**
 * tests that it is possible to set up paths using the cake 1.3 notation for them (models, behaviors, controllers...)
 *
 * @access public
 * @return void
 */
	function testCompatibleBuild() {
		$old = App::path('models');
		$expected = array(
			APP . 'Model' . DS,
			APP . 'models' . DS
		);
		$this->assertEqual($expected, $old);

		App::build(array('models' => array('/path/to/models/')));

		$new = App::path('models');

		$expected = array(
			'/path/to/models/',
			APP . 'Model' . DS,
			APP . 'models' . DS
		);
		$this->assertEqual($expected, $new);
		$this->assertEqual($expected, App::path('Model'));

		App::build(array('datasources' => array('/path/to/datasources/')));
		$expected = array(
			'/path/to/datasources/',
			APP . 'Model' . DS . 'Datasource' . DS,
			APP . 'models' . DS . 'datasources' . DS
		);
		$result = App::path('datasources');
		$this->assertEqual($expected, $result);
		$this->assertEqual($expected, App::path('Model/Datasource'));

		App::build(array('behaviors' => array('/path/to/behaviors/')));
		$expected = array(
			'/path/to/behaviors/',
			APP . 'Model' . DS . 'Behavior' . DS,
			APP . 'models' . DS . 'behaviors' . DS
		);
		$result = App::path('behaviors');
		$this->assertEqual($expected, $result);
		$this->assertEqual($expected, App::path('Model/Behavior'));

		App::build(array('controllers' => array('/path/to/controllers/')));
		$expected = array(
			'/path/to/controllers/',
			APP . 'Controller' . DS,
			APP . 'controllers' . DS
		);
		$result = App::path('controllers');
		$this->assertEqual($expected, $result);
		$this->assertEqual($expected, App::path('Controller'));

		App::build(array('components' => array('/path/to/components/')));
		$expected = array(
			'/path/to/components/',
			APP . 'Controller' . DS . 'Component' . DS,
			APP . 'controllers' . DS . 'components' . DS
		);
		$result = App::path('components');
		$this->assertEqual($expected, $result);
		$this->assertEqual($expected, App::path('Controller/Component'));

		App::build(array('views' => array('/path/to/views/')));
		$expected = array(
			'/path/to/views/',
			APP . 'View' . DS,
			APP . 'views' . DS
		);
		$result = App::path('views');
		$this->assertEqual($expected, $result);
		$this->assertEqual($expected, App::path('View'));

		App::build(array('helpers' => array('/path/to/helpers/')));
		$expected = array(
			'/path/to/helpers/',
			APP . 'View' . DS . 'Helper' .DS,
			APP . 'views' . DS . 'helpers' . DS
		);
		$result = App::path('helpers');
		$this->assertEqual($expected, $result);
		$this->assertEqual($expected, App::path('View/Helper'));

		App::build(array('shells' => array('/path/to/shells/')));
		$expected = array(
			'/path/to/shells/',
			APP . 'Console' . DS . 'Command' . DS,
			APP . 'console' . DS . 'shells' . DS,
		);
		$result = App::path('shells');
		$this->assertEqual($expected, $result);
		$this->assertEqual($expected, App::path('Console/Command'));

		App::build(); //reset defaults
		$defaults = App::path('Model');
		$this->assertEqual($old, $defaults);
	}

/**
 * testBuildWithReset method
 *
 * @access public
 * @return void
 */
	function testBuildWithReset() {
		$old = App::path('Model');
		$expected = array(
			APP . 'Model' . DS,
			APP . 'models' . DS
		);
		$this->assertEqual($expected, $old);

		App::build(array('Model' => array('/path/to/models/')), true);

		$new = App::path('Model');

		$expected = array(
			'/path/to/models/'
		);
		$this->assertEqual($expected, $new);

		App::build(); //reset defaults
		$defaults = App::path('Model');
		$this->assertEqual($old, $defaults);
	}

/**
 * testCore method
 *
 * @access public
 * @return void
 */
	function testCore() {
		$model = App::core('Model');
		$this->assertEqual(array(LIBS . 'Model' . DS), $model);

		$view = App::core('View');
		$this->assertEqual(array(LIBS . 'View' . DS), $view);

		$controller = App::core('Controller');
		$this->assertEqual(array(LIBS . 'Controller' . DS), $controller);

		$component = App::core('Controller/Component');
		$this->assertEqual(array(LIBS . 'Controller' . DS . 'Component' . DS), $component);

		$auth = App::core('Controller/Component/Auth');
		$this->assertEqual(array(LIBS . 'Controller' . DS . 'Component' . DS . 'Auth' . DS), $auth);

		$datasource = App::core('Model/Datasource');
		$this->assertEqual(array(LIBS . 'Model' . DS . 'Datasource' . DS), $datasource);
	}

/**
 * testListObjects method
 *
 * @access public
 * @return void
 */
	function testListObjects() {
		$result = App::objects('class',  LIBS . 'Routing', false);
		$this->assertTrue(in_array('Dispatcher', $result));
		$this->assertTrue(in_array('Router', $result));

		App::build(array(
			'Model/Behavior' => App::core('Model/Behavior'),
			'Controller' => App::core('Controller'),
			'Controller/Component' => App::core('Controller/Component'),
			'View' => App::core('View'),
			'Model' => App::core('Model'),
			'View/Helper' => App::core('View/Helper'),
		), true);
		$result = App::objects('behavior', null, false);
		$this->assertTrue(in_array('TreeBehavior', $result));
		$result = App::objects('Model/Behavior', null, false);
		$this->assertTrue(in_array('TreeBehavior', $result));

		$result = App::objects('controller', null, false);
		$this->assertTrue(in_array('PagesController', $result));
		$result = App::objects('Controller', null, false);
		$this->assertTrue(in_array('PagesController', $result));

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
		$this->assertEqual($result, $expected);

		$result = App::objects('NonExistingType');
		$this->assertEqual($result, array());

		App::build(array(
			'plugins' => array(
				LIBS . 'tests' . DS . 'test_app' . DS . 'Lib' . DS
			)
		));
		$result = App::objects('plugin', null, false);
		$this->assertTrue(in_array('Cache', $result));
		$this->assertTrue(in_array('Log', $result));

		App::build();
	}

/**
 * Tests listing objects within a plugin
 *
 * @return void
 */
	function testListObjectsInPlugin() {
		App::build(array(
			'Model' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'Model' . DS),
			'plugins' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));

		$result = App::objects('TestPlugin.model');
		$this->assertTrue(in_array('TestPluginPost', $result));
		$result = App::objects('TestPlugin.Model');
		$this->assertTrue(in_array('TestPluginPost', $result));

		$result = App::objects('TestPlugin.behavior');
		$this->assertTrue(in_array('TestPluginPersisterOne', $result));
		$result = App::objects('TestPlugin.Model/Behavior');
		$this->assertTrue(in_array('TestPluginPersisterOne', $result));

		$result = App::objects('TestPlugin.helper');
		$expected = array('OtherHelperHelper', 'PluggedHelper', 'TestPluginApp');
		$this->assertEquals($result, $expected);
		$result = App::objects('TestPlugin.View/Helper');
		$expected = array('OtherHelperHelper', 'PluggedHelper', 'TestPluginApp');
		$this->assertEquals($result, $expected);

		$result = App::objects('TestPlugin.component');
		$this->assertTrue(in_array('OtherComponent', $result));
		$result = App::objects('TestPlugin.Controller/Component');
		$this->assertTrue(in_array('OtherComponent', $result));

		$result = App::objects('TestPluginTwo.behavior');
		$this->assertEquals($result, array());
		$result = App::objects('TestPluginTwo.Model/Behavior');
		$this->assertEquals($result, array());

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
	function testPluginPath() {
		App::build(array(
			'plugins' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
		$path = App::pluginPath('test_plugin');
		$expected = LIBS . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' . DS;
		$this->assertEqual($path, $expected);

		$path = App::pluginPath('TestPlugin');
		$expected = LIBS . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' . DS;
		$this->assertEqual($path, $expected);

		$path = App::pluginPath('TestPluginTwo');
		$expected = LIBS . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin_two' . DS;
		$this->assertEqual($path, $expected);
		App::build();
	}

/**
 * test that pluginPath can find paths for plugins.
 *
 * @return void
 */
	function testThemePath() {
		App::build(array(
			'View' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'views' . DS)
		));
		$path = App::themePath('test_theme');
		$expected = LIBS . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS;
		$this->assertEqual($path, $expected);

		$path = App::themePath('TestTheme');
		$expected = LIBS . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS;
		$this->assertEqual($path, $expected);

		App::build();
	}

/**
 * testClassLoading method
 *
 * @access public
 * @return void
 */
	function testClassLoading() {
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

		if (!class_exists('AppController')) {
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
	function testPluginImporting() {
		App::build(array(
			'libs' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'Lib' . DS),
			'plugins' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));

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
		
		App::build();
	}

/**
 * test that building helper paths actually works.
 *
 * @return void
 * @link http://cakephp.lighthouseapp.com/projects/42648/tickets/410
 */
	function testImportingHelpersFromAlternatePaths() {

		$this->assertFalse(class_exists('BananaHelper', false), 'BananaHelper exists, cannot test importing it.');
		App::build(array(
			'View/Helper' => array(
				LIBS . 'tests' . DS . 'test_app' . DS . 'View' . DS . 'Helper' . DS
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
 * @access public
 * @return void
 */
	function testFileLoading () {
		$file = App::import('File', 'RealFile', false, array(), LIBS  . 'config' . DS . 'config.php');
		$this->assertTrue($file);

		$file = App::import('File', 'NoFile', false, array(), LIBS  . 'config' . DS . 'cake' . DS . 'config.php');
		$this->assertFalse($file);
	}

/**
 * testFileLoadingWithArray method
 *
 * @access public
 * @return void
 */
	function testFileLoadingWithArray() {
		$type = array('type' => 'File', 'name' => 'SomeName', 'parent' => false,
				'file' => LIBS  . DS . 'config' . DS . 'config.php');
		$file = App::import($type);
		$this->assertTrue($file);

		$type = array('type' => 'File', 'name' => 'NoFile', 'parent' => false,
				'file' => LIBS  . 'config' . DS . 'cake' . DS . 'config.php');
		$file = App::import($type);
		$this->assertFalse($file);
	}

/**
 * testFileLoadingReturnValue method
 *
 * @access public
 * @return void
 */
	function testFileLoadingReturnValue () {
		$file = App::import('File', 'Name', false, array(), LIBS  . 'config' . DS . 'config.php', true);
		$this->assertTrue(!empty($file));

		$this->assertTrue(isset($file['Cake.version']));

		$type = array('type' => 'File', 'name' => 'OtherName', 'parent' => false,
				'file' => LIBS  . 'config' . DS . 'config.php', 'return' => true);
		$file = App::import($type);
		$this->assertTrue(!empty($file));

		$this->assertTrue(isset($file['Cake.version']));
	}

/**
 * testLoadingWithSearch method
 *
 * @access public
 * @return void
 */
	function testLoadingWithSearch () {
		$file = App::import('File', 'NewName', false, array(LIBS . 'config' . DS), 'config.php');
		$this->assertTrue($file);

		$file = App::import('File', 'AnotherNewName', false, array(LIBS), 'config.php');
		$this->assertFalse($file);
	}

/**
 * testLoadingWithSearchArray method
 *
 * @access public
 * @return void
 */
	function testLoadingWithSearchArray() {
		$type = array(
			'type' => 'File',
			'name' => 'RandomName',
			'parent' => false,
			'file' => 'config.php',
			'search' => array(LIBS . 'config' . DS)
		);
		$file = App::import($type);
		$this->assertTrue($file);

		$type = array(
			'type' => 'File',
			'name' => 'AnotherRandomName',
			'parent' => false,
			'file' => 'config.php',
			'search' => array(LIBS)
		);
		$file = App::import($type);
		$this->assertFalse($file);
	}

/**
 * testMultipleLoading method
 *
 * @access public
 * @return void
 */
	function testMultipleLoading() {
		if (class_exists('PersisterOne', false) || class_exists('PersisterTwo', false)) {
			$this->markTestSkipped('Cannot test loading of classes that exist.');
		}
		App::build(array(
			'Model' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'Model' . DS)
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


	function testLoadingVendor() {
		App::build(array(
			'plugins' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'vendors' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'vendors'. DS),
		), true);

		ob_start();
		$result = App::import('Vendor', 'css/TestAsset', array('ext' => 'css'));
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEqual($text, 'this is the test asset css file');

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
		$this->assertEqual($text, 'This is a file with dot in file name');

		ob_start();
		$result = App::import('Vendor', 'TestHello', array('file' => 'Test'.DS.'hello.php'));
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEqual($text, 'This is the hello.php file in Test directory');

		ob_start();
		$result = App::import('Vendor', 'MyTest', array('file' => 'Test'.DS.'MyTest.php'));
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEqual($text, 'This is the MyTest.php file');

		ob_start();
		$result = App::import('Vendor', 'Welcome');
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEqual($text, 'This is the welcome.php file in vendors directory');

		ob_start();
		$result = App::import('Vendor', 'TestPlugin.Welcome');
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEqual($text, 'This is the welcome.php file in test_plugin/vendors directory');
	}

/**
 * Tests that the automatic class loader will also find in "libs" folder for both
 * app and plugins if it does not find the class in other configured paths
 *
 */
	public function testLoadClassInLibs() {
		App::build(array(
			'libs' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'libs' . DS),
			'plugins' => array(LIBS . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		), true);

		$this->assertFalse(class_exists('CustomLibClass', false));
		App::uses('CustomLibClass', 'TestPlugin.Custom/Package');
		$this->assertTrue(class_exists('CustomLibClass'));

		$this->assertFalse(class_exists('TestUtilityClass', false));
		App::uses('TestUtilityClass', 'Utility');
		$this->assertTrue(class_exists('CustomLibClass'));
	}
}
