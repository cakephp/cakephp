<?php
/* SVN FILE: $Id$ */
/**
 * TestTaskTest file
 *
 * Test Case for test generation shell task
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
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
App::import('Core', 'Shell');
App::import('Core', array('Controller', 'Model'));

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

if (!class_exists('TestTask')) {
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'view.php';
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'controller.php';
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'template.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestTestTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'ViewTask', 'MockViewTask',
	array('in', '_stop', 'err', 'out', 'createFile')
);

Mock::generate('ControllerTask', 'ViewTaskMockControllerTask');

class ViewTaskComment extends Model {
	var $name = 'ViewTaskComment';
	var $useTable = 'comments';

	var $belongsTo = array(
		'Article' => array(
			'className' => 'ViewTaskArticle',
			'foreignKey' => 'article_id'
		)
	);
}

class ViewTaskArticle extends Model {
	var $name = 'ViewTaskArticle';
	var $useTable = 'articles';
}

class ViewTaskCommentsController extends Controller {
	var $name = 'ViewTaskComments';

	function index() {

	}
	function add() {

	}
}


/**
 * ViewTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class ViewTaskTest extends CakeTestCase {

	var $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag');
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestTestTaskMockShellDispatcher();
		$this->Dispatcher->shellPaths = Configure::read('shellPaths');
		$this->Task =& new MockViewTask($this->Dispatcher);
		$this->Task->Dispatch =& $this->Dispatcher;
		$this->Task->Template =& new TemplateTask($this->Dispatcher);
		$this->Task->Controller =& new ViewTaskMockControllerTask();
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function endTest() {
		ClassRegistry::flush();
	}

/**
 * Test getContent and parsing of Templates.
 *
 * @return void
 **/
	function testGetContent() {
		$vars = array(
			'modelClass' => 'TestViewModel',
			'schema' => array(),
			'primaryKey' => 'id',
			'displayField' => 'name',
			'singularVar' => 'testViewModel',
			'pluralVar' => 'testViewModels',
			'singularHumanName' => 'Test View Model',
			'pluralHumanName' => 'Test View Models',
			'fields' => array('id', 'name', 'body'),
			'associations' => array()
		);
		$result = $this->Task->getContent('view', $vars);

		$this->assertPattern('/Delete Test View Model/', $result);
		$this->assertPattern('/Edit Test View Model/', $result);
		$this->assertPattern('/List Test View Models/', $result);
		$this->assertPattern('/New Test View Model/', $result);

		$this->assertPattern('/testViewModel\[\'TestViewModel\'\]\[\'id\'\]/', $result);
		$this->assertPattern('/testViewModel\[\'TestViewModel\'\]\[\'name\'\]/', $result);
		$this->assertPattern('/testViewModel\[\'TestViewModel\'\]\[\'body\'\]/', $result);
	}

/**
 * test Bake method
 *
 * @return void
 **/
	function testBake() {
		$this->Task->path = TMP;
		$this->Task->controllerName = 'ViewTaskComments';
		$this->Task->controllerPath = 'view_task_comments';

		$this->Task->expectAt(0, 'createFile', array(
			TMP . 'view_task_comments' . DS . 'view.ctp',
			new PatternExpectation('/View Task Articles/')
		));
		$this->Task->bake('view', true);

		$this->Task->expectAt(1, 'createFile', array(TMP . 'view_task_comments' . DS . 'edit.ctp', '*'));
		$this->Task->bake('edit', true);

		$this->Task->expectAt(2, 'createFile', array(
			TMP . 'view_task_comments' . DS . 'index.ctp', 
			new PatternExpectation('/\$viewTaskComment\[\'Article\'\]\[\'title\'\]/')
		));
		$this->Task->bake('index', true);

		@rmdir(TMP . 'view_task_comments');
	}

/**
 * test bake actions baking multiple actions.
 *
 * @return void
 **/
	function testBakeActions() {
		$this->Task->path = TMP;
		$this->Task->controllerName = 'ViewTaskComments';
		$this->Task->controllerPath = 'view_task_comments';

		$this->Task->expectAt(0, 'createFile', array(TMP . 'view_task_comments' . DS . 'view.ctp', '*'));
		$this->Task->expectAt(1, 'createFile', array(TMP . 'view_task_comments' . DS . 'edit.ctp', '*'));
		$this->Task->expectAt(2, 'createFile', array(TMP . 'view_task_comments' . DS . 'index.ctp', '*'));

		$this->Task->bakeActions(array('view', 'edit', 'index'), array());

		@rmdir(TMP . 'view_task_comments');
	}

