<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Shell\Task\FixtureTask;
use Cake\Shell\Task\TemplateTask;
use Cake\TestSuite\TestCase;

/**
 * FixtureTaskTest class
 *
 */
class FixtureTaskTest extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = array('core.articles', 'core.comments', 'core.datatypes', 'core.binary_tests', 'core.users');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock('Cake\Shell\Task\FixtureTask',
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array($io)
		);
		$this->Task->Model = $this->getMock('Cake\Shell\Task\ModelTask',
			array('in', 'out', 'err', 'createFile', 'getName', 'getTable', 'listAll'),
			array($io)
		);
		$this->Task->Template = new TemplateTask($io);
		$this->Task->Template->interactive = false;
		$this->Task->Template->initialize();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
	}

/**
 * Test that initialize() copies the connection property over.
 *
 * @return void
 */
	public function testInitializeCopyConnection() {
		$this->assertEquals('', $this->Task->connection);
		$this->Task->params = ['connection' => 'test'];

		$this->Task->initialize();
		$this->assertEquals('test', $this->Task->connection);
	}

/**
 * test that initialize sets the path
 *
 * @return void
 */
	public function testGetPath() {
		$this->assertPathEquals(ROOT . '/tests/Fixture/', $this->Task->getPath());
	}

/**
 * test generating a fixture with database conditions.
 *
 * @return void
 */
	public function testImportRecordsFromDatabaseWithConditionsPoo() {
		$this->Task->connection = 'test';
		$this->Task->params = ['schema' => true, 'records' => true];

		$result = $this->Task->bake('Articles');

		$this->assertContains('namespace App\Test\Fixture;', $result);
		$this->assertContains('use Cake\TestSuite\Fixture\TestFixture;', $result);
		$this->assertContains('class ArticlesFixture extends TestFixture', $result);
		$this->assertContains('public $records', $result);
		$this->assertContains('public $import', $result);
		$this->assertContains("'title' => 'First Article'", $result, 'Missing import data %s');
		$this->assertContains('Second Article', $result, 'Missing import data %s');
		$this->assertContains('Third Article', $result, 'Missing import data %s');
	}

/**
 * test that connection gets set to the import options when a different connection is used.
 *
 * @return void
 */
	public function testImportOptionsAlternateConnection() {
		$this->Task->connection = 'test';
		$this->Task->params = ['schema' => true];
		$result = $this->Task->bake('Article');
		$this->assertContains("'connection' => 'test'", $result);
	}

/**
 * Ensure that fixture data doesn't get overly escaped.
 *
 * @return void
 */
	public function testImportRecordsNoEscaping() {
		$articles = TableRegistry::get('Articles');
		$articles->updateAll(['body' => "Body \"value\""], []);

		$this->Task->connection = 'test';
		$this->Task->params = ['schema' => 'true', 'records' => true];
		$result = $this->Task->bake('Article');
		$this->assertContains("'body' => 'Body \"value\"'", $result, 'Data has bad escaping');
	}

/**
 * Test the table option.
 *
 * @return void
 */
	public function testMainWithTableOption() {
		$this->Task->connection = 'test';
		$this->Task->params = ['table' => 'comments'];
		$filename = $this->_normalizePath(ROOT . '/tests/Fixture/ArticlesFixture.php');

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains("public \$table = 'comments';"));

		$this->Task->main('articles');
	}

/**
 * test that execute passes runs bake depending with named model.
 *
 * @return void
 */
	public function testMainWithPluginModel() {
		$this->Task->connection = 'test';
		$filename = $this->_normalizePath(TEST_APP . 'Plugin/TestPlugin/tests/Fixture/ArticlesFixture.php');

		Plugin::load('TestPlugin');

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains('class ArticlesFixture'));

		$this->Task->main('TestPlugin.Articles');
	}

/**
 * test that execute runs all() when args[0] = all
 *
 * @return void
 */
	public function testMainIntoAll() {
		$this->Task->connection = 'test';
		$this->Task->Model->expects($this->any())
			->method('listAll')
			->will($this->returnValue(array('articles', 'comments')));

		$filename = $this->_normalizePath(ROOT . '/tests/Fixture/ArticlesFixture.php');
		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains('class ArticlesFixture'));

		$filename = $this->_normalizePath(ROOT . '/tests/Fixture/CommentsFixture.php');
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with($filename, $this->stringContains('class CommentsFixture'));

		$this->Task->all();
	}

/**
 * test using all() with -count and -records
 *
 * @return void
 */
	public function testAllWithCountAndRecordsFlags() {
		$this->Task->connection = 'test';
		$this->Task->params = ['count' => 10, 'records' => true];

		$this->Task->Model->expects($this->any())->method('listAll')
			->will($this->returnValue(array('Articles', 'comments')));

		$filename = $this->_normalizePath(ROOT . '/tests/Fixture/ArticlesFixture.php');
		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains("'title' => 'Third Article'"));

		$filename = $this->_normalizePath(ROOT . '/tests/Fixture/CommentsFixture.php');
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with($filename, $this->stringContains("'comment' => 'First Comment for First Article'"));
		$this->Task->expects($this->exactly(2))->method('createFile');

		$this->Task->all();
	}

