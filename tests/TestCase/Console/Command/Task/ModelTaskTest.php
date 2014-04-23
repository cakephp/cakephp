<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         1.2.6
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\ModelTask;
use Cake\Console\Command\Task\TemplateTask;
use Cake\Core\Plugin;
use Cake\Model\Model;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Inflector;

/**
 * ModelTaskTest class
 */
class ModelTaskTest extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'core.bake_article', 'core.bake_comment', 'core.bake_articles_bake_tag',
		'core.bake_tag', 'core.user', 'core.category_thread', 'core.number_tree',
		'core.counter_cache_user'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\ModelTask',
			array('in', 'err', 'createFile', '_stop', '_checkUnitTest'),
			array($io)
		);
		$this->Task->connection = 'test';
		$this->_setupOtherMocks();
		TableRegistry::clear();
	}

/**
 * Setup a mock that has out mocked. Normally this is not used as it makes $this->at() really tricky.
 *
 * @return void
 */
	protected function _useMockedOut() {
		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\ModelTask',
			array('in', 'out', 'err', 'hr', 'createFile', '_stop', '_checkUnitTest'),
			array($io)
		);
		$this->_setupOtherMocks();
	}

/**
 * sets up the rest of the dependencies for Model Task
 *
 * @return void
 */
	protected function _setupOtherMocks() {
		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task->Fixture = $this->getMock('Cake\Console\Command\Task\FixtureTask', [], [$io]);
		$this->Task->Test = $this->getMock('Cake\Console\Command\Task\FixtureTask', [], [$io]);
		$this->Task->Template = new TemplateTask($io);
		$this->Task->Template->interactive = false;

		$this->Task->name = 'Model';
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
		$this->fixtureManager->shutDown();
	}

/**
 * Test that listAll uses the connection property
 *
 * @return void
 */
	public function testListAllConnection() {
		$this->_useMockedOut();
		$this->Task->connection = 'test';

		$result = $this->Task->listAll();
		$this->assertContains('bake_articles', $result);
		$this->assertContains('bake_articles_bake_tags', $result);
		$this->assertContains('bake_tags', $result);
		$this->assertContains('bake_comments', $result);
		$this->assertContains('category_threads', $result);
	}

/**
 * Test getName() method.
 *
 * @return void
 */
	public function testGetTable() {
		$result = $this->Task->getTable('BakeArticle');
		$this->assertEquals('bake_articles', $result);

		$result = $this->Task->getTable('BakeArticles');
		$this->assertEquals('bake_articles', $result);

		$this->Task->params['table'] = 'bake_articles';
		$result = $this->Task->getTable('Article');
		$this->assertEquals('bake_articles', $result);
	}

/**
 * Test getting the a table class.
 *
 * @return void
 */
	public function testGetTableObject() {
		$result = $this->Task->getTableObject('Article', 'bake_articles');
		$this->assertInstanceOf('Cake\ORM\Table', $result);
		$this->assertEquals('bake_articles', $result->table());
		$this->assertEquals('Article', $result->alias());
	}

/**
 * Test getAssociations with off flag.
 *
 * @return void
 */
	public function testGetAssociationsNoFlag() {
		$this->Task->params['no-associations'] = true;
		$articles = TableRegistry::get('BakeArticle');
		$this->assertEquals([], $this->Task->getAssociations($articles));
	}

/**
 * Test getAssociations
 *
 * @return void
 */
	public function testGetAssociations() {
		$articles = TableRegistry::get('BakeArticles');
		$result = $this->Task->getAssociations($articles);
		$expected = [
			'belongsTo' => [
				[
					'alias' => 'BakeUsers',
					'foreignKey' => 'bake_user_id',
				],
			],
			'hasMany' => [
				[
					'alias' => 'BakeComments',
					'foreignKey' => 'bake_article_id',
				],
			],
			'belongsToMany' => [
				[
					'alias' => 'BakeTags',
					'foreignKey' => 'bake_article_id',
					'joinTable' => 'bake_articles_bake_tags',
					'targetForeignKey' => 'bake_tag_id',
				],
			],
		];
		$this->assertEquals($expected, $result);
	}

