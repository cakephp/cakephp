<?php
/**
 * TestTaskTest file
 *
 * Test Case for test generation shell task
 *
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         CakePHP v 1.2.0.7726
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\TemplateTask;
use Cake\Console\Command\Task\TestTask;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Cake\Utility\ClassRegistry;

/**
 * Test Article model
 *
 */
class TestTaskArticlesTable extends Table {

/**
 * Table name to use
 *
 * @var string
 */
	protected $_table = 'articles';

/**
 * HasMany Associations
 *
 * @var array
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
 */
class TestTaskTags extends Table {

/**
 * Table name
 *
 * @var string
 */
	public $useTable = 'tags';

/**
 * Has and Belongs To Many Associations
 *
 * @var array
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
 */
class TestTaskAppModel extends Table {
}

/**
 * Testing AppMode (TaskComment)
 *
 */
class TestTaskComment extends TestTaskAppModel {

/**
 * Table name
 *
 * @var string
 */
	protected $_table = 'comments';

/**
 * Belongs To Associations
 *
 * @var array
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
 */
class TestTaskCommentsController extends Controller {

/**
 * Models to use
 *
 * @var array
 */
	public $uses = array('TestTaskComment', 'TestTaskTag');
}

/**
 * TestTaskTest class
 *
 */
class TestTaskTest extends TestCase {

/**
 * Fixtures
 *
 * @var string
 */
	public $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('Cake\Console\ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\TestTask',
			array('in', 'err', 'createFile', '_stop', 'isLoadableClass'),
			array($out, $out, $in)
		);
		$this->Task->name = 'Test';
		$this->Task->Template = new TemplateTask($out, $out, $in);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
		Plugin::unload();
	}

/**
 * Test that file path generation doesn't continuously append paths.
 *
 * @return void
 */
	public function testFilePathGenerationModelRepeated() {
		$this->Task->expects($this->never())->method('err');
		$this->Task->expects($this->never())->method('_stop');

		$file = TESTS . 'TestCase/Model/MyClassTest.php';

		$this->Task->expects($this->at(1))->method('createFile')
			->with($file, $this->anything());

		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, $this->anything());

		$file = TESTS . 'TestCase/Controller/CommentsControllerTest.php';
		$this->Task->expects($this->at(5))->method('createFile')
			->with($file, $this->anything());

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
	public function testMethodIntrospection() {
		$result = $this->Task->getTestableMethods(__NAMESPACE__ . '\TestTaskArticlesTable');
		$expected = array('dosomething', 'dosomethingelse');
		$this->assertEquals($expected, array_map('strtolower', $result));
	}

/**
 * test that the generation of fixtures works correctly.
 *
 * @return void
 */
	public function testFixtureArrayGenerationFromModel() {
		$this->markTestIncomplete('Not working right now');

		$subject = new TestTaskArticlesTable();
		$result = $this->Task->generateFixtureList($subject);
		$expected = array('plugin.test_task.test_task_comment', 'app.articles_tags',
			'app.test_task_article', 'app.test_task_tag');

		$this->assertEquals(sort($expected), sort($result));
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

		$this->assertEquals(sort($expected), sort($result));
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
		$this->assertEquals($this->Task->classTypes['Controller'], $result);
	}

/**
 * creating test subjects should clear the registry so the registry is always fresh
 *
 * @return void
 */
	public function testRegistryClearWhenBuildingTestObjects() {
		$this->markTestIncomplete('Not working right now');

		$model = new TestTaskCommentsTable();
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
		$this->Task->buildTestSubject('Model', 'TestTaskComment');

		$keys = ClassRegistry::keys();
		$this->assertFalse(in_array('random', $keys));
	}

/**
 * test that getClassName returns the user choice as a class name.
 *
 * @return void
 */
	public function testGetClassName() {
		$objects = App::objects('model');
		$this->skipIf(empty($objects), 'No models in app.');

		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('MyCustomClass'));
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue(1));

		$result = $this->Task->getClassName('Model');
		$this->assertEquals('MyCustomClass', $result);