/**
 * test baking a customAction (non crud)
 *
 * @return void
 **/
	function testCustomAction() {
		$this->Task->path = TMP;
		$this->Task->controllerName = 'ViewTaskComments';
		$this->Task->controllerPath = 'view_task_comments';
		$this->Task->params['app'] = APP;

		$this->Task->setReturnValueAt(0, 'in', '');
		$this->Task->setReturnValueAt(1, 'in', 'my_action');
		$this->Task->setReturnValueAt(2, 'in', 'y');
		$this->Task->expectAt(0, 'createFile', array(TMP . 'view_task_comments' . DS . 'my_action.ctp', '*'));

		$this->Task->customAction();

		@rmdir(TMP . 'view_task_comments');
	}

/**
 * Test all()
 *
 * @return void
 **/
	function testExecuteIntoAll() {
		$this->Task->path = TMP;
		$this->Task->args[0] = 'all';

		$this->Task->Controller->setReturnValue('listAll', array('view_task_comments'));
		$this->Task->Controller->expectOnce('listAll');

		$this->Task->expectCallCount('createFile', 4);
		$this->Task->expectAt(0, 'createFile', array(TMP . 'view_task_comments' . DS . 'index.ctp', '*'));
		$this->Task->expectAt(1, 'createFile', array(TMP . 'view_task_comments' . DS . 'view.ctp', '*'));
		$this->Task->expectAt(2, 'createFile', array(TMP . 'view_task_comments' . DS . 'add.ctp', '*'));
		$this->Task->expectAt(3, 'createFile', array(TMP . 'view_task_comments' . DS . 'edit.ctp', '*'));

		$this->Task->execute();

		@rmdir(TMP . 'view_task_comments');
	}

/**
 * test `cake bake view $controller view`
 *
 * @return void
 **/
	function testExecuteWithActionParam() {
		$this->Task->path = TMP;
		$this->Task->args[0] = 'ViewTaskComments';
		$this->Task->args[1] = 'view';

		$this->Task->expectCallCount('createFile', 1);
		$this->Task->expectAt(0, 'createFile', array(TMP . 'view_task_comments' . DS . 'view.ctp', '*'));
		$this->Task->execute();

		@rmdir(TMP . 'view_task_comments');
	}

/**
 * test `cake bake view $controller`
 *
 * @return void
 **/
	function testExecuteWithController() {
		$this->Task->path = TMP;
		$this->Task->args[0] = 'ViewTaskComments';

		$this->Task->expectCallCount('createFile', 2);
		$this->Task->expectAt(0, 'createFile', array(TMP . 'view_task_comments' . DS . 'index.ctp', '*'));
		$this->Task->expectAt(1, 'createFile', array(TMP . 'view_task_comments' . DS . 'add.ctp', '*'));
		$this->Task->execute();

		@rmdir(TMP . 'view_task_comments');
	}

/**
 * test execute into interactive.
 *
 * @return void
 **/
	function testExecuteInteractive() {
		$this->Task->path = TMP;
		$this->Task->connection = 'test_suite';
		$this->Task->args = array();

		$this->Task->Controller->setReturnValue('getName', 'ViewTaskComments');
		$this->Task->setReturnValue('in', 'y');
		$this->Task->setReturnValueAt(0, 'in', 'y');
		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->setReturnValueAt(2, 'in', 'n');

		$this->Task->expectCallCount('createFile', 4);
		$this->Task->expectAt(0, 'createFile', array(TMP . 'view_task_comments' . DS . 'index.ctp', '*'));
		$this->Task->expectAt(1, 'createFile', array(TMP . 'view_task_comments' . DS . 'view.ctp', '*'));
		$this->Task->expectAt(2, 'createFile', array(TMP . 'view_task_comments' . DS . 'add.ctp', '*'));
		$this->Task->expectAt(3, 'createFile', array(TMP . 'view_task_comments' . DS . 'edit.ctp', '*'));

		$this->Task->execute();

		@rmdir(TMP . 'view_task_comments');
	}
}
?>