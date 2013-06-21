<?php
/**
 * DboSourceTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *	Licensed under The Open Group Test Suite License
 *	Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model.Datasource
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('DataSource', 'Model/Datasource');
App::uses('DboSource', 'Model/Datasource');
App::uses('DboTestSource', 'Model/Datasource');
App::uses('DboSecondTestSource', 'Model/Datasource');
App::uses('MockDataSource', 'Model/Datasource');
require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * Class MockPDO
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class MockPDO extends PDO {

	public function __construct() {
	}

}

/**
 * Class MockDataSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class MockDataSource extends DataSource {
}

/**
 * Class DboTestSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DboTestSource extends DboSource {

	public $nestedSupport = false;

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

	public function nestedTransactionSupported() {
		return $this->useNestedTransactions && $this->nestedSupport;
	}

}

/**
 * Class DboSecondTestSource
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class DboSecondTestSource extends DboSource {

	public $startQuote = '_';

	public $endQuote = '_';

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
 * tearDown method
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
		$this->assertEquals(' WHERE 1 = 1', $result, 'true conditions failed %s');

		$result = $this->testDb->conditions(false);
		$this->assertEquals(' WHERE 0 = 1', $result, 'false conditions failed %s');

		$result = $this->testDb->conditions(null);
		$this->assertEquals(' WHERE 1 = 1', $result, 'null conditions failed %s');

		$result = $this->testDb->conditions(array());
		$this->assertEquals(' WHERE 1 = 1', $result, 'array() conditions failed %s');

		$result = $this->testDb->conditions('');
		$this->assertEquals(' WHERE 1 = 1', $result, '"" conditions failed %s');

		$result = $this->testDb->conditions(' ', '"  " conditions failed %s');
		$this->assertEquals(' WHERE 1 = 1', $result);
	}

/**
 * test that booleans work on empty set.
 *
 * @return void
 */
	public function testBooleanEmptyConditionsParsing() {
		$result = $this->testDb->conditions(array('OR' => array()));
		$this->assertEquals(' WHERE  1 = 1', $result, 'empty conditions failed');

		$result = $this->testDb->conditions(array('OR' => array('OR' => array())));
		$this->assertEquals(' WHERE  1 = 1', $result, 'nested empty conditions failed');
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
		$this->assertEquals($expected, $data);

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
		$this->assertEquals($expected, $data);

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
		$this->assertEquals($expected, $data);

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
		$this->assertEquals($expected, $data);

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
		$this->assertEquals($expected, $data);

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
		$this->assertEquals($expected, $data);

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
		$this->assertEquals($expected, $data);

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
		$this->assertEquals($expected, $data);
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
		$this->db->query('directCall', array(), $this->Model);
	}

