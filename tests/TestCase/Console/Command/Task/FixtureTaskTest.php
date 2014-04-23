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
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\FixtureTask;
use Cake\Console\Command\Task\TemplateTask;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
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
	public $fixtures = array('core.article', 'core.comment', 'core.datatype', 'core.binary_test', 'core.user');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\FixtureTask',
			array('in', 'err', 'createFile', '_stop', 'clear'),
			array($io)
		);
		$this->Task->Model = $this->getMock('Cake\Console\Command\Task\ModelTask',
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
 * test that initialize sets the path
 *
 * @return void
 */
	public function testGetPath() {
		$this->assertPathEquals(ROOT . '/Test/Fixture/', $this->Task->getPath());
	}

/**
 * test importOptions with overwriting command line options.
 *
 * @return void
 */
	public function testImportOptionsWithCommandLineOptions() {
		$this->Task->params = ['schema' => true, 'records' => true];

		$result = $this->Task->importOptions('Article');
		$expected = ['fromTable' => true, 'schema' => 'Article', 'records' => true];
		$this->assertEquals($expected, $result);
	}

/**
 * test importOptions with schema.
 *
 * @return void
 */
	public function testImportOptionsWithSchema() {
		$this->Task->params = ['schema' => true];

		$result = $this->Task->importOptions('Articles');
		$expected = ['schema' => 'Articles'];
		$this->assertEquals($expected, $result);
	}

/**
 * test importOptions with records.
 *
 * @return void
 */
	public function testImportOptionsWithRecords() {
		$this->Task->params = array('records' => true);

		$result = $this->Task->importOptions('Article');
		$expected = array('fromTable' => true, 'records' => true);
		$this->assertEquals($expected, $result);
	}

/**
 * test generating a fixture with database conditions.
 *
 * @return void
 */
	public function testImportRecordsFromDatabaseWithConditionsPoo() {
		$this->Task->connection = 'test';

		$result = $this->Task->bake('Articles', false, array(
			'fromTable' => true,
			'schema' => 'Articles',
			'records' => false
		));

		$this->assertContains('namespace App\Test\Fixture;', $result);
		$this->assertContains('use Cake\TestSuite\Fixture\TestFixture;', $result);
		$this->assertContains('class ArticleFixture extends TestFixture', $result);
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
		$result = $this->Task->bake('Article', false, array('schema' => 'Article'));
		$this->assertContains("'connection' => 'test'", $result);
	}

/**
 * Ensure that fixture data doesn't get overly escaped.
 *
 * @return void
 */
	public function testImportRecordsNoEscaping() {
		$db = ConnectionManager::get('test');
		if ($db instanceof Sqlserver) {
			$this->markTestSkipped('This test does not run on SQLServer');
		}

		$articles = TableRegistry::get('Articles');
		$articles->updateAll(['body' => "Body \"value\""], []);

		$this->Task->connection = 'test';
		$result = $this->Task->bake('Article', false, array(
			'fromTable' => true,
			'schema' => 'Article',
			'records' => false
		));
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
		$filename = $this->_normalizePath(ROOT . '/Test/Fixture/ArticleFixture.php');

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains("public \$table = 'comments';"));

		$this->Task->main('article');
	}

/**
 * test that execute passes runs bake depending with named model.
 *
 * @return void
 */
	public function testMainWithNamedModel() {
		$this->Task->connection = 'test';
		$filename = $this->_normalizePath(ROOT . '/Test/Fixture/ArticleFixture.php');

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains('class ArticleFixture'));

		$this->Task->main('article');
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

		$filename = $this->_normalizePath(ROOT . '/Test/Fixture/ArticleFixture.php');
		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains('class ArticleFixture'));

		$filename = $this->_normalizePath(ROOT . '/Test/Fixture/CommentFixture.php');
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with($filename, $this->stringContains('class CommentFixture'));

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

		$filename = $this->_normalizePath(ROOT . '/Test/Fixture/ArticleFixture.php');
		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains("'title' => 'Third Article'"));

		$filename = $this->_normalizePath(ROOT . '/Test/Fixture/CommentFixture.php');
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

		$filename = $this->_normalizePath(ROOT . '/Test/Fixture/ArticleFixture.php');
		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains("public \$import = ['model' => 'Articles'"));

		$filename = $this->_normalizePath(ROOT . '/Test/Fixture/CommentFixture.php');
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

		$filename = $this->_normalizePath(ROOT . '/Test/Fixture/ArticleFixture.php');
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

		$result = $this->Task->bake('Article');
		$this->assertContains('class ArticleFixture extends TestFixture', $result);
		$this->assertContains('public $fields', $result);
		$this->assertContains('public $records', $result);
		$this->assertNotContains('public $import', $result);

		$result = $this->Task->bake('Article', 'comments');
		$this->assertContains('class ArticleFixture extends TestFixture', $result);
		$this->assertContains('public $table = \'comments\';', $result);
		$this->assertContains('public $fields = [', $result);

		$result = $this->Task->bake('Article', 'comments', array('records' => true));
		$this->assertContains("public \$import = ['records' => true, 'connection' => 'test'];", $result);
		$this->assertNotContains('public $records', $result);

		$result = $this->Task->bake('Article', 'comments', array('schema' => 'Article'));
		$this->assertContains("public \$import = ['model' => 'Article', 'connection' => 'test'];", $result);
		$this->assertNotContains('public $fields', $result);

		$result = $this->Task->bake('Article', 'comments', array('schema' => 'Article', 'records' => true));
		$this->assertContains("public \$import = ['model' => 'Article', 'records' => true, 'connection' => 'test'];", $result);
		$this->assertNotContains('public $fields', $result);
		$this->assertNotContains('public $records', $result);
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
		$filename = $this->_normalizePath(ROOT . '/Test/Fixture/ArticleFixture.php');

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains('ArticleFixture'));

		$result = $this->Task->generateFixtureFile('Article', []);
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
		$filename = $this->_normalizePath(TEST_APP . 'Plugin/TestPlugin/Test/Fixture/ArticleFixture.php');

		Plugin::load('TestPlugin');
		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains('class Article'));

		$result = $this->Task->generateFixtureFile('Article', []);
		$this->assertContains('<?php', $result);
		$this->assertContains('namespace TestPlugin\Test\Fixture;', $result);
	}

}