		$result = $this->Task->getClassName('Model');
		$options = App::objects('model');
		$this->assertEquals($options[0], $result);
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
		$this->assertEquals($expected, $result);
	}

/**
 * test that resolving class names works
 *
 * @return void
 */
	public function testGetRealClassname() {
		$result = $this->Task->getRealClassname('Model', 'Post');
		$this->assertEquals('App\Model\Post', $result);

		$result = $this->Task->getRealClassname('Controller', 'Posts');
		$this->assertEquals('App\Controller\PostsController', $result);

		$result = $this->Task->getRealClassname('Controller', 'PostsController');
		$this->assertEquals('App\Controller\PostsController', $result);

		$result = $this->Task->getRealClassname('Controller', 'AlertTypes');
		$this->assertEquals('App\Controller\AlertTypesController', $result);

		$result = $this->Task->getRealClassname('Helper', 'Form');
		$this->assertEquals('App\View\Helper\FormHelper', $result);

		$result = $this->Task->getRealClassname('Helper', 'FormHelper');
		$this->assertEquals('App\View\Helper\FormHelper', $result);

		$result = $this->Task->getRealClassname('Behavior', 'Tree');
		$this->assertEquals('App\Model\Behavior\TreeBehavior', $result);

		$result = $this->Task->getRealClassname('Component', 'Auth');
		$this->assertEquals('App\Controller\Component\AuthComponent', $result);

		$result = $this->Task->getRealClassname('Component', 'Utility', 'TestPlugin');
		$this->assertEquals('TestPlugin\Controller\Component\UtilityComponent', $result);
	}

/**
 * Test baking a test for a concrete model.
 *
 * @return void
 */
	public function testBakeModelTest() {
		$this->markTestIncomplete('Model tests need reworking.');

		$this->Task->expects($this->once())->method('createFile')->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('isLoadableClass')->will($this->returnValue(true));

		$result = $this->Task->bake('Model', 'TestTaskArticle');

		$this->assertContains("use App\Model\TestTaskArticle", $result);
		$this->assertContains('class TestTaskArticleTest extends TestCase', $result);

		$this->assertContains('function setUp()', $result);
		$this->assertContains("\$this->TestTaskArticle = ClassRegistry::init('TestTaskArticle')", $result);

		$this->assertContains('function tearDown()', $result);
		$this->assertContains('unset($this->TestTaskArticle)', $result);
	}

/**
 * test baking controller test files
 *
 * @return void
 */
	public function testBakeControllerTest() {
		$this->markTestIncomplete('This test explodes because of namespicing');

		$this->Task->expects($this->once())->method('createFile')->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('isLoadableClass')->will($this->returnValue(true));

		$result = $this->Task->bake('Controller', 'TestTaskComments');

		$this->assertContains("App::uses('TestTaskCommentsController', 'Controller')", $result);
		$this->assertContains('class TestTaskCommentsControllerTest extends ControllerTestCase', $result);

		$this->assertNotContains('function setUp()', $result);
		$this->assertNotContains("\$this->TestTaskComments = new TestTaskCommentsController()", $result);
		$this->assertNotContains("\$this->TestTaskComments->constructClasses()", $result);

		$this->assertNotContains('function tearDown()', $result);
		$this->assertNotContains('unset($this->TestTaskComments)', $result);

		$this->assertContains("'app.test_task_article'", $result);
		$this->assertContains("'app.test_task_comment'", $result);
		$this->assertContains("'app.test_task_tag'", $result);
		$this->assertContains("'app.articles_tag'", $result);
	}

/**
 * test baking component test files,
 *
 * @return void
 */
	public function testBakeComponentTest() {
		Configure::write('App.namespace', 'TestApp');
		$this->Task->expects($this->once())->method('createFile')->will($this->returnValue(true));

		$result = $this->Task->bake('Component', 'Apple');

		$this->assertContains('use Cake\Controller\ComponentRegistry', $result);
		$this->assertContains('use TestApp\Controller\Component\AppleComponent', $result);
		$this->assertContains('class AppleComponentTest extends TestCase', $result);

		$this->assertContains('function setUp()', $result);
		$this->assertContains("\$registry = new ComponentRegistry()", $result);
		$this->assertContains("\$this->Apple = new AppleComponent(\$registry)", $result);

		$this->assertContains('function testStartup()', $result);
		$this->assertContains('$this->markTestIncomplete(\'testStartup not implemented.\')', $result);

		$this->assertContains('function tearDown()', $result);
		$this->assertContains('unset($this->Apple)', $result);
	}

/**
 * test baking behavior test files,
 *
 * @return void
 */
	public function testBakeBehaviorTest() {
		$this->Task->expects($this->once())->method('createFile')->will($this->returnValue(true));

		$result = $this->Task->bake('Behavior', 'Example');

		$this->assertContains("use App\Model\Behavior\ExampleBehavior;", $result);
		$this->assertContains('class ExampleBehaviorTest extends TestCase', $result);

		$this->assertContains('function setUp()', $result);
		$this->assertContains("\$this->Example = new ExampleBehavior()", $result);

		$this->assertContains('function tearDown()', $result);
		$this->assertContains('unset($this->Example)', $result);
	}

/**
 * test baking helper test files,
 *
 * @return void
 */
	public function testBakeHelperTest() {
		$this->Task->expects($this->once())->method('createFile')->will($this->returnValue(true));

		$result = $this->Task->bake('Helper', 'Example');

		$this->assertContains("use Cake\View\View;", $result);
		$this->assertContains("use App\View\Helper\ExampleHelper;", $result);
		$this->assertContains('class ExampleHelperTest extends TestCase', $result);

		$this->assertContains('function setUp()', $result);
		$this->assertContains("\$View = new View()", $result);
		$this->assertContains("\$this->Example = new ExampleHelper(\$View)", $result);

		$this->assertContains('function tearDown()', $result);
		$this->assertContains('unset($this->Example)', $result);
	}

/**
 * test Constructor generation ensure that constructClasses is called for controllers
 *
 * @return void
 */
	public function testGenerateConstructor() {
		$result = $this->Task->generateConstructor('controller', 'PostsController', null);
		$expected = array('', '', '');
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateConstructor('model', 'Post', null);
		$expected = array('', "ClassRegistry::init('Post');\n", '');
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateConstructor('helper', 'FormHelper', null);
		$expected = array("\$View = new View();\n", "new FormHelper(\$View);\n", '');
		$this->assertEquals($expected, $result);
	}

/**
 * Test generateUses()
 */
	public function testGenerateUses() {
		$result = $this->Task->generateUses('model', 'Model', 'App\Model\Post');
		$expected = array(
			'App\Model\Post',
		);
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateUses('controller', 'Controller', 'App\Controller\PostsController');
		$expected = array(
			'App\Controller\PostsController',
		);
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateUses('helper', 'View/Helper', 'App\View\Helper\FormHelper');
		$expected = array(
			'Cake\View\View',
			'App\View\Helper\FormHelper',
		);
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateUses('component', 'Controller/Component', 'App\Controller\Component\AuthComponent');
		$expected = array(
			'Cake\Controller\ComponentRegistry',
			'App\Controller\Component\AuthComponent',
		);
		$this->assertEquals($expected, $result);
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

		//fake plugin path
		Plugin::load('TestTest', array('path' => APP . 'Plugin/TestTest/'));
		$path = APP . 'Plugin/TestTest/Test/TestCase/View/Helper/FormHelperTest.php';
		$this->Task->expects($this->once())->method('createFile')
			->with($path, $this->anything());

		$this->Task->bake('Helper', 'Form');
		Plugin::unload();
	}

/**
 * test interactive with plugins lists from the plugin
 *
 * @return void
 */
	public function testInteractiveWithPlugin() {
		$testApp = TEST_APP . 'Plugin/';
		Plugin::load('TestPlugin');

		$this->Task->plugin = 'TestPlugin';
		$path = $testApp . 'TestPlugin/Test/TestCase/View/Helper/OtherHelperTest.php';
		$this->Task->expects($this->any())
			->method('in')
			->will($this->onConsecutiveCalls(
				5, //helper
				1 //OtherHelper
			));

		$this->Task->expects($this->once())
			->method('createFile')
			->with($path, $this->anything());

		$this->Task->stdout->expects($this->at(21))
			->method('write')
			->with('1. OtherHelperHelper');

		$this->Task->execute();
	}

