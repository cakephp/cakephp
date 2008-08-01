<?php
/* SVN FILE: $Id$ */
/**
 * DboPostgres test
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link			http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package			cake
 * @subpackage		cake.cake.libs
 * @since			CakePHP(tm) v 1.2.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
require_once LIBS.'model'.DS.'model.php';
require_once LIBS.'model'.DS.'datasources'.DS.'datasource.php';
require_once LIBS.'model'.DS.'datasources'.DS.'dbo_source.php';
require_once LIBS.'model'.DS.'datasources'.DS.'dbo'.DS.'dbo_postgres.php';
require_once dirname(dirname(dirname(__FILE__))) . DS . 'models.php';


/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class DboPostgresTestDb extends DboPostgres {
/**
 * simulated property
 *
 * @var array
 * @access public
 */
	var $simulated = array();
/**
 * execute method
 *
 * @param mixed $sql
 * @access protected
 * @return void
 */
	function _execute($sql) {
		$this->simulated[] = $sql;
		return null;
	}
/**
 * getLastQuery method
 *
 * @access public
 * @return void
 */
	function getLastQuery() {
		return $this->simulated[count($this->simulated) - 1];
	}
}
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class PostgresTestModel extends Model {
/**
 * name property
 *
 * @var string 'PostgresTestModel'
 * @access public
 */
	var $name = 'PostgresTestModel';
/**
 * useTable property
 *
 * @var bool false
 * @access public
 */
	var $useTable = false;
/**
 * find method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @access public
 * @return void
 */
	function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}
/**
 * findAll method
 *
 * @param mixed $conditions
 * @param mixed $fields
 * @param mixed $order
 * @param mixed $recursive
 * @access public
 * @return void
 */
	function findAll($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}
/**
 * schema method
 *
 * @access public
 * @return void
 */
	function schema() {
		return array(
			'id'		=> array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'client_id' => array('type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
			'name'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'login'		=> array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
			'passwd'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'addr_1'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'addr_2'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '25'),
			'zip_code'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'city'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'country'	=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'phone'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'fax'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'url'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '255'),
			'email'		=> array('type' => 'string', 'null' => '1', 'default' => '', 'length' => '155'),
			'comments'	=> array('type' => 'text', 'null' => '1', 'default' => '', 'length' => ''),
			'last_login'=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => ''),
			'created'	=> array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
			'updated'	=> array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null)
		);
	}
}
/**
 * The test class for the DboPostgres
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources.dbo
 */
