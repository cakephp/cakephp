<?php
/**
 * TestTaskTest file
 *
 * Test Case for test generation shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);
App::import('Controller', 'Controller', false);
App::import('Model', 'Model', false);

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'test.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'template.php';


Mock::generatePartial(
	'ShellDispatcher', 'TestTestTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'TestTask', 'MockTestTask',
	array('in', '_stop', 'err', 'out', 'createFile', 'isLoadableClass')
);

/**
 * Test Article model
 *
 * @package cake
 * @subpackage cake.tests.cases.console.libs.tasks
 */
class TestTaskArticle extends Model {

/**
 * Model name
 *
 * @var string
 * @access public
 */
	var $name = 'TestTaskArticle';

/**
 * Table name to use
 *
 * @var string
 * @access public
 */
	var $useTable = 'articles';

/**
 * HasMany Associations
 *
 * @var array
 * @access public
 */
	var $hasMany = array(
		'Comment' => array(
			'className' => 'TestTask.TestTaskComment',
			'foreignKey' => 'article_id',
		)
	);

/**
 * Has and Belongs To Many Associations
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array(
		'Tag' => array(
			'className' => 'TestTaskTag',
			'joinTable' => 'articles_tags',
			'foreignKey' => 'article_id',
			'associationForeignKey' => 'tag_id'
		)
	);

/**
 * Example public method
 *
 * @return void
 * @access public
 */
	function doSomething() {
	}

/**
 * Example Secondary public method
 *
 * @return void
 * @access public
 */
	function doSomethingElse() {
	}

/**
 * Example protected method
 *
 * @return void
 * @access protected
 */
	function _innerMethod() {
	}
}

/**
 * Tag Testing Model
 *
 * @package cake
 * @subpackage cake.tests.cases.console.libs.tasks
 */
