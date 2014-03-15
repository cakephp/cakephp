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
 * @since         CakePHP v 1.2.6
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
 *
 */
class ModelTaskTest extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'core.bake_article', 'core.bake_comment', 'core.bake_articles_bake_tag',
		'core.bake_tag', 'core.category_thread', 'core.number_tree'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('Cake\Console\ConsoleOutput', [], [], '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', [], [], '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\ModelTask',
			array('in', 'err', 'createFile', '_stop', '_checkUnitTest'),
			array($out, $out, $in)
		);
		$this->Task->connection = 'test';
		$this->_setupOtherMocks();
	}

/**
 * Setup a mock that has out mocked. Normally this is not used as it makes $this->at() really tricky.
 *
 * @return void
 */
	protected function _useMockedOut() {
		$out = $this->getMock('Cake\Console\ConsoleOutput', [], [], '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', [], [], '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\ModelTask',
			array('in', 'out', 'err', 'hr', 'createFile', '_stop', '_checkUnitTest'),
			array($out, $out, $in)
		);
		$this->_setupOtherMocks();
	}

/**
 * sets up the rest of the dependencies for Model Task
 *
 * @return void
 */
	protected function _setupOtherMocks() {
		$out = $this->getMock('Cake\Console\ConsoleOutput', [], [], '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', [], [], '', false);

		$this->Task->Fixture = $this->getMock('Cake\Console\Command\Task\FixtureTask', [], [$out, $out, $in]);
		$this->Task->Test = $this->getMock('Cake\Console\Command\Task\FixtureTask', [], [$out, $out, $in]);
		$this->Task->Template = new TemplateTask($out, $out, $in);

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
		$this->Task->args[0] = 'BakeArticle';
		$result = $this->Task->getTable();
		$this->assertEquals('bake_articles', $result);

		$this->Task->args[0] = 'BakeArticles';
		$result = $this->Task->getTable();
		$this->assertEquals('bake_articles', $result);

		$this->Task->args[0] = 'Article';
		$this->Task->params['table'] = 'bake_articles';
		$result = $this->Task->getTable();
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
					'className' => 'BakeUsers',
					'foreignKey' => 'bake_user_id',
				],
			],
			'hasMany' => [
				[
					'alias' => 'BakeComments',
					'className' => 'BakeComments',
					'foreignKey' => 'bake_article_id',
				],
			],
			'belongsToMany' => [
				[
					'alias' => 'BakeTags',
					'className' => 'BakeTags',
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
					'className' => 'BakeArticles',
					'foreignKey' => 'bake_article_id',
				],
				[
					'alias' => 'BakeUsers',
					'className' => 'BakeUsers',
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
					'className' => 'CategoryThreads',
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
					'className' => 'BakeComments',
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
					'className' => 'CategoryThreads',
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
					'className' => 'BakeTags',
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
	public function testFields() {
		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->getFields($model);
		$expected = [
			'id',
			'bake_user_id',
			'title',
			'body',
			'published',
		];
		$this->assertEquals($expected, $result);
	}

/**
 * Test getting field with the no- option
 *
 * @return void
 */
	public function testFieldsDisabled() {
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
	public function testFieldsWhiteList() {
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
			'id' => ['rule' => 'numeric', 'allowEmpty' => true],
			'bake_user_id' => ['rule' => 'numeric', 'allowEmpty' => true],
			'title' => ['rule' => 'notEmpty', 'allowEmpty' => false],
			'body' => ['rule' => 'notEmpty', 'allowEmpty' => false],
			'published' => ['rule' => 'boolean', 'allowEmpty' => false],
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
		$this->assertEquals(['Tree'], $result);

		$model = TableRegistry::get('BakeArticles');
		$result = $this->Task->getBehaviors($model);
		$this->assertEquals(['Timestamp'], $result);
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
			->with('Model', 'BakeArticle');
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
 * test confirming of associations, and that when an association is hasMany
 * a question for the hasOne is also not asked.
 *
 * @return void
 */
	public function testConfirmAssociations() {
		$this->markTestIncomplete('Not done here yet');
		$associations = array(
			'hasOne' => array(
				array(
					'alias' => 'ChildCategoryThread',
					'className' => 'CategoryThread',
					'foreignKey' => 'parent_id',
				),
			),
			'hasMany' => array(
				array(
					'alias' => 'ChildCategoryThread',
					'className' => 'CategoryThread',
					'foreignKey' => 'parent_id',
				),
			),
			'belongsTo' => array(
				array(
					'alias' => 'User',
					'className' => 'User',
					'foreignKey' => 'user_id',
				),
			)
		);
		$model = new Model(array('ds' => 'test', 'name' => 'CategoryThread'));

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('n', 'y', 'n', 'n', 'n'));

		$result = $this->Task->confirmAssociations($model, $associations);
		$this->assertTrue(empty($result['hasOne']));

		$result = $this->Task->confirmAssociations($model, $associations);
		$this->assertTrue(empty($result['hasMany']));
		$this->assertTrue(empty($result['hasOne']));
	}

/**
 * test that inOptions generates questions and only accepts a valid answer
 *
 * @return void
 */
	public function testInOptions() {
		$this->markTestIncomplete('Not done here yet');
		$this->_useMockedOut();

		$options = array('one', 'two', 'three');
		$this->Task->expects($this->at(0))->method('out')->with('1. one');
		$this->Task->expects($this->at(1))->method('out')->with('2. two');
		$this->Task->expects($this->at(2))->method('out')->with('3. three');
		$this->Task->expects($this->at(3))->method('in')->will($this->returnValue(10));

		$this->Task->expects($this->at(4))->method('out')->with('1. one');
		$this->Task->expects($this->at(5))->method('out')->with('2. two');
		$this->Task->expects($this->at(6))->method('out')->with('3. three');
		$this->Task->expects($this->at(7))->method('in')->will($this->returnValue(2));
		$result = $this->Task->inOptions($options, 'Pick a number');
		$this->assertEquals(1, $result);
	}

/**
 * test baking validation
 *
 * @return void
 */
	public function testBakeValidation() {
		$this->markTestIncomplete('Not done here yet');
		$validate = array(
			'name' => array(
				'notempty' => 'notEmpty'
			),
			'email' => array(
				'email' => 'email',
			),
			'some_date' => array(
				'date' => 'date'
			),
			'some_time' => array(
				'time' => 'time'
			)
		);
		$result = $this->Task->bake('BakeArticle', compact('validate'));
		$this->assertRegExp('/class BakeArticle extends AppModel \{/', $result);
		$this->assertRegExp('/\$validate \= array\(/', $result);
		$expected = <<< STRINGEND
array(
			'notempty' => array(
				'rule' => array('notEmpty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
STRINGEND;
		$this->assertRegExp('/' . preg_quote(str_replace("\r\n", "\n", $expected), '/') . '/', $result);
	}

/**
 * test baking relations
 *
 * @return void
 */
	public function testBakeRelations() {
		$this->markTestIncomplete('Not done here yet');
		$associations = array(
			'belongsTo' => array(
				array(
					'alias' => 'SomethingElse',
					'className' => 'SomethingElse',
					'foreignKey' => 'something_else_id',
				),
				array(
					'alias' => 'BakeUser',
					'className' => 'BakeUser',
					'foreignKey' => 'bake_user_id',
				),
			),
			'hasOne' => array(
				array(
					'alias' => 'OtherModel',
					'className' => 'OtherModel',
					'foreignKey' => 'other_model_id',
				),
			),
			'hasMany' => array(
				array(
					'alias' => 'BakeComment',
					'className' => 'BakeComment',
					'foreignKey' => 'parent_id',
				),
			),
			'hasAndBelongsToMany' => array(
				array(
					'alias' => 'BakeTag',
					'className' => 'BakeTag',
					'foreignKey' => 'bake_article_id',
					'joinTable' => 'bake_articles_bake_tags',
					'associationForeignKey' => 'bake_tag_id',
				),
			)
		);
		$result = $this->Task->bake('BakeArticle', compact('associations'));
		$this->assertContains(' * @property BakeUser $BakeUser', $result);
		$this->assertContains(' * @property OtherModel $OtherModel', $result);
		$this->assertContains(' * @property BakeComment $BakeComment', $result);
		$this->assertContains(' * @property BakeTag $BakeTag', $result);
		$this->assertRegExp('/\$hasAndBelongsToMany \= array\(/', $result);
		$this->assertRegExp('/\$hasMany \= array\(/', $result);
		$this->assertRegExp('/\$belongsTo \= array\(/', $result);
		$this->assertRegExp('/\$hasOne \= array\(/', $result);
		$this->assertRegExp('/BakeTag/', $result);
		$this->assertRegExp('/OtherModel/', $result);
		$this->assertRegExp('/SomethingElse/', $result);
		$this->assertRegExp('/BakeComment/', $result);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 */
	public function testBakeWithPlugin() {
		$this->markTestIncomplete('Not done here yet');
		$this->Task->plugin = 'ControllerTest';

		//fake plugin path
		Plugin::load('ControllerTest', array('path' => APP . 'Plugin/ControllerTest/'));
		$path = APP . 'Plugin/ControllerTest/Model/BakeArticle.php';
		$this->Task->expects($this->once())->method('createFile')
			->with($path, $this->stringContains('BakeArticle extends ControllerTestAppModel'));

		$result = $this->Task->bake('BakeArticle', array(), array());
		$this->assertContains("App::uses('ControllerTestAppModel', 'ControllerTest.Model');", $result);

		$this->assertEquals(count(ClassRegistry::keys()), 0);
		$this->assertEquals(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * test bake() for models with behaviors
 *
 * @return void
 */
	public function testBakeWithBehaviors() {
		$this->markTestIncomplete('Not done here yet');
		$result = $this->Task->bake('NumberTree', array('actsAs' => array('Tree', 'PluginName.Sluggable')));
		$expected = <<<TEXT
/**
 * Behaviors
 *
 * @var array
 */
	public \$actsAs = array(
		'Tree',
		'PluginName.Sluggable',
	);
TEXT;
		$this->assertTextContains($expected, $result);
	}

/**
 * test that execute passes runs bake depending with named model.
 *
 * @return void
 */
	public function testExecuteWithNamedModel() {
		$this->markTestIncomplete('Not done here yet');
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeArticle');
		$filename = '/my/path/BakeArticle.php';

		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(1));
		$this->Task->expects($this->once())->method('createFile')
			->with($filename, $this->stringContains('class BakeArticle extends AppModel'));

		$this->Task->execute();

		$this->assertEquals(count(ClassRegistry::keys()), 0);
		$this->assertEquals(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * data provider for testExecuteWithNamedModelVariations
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
	public function testExecuteWithNamedModelVariations($name) {
		$this->markTestIncomplete('Not done here yet');
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(1));

		$this->Task->args = array($name);
		$filename = '/my/path/BakeArticle.php';

		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains('class BakeArticle extends AppModel'));
		$this->Task->execute();
	}

/**
 * test that execute with a model name picks up hasMany associations.
 *
 * @return void
 */
	public function testExecuteWithNamedModelHasManyCreated() {
		$this->markTestIncomplete('Not done here yet');
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeArticle');
		$filename = '/my/path/BakeArticle.php';

		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(1));
		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, $this->stringContains("'BakeComment' => array("));

		$this->Task->execute();
	}

/**
 * test that execute runs all() when args[0] = all
 *
 * @return void
 */
	public function testExecuteIntoAll() {
		$this->markTestIncomplete('Not done here yet');
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(true));

		$this->Task->Fixture->expects($this->exactly(5))->method('bake');
		$this->Task->Test->expects($this->exactly(5))->method('bake');

		$filename = '/my/path/BakeArticle.php';
		$this->Task->expects($this->at(1))->method('createFile')
			->with($filename, $this->stringContains('class BakeArticle'));

		$filename = '/my/path/BakeArticlesBakeTag.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($filename, $this->stringContains('class BakeArticlesBakeTag'));

		$filename = '/my/path/BakeComment.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($filename, $this->stringContains('class BakeComment'));

		$filename = '/my/path/BakeComment.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($filename, $this->stringContains('public $primaryKey = \'otherid\';'));

		$filename = '/my/path/BakeTag.php';
		$this->Task->expects($this->at(4))->method('createFile')
			->with($filename, $this->stringContains('class BakeTag'));

		$filename = '/my/path/BakeTag.php';
		$this->Task->expects($this->at(4))->method('createFile')
			->with($filename, $this->logicalNot($this->stringContains('public $primaryKey')));

		$filename = '/my/path/CategoryThread.php';
		$this->Task->expects($this->at(5))->method('createFile')
			->with($filename, $this->stringContains('class CategoryThread'));

		$this->Task->execute();

		$this->assertEquals(count(ClassRegistry::keys()), 0);
		$this->assertEquals(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * test that odd tablenames aren't inflected back from modelname
 *
 * @return void
 */
	public function testExecuteIntoAllOddTables() {
		$this->markTestIncomplete('Not done here yet');
		$out = $this->getMock('Cake\Console\ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('Cake\Console\Command\Task\ModelTask',
			array('in', 'err', '_stop', '_checkUnitTest', 'getAllTables', '_getModelObject', 'bake', 'bakeFixture'),
			array($out, $out, $in)
		);
		$this->_setupOtherMocks();

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('getAllTables')->will($this->returnValue(array('bake_odd')));
		$object = new Model(array('name' => 'BakeOdd', 'table' => 'bake_odd', 'ds' => 'test'));
		$this->Task->expects($this->once())->method('_getModelObject')->with('BakeOdd', 'bake_odd')->will($this->returnValue($object));
		$this->Task->expects($this->at(3))->method('bake')->with($object, false)->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('bakeFixture')->with('BakeOdd', 'bake_odd');

		$this->Task->execute();

		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('ModelTask',
			array('in', 'err', '_stop', '_checkUnitTest', 'getAllTables', '_getModelObject', 'doAssociations', 'doValidation', 'doActsAs', 'createFile'),
			array($out, $out, $in)
		);
		$this->_setupOtherMocks();

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('getAllTables')->will($this->returnValue(array('bake_odd')));
		$object = new Model(array('name' => 'BakeOdd', 'table' => 'bake_odd', 'ds' => 'test'));
		$this->Task->expects($this->once())->method('_getModelObject')->will($this->returnValue($object));
		$this->Task->expects($this->once())->method('doAssociations')->will($this->returnValue(array()));
		$this->Task->expects($this->once())->method('doValidation')->will($this->returnValue(array()));
		$this->Task->expects($this->once())->method('doActsAs')->will($this->returnValue(array()));

		$filename = '/my/path/BakeOdd.php';
		$this->Task->expects($this->once())->method('createFile')
			->with($filename, $this->stringContains('class BakeOdd'));

		$filename = '/my/path/BakeOdd.php';
		$this->Task->expects($this->once())->method('createFile')
			->with($filename, $this->stringContains('public $useTable = \'bake_odd\''));

		$this->Task->execute();
	}

/**
 * test that odd tablenames aren't inflected back from modelname
 *
 * @return void
 */
	public function testExecuteIntoBakeOddTables() {
		$this->markTestIncomplete('Not done here yet');
		$out = $this->getMock('Cake\Console\ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('Cake\Console\Command\Task\ModelTask',
			array('in', 'err', '_stop', '_checkUnitTest', 'getAllTables', '_getModelObject', 'bake', 'bakeFixture'),
			array($out, $out, $in)
		);
		$this->_setupOtherMocks();

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeOdd');
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('getAllTables')->will($this->returnValue(array('articles', 'bake_odd')));
		$object = new Model(array('name' => 'BakeOdd', 'table' => 'bake_odd', 'ds' => 'test'));
		$this->Task->expects($this->once())->method('_getModelObject')->with('BakeOdd', 'bake_odd')->will($this->returnValue($object));
		$this->Task->expects($this->once())->method('bake')->with($object, false)->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('bakeFixture')->with('BakeOdd', 'bake_odd');

		$this->Task->execute();

		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock('ModelTask',
			array('in', 'err', '_stop', '_checkUnitTest', 'getAllTables', '_getModelObject', 'doAssociations', 'doValidation', 'doActsAs', 'createFile'),
			array($out, $out, $in)
		);
		$this->_setupOtherMocks();

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('BakeOdd');
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(true));
		$this->Task->expects($this->once())->method('getAllTables')->will($this->returnValue(array('articles', 'bake_odd')));
		$object = new Model(array('name' => 'BakeOdd', 'table' => 'bake_odd', 'ds' => 'test'));
		$this->Task->expects($this->once())->method('_getModelObject')->will($this->returnValue($object));
		$this->Task->expects($this->once())->method('doAssociations')->will($this->returnValue(array()));
		$this->Task->expects($this->once())->method('doValidation')->will($this->returnValue(array()));
		$this->Task->expects($this->once())->method('doActsAs')->will($this->returnValue(array()));

		$filename = '/my/path/BakeOdd.php';
		$this->Task->expects($this->once())->method('createFile')
			->with($filename, $this->stringContains('class BakeOdd'));

		$filename = '/my/path/BakeOdd.php';
		$this->Task->expects($this->once())->method('createFile')
			->with($filename, $this->stringContains('public $useTable = \'bake_odd\''));

		$this->Task->execute();
	}

/**
 * test that skipTables changes how all() works.
 *
 * @return void
 */
	public function testSkipTablesAndAll() {
		$this->markTestIncomplete('Not done here yet');
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(true));
		$this->Task->skipTables = array('bake_tags');

		$this->Task->Fixture->expects($this->exactly(4))->method('bake');
		$this->Task->Test->expects($this->exactly(4))->method('bake');

		$filename = '/my/path/BakeArticle.php';
		$this->Task->expects($this->at(1))->method('createFile')
			->with($filename, $this->stringContains('class BakeArticle'));

		$filename = '/my/path/BakeArticlesBakeTag.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($filename, $this->stringContains('class BakeArticlesBakeTag'));

		$filename = '/my/path/BakeComment.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($filename, $this->stringContains('class BakeComment'));

		$filename = '/my/path/CategoryThread.php';
		$this->Task->expects($this->at(4))->method('createFile')
			->with($filename, $this->stringContains('class CategoryThread'));

		$this->Task->execute();
	}

/**
 * test the interactive side of bake.
 *
 * @return void
 */
	public function testExecuteIntoInteractive() {
		$this->markTestIncomplete('Not done here yet');
		$tables = $this->Task->listAll('test');
		$article = array_search('bake_articles', $tables) + 1;

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->interactive = true;

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls(
				$article, // article
				'n', // no validation
				'y', // associations
				'y', // comment relation
				'y', // user relation
				'y', // tag relation
				'n', // additional assocs
				'y' // looks good?
			));
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(true));

		$this->Task->Test->expects($this->once())->method('bake');
		$this->Task->Fixture->expects($this->once())->method('bake');

		$filename = '/my/path/BakeArticle.php';

		$this->Task->expects($this->once())->method('createFile')
			->with($filename, $this->stringContains('class BakeArticle'));

		$this->Task->execute();

		$this->assertEquals(count(ClassRegistry::keys()), 0);
		$this->assertEquals(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * test using bake interactively with a table that does not exist.
 *
 * @return void
 */
	public function testExecuteWithNonExistantTableName() {
		$this->markTestIncomplete('Not done here yet');
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls(
				'Foobar', // Or type in the name of the model
				'y', // Do you want to use this table
				'n' // Doesn't exist, continue anyway?
			));

		$this->Task->execute();
	}

/**
 * test using bake interactively with a table that does not exist.
 *
 * @return void
 */
	public function testForcedExecuteWithNonExistantTableName() {
		$this->markTestIncomplete('Not done here yet');
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls(
				'Foobar', // Or type in the name of the model
				'y', // Do you want to use this table
				'y', // Doesn't exist, continue anyway?
				'id', // Primary key
				'y' // Looks good?
			));

		$this->Task->execute();
	}

}
