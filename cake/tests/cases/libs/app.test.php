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
		$old = App::path('models');
		$expected = array(
			APP . 'models' . DS,
			APP,
			LIBS . 'model' . DS
		);
		$this->assertEqual($expected, $old);

		App::build(array('models' => array('/path/to/models/')));

		$new = App::path('models');

		$expected = array(
			'/path/to/models/',
			APP . 'models' . DS,
			APP,
			LIBS . 'model' . DS
		);
		$this->assertEqual($expected, $new);

		App::build(); //reset defaults
		$defaults = App::path('models');
		$this->assertEqual($old, $defaults);
	}

/**
 * testBuildWithReset method
 *
 * @access public
 * @return void
 */
	function testBuildWithReset() {
		$old = App::path('models');
		$expected = array(
			APP . 'models' . DS,
			APP,
			LIBS . 'model' . DS
		);
		$this->assertEqual($expected, $old);

		App::build(array('models' => array('/path/to/models/')), true);

		$new = App::path('models');

		$expected = array(
			'/path/to/models/'
		);
		$this->assertEqual($expected, $new);

		App::build(); //reset defaults
		$defaults = App::path('models');
		$this->assertEqual($old, $defaults);
	}

/**
 * testCore method
 *
 * @access public
 * @return void
 */
	function testCore() {
		$model = App::core('models');
		$this->assertEqual(array(LIBS . 'model' . DS), $model);

		$view = App::core('views');
		$this->assertEqual(array(LIBS . 'view' . DS), $view);

		$controller = App::core('controllers');
		$this->assertEqual(array(LIBS . 'controller' . DS), $controller);

	}

/**
 * testListObjects method
 *
 * @access public
 * @return void
 */
	function testListObjects() {
		$result = App::objects('class', TEST_CAKE_CORE_INCLUDE_PATH . 'libs', false);
		$this->assertTrue(in_array('Xml', $result));
		$this->assertTrue(in_array('Cache', $result));
		$this->assertTrue(in_array('HttpSocket', $result));

		$result = App::objects('behavior', null, false);
		$this->assertTrue(in_array('Tree', $result));

		$result = App::objects('controller', null, false);
		$this->assertTrue(in_array('Pages', $result));

		$result = App::objects('component', null, false);
		$this->assertTrue(in_array('Auth', $result));

		$result = App::objects('view', null, false);
		$this->assertTrue(in_array('Media', $result));

		$result = App::objects('helper', null, false);
		$this->assertTrue(in_array('Html', $result));

		$result = App::objects('model', null, false);
		$notExpected = array('AppModel', 'ModelBehavior', 'ConnectionManager',  'DbAcl', 'Model', 'CakeSchema');
		foreach ($notExpected as $class) {
			$this->assertFalse(in_array($class, $result));
		}

		$result = App::objects('file');
		$this->assertFalse($result);

		$result = App::objects('file', 'non_existing_configure');
		$expected = array();
		$this->assertEqual($result, $expected);

		$result = App::objects('NonExistingType');
		$this->assertFalse($result);

		App::build(array(
			'plugins' => array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'libs' . DS
			)
		));
		$result = App::objects('plugin', null, false);
		$this->assertTrue(in_array('Cache', $result));
		$this->assertTrue(in_array('Log', $result));

		App::build();
	}

/**
 * test that pluginPath can find paths for plugins.
 *
 * @return void
 */
	function testPluginPath() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
		));
		$path = App::pluginPath('test_plugin');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' . DS;
		$this->assertEqual($path, $expected);

		$path = App::pluginPath('TestPlugin');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin' . DS;
		$this->assertEqual($path, $expected);

		$path = App::pluginPath('TestPluginTwo');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS . 'test_plugin_two' . DS;
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
			'views' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS)
		));
		$path = App::themePath('test_theme');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS;
		$this->assertEqual($path, $expected);

		$path = App::themePath('TestTheme');
		$expected = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'themed' . DS . 'test_theme' . DS;
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
		$file = App::import();
		$this->assertTrue($file);

		$file = App::import('Model', 'Model', false);
		$this->assertTrue($file);
		$this->assertTrue(class_exists('Model'));

		$file = App::import('Controller', 'Controller', false);
		$this->assertTrue($file);
		$this->assertTrue(class_exists('Controller'));

		$file = App::import('Component', 'Component', false);
		$this->assertTrue($file);
		$this->assertTrue(class_exists('Component'));

		$file = App::import('Shell', 'Shell', false);
		$this->assertTrue($file);
		$this->assertTrue(class_exists('Shell'));

		$file = App::import('Lib', 'config/PhpReader');
		$this->assertTrue($file);
		$this->assertTrue(class_exists('PhpReader'));

		$file = App::import('Model', 'SomeRandomModelThatDoesNotExist', false);
		$this->assertFalse($file);

		$file = App::import('Model', 'AppModel', false);
		$this->assertTrue($file);
		$this->assertTrue(class_exists('AppModel'));

		$file = App::import('WrongType', null, true, array(), '');
		$this->assertTrue($file);

		$file = App::import('Model', 'NonExistingPlugin.NonExistingModel', false);
		$this->assertFalse($file);

		$file = App::import('Core', 'NonExistingPlugin.NonExistingModel', false);
		$this->assertFalse($file);

		$file = App::import('Model', array('NonExistingPlugin.NonExistingModel'), false);
		$this->assertFalse($file);

		$file = App::import('Core', array('NonExistingPlugin.NonExistingModel'), false);
		$this->assertFalse($file);

		$file = App::import('Core', array('NonExistingPlugin.NonExistingModel.AnotherChild'), false);
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
			'libs' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'libs' . DS),
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)
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
		App::build();
		$this->assertFalse(class_exists('BananaHelper'), 'BananaHelper exists, cannot test importing it.');
		App::import('Helper', 'Banana');
		$this->assertFalse(class_exists('BananaHelper'), 'BananaHelper was not found because the path does not exist.');

		App::build(array(
			'helpers' => array(
				TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'views' . DS . 'helpers' . DS
			)
		));
		App::build(array('vendors' => array(TEST_CAKE_CORE_INCLUDE_PATH)));
		$this->assertFalse(class_exists('BananaHelper'), 'BananaHelper exists, cannot test importing it.');
		App::import('Helper', 'Banana');
		$this->assertTrue(class_exists('BananaHelper'), 'BananaHelper was not loaded.');

		App::build();
	}