class TestTaskTag extends Model {

/**
 * Model name
 *
 * @var string
 * @access public
 */
	var $name = 'TestTaskTag';

/**
 * Table name
 *
 * @var string
 * @access public
 */
	var $useTable = 'tags';

/**
 * Has and Belongs To Many Associations
 *
 * @var array
 * @access public
 */
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
 * Simulated plugin
 *
 * @package cake
 * @subpackage cake.tests.cases.console.libs.tasks
 */
class TestTaskAppModel extends Model {
}

/**
 * Testing AppMode (TaskComment)
 *
 * @package cake
 * @subpackage cake.tests.cases.console.libs.tasks
 */
class TestTaskComment extends TestTaskAppModel {

/**
 * Model name
 *
 * @var string
 * @access public
 */
	var $name = 'TestTaskComment';

/**
 * Table name
 *
 * @var string
 * @access public
 */
	var $useTable = 'comments';

/**
 * Belongs To Associations
 *
 * @var array
 * @access public
 */
	var $belongsTo = array(
		'Article' => array(
			'className' => 'TestTaskArticle',
			'foreignKey' => 'article_id',
		)
	);
}

/**
 * Test Task Comments Controller
 *
 * @package cake
 * @subpackage cake.tests.cases.console.libs.tasks
 */
class TestTaskCommentsController extends Controller {

/**
 * Controller Name
 *
 * @var string
 * @access public
 */
	var $name = 'TestTaskComments';

/**
 * Models to use
 *
 * @var array
 * @access public
 */
	var $uses = array('TestTaskComment', 'TestTaskTag');
}

/**
 * TestTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class TestTaskTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var string
 * @access public
 */
	var $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag');

/**
 * startTest method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestTestTaskMockShellDispatcher();
		$this->Dispatcher->shellPaths = App::path('shells');
		$this->Task =& new MockTestTask($this->Dispatcher);
		$this->Task->name = 'TestTask';
		$this->Task->Dispatch =& $this->Dispatcher;
		$this->Task->Template =& new TemplateTask($this->Dispatcher);
	}

/**
 * endTest method
 *
 * @return void
 * @access public
 */
	function endTest() {
		ClassRegistry::flush();
	}

/**
 * Test that file path generation doesn't continuously append paths.
 *
 * @return void
 * @access public
 */
	function testFilePathGeneration() {
		$file = TESTS . 'cases' . DS . 'models' . DS . 'my_class.test.php';

		$this->Task->Dispatch->expectNever('stderr');
		$this->Task->Dispatch->expectNever('_stop');

		$this->Task->setReturnValue('in', 'y');
		$this->Task->expectAt(0, 'createFile', array($file, '*'));
		$this->Task->bake('Model', 'MyClass');

		$this->Task->expectAt(1, 'createFile', array($file, '*'));
		$this->Task->bake('Model', 'MyClass');

		$file = TESTS . 'cases' . DS . 'controllers' . DS . 'comments_controller.test.php';
		$this->Task->expectAt(2, 'createFile', array($file, '*'));
		$this->Task->bake('Controller', 'Comments');
	}

/**
 * Test that method introspection pulls all relevant non parent class
 * methods into the test case.
 *
 * @return void
 */
	function testMethodIntrospection() {
		$result = $this->Task->getTestableMethods('TestTaskArticle');
		$expected = array('dosomething', 'dosomethingelse');
		$this->assertEqual(array_map('strtolower', $result), $expected);
	}

/**
 * test that the generation of fixtures works correctly.
 *
 * @return void
 * @access public
 */
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
 * @access public
 */
	function testFixtureArrayGenerationFromController() {
		$subject = new TestTaskCommentsController();
		$result = $this->Task->generateFixtureList($subject);
		$expected = array('plugin.test_task.test_task_comment', 'app.articles_tags',
			'app.test_task_article', 'app.test_task_tag');

		$this->assertEqual(sort($result), sort($expected));
	}

/**
 * test user interaction to get object type
 *
 * @return void
 * @access public
 */
	function testGetObjectType() {
		$this->Task->expectOnce('_stop');
		$this->Task->setReturnValueAt(0, 'in', 'q');
		$this->Task->getObjectType();

		$this->Task->setReturnValueAt(1, 'in', 2);
		$result = $this->Task->getObjectType();
		$this->assertEqual($result, $this->Task->classTypes[1]);
	}

/**
 * creating test subjects should clear the registry so the registry is always fresh
 *
 * @return void
 * @access public
 */
	function testRegistryClearWhenBuildingTestObjects() {
		ClassRegistry::flush();
		$model = ClassRegistry::init('TestTaskComment');
		$model->bindModel(array(
			'belongsTo' => array(
				'Random' => array(
					'className' => 'TestTaskArticle',
					'foreignKey' => 'article_id',
				)
			)
		));
		$keys = ClassRegistry::keys();
		$this->assertTrue(in_array('random', $keys));
		$object =& $this->Task->buildTestSubject('Model', 'TestTaskComment');

		$keys = ClassRegistry::keys();
		$this->assertFalse(in_array('random', $keys));
	}

/**
 * test that getClassName returns the user choice as a classname.
 *
 * @return void
 * @access public
 */
	function testGetClassName() {
		$objects = App::objects('model');
		$skip = $this->skipIf(empty($objects), 'No models in app, this test will fail. %s');
		if ($skip) {
			return;
		}
		$this->Task->setReturnValueAt(0, 'in', 'MyCustomClass');
		$result = $this->Task->getClassName('Model');
		$this->assertEqual($result, 'MyCustomClass');

		$this->Task->setReturnValueAt(1, 'in', 1);
		$result = $this->Task->getClassName('Model');
		$options = App::objects('model');
		$this->assertEqual($result, $options[0]);
	}

/**
 * Test the user interaction for defining additional fixtures.
 *
 * @return void
 * @access public
 */
	function testGetUserFixtures() {
		$this->Task->setReturnValueAt(0, 'in', 'y');
		$this->Task->setReturnValueAt(1, 'in', 'app.pizza, app.topping, app.side_dish');
		$result = $this->Task->getUserFixtures();
		$expected = array('app.pizza', 'app.topping', 'app.side_dish');
		$this->assertEqual($result, $expected);
	}

/**
 * test that resolving classnames works
 *
 * @return void
 * @access public
 */
	function testGetRealClassname() {
		$result = $this->Task->getRealClassname('Model', 'Post');
		$this->assertEqual($result, 'Post');

		$result = $this->Task->getRealClassname('Controller', 'Posts');
		$this->assertEqual($result, 'PostsController');

		$result = $this->Task->getRealClassname('Helper', 'Form');
		$this->assertEqual($result, 'FormHelper');

		$result = $this->Task->getRealClassname('Behavior', 'Containable');
		$this->assertEqual($result, 'ContainableBehavior');

		$result = $this->Task->getRealClassname('Component', 'Auth');
		$this->assertEqual($result, 'AuthComponent');
	}

/**
 * test baking files.  The conditionally run tests are known to fail in PHP4
 * as PHP4 classnames are all lower case, breaking the plugin path inflection.
 *
 * @return void
 * @access public
 */
	function testBakeModelTest() {
		$this->Task->setReturnValue('createFile', true);
		$this->Task->setReturnValue('isLoadableClass', true);

		$result = $this->Task->bake('Model', 'TestTaskArticle');

		$this->assertPattern('/App::import\(\'Model\', \'TestTaskArticle\'\)/', $result);
		$this->assertPattern('/class TestTaskArticleTestCase extends CakeTestCase/', $result);

		$this->assertPattern('/function startTest\(\)/', $result);
		$this->assertPattern("/\\\$this->TestTaskArticle \=\& ClassRegistry::init\('TestTaskArticle'\)/", $result);

		$this->assertPattern('/function endTest\(\)/', $result);
		$this->assertPattern('/unset\(\$this->TestTaskArticle\)/', $result);

		$this->assertPattern('/function testDoSomething\(\)/i', $result);
		$this->assertPattern('/function testDoSomethingElse\(\)/i', $result);

		$this->assertPattern("/'app\.test_task_article'/", $result);
		if (PHP5) {
			$this->assertPattern("/'plugin\.test_task\.test_task_comment'/", $result);
		}
		$this->assertPattern("/'app\.test_task_tag'/", $result);
		$this->assertPattern("/'app\.articles_tag'/", $result);
	}

/**
 * test baking controller test files, ensure that the stub class is generated.
 * Conditional assertion is known to fail on PHP4 as classnames are all lower case
 * causing issues with inflection of path name from classname.
 *
 * @return void
 * @access public
 */
	function testBakeControllerTest() {
		$this->Task->setReturnValue('createFile', true);
		$this->Task->setReturnValue('isLoadableClass', true);

		$result = $this->Task->bake('Controller', 'TestTaskComments');

		$this->assertPattern('/App::import\(\'Controller\', \'TestTaskComments\'\)/', $result);
		$this->assertPattern('/class TestTaskCommentsControllerTestCase extends CakeTestCase/', $result);

		$this->assertPattern('/class TestTestTaskCommentsController extends TestTaskCommentsController/', $result);
		$this->assertPattern('/var \$autoRender = false/', $result);
		$this->assertPattern('/function redirect\(\$url, \$status = null, \$exit = true\)/', $result);

		$this->assertPattern('/function startTest\(\)/', $result);
		$this->assertPattern("/\\\$this->TestTaskComments \=\& new TestTestTaskCommentsController\(\)/", $result);
		$this->assertPattern("/\\\$this->TestTaskComments->constructClasses\(\)/", $result);

		$this->assertPattern('/function endTest\(\)/', $result);
		$this->assertPattern('/unset\(\$this->TestTaskComments\)/', $result);

		$this->assertPattern("/'app\.test_task_article'/", $result);
		if (PHP5) {
			$this->assertPattern("/'plugin\.test_task\.test_task_comment'/", $result);
		}
		$this->assertPattern("/'app\.test_task_tag'/", $result);
		$this->assertPattern("/'app\.articles_tag'/", $result);
	}

/**
 * test Constructor generation ensure that constructClasses is called for controllers
 *
 * @return void
 * @access public
 */
	function testGenerateConstructor() {
		$result = $this->Task->generateConstructor('controller', 'PostsController');
		$expected = "new TestPostsController();\n\t\t\$this->Posts->constructClasses();\n";
		$this->assertEqual($result, $expected);

		$result = $this->Task->generateConstructor('model', 'Post');
		$expected = "ClassRegistry::init('Post');\n";
		$this->assertEqual($result, $expected);

		$result = $this->Task->generateConstructor('helper', 'FormHelper');
		$expected = "new FormHelper();\n";
		$this->assertEqual($result, $expected);
	}

/**
 * Test that mock class generation works for the appropriate classes
 *
 * @return void
 * @access public
 */
	function testMockClassGeneration() {
		$result = $this->Task->hasMockClass('controller');
		$this->assertTrue($result);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 * @access public
 */
	function testBakeWithPlugin() {
		$this->Task->plugin = 'TestTest';

		$path = APP . 'plugins' . DS . 'test_test' . DS . 'tests' . DS . 'cases' . DS . 'helpers' . DS . 'form.test.php';
		$this->Task->expectAt(0, 'createFile', array($path, '*'));
		$this->Task->bake('Helper', 'Form');
	}

/**
 * Test filename generation for each type + plugins
 *
 * @return void
 * @access public
 */
	function testTestCaseFileName() {
		$this->Task->path = '/my/path/tests/';

		$result = $this->Task->testCaseFileName('Model', 'Post');
		$expected = $this->Task->path . 'cases' . DS . 'models' . DS . 'post.test.php';
		$this->assertEqual($result, $expected);

		$result = $this->Task->testCaseFileName('Helper', 'Form');
		$expected = $this->Task->path . 'cases' . DS . 'helpers' . DS . 'form.test.php';
		$this->assertEqual($result, $expected);

		$result = $this->Task->testCaseFileName('Controller', 'Posts');
		$expected = $this->Task->path . 'cases' . DS . 'controllers' . DS . 'posts_controller.test.php';
		$this->assertEqual($result, $expected);

		$result = $this->Task->testCaseFileName('Behavior', 'Containable');
		$expected = $this->Task->path . 'cases' . DS . 'behaviors' . DS . 'containable.test.php';
		$this->assertEqual($result, $expected);

		$result = $this->Task->testCaseFileName('Component', 'Auth');
		$expected = $this->Task->path . 'cases' . DS . 'components' . DS . 'auth.test.php';
		$this->assertEqual($result, $expected);

		$this->Task->plugin = 'TestTest';
		$result = $this->Task->testCaseFileName('Model', 'Post');
		$expected = APP . 'plugins' . DS . 'test_test' . DS . 'tests' . DS . 'cases' . DS . 'models' . DS . 'post.test.php';
		$this->assertEqual($result, $expected);
	}

/**
 * test execute with a type defined
 *
 * @return void
 * @access public
 */
	function testExecuteWithOneArg() {
		$this->Task->args[0] = 'Model';
		$this->Task->setReturnValueAt(0, 'in', 'TestTaskTag');
		$this->Task->setReturnValue('isLoadableClass', true);
		$this->Task->expectAt(0, 'createFile', array('*', new PatternExpectation('/class TestTaskTagTestCase extends CakeTestCase/')));
		$this->Task->execute();
	}

/**
 * test execute with type and class name defined
 *
 * @return void
 * @access public
 */
	function testExecuteWithTwoArgs() {
		$this->Task->args = array('Model', 'TestTaskTag');
		$this->Task->setReturnValueAt(0, 'in', 'TestTaskTag');
		$this->Task->setReturnValue('isLoadableClass', true);
		$this->Task->expectAt(0, 'createFile', array('*', new PatternExpectation('/class TestTaskTagTestCase extends CakeTestCase/')));
		$this->Task->execute();
	}
}