class DboPostgresTest extends CakeTestCase {
/**
 * Do not automatically load fixtures for each test, they will be loaded manually using CakeTestCase::loadFixtures
 *
 * @var boolean
 * @access public
 */
	var $autoFixtures = false;
/**
 * Fixtures
 *
 * @var object
 * @access public
 */
	var $fixtures = array('core.user');
/**
 * Actual DB connection used in testing
 *
 * @var object
 * @access public
 */
	var $db = null;
/**
 * Simulated DB connection used in testing
 *
 * @var object
 * @access public
 */
	var $db2 = null;
/**
 * Skip if cannot connect to postgres
 *
 * @access public
 */
	function skip() {
		$this->_initDb();
		$this->skipif($this->db->config['driver'] != 'postgres', 'PostgreSQL connection not available');
	}
/**
 * Set up test suite database connection
 *
 * @access public
 */
	function startTest() {
		$this->_initDb();
	}
/**
 * Sets up a Dbo class instance for testing
 *
 * @access public
 */
	function setUp() {
		Configure::write('Cache.disable', true);
		$this->startTest();
		$this->db =& ConnectionManager::getDataSource('test_suite');
		$this->db2 = new DboPostgresTestDb($this->db->config, false);
		$this->model = new PostgresTestModel();
	}
/**
 * Sets up a Dbo class instance for testing
 *
 * @access public
 */
	function tearDown() {
		Configure::write('Cache.disable', false);
		unset($this->db2);
	}
/**
 * Test field and value quoting method
 *
 * @access public
 */
	function testQuoting() {
		$result = $this->db2->fields($this->model);
		$expected = array(
			'"PostgresTestModel"."id" AS "PostgresTestModel__id"',
			'"PostgresTestModel"."client_id" AS "PostgresTestModel__client_id"',
			'"PostgresTestModel"."name" AS "PostgresTestModel__name"',
			'"PostgresTestModel"."login" AS "PostgresTestModel__login"',
			'"PostgresTestModel"."passwd" AS "PostgresTestModel__passwd"',
			'"PostgresTestModel"."addr_1" AS "PostgresTestModel__addr_1"',
			'"PostgresTestModel"."addr_2" AS "PostgresTestModel__addr_2"',
			'"PostgresTestModel"."zip_code" AS "PostgresTestModel__zip_code"',
			'"PostgresTestModel"."city" AS "PostgresTestModel__city"',
			'"PostgresTestModel"."country" AS "PostgresTestModel__country"',
			'"PostgresTestModel"."phone" AS "PostgresTestModel__phone"',
			'"PostgresTestModel"."fax" AS "PostgresTestModel__fax"',
			'"PostgresTestModel"."url" AS "PostgresTestModel__url"',
			'"PostgresTestModel"."email" AS "PostgresTestModel__email"',
			'"PostgresTestModel"."comments" AS "PostgresTestModel__comments"',
			'"PostgresTestModel"."last_login" AS "PostgresTestModel__last_login"',
			'"PostgresTestModel"."created" AS "PostgresTestModel__created"',
			'"PostgresTestModel"."updated" AS "PostgresTestModel__updated"'
		);
		$this->assertEqual($result, $expected);

		$expected = "'1.2'";
		$result = $this->db2->value(1.2, 'float');
		$this->assertIdentical($expected, $result);

		$expected = "'1,2'";
		$result = $this->db2->value('1,2', 'float');
		$this->assertIdentical($expected, $result);
	}
/**
 * testColumnParsing method
 *
 * @access public
 * @return void
 */
	function testColumnParsing() {
		$this->assertEqual($this->db2->column('text'), 'text');
		$this->assertEqual($this->db2->column('date'), 'date');
		$this->assertEqual($this->db2->column('boolean'), 'boolean');
		$this->assertEqual($this->db2->column('character varying'), 'string');
		$this->assertEqual($this->db2->column('time without time zone'), 'time');
		$this->assertEqual($this->db2->column('timestamp without time zone'), 'datetime');
	}
/**
 * testValueQuoting method
 *
 * @access public
 * @return void
 */
	function testValueQuoting() {
		$this->assertEqual($this->db2->value('0', 'integer'), "'0'");
		$this->assertEqual($this->db2->value('', 'integer'), "DEFAULT");
		$this->assertEqual($this->db2->value('', 'float'), "DEFAULT");
		$this->assertEqual($this->db2->value('0.0', 'float'), "'0.0'");

		$this->assertEqual($this->db2->value('t', 'boolean'), "TRUE");
		$this->assertEqual($this->db2->value('f', 'boolean'), "FALSE");
		$this->assertEqual($this->db2->value(true), "TRUE");
		$this->assertEqual($this->db2->value(false), "FALSE");
		$this->assertEqual($this->db2->value('t'), "'t'");
		$this->assertEqual($this->db2->value('f'), "'f'");
		$this->assertEqual($this->db2->value('', 'boolean'), 'FALSE');
		$this->assertEqual($this->db2->value(0, 'boolean'), 'FALSE');
		$this->assertEqual($this->db2->value(1, 'boolean'), 'TRUE');
		$this->assertEqual($this->db2->value('1', 'boolean'), 'TRUE');
		$this->assertEqual($this->db2->value(null, 'boolean'), "NULL");
	}
/**
 * testLastInsertIdMultipleInsert method
 *
 * @access public
 * @return void
 */
	function testLastInsertIdMultipleInsert() {
		$this->loadFixtures('User');

		$User =& new User();
		$db1 = ConnectionManager::getDataSource('test_suite');

		if (PHP5) {
			$db2 = clone $db1;
		} else {
			$db2 = $db1;
		}

		$db2->connect();
		$this->assertNotEqual($db1->connection, $db2->connection);

		$db1->truncate($User->useTable);

		$table = $db1->fullTableName($User->useTable, false);
		$db1->execute(
			"INSERT INTO {$table} (\"user\", password) VALUES ('mariano', '5f4dcc3b5aa765d61d8327deb882cf99')"
		);
		$db2->execute(
			"INSERT INTO {$table} (\"user\", password) VALUES ('hoge', '5f4dcc3b5aa765d61d8327deb882cf99')"
		);
		$this->assertEqual($db1->lastInsertId($table), 1);
		$this->assertEqual($db2->lastInsertId($table), 2);
	}

/**
 * Tests that table lists and descriptions are scoped to the proper Postgres schema 
 *
 * @access public
 * @return void
 */	
	function testSchemaScoping() {
		$db1 =& ConnectionManager::getDataSource('test_suite');
		$db1->cacheSources = false;
		$db1->reconnect(array('persistent' => false));
		$db1->query('CREATE SCHEMA _scope_test');

		$db2 =& ConnectionManager::create(
			'test_suite_2',
			array_merge($db1->config, array('driver' => 'postgres', 'schema' => '_scope_test'))
		);
		$db2->cacheSources = false;
		
		$db2->query('DROP SCHEMA _scope_test');
	}
}

?>