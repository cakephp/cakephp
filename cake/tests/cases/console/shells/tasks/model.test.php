<?php
/**
 * ModelTaskTest file
 *
 * Test Case for test generation shell task
 *
 * PHP 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright 2006-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2006-2010, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       cake.tests.cases.console.libs.tasks
 * @since         CakePHP v 1.2.6
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);
App::import('Shell', array(
	'tasks/model',
	'tasks/fixture',
	'tasks/template'
));

require_once CAKE . 'console' .  DS . 'shell_dispatcher.php';

/**
 * ModelTaskTest class
 *
 * @package       cake.tests.cases.console.libs.tasks
 */
class ModelTaskTest extends CakeTestCase {

/**
 * fixtures
 *
 * @var array
 * @access public
 */
	public $fixtures = array(
		'core.bake_article', 'core.bake_comment', 'core.bake_articles_bake_tag',
		'core.bake_tag', 'core.category_thread'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('ModelTask',
			array('in', 'err', 'createFile', '_stop', '_checkUnitTest'),
			array($out, $out, $in)
		);
		$this->_setupOtherMocks();
	}

/**
 * Setup a mock that has out mocked.  Normally this is not used as it makes $this->at() really tricky.
 *
 * @return void
 */
	protected function _useMockedOut() {
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('ModelTask',
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
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task->Fixture = $this->getMock('FixtureTask', array(), array($out, $out, $in));
		$this->Task->Test = $this->getMock('FixtureTask', array(), array($out, $out, $in));
		$this->Task->Template = new TemplateTask($out, $out, $in);

		$this->Task->name = 'Model';
		$this->Task->interactive = true;
	}

/**
 * teardown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
	}

/**
 * Test that listAll scans the database connection and lists all the tables in it.s
 *
 * @return void
 */
	public function testListAll() {
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}
		$this->_useMockedOut();

		$this->Task->expects($this->at(1))->method('out')->with('1. BakeArticle');
		$this->Task->expects($this->at(2))->method('out')->with('2. BakeArticlesBakeTag');
		$this->Task->expects($this->at(3))->method('out')->with('3. BakeComment');
		$this->Task->expects($this->at(4))->method('out')->with('4. BakeTag');
		$this->Task->expects($this->at(5))->method('out')->with('5. CategoryThread');

		$this->Task->expects($this->at(7))->method('out')->with('1. BakeArticle');
		$this->Task->expects($this->at(8))->method('out')->with('2. BakeArticlesBakeTag');
		$this->Task->expects($this->at(9))->method('out')->with('3. BakeComment');
		$this->Task->expects($this->at(10))->method('out')->with('4. BakeTag');
		$this->Task->expects($this->at(11))->method('out')->with('5. CategoryThread');

		$result = $this->Task->listAll('test');
		$expected = array('bake_articles', 'bake_articles_bake_tags', 'bake_comments', 'bake_tags', 'category_threads');
		$this->assertEqual($result, $expected);

		$this->Task->connection = 'test';
		$result = $this->Task->listAll();
		$expected = array('bake_articles', 'bake_articles_bake_tags', 'bake_comments', 'bake_tags', 'category_threads');
		$this->assertEqual($result, $expected);
	}

/**
 * Test that getName interacts with the user and returns the model name.
 *
 * @return void
 */
	public function testGetNameQuit() {
		$this->Task->expects($this->once())->method('in')->will($this->returnValue('q'));
		$this->Task->expects($this->once())->method('_stop');
		$this->Task->getName('test');
	}

/**
 * test getName with a valid option.
 *
 * @return void
 */
	function testGetNameValidOption() {
		$listing = $this->Task->listAll('test');
		$this->Task->expects($this->any())->method('in')->will($this->onConsecutiveCalls(1, 4));

		$result = $this->Task->getName('test');
		$this->assertEquals(Inflector::classify($listing[0]), $result);

		$result = $this->Task->getName('test');
		$this->assertEquals(Inflector::classify($listing[3]), $result);
	}

/**
 * test that an out of bounds option causes an error.
 *
 * @return void
 */
	function testGetNameWithOutOfBoundsOption() {
		$this->Task->expects($this->any())->method('in')->will($this->onConsecutiveCalls(99, 1));
		$this->Task->expects($this->once())->method('err');

		$result = $this->Task->getName('test');
	}

/**
 * Test table name interactions
 *
 * @return void
 */
	public function testGetTableName() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$result = $this->Task->getTable('BakeArticle', 'test');
		$expected = 'bake_articles';
		$this->assertEqual($result, $expected);
	}

