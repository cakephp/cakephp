<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         1.2.0
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
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use TestApp\Controller\PostsController;
use TestApp\Model\Table\ArticlesTable;

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
	public $fixtures = ['core.article', 'core.author',
		'core.comment', 'core.articles_tag', 'core.tag'];

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\TestTask',
			array('in', 'err', 'createFile', '_stop', 'isLoadableClass'),
			array($this->io)
		);
		$this->Task->name = 'Test';
		$this->Task->Template = new TemplateTask($this->io);
		$this->Task->Template->interactive = false;
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
 * Test that with no args execute() outputs the types you can generate
 * tests for.
 *
 * @return void
 */
	public function testExecuteNoArgsPrintsTypeOptions() {
		$this->Task = $this->getMockBuilder('Cake\Console\Command\Task\TestTask')
			->disableOriginalConstructor()
			->setMethods(['outputTypeChoices'])
			->getMock();

		$this->Task->expects($this->once())
			->method('outputTypeChoices');

		$this->Task->main();
	}

/**
 * Test outputTypeChoices method
 *
 * @return void
 */
	public function testOutputTypeChoices() {
		$this->io->expects($this->at(0))
			->method('out')
			->with($this->stringContains('You must provide'));
		$this->io->expects($this->at(1))
			->method('out')
			->with($this->stringContains('1. Entity'));
		$this->io->expects($this->at(2))
			->method('out')
			->with($this->stringContains('2. Table'));
		$this->io->expects($this->at(3))
			->method('out')
			->with($this->stringContains('3. Controller'));
		$this->Task->outputTypeChoices();
	}

/**
 * Test that with no args execute() outputs the types you can generate
 * tests for.
 *
 * @return void
 */
	public function testExecuteOneArgPrintsClassOptions() {
		$this->Task = $this->getMockBuilder('Cake\Console\Command\Task\TestTask')
			->disableOriginalConstructor()
			->setMethods(['outputClassChoices'])
			->getMock();

		$this->Task->expects($this->once())
			->method('outputClassChoices');

		$this->Task->main('Entity');
	}

/**
 * test execute with type and class name defined
 *
 * @return void
 */
	public function testExecuteWithTwoArgs() {
		$this->Task->expects($this->once())->method('createFile')
			->with(
				$this->stringContains('TestCase' . DS . 'Model' . DS . 'Table' . DS . 'TestTaskTagTableTest.php'),
				$this->stringContains('class TestTaskTagTableTest extends TestCase')
			);
		$this->Task->main('Table', 'TestTaskTag');
	}

/**
 * Test generating class options for table.
 *
 * @return void
 */
	public function testOutputClassOptionsForTable() {
		$this->io->expects($this->at(0))
			->method('out')
			->with($this->stringContains('You must provide'));
		$this->io->expects($this->at(1))
			->method('out')
			->with($this->stringContains('1. ArticlesTable'));
		$this->io->expects($this->at(2))
			->method('out')
			->with($this->stringContains('2. ArticlesTagsTable'));
		$this->io->expects($this->at(3))
			->method('out')
			->with($this->stringContains('3. AuthUsersTable'));
		$this->io->expects($this->at(4))
			->method('out')
			->with($this->stringContains('4. AuthorsTable'));

		$this->Task->outputClassChoices('Table');
	}

/**
 * Test generating class options for table.
 *
 * @return void
 */
	public function testOutputClassOptionsForTablePlugin() {
		Plugin::load('TestPlugin');

		$this->Task->plugin = 'TestPlugin';
		$this->io->expects($this->at(0))
			->method('out')
			->with($this->stringContains('You must provide'));
		$this->io->expects($this->at(1))
			->method('out')
			->with($this->stringContains('1. TestPluginCommentsTable'));

		$this->Task->outputClassChoices('Table');
	}

/**
 * Test that method introspection pulls all relevant non parent class
 * methods into the test case.
 *
 * @return void
 */
	public function testMethodIntrospection() {
		$result = $this->Task->getTestableMethods('TestApp\Model\Table\ArticlesTable');
		$expected = array('dosomething', 'dosomethingelse');
		$this->assertEquals($expected, array_map('strtolower', $result));
	}

/**
 * test that the generation of fixtures works correctly.
 *
 * @return void
 */
	public function testFixtureArrayGenerationFromModel() {
		$subject = new ArticlesTable();
		$result = $this->Task->generateFixtureList($subject);
		$expected = [
			'app.article',
			'app.author',
			'app.tag',
			'app.articles_tag'
		];
		$this->assertEquals($expected, $result);
	}

