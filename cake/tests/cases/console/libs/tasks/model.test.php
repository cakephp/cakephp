<?php
/**
 * ModelTaskTest file
 *
 * Test Case for test generation shell task
 *
 * PHP versions 4 and 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.6
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'model.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'fixture.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'template.php';

Mock::generatePartial(
	'ShellDispatcher', 'TestModelTaskMockShellDispatcher',
	array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
);
Mock::generatePartial(
	'ModelTask', 'MockModelTask',
	array('in', 'out', 'hr', 'err', 'createFile', '_stop', '_checkUnitTest')
);

Mock::generate(
	'Model', 'MockModelTaskModel'
);

Mock::generate(
	'FixtureTask', 'MockModelTaskFixtureTask'
);

/**
 * ModelTaskTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 */
class ModelTaskTest extends CakeTestCase {

/**
 * fixtures
 *
 * @var array
 * @access public
 */
	var $fixtures = array('core.article', 'core.comment', 'core.articles_tag', 'core.tag', 'core.category_thread');

/**
 * starTest method
 *
 * @return void
 * @access public
 */
	function startTest() {
		$this->Dispatcher =& new TestModelTaskMockShellDispatcher();
		$this->Task =& new MockModelTask($this->Dispatcher);
		$this->Task->name = 'ModelTask';
		$this->Task->interactive = true;
		$this->Task->Dispatch =& $this->Dispatcher;
		$this->Task->Dispatch->shellPaths = App::path('shells');
		$this->Task->Template =& new TemplateTask($this->Task->Dispatch);
		$this->Task->Fixture =& new MockModelTaskFixtureTask();
		$this->Task->Test =& new MockModelTaskFixtureTask();
	}

/**
 * endTest method
 *
 * @return void
 * @access public
 */
	function endTest() {
		unset($this->Task, $this->Dispatcher);
		ClassRegistry::flush();
	}

/**
 * Test that listAll scans the database connection and lists all the tables in it.s
 *
 * @return void
 * @access public
 */
	function testListAll() {
		$this->Task->expectAt(1, 'out', array('1. Article'));
		$this->Task->expectAt(2, 'out', array('2. ArticlesTag'));
		$this->Task->expectAt(3, 'out', array('3. CategoryThread'));
		$this->Task->expectAt(4, 'out', array('4. Comment'));
		$this->Task->expectAt(5, 'out', array('5. Tag'));
		$result = $this->Task->listAll('test_suite');
		$expected = array('articles', 'articles_tags', 'category_threads', 'comments', 'tags');
		$this->assertEqual($result, $expected);

		$this->Task->expectAt(7, 'out', array('1. Article'));
		$this->Task->expectAt(8, 'out', array('2. ArticlesTag'));
		$this->Task->expectAt(9, 'out', array('3. CategoryThread'));
		$this->Task->expectAt(10, 'out', array('4. Comment'));
		$this->Task->expectAt(11, 'out', array('5. Tag'));

		$this->Task->connection = 'test_suite';
		$result = $this->Task->listAll();
		$expected = array('articles', 'articles_tags', 'category_threads', 'comments', 'tags');
		$this->assertEqual($result, $expected);
	}

/**
 * Test that getName interacts with the user and returns the model name.
 *
 * @return void
 * @access public
 */
	function testGetName() {
		$this->Task->setReturnValue('in', 1);

		$this->Task->setReturnValueAt(0, 'in', 'q');
		$this->Task->expectOnce('_stop');
		$this->Task->getName('test_suite');

		$this->Task->setReturnValueAt(1, 'in', 1);
		$result = $this->Task->getName('test_suite');
		$expected = 'Article';
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(2, 'in', 4);
		$result = $this->Task->getName('test_suite');
		$expected = 'Comment';
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(3, 'in', 10);
		$result = $this->Task->getName('test_suite');
		$this->Task->expectOnce('err');
	}

/**
 * Test table name interactions
 *
 * @return void
 * @access public
 */
	function testGetTableName() {
		$this->Task->setReturnValueAt(0, 'in', 'y');
		$result = $this->Task->getTable('Article', 'test_suite');
		$expected = 'articles';
		$this->assertEqual($result, $expected);

		$this->Task->setReturnValueAt(1, 'in', 'n');
		$this->Task->setReturnValueAt(2, 'in', 'my_table');
		$result = $this->Task->getTable('Article', 'test_suite');
		$expected = 'my_table';
		$this->assertEqual($result, $expected);
	}

/**
 * test that initializing the validations works.
 *
 * @return void
 * @access public
 */
	function testInitValidations() {
		$result = $this->Task->initValidations();
		$this->assertTrue(in_array('notempty', $result));
	}

/**
 * test that individual field validation works, with interactive = false
 * tests the guessing features of validation
 *
 * @return void
 * @access public
 */
	function testFieldValidationGuessing() {
		$this->Task->interactive = false;
		$this->Task->initValidations();

		$result = $this->Task->fieldValidation('text', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('notempty' => 'notempty');
		$this->assertEqual($expected, $result);

		$result = $this->Task->fieldValidation('text', array('type' => 'date', 'length' => 10, 'null' => false));
		$expected = array('date' => 'date');
		$this->assertEqual($expected, $result);

		$result = $this->Task->fieldValidation('text', array('type' => 'time', 'length' => 10, 'null' => false));
		$expected = array('time' => 'time');
		$this->assertEqual($expected, $result);

		$result = $this->Task->fieldValidation('email', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('email' => 'email');
		$this->assertEqual($expected, $result);

		$result = $this->Task->fieldValidation('test', array('type' => 'integer', 'length' => 10, 'null' => false));
		$expected = array('numeric' => 'numeric');
		$this->assertEqual($expected, $result);

		$result = $this->Task->fieldValidation('test', array('type' => 'boolean', 'length' => 10, 'null' => false));
		$expected = array('boolean' => 'boolean');
		$this->assertEqual($expected, $result);

		$result = $this->Task->fieldValidation('test', array('type' => 'string', 'length' => 36, 'null' => false));
		$expected = array('uuid' => 'uuid');
		$this->assertEqual($expected, $result);
	}

/**
 * test that interactive field validation works and returns multiple validators.
 *
 * @return void
 * @access public
 */
	function testInteractiveFieldValidation() {
		$this->Task->initValidations();
		$this->Task->interactive = true;
		$this->Task->setReturnValueAt(0, 'in', '19');
		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->setReturnValueAt(2, 'in', '15');
		$this->Task->setReturnValueAt(3, 'in', 'n');

		$result = $this->Task->fieldValidation('text', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('notempty' => 'notempty', 'maxlength' => 'maxlength');
		$this->assertEqual($result, $expected);
	}

/**
 * test that a bogus response doesn't cause errors to bubble up.
 *
 * @return void
 */
	function testInteractiveFieldValidationWithBogusResponse() {
		$this->Task->initValidations();
		$this->Task->interactive = true;
		$this->Task->setReturnValueAt(0, 'in', '999999');
		$this->Task->setReturnValueAt(1, 'in', '19');
		$this->Task->setReturnValueAt(2, 'in', 'n');
		$this->Task->expectAt(4, 'out', array(new PatternExpectation('/make a valid/')));

		$result = $this->Task->fieldValidation('text', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('notempty' => 'notempty');
		$this->assertEqual($result, $expected);
	}

/**
 * test that a regular expression can be used for validation.
 *
 * @return void
 */
	function testInteractiveFieldValidationWithRegexp() {
		$this->Task->initValidations();
		$this->Task->interactive = true;
		$this->Task->setReturnValueAt(0, 'in', '/^[a-z]{0,9}$/');
		$this->Task->setReturnValueAt(1, 'in', 'n');

		$result = $this->Task->fieldValidation('text', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('a_z_0_9' => '/^[a-z]{0,9}$/');
		$this->assertEqual($result, $expected);
	}

/**
 * test the validation Generation routine
 *
 * @return void
 * @access public
 */
	function testNonInteractiveDoValidation() {
		$Model =& new MockModelTaskModel();
		$Model->primaryKey = 'id';
		$Model->setReturnValue('schema', array(
			'id' => array(
				'type' => 'integer',
				'length' => 11,
				'null' => false,
				'key' => 'primary',
			),
			'name' => array(
				'type' => 'string',
				'length' => 20,
				'null' => false,
			),
			'email' => array(
				'type' => 'string',
				'length' => 255,
				'null' => false,
			),
			'some_date' => array(
				'type' => 'date',
				'length' => '',
				'null' => false,
			),
			'some_time' => array(
				'type' => 'time',
				'length' => '',
				'null' => false,
			),
			'created' => array(
				'type' => 'datetime',
				'length' => '',
				'null' => false,
			)
		));
		$this->Task->interactive = false;

		$result = $this->Task->doValidation($Model);
		$expected = array(
			'name' => array(
				'notempty' => 'notempty'
			),
			'email' => array(
				'email' => 'email',
			),
			'some_date' => array(
				'date' => 'date'
			),
			'some_time' => array(
				'time' => 'time'
			),
		);
		$this->assertEqual($result, $expected);
	}

/**
 * test that finding primary key works
 *
 * @return void
 * @access public
 */
	function testFindPrimaryKey() {
		$fields = array(
			'one' => array(),
			'two' => array(),
			'key' => array('key' => 'primary')
		);
		$this->Task->expectAt(0, 'in', array('*', null, 'key'));
		$this->Task->setReturnValue('in', 'my_field');
		$result = $this->Task->findPrimaryKey($fields);
		$expected = 'my_field';
		$this->assertEqual($result, $expected);
	}

/**
 * test finding Display field
 *
 * @return void
 * @access public
 */
	function testFindDisplayField() {
		$fields = array('id' => array(), 'tagname' => array(), 'body' => array(),
			'created' => array(), 'modified' => array());

		$this->Task->setReturnValue('in', 'n');
		$this->Task->setReturnValueAt(0, 'in', 'n');
		$result = $this->Task->findDisplayField($fields);
		$this->assertFalse($result);

		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->setReturnValueAt(2, 'in', 2);
		$result = $this->Task->findDisplayField($fields);
		$this->assertEqual($result, 'tagname');
	}

/**
 * test that belongsTo generation works.
 *
 * @return void
 * @access public
 */
	function testBelongsToGeneration() {
		$model = new Model(array('ds' => 'test_suite', 'name' => 'Comment'));
		$result = $this->Task->findBelongsTo($model, array());
		$expected = array(
			'belongsTo' => array(
				array(
					'alias' => 'Article',
					'className' => 'Article',
					'foreignKey' => 'article_id',
				),
				array(
					'alias' => 'User',
					'className' => 'User',
					'foreignKey' => 'user_id',
				),
			)
		);
		$this->assertEqual($result, $expected);

		$model = new Model(array('ds' => 'test_suite', 'name' => 'CategoryThread'));
		$result = $this->Task->findBelongsTo($model, array());
		$expected = array(
			'belongsTo' => array(
				array(
					'alias' => 'ParentCategoryThread',
					'className' => 'CategoryThread',
					'foreignKey' => 'parent_id',
				),
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * test that hasOne and/or hasMany relations are generated properly.
 *
 * @return void
 * @access public
 */
	function testHasManyHasOneGeneration() {
		$model = new Model(array('ds' => 'test_suite', 'name' => 'Article'));
		$this->Task->connection = 'test_suite';
		$this->Task->listAll();
		$result = $this->Task->findHasOneAndMany($model, array());
		$expected = array(
			'hasMany' => array(
				array(
					'alias' => 'Comment',
					'className' => 'Comment',
					'foreignKey' => 'article_id',
				),
			),
			'hasOne' => array(
				array(
					'alias' => 'Comment',
					'className' => 'Comment',
					'foreignKey' => 'article_id',
				),
			),
		);
		$this->assertEqual($result, $expected);

		$model = new Model(array('ds' => 'test_suite', 'name' => 'CategoryThread'));
		$result = $this->Task->findHasOneAndMany($model, array());
		$expected = array(
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
			)
		);
		$this->assertEqual($result, $expected);
	}

/**
 * Test that HABTM generation works
 *
 * @return void
 * @access public
 */
	function testHasAndBelongsToManyGeneration() {
		$model = new Model(array('ds' => 'test_suite', 'name' => 'Article'));
		$this->Task->connection = 'test_suite';
		$this->Task->listAll();
		$result = $this->Task->findHasAndBelongsToMany($model, array());
		$expected = array(
			'hasAndBelongsToMany' => array(
				array(
					'alias' => 'Tag',
					'className' => 'Tag',
					'foreignKey' => 'article_id',
					'joinTable' => 'articles_tags',
					'associationForeignKey' => 'tag_id',
				),
			),
		);
		$this->assertEqual($result, $expected);
	}

/**
 * test non interactive doAssociations
 *
 * @return void
 * @access public
 */
	function testDoAssociationsNonInteractive() {
		$this->Task->connection = 'test_suite';
		$this->Task->interactive = false;
		$model = new Model(array('ds' => 'test_suite', 'name' => 'Article'));
		$result = $this->Task->doAssociations($model);
		$expected = array(
			'hasMany' => array(
				array(
					'alias' => 'Comment',
					'className' => 'Comment',
					'foreignKey' => 'article_id',
				),
			),
			'hasAndBelongsToMany' => array(
				array(
					'alias' => 'Tag',
					'className' => 'Tag',
					'foreignKey' => 'article_id',
					'joinTable' => 'articles_tags',
					'associationForeignKey' => 'tag_id',
				),
			),
		);
	}

/**
 * Ensure that the fixutre object is correctly called.
 *
 * @return void
 * @access public
 */
	function testBakeFixture() {
		$this->Task->plugin = 'test_plugin';
		$this->Task->interactive = true;
		$this->Task->Fixture->expectAt(0, 'bake', array('Article', 'articles'));
		$this->Task->bakeFixture('Article', 'articles');

		$this->assertEqual($this->Task->plugin, $this->Task->Fixture->plugin);
		$this->assertEqual($this->Task->connection, $this->Task->Fixture->connection);
		$this->assertEqual($this->Task->interactive, $this->Task->Fixture->interactive);
	}

/**
 * Ensure that the test object is correctly called.
 *
 * @return void
 * @access public
 */
	function testBakeTest() {
		$this->Task->plugin = 'test_plugin';
		$this->Task->interactive = true;
		$this->Task->Test->expectAt(0, 'bake', array('Model', 'Article'));
		$this->Task->bakeTest('Article');

		$this->assertEqual($this->Task->plugin, $this->Task->Test->plugin);
		$this->assertEqual($this->Task->connection, $this->Task->Test->connection);
		$this->assertEqual($this->Task->interactive, $this->Task->Test->interactive);
	}

/**
 * test confirming of associations, and that when an association is hasMany
 * a question for the hasOne is also not asked.
 *
 * @return void
 * @access public
 */
	function testConfirmAssociations() {
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
		$model = new Model(array('ds' => 'test_suite', 'name' => 'CategoryThread'));
		$this->Task->setReturnValueAt(0, 'in', 'y');
		$result = $this->Task->confirmAssociations($model, $associations);
		$this->assertTrue(empty($result['hasOne']));

		$this->Task->setReturnValue('in', 'n');
		$result = $this->Task->confirmAssociations($model, $associations);
		$this->assertTrue(empty($result['hasMany']));
		$this->assertTrue(empty($result['hasOne']));
	}

/**
 * test that inOptions generates questions and only accepts a valid answer
 *
 * @return void
 * @access public
 */
	function testInOptions() {
		$options = array('one', 'two', 'three');
		$this->Task->expectAt(0, 'out', array('1. one'));
		$this->Task->expectAt(1, 'out', array('2. two'));
		$this->Task->expectAt(2, 'out', array('3. three'));
		$this->Task->setReturnValueAt(0, 'in', 10);

		$this->Task->expectAt(3, 'out', array('1. one'));
		$this->Task->expectAt(4, 'out', array('2. two'));
		$this->Task->expectAt(5, 'out', array('3. three'));
		$this->Task->setReturnValueAt(1, 'in', 2);
		$result = $this->Task->inOptions($options, 'Pick a number');
		$this->assertEqual($result, 1);
	}

/**
 * test baking validation
 *
 * @return void
 * @access public
 */
	function testBakeValidation() {
		$validate = array(
			'name' => array(
				'notempty' => 'notempty'
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
		$result = $this->Task->bake('Article', compact('validate'));
		$this->assertPattern('/class Article extends AppModel \{/', $result);
		$this->assertPattern('/\$name \= \'Article\'/', $result);
		$this->assertPattern('/\$validate \= array\(/', $result);
		$expected = <<< STRINGEND
array(
			'notempty' => array(
				'rule' => array('notempty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
STRINGEND;
		$this->assertPattern('/' . preg_quote(str_replace("\r\n", "\n", $expected), '/') . '/', $result);
	}

/**
 * test baking relations
 *
 * @return void
 * @access public
 */
	function testBakeRelations() {
		$associations = array(
			'belongsTo' => array(
				array(
					'alias' => 'SomethingElse',
					'className' => 'SomethingElse',
					'foreignKey' => 'something_else_id',
				),
				array(
					'alias' => 'User',
					'className' => 'User',
					'foreignKey' => 'user_id',
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
					'alias' => 'Comment',
					'className' => 'Comment',
					'foreignKey' => 'parent_id',
				),
			),
			'hasAndBelongsToMany' => array(
				array(
					'alias' => 'Tag',
					'className' => 'Tag',
					'foreignKey' => 'article_id',
					'joinTable' => 'articles_tags',
					'associationForeignKey' => 'tag_id',
				),
			)
		);
		$result = $this->Task->bake('Article', compact('associations'));
		$this->assertPattern('/\$hasAndBelongsToMany \= array\(/', $result);
		$this->assertPattern('/\$hasMany \= array\(/', $result);
		$this->assertPattern('/\$belongsTo \= array\(/', $result);
		$this->assertPattern('/\$hasOne \= array\(/', $result);
		$this->assertPattern('/Tag/', $result);
		$this->assertPattern('/OtherModel/', $result);
		$this->assertPattern('/SomethingElse/', $result);
		$this->assertPattern('/Comment/', $result);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 * @access public
 */
	function testBakeWithPlugin() {
		$this->Task->plugin = 'ControllerTest';

		$path = APP . 'plugins' . DS . 'controller_test' . DS . 'models' . DS . 'article.php';
		$this->Task->expectAt(0, 'createFile', array($path, '*'));
		$this->Task->bake('Article', array(), array());

		$this->Task->plugin = 'controllerTest';

		$path = APP . 'plugins' . DS . 'controller_test' . DS . 'models' . DS . 'article.php';
		$this->Task->expectAt(1, 'createFile', array(
		$path, new PatternExpectation('/Article extends ControllerTestAppModel/')));
		$this->Task->bake('Article', array(), array());

		$this->assertEqual(count(ClassRegistry::keys()), 0);
		$this->assertEqual(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * test that execute passes runs bake depending with named model.
 *
 * @return void
 * @access public
 */
	function testExecuteWithNamedModel() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('article');
		$filename = '/my/path/article.php';
		$this->Task->setReturnValue('_checkUnitTest', 1);
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class Article extends AppModel/')));
		$this->Task->execute();

		$this->assertEqual(count(ClassRegistry::keys()), 0);
		$this->assertEqual(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * test that execute passes with different inflections of the same name.
 *
 * @return void
 * @access public
 */
	function testExecuteWithNamedModelVariations() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->setReturnValue('_checkUnitTest', 1);

		$this->Task->args = array('article');
		$filename = '/my/path/article.php';

		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class Article extends AppModel/')));
		$this->Task->execute();

		$this->Task->args = array('Articles');
		$this->Task->expectAt(1, 'createFile', array($filename, new PatternExpectation('/class Article extends AppModel/')));
		$this->Task->execute();

		$this->Task->args = array('articles');
		$this->Task->expectAt(2, 'createFile', array($filename, new PatternExpectation('/class Article extends AppModel/')));
		$this->Task->execute();
	}

/**
 * test that execute with a model name picks up hasMany associations.
 *
 * @return void
 * @access public
 */
	function testExecuteWithNamedModelHasManyCreated() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('article');
		$filename = '/my/path/article.php';
		$this->Task->setReturnValue('_checkUnitTest', 1);
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation("/'Comment' \=\> array\(/")));
		$this->Task->execute();
	}

/**
 * test that execute runs all() when args[0] = all
 *
 * @return void
 * @access public
 */
	function testExecuteIntoAll() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->setReturnValue('_checkUnitTest', true);

		$this->Task->Fixture->expectCallCount('bake', 5);
		$this->Task->Test->expectCallCount('bake', 5);

		$filename = '/my/path/article.php';
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class Article/')));

		$filename = '/my/path/articles_tag.php';
		$this->Task->expectAt(1, 'createFile', array($filename, new PatternExpectation('/class ArticlesTag/')));

		$filename = '/my/path/category_thread.php';
		$this->Task->expectAt(2, 'createFile', array($filename, new PatternExpectation('/class CategoryThread/')));

		$filename = '/my/path/comment.php';
		$this->Task->expectAt(3, 'createFile', array($filename, new PatternExpectation('/class Comment/')));

		$filename = '/my/path/tag.php';
		$this->Task->expectAt(4, 'createFile', array($filename, new PatternExpectation('/class Tag/')));

		$this->Task->execute();

		$this->assertEqual(count(ClassRegistry::keys()), 0);
		$this->assertEqual(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * test that skipTables changes how all() works.
 *
 * @return void
 */
	function testSkipTablesAndAll() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('all');
		$this->Task->setReturnValue('_checkUnitTest', true);
		$this->Task->skipTables = array('tags');

		$this->Task->Fixture->expectCallCount('bake', 4);
		$this->Task->Test->expectCallCount('bake', 4);

		$filename = '/my/path/article.php';
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class Article/')));

		$filename = '/my/path/articles_tag.php';
		$this->Task->expectAt(1, 'createFile', array($filename, new PatternExpectation('/class ArticlesTag/')));

		$filename = '/my/path/category_thread.php';
		$this->Task->expectAt(2, 'createFile', array($filename, new PatternExpectation('/class CategoryThread/')));

		$filename = '/my/path/comment.php';
		$this->Task->expectAt(3, 'createFile', array($filename, new PatternExpectation('/class Comment/')));

		$this->Task->execute();
	}

/**
 * test the interactive side of bake.
 *
 * @return void
 * @access public
 */
	function testExecuteIntoInteractive() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';
		$this->Task->interactive = true;

		$this->Task->setReturnValueAt(0, 'in', '1'); //choose article
		$this->Task->setReturnValueAt(1, 'in', 'n'); //no validation
		$this->Task->setReturnValueAt(2, 'in', 'y'); //yes to associations
		$this->Task->setReturnValueAt(3, 'in', 'y'); //yes to comment relation
		$this->Task->setReturnValueAt(4, 'in', 'y'); //yes to user relation
		$this->Task->setReturnValueAt(5, 'in', 'y'); //yes to tag relation
		$this->Task->setReturnValueAt(6, 'in', 'n'); //no to additional assocs
		$this->Task->setReturnValueAt(7, 'in', 'y'); //yes to looksGood?
		$this->Task->setReturnValue('_checkUnitTest', true);

		$this->Task->Test->expectOnce('bake');
		$this->Task->Fixture->expectOnce('bake');

		$filename = '/my/path/article.php';
		$this->Task->expectOnce('createFile');
		$this->Task->expectAt(0, 'createFile', array($filename, new PatternExpectation('/class Article/')));
		$this->Task->execute();

		$this->assertEqual(count(ClassRegistry::keys()), 0);
		$this->assertEqual(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * test using bake interactively with a table that does not exist.
 *
 * @return void
 * @access public
 */
	function testExecuteWithNonExistantTableName() {
		$this->Task->connection = 'test_suite';
		$this->Task->path = '/my/path/';

		$this->Task->expectOnce('_stop');
		$this->Task->expectOnce('err');

		$this->Task->setReturnValueAt(0, 'in', 'Foobar');
		$this->Task->setReturnValueAt(1, 'in', 'y');
		$this->Task->execute();
	}
}
