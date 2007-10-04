<?php
/* SVN FILE: $Id$ */
/**
 * DboPostgres test
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
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

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class DboPostgresTestDb extends DboPostgres {

	var $simulated = array();

	function _execute($sql) {
		$this->simulated[] = $sql;
		return null;
	}

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

	var $name = 'PostgresTestModel';
	var $useTable = false;

	function find($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

	function findAll($conditions = null, $fields = null, $order = null, $recursive = null) {
		return $conditions;
	}

	function schema() {
		return new Set(array(
			'id'		=> array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
			'client_id'	=> array('type' => 'integer', 'null' => '', 'default' => '0', 'length' => '11'),
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
		));
	}
}
/**
 * The test class for the DboPostgres
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources.dbo
 */
class DboPostgresTest extends UnitTestCase {
/**
 * The Dbo instance to be tested
 *
 * @var object
 * @access public
 */
	var $Db = null;
/**
 * Skip if cannot connect to mysql
 *
 * @return void
 * @access public
 */
	function skip() {
		$skip = true;
		if(function_exists('pg_connect')) {
			$skip = false;
		}
		$this->skipif (true, 'Postgres not installed');
	}

/**
 * Sets up a Dbo class instance for testing
 *
 * @return void
 * @access public
 */
	function setUp() {
		require_once r('//', '/', APP) . 'config/database.php';
		$config = new DATABASE_CONFIG();
		$this->Db =& new DboPostgresTestDb($config->default, false);
		$this->Db->fullDebug = false;
		$this->model = new PostgresTestModel();
	}
/**
 * Sets up a Dbo class instance for testing
 *
 * @return void
 * @access public
 */
	function tearDown() {
		unset($this->Db);
	}
/**
 * Test Dbo value method
 *
 * @return void
 * @access public
 */
	function testQuoting() {

		$result = $this->Db->fields($this->model);
		$expected = array(
			'PostgresTestModel`.`id` AS `PostgresTestModel__0`',
			'`PostgresTestModel`.`client_id` AS `PostgresTestModel__1`',
			'`PostgresTestModel`.`name` AS `PostgresTestModel__2`',
			'`PostgresTestModel`.`login` AS `PostgresTestModel__3`',
			'`PostgresTestModel`.`passwd` AS `PostgresTestModel__4`',
			'`PostgresTestModel`.`addr_1` AS `PostgresTestModel__5`',
			'`PostgresTestModel`.`addr_2` AS `PostgresTestModel__6`',
			'`PostgresTestModel`.`zip_code` AS `PostgresTestModel__7`',
			'`PostgresTestModel`.`city` AS `PostgresTestModel__8`',
			'`PostgresTestModel`.`country` AS `PostgresTestModel__9`',
			'`PostgresTestModel`.`phone` AS `PostgresTestModel__10`',
			'`PostgresTestModel`.`fax` AS `PostgresTestModel__11`',
			'`PostgresTestModel`.`url` AS `PostgresTestModel__12`',
			'`PostgresTestModel`.`email` AS `PostgresTestModel__13`',
			'`PostgresTestModel`.`comments` AS `PostgresTestModel__14`',
			'`PostgresTestModel`.`last_login` AS `PostgresTestModel__15`',
			'`PostgresTestModel`.`created` AS `PostgresTestModel__16`',
			'`PostgresTestModel`.`updated` AS `PostgresTestModel__17`'
		);
		$this->assertEqual($result, $expected);

		$expected = 1.2;
		$result = $this->Db->value(1.2, 'float');
		$this->assertIdentical($expected, $result);

		$expected = "'1,2'";
		$result = $this->Db->value('1,2', 'float');
		$this->assertIdentical($expected, $result);
	}
}
?>