/**
 * test that the generation of fixtures works correctly.
 *
 * @return void
 */
	public function testFixtureArrayGenerationFromController() {
		$subject = new PostsController();
		$result = $this->Task->generateFixtureList($subject);
		$expected = [
			'app.post',
		];
		$this->assertEquals($expected, $result);
	}

/**
 * creating test subjects should clear the registry so the registry is always fresh
 *
 * @return void
 */
	public function testRegistryClearWhenBuildingTestObjects() {
		$articles = TableRegistry::get('Articles');
		$this->Task->buildTestSubject('Table', 'Posts');

		$this->assertFalse(TableRegistry::exists('Articles'));
	}

/**
 * Dataprovider for class name generation.
 *
 * @return array
 */
	public static function realClassProvider() {
		return [
			['Entity', 'Article', 'App\Model\Entity\Article'],
			['entity', 'ArticleEntity', 'App\Model\Entity\ArticleEntity'],
			['Table', 'Posts', 'App\Model\Table\PostsTable'],
			['table', 'PostsTable', 'App\Model\Table\PostsTable'],
			['Controller', 'Posts', 'App\Controller\PostsController'],
			['controller', 'PostsController', 'App\Controller\PostsController'],
			['Behavior', 'Timestamp', 'App\Model\Behavior\TimestampBehavior'],
			['behavior', 'TimestampBehavior', 'App\Model\Behavior\TimestampBehavior'],
			['Helper', 'Form', 'App\View\Helper\FormHelper'],
			['helper', 'FormHelper', 'App\View\Helper\FormHelper'],
			['Component', 'Auth', 'App\Controller\Component\AuthComponent'],
			['component', 'AuthComponent', 'App\Controller\Component\AuthComponent'],
			['Shell', 'Example', 'App\Console\Command\ExampleShell'],
			['shell', 'Example', 'App\Console\Command\ExampleShell'],
			['Cell', 'Example', 'App\View\Cell\ExampleCell'],
			['cell', 'Example', 'App\View\Cell\ExampleCell'],
		];
	}

/**
 * test that resolving class names works
 *
 * @dataProvider realClassProvider
 * @return void
 */
	public function testGetRealClassname($type, $name, $expected) {
		$result = $this->Task->getRealClassname($type, $name);
		$this->assertEquals($expected, $result);
	}

/**
 * test resolving class names with plugins
 *
 * @return void
 */
	public function testGetRealClassnamePlugin() {
		Plugin::load('TestPlugin');
		$this->Task->plugin = 'TestPlugin';
		$result = $this->Task->getRealClassname('Helper', 'Asset');
		$expected = 'TestPlugin\View\Helper\AssetHelper';
		$this->assertEquals($expected, $result);
	}

/**
 * Test baking a test for a concrete model with fixtures arg
 *
 * @return void
 */
	public function testBakeFixturesParam() {
		$this->Task->expects($this->once())
			->method('createFile')
			->will($this->returnValue(true));

		$this->Task->params['fixtures'] = 'app.post, app.comments , app.user ,';
		$result = $this->Task->bake('Table', 'Articles');

		$this->assertContains('public $fixtures = [', $result);
		$this->assertContains('app.post', $result);
		$this->assertContains('app.comments', $result);
		$this->assertContains('app.user', $result);
		$this->assertNotContains("''", $result);
	}

/**
 * Test baking a test for a cell.
 *
 * @return void
 */
	public function testBakeCellTest() {
		$this->Task->expects($this->once())
			->method('createFile')
			->will($this->returnValue(true));

		$result = $this->Task->bake('Cell', 'Articles');

		$this->assertContains("use App\View\Cell\ArticlesCell", $result);
		$this->assertContains('class ArticlesCellTest extends TestCase', $result);

		$this->assertContains('function setUp()', $result);
		$this->assertContains("\$this->request = \$this->getMock('Cake\Network\Request')", $result);
		$this->assertContains("\$this->response = \$this->getMock('Cake\Network\Response')", $result);
		$this->assertContains("\$this->Articles = new ArticlesCell(\$this->request, \$this->response", $result);
	}

/**
 * Test baking a test for a concrete model.
 *
 * @return void
 */
	public function testBakeModelTest() {
		$this->Task->expects($this->once())
			->method('createFile')
			->will($this->returnValue(true));

		$result = $this->Task->bake('Table', 'Articles');

		$this->assertContains("use App\Model\Table\ArticlesTable", $result);
		$this->assertContains('class ArticlesTableTest extends TestCase', $result);

		$this->assertContains('function setUp()', $result);
		$this->assertContains("\$config = TableRegistry::exists('Articles') ?", $result);
		$this->assertContains("\$this->Articles = TableRegistry::get('Articles', \$config", $result);

		$this->assertContains('function tearDown()', $result);
		$this->assertContains('unset($this->Articles)', $result);
	}

