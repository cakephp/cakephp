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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
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
	var $realFile;

	function testClassLoading() {
		$file = App::import();
		$this->assertTrue($file);

		$file = App::import('Core', 'Model', false);
		$this->assertTrue($file);

		$file = App::import('Model', 'SomeRandomModelThatDoesNotExist', false);
		$this->assertFalse($file);

		$file = App::import('Model', 'AppModel', false);
		$this->assertTrue($file);
	}

	function testFileLoading () {
		$file = App::import('File', 'RealFile', false, array(), CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'config' . DS . 'config.php');
		$this->assertTrue($file);

		$file = App::import('File', 'NoFile', false, array(), CAKE_CORE_INCLUDE_PATH . DS . 'config' . DS . 'cake' . DS . 'config.php');
		$this->assertFalse($file);
	}
	// import($type = null, $name = null, $parent = true, $file = null, $search = array(), $return = false) {
	function testFileLoadingWithArray() {
		$type = array('type' => 'File', 'name' => 'SomeName', 'parent' => false,
				'file' => CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'config' . DS . 'config.php');
		$file = App::import($type);
		$this->assertTrue($file);

		$type = array('type' => 'File', 'name' => 'NoFile', 'parent' => false,
				'file' => CAKE_CORE_INCLUDE_PATH . DS . 'config' . DS . 'cake' . DS . 'config.php');
		$file = App::import($type);
		$this->assertFalse($file);
	}

	function testFileLoadingReturnValue () {
		$file = App::import('File', 'Name', false, array(), CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'config' . DS . 'config.php', true);
		$this->assertTrue($file);

		$this->assertTrue(isset($file['Cake.version']));

		$type = array('type' => 'File', 'name' => 'OtherName', 'parent' => false,
				'file' => CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'config' . DS . 'config.php', 'return' => true);
		$file = App::import($type);
		$this->assertTrue($file);

		$this->assertTrue(isset($file['Cake.version']));
	}

	function testLoadingWithSearch () {
		$file = App::import('File', 'NewName', false, array(CAKE_CORE_INCLUDE_PATH), 'config.php');
		$this->assertTrue($file);

		$file = App::import('File', 'AnotherNewName', false, array(LIBS), 'config.php');
		$this->assertFalse($file);
	}

	function testLoadingWithSearchArray () {
		$type = array('type' => 'File', 'name' => 'RandomName', 'parent' => false, 'file' => 'config.php', 'search' => array(CAKE_CORE_INCLUDE_PATH));
		$file = App::import($type);
		$this->assertTrue($file);

		$type = array('type' => 'File', 'name' => 'AnotherRandomName', 'parent' => false, 'file' => 'config.php', 'search' => array(LIBS));
		$file = App::import($type);
		$this->assertFalse($file);
	}
}
?>