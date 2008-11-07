<?php
/* SVN FILE: $Id$ */
/**
 * Test Case for Shell
 *
 * 
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2006-2008, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2006-2008, Cake Software Foundation, Inc.
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package       cake
 * @subpackage    cake.cake.libs.
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'Shell');

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

/**
 * Test Shell class
 *
 * @package       cake.tests
 * @subpackage    cake.tests.cases.libs
 */
class TestShell extends Shell {
	
}

Mock::generate('ShellDispatcher');

/**
 * Test case class for shell
 *
 * @package       cake.tests
 * @subpackage    cake.tests.cases.libs
 */
class CakeShellTestCase extends CakeTestCase {
	var $fixtures = array('core.post', 'core.comment');
/**
 * setup
 *
 * @return void
 **/
	function setUp() {
		$this->Dispatcher =& new MockShellDispatcher();
		$this->Shell =& new TestShell($this->Dispatcher);
	}
/**
 * undocumented function
 *
 * @return void
 * @access public
 */
	function testInitialize() {
		$_back = array(
			'modelPaths' => Configure::read('modelPaths'),
			'pluginPaths' => Configure::read('pluginPaths'),
			'viewPaths' => Configure::read('viewPaths'),
		);
		Configure::write('pluginPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS));
		Configure::write('modelPaths', array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'models' . DS));
		$this->Shell->uses = array('TestPlugin.TestPluginPost');
		$this->Shell->initialize();

		$this->assertTrue(isset($this->Shell->TestPluginPost));
		$this->assertTrue(is_a($this->Shell->TestPluginPost, 'TestPluginPost'));
		$this->assertEqual($this->Shell->modelClass, 'TestPluginPost');
		
		$this->Shell->uses = array('Comment');
		$this->Shell->initialize();
		$this->assertTrue(isset($this->Shell->Comment));
		$this->assertTrue(is_a($this->Shell->Comment, 'Comment'));
		$this->assertEqual($this->Shell->modelClass, 'Comment');
		
		Configure::write('pluginPaths', $_back['pluginPaths']);
		Configure::write('modelPaths', $_back['modelPaths']);
	}

/**
 * Test Loading of Tasks
 *
 * @return void
 **/
	function testLoadTasks() {
		
	}
/**
 * test ShortPath
 *
 * @return void
 **/
	function testShortPath() {
		
	}
/**
 * test File creation
 *
 * @return void
 **/
	function createFile() {
		
	}
}

?>