/**
 * test gettting a custom table name.
 *
 * @return void
 */
	function testGetTableNameCustom() {
		$this->Task->expects($this->any())->method('in')->will($this->onConsecutiveCalls('n', 'my_table'));
		$result = $this->Task->getTable('BakeArticle', 'test');
		$expected = 'my_table';
		$this->assertEqual($result, $expected);
	}

/**
 * test that initializing the validations works.
 *
 * @return void
 */
	public function testInitValidations() {
		$result = $this->Task->initValidations();
		$this->assertTrue(in_array('notempty', $result));
	}

/**
 * test that individual field validation works, with interactive = false
 * tests the guessing features of validation
 *
 * @return void
 */
	public function testFieldValidationGuessing() {
		$this->Task->interactive = false;
		$this->Task->initValidations();

		$result = $this->Task->fieldValidation('text', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('notempty' => 'notempty');

		$result = $this->Task->fieldValidation('text', array('type' => 'date', 'length' => 10, 'null' => false));
		$expected = array('date' => 'date');

		$result = $this->Task->fieldValidation('text', array('type' => 'time', 'length' => 10, 'null' => false));
		$expected = array('time' => 'time');

		$result = $this->Task->fieldValidation('email', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('email' => 'email');

		$result = $this->Task->fieldValidation('test', array('type' => 'integer', 'length' => 10, 'null' => false));
		$expected = array('numeric' => 'numeric');

		$result = $this->Task->fieldValidation('test', array('type' => 'boolean', 'length' => 10, 'null' => false));
		$expected = array('numeric' => 'numeric');
	}

/**
 * test that interactive field validation works and returns multiple validators.
 *
 * @return void
 */
	public function testInteractiveFieldValidation() {
		$this->Task->initValidations();
		$this->Task->interactive = true;
		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('20', 'y', '16', 'n'));

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
		$this->_useMockedOut();
		$this->Task->initValidations();
		$this->Task->interactive = true;

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('999999', '20', 'n'));

		$this->Task->expects($this->at(7))->method('out')
			->with(new PHPUnit_Framework_Constraint_PCREMatch('/make a valid/'));

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
		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('/^[a-z]{0,9}$/', 'n'));

		$result = $this->Task->fieldValidation('text', array('type' => 'string', 'length' => 10, 'null' => false));
		$expected = array('a_z_0_9' => '/^[a-z]{0,9}$/');
		$this->assertEqual($result, $expected);
	}

/**
 * test the validation Generation routine
 *
 * @return void
 */
	public function testNonInteractiveDoValidation() {
		$Model = $this->getMock('Model');
		$Model->primaryKey = 'id';
		$Model->expects($this->any())->method('schema')->will($this->returnValue(array(
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
		)));
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
 */
	public function testFindPrimaryKey() {
		$fields = array(
			'one' => array(),
			'two' => array(),
			'key' => array('key' => 'primary')
		);
		$anything = new PHPUnit_Framework_Constraint_IsAnything();
		$this->Task->expects($this->once())->method('in')
			->with($anything, null, 'key')
			->will($this->returnValue('my_field'));
	
		$result = $this->Task->findPrimaryKey($fields);
		$expected = 'my_field';
		$this->assertEqual($result, $expected);
	}

/**
 * test finding Display field
 *
 * @return void
 */
	public function testFindDisplayFieldNone() {
		$fields = array(
			'id' => array(), 'tagname' => array(), 'body' => array(),
			'created' => array(), 'modified' => array()
		);
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('n'));
		$result = $this->Task->findDisplayField($fields);
		$this->assertFalse($result);
	}

/**
 * Test finding a displayname from user input
 *
 * @return void
 */
	public function testFindDisplayName() {
		$fields = array(
			'id' => array(), 'tagname' => array(), 'body' => array(),
			'created' => array(), 'modified' => array()
		);
		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('y', 2));

		$result = $this->Task->findDisplayField($fields);
		$this->assertEqual($result, 'tagname');
	}