/**
 * test baking controller test files
 *
 * @return void
 */
	public function testBakeControllerTest() {
		Configure::write('App.namespace', 'TestApp');

		$this->Task->expects($this->once())
			->method('createFile')
			->will($this->returnValue(true));

		$result = $this->Task->bake('Controller', 'PostsController');

		$this->assertContains("use TestApp\Controller\PostsController", $result);
		$this->assertContains('class PostsControllerTest extends ControllerTestCase', $result);

		$this->assertNotContains('function setUp()', $result);
		$this->assertNotContains("\$this->Posts = new PostsController()", $result);
		$this->assertNotContains("\$this->Posts->constructClasses()", $result);

		$this->assertNotContains('function tearDown()', $result);
		$this->assertNotContains('unset($this->Posts)', $result);

		$this->assertContains("'app.post'", $result);
	}

/**
 * test baking controller test files
 *
 * @return void
 */
	public function testBakePrefixControllerTest() {
		Configure::write('App.namespace', 'TestApp');

		$this->Task->expects($this->once())
			->method('createFile')
			->with($this->stringContains('Controller' . DS . 'Admin' . DS . 'PostsControllerTest.php'))
			->will($this->returnValue(true));

		$result = $this->Task->bake('controller', 'Admin\Posts');

		$this->assertContains("use TestApp\Controller\Admin\PostsController", $result);
		$this->assertContains('class PostsControllerTest extends ControllerTestCase', $result);

		$this->assertNotContains('function setUp()', $result);
		$this->assertNotContains("\$this->Posts = new PostsController()", $result);
		$this->assertNotContains("\$this->Posts->constructClasses()", $result);

		$this->assertNotContains('function tearDown()', $result);
		$this->assertNotContains('unset($this->Posts)', $result);
	}

/**
 * test baking component test files,
 *
 * @return void
 */
	public function testBakeComponentTest() {
		Configure::write('App.namespace', 'TestApp');

		$this->Task->expects($this->once())
			->method('createFile')
			->will($this->returnValue(true));

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
		$this->Task->expects($this->once())
			->method('createFile')
			->will($this->returnValue(true));

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
		$this->Task->expects($this->once())
			->method('createFile')
			->will($this->returnValue(true));

		$result = $this->Task->bake('Helper', 'Example');

		$this->assertContains("use Cake\View\View;", $result);
		$this->assertContains("use App\View\Helper\ExampleHelper;", $result);
		$this->assertContains('class ExampleHelperTest extends TestCase', $result);

		$this->assertContains('function setUp()', $result);
		$this->assertContains("\$view = new View()", $result);
		$this->assertContains("\$this->Example = new ExampleHelper(\$view)", $result);

		$this->assertContains('function tearDown()', $result);
		$this->assertContains('unset($this->Example)', $result);
	}

/**
 * Test baking a test for a concrete model.
 *
 * @return void
 */
	public function testBakeShellTest() {
		$this->Task->expects($this->once())
			->method('createFile')
			->will($this->returnValue(true));

		$result = $this->Task->bake('Shell', 'Articles');

		$this->assertContains("use App\Console\Command\ArticlesShell", $result);
		$this->assertContains('class ArticlesShellTest extends TestCase', $result);

		$this->assertContains('function setUp()', $result);
		$this->assertContains("\$this->io = \$this->getMock('Cake\Console\ConsoleIo');", $result);
		$this->assertContains("\$this->Articles = new ArticlesShell(\$this->io);", $result);

		$this->assertContains('function tearDown()', $result);
		$this->assertContains('unset($this->Articles)', $result);
	}

/**
 * test Constructor generation ensure that constructClasses is called for controllers
 *
 * @return void
 */
	public function testGenerateConstructor() {
		$result = $this->Task->generateConstructor('controller', 'PostsController');
		$expected = ['', '', ''];
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateConstructor('table', 'App\Model\\Table\PostsTable');
		$expected = [
			"\$config = TableRegistry::exists('Posts') ? [] : ['className' => 'App\Model\\Table\PostsTable'];\n",
			"TableRegistry::get('Posts', \$config);\n",
			''
		];
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateConstructor('helper', 'FormHelper');
		$expected = ["\$view = new View();\n", "new FormHelper(\$view);\n", ''];
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateConstructor('entity', 'TestPlugin\Model\Entity\Article');
		$expected = ["", "new Article();\n", ''];
		$this->assertEquals($expected, $result);
	}

