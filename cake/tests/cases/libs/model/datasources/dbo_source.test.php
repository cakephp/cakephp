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
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
App::import('Model', array('Model', 'DataSource', 'DboSource', 'DboMysql', 'App'));
require_once dirname(dirname(__FILE__)) . DS . 'models.php';

/**
 * DboSourceTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
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
 * testRenderStatement method
 *
 * @access public
 * @return void
 */
	function testRenderStatement() {
		$result = $this->testDb->renderStatement('select', array(
			'fields' => 'id', 'table' => 'table', 'conditions' => 'WHERE 1=1',
			'alias' => '', 'joins' => '', 'order' => '', 'limit' => '', 'group' => ''
		));
		$this->assertPattern('/^\s*SELECT\s+id\s+FROM\s+table\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->testDb->renderStatement('update', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => ''));
		$this->assertPattern('/^\s*UPDATE\s+table\s+SET\s+value=2\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->testDb->renderStatement('update', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => 'alias', 'joins' => ''));
		$this->assertPattern('/^\s*UPDATE\s+table\s+AS\s+alias\s+SET\s+value=2\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->testDb->renderStatement('delete', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => ''));
		$this->assertPattern('/^\s*DELETE\s+FROM\s+table\s+WHERE\s+1=1\s*$/', $result);

		$result = $this->testDb->renderStatement('delete', array('fields' => 'value=2', 'table' => 'table', 'conditions' => 'WHERE 1=1', 'alias' => 'alias', 'joins' => ''));
		$this->assertPattern('/^\s*DELETE\s+alias\s+FROM\s+table\s+AS\s+alias\s+WHERE\s+1=1\s*$/', $result);
	}

/**
 * testStatements method
 *
 * @access public
 * @return void
 */
	function testStatements() {
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

		$result = $this->testDb->insertMulti('articles', array('field'), array('(1)', '(2)'));
		$this->assertNull($result);
		$result = $this->testDb->getLastQuery();
		$this->assertPattern('/^\s*INSERT INTO\s+' . $this->testDb->fullTableName('articles') . '\s+\(`field`\)\s+VALUES\s+\(1\),\s*\(2\)\s*$/', $result);
	}

/**
 * testSchema method
 *
 * @access public
 * @return void
 */
	function testSchema() {
		$Schema = new CakeSchema();
		$Schema->tables = array('table' => array(), 'anotherTable' => array());

		$this->expectError();
		$result = $this->testDb->dropSchema(null);
		$this->assertTrue($result === null);

		$result = $this->testDb->dropSchema($Schema, 'non_existing');
		$this->assertTrue(empty($result));

		$result = $this->testDb->dropSchema($Schema, 'table');
		$this->assertPattern('/^\s*DROP TABLE IF EXISTS\s+' . $this->testDb->fullTableName('table') . ';\s*$/s', $result);
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
 * testOrderParsing method
 *
 * @access public
 * @return void
 */
	function testOrderParsing() {
		$result = $this->testDb->order("ADDTIME(Event.time_begin, '-06:00:00') ASC");
		$expected = " ORDER BY ADDTIME(`Event`.`time_begin`, '-06:00:00') ASC";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->order("title, id");
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->testDb->order("title desc, id desc");
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+desc,\s+`id`\s+desc\s*$/', $result);

		$result = $this->testDb->order(array("title desc, id desc"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+desc,\s+`id`\s+desc\s*$/', $result);

		$result = $this->testDb->order(array("title", "id"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->testDb->order(array(array('title'), array('id')));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC,\s+`id`\s+ASC\s*$/', $result);

		$result = $this->testDb->order(array("Post.title" => 'asc', "Post.id" => 'desc'));
		$this->assertPattern('/^\s*ORDER BY\s+`Post`.`title`\s+asc,\s+`Post`.`id`\s+desc\s*$/', $result);

		$result = $this->testDb->order(array(array("Post.title" => 'asc', "Post.id" => 'desc')));
		$this->assertPattern('/^\s*ORDER BY\s+`Post`.`title`\s+asc,\s+`Post`.`id`\s+desc\s*$/', $result);

		$result = $this->testDb->order(array("title"));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC\s*$/', $result);

		$result = $this->testDb->order(array(array("title")));
		$this->assertPattern('/^\s*ORDER BY\s+`title`\s+ASC\s*$/', $result);

		$result = $this->testDb->order("Dealer.id = 7 desc, Dealer.id = 3 desc, Dealer.title asc");
		$expected = " ORDER BY `Dealer`.`id` = 7 desc, `Dealer`.`id` = 3 desc, `Dealer`.`title` asc";
		$this->assertEqual($result, $expected);

		$result = $this->testDb->order(array("Page.name" => "='test' DESC"));
		$this->assertPattern("/^\s*ORDER BY\s+`Page`\.`name`\s*='test'\s+DESC\s*$/", $result);

		$result = $this->testDb->order("Page.name = 'view' DESC");
		$this->assertPattern("/^\s*ORDER BY\s+`Page`\.`name`\s*=\s*'view'\s+DESC\s*$/", $result);

		$result = $this->testDb->order("(Post.views)");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Post`\.`views`\)\s+ASC\s*$/", $result);

		$result = $this->testDb->order("(Post.views)*Post.views");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Post`\.`views`\)\*`Post`\.`views`\s+ASC\s*$/", $result);

		$result = $this->testDb->order("(Post.views) * Post.views");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Post`\.`views`\) \* `Post`\.`views`\s+ASC\s*$/", $result);

		$result = $this->testDb->order("(Model.field1 + Model.field2) * Model.field3");
		$this->assertPattern("/^\s*ORDER BY\s+\(`Model`\.`field1` \+ `Model`\.`field2`\) \* `Model`\.`field3`\s+ASC\s*$/", $result);

		$result = $this->testDb->order("Model.name+0 ASC");
		$this->assertPattern("/^\s*ORDER BY\s+`Model`\.`name`\+0\s+ASC\s*$/", $result);

		$result = $this->testDb->order("Anuncio.destaque & 2 DESC");
		$expected = ' ORDER BY `Anuncio`.`destaque` & 2 DESC';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->order("3963.191 * id");
		$expected = ' ORDER BY 3963.191 * id ASC';
		$this->assertEqual($result, $expected);

		$result = $this->testDb->order(array('Property.sale_price IS NULL'));
		$expected = ' ORDER BY `Property`.`sale_price` IS NULL ASC';
		$this->assertEqual($result, $expected);
	}

/**
 * testComplexSortExpression method
 *
 * @return void
 */
	public function testComplexSortExpression() {
		$result = $this->testDb->order(array('(Model.field > 100) DESC', 'Model.field ASC'));
		$this->assertPattern("/^\s*ORDER BY\s+\(`Model`\.`field`\s+>\s+100\)\s+DESC,\s+`Model`\.`field`\s+ASC\s*$/", $result);
	}

/**
 * testCalculations method
 *
 * @access public
 * @return void
 */
	function testCalculations() {
		$result = $this->testDb->calculate($this->Model, 'count');
		$this->assertEqual($result, 'COUNT(*) AS `count`');

		$result = $this->testDb->calculate($this->Model, 'count', array('id'));
		$this->assertEqual($result, 'COUNT(`id`) AS `count`');

		$result = $this->testDb->calculate(
			$this->Model,
			'count',
			array($this->testDb->expression('DISTINCT id'))
		);
		$this->assertEqual($result, 'COUNT(DISTINCT id) AS `count`');

		$result = $this->testDb->calculate($this->Model, 'count', array('id', 'id_count'));
		$this->assertEqual($result, 'COUNT(`id`) AS `id_count`');

		$result = $this->testDb->calculate($this->Model, 'count', array('Model.id', 'id_count'));
		$this->assertEqual($result, 'COUNT(`Model`.`id`) AS `id_count`');

		$result = $this->testDb->calculate($this->Model, 'max', array('id'));
		$this->assertEqual($result, 'MAX(`id`) AS `id`');

		$result = $this->testDb->calculate($this->Model, 'max', array('Model.id', 'id'));
		$this->assertEqual($result, 'MAX(`Model`.`id`) AS `id`');

		$result = $this->testDb->calculate($this->Model, 'max', array('`Model`.`id`', 'id'));
		$this->assertEqual($result, 'MAX(`Model`.`id`) AS `id`');

		$result = $this->testDb->calculate($this->Model, 'min', array('`Model`.`id`', 'id'));
		$this->assertEqual($result, 'MIN(`Model`.`id`) AS `id`');

		$result = $this->testDb->calculate($this->Model, 'min', 'left');
		$this->assertEqual($result, 'MIN(`left`) AS `left`');
	}

/**
 * testLength method
 *
 * @access public
 * @return void
 */
	function testLength() {
		$result = $this->testDb->length('varchar(255)');
		$expected = 255;
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length('int(11)');
		$expected = 11;
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length('float(5,3)');
		$expected = '5,3';
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length('decimal(5,2)');
		$expected = '5,2';
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length("enum('test','me','now')");
		$expected = 4;
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length("set('a','b','cd')");
		$expected = 2;
		$this->assertIdentical($result, $expected);

		$this->expectError();
		$result = $this->testDb->length(false);
		$this->assertTrue($result === null);

		$result = $this->testDb->length('datetime');
		$expected = null;
		$this->assertIdentical($result, $expected);

		$result = $this->testDb->length('text');
		$expected = null;
		$this->assertIdentical($result, $expected);
	}

/**
 * testBuildIndex method
 *
 * @access public
 * @return void
 */
	function testBuildIndex() {
		$data = array(
			'PRIMARY' => array('column' => 'id')
		);
		$result = $this->testDb->buildIndex($data);
		$expected = array('PRIMARY KEY  (`id`)');
		$this->assertIdentical($result, $expected);

		$data = array(
			'MyIndex' => array('column' => 'id', 'unique' => true)
		);
		$result = $this->testDb->buildIndex($data);
		$expected = array('UNIQUE KEY `MyIndex` (`id`)');
		$this->assertEqual($result, $expected);

		$data = array(
			'MyIndex' => array('column' => array('id', 'name'), 'unique' => true)
		);
		$result = $this->testDb->buildIndex($data);
		$expected = array('UNIQUE KEY `MyIndex` (`id`, `name`)');
		$this->assertEqual($result, $expected);
	}

/**
 * testBuildColumn method
 *
 * @access public
 * @return void
 */
	function testBuildColumn() {
		$this->expectError();
		$data = array(
			'name' => 'testName',
			'type' => 'varchar(255)',
			'default',
			'null' => true,
			'key'
		);
		$this->testDb->buildColumn($data);

		$data = array(
			'name' => 'testName',
			'type' => 'string',
			'length' => 255,
			'default',
			'null' => true,
			'key'
		);
		$result = $this->testDb->buildColumn($data);
		$expected = '`testName` varchar(255) DEFAULT NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'default' => '',
			'null' => false,
		);
		$restore = $this->testDb->columns;

		$this->testDb->columns = array('integer' => array('name' => 'int', 'limit' => '11', 'formatter' => 'intval'), );
		$result = $this->testDb->buildColumn($data);
		$expected = '`int_field` int(11) NOT NULL';
		$this->assertEqual($result, $expected);

		$this->testDb->fieldParameters['param'] = array(
			'value' => 'COLLATE',
			'quote' => false,
			'join' => ' ',
			'column' => 'Collate',
			'position' => 'beforeDefault',
			'options' => array('GOOD', 'OK')
		);
		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'default' => '',
			'null' => false,
			'param' => 'BAD'
		);
		$result = $this->testDb->buildColumn($data);
		$expected = '`int_field` int(11) NOT NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'int_field',
			'type' => 'integer',
			'default' => '',
			'null' => false,
			'param' => 'GOOD'
		);
		$result = $this->testDb->buildColumn($data);
		$expected = '`int_field` int(11) COLLATE GOOD NOT NULL';
		$this->assertEqual($result, $expected);

		$this->testDb->columns = $restore;

		$data = array(
			'name' => 'created',
			'type' => 'timestamp',
			'default' => 'current_timestamp',
			'null' => false,
 		);
		$result = $this->db->buildColumn($data);
		$expected = '`created` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'created',
			'type' => 'timestamp',
			'default' => 'CURRENT_TIMESTAMP',
			'null' => true,
		);
		$result = $this->db->buildColumn($data);
		$expected = '`created` timestamp DEFAULT CURRENT_TIMESTAMP';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'modified',
			'type' => 'timestamp',
			'null' => true,
		);
		$result = $this->db->buildColumn($data);
		$expected = '`modified` timestamp NULL';
		$this->assertEqual($result, $expected);

		$data = array(
			'name' => 'modified',
			'type' => 'timestamp',
			'default' => null,
			'null' => true,
		);
		$result = $this->db->buildColumn($data);
		$expected = '`modified` timestamp NULL';
		$this->assertEqual($result, $expected);
	}

/**
 * test hasAny()
 *
 * @return void
 */
	function testHasAny() {
		$this->testDb->hasAny($this->Model, array());
		$expected = 'SELECT COUNT(`TestModel`.`id`) AS count FROM `test_models` AS `TestModel` WHERE 1 = 1';
		$this->assertEqual(end($this->testDb->simulated), $expected);

		$this->testDb->hasAny($this->Model, array('TestModel.name' => 'harry'));
		$expected = "SELECT COUNT(`TestModel`.`id`) AS count FROM `test_models` AS `TestModel` WHERE `TestModel`.`name` = 'harry'";
		$this->assertEqual(end($this->testDb->simulated), $expected);
	}

/**
 * testIntrospectType method
 *
 * @access public
 * @return void
 */
	function testIntrospectType() {
		$this->assertEqual($this->testDb->introspectType(0), 'integer');
		$this->assertEqual($this->testDb->introspectType(2), 'integer');
		$this->assertEqual($this->testDb->introspectType('2'), 'string');
		$this->assertEqual($this->testDb->introspectType('2.2'), 'string');
		$this->assertEqual($this->testDb->introspectType(2.2), 'float');
		$this->assertEqual($this->testDb->introspectType('stringme'), 'string');
		$this->assertEqual($this->testDb->introspectType('0stringme'), 'string');

		$data = array(2.2);
		$this->assertEqual($this->testDb->introspectType($data), 'float');

		$data = array('2.2');
		$this->assertEqual($this->testDb->introspectType($data), 'float');

		$data = array(2);
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array('2');
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array('string');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array(2.2, '2.2');
		$this->assertEqual($this->testDb->introspectType($data), 'float');

		$data = array(2, '2');
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array('string one', 'string two');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array('2.2', 3);
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array('2.2', '0stringme');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array(2.2, 3);
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array(2.2, '0stringme');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array(2, 'stringme');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array(2, '2.2', 'stringgme');
		$this->assertEqual($this->testDb->introspectType($data), 'string');

		$data = array(2, '2.2');
		$this->assertEqual($this->testDb->introspectType($data), 'integer');

		$data = array(2, 2.2);
		$this->assertEqual($this->testDb->introspectType($data), 'integer');


		// NULL
		$result = $this->testDb->value(null, 'boolean');
		$this->assertEqual($result, 'NULL');

		// EMPTY STRING
		$result = $this->testDb->value('', 'boolean');
		$this->assertEqual($result, 0);


		// BOOLEAN
		$result = $this->testDb->value('true', 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value('false', 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value(true, 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value(false, 'boolean');
		$this->assertEqual($result, 0);

		$result = $this->testDb->value(1, 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value(0, 'boolean');
		$this->assertEqual($result, 0);

		$result = $this->testDb->value('abc', 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value(1.234, 'boolean');
		$this->assertEqual($result, 1);

		$result = $this->testDb->value('1.234e05', 'boolean');
		$this->assertEqual($result, 1);

		// NUMBERS
		$result = $this->testDb->value(123, 'integer');
		$this->assertEqual($result, 123);

		$result = $this->testDb->value('123', 'integer');
		$this->assertEqual($result, '123');

		$result = $this->testDb->value('0123', 'integer');
		$this->assertEqual($result, "'0123'");

		$result = $this->testDb->value('0x123ABC', 'integer');
		$this->assertEqual($result, "'0x123ABC'");

		$result = $this->testDb->value('0x123', 'integer');
		$this->assertEqual($result, "'0x123'");

		$result = $this->testDb->value(1.234, 'float');
		$this->assertEqual($result, 1.234);

		$result = $this->testDb->value('1.234', 'float');
		$this->assertEqual($result, '1.234');

		$result = $this->testDb->value(' 1.234 ', 'float');
		$this->assertEqual($result, "' 1.234 '");

		$result = $this->testDb->value('1.234e05', 'float');
		$this->assertEqual($result, "'1.234e05'");

		$result = $this->testDb->value('1.234e+5', 'float');
		$this->assertEqual($result, "'1.234e+5'");

		$result = $this->testDb->value('1,234', 'float');
		$this->assertEqual($result, "'1,234'");

		$result = $this->testDb->value('FFF', 'integer');
		$this->assertEqual($result, "'FFF'");

		$result = $this->testDb->value('abc', 'integer');
		$this->assertEqual($result, "'abc'");

		// STRINGS
		$result = $this->testDb->value('123', 'string');
		$this->assertEqual($result, "'123'");

		$result = $this->testDb->value(123, 'string');
		$this->assertEqual($result, "'123'");

		$result = $this->testDb->value(1.234, 'string');
		$this->assertEqual($result, "'1.234'");

		$result = $this->testDb->value('abc', 'string');
		$this->assertEqual($result, "'abc'");

		$result = $this->testDb->value(' abc ', 'string');
		$this->assertEqual($result, "' abc '");

		$result = $this->testDb->value('a bc', 'string');
		$this->assertEqual($result, "'a bc'");
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
 * testRealQueries method
 *
 * @access public
 * @return void
 */
	function testRealQueries() {
		$this->loadFixtures('Apple', 'Article', 'User', 'Comment', 'Tag', 'Sample');

		$Apple = ClassRegistry::init('Apple');
		$Article = ClassRegistry::init('Article');

		$result = $this->db->rawQuery('SELECT color, name FROM ' . $this->db->fullTableName('apples'));
		$this->assertTrue(!empty($result));

		$result = $this->db->fetchRow($result);
		$expected = array($this->db->fullTableName('apples', false) => array(
			'color' => 'Red 1',
			'name' => 'Red Apple 1'
		));
		$this->assertEqual($result, $expected);

		$result = $this->db->fetchAll('SELECT name FROM ' . $this->testDb->fullTableName('apples') . ' ORDER BY id');
		$expected = array(
			array($this->db->fullTableName('apples', false) => array('name' => 'Red Apple 1')),
			array($this->db->fullTableName('apples', false) => array('name' => 'Bright Red Apple')),
			array($this->db->fullTableName('apples', false) => array('name' => 'green blue')),
			array($this->db->fullTableName('apples', false) => array('name' => 'Test Name')),
			array($this->db->fullTableName('apples', false) => array('name' => 'Blue Green')),
			array($this->db->fullTableName('apples', false) => array('name' => 'My new apple')),
			array($this->db->fullTableName('apples', false) => array('name' => 'Some odd color'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->db->field($this->testDb->fullTableName('apples', false), 'SELECT color, name FROM ' . $this->testDb->fullTableName('apples') . ' ORDER BY id');
		$expected = array(
			'color' => 'Red 1',
			'name' => 'Red Apple 1'
		);
		$this->assertEqual($result, $expected);

		$Apple->unbindModel(array(), false);
		$result = $this->db->read($Apple, array(
			'fields' => array($Apple->escapeField('name')),
			'conditions' => null,
			'recursive' => -1
		));
		$expected = array(
			array('Apple' => array('name' => 'Red Apple 1')),
			array('Apple' => array('name' => 'Bright Red Apple')),
			array('Apple' => array('name' => 'green blue')),
			array('Apple' => array('name' => 'Test Name')),
			array('Apple' => array('name' => 'Blue Green')),
			array('Apple' => array('name' => 'My new apple')),
			array('Apple' => array('name' => 'Some odd color'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->db->read($Article, array(
			'fields' => array('id', 'user_id', 'title'),
			'conditions' => null,
			'recursive' => 1
		));

		$this->assertTrue(Set::matches('/Article[id=1]', $result));
		$this->assertTrue(Set::matches('/Comment[id=1]', $result));
		$this->assertTrue(Set::matches('/Comment[id=2]', $result));
		$this->assertFalse(Set::matches('/Comment[id=10]', $result));
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
 * test that execute runs queries.
 *
 * @return void
 */
	function testExecute() {
		$query = 'SELECT * FROM ' . $this->testDb->fullTableName('articles') . ' WHERE 1 = 1';
		$this->db->took = null;
		$this->db->affected = null;
		$result = $this->db->execute($query, array('stats' => false));
		$this->assertNotNull($result, 'No query performed! %s');
		$this->assertNull($this->db->took, 'Stats were set %s');
		$this->assertNull($this->db->affected, 'Stats were set %s');

		$result = $this->db->execute($query);
		$this->assertNotNull($result, 'No query performed! %s');
		$this->assertNotNull($this->db->took, 'Stats were not set %s');
		$this->assertNotNull($this->db->affected, 'Stats were not set %s');
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
 * test fields generating usable virtual fields to use in query
 *
 * @return void
 */
	function testVirtualFields() {
		$this->loadFixtures('Article');

		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
			'comment_count' => 'SELECT COUNT(*) FROM ' . $this->db->fullTableName('comments') .
				' WHERE Article.id = ' . $this->db->fullTableName('comments') . '.article_id'
		);
		$result = $this->db->fields($Article);
		$expected = array(
			'`Article`.`id`',
			'`Article`.`user_id`',
			'`Article`.`title`',
			'`Article`.`body`',
			'`Article`.`published`',
			'`Article`.`created`',
			'`Article`.`updated`',
			'(NOW()) AS  `Article__this_moment`',
			'(1 + 1) AS  `Article__two`',
			'(SELECT COUNT(*) FROM comments WHERE `Article`.`id` = `comments`.`article_id`) AS  `Article__comment_count`'
		);
		$this->assertEqual($expected, $result);

		$result = $this->db->fields($Article, null, array('this_moment', 'title'));
		$expected = array(
			'`Article`.`title`',
			'(NOW()) AS  `Article__this_moment`',
		);
		$this->assertEqual($expected, $result);

		$result = $this->db->fields($Article, null, array('Article.title', 'Article.this_moment'));
		$expected = array(
			'`Article`.`title`',
			'(NOW()) AS  `Article__this_moment`',
		);
		$this->assertEqual($expected, $result);

		$result = $this->db->fields($Article, null, array('Article.this_moment', 'Article.title'));
		$expected = array(
			'`Article`.`title`',
			'(NOW()) AS  `Article__this_moment`',
		);
		$this->assertEqual($expected, $result);

		$result = $this->db->fields($Article, null, array('Article.*'));
		$expected = array(
			'`Article`.*',
			'(NOW()) AS  `Article__this_moment`',
			'(1 + 1) AS  `Article__two`',
			'(SELECT COUNT(*) FROM comments WHERE `Article`.`id` = `comments`.`article_id`) AS  `Article__comment_count`'
		);
		$this->assertEqual($expected, $result);

		$result = $this->db->fields($Article, null, array('*'));
		$expected = array(
			'*',
			'(NOW()) AS  `Article__this_moment`',
			'(1 + 1) AS  `Article__two`',
			'(SELECT COUNT(*) FROM comments WHERE `Article`.`id` = `comments`.`article_id`) AS  `Article__comment_count`'
		);
		$this->assertEqual($expected, $result);
	}

/**
 * test conditions to generate query conditions for virtual fields
 *
 * @return void
 */
	function testVirtualFieldsInConditions() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
			'comment_count' => 'SELECT COUNT(*) FROM ' . $this->db->fullTableName('comments') .
				' WHERE Article.id = ' . $this->db->fullTableName('comments') . '.article_id'
		);
		$conditions = array('two' => 2);
		$result = $this->db->conditions($conditions, true, false, $Article);
		$expected = '(1 + 1) = 2';
		$this->assertEqual($expected, $result);

		$conditions = array('this_moment BETWEEN ? AND ?' => array(1,2));
		$expected = 'NOW() BETWEEN 1 AND 2';
		$result = $this->db->conditions($conditions, true, false, $Article);
		$this->assertEqual($expected, $result);

		$conditions = array('comment_count >' => 5);
		$expected = '(SELECT COUNT(*) FROM comments WHERE `Article`.`id` = `comments`.`article_id`) > 5';
		$result = $this->db->conditions($conditions, true, false, $Article);
		$this->assertEqual($expected, $result);

		$conditions = array('NOT' => array('two' => 2));
		$result = $this->db->conditions($conditions, true, false, $Article);
		$expected = 'NOT ((1 + 1) = 2)';
		$this->assertEqual($expected, $result);
	}

/**
 * test that virtualFields with complex functions and aliases work.
 *
 * @return void
 */
	function testConditionsWithComplexVirtualFields() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'distance' => 'ACOS(SIN(20 * PI() / 180)
					* SIN(Article.latitude * PI() / 180)
					+ COS(20 * PI() / 180)
					* COS(Article.latitude * PI() / 180)
					* COS((50 - Article.longitude) * PI() / 180)
				) * 180 / PI() * 60 * 1.1515 * 1.609344'
		);
		$conditions = array('distance >=' => 20);
		$result = $this->db->conditions($conditions, true, true, $Article);

		$this->assertPattern('/\) >= 20/', $result);
		$this->assertPattern('/[`\'"]Article[`\'"].[`\'"]latitude[`\'"]/', $result);
		$this->assertPattern('/[`\'"]Article[`\'"].[`\'"]longitude[`\'"]/', $result);
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
 * test calculate to generate claculate statements on virtual fields
 *
 * @return void
 */
	function testVirtualFieldsInCalculate() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_moment' => 'NOW()',
			'two' => '1 + 1',
			'comment_count' => 'SELECT COUNT(*) FROM ' . $this->db->fullTableName('comments') .
				' WHERE Article.id = ' . $this->db->fullTableName('comments'). '.article_id'
		);

		$result = $this->db->calculate($Article, 'count', array('this_moment'));
		$expected = 'COUNT(NOW()) AS `count`';
		$this->assertEqual($expected, $result);

		$result = $this->db->calculate($Article, 'max', array('comment_count'));
		$expected = 'MAX(SELECT COUNT(*) FROM comments WHERE `Article`.`id` = `comments`.`article_id`) AS `comment_count`';
		$this->assertEqual($expected, $result);
	}

/**
 * test a full example of using virtual fields
 *
 * @return void
 */
	function testVirtualFieldsFetch() {
		$this->loadFixtures('Article', 'Comment');

		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'comment_count' => 'SELECT COUNT(*) FROM ' . $this->db->fullTableName('comments') .
				' WHERE Article.id = ' . $this->db->fullTableName('comments') . '.article_id'
		);

		$conditions = array('comment_count >' => 2);
		$query = 'SELECT ' . join(',',$this->db->fields($Article, null, array('id', 'comment_count'))) .
				' FROM ' .  $this->db->fullTableName($Article) . ' Article ' . $this->db->conditions($conditions, true, true, $Article);
		$result = $this->db->fetchAll($query);
		$expected = array(array(
			'Article' => array('id' => 1, 'comment_count' => 4)
		));
		$this->assertEqual($expected, $result);
	}

/**
 * test reading complex virtualFields with subqueries.
 *
 * @return void
 */
	function testVirtualFieldsComplexRead() {
		$this->loadFixtures('DataTest', 'Article', 'Comment');
		
		$Article = ClassRegistry::init('Article');
		$commentTable = $this->db->fullTableName('comments');
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'comment_count' => 'SELECT COUNT(*) FROM ' . $commentTable . 
				' AS Comment WHERE Article.id = Comment.article_id'
		);
		$result = $Article->find('all');
		$this->assertTrue(count($result) > 0);
		$this->assertTrue($result[0]['Article']['comment_count'] > 0);

		$DataTest = ClassRegistry::init('DataTest');
		$DataTest->virtualFields = array(
			'complicated' => 'ACOS(SIN(20 * PI() / 180)
				* SIN(DataTest.float * PI() / 180)
				+ COS(20 * PI() / 180)
				* COS(DataTest.count * PI() / 180)
				* COS((50 - DataTest.float) * PI() / 180)
				) * 180 / PI() * 60 * 1.1515 * 1.609344'
		);
		$result = $DataTest->find('all');
		$this->assertTrue(count($result) > 0);
		$this->assertTrue($result[0]['DataTest']['complicated'] > 0);
	}

/**
 * test that virtualFields with complex functions and aliases work.
 *
 * @return void
 */
	function testFieldsWithComplexVirtualFields() {
		$Article = new Article();
		$Article->virtualFields = array(
			'distance' => 'ACOS(SIN(20 * PI() / 180)
					* SIN(Article.latitude * PI() / 180)
					+ COS(20 * PI() / 180)
					* COS(Article.latitude * PI() / 180)
					* COS((50 - Article.longitude) * PI() / 180)
				) * 180 / PI() * 60 * 1.1515 * 1.609344'
		);

		$fields = array('id', 'distance');
		$result = $this->db->fields($Article, null, $fields);
		$qs = $this->db->startQuote;
		$qe = $this->db->endQuote;

		$this->assertEqual($result[0], "{$qs}Article{$qe}.{$qs}id{$qe}");
		$this->assertPattern('/Article__distance/', $result[1]);
		$this->assertPattern('/[`\'"]Article[`\'"].[`\'"]latitude[`\'"]/', $result[1]);
		$this->assertPattern('/[`\'"]Article[`\'"].[`\'"]longitude[`\'"]/', $result[1]);
	}

/**
 * test reading virtual fields containing newlines when recursive > 0
 *
 * @return void
 */
	function testReadVirtualFieldsWithNewLines() {
		$Article = new Article();
		$Article->recursive = 1;
		$Article->virtualFields = array(
			'test' => '
			User.id + User.id
			'
		);
		$result = $this->db->fields($Article, null, array());
		$result = $this->db->fields($Article, $Article->alias, $result);
		$this->assertPattern('/[`\"]User[`\"]\.[`\"]id[`\"] \+ [`\"]User[`\"]\.[`\"]id[`\"]/', $result[7]);
	}

/**
 * test group to generate GROUP BY statements on virtual fields
 *
 * @return void
 */
	function testVirtualFieldsInGroup() {
		$Article = ClassRegistry::init('Article');
		$Article->virtualFields = array(
			'this_year' => 'YEAR(Article.created)'
		);

		$result = $this->db->group('this_year', $Article);

		$expected = " GROUP BY (YEAR(`Article`.`created`))";
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

		$Article =& ClassRegistry::init('Article');
		$this->testDb->fields($Article, null, array('title', 'body', 'published'));
		$this->assertTrue(empty($this->testDb->methodCache['fields']), 'Cache not empty');
	}
}
