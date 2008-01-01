<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5432
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
uses('configure');
class AppImportTest extends UnitTestCase {

	function testClassLoading() {
		$file = App::import();
		$this->assertTrue($file);

		$file = App::import('Core', 'Model', false);
		$this->assertTrue($file);

		$file = App::import('Model', 'SomeRandomModelThatDoesNotExist', false);
		$this->assertFalse($file);

		$file = App::import('Model', 'AppModel', false);
		$this->assertTrue($file);

		if (!class_exists('AppController')) {
			$classes = array_flip(get_declared_classes());

			if (PHP5) {
				$this->assertFalse(isset($classes['PagesController']));
				$this->assertFalse(isset($classes['AppController']));
			} else {
				$this->assertFalse(isset($classes['pagescontroller']));
				$this->assertFalse(isset($classes['appcontroller']));
			}

			$file = App::import('Controller', 'Pages');
			$this->assertTrue($file);

			$classes = array_flip(get_declared_classes());

			if (PHP5) {
				$this->assertTrue(isset($classes['PagesController']));
				$this->assertTrue(isset($classes['AppController']));
			} else {
				$this->assertTrue(isset($classes['pagescontroller']));
				$this->assertTrue(isset($classes['appcontroller']));
			}
		}
	}

	function testFileLoading () {
		$file = App::import('File', 'RealFile', false, array(), TEST_CAKE_CORE_INCLUDE_PATH  . 'config' . DS . 'config.php');
		$this->assertTrue($file);

		$file = App::import('File', 'NoFile', false, array(), TEST_CAKE_CORE_INCLUDE_PATH  . 'config' . DS . 'cake' . DS . 'config.php');
		$this->assertFalse($file);
	}
	// import($type = null, $name = null, $parent = true, $file = null, $search = array(), $return = false) {
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

	function testFileLoadingReturnValue () {
		$file = App::import('File', 'Name', false, array(), TEST_CAKE_CORE_INCLUDE_PATH  . 'config' . DS . 'config.php', true);
		$this->assertTrue($file);

		$this->assertTrue(isset($file['Cake.version']));

		$type = array('type' => 'File', 'name' => 'OtherName', 'parent' => false,
				'file' => TEST_CAKE_CORE_INCLUDE_PATH  . 'config' . DS . 'config.php', 'return' => true);
		$file = App::import($type);
		$this->assertTrue($file);

		$this->assertTrue(isset($file['Cake.version']));
	}

	function testLoadingWithSearch () {
		$file = App::import('File', 'NewName', false, array(TEST_CAKE_CORE_INCLUDE_PATH ), 'config.php');
		$this->assertTrue($file);

		$file = App::import('File', 'AnotherNewName', false, array(LIBS), 'config.php');
		$this->assertFalse($file);
	}

	function testLoadingWithSearchArray () {
		$type = array('type' => 'File', 'name' => 'RandomName', 'parent' => false, 'file' => 'config.php', 'search' => array(TEST_CAKE_CORE_INCLUDE_PATH ));
		$file = App::import($type);
		$this->assertTrue($file);

		$type = array('type' => 'File', 'name' => 'AnotherRandomName', 'parent' => false, 'file' => 'config.php', 'search' => array(LIBS));
		$file = App::import($type);
		$this->assertFalse($file);
	}

	function testMultipleLoading() {
		$toLoad = array('I18n', 'Socket');

		$classes = array_flip(get_declared_classes());
		$this->assertFalse(isset($classes['i18n']));
		$this->assertFalse(isset($classes['Socket']));

		$load = App::import($toLoad);
		$this->assertTrue($load);

		$classes = array_flip(get_declared_classes());

		if (PHP5) {
			$this->assertTrue(isset($classes['I18n']));
		} else {
			$this->assertTrue(isset($classes['i18n']));
		}

		$load = App::import(array('I18n', 'SomeNotFoundClass', 'Socket'));
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
		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));
		Configure::write('vendorPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'vendors'. DS));

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

		$result = App::import('Vendor', 'Sample');
		$this->assertTrue($result);
		$this->assertTrue(class_exists('SampleClassTestName'));
	}
}
?>