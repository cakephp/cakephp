<?php
/**
 * DboSourceTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *	Licensed under The Open Group Test Suite License
 *	Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.model.datasources
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Model', array('Model', 'DataSource', 'DboSource', 'DboMysql', 'App'));
require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * DboSourceTest class
 *
 * @package       cake.tests.cases.libs.model.datasources
 */
class DboSourceTest extends CakeTestCase {

/**
 * debug property
 *
 * @var mixed null
 * @access public
 */
	public $debug = null;

/**
 * autoFixtures property
 *
 * @var bool false
 * @access public
 */
	public $autoFixtures = false;

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array(
		'core.apple', 'core.article', 'core.articles_tag', 'core.attachment', 'core.comment',
		'core.sample', 'core.tag', 'core.user', 'core.post', 'core.author', 'core.data_test'
	);

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		parent::setUp();
		$this->__config = $this->db->config;

		if (!class_exists('DboTest')) {
			$db = ConnectionManager::getDataSource('test');
			$class = get_class($db);
			eval("class DboTest extends $class {
				var \$simulated = array();

/**
 * execute method
 *
 * @param \$sql
 * @access protected
 * @return void
 */
				function _execute(\$sql) {
					\$this->simulated[] = \$sql;
					return null;
				}

/**
 * getLastQuery method
 *
 * @access public
 * @return void
 */
				function getLastQuery() {
					return \$this->simulated[count(\$this->simulated) - 1];
				}
			}");
		}

		$this->testDb = new DboTest($this->__config);
		$this->testDb->cacheSources = false;
		$this->testDb->startQuote = '`';
		$this->testDb->endQuote = '`';

		$this->Model = new TestModel();
	}

/**
 * endTest method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		unset($this->Model);
	}


/**
 * test that booleans and null make logical condition strings.
 *
 * @return void
 */
	function testBooleanNullConditionsParsing() {
		$result = $this->testDb->conditions(true);
		$this->assertEqual($result, ' WHERE 1 = 1', 'true conditions failed %s');

		$result = $this->testDb->conditions(false);
		$this->assertEqual($result, ' WHERE 0 = 1', 'false conditions failed %s');

		$result = $this->testDb->conditions(null);
		$this->assertEqual($result, ' WHERE 1 = 1', 'null conditions failed %s');

		$result = $this->testDb->conditions(array());
		$this->assertEqual($result, ' WHERE 1 = 1', 'array() conditions failed %s');

		$result = $this->testDb->conditions('');
		$this->assertEqual($result, ' WHERE 1 = 1', '"" conditions failed %s');

		$result = $this->testDb->conditions(' ', '"  " conditions failed %s');
		$this->assertEqual($result, ' WHERE 1 = 1');
	}

/**
 * test that order() will accept objects made from DboSource::expression
 *
 * @return void
 */
	function testOrderWithExpression() {
		$expression = $this->testDb->expression("CASE Sample.id WHEN 1 THEN 'Id One' ELSE 'Other Id' END AS case_col");
		$result = $this->testDb->order($expression);
		$expected = " ORDER BY CASE Sample.id WHEN 1 THEN 'Id One' ELSE 'Other Id' END AS case_col";
		$this->assertEqual($result, $expected);
	}