/**
 * test using all() with -schema
 *
 * @return void
 */
	public function testAllWithSchemaImport() {
		$this->Task->connection = 'test';
		$this->Task->params = array('schema' => true);

		$this->Task->Model->expects($this->any())->method('listAll')
			->will($this->returnValue(array('Articles', 'comments')));

		$filename = $this->_normalizePath(ROOT . '/tests/Fixture/ArticlesFixture.php');
		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains("public \$import = ['model' => 'Articles'"));

		$filename = $this->_normalizePath(ROOT . '/tests/Fixture/CommentsFixture.php');
		$this->Task->expects($this->at(1))->method('createFile')
			->with($filename, $this->stringContains("public \$import = ['model' => 'Comments'"));
		$this->Task->expects($this->exactly(2))->method('createFile');

		$this->Task->all();
	}

/**
 * test interactive mode of execute
 *
 * @return void
 */
	public function testMainNoArgs() {
		$this->Task->connection = 'test';

		$this->Task->Model->expects($this->any())
			->method('listAll')
			->will($this->returnValue(['articles', 'comments']));

		$filename = $this->_normalizePath(ROOT . '/tests/Fixture/ArticlesFixture.php');
		$this->Task->expects($this->never())
			->method('createFile');

		$this->Task->main();
	}

/**
 * Test that bake works
 *
 * @return void
 */
	public function testBake() {
		$this->Task->connection = 'test';

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($this->anything(), $this->logicalAnd(
				$this->stringContains('class ArticlesFixture extends TestFixture'),
				$this->stringContains('public $fields'),
				$this->stringContains('public $records'),
				$this->logicalNot($this->stringContains('public $import'))
			));
		$result = $this->Task->main('Articles');
	}

/**
 * test main() with importing records
 *
 * @return void
 */
	public function testMainImportRecords() {
		$this->Task->connection = 'test';
		$this->Task->params = ['import-records' => true];

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($this->anything(), $this->logicalAnd(
				$this->stringContains("public \$import = ['records' => true, 'connection' => 'test'];"),
				$this->logicalNot($this->stringContains('public $records'))
			));

		$this->Task->main('Article');
	}

/**
 * test main() with importing schema.
 *
 * @return void
 */
	public function testMainImportSchema() {
		$this->Task->connection = 'test';
		$this->Task->params = ['schema' => true, 'import-records' => true];

		$this->Task->expects($this->once())
			->method('createFile')
			->with($this->anything(), $this->logicalAnd(
				$this->stringContains("public \$import = ['model' => 'Article', 'records' => true, 'connection' => 'test'];"),
				$this->logicalNot($this->stringContains('public $fields')),
				$this->logicalNot($this->stringContains('public $records'))
			));
		$this->Task->bake('Article', 'comments');
	}

/**
 * test record generation with float and binary types
 *
 * @return void
 */
	public function testRecordGenerationForBinaryAndFloat() {
		$this->Task->connection = 'test';

		$result = $this->Task->bake('Article', 'datatypes');
		$this->assertContains("'float_field' => 1", $result);
		$this->assertContains("'bool' => 1", $result);
		$this->assertContains("_constraints", $result);
		$this->assertContains("'primary' => ['type' => 'primary'", $result);
		$this->assertContains("'columns' => ['id']", $result);

		$result = $this->Task->bake('Article', 'binary_tests');
		$this->assertContains("'data' => 'Lorem ipsum dolor sit amet'", $result);
	}

/**
 * Test that file generation includes headers and correct path for plugins.
 *
 * @return void
 */
	public function testGenerateFixtureFile() {
		$this->Task->connection = 'test';
		$filename = $this->_normalizePath(ROOT . '/tests/Fixture/ArticlesFixture.php');

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains('ArticlesFixture'));

		$result = $this->Task->generateFixtureFile('Articles', []);
		$this->assertContains('<?php', $result);
		$this->assertContains('namespace App\Test\Fixture;', $result);
	}

/**
 * test generating files into plugins.
 *
 * @return void
 */
	public function testGeneratePluginFixtureFile() {
		$this->Task->connection = 'test';
		$this->Task->plugin = 'TestPlugin';
		$filename = $this->_normalizePath(TEST_APP . 'Plugin/TestPlugin/tests/Fixture/ArticlesFixture.php');

		Plugin::load('TestPlugin');
		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains('class Articles'));

		$result = $this->Task->generateFixtureFile('Articles', []);
		$this->assertContains('<?php', $result);
		$this->assertContains('namespace TestPlugin\Test\Fixture;', $result);
	}

}
