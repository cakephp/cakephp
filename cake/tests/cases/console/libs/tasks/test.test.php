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
	require CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'test.php';
}

Mock::generatePartial(
	'ShellDispatcher', 'TestTestTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'TestTask', 'MockTestTask',
	array('in', 'out', 'createFile')
);

/**
 * Test subject for method extraction
 *
 **/
class TestTaskSubjectClass extends Object {
	function methodOne() { }
	function methodTwo() { }
	function _noTest() { }
}

/**
 * Test subject models for fixture generation
 **/
class TestTaskArticle extends Model {
	var $name = 'TestTaskArticle';
	var $useTable = 'articles';
	var $hasMany = array(
		'Comment' => array(
			'className' => 'TestTask.TestTaskComment',
			'foreignKey' => 'article_id',
		)
	);
	var $hasAndBelongsToMany = array(
		'Tag' => array(
			'className' => 'TestTaskTag',
			'joinTable' => 'articles_tags',
			'foreignKey' => 'article_id',
			'associationForeignKey' => 'tag_id'
		)
	);
}
class TestTaskTag extends Model {
	var $name = 'TestTaskTag';
	var $useTable = 'tags';
	var $hasAndBelongsToMany = array(
		'Article' => array(
			'className' => 'TestTaskArticle',
			'joinTable' => 'articles_tags',
			'foreignKey' => 'tag_id',
			'associationForeignKey' => 'article_id'
		)
	);
}
/**
 * Simulated Plugin
 **/
class TestTaskAppModel extends Model {
	
}
class TestTaskComment extends TestTaskAppModel {
	var $name = 'TestTaskComment';
	var $useTable = 'comments';
	var $belongsTo = array(
		'Article' => array(
			'className' => 'TestTaskArticle',
			'foreignKey' => 'article_id',
		)
	);
}

class TestTaskCommentsController extends Controller {
	var $name = 'TestTaskComments';
	var $uses = array('TestTaskComment', 'TestTaskTag');
}

/**
 * TestTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class TestTaskTest extends CakeTestCase {

	var $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag');
/**
 * setUp method
 *
 * @return void
 * @access public
 */
	function setUp() {
		$this->Dispatcher =& new TestTestTaskMockShellDispatcher();
		$this->Task =& new MockTestTask($this->Dispatcher);
		$this->Task->Dispatch = new $this->Dispatcher;
	}

/**
 * tearDown method
 *
 * @return void
 * @access public
 */
	function tearDown() {
		ClassRegistry::flush();
	}

/**
 * Test that file path generation doesn't continuously append paths.
 *
 * @access public
 * @return void
 */
	function testFilePathGeneration () {
		$file = TESTS . 'cases' . DS . 'models' . DS . 'my_class.test.php';

		$this->Task->Dispatch->expectNever('stderr');
		$this->Task->Dispatch->expectNever('_stop');

		$this->Task->setReturnValueAt(0, 'in', 'y');
		$this->Task->expectAt(0, 'createFile', array($file, '*'));
		$this->Task->bake('Model', 'MyClass');

		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->expectAt(1, 'createFile', array($file, '*'));
		$this->Task->bake('Model', 'MyClass');
	}

/**
 * Test that method introspection pulls all relevant non parent class 
 * methods into the test case.
 *
 * @return void
 **/
	function testMethodIntrospection() {
		$result = $this->Task->getTestableMethods('TestTaskSubjectClass');
		$expected = array('methodOne', 'methodTwo');
		$this->assertEqual($result, $expected);
	}

/**
 * test that the generation of fixtures works correctly.
 *
 * @return void
 **/
	function testFixtureArrayGenerationFromModel() {
		$subject = ClassRegistry::init('TestTaskArticle');
		$result = $this->Task->generateFixtureList($subject);
		$expected = array('plugin.test_task.test_task_comment', 'app.articles_tags', 
			'app.test_task_article', 'app.test_task_tag');

		$this->assertEqual(sort($result), sort($expected));
		
	}

/**
 * test that the generation of fixtures works correctly.
 *
 * @return void
 **/
	function testFixtureArrayGenerationFromController() {
		$subject = new TestTaskCommentsController();
		$result = $this->Task->generateFixtureList($subject);
		$expected = array('plugin.test_task.test_task_comment', 'app.articles_tags', 
			'app.test_task_article', 'app.test_task_tag');

		$this->assertEqual(sort($result), sort($expected));

	}
}
?>