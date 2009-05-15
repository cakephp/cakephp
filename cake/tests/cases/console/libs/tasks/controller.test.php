<?php
/**
 * ControllerTask Test Case
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
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

require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'controller.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'model.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'template.php';


Mock::generatePartial(
	'ShellDispatcher', 'TestControllerTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);

Mock::generatePartial(
	'ControllerTask', 'MockControllerTask',
	array('in', 'out', 'err', 'createFile', '_stop', '_checkUnitTest')
);

Mock::generatePartial(
	'ModelTask', 'ControllerMockModelTask',
	array('in', 'out', 'err', 'createFile', '_stop', '_checkUnitTest')
);

/**
 * ControllerTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class ControllerTaskTest extends CakeTestCase {
/**
 * fixtures
 *
 * @var array
 **/
	var $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag');

/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestControllerTaskMockShellDispatcher();
		$this->Task =& new MockControllerTask($this->Dispatcher);
		$this->Task->Dispatch =& new $this->Dispatcher;
		$this->Task->Dispatch->shellPaths = Configure::read('shellPaths');
		$this->Task->Template =& new TemplateTask($this->Task->Dispatch);
		$this->Task->Model =& new ControllerMockModelTask($this->Task->Dispatch);
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		unset($this->Task, $this->Dispatcher);
		ClassRegistry::flush();
	}

/**
 * test ListAll
 *
 * @return void
 **/
	function testListAll() {
		$this->Task->connection = 'test_suite';
		$this->Task->interactive = true;
		$this->Task->expectAt(1, 'out', array('1. Articles'));
		$this->Task->expectAt(2, 'out', array('2. ArticlesTags'));
		$this->Task->expectAt(3, 'out', array('3. Comments'));
		$this->Task->expectAt(4, 'out', array('4. Tags'));

		$expected = array('Articles', 'ArticlesTags', 'Comments', 'Tags');
		$result = $this->Task->listAll('test_suite');
		$this->assertEqual($result, $expected);

		$this->Task->expectAt(6, 'out', array('1. Articles'));
		$this->Task->expectAt(7, 'out', array('2. ArticlesTags'));
		$this->Task->expectAt(8, 'out', array('4. Comments'));
		$this->Task->expectAt(9, 'out', array('5. Tags'));

		$this->Task->interactive = false;
		$result = $this->Task->listAll();

		$expected = array('articles', 'articles_tags', 'comments', 'tags');
		$this->assertEqual($result, $expected);	
	}

/**
 * Test that getName interacts with the user and returns the controller name.
 *
 * @return void
 **/
	function testGetName() {
		$this->Task->setReturnValue('in', 1);

		$this->Task->setReturnValueAt(0, 'in', 'q');
		$this->Task->expectOnce('_stop');
		$this->Task->getName('test_suite');

		$this->Task->setReturnValueAt(1, 'in', 1);
		$result = $this->Task->getName('test_suite');
		$expected = 'Articles';
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(2, 'in', 3);
		$result = $this->Task->getName('test_suite');
		$expected = 'Comments';
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(3, 'in', 10);
		$result = $this->Task->getName('test_suite');
		$this->Task->expectOnce('err');
	}
}
?>