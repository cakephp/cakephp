<?php
/**
 * DboSourceTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *	Licensed under The Open Group Test Suite License
 *	Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('DataSource', 'Model/Datasource');
App::uses('DboSource', 'Model/Datasource');
require_once dirname(dirname(__FILE__)) . DS . 'models.php';

class MockDataSource extends DataSource {
}

class DboTestSource extends DboSource {

	public function connect($config = array()) {
		$this->connected = true;
	}

	public function mergeAssociation(&$data, &$merge, $association, $type, $selfJoin = false) {
		return parent::_mergeAssociation($data, $merge, $association, $type, $selfJoin);
	}

	public function setConfig($config = array()) {
		$this->config = $config;
	}

	public function setConnection($conn) {
		$this->_connection = $conn;
	}
}

/**
 * DboSourceTest class
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DboSourceTest extends CakeTestCase {

/**
 * debug property
 *
 * @var mixed null
 */
	public $debug = null;

/**
 * autoFixtures property
 *
 * @var bool false
 */
	public $autoFixtures = false;

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array(
		'core.apple', 'core.article', 'core.articles_tag', 'core.attachment', 'core.comment',
		'core.sample', 'core.tag', 'core.user', 'core.post', 'core.author', 'core.data_test'
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->__config = $this->db->config;

		$this->testDb = new DboTestSource();
		$this->testDb->cacheSources = false;
		$this->testDb->startQuote = '`';
		$this->testDb->endQuote = '`';

		$this->Model = new TestModel();
	}

/**
 * endTest method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Model);
	}


/**
 * test that booleans and null make logical condition strings.
 *
 * @return void
 */
	public function testBooleanNullConditionsParsing() {
		$result = $this->testDb->conditions(true);
		$this->assertEquals($result, ' WHERE 1 = 1', 'true conditions failed %s');

		$result = $this->testDb->conditions(false);
		$this->assertEquals($result, ' WHERE 0 = 1', 'false conditions failed %s');

		$result = $this->testDb->conditions(null);
		$this->assertEquals($result, ' WHERE 1 = 1', 'null conditions failed %s');

		$result = $this->testDb->conditions(array());
		$this->assertEquals($result, ' WHERE 1 = 1', 'array() conditions failed %s');

		$result = $this->testDb->conditions('');
		$this->assertEquals($result, ' WHERE 1 = 1', '"" conditions failed %s');

		$result = $this->testDb->conditions(' ', '"  " conditions failed %s');
		$this->assertEquals($result, ' WHERE 1 = 1');
	}

/**
 * test that order() will accept objects made from DboSource::expression
 *
 * @return void
 */
	public function testOrderWithExpression() {
		$expression = $this->testDb->expression("CASE Sample.id WHEN 1 THEN 'Id One' ELSE 'Other Id' END AS case_col");
		$result = $this->testDb->order($expression);
		$expected = " ORDER BY CASE Sample.id WHEN 1 THEN 'Id One' ELSE 'Other Id' END AS case_col";
		$this->assertEquals($expected, $result);
	}

/**
 * testMergeAssociations method
 *
 * @return void
 */
	public function testMergeAssociations() {
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
		$this->testDb->mergeAssociation($data, $merge, 'Topic', 'hasOne');
		$this->assertEquals($data, $expected);

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
		$this->testDb->mergeAssociation($data, $merge, 'User2', 'belongsTo');
		$this->assertEquals($data, $expected);

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
		$this->testDb->mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEquals($data, $expected);

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
		$this->testDb->mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEquals($data, $expected);

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
		$this->testDb->mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEquals($data, $expected);

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
		$this->testDb->mergeAssociation($data, $merge, 'Comment', 'hasMany');
		$this->assertEquals($data, $expected);

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
		$this->testDb->mergeAssociation($data, $merge, 'Tag', 'hasAndBelongsToMany');
		$this->assertEquals($data, $expected);

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
		$this->testDb->mergeAssociation($data, $merge, 'Tag', 'hasOne');
		$this->assertEquals($data, $expected);
	}