/**
 * test that belongsTo generation works.
 *
 * @return void
 */
	public function testBelongsToGeneration() {
		$model = TableRegistry::get('BakeComments');
		$result = $this->Task->findBelongsTo($model, []);
		$expected = [
			'belongsTo' => [
				[
					'alias' => 'BakeArticles',
					'foreignKey' => 'bake_article_id',
				],
				[
					'alias' => 'BakeUsers',
					'foreignKey' => 'bake_user_id',
				],
			]
		];
		$this->assertEquals($expected, $result);

		$model = TableRegistry::get('CategoryThreads');
		$result = $this->Task->findBelongsTo($model, array());
		$expected = [
			'belongsTo' => [
				[
					'alias' => 'ParentCategoryThreads',
					'foreignKey' => 'parent_id',
				],
			]
		];
		$this->assertEquals($expected, $result);
	}

/**
 * test that hasOne and/or hasMany relations are generated properly.
 *
 * @return void
 */
	public function testHasManyGeneration() {
		$this->Task->connection = 'test';
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->findHasMany($model, []);
		$expected = [
			'hasMany' => [
				[
					'alias' => 'BakeComments',
					'foreignKey' => 'bake_article_id',
				],
			],
		];
		$this->assertEquals($expected, $result);

		$model = TableRegistry::get('CategoryThreads');
		$result = $this->Task->findHasMany($model, []);
		$expected = [
			'hasMany' => [
				[
					'alias' => 'ChildCategoryThreads',
					'foreignKey' => 'parent_id',
				],
			]
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Test that HABTM generation works
 *
 * @return void
 */
	public function testHasAndBelongsToManyGeneration() {
		$this->Task->connection = 'test';
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->findBelongsToMany($model, []);
		$expected = [
			'belongsToMany' => [
				[
					'alias' => 'BakeTags',
					'foreignKey' => 'bake_article_id',
					'joinTable' => 'bake_articles_bake_tags',
					'targetForeignKey' => 'bake_tag_id',
				],
			],
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Test getting accessible fields.
 *
 * @return void
 */
	public function testGetFields() {
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->getFields($model);
		$expected = [
			'bake_user_id',
			'title',
			'body',
			'published',
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Test getting accessible fields includes associations.
 *
 * @return void
 */
	public function testGetFieldsAssociations() {
		$model = TableRegistry::get('BakeArticles');
		$model->belongsToMany('BakeTags');
		$model->belongsTo('BakeAuthors');
		$model->hasMany('BakeComments');

		$result = $this->Task->getFields($model);
		$this->assertContains('bake_tags', $result);
		$this->assertContains('bake_comments', $result);
		$this->assertContains('bake_author', $result);
	}

/**
 * Test getting field with the no- option
 *
 * @return void
 */
	public function testGetFieldsDisabled() {
		$model = TableRegistry::get('BakeArticles');
		$this->Task->params['no-fields'] = true;
		$result = $this->Task->getFields($model);
		$this->assertEquals([], $result);
	}

/**
 * Test getting field with a whitelist
 *
 * @return void
 */
	public function testGetFieldsWhiteList() {
		$model = TableRegistry::get('BakeArticles');
		$this->Task->params['fields'] = 'id, title  , , body ,  created';
		$result = $this->Task->getFields($model);
		$expected = [
			'id',
			'title',
			'body',
			'created',
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Test getting hidden fields.
 *
 * @return void
 */
	public function testGetHiddenFields() {
		$model = TableRegistry::get('Users');
		$result = $this->Task->getHiddenFields($model);
		$expected = [
			'password',
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Test getting hidden field with the no- option
 *
 * @return void
 */
	public function testGetHiddenFieldsDisabled() {
		$model = TableRegistry::get('Users');
		$this->Task->params['no-hidden'] = true;
		$result = $this->Task->getHiddenFields($model);
		$this->assertEquals([], $result);
	}

/**
 * Test getting hidden field with a whitelist
 *
 * @return void
 */
	public function testGetHiddenFieldsWhiteList() {
		$model = TableRegistry::get('Users');
		$this->Task->params['hidden'] = 'id, title  , , body ,  created';
		$result = $this->Task->getHiddenFields($model);
		$expected = [
			'id',
			'title',
			'body',
			'created',
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Test getting primary key
 *
 * @return void
 */
	public function testGetPrimaryKey() {
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->getPrimaryKey($model);
		$expected = ['id'];
		$this->assertEquals($expected, $result);

		$this->Task->params['primary-key'] = 'id, , account_id';
		$result = $this->Task->getPrimaryKey($model);
		$expected = ['id', 'account_id'];
		$this->assertEquals($expected, $result);
	}
/**
 * test getting validation rules with the no-validation rule.
 *
 * @return void
 */
	public function testGetValidationDisabled() {
		$model = TableRegistry::get('BakeArticles');
		$this->Task->params['no-validation'] = true;
		$result = $this->Task->getValidation($model);
		$this->assertEquals([], $result);
	}

/**
 * test getting validation rules.
 *
 * @return void
 */
	public function testGetValidation() {
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->getValidation($model);
		$expected = [
			'id' => ['rule' => 'numeric', 'allowEmpty' => 'create'],
			'bake_user_id' => ['rule' => 'numeric', 'allowEmpty' => false],
			'title' => ['rule' => false, 'allowEmpty' => false],
			'body' => ['rule' => false, 'allowEmpty' => true],
			'published' => ['rule' => 'boolean', 'allowEmpty' => true],
		];
		$this->assertEquals($expected, $result);
	}

/**
 * test non interactive doActsAs
 *
 * @return void
 */
	public function testGetBehaviors() {
		$model = TableRegistry::get('NumberTrees');
		$result = $this->Task->getBehaviors($model);
		$this->assertEquals(['Tree' => []], $result);

		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->getBehaviors($model);
		$this->assertEquals(['Timestamp' => []], $result);

		$model = TableRegistry::get('CounterCacheUsers');
		$result = $this->Task->getBehaviors($model);
		$expected = [
			'CounterCache' => ["'Posts' => ['post_count']"]
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Test getDisplayField() method.
 *
 * @return void
 */
	public function testGetDisplayField() {
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->getDisplayField($model);
		$this->assertEquals('title', $result);

		$this->Task->params['display-field'] = 'custom';
		$result = $this->Task->getDisplayField($model);
		$this->assertEquals('custom', $result);
	}

/**
 * Ensure that the fixture object is correctly called.
 *
 * @return void
 */
	public function testBakeFixture() {
		$this->Task->plugin = 'TestPlugin';
		$this->Task->Fixture->expects($this->at(0))
			->method('bake')
			->with('BakeArticle', 'bake_articles');
		$this->Task->bakeFixture('BakeArticle', 'bake_articles');

		$this->assertEquals($this->Task->plugin, $this->Task->Fixture->plugin);
		$this->assertEquals($this->Task->connection, $this->Task->Fixture->connection);
		$this->assertEquals($this->Task->interactive, $this->Task->Fixture->interactive);
	}

/**
 * Ensure that the fixture baking can be disabled
 *
 * @return void
 */
	public function testBakeFixtureDisabled() {
		$this->Task->params['no-fixture'] = true;
		$this->Task->plugin = 'TestPlugin';
		$this->Task->Fixture->expects($this->never())
			->method('bake');
		$this->Task->bakeFixture('BakeArticle', 'bake_articles');
	}

/**
 * Ensure that the test object is correctly called.
 *
 * @return void
 */
	public function testBakeTest() {
		$this->Task->plugin = 'TestPlugin';
		$this->Task->Test->expects($this->at(0))
			->method('bake')
			->with('Table', 'BakeArticle');
		$this->Task->bakeTest('BakeArticle');

		$this->assertEquals($this->Task->plugin, $this->Task->Test->plugin);
		$this->assertEquals($this->Task->connection, $this->Task->Test->connection);
		$this->assertEquals($this->Task->interactive, $this->Task->Test->interactive);
	}

/**
 * Ensure that test baking can be disabled.
 *
 * @return void
 */
	public function testBakeTestDisabled() {
		$this->Task->params['no-test'] = true;
		$this->Task->plugin = 'TestPlugin';
		$this->Task->Test->expects($this->never())
			->method('bake');
		$this->Task->bakeTest('BakeArticle');
	}

/**
 * test baking validation
 *
 * @return void
 */
	public function testBakeTableValidation() {
		$validation = array(
			'id' => array(
				'allowEmpty' => 'create',
				'rule' => 'numeric',
			),
			'name' => array(
				'allowEmpty' => false,
				'rule' => false,
			),
			'email' => array(
				'allowEmpty' => true,
				'rule' => 'email',
			),
		);
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->bakeTable($model, compact('validation'));

		$this->assertContains('namespace App\Model\Table;', $result);
		$this->assertContains('use Cake\ORM\Table;', $result);
		$this->assertContains('use Cake\Validation\Validator;', $result);
		$this->assertContains('class BakeArticlesTable extends Table {', $result);
		$this->assertContains('public function validationDefault(Validator $validator) {', $result);
		$this->assertContains("->add('id', 'valid', ['rule' => 'numeric'])", $result);
		$this->assertContains("->add('email', 'valid', ['rule' => 'email'])", $result);
		$this->assertContains("->allowEmpty('id', 'create')", $result);
		$this->assertContains("->allowEmpty('email')", $result);
		$this->assertContains("->validatePresence('name', 'create')", $result);
	}

/**
 * test baking
 *
 * @return void
 */
	public function testBakeTableConfig() {
		$config = [
			'table' => 'articles',
			'primaryKey' => ['id'],
			'displayField' => 'title',
			'behaviors' => ['Timestamp' => ''],
		];
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->bakeTable($model, $config);

		$this->assertContains('public function initialize(array $config) {', $result);
		$this->assertContains("this->primaryKey(['id']);\n", $result);
		$this->assertContains("this->displayField('title');\n", $result);
		$this->assertContains("this->addBehavior('Timestamp');\n", $result);
		$this->assertContains("this->table('articles');\n", $result);
		$this->assertContains('use Cake\Validation\Validator;', $result);
	}

/**
 * test baking relations
 *
 * @return void
 */
	public function testBakeTableRelations() {
		$associations = [
			'belongsTo' => [
				[
					'alias' => 'SomethingElse',
					'foreignKey' => 'something_else_id',
				],
				[
					'alias' => 'BakeUser',
					'foreignKey' => 'bake_user_id',
				],
			],
			'hasMany' => [
				[
					'alias' => 'BakeComment',
					'foreignKey' => 'parent_id',
				],
			],
			'belongsToMany' => [
				[
					'alias' => 'BakeTag',
					'foreignKey' => 'bake_article_id',
					'joinTable' => 'bake_articles_bake_tags',
					'targetForeignKey' => 'bake_tag_id',
				],
			]
		];
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->bakeTable($model, compact('associations'));
		$this->assertContains("this->hasMany('BakeComment', [", $result);
		$this->assertContains("this->belongsTo('SomethingElse', [", $result);
		$this->assertContains("this->belongsTo('BakeUser', [", $result);
		$this->assertContains("this->belongsToMany('BakeTag', [", $result);
		$this->assertContains("'joinTable' => 'bake_articles_bake_tags',", $result);
	}

/**
 * test baking an entity class
 *
 * @return void
 */
	public function testBakeEntity() {
		$config = [
			'fields' => []
		];
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->bakeEntity($model, $config);

		$this->assertContains('namespace App\Model\Entity;', $result);
		$this->assertContains('use Cake\ORM\Entity;', $result);
		$this->assertContains('class BakeArticle extends Entity {', $result);
		$this->assertNotContains('$_accessible', $result);
	}

/**
 * test baking an entity class
 *
 * @return void
 */
	public function testBakeEntityFields() {
		$config = [
			'fields' => ['title', 'body', 'published']
		];
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->bakeEntity($model, $config);

		$this->assertContains("protected \$_accessible = [", $result);
		$this->assertContains("'title' => true,", $result);
		$this->assertContains("'body' => true,", $result);
		$this->assertContains("'published' => true", $result);
		$this->assertNotContains("protected \$_hidden", $result);
	}

/**
 * test baking an entity class sets hidden fields.
 *
 * @return void
 */
	public function testBakeEntityHidden() {
		$model = TableRegistry::get('BakeUsers');
		$config = [
			'hidden' => ['password'],
		];
		$result = $this->Task->bakeEntity($model, $config);

		$this->assertContains("protected \$_hidden = [", $result);
		$this->assertContains("'password'", $result);
		$this->assertNotContains("protected \$_accessible", $result);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 */
	public function testBakeTableWithPlugin() {
		$this->Task->plugin = 'ControllerTest';

		// fake plugin path
		Plugin::load('ControllerTest', array('path' => APP . 'Plugin' . DS . 'ControllerTest' . DS));
		$path = $this->_normalizePath(APP . 'Plugin/ControllerTest/Model/Table/BakeArticlesTable.php');
		$this->Task->expects($this->once())->method('createFile')
			->with($path, $this->logicalAnd(
				$this->stringContains('namespace ControllerTest\\Model\\Table;'),
				$this->stringContains('use Cake\\ORM\\Table;'),
				$this->stringContains('class BakeArticlesTable extends Table {')
			));

		$model = TableRegistry::get('BakeArticles');
		$this->Task->bakeTable($model);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 */
	public function testBakeEntityWithPlugin() {
		$this->Task->plugin = 'ControllerTest';

		// fake plugin path
		Plugin::load('ControllerTest', array('path' => APP . 'Plugin' . DS . 'ControllerTest' . DS));
		$path = APP . 'Plugin' . DS . 'ControllerTest' . DS . 'Model' . DS . 'Entity' . DS . 'BakeArticle.php';
		$path = $this->_normalizePath($path);
		$this->Task->expects($this->once())->method('createFile')
			->with($path, $this->logicalAnd(
				$this->stringContains('namespace ControllerTest\\Model\\Entity;'),
				$this->stringContains('use Cake\\ORM\\Entity;'),
				$this->stringContains('class BakeArticle extends Entity {')
			));

		$model = TableRegistry::get('BakeArticles');
		$this->Task->bakeEntity($model);
	}

/**
 * test that execute with no args
 *
 * @return void
 */
	public function testMainNoArgs() {
		$this->_useMockedOut();
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';

		$this->Task->expects($this->at(0))
			->method('out')
			->with($this->stringContains('Choose a model to bake from the following:'));

		$this->Task->main();
	}

/**
 * test that execute passes runs bake depending with named model.
 *
 * @return void
 */
	public function testMainWithNamedModel() {
		$this->Task->connection = 'test';

		$tableFile = $this->_normalizePath(APP . 'Model/Table/BakeArticlesTable.php');
		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($tableFile, $this->stringContains('class BakeArticlesTable extends Table'));

		$entityFile = $this->_normalizePath(APP . 'Model/Entity/BakeArticle.php');
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with($entityFile, $this->stringContains('class BakeArticle extends Entity'));

		$this->Task->main('BakeArticles');
	}

/**
 * data provider for testMainWithNamedModelVariations
 *
 * @return void
 */
	public static function nameVariations() {
		return array(
			array('BakeArticles'), array('BakeArticle'), array('bake_article'), array('bake_articles')
		);
	}

/**
 * test that execute passes with different inflections of the same name.
 *
 * @dataProvider nameVariations
 * @return void
 */
	public function testMainWithNamedModelVariations($name) {
		$this->Task->connection = 'test';

		$filename = $this->_normalizePath(APP . 'Model/Table/BakeArticlesTable.php');

		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains('class BakeArticlesTable extends Table {'));
		$this->Task->main($name);
	}

/**
 * test that execute runs all() when args[0] = all
 *
 * @return void
 */
	public function testMainIntoAll() {
		$count = count($this->Task->listAll());
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}

		$this->Task->connection = 'test';

		$this->Task->Fixture->expects($this->exactly($count))
			->method('bake');
		$this->Task->Test->expects($this->exactly($count))
			->method('bake');

		$filename = $this->_normalizePath(APP . 'Model/Table/BakeArticlesTable.php');
		$this->Task->expects($this->at(0))
			->method('createFile')
			->with($filename, $this->stringContains('class BakeArticlesTable extends'));

		$filename = $this->_normalizePath(APP . 'Model/Entity/BakeArticle.php');
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with($filename, $this->stringContains('class BakeArticle extends'));

		$filename = $this->_normalizePath(APP . 'Model/Table/BakeArticlesBakeTagsTable.php');
		$this->Task->expects($this->at(2))
			->method('createFile')
			->with($filename, $this->stringContains('class BakeArticlesBakeTagsTable extends'));

		$filename = $this->_normalizePath(APP . 'Model/Entity/BakeArticlesBakeTag.php');
		$this->Task->expects($this->at(3))
			->method('createFile')
			->with($filename, $this->stringContains('class BakeArticlesBakeTag extends'));

		$filename = $this->_normalizePath(APP . 'Model/Table/BakeCommentsTable.php');
		$this->Task->expects($this->at(4))
			->method('createFile')
			->with($filename, $this->stringContains('class BakeCommentsTable extends'));

		$filename = $this->_normalizePath(APP . 'Model/Entity/BakeComment.php');
		$this->Task->expects($this->at(5))
			->method('createFile')
			->with($filename, $this->stringContains('class BakeComment extends'));

		$filename = $this->_normalizePath(APP . 'Model/Table/BakeTagsTable.php');
		$this->Task->expects($this->at(6))
			->method('createFile')
			->with($filename, $this->stringContains('class BakeTagsTable extends'));

		$filename = $this->_normalizePath(APP . 'Model/Entity/BakeTag.php');
		$this->Task->expects($this->at(7))
			->method('createFile')
			->with($filename, $this->stringContains('class BakeTag extends'));

		$filename = $this->_normalizePath(APP . 'Model/Table/CategoryThreadsTable.php');
		$this->Task->expects($this->at(8))
			->method('createFile')
			->with($filename, $this->stringContains('class CategoryThreadsTable extends'));

		$filename = $this->_normalizePath(APP . 'Model/Entity/CategoryThread.php');
		$this->Task->expects($this->at(9))
			->method('createFile')
			->with($filename, $this->stringContains('class CategoryThread extends'));

		$this->Task->all();
	}

/**
 * test that skipTables changes how all() works.
 *
 * @return void
 */
	public function testSkipTablesAndAll() {
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}

		$this->Task->connection = 'test';
		$this->Task->skipTables = ['bake_tags'];

		$this->Task->Fixture->expects($this->exactly(7))
			->method('bake');
		$this->Task->Test->expects($this->exactly(7))
			->method('bake');

		$filename = $this->_normalizePath(APP . 'Model/Entity/BakeArticle.php');
		$this->Task->expects($this->at(1))
			->method('createFile')
			->with($filename);

		$filename = $this->_normalizePath(APP . 'Model/Entity/BakeArticlesBakeTag.php');
		$this->Task->expects($this->at(3))
			->method('createFile')
			->with($filename);

		$filename = $this->_normalizePath(APP . 'Model/Entity/BakeComment.php');
		$this->Task->expects($this->at(5))
			->method('createFile')
			->with($filename);

		$filename = $this->_normalizePath(APP . 'Model/Entity/CategoryThread.php');
		$this->Task->expects($this->at(7))
			->method('createFile')
			->with($filename);

		$filename = $this->_normalizePath(APP . 'Model/Entity/CounterCacheUser.php');
		$this->Task->expects($this->at(9))
			->method('createFile')
			->with($filename);

		$filename = $this->_normalizePath(APP . 'Model/Entity/NumberTree.php');
		$this->Task->expects($this->at(11))
			->method('createFile')
			->with($filename);

		$this->Task->all();
	}

}