/**
 * testValue method
 *
 * @return void
 */
	public function testValue() {
		if ($this->db instanceof Sqlserver) {
			$this->markTestSkipped('Cannot run this test with SqlServer');
		}
		$result = $this->db->value('{$__cakeForeignKey__$}');
		$this->assertEquals('{$__cakeForeignKey__$}', $result);

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
		$this->assertEquals('foo', $this->testDb->config['prefix']);
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

		$result = $this->testDb->name('I18n__title__pt-br.locale');
		$expected = '`I18n__title__pt-br`.`locale`';
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
 * test that cacheMethod works as expected
 *
 * @return void
 */
	public function testCacheMethod() {
		$this->testDb->cacheMethods = true;
		$result = $this->testDb->cacheMethod('name', 'some-key', 'stuff');
		$this->assertEquals('stuff', $result);

		$result = $this->testDb->cacheMethod('name', 'some-key');
		$this->assertEquals('stuff', $result);

		$result = $this->testDb->cacheMethod('conditions', 'some-key');
		$this->assertNull($result);

		$result = $this->testDb->cacheMethod('name', 'other-key');
		$this->assertNull($result);

		$this->testDb->cacheMethods = false;
		$result = $this->testDb->cacheMethod('name', 'some-key', 'stuff');
		$this->assertEquals('stuff', $result);

		$result = $this->testDb->cacheMethod('name', 'some-key');
		$this->assertNull($result);
	}

/**
 * Test that rare collisions do not happen with method caching
 *
 * @return void
 */
	public function testNameMethodCacheCollisions() {
		$this->testDb->cacheMethods = true;
		$this->testDb->flushMethodCache();
		$this->testDb->name('Model.fieldlbqndkezcoapfgirmjsh');
		$result = $this->testDb->name('Model.fieldkhdfjmelarbqnzsogcpi');
		$expected = '`Model`.`fieldkhdfjmelarbqnzsogcpi`';
		$this->assertEquals($expected, $result);
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
		$result = Hash::extract($log['log'], '{n}.query');
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
		$expected = array('query' => 'Query 1', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');

		$this->assertEquals($expected, $log['log'][0]);
		$expected = array('query' => 'Query 2', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEquals($expected, $log['log'][1]);
		$expected = array('query' => 'Error 1', 'affected' => '', 'numRows' => '', 'took' => '');
	}

/**
 * test getting the query log as an array, setting bind params.
 *
 * @return void
 */
	public function testGetLogParams() {
		$this->testDb->logQuery('Query 1', array(1, 2, 'abc'));
		$this->testDb->logQuery('Query 2', array('field1' => 1, 'field2' => 'abc'));

		$log = $this->testDb->getLog();
		$expected = array('query' => 'Query 1', 'params' => array(1, 2, 'abc'), 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEquals($expected, $log['log'][0]);
		$expected = array('query' => 'Query 2', 'params' => array('field1' => 1, 'field2' => 'abc'), 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEquals($expected, $log['log'][1]);
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
		$this->assertEquals('articles', $result);

		$Article->tablePrefix = 'tbl_';
		$result = $this->testDb->fullTableName($Article, false, false);
		$this->assertEquals('tbl_articles', $result);

		$Article->useTable = $Article->table = 'with spaces';
		$Article->tablePrefix = '';
		$result = $this->testDb->fullTableName($Article, true, false);
		$this->assertEquals('`with spaces`', $result);

		$this->loadFixtures('Article');
		$Article->useTable = $Article->table = 'articles';
		$Article->setDataSource('test');
		$testdb = $Article->getDataSource();
		$result = $testdb->fullTableName($Article, false, true);
		$this->assertEquals($testdb->getSchemaName() . '.articles', $result);

		// tests for empty schemaName
		$noschema = ConnectionManager::create('noschema', array(
			'datasource' => 'DboTestSource'
			));
		$Article->setDataSource('noschema');
		$Article->schemaName = null;
		$result = $noschema->fullTableName($Article, false, true);
		$this->assertEquals('articles', $result);

		$this->testDb->config['prefix'] = 't_';
		$result = $this->testDb->fullTableName('post_tag', false, false);
		$this->assertEquals('t_post_tag', $result);
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
 * test that queryAssociation() reuse already joined data for 'belongsTo' and 'hasOne' associations
 * instead of running unneeded queries for each record
 *
 * @return void
 */
	public function testQueryAssociationUnneededQueries() {
		$this->loadFixtures('Article', 'User', 'Comment', 'Attachment', 'Tag', 'ArticlesTag');
		$Comment = new Comment;

		$fullDebug = $this->db->fullDebug;
		$this->db->fullDebug = true;

		$Comment->find('all', array('recursive' => 2)); // ensure Model descriptions are saved
		$this->db->getLog();

		// case: Comment  belongsTo User and Article
		$Comment->unbindModel(array(
			'hasOne' => array('Attachment')
		));
		$Comment->Article->unbindModel(array(
			'belongsTo' => array('User'),
			'hasMany' => array('Comment'),
			'hasAndBelongsToMany' => array('Tag')
		));
		$Comment->find('all', array('recursive' => 2));
		$log = $this->db->getLog();
		$this->assertEquals(1, count($log['log']));

		// case: Comment belongsTo Article, Article belongsTo User
		$Comment->unbindModel(array(
			'belongsTo' => array('User'),
			'hasOne' => array('Attachment')
		));
		$Comment->Article->unbindModel(array(
			'hasMany' => array('Comment'),
			'hasAndBelongsToMany' => array('Tag'),
		));
		$Comment->find('all', array('recursive' => 2));
		$log = $this->db->getLog();
		$this->assertEquals(7, count($log['log']));

		// case: Comment hasOne Attachment
		$Comment->unbindModel(array(
			'belongsTo' => array('Article', 'User'),
		));
		$Comment->Attachment->unbindModel(array(
			'belongsTo' => array('Comment'),
		));
		$Comment->find('all', array('recursive' => 2));
		$log = $this->db->getLog();
		$this->assertEquals(1, count($log['log']));

		$this->db->fullDebug = $fullDebug;
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
 * test that fields() method cache detects datasource changes
 *
 * @return void
 */
	public function testFieldsCacheKeyWithDatasourceChange() {
		ConnectionManager::create('firstschema', array(
			'datasource' => 'DboTestSource'
		));
		ConnectionManager::create('secondschema', array(
			'datasource' => 'DboSecondTestSource'
		));
		Cache::delete('method_cache', '_cake_core_');
		DboTestSource::$methodCache = array();
		$Article = ClassRegistry::init('Article');

		$Article->setDataSource('firstschema');
		$ds = $Article->getDataSource();
		$ds->cacheMethods = true;
		$first = $ds->fields($Article, null, array('title', 'body', 'published'));

		$Article->setDataSource('secondschema');
		$ds = $Article->getDataSource();
		$ds->cacheMethods = true;
		$second = $ds->fields($Article, null, array('title', 'body', 'published'));

		$this->assertNotEquals($first, $second);
		$this->assertEquals(2, count(DboTestSource::$methodCache['fields']));
	}

/**
 * Test that group works without a model
 *
 * @return void
 */
	public function testGroupNoModel() {
		$result = $this->db->group('created');
		$this->assertEquals(' GROUP BY created', $result);
	}

/**
 * Test getting the last error.
 */
	public function testLastError() {
		$stmt = $this->getMock('PDOStatement');
		$stmt->expects($this->any())
			->method('errorInfo')
			->will($this->returnValue(array('', 'something', 'bad')));

		$result = $this->db->lastError($stmt);
		$expected = 'something: bad';
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that transaction commands are logged
 *
 * @return void
 */
	public function testTransactionLogging() {
		$conn = $this->getMock('MockPDO');
		$db = new DboTestSource;
		$db->setConnection($conn);
		$conn->expects($this->exactly(2))->method('beginTransaction')
			->will($this->returnValue(true));
		$conn->expects($this->once())->method('commit')->will($this->returnValue(true));
		$conn->expects($this->once())->method('rollback')->will($this->returnValue(true));

		$db->begin();
		$log = $db->getLog();
		$expected = array('query' => 'BEGIN', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');
		$this->assertEquals($expected, $log['log'][0]);

		$db->commit();
		$expected = array('query' => 'COMMIT', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');
		$log = $db->getLog();
		$this->assertEquals($expected, $log['log'][0]);

		$db->begin();
		$expected = array('query' => 'BEGIN', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');
		$log = $db->getLog();
		$this->assertEquals($expected, $log['log'][0]);

		$db->rollback();
		$expected = array('query' => 'ROLLBACK', 'params' => array(), 'affected' => '', 'numRows' => '', 'took' => '');
		$log = $db->getLog();
		$this->assertEquals($expected, $log['log'][0]);
	}

/**
 * Test nested transaction calls
 *
 * @return void
 */
	public function testTransactionNested() {
		$conn = $this->getMock('MockPDO');
		$db = new DboTestSource();
		$db->setConnection($conn);
		$db->useNestedTransactions = true;
		$db->nestedSupport = true;

		$conn->expects($this->at(0))->method('beginTransaction')->will($this->returnValue(true));
		$conn->expects($this->at(1))->method('exec')->with($this->equalTo('SAVEPOINT LEVEL1'))->will($this->returnValue(true));
		$conn->expects($this->at(2))->method('exec')->with($this->equalTo('RELEASE SAVEPOINT LEVEL1'))->will($this->returnValue(true));
		$conn->expects($this->at(3))->method('exec')->with($this->equalTo('SAVEPOINT LEVEL1'))->will($this->returnValue(true));
		$conn->expects($this->at(4))->method('exec')->with($this->equalTo('ROLLBACK TO SAVEPOINT LEVEL1'))->will($this->returnValue(true));
		$conn->expects($this->at(5))->method('commit')->will($this->returnValue(true));

		$this->_runTransactions($db);
	}

/**
 * Test nested transaction calls without support
 *
 * @return void
 */
	public function testTransactionNestedWithoutSupport() {
		$conn = $this->getMock('MockPDO');
		$db = new DboTestSource();
		$db->setConnection($conn);
		$db->useNestedTransactions = true;
		$db->nestedSupport = false;

		$conn->expects($this->once())->method('beginTransaction')->will($this->returnValue(true));
		$conn->expects($this->never())->method('exec');
		$conn->expects($this->once())->method('commit')->will($this->returnValue(true));

		$this->_runTransactions($db);
	}

/**
 * Test nested transaction disabled
 *
 * @return void
 */
	public function testTransactionNestedDisabled() {
		$conn = $this->getMock('MockPDO');
		$db = new DboTestSource();
		$db->setConnection($conn);
		$db->useNestedTransactions = false;
		$db->nestedSupport = true;

		$conn->expects($this->once())->method('beginTransaction')->will($this->returnValue(true));
		$conn->expects($this->never())->method('exec');
		$conn->expects($this->once())->method('commit')->will($this->returnValue(true));

		$this->_runTransactions($db);
	}

/**
 * Nested transaction calls
 *
 * @param DboTestSource $db
 * @return void
 */
	protected function _runTransactions($db) {
		$db->begin();
		$db->begin();
		$db->commit();
		$db->begin();
		$db->rollback();
		$db->commit();
	}

/**
 * Test build statement with some fields missing
 *
 * @return void
 */
	public function testBuildStatementDefaults() {
		$conn = $this->getMock('MockPDO', array('quote'));
		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('foo bar'));
		$db = new DboTestSource;
		$db->setConnection($conn);
		$subQuery = $db->buildStatement(
			array(
				'fields' => array('DISTINCT(AssetsTag.asset_id)'),
				'table' => "assets_tags",
				'alias' => "AssetsTag",
				'conditions' => array("Tag.name" => 'foo bar'),
				'limit' => null,
				'group' => "AssetsTag.asset_id"
			),
			$this->Model
		);
		$expected = 'SELECT DISTINCT(AssetsTag.asset_id) FROM assets_tags AS AssetsTag   WHERE Tag.name = foo bar  GROUP BY AssetsTag.asset_id  ';
		$this->assertEquals($expected, $subQuery);
	}

/**
 * data provider for testBuildJoinStatement
 *
 * @return array
 */
	public static function joinStatements($schema) {
		return array(
			array(array(
				'type' => 'LEFT',
				'alias' => 'PostsTag',
				'table' => 'posts_tags',
				'conditions' => array('PostsTag.post_id = Post.id')
			), 'LEFT JOIN cakephp.posts_tags AS PostsTag ON (PostsTag.post_id = Post.id)'),
			array(array(
				'type' => 'LEFT',
				'alias' => 'Stock',
				'table' => '(SELECT Stock.article_id, sum(quantite) quantite FROM stocks AS Stock GROUP BY Stock.article_id)',
				'conditions' => 'Stock.article_id = Article.id'
			), 'LEFT JOIN (SELECT Stock.article_id, sum(quantite) quantite FROM stocks AS Stock GROUP BY Stock.article_id) AS Stock ON (Stock.article_id = Article.id)')
		);
	}

/**
 * Test buildJoinStatement()
 * ensure that schemaName is not added when table value is a subquery
 *
 * @dataProvider joinStatements
 * @return void
 */
	public function testBuildJoinStatement($join, $expected) {
		$db = $this->getMock('DboTestSource', array('getSchemaName'));
		$db->expects($this->any())
			->method('getSchemaName')
			->will($this->returnValue('cakephp'));
		$result = $db->buildJoinStatement($join);
		$this->assertEquals($expected, $result);
	}

/**
 * data provider for testBuildJoinStatementWithTablePrefix
 *
 * @return array
 */
	public static function joinStatementsWithPrefix($schema) {
		return array(
			array(array(
				'type' => 'LEFT',
				'alias' => 'PostsTag',
				'table' => 'posts_tags',
				'conditions' => array('PostsTag.post_id = Post.id')
			), 'LEFT JOIN pre_posts_tags AS PostsTag ON (PostsTag.post_id = Post.id)'),
				array(array(
					'type' => 'LEFT',
					'alias' => 'Stock',
					'table' => '(SELECT Stock.article_id, sum(quantite) quantite FROM stocks AS Stock GROUP BY Stock.article_id)',
					'conditions' => 'Stock.article_id = Article.id'
				), 'LEFT JOIN (SELECT Stock.article_id, sum(quantite) quantite FROM stocks AS Stock GROUP BY Stock.article_id) AS Stock ON (Stock.article_id = Article.id)')
			);
	}

/**
 * Test buildJoinStatement()
 * ensure that prefix is not added when table value is a subquery
 *
 * @dataProvider joinStatementsWithPrefix
 * @return void
 */
	public function testBuildJoinStatementWithTablePrefix($join, $expected) {
		$db = new DboTestSource;
		$db->config['prefix'] = 'pre_';
		$result = $db->buildJoinStatement($join);
		$this->assertEquals($expected, $result);
	}

/**
 * Test conditionKeysToString()
 *
 * @return void
 */
	public function testConditionKeysToString() {
		$Article = ClassRegistry::init('Article');
		$conn = $this->getMock('MockPDO', array('quote'));
		$db = new DboTestSource;
		$db->setConnection($conn);

		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('just text'));

		$conditions = array('Article.name' => 'just text');
		$result = $db->conditionKeysToString($conditions, true, $Article);
		$expected = "Article.name = just text";
		$this->assertEquals($expected, $result[0]);

		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('just text'));
		$conn->expects($this->at(1))
			->method('quote')
			->will($this->returnValue('other text'));

		$conditions = array('Article.name' => array('just text', 'other text'));
		$result = $db->conditionKeysToString($conditions, true, $Article);
		$expected = "Article.name IN (just text, other text)";
		$this->assertEquals($expected, $result[0]);
	}

/**
 * Test conditionKeysToString() with virtual field
 *
 * @return void
 */
	public function testConditionKeysToStringVirtualField() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'extra' => 'something virtual'
		);
		$conn = $this->getMock('MockPDO', array('quote'));
		$db = new DboTestSource;
		$db->setConnection($conn);

		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('just text'));

		$conditions = array('Article.extra' => 'just text');
		$result = $db->conditionKeysToString($conditions, true, $Article);
		$expected = "(" . $Article->virtualFields['extra'] . ") = just text";
		$this->assertEquals($expected, $result[0]);

		$conn->expects($this->at(0))
			->method('quote')
			->will($this->returnValue('just text'));
		$conn->expects($this->at(1))
			->method('quote')
			->will($this->returnValue('other text'));

		$conditions = array('Article.extra' => array('just text', 'other text'));
		$result = $db->conditionKeysToString($conditions, true, $Article);
		$expected = "(" . $Article->virtualFields['extra'] . ") IN (just text, other text)";
		$this->assertEquals($expected, $result[0]);
	}

/**
 * Test the limit function.
 *
 * @return void
 */
	public function testLimit() {
		$db = new DboTestSource;

		$result = $db->limit('0');
		$this->assertNull($result);

		$result = $db->limit('10');
		$this->assertEquals(' LIMIT 10', $result);

		$result = $db->limit('FARTS', 'BOOGERS');
		$this->assertEquals(' LIMIT 0, 0', $result);

		$result = $db->limit(20, 10);
		$this->assertEquals(' LIMIT 10, 20', $result);

		$result = $db->limit(10, 300000000000000000000000000000);
		$scientificNotation = sprintf('%.1E', 300000000000000000000000000000);
		$this->assertNotContains($scientificNotation, $result);
	}

}
