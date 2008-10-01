<?php
/* SVN FILE: $Id$ */
/**
 * Test for Schema database management
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs
 * @since			CakePHP(tm) v 1.2.0.5550
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Schema');

/**
 * Test for Schema database management
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs
 */
class MyAppSchema extends CakeSchema {
/**
 * name property
 *
 * @var string 'MyApp'
 * @access public
 */
	var $name = 'MyApp';
/**
 * connection property
 *
 * @var string 'test_suite'
 * @access public
 */
	var $connection = 'test_suite';
/**
 * comments property
 *
 * @var array
 * @access public
 */
	var $comments = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'),
		'post_id' => array('type' => 'integer', 'null' => false, 'default' => 0),
		'user_id' => array('type' => 'integer', 'null' => false),
		'title' => array('type' => 'string', 'null' => false, 'length' => 100),
		'comment' => array('type' => 'text', 'null' => false, 'default' => null),
		'published' => array('type' => 'string', 'null' => true, 'default' => 'N', 'length' => 1),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'updated' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => true)),
	);
/**
 * posts property
 *
 * @var array
 * @access public
 */
	var $posts = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'),
		'author_id' => array('type' => 'integer', 'null' => true, 'default' => ''),
		'title' => array('type' => 'string', 'null' => false, 'default' => 'Title'),
		'body' => array('type' => 'text', 'null' => true, 'default' => null),
		'summary' => array('type' => 'text', 'null' => true),
		'published' => array('type' => 'string', 'null' => true, 'default' => 'Y', 'length' => 1),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'updated' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => true)),
	);
/**
 * setup method
 *
 * @param mixed $version
 * @access public
 * @return void
 */
	function setup($version) {
	}
/**
 * teardown method
 *
 * @param mixed $version
 * @access public
 * @return void
 */
	function teardown($version) {
	}
}
/**
 * TestAppSchema class
 *
 * @package cake
 * @subpackage cake.tests.cases.libs.model
 */
class TestAppSchema extends CakeSchema {
/**
 * name property
 *
 * @var string 'MyApp'
 * @access public
 */
	var $name = 'MyApp';
/**
 * comments property
 *
 * @var array
 * @access public
 */
	var $comments = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => 0,'key' => 'primary'),
		'article_id' => array('type' => 'integer', 'null' => false),
		'user_id' => array('type' => 'integer', 'null' => false),
		'comment' => array('type' => 'text', 'null' => true, 'default' => null),
		'published' => array('type' => 'string', 'null' => true, 'default' => 'N', 'length' => 1),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'updated' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => true)),
	);
/**
 * posts property
 *
 * @var array
 * @access public
 */
	var $posts = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'),
		'author_id' => array('type' => 'integer', 'null' => false),
		'title' => array('type' => 'string', 'null' => false),
		'body' => array('type' => 'text', 'null' => true, 'default' => null),
		'published' => array('type' => 'string', 'null' => true, 'default' => 'N', 'length' => 1),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'updated' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => true)),
	);
/**
 * posts_tags property
 *
 * @var array
 * @access public
 */
	var $posts_tags = array(
		'post_id' => array('type' => 'integer', 'null' => false),
		'tag_id' => array('type' => 'string', 'null' => false, 'key' => 'primary'),
		'indexes' => array()
	);
/**
 * tags property
 *
 * @var array
 * @access public
 */
	var $tags = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'),
		'tag' => array('type' => 'string', 'null' => false),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'updated' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => true))
	);
/**
 * datatypes property
 *
 * @var array
 * @access public
 */
	var $datatypes = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'),
		'float_field' => array('type' => 'float', 'null' => false, 'length' => '5,2'),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => true))
	);
/**
 * setup method
 *
 * @param mixed $version
 * @access public
 * @return void
 */
	function setup($version) {
	}