/**
 * testMergeAssociations method
 *
 * @access public
 * @return void
 */
	function testMergeAssociations() {
		$data = array('Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
		));
		$merge = array('Topic' => array(array(
			'id' => '1', 'topic' => 'Topic', 'created' => '2007-03-17 01:16:23',
			'updated' => '2007-03-17 01:18:31'
		)));
		$expected = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Topic' => array(
				'id' => '1', 'topic' => 'Topic', 'created' => '2007-03-17 01:16:23',
				'updated' => '2007-03-17 01:18:31'
			)
		);
		$this->testDb->__mergeAssociation($data, $merge, 'Topic', 'hasOne');
		$this->assertEqual($data, $expected);

		$data = array('Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
		));
		$merge = array('User2' => array(array(
			'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
			'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
		)));

		$expected = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article',
				'body' => 'First Article Body', 'published' => 'Y',
				'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'User2' => array(
				'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
			)
		);
		$this->testDb->__mergeAssociation($data, $merge, 'User2', 'belongsTo');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(array('Comment' => false));
		$expected = array(
			'Article2' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Comment' => array()
		);
		$this->testDb->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
				'Comment' => array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Comment' => array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$expected = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Comment' => array(
				array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$this->testDb->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
				'Comment' => array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'User2' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Comment' => array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'User2' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$expected = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Comment' => array(
				array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'User2' => array(
						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					)
				),
				array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'User2' => array(
						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					)
				)
			)
		);
		$this->testDb->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
				'Comment' => array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'User2' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Tag' => array(
					array('id' => 1, 'tag' => 'Tag 1'),
					array('id' => 2, 'tag' => 'Tag 2')
				)
			),
			array(
				'Comment' => array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'User2' => array(
					'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				'Tag' => array()
			)
		);
		$expected = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Comment' => array(
				array(
					'id' => '1', 'comment' => 'Comment 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'User2' => array(
						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
					'Tag' => array(
						array('id' => 1, 'tag' => 'Tag 1'),
						array('id' => 2, 'tag' => 'Tag 2')
					)
				),
				array(
					'id' => '2', 'comment' => 'Comment 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31',
					'User2' => array(
						'id' => '1', 'user' => 'mariano', 'password' => '5f4dcc3b5aa765d61d8327deb882cf99', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
					),
					'Tag' => array()
				)
			)
		);
		$this->testDb->__mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
				'Tag' => array(
					'id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Tag' => array(
					'id' => '2', 'tag' => 'Tag 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Tag' => array(
					'id' => '3', 'tag' => 'Tag 3', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$expected = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Tag' => array(
				array(
					'id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				array(
					'id' => '2', 'tag' => 'Tag 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				),
				array(
					'id' => '3', 'tag' => 'Tag 3', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$this->testDb->__mergeAssociation($data, $merge, 'Tag', 'hasAndBelongsToMany');
		$this->assertEqual($data, $expected);

		$data = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			)
		);
		$merge = array(
			array(
				'Tag' => array(
					'id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Tag' => array(
					'id' => '2', 'tag' => 'Tag 2', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			),
			array(
				'Tag' => array(
					'id' => '3', 'tag' => 'Tag 3', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31'
				)
			)
		);
		$expected = array(
			'Article' => array(
				'id' => '1', 'user_id' => '1', 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y', 'created' => '2007-03-18 10:39:23', 'updated' => '2007-03-18 10:41:31'
			),
			'Tag' => array('id' => '1', 'tag' => 'Tag 1', 'created' => '2007-03-17 01:16:23', 'updated' => '2007-03-17 01:18:31')
		);
		$this->testDb->__mergeAssociation($data, $merge, 'Tag', 'hasOne');
		$this->assertEqual($data, $expected);
	}


/**
 * testMagicMethodQuerying method
 *
 * @access public
 * @return void
 */
	function testMagicMethodQuerying() {
		$result = $this->testDb->query('findByFieldName', array('value'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => 'value'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findByFindBy', array('value'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.find_by' => 'value'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findAllByFieldName', array('value'), $this->Model);
		$expected = array('all', array(
			'conditions' => array('TestModel.field_name' => 'value'),
			'fields' => null, 'order' => null, 'limit' => null,
			'page' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findAllById', array('a'), $this->Model);
		$expected = array('all', array(
			'conditions' => array('TestModel.id' => 'a'),
			'fields' => null, 'order' => null, 'limit' => null,
			'page' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findByFieldName', array(array('value1', 'value2', 'value3')), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => array('value1', 'value2', 'value3')),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findByFieldName', array(null), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => null),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findByFieldName', array('= a'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => '= a'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('findByFieldName', array(), $this->Model);
		$expected = false;
		$this->assertEqual($result, $expected);

		$result = $this->testDb->query('directCall', array(), $this->Model);
		$this->assertFalse($result);

		$result = $this->testDb->query('directCall', true, $this->Model);
		$this->assertFalse($result);

		$result = $this->testDb->query('directCall', false, $this->Model);
		$this->assertFalse($result);
	}


/**
 * testValue method
 *
 * @access public
 * @return void
 */
	function testValue() {
		$result = $this->testDb->value('{$__cakeForeignKey__$}');
		$this->assertEqual($result, '{$__cakeForeignKey__$}');

		$result = $this->testDb->value(array('first', 2, 'third'));
		$expected = array('\'first\'', 2, '\'third\'');
		$this->assertEqual($result, $expected);
	}

/**
 * testReconnect method
 *
 * @access public
 * @return void
 */
	function testReconnect() {
		$this->testDb->reconnect(array('prefix' => 'foo'));
		$this->assertTrue($this->testDb->connected);
		$this->assertEqual($this->testDb->config['prefix'], 'foo');
	}

/**
 * testName method
 *
 * @access public
 * @return void
 */
	function testName() {
		$result = $this->testDb->name('name');
		$expected = '`name`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name(array('name', 'Model.*'));
		$expected = array('`name`', '`Model`.*');
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('MTD()');
		$expected = 'MTD()';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('(sm)');
		$expected = '(sm)';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('name AS x');
		$expected = '`name` AS `x`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('Model.name AS x');
		$expected = '`Model`.`name` AS `x`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('Function(Something.foo)');
		$expected = 'Function(`Something`.`foo`)';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('Function(SubFunction(Something.foo))');
		$expected = 'Function(SubFunction(`Something`.`foo`))';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('Function(Something.foo) AS x');
		$expected = 'Function(`Something`.`foo`) AS `x`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name('name-with-minus');
		$expected = '`name-with-minus`';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->name(array('my-name', 'Foo-Model.*'));
		$expected = array('`my-name`', '`Foo-Model`.*');
		$this->assertEqual($result, $expected);
		
		$result = $this->testDb->name(array('Team.P%', 'Team.G/G'));
		$expected = array('`Team`.`P%`', '`Team`.`G/G`');
		$this->assertEqual($result, $expected);
	}

/**
 * test that cacheMethod works as exepected
 *
 * @return void
 */
	function testCacheMethod() {
		$this->testDb->cacheMethods = true;
		$result = $this->testDb->cacheMethod('name', 'some-key', 'stuff');
		$this->assertEqual($result, 'stuff');

		$result = $this->testDb->cacheMethod('name', 'some-key');
		$this->assertEqual($result, 'stuff');

		$result = $this->testDb->cacheMethod('conditions', 'some-key');
		$this->assertNull($result);

		$result = $this->testDb->cacheMethod('name', 'other-key');
		$this->assertNull($result);

		$this->testDb->cacheMethods = false;
		$result = $this->testDb->cacheMethod('name', 'some-key', 'stuff');
		$this->assertEqual($result, 'stuff');

		$result = $this->testDb->cacheMethod('name', 'some-key');
		$this->assertNull($result);
	}

/**
 * testLog method
 *
 * @access public
 * @return void
 */
	function testLog() {
		$this->testDb->logQuery('Query 1');
		$this->testDb->logQuery('Query 2');

		$log = $this->testDb->getLog(false, false);
		$result = Set::extract($log['log'], '/query');
		$expected = array('Query 1', 'Query 2');
		$this->assertEqual($result, $expected);

		$oldError = $this->testDb->error;
		$this->testDb->error = true;
		$result = $this->testDb->logQuery('Error 1');
		$this->assertFalse($result);
		$this->testDb->error = $oldError;

		$log = $this->testDb->getLog(false, false);
		$result = Set::combine($log['log'], '/query', '/error');
		$expected = array('Query 1' => false, 'Query 2' => false, 'Error 1' => true);
		$this->assertEqual($result, $expected);

		Configure::write('debug', 2);
		ob_start();
		$this->testDb->showLog();
		$contents = ob_get_clean();

		$this->assertPattern('/Query 1/s', $contents);
		$this->assertPattern('/Query 2/s', $contents);
		$this->assertPattern('/Error 1/s', $contents);

		ob_start();
		$this->testDb->showLog(true);
		$contents = ob_get_clean();

		$this->assertPattern('/Query 1/s', $contents);
		$this->assertPattern('/Query 2/s', $contents);
		$this->assertPattern('/Error 1/s', $contents);

		$oldError = $this->testDb->error;
		$oldDebug = Configure::read('debug');
		Configure::write('debug', 2);

		$this->testDb->error = $oldError;
		Configure::write('debug', $oldDebug);
	}

	function testShowQueryError() {
		$this->testDb->error = true;
		try {
			$this->testDb->showQuery('Error 2');
			$this->fail('No exception');
		} catch (Exception $e) {
			$this->assertPattern('/SQL Error/', $e->getMessage());
			$this->assertTrue(true, 'Exception thrown');
		}
	}

/**
 * test getting the query log as an array.
 *
 * @return void
 */
	function testGetLog() {
		$this->testDb->logQuery('Query 1');
		$this->testDb->logQuery('Query 2');

		$oldError = $this->testDb->error;
		$this->testDb->error = true;
		$result = $this->testDb->logQuery('Error 1');
		$this->assertFalse($result);
		$this->testDb->error = $oldError;

		$log = $this->testDb->getLog();
		$expected = array('query' => 'Query 1', 'error' => '', 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEqual($log['log'][0], $expected);
		$expected = array('query' => 'Query 2', 'error' => '', 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEqual($log['log'][1], $expected);
		$expected = array('query' => 'Error 1', 'error' => true, 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEqual($log['log'][2], $expected);
	}

/**
 * test that query() returns boolean values from operations like CREATE TABLE
 *
 * @return void
 */
	function testFetchAllBooleanReturns() {
		$name = $this->db->fullTableName('test_query');
		$query = "CREATE TABLE {$name} (name varchar(10));";
		$result = $this->db->query($query);
		$this->assertTrue($result, 'Query did not return a boolean');

		$query = "DROP TABLE {$name};";
		$result = $this->db->query($query);
		$this->assertTrue($result, 'Query did not return a boolean');
	}

/**
 * test ShowQuery generation of regular and error messages
 *
 * @return void
 */
	function testShowQuery() {
		$this->testDb->error = false;
		ob_start();
		$this->testDb->showQuery('Some Query');
		$contents = ob_get_clean();
		$this->assertPattern('/Some Query/s', $contents);
		$this->assertPattern('/Aff:/s', $contents);
		$this->assertPattern('/Num:/s', $contents);
		$this->assertPattern('/Took:/s', $contents);
	}

/**
 * test order to generate query order clause for virtual fields
 *
 * @return void
 */
	function testVirtualFieldsInOrder() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
		);
		$order = array('two', 'this_moment');
		$result = $this->db->order($order, 'ASC', $Article);
		$expected = ' ORDER BY (1 + 1) ASC, (NOW()) ASC';
		$this->assertEqual($expected, $result);

		$order = array('Article.two', 'Article.this_moment');
		$result = $this->db->order($order, 'ASC', $Article);
		$expected = ' ORDER BY (1 + 1) ASC, (NOW()) ASC';
		$this->assertEqual($expected, $result);
	}



/**
 * test the permutations of fullTableName()
 *
 * @return void
 */
	function testFullTablePermutations() {
		$Article = ClassRegistry::init('Article');
		$result = $this->testDb->fullTableName($Article, false);
		$this->assertEqual($result, 'articles');

		$Article->tablePrefix = 'tbl_';
		$result = $this->testDb->fullTableName($Article, false);
		$this->assertEqual($result, 'tbl_articles');
		
		$Article->useTable = $Article->table = 'with spaces';
		$Article->tablePrefix = '';
		$result = $this->testDb->fullTableName($Article);
		$this->assertEqual($result, '`with spaces`');
	}

/**
 * test that read() only calls queryAssociation on db objects when the method is defined.
 *
 * @return void
 */
	function testReadOnlyCallingQueryAssociationWhenDefined() {
		$this->loadFixtures('Article', 'User', 'ArticlesTag', 'Tag');
		ConnectionManager::create('test_no_queryAssociation', array(
			'datasource' => 'data'
		));
		$Article = ClassRegistry::init('Article');
		$Article->Comment->useDbConfig = 'test_no_queryAssociation';
		$result = $Article->find('all');
		$this->assertTrue(is_array($result));
	}

/**
 * test that fields() is using methodCache()
 *
 * @return void
 */
	function testFieldsUsingMethodCache() {
		$this->testDb->cacheMethods = false;
		$this->assertTrue(empty($this->testDb->methodCache['fields']), 'Cache not empty');

		$Article = ClassRegistry::init('Article');
		$this->testDb->fields($Article, null, array('title', 'body', 'published'));
		$this->assertTrue(empty($this->testDb->methodCache['fields']), 'Cache not empty');
	}

/**
 * testStatements method
 *
 * @access public
 * @return void
 */
	function testStatements() {
		$this->skipIf(!$this->testDb instanceof DboMysql);
		$this->loadFixtures('Article', 'User', 'Comment', 'Tag', 'Attachment', 'ArticlesTag');
		$Article = new Article();

		$result = $this->testDb->update($Article, array('field1'), array('value1'));
		$this->assertFalse($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*UPDATE\s+' . $this->testDb->fullTableName('articles') . '\s+SET\s+`field1`\s*=\s*\'value1\'\s+WHERE\s+1 = 1\s*$/', $result);

		$result = $this->testDb->update($Article, array('field1'), array('2'), '2=2');
		$this->assertFalse($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*UPDATE\s+' . $this->testDb->fullTableName('articles') . ' AS `Article`\s+LEFT JOIN\s+' . $this->testDb->fullTableName('users') . ' AS `User` ON \(`Article`.`user_id` = `User`.`id`\)\s+SET\s+`Article`\.`field1`\s*=\s*2\s+WHERE\s+2\s*=\s*2\s*$/', $result);

		$result = $this->testDb->delete($Article);
		$this->assertTrue($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*DELETE\s+FROM\s+' . $this->testDb->fullTableName('articles') . '\s+WHERE\s+1 = 1\s*$/', $result);

		$result = $this->testDb->delete($Article, true);
		$this->assertTrue($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*DELETE\s+`Article`\s+FROM\s+' . $this->testDb->fullTableName('articles') . '\s+AS `Article`\s+LEFT JOIN\s+' . $this->testDb->fullTableName('users') . ' AS `User` ON \(`Article`.`user_id` = `User`.`id`\)\s+WHERE\s+1\s*=\s*1\s*$/', $result);

		$result = $this->testDb->delete($Article, '2=2');
		$this->assertTrue($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*DELETE\s+`Article`\s+FROM\s+' . $this->testDb->fullTableName('articles') . '\s+AS `Article`\s+LEFT JOIN\s+' . $this->testDb->fullTableName('users') . ' AS `User` ON \(`Article`.`user_id` = `User`.`id`\)\s+WHERE\s+2\s*=\s*2\s*$/', $result);

		$result = $this->testDb->hasAny($Article, '1=2');
		$this->assertFalse($result);
	}
}