	public static function caseFileNameProvider() {
		return array(
			array('Model', 'Post', 'TestCase/Model/PostTest.php'),
			array('Helper', 'Form', 'TestCase/View/Helper/FormHelperTest.php'),
			array('Controller', 'Posts', 'TestCase/Controller/PostsControllerTest.php'),
			array('Behavior', 'Tree', 'TestCase/Model/Behavior/TreeBehaviorTest.php'),
			array('Component', 'Auth', 'TestCase/Controller/Component/AuthComponentTest.php'),
			array('model', 'Post', 'TestCase/Model/PostTest.php'),
			array('helper', 'Form', 'TestCase/View/Helper/FormHelperTest.php'),
			array('controller', 'Posts', 'TestCase/Controller/PostsControllerTest.php'),
			array('behavior', 'Tree', 'TestCase/Model/Behavior/TreeBehaviorTest.php'),
			array('component', 'Auth', 'TestCase/Controller/Component/AuthComponentTest.php'),
		);
	}

/**
 * Test filename generation for each type + plugins
 *
 * @dataProvider caseFileNameProvider
 * @return void
 */
	public function testTestCaseFileName($type, $class, $expected) {
		$this->Task->path = DS . 'my/path/tests/';

		$result = $this->Task->testCaseFileName($type, $class);
		$expected = $this->Task->path . $expected;
		$this->assertEquals($expected, $result);
	}

/**
 * Test filename generation for plugins.
 *
 * @return void
 */
	public function testTestCaseFileNamePlugin() {
		$this->Task->path = DS . 'my/path/tests/';

		Plugin::load('TestTest', array('path' => APP . 'Plugin/TestTest/'));
		$this->Task->plugin = 'TestTest';
		$result = $this->Task->testCaseFileName('Model', 'Post');
		$expected = APP . 'Plugin/TestTest/Test/TestCase/Model/PostTest.php';
		$this->assertEquals($expected, $result);
	}

/**
 * test execute with a type defined
 *
 * @return void
 */
	public function testExecuteWithOneArg() {
		$this->markTestIncomplete('Tests using models need work');

		$this->Task->args[0] = 'Model';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('TestTaskTag'));
		$this->Task->expects($this->once())->method('isLoadableClass')->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('createFile')
			->with(
				$this->anything(),
				$this->stringContains('class TestTaskTagTest extends TestCase')
			);
		$this->Task->execute();
	}

