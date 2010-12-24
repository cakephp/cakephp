<?php
/**
 * TestTaskTest file
 *
 * Test Case for test generation shell task
 *
 * PHP 5
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);
App::import('Shell', array(
	'tasks/test',
	'tasks/template'
));

App::import('Controller', 'Controller', false);
App::import('Model', 'Model', false);

require_once CAKE . 'console' .  DS . 'shell_dispatcher.php';

/**
 * Test Article model
 *
 * @package cake
 * @package    cake.tests.cases.console.libs.tasks
 */
class TestTaskArticle extends Model {

/**
 * Model name
 *
 * @var string
 * @access public
 */
	public $name = 'TestTaskArticle';

/**
 * Table name to use
 *
 * @var string
 * @access public
 */
	public $useTable = 'articles';

/**
 * HasMany Associations
 *
 * @var array
 * @access public
 */
	public $hasMany = array(
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
	public $hasAndBelongsToMany = array(
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
 */
	public function doSomething() {
	}

/**
 * Example Secondary public method
 *
 * @return void
 */
	public function doSomethingElse() {
	}

/**
 * Example protected method
 *
 * @return void
 */
	protected function _innerMethod() {
	}
}

/**
 * Tag Testing Model
 *
 * @package cake
 * @package    cake.tests.cases.console.libs.tasks
 */
class TestTaskTag extends Model {

/**
 * Model name
 *
 * @var string
 * @access public
 */
	public $name = 'TestTaskTag';

/**
 * Table name
 *
 * @var string
 * @access public
 */
	public $useTable = 'tags';

/**
 * Has and Belongs To Many Associations
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array(
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
 * @package    cake.tests.cases.console.libs.tasks
 */
class TestTaskAppModel extends Model {
}

/**
 * Testing AppMode (TaskComment)
 *
 * @package cake
 * @package    cake.tests.cases.console.libs.tasks
 */
class TestTaskComment extends TestTaskAppModel {

/**
 * Model name
 *
 * @var string
 * @access public
 */
	public $name = 'TestTaskComment';

/**
 * Table name
 *
 * @var string
 * @access public
 */
	public $useTable = 'comments';

/**
 * Belongs To Associations
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
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
 * @package    cake.tests.cases.console.libs.tasks
 */
class TestTaskCommentsController extends Controller {

/**
 * Controller Name
 *
 * @var string
 * @access public
 */
	public $name = 'TestTaskComments';

/**
 * Models to use
 *
 * @var array
 * @access public
 */
	public $uses = array('TestTaskComment', 'TestTaskTag');
}

/**
 * TestTaskTest class
 *
 * @package       cake.tests.cases.console.libs.tasks
 */
class TestTaskTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var string
 * @access public
 */
	public $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag');

/**
 * setup method
 *
 * @return void
 */
	public function setup() {
		parent::setup();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('TestTask', 
			array('in', 'err', 'createFile', '_stop', 'isLoadableClass'),
			array($out, $out, $in)
		);
		$this->Task->name = 'Test';
		$this->Task->Template = new TemplateTask($out, $out, $in);
	}

/**
 * endTest method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
	}

/**
 * Test that file path generation doesn't continuously append paths.
 *
 * @return void
 */
	public function testFilePathGenerationModelRepeated() {
		$this->Task->expects($this->never())->method('err');
		$this->Task->expects($this->never())->method('_stop');

		$file = TESTS . 'cases' . DS . 'models' . DS . 'my_class.test.php';

		$this->Task->expects($this->at(1))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$file = TESTS . 'cases' . DS . 'controllers' . DS . 'comments_controller.test.php';
		$this->Task->expects($this->at(5))->method('createFile')
			->with($file, new PHPUnit_Framework_Constraint_IsAnything());

		$this->Task->bake('Model', 'MyClass');
		$this->Task->bake('Model', 'MyClass');
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
 */
	public function testFixtureArrayGenerationFromModel() {
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
 */
	public function testFixtureArrayGenerationFromController() {
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
 */
	public function testGetObjectType() {
		$this->Task->expects($this->once())->method('_stop');
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('q'));
		$this->Task->expects($this->at(2))->method('in')->will($this->returnValue(2));

		$this->Task->getObjectType();

		$result = $this->Task->getObjectType();
		$this->assertEqual($result, $this->Task->classTypes[1]);
	}

/**
 * creating test subjects should clear the registry so the registry is always fresh
 *
 * @return void
 */
	public function testRegistryClearWhenBuildingTestObjects() {
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
		$this->assertTrue(in_array('test_task_comment', $keys));
		$object = $this->Task->buildTestSubject('Model', 'TestTaskComment');

		$keys = ClassRegistry::keys();
		$this->assertFalse(in_array('random', $keys));
	}

/**
 * test that getClassName returns the user choice as a classname.
 *
 * @return void
 */
	public function testGetClassName() {
		$objects = App::objects('model');
		$skip = $this->skipIf(empty($objects), 'No models in app, this test will fail. %s');
		if ($skip) {
			return;
		}
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('MyCustomClass'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue(1));

		$result = $this->Task->getClassName('Model');
		$this->assertEqual($result, 'MyCustomClass');

		$result = $this->Task->getClassName('Model');
		$options = App::objects('model');
		$this->assertEqual($result, $options[0]);
	}

/**
 * Test the user interaction for defining additional fixtures.
 *
 * @return void
 */
	public function testGetUserFixtures() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->expects($this->at(1))->method('in')
			->will($this->returnValue('app.pizza, app.topping, app.side_dish'));

		$result = $this->Task->getUserFixtures();
		$expected = array('app.pizza', 'app.topping', 'app.side_dish');
		$this->assertEqual($result, $expected);
	}

/**
 * test that resolving classnames works
 *
 * @return void
 */
	public function testGetRealClassname() {
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
 */
	public function testBakeModelTest() {
		$this->Task->expects($this->once())->method('createFile')->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('isLoadableClass')->will($this->returnValue(true));

		$result = $this->Task->bake('Model', 'TestTaskArticle');

		$this->assertContains("App::import('Model', 'TestTaskArticle')", $result);
		$this->assertContains('class TestTaskArticleTestCase extends CakeTestCase', $result);

		$this->assertContains('function startTest()', $result);
		$this->assertContains("\$this->TestTaskArticle = ClassRegistry::init('TestTaskArticle')", $result);

		$this->assertContains('function endTest()', $result);
		$this->assertContains('unset($this->TestTaskArticle)', $result);

		$this->assertContains('function testDoSomething()', $result);
		$this->assertContains('function testDoSomethingElse()', $result);

		$this->assertContains("'app.test_task_article'", $result);
		$this->assertContains("'plugin.test_task.test_task_comment'", $result);
		$this->assertContains("'app.test_task_tag'", $result);
		$this->assertContains("'app.articles_tag'", $result);
	}

/**
 * test baking controller test files, ensure that the stub class is generated.
 * Conditional assertion is known to fail on PHP4 as classnames are all lower case
 * causing issues with inflection of path name from classname.
 *
 * @return void
 */
	public function testBakeControllerTest() {
		$this->Task->expects($this->once())->method('createFile')->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('isLoadableClass')->will($this->returnValue(true));

		$result = $this->Task->bake('Controller', 'TestTaskComments');

		$this->assertContains("App::import('Controller', 'TestTaskComments')", $result);
		$this->assertContains('class TestTaskCommentsControllerTestCase extends CakeTestCase', $result);

		$this->assertContains('class TestTestTaskCommentsController extends TestTaskCommentsController', $result);
		$this->assertContains('public $autoRender = false', $result);
		$this->assertContains('function redirect($url, $status = null, $exit = true)', $result);

		$this->assertContains('function startTest()', $result);
		$this->assertContains("\$this->TestTaskComments = new TestTestTaskCommentsController()", $result);
		$this->assertContains("\$this->TestTaskComments->constructClasses()", $result);

		$this->assertContains('function endTest()', $result);
		$this->assertContains('unset($this->TestTaskComments)', $result);

		$this->assertContains("'app.test_task_article'", $result);
		$this->assertContains("'plugin.test_task.test_task_comment'", $result);
		$this->assertContains("'app.test_task_tag'", $result);
		$this->assertContains("'app.articles_tag'", $result);
	}

/**
 * test Constructor generation ensure that constructClasses is called for controllers
 *
 * @return void
 */
	public function testGenerateConstructor() {
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
 */
	public function testMockClassGeneration() {
		$result = $this->Task->hasMockClass('controller');
		$this->assertTrue($result);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 */
	public function testBakeWithPlugin() {
		$this->Task->plugin = 'TestTest';

		$path = APP . 'plugins' . DS . 'test_test' . DS . 'tests' . DS . 'cases' . DS . 'helpers' . DS . 'form.test.php';
		$this->Task->expects($this->once())->method('createFile')
			->with($path, new PHPUnit_Framework_Constraint_IsAnything());

		$this->Task->bake('Helper', 'Form');
	}

/**
 * Test filename generation for each type + plugins
 *
 * @return void
 */
	public function testTestCaseFileName() {
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
 */
	public function testExecuteWithOneArg() {
		$this->Task->args[0] = 'Model';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('TestTaskTag'));
		$this->Task->expects($this->once())->method('isLoadableClass')->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('createFile')
			->with(
				new PHPUnit_Framework_Constraint_IsAnything(),
				new PHPUnit_Framework_Constraint_PCREMatch('/class TestTaskTagTestCase extends CakeTestCase/')
			);
		$this->Task->execute();
	}

/**
 * test execute with type and class name defined
 *
 * @return void
 */
	public function testExecuteWithTwoArgs() {
		$this->Task->args = array('Model', 'TestTaskTag');
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('TestTaskTag'));
		$this->Task->expects($this->once())->method('createFile')
			->with(
				new PHPUnit_Framework_Constraint_IsAnything(),
				new PHPUnit_Framework_Constraint_PCREMatch('/class TestTaskTagTestCase extends CakeTestCase/')
			);
		$this->Task->expects($this->any())->method('isLoadableClass')->will($this->returnValue(true));
		$this->Task->execute();
	}
}