/**
 * testMagicMethodQuerying method
 *
 * @return void
 */
	public function testMagicMethodQuerying() {
		$result = $this->db->query('findByFieldName', array('value'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => 'value'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findByFindBy', array('value'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.find_by' => 'value'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findAllByFieldName', array('value'), $this->Model);
		$expected = array('all', array(
			'conditions' => array('TestModel.field_name' => 'value'),
			'fields' => null, 'order' => null, 'limit' => null,
			'page' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findAllById', array('a'), $this->Model);
		$expected = array('all', array(
			'conditions' => array('TestModel.id' => 'a'),
			'fields' => null, 'order' => null, 'limit' => null,
			'page' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findByFieldName', array(array('value1', 'value2', 'value3')), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => array('value1', 'value2', 'value3')),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findByFieldName', array(null), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => null),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findByFieldName', array('= a'), $this->Model);
		$expected = array('first', array(
			'conditions' => array('TestModel.field_name' => '= a'),
			'fields' => null, 'order' => null, 'recursive' => null
		));
		$this->assertEquals($expected, $result);

		$result = $this->db->query('findByFieldName', array(), $this->Model);
		$expected = false;
		$this->assertEquals($expected, $result);
	}

/**
 *
 * @expectedException PDOException
 * @return void
 */
	public function testDirectCallThrowsException() {
		$result = $this->db->query('directCall', array(), $this->Model);
	}


/**
 * testValue method
 *
 * @return void
 */
	public function testValue() {
		$result = $this->db->value('{$__cakeForeignKey__$}');
		$this->assertEquals($result, '{$__cakeForeignKey__$}');

		$result = $this->db->value(array('first', 2, 'third'));
		$expected = array('\'first\'', 2, '\'third\'');
		$this->assertEquals($expected, $result);
	}

/**
 * testReconnect method
 *
 * @return void
 */
	public function testReconnect() {
		$this->testDb->reconnect(array('prefix' => 'foo'));
		$this->assertTrue($this->testDb->connected);
		$this->assertEquals($this->testDb->config['prefix'], 'foo');
	}

/**
 * testName method
 *
 * @return void
 */
	public function testName() {
		$result = $this->testDb->name('name');
		$expected = '`name`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name(array('name', 'Model.*'));
		$expected = array('`name`', '`Model`.*');
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('MTD()');
		$expected = 'MTD()';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('(sm)');
		$expected = '(sm)';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('name AS x');
		$expected = '`name` AS `x`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('Model.name AS x');
		$expected = '`Model`.`name` AS `x`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('Function(Something.foo)');
		$expected = 'Function(`Something`.`foo`)';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('Function(SubFunction(Something.foo))');
		$expected = 'Function(SubFunction(`Something`.`foo`))';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('Function(Something.foo) AS x');
		$expected = 'Function(`Something`.`foo`) AS `x`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('name-with-minus');
		$expected = '`name-with-minus`';
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name(array('my-name', 'Foo-Model.*'));
		$expected = array('`my-name`', '`Foo-Model`.*');
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name(array('Team.P%', 'Team.G/G'));
		$expected = array('`Team`.`P%`', '`Team`.`G/G`');
		$this->assertEquals($expected, $result);

		$result = $this->testDb->name('Model.name as y');
		$expected = '`Model`.`name` AS `y`';
		$this->assertEquals($expected, $result);
	}

/**
 * test that cacheMethod works as exepected
 *
 * @return void
 */
	public function testCacheMethod() {
		$this->testDb->cacheMethods = true;
		$result = $this->testDb->cacheMethod('name', 'some-key', 'stuff');
		$this->assertEquals($result, 'stuff');

		$result = $this->testDb->cacheMethod('name', 'some-key');
		$this->assertEquals($result, 'stuff');

		$result = $this->testDb->cacheMethod('conditions', 'some-key');
		$this->assertNull($result);

		$result = $this->testDb->cacheMethod('name', 'other-key');
		$this->assertNull($result);

		$this->testDb->cacheMethods = false;
		$result = $this->testDb->cacheMethod('name', 'some-key', 'stuff');
		$this->assertEquals($result, 'stuff');

		$result = $this->testDb->cacheMethod('name', 'some-key');
		$this->assertNull($result);
	}

/**
 * testLog method
 *
 * @outputBuffering enabled
 * @return void
 */
	public function testLog() {
		$this->testDb->logQuery('Query 1');
		$this->testDb->logQuery('Query 2');

		$log = $this->testDb->getLog(false, false);
		$result = Set::extract($log['log'], '/query');
		$expected = array('Query 1', 'Query 2');
		$this->assertEquals($expected, $result);

		$oldDebug = Configure::read('debug');
		Configure::write('debug', 2);
		ob_start();
		$this->testDb->showLog();
		$contents = ob_get_clean();

		$this->assertRegExp('/Query 1/s', $contents);
		$this->assertRegExp('/Query 2/s', $contents);

		ob_start();
		$this->testDb->showLog(true);
		$contents = ob_get_clean();

		$this->assertRegExp('/Query 1/s', $contents);
		$this->assertRegExp('/Query 2/s', $contents);

		Configure::write('debug', $oldDebug);
	}

/**
 * test getting the query log as an array.
 *
 * @return void
 */
	public function testGetLog() {
		$this->testDb->logQuery('Query 1');
		$this->testDb->logQuery('Query 2');

		$log = $this->testDb->getLog();
		$expected = array('query' => 'Query 1', 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEquals($log['log'][0], $expected);
		$expected = array('query' => 'Query 2', 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEquals($log['log'][1], $expected);
		$expected = array('query' => 'Error 1', 'affected' => '', 'numRows' => '', 'took' => '');
	}

/**
 * test that query() returns boolean values from operations like CREATE TABLE
 *
 * @return void
 */
	public function testFetchAllBooleanReturns() {
		$name = $this->db->fullTableName('test_query');
		$query = "CREATE TABLE {$name} (name varchar(10));";
		$result = $this->db->query($query);
		$this->assertTrue($result, 'Query did not return a boolean');

		$query = "DROP TABLE {$name};";
		$result = $this->db->query($query);
		$this->assertTrue($result, 'Query did not return a boolean');
	}


/**
 * test order to generate query order clause for virtual fields
 *
 * @return void
 */
	public function testVirtualFieldsInOrder() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
		);
		$order = array('two', 'this_moment');
		$result = $this->db->order($order, 'ASC', $Article);
		$expected = ' ORDER BY (1 + 1) ASC, (NOW()) ASC';
		$this->assertEquals($expected, $result);

		$order = array('Article.two', 'Article.this_moment');
		$result = $this->db->order($order, 'ASC', $Article);
		$expected = ' ORDER BY (1 + 1) ASC, (NOW()) ASC';
		$this->assertEquals($expected, $result);
	}



/**
 * test the permutations of fullTableName()
 *
 * @return void
 */
	public function testFullTablePermutations() {
		$Article = ClassRegistry::init('Article');
		$result = $this->testDb->fullTableName($Article, false, false);
		$this->assertEquals($result, 'articles');

		$Article->tablePrefix = 'tbl_';
		$result = $this->testDb->fullTableName($Article, false, false);
		$this->assertEquals($result, 'tbl_articles');

		$Article->useTable = $Article->table = 'with spaces';
		$Article->tablePrefix = '';
		$result = $this->testDb->fullTableName($Article, true, false);
		$this->assertEquals($result, '`with spaces`');
	}

/**
 * test that read() only calls queryAssociation on db objects when the method is defined.
 *
 * @return void
 */
	public function testReadOnlyCallingQueryAssociationWhenDefined() {
		$this->loadFixtures('Article', 'User', 'ArticlesTag', 'Tag');
		ConnectionManager::create('test_no_queryAssociation', array(
			'datasource' => 'MockDataSource'
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
	public function testFieldsUsingMethodCache() {
		$this->testDb->cacheMethods = false;
		DboTestSource::$methodCache = array();

		$Article = ClassRegistry::init('Article');
		$this->testDb->fields($Article, null, array('title', 'body', 'published'));
		$this->assertTrue(empty(DboTestSource::$methodCache['fields']), 'Cache not empty');
	}

/**
 * testStatements method
 *
 * @return void
 */
	public function testStatements() {
		$this->skipIf(!$this->testDb instanceof DboMysql);

		$this->loadFixtures('Article', 'User', 'Comment', 'Tag', 'Attachment', 'ArticlesTag');
		$Article = new Article();

		$result = $this->testDb->update($Article, array('field1'), array('value1'));
		$this->assertFalse($result);
		$result = $this->testDb->getLastQuery();
		$this->assertRegExp('/^\s*UPDATE\s+' . $this->testDb->fullTableName('articles') . '\s+SET\s+`field1`\s*=\s*\'value1\'\s+WHERE\s+1 = 1\s*$/', $result);

		$result = $this->testDb->update($Article, array('field1'), array('2'), '2=2');
		$this->assertFalse($result);
		$result = $this->testDb->getLastQuery();
		$this->assertRegExp('/^\s*UPDATE\s+' . $this->testDb->fullTableName('articles') . ' AS `Article`\s+LEFT JOIN\s+' . $this->testDb->fullTableName('users') . ' AS `User` ON \(`Article`.`user_id` = `User`.`id`\)\s+SET\s+`Article`\.`field1`\s*=\s*2\s+WHERE\s+2\s*=\s*2\s*$/', $result);

		$result = $this->testDb->delete($Article);
		$this->assertTrue($result);
		$result = $this->testDb->getLastQuery();
		$this->assertRegExp('/^\s*DELETE\s+FROM\s+' . $this->testDb->fullTableName('articles') . '\s+WHERE\s+1 = 1\s*$/', $result);

		$result = $this->testDb->delete($Article, true);
		$this->assertTrue($result);
		$result = $this->testDb->getLastQuery();
		$this->assertRegExp('/^\s*DELETE\s+`Article`\s+FROM\s+' . $this->testDb->fullTableName('articles') . '\s+AS `Article`\s+LEFT JOIN\s+' . $this->testDb->fullTableName('users') . ' AS `User` ON \(`Article`.`user_id` = `User`.`id`\)\s+WHERE\s+1\s*=\s*1\s*$/', $result);

		$result = $this->testDb->delete($Article, '2=2');
		$this->assertTrue($result);
		$result = $this->testDb->getLastQuery();
		$this->assertRegExp('/^\s*DELETE\s+`Article`\s+FROM\s+' . $this->testDb->fullTableName('articles') . '\s+AS `Article`\s+LEFT JOIN\s+' . $this->testDb->fullTableName('users') . ' AS `User` ON \(`Article`.`user_id` = `User`.`id`\)\s+WHERE\s+2\s*=\s*2\s*$/', $result);

		$result = $this->testDb->hasAny($Article, '1=2');
		$this->assertFalse($result);
	}

/**
 * Test that group works without a model
 *
 * @return void
 */
	function testGroupNoModel() {
		$result = $this->db->group('created');
		$this->assertEquals(' GROUP BY created', $result);
	}

/**
 * Test getting the last error.
 */
	function testLastError() {
		$stmt = $this->getMock('PDOStatement');
		$stmt->expects($this->any())
			->method('errorInfo')
			->will($this->returnValue(array('', 'something', 'bad')));

		$result = $this->db->lastError($stmt);
		$expected = 'something: bad';
		$this->assertEquals($expected, $result);
	}
}