/**
 * testFileLoading method
 *
 * @access public
 * @return void
 */
	function testFileLoading () {
		$file = App::import('File', 'RealFile', false, array(), TEST_CAKE_CORE_INCLUDE_PATH  . 'config' . DS . 'config.php');
		$this->assertTrue($file);

		$file = App::import('File', 'NoFile', false, array(), TEST_CAKE_CORE_INCLUDE_PATH  . 'config' . DS . 'cake' . DS . 'config.php');
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
				'file' => TEST_CAKE_CORE_INCLUDE_PATH  . DS . 'config' . DS . 'config.php');
		$file = App::import($type);
		$this->assertTrue($file);

		$type = array('type' => 'File', 'name' => 'NoFile', 'parent' => false,
				'file' => TEST_CAKE_CORE_INCLUDE_PATH  . 'config' . DS . 'cake' . DS . 'config.php');
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
		$file = App::import('File', 'Name', false, array(), TEST_CAKE_CORE_INCLUDE_PATH  . 'config' . DS . 'config.php', true);
		$this->assertTrue(!empty($file));

		$this->assertTrue(isset($file['Cake.version']));

		$type = array('type' => 'File', 'name' => 'OtherName', 'parent' => false,
				'file' => TEST_CAKE_CORE_INCLUDE_PATH  . 'config' . DS . 'config.php', 'return' => true);
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
		$file = App::import('File', 'NewName', false, array(TEST_CAKE_CORE_INCLUDE_PATH ), 'config.php');
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
	function testLoadingWithSearchArray () {
		$type = array('type' => 'File', 'name' => 'RandomName', 'parent' => false, 'file' => 'config.php', 'search' => array(TEST_CAKE_CORE_INCLUDE_PATH ));
		$file = App::import($type);
		$this->assertTrue($file);

		$type = array('type' => 'File', 'name' => 'AnotherRandomName', 'parent' => false, 'file' => 'config.php', 'search' => array(LIBS));
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
		if (class_exists('I18n', false) || class_exists('CakeSocket', false)) {
			$this->markTestSkipped('Cannot test loading of classes that exist.');
		}
		$toLoad = array('I18n', 'CakeSocket');

		$classes = array_flip(get_declared_classes());
		$this->assertFalse(isset($classes['i18n']));
		$this->assertFalse(isset($classes['CakeSocket']));

		$load = App::import($toLoad);
		$this->assertTrue($load);

		$classes = array_flip(get_declared_classes());


		$this->assertTrue(isset($classes['I18n']));

		$load = App::import(array('I18n', 'SomeNotFoundClass', 'CakeSocket'));
		$this->assertFalse($load);

		$load = App::import($toLoad);
		$this->assertTrue($load);
	}

/**
 * This test only works if you have plugins/my_plugin set up.
 * plugins/my_plugin/models/my_plugin.php and other_model.php
 */

/*
	function testMultipleLoadingByType() {
		$classes = array_flip(get_declared_classes());
		$this->assertFalse(isset($classes['OtherPlugin']));
		$this->assertFalse(isset($classes['MyPlugin']));


		$load = App::import('Model', array('MyPlugin.OtherPlugin', 'MyPlugin.MyPlugin'));
		$this->assertTrue($load);

		$classes = array_flip(get_declared_classes());
		$this->assertTrue(isset($classes['OtherPlugin']));
		$this->assertTrue(isset($classes['MyPlugin']));
	}
*/
	function testLoadingVendor() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
			'vendors' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors'. DS),
		), true);

		ob_start();
		$result = App::import('Vendor', 'TestPlugin.TestPluginAsset', array('ext' => 'css'));
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEqual($text, 'this is the test plugin asset css file');

		ob_start();
		$result = App::import('Vendor', 'TestAsset', array('ext' => 'css'));
		$text = ob_get_clean();
		$this->assertTrue($result);
		$this->assertEqual($text, 'this is the test asset css file');

		$result = App::import('Vendor', 'TestPlugin.SamplePlugin');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('SamplePluginClassTestName'));

		$result = App::import('Vendor', 'ConfigureTestVendorSample');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('ConfigureTestVendorSample'));

		ob_start();
		$result = App::import('Vendor', 'SomeName', array('file' => 'some.name.php'));
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
}