/**
 * Test generateUses()
 *
 * @return void
 */
	public function testGenerateUses() {
		$result = $this->Task->generateUses('table', 'App\Model\Table\PostsTable');
		$expected = array(
			'Cake\ORM\TableRegistry',
			'App\Model\Table\PostsTable',
		);
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateUses('controller', 'App\Controller\PostsController');
		$expected = array(
			'App\Controller\PostsController',
		);
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateUses('helper', 'App\View\Helper\FormHelper');
		$expected = array(
			'Cake\View\View',
			'App\View\Helper\FormHelper',
		);
		$this->assertEquals($expected, $result);

		$result = $this->Task->generateUses('component', 'App\Controller\Component\AuthComponent');
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
		$this->Task->plugin = 'TestPlugin';

		Plugin::load('TestPlugin');
		$path = TEST_APP . 'Plugin/TestPlugin/Test/TestCase/View/Helper/FormHelperTest.php';
		$path = str_replace('/', DS, $path);
		$this->Task->expects($this->once())->method('createFile')
			->with($path, $this->anything());

		$this->Task->bake('Helper', 'Form');
	}

/**
 * Provider for test case file names.
 *
 * @return array
 */
	public static function caseFileNameProvider() {
		return array(
			array('Table', 'App\Model\Table\PostsTable', 'TestCase/Model/Table/PostsTableTest.php'),
			array('Entity', 'App\Model\Entity\Article', 'TestCase/Model/Entity/ArticleTest.php'),
			array('Helper', 'App\View\Helper\FormHelper', 'TestCase/View/Helper/FormHelperTest.php'),
			array('Controller', 'App\Controller\PostsController', 'TestCase/Controller/PostsControllerTest.php'),
			array('Controller', 'App\Controller\Admin\PostsController', 'TestCase/Controller/Admin/PostsControllerTest.php'),
			array('Behavior', 'App\Model\Behavior\TreeBehavior', 'TestCase/Model/Behavior/TreeBehaviorTest.php'),
			array('Component', 'App\Controller\Component\AuthComponent', 'TestCase/Controller/Component/AuthComponentTest.php'),
			array('entity', 'App\Model\Entity\Article', 'TestCase/Model/Entity/ArticleTest.php'),
			array('table', 'App\Model\Table\PostsTable', 'TestCase/Model/Table/PostsTableTest.php'),
			array('helper', 'App\View\Helper\FormHelper', 'TestCase/View/Helper/FormHelperTest.php'),
			array('controller', 'App\Controller\PostsController', 'TestCase/Controller/PostsControllerTest.php'),
			array('behavior', 'App\Model\Behavior\TreeBehavior', 'TestCase/Model/Behavior/TreeBehaviorTest.php'),
			array('component', 'App\Controller\Component\AuthComponent', 'TestCase/Controller/Component/AuthComponentTest.php'),
			['Shell', 'App\Console\Command\ExampleShell', 'TestCase/Console/Command/ExampleShellTest.php'],
			['shell', 'App\Console\Command\ExampleShell', 'TestCase/Console/Command/ExampleShellTest.php'],
		);
	}

/**
 * Test filename generation for each type + plugins
 *
 * @dataProvider caseFileNameProvider
 * @return void
 */
	public function testTestCaseFileName($type, $class, $expected) {
		$result = $this->Task->testCaseFileName($type, $class);
		$expected = ROOT . DS . 'Test/' . $expected;
		$this->assertPathEquals($expected, $result);
	}

/**
 * Test filename generation for plugins.
 *
 * @return void
 */
	public function testTestCaseFileNamePlugin() {
		$this->Task->path = DS . 'my/path/tests/';

		Plugin::load('TestPlugin');
		$this->Task->plugin = 'TestPlugin';
		$class = 'TestPlugin\Model\Entity\Post';
		$result = $this->Task->testCaseFileName('entity', $class);

		$expected = TEST_APP . 'Plugin/TestPlugin/Test/TestCase/Model/Entity/PostTest.php';
		$this->assertPathEquals($expected, $result);
	}

/**
 * Data provider for mapType() tests.
 *
 * @return array
 */
	public static function mapTypeProvider() {
		return array(
			array('controller', 'Controller'),
			array('Controller', 'Controller'),
			array('component', 'Controller\Component'),
			array('Component', 'Controller\Component'),
			array('table', 'Model\Table'),
			array('Table', 'Model\Table'),
			array('entity', 'Model\Entity'),
			array('Entity', 'Model\Entity'),
			array('behavior', 'Model\Behavior'),
			array('Behavior', 'Model\Behavior'),
			array('helper', 'View\Helper'),
			array('Helper', 'View\Helper'),
			array('Helper', 'View\Helper'),
		);
	}

/**
 * Test that mapType returns the correct package names.
 *
 * @dataProvider mapTypeProvider
 * @return void
 */
	public function testMapType($original, $expected) {
		$this->assertEquals($expected, $this->Task->mapType($original));
	}
}