/**
 * test that belongsTo generation works.
 *
 * @return void
 */
	public function testBelongsToGeneration() {
		$model = new Model(array('ds' => 'test', 'name' => 'BakeComment'));
		$result = $this->Task->findBelongsTo($model, array());
		$expected = array(
			'belongsTo' => array(
				array(
					'alias' => 'BakeArticle',
					'className' => 'BakeArticle',
					'foreignKey' => 'bake_article_id',
				),
				array(
					'alias' => 'BakeUser',
					'className' => 'BakeUser',
					'foreignKey' => 'bake_user_id',
				),
			)
		);
		$this->assertEqual($result, $expected);

		$model = new Model(array('ds' => 'test', 'name' => 'CategoryThread'));
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
 */
	public function testHasManyHasOneGeneration() {
		$model = new Model(array('ds' => 'test', 'name' => 'BakeArticle'));
		$this->Task->connection = 'test';
		$this->Task->listAll();
		$result = $this->Task->findHasOneAndMany($model, array());
		$expected = array(
			'hasMany' => array(
				array(
					'alias' => 'BakeComment',
					'className' => 'BakeComment',
					'foreignKey' => 'bake_article_id',
				),
			),
			'hasOne' => array(
				array(
					'alias' => 'BakeComment',
					'className' => 'BakeComment',
					'foreignKey' => 'bake_article_id',
				),
			),
		);
		$this->assertEqual($result, $expected);

		$model = new Model(array('ds' => 'test', 'name' => 'CategoryThread'));
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
 */
	public function testHasAndBelongsToManyGeneration() {
		$model = new Model(array('ds' => 'test', 'name' => 'BakeArticle'));
		$this->Task->connection = 'test';
		$this->Task->listAll();
		$result = $this->Task->findHasAndBelongsToMany($model, array());
		$expected = array(
			'hasAndBelongsToMany' => array(
				array(
					'alias' => 'BakeTag',
					'className' => 'BakeTag',
					'foreignKey' => 'bake_article_id',
					'joinTable' => 'bake_articles_bake_tags',
					'associationForeignKey' => 'bake_tag_id',
				),
			),
		);
		$this->assertEqual($result, $expected);
	}

/**
 * test non interactive doAssociations
 *
 * @return void
 */
	public function testDoAssociationsNonInteractive() {
		$this->Task->connection = 'test';
		$this->Task->interactive = false;
		$model = new Model(array('ds' => 'test', 'name' => 'BakeArticle'));
		$result = $this->Task->doAssociations($model);
		$expected = array(
			'hasMany' => array(
				array(
					'alias' => 'BakeComment',
					'className' => 'BakeComment',
					'foreignKey' => 'bake_article_id',
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
			),
		);
	}

/**
 * Ensure that the fixutre object is correctly called.
 *
 * @return void
 */
	public function testBakeFixture() {
		$this->Task->plugin = 'test_plugin';
		$this->Task->interactive = true;
		$this->Task->Fixture->expects($this->at(0))->method('bake')->with('BakeArticle', 'bake_articles');
		$this->Task->bakeFixture('BakeArticle', 'bake_articles');

		$this->assertEqual($this->Task->plugin, $this->Task->Fixture->plugin);
		$this->assertEqual($this->Task->connection, $this->Task->Fixture->connection);
		$this->assertEqual($this->Task->interactive, $this->Task->Fixture->interactive);
	}

/**
 * Ensure that the test object is correctly called.
 *
 * @return void
 */
	public function testBakeTest() {
		$this->Task->plugin = 'test_plugin';
		$this->Task->interactive = true;
		$this->Task->Test->expects($this->at(0))->method('bake')->with('Model', 'BakeArticle');
		$this->Task->bakeTest('BakeArticle');

		$this->assertEqual($this->Task->plugin, $this->Task->Test->plugin);
		$this->assertEqual($this->Task->connection, $this->Task->Test->connection);
		$this->assertEqual($this->Task->interactive, $this->Task->Test->interactive);
	}

/**
 * test confirming of associations, and that when an association is hasMany
 * a question for the hasOne is also not asked.
 *
 * @return void
 */
	public function testConfirmAssociations() {
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
		$this->assertEqual($result, 1);
	}

/**
 * test baking validation
 *
 * @return void
 */
	public function testBakeValidation() {
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
		$result = $this->Task->bake('BakeArticle', compact('validate'));
		$this->assertPattern('/class BakeArticle extends AppModel \{/', $result);
		$this->assertPattern('/\$name \= \'BakeArticle\'/', $result);
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
 */
	public function testBakeRelations() {
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
		$this->assertPattern('/\$hasAndBelongsToMany \= array\(/', $result);
		$this->assertPattern('/\$hasMany \= array\(/', $result);
		$this->assertPattern('/\$belongsTo \= array\(/', $result);
		$this->assertPattern('/\$hasOne \= array\(/', $result);
		$this->assertPattern('/BakeTag/', $result);
		$this->assertPattern('/OtherModel/', $result);
		$this->assertPattern('/SomethingElse/', $result);
		$this->assertPattern('/BakeComment/', $result);
	}

/**
 * test bake() with a -plugin param
 *
 * @return void
 */
	public function testBakeWithPlugin() {
		$this->Task->plugin = 'controllerTest';

		$path = APP . 'plugins' . DS . 'controller_test' . DS . 'models' . DS . 'bake_article.php';
		$this->Task->expects($this->once())->method('createFile')
			->with($path, new PHPUnit_Framework_Constraint_PCREMatch('/BakeArticle extends ControllerTestAppModel/'));
	
		$this->Task->bake('BakeArticle', array(), array());

		$this->assertEqual(count(ClassRegistry::keys()), 0);
		$this->assertEqual(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * test that execute passes runs bake depending with named model.
 *
 * @return void
 */
	public function testExecuteWithNamedModel() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('bake_article');
		$filename = '/my/path/bake_article.php';
		
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(1));
		$this->Task->expects($this->once())->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class BakeArticle extends AppModel/'));

		$this->Task->execute();

		$this->assertEqual(count(ClassRegistry::keys()), 0);
		$this->assertEqual(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * data provider for testExecuteWithNamedModelVariations
 *
 * @return void
 */
	static function nameVariations() {
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
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(1));

		$this->Task->args = array($name);
		$filename = '/my/path/bake_article.php';

		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class BakeArticle extends AppModel/'));
		$this->Task->execute();
	}

/**
 * test that execute with a model name picks up hasMany associations.
 *
 * @return void
 */
	public function testExecuteWithNamedModelHasManyCreated() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->args = array('bake_article');
		$filename = '/my/path/bake_article.php';
	
		$this->Task->expects($this->once())->method('_checkUnitTest')->will($this->returnValue(1));
		$this->Task->expects($this->at(0))->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch("/'BakeComment' \=\> array\(/"));

		$this->Task->execute();
	}

/**
 * test that execute runs all() when args[0] = all
 *
 * @return void
 */
	public function testExecuteIntoAll() {
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

		$filename = '/my/path/bake_article.php';
		$this->Task->expects($this->at(1))->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class BakeArticle/'));

		$filename = '/my/path/bake_articles_bake_tag.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class BakeArticlesBakeTag/'));

		$filename = '/my/path/bake_comment.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class BakeComment/'));

		$filename = '/my/path/bake_tag.php';
		$this->Task->expects($this->at(4))
			->method('createFile')->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class BakeTag/'));
		
		$filename = '/my/path/category_thread.php';
		$this->Task->expects($this->at(5))->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class CategoryThread/'));

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

		$filename = '/my/path/bake_article.php';
		$this->Task->expects($this->at(1))->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class BakeArticle/'));

		$filename = '/my/path/bake_articles_bake_tag.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class BakeArticlesBakeTag/'));

		$filename = '/my/path/bake_comment.php';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class BakeComment/'));

		$filename = '/my/path/category_thread.php';
		$this->Task->expects($this->at(4))->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class CategoryThread/'));

		$this->Task->execute();
	}

/**
 * test the interactive side of bake.
 *
 * @return void
 */
	public function testExecuteIntoInteractive() {
		$count = count($this->Task->listAll('test'));
		if ($count != count($this->fixtures)) {
			$this->markTestSkipped('Additional tables detected.');
		}

		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';
		$this->Task->interactive = true;

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls(
				'1', // article
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

		$filename = '/my/path/bake_article.php';

		$this->Task->expects($this->once())->method('createFile')
			->with($filename, new PHPUnit_Framework_Constraint_PCREMatch('/class BakeArticle/'));

		$this->Task->execute();

		$this->assertEqual(count(ClassRegistry::keys()), 0);
		$this->assertEqual(count(ClassRegistry::mapKeys()), 0);
	}

/**
 * test using bake interactively with a table that does not exist.
 *
 * @return void
 */
	public function testExecuteWithNonExistantTableName() {
		$this->Task->connection = 'test';
		$this->Task->path = '/my/path/';

		$this->Task->expects($this->once())->method('_stop');
		$this->Task->expects($this->once())->method('err');

		$this->Task->expects($this->any())->method('in')
			->will($this->onConsecutiveCalls('Foobar', 'y'));

		$this->Task->execute();
	}
}
