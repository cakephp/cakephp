<?php
/* SVN FILE: $Id$ */
/**
 * DboMysql test
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
require_once LIBS.'model'.DS.'datasources'.DS.'dbo'.DS.'dbo_mysql.php';

/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class DboMysqlTestDb extends DboMysql {

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
class MysqlTestModel extends Model {

	var $name = 'MysqlTestModel';
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
 * The test class for the DboMysql
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources.dbo
 */
class DboMysqlTest extends UnitTestCase {
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
		if(function_exists('mysql_connect')) {
			$skip = false;
		}
		$this->skipif ($skip, 'Mysql not installed');
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
		$this->Db =& new DboMysqlTestDb($config->default, false);
		$this->Db->fullDebug = false;
		$this->model = new MysqlTestModel();
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
			'MysqlTestModel`.`id` AS `MysqlTestModel__0`',
			'`MysqlTestModel`.`client_id` AS `MysqlTestModel__1`',
			'`MysqlTestModel`.`name` AS `MysqlTestModel__2`',
			'`MysqlTestModel`.`login` AS `MysqlTestModel__3`',
			'`MysqlTestModel`.`passwd` AS `MysqlTestModel__4`',
			'`MysqlTestModel`.`addr_1` AS `MysqlTestModel__5`',
			'`MysqlTestModel`.`addr_2` AS `MysqlTestModel__6`',
			'`MysqlTestModel`.`zip_code` AS `MysqlTestModel__7`',
			'`MysqlTestModel`.`city` AS `MysqlTestModel__8`',
			'`MysqlTestModel`.`country` AS `MysqlTestModel__9`',
			'`MysqlTestModel`.`phone` AS `MysqlTestModel__10`',
			'`MysqlTestModel`.`fax` AS `MysqlTestModel__11`',
			'`MysqlTestModel`.`url` AS `MysqlTestModel__12`',
			'`MysqlTestModel`.`email` AS `MysqlTestModel__13`',
			'`MysqlTestModel`.`comments` AS `MysqlTestModel__14`',
			'`MysqlTestModel`.`last_login` AS `MysqlTestModel__15`',
			'`MysqlTestModel`.`created` AS `MysqlTestModel__16`',
			'`MysqlTestModel`.`updated` AS `MysqlTestModel__17`'
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