/**
 * test execute with type and class name defined
 *
 * @return void
 */
	public function testExecuteWithTwoArgs() {
		$this->markTestIncomplete('Tests using models need work');

		$this->Task->args = array('Model', 'TestTaskTag');
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('TestTaskTag'));
		$this->Task->expects($this->once())->method('createFile')
			->with(
				$this->anything(),
				$this->stringContains('class TestTaskTagTest extends TestCase')
			);
		$this->Task->expects($this->any())->method('isLoadableClass')->will($this->returnValue(true));
		$this->Task->execute();
	}

/**
 * test execute with type and class name defined and lower case.
 *
 * @return void
 */
	public function testExecuteWithTwoArgsLowerCase() {
		$this->markTestIncomplete('Tests using models need work');

		$this->Task->args = array('model', 'TestTaskTag');
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('TestTaskTag'));
		$this->Task->expects($this->once())->method('createFile')
			->with(
				$this->anything(),
				$this->stringContains('class TestTaskTagTest extends TestCase')
			);
		$this->Task->expects($this->any())->method('isLoadableClass')->will($this->returnValue(true));
		$this->Task->execute();
	}

/**
 * Data provider for mapType() tests.
 *
 * @return array
 */
	public static function mapTypeProvider() {
		return array(
			array('controller', null, 'Controller'),
			array('Controller', null, 'Controller'),
			array('component', null, 'Controller/Component'),
			array('Component', null, 'Controller/Component'),
			array('model', null, 'Model'),
			array('Model', null, 'Model'),
			array('behavior', null, 'Model/Behavior'),
			array('Behavior', null, 'Model/Behavior'),
			array('helper', null, 'View/Helper'),
			array('Helper', null, 'View/Helper'),
			array('Helper', 'DebugKit', 'DebugKit.View/Helper'),
		);
	}

/**
 * Test that mapType returns the correct package names.
 *
 * @dataProvider mapTypeProvider
 * @return void
 */
	public function testMapType($original, $plugin, $expected) {
		$this->assertEquals($expected, $this->Task->mapType($original, $plugin));
	}
}