/**
 * teardown method
 *
 * @param mixed $version
 * @access public
 * @return void
 */
	function teardown($version) {
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class SchemaPost extends CakeTestModel {
/**
 * name property
 *
 * @var string 'SchemaPost'
 * @access public
 */
	var $name = 'SchemaPost';
/**
 * useTable property
 *
 * @var string 'posts'
 * @access public
 */
	var $useTable = 'posts';
/**
 * hasMany property
 *
 * @var array
 * @access public
 */
	var $hasMany = array('SchemaComment');
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('SchemaTag');

}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class SchemaComment extends CakeTestModel {
/**
 * name property
 *
 * @var string 'SchemaComment'
 * @access public
 */
	var $name = 'SchemaComment';
/**
 * useTable property
 *
 * @var string 'comments'
 * @access public
 */
	var $useTable = 'comments';
/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('SchemaPost');
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class SchemaTag extends CakeTestModel {
/**
 * name property
 *
 * @var string 'SchemaTag'
 * @access public
 */
	var $name = 'SchemaTag';
/**
 * useTable property
 *
 * @var string 'tags'
 * @access public
 */
	var $useTable = 'tags';
/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('SchemaPost');
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model
 */
class SchemaDatatype extends CakeTestModel {
/**
 * name property
 *
 * @var string 'SchemaDatatype'
 * @access public
 */
	var $name = 'SchemaDatatype';
/**
 * useTable property
 *
 * @var string 'datatypes'
 * @access public
 */
	var $useTable = 'datatypes';
}
/**
 * Testdescribe class
 *
 * This class is defined purely to inherit the cacheSources variable otherwise testSchemaCreatTable will fail if
 * listSources has already been called and its source cache populated - I.e. if the test is run within a group
 *
 * @uses                 CakeTestModel
 * @package
 * @subpackage           cake.tests.cases.libs.model
 */
class Testdescribe extends CakeTestModel {
/**
 * name property
 *
 * @var string 'Testdescribe'
 * @access public
 */
	var $name = 'Testdescribe';
}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs
 */
class CakeSchemaTest extends CakeTestCase {
/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array('core.post', 'core.tag', 'core.posts_tag', 'core.comment', 'core.datatype');
/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->Schema = new TestAppSchema();
	}
/**
 * testSchemaName method
 *
 * @access public
 * @return void
 */
	function testSchemaName() {
		$Schema = new CakeSchema();
		$this->assertEqual(strtolower($Schema->name), strtolower(APP_DIR));

		Configure::write('App.dir', 'Some.name.with.dots');
		$Schema = new CakeSchema();
		$this->assertEqual($Schema->name, 'SomeNameWithDots');

		Configure::write('App.dir', 'app');
	}
/**
 * testSchemaRead method
 *
 * @access public
 * @return void
 */
	function testSchemaRead() {
		$read = $this->Schema->read(array(
			'connection' => 'test_suite',
			'name' => 'TestApp',
			'models' => array('SchemaPost', 'SchemaComment', 'SchemaTag', 'SchemaDatatype')
		));
		unset($read['tables']['missing']);

		$this->assertEqual($read['tables'], $this->Schema->tables);
		$this->assertIdentical(
			$read['tables']['datatypes']['float_field'],
			$this->Schema->tables['datatypes']['float_field']
		);

		$db =& ConnectionManager::getDataSource('test_suite');
		$config = $db->config;
		$config['prefix'] = 'schema_test_prefix_';
		ConnectionManager::create('schema_prefix', $config);
		$read = $this->Schema->read(array('connection' => 'schema_prefix', 'models' => false));
		$this->assertTrue(empty($read['tables']));
	}
/**
 * testSchemaWrite method
 *
 * @access public
 * @return void
 */
	function testSchemaWrite() {
		$write = $this->Schema->write(array('name' => 'MyOtherApp', 'tables' => $this->Schema->tables, 'path' => TMP . 'tests'));
		$file = file_get_contents(TMP . 'tests' . DS .'schema.php');
		$this->assertEqual($write, $file);

		require_once( TMP . 'tests' . DS .'schema.php');
		$OtherSchema = new MyOtherAppSchema();
		$this->assertEqual($this->Schema->tables, $OtherSchema->tables);

	}
/**
 * testSchemaComparison method
 *
 * @access public
 * @return void
 */
	function testSchemaComparison() {
		$New = new MyAppSchema();
		$compare = $New->compare($this->Schema);
		$expected = array(
			'comments' => array(
				'add' => array(
					'post_id' => array('type' => 'integer', 'null' => false, 'default' => 0),
					'title' => array('type' => 'string', 'null' => false, 'length' => 100)
				),
				'drop' => array('article_id' => array('type' => 'integer', 'null' => false)),
				'change' => array(
					'comment' => array('type' => 'text', 'null' => false, 'default' => null)
				)
			),
			'posts' => array(
				'add' => array('summary' => array('type' => 'text', 'null' => 1)),
				'change' => array(
					'author_id' => array('type' => 'integer', 'null' => true, 'default' => ''),
					'title' => array('type' => 'string', 'null' => false, 'default' => 'Title'),
					'published' => array(
						'type' => 'string', 'null' => true, 'default' => 'Y', 'length' => '1'
					)
				)
			),
		);
		$this->assertEqual($expected, $compare);
	}
/**
 * testSchemaLoading method
 *
 * @access public
 * @return void
 */
	function testSchemaLoading() {
		$Other = $this->Schema->load(array('name' => 'MyOtherApp', 'path' => TMP . 'tests'));
		$this->assertEqual($Other->name, 'MyOtherApp');
		$this->assertEqual($Other->tables, $this->Schema->tables);
	}
/**
 * testSchemaCreateTable method
 *
 * @access public
 * @return void
 */
	function testSchemaCreateTable() {
		$db =& ConnectionManager::getDataSource('test_suite');
		$db->cacheSources = false;

		$db->query(
			'CREATE TABLE ' . $db->fullTableName('testdescribes') . ' (id int(11) AUTO_INCREMENT' .
			', int_null int(10) unsigned NULL, int_not_null int(10) unsigned NOT NULL, primary ' .
			'key(id));'
		);

		$Schema =& new CakeSchema(array('connection' => 'test_suite'));
		$read = $Schema->read(array('models' => array('Testdescribe')));
		unset($read['tables']['missing']);
		$Schema->tables = $read['tables'];

		$sql = $db->createSchema($Schema);

		$this->assertPattern('/`int_null` int\(10\) DEFAULT NULL/', $sql);
		$this->assertPattern('/`int_not_null` int\(10\) NOT NULL/', $sql);

		$db->query('DROP TABLE ' . $this->db->fullTableName('testdescribes'));
	}
/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->Schema);
	}
}
?>