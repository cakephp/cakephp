<?php
/* SVN FILE: $Id$ */
/**
 * DboMysql test
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
App::import('Core', array('Model', 'DataSource', 'DboSource', 'DboMysql'));


/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class DboMysqlTestDb extends DboMysql {
/**
 * simulated property
 * 
 * @var array
 * @access public
 */
	var $simulated = array();
/**
 * testing property
 * 
 * @var bool true
 * @access public
 */
	var $testing = true;
/**
 * execute method
 * 
 * @param mixed $sql 
 * @access protected
 * @return void
 */
	function _execute($sql) {
		if ($this->testing) {
			$this->simulated[] = $sql;
			return null;
		}
		return parent::_execute($sql);
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
class MysqlTestModel extends Model {
/**
 * name property
 * 
 * @var string 'MysqlTestModel'
 * @access public
 */
	var $name = 'MysqlTestModel';
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
		);
	}
}
/**
 * The test class for the DboMysql
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources.dbo
 */
class DboMysqlTest extends CakeTestCase {
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
 * @access public
 */
	function skip() {
		$this->_initDb();
		$this->skipif($this->db->config['driver'] != 'mysql', 'MySQL connection not available');
	}
/**
 * Sets up a Dbo class instance for testing
 *
 * @access public
 */
	function setUp() {
		$db = ConnectionManager::getDataSource('test_suite');
		$this->db = new DboMysqlTestDb($db->config);
		$this->model = new MysqlTestModel();
	}
/**
 * Sets up a Dbo class instance for testing
 *
 * @access public
 */
	function tearDown() {
		unset($this->db);
	}
/**
 * Test Dbo value method
 *
 * @access public
 */
	function testQuoting() {
		$result = $this->db->fields($this->model);
		$expected = array(
			'`MysqlTestModel`.`id`',
			'`MysqlTestModel`.`client_id`',
			'`MysqlTestModel`.`name`',
			'`MysqlTestModel`.`login`',
			'`MysqlTestModel`.`passwd`',
			'`MysqlTestModel`.`addr_1`',
			'`MysqlTestModel`.`addr_2`',
			'`MysqlTestModel`.`zip_code`',
			'`MysqlTestModel`.`city`',
			'`MysqlTestModel`.`country`',
			'`MysqlTestModel`.`phone`',
			'`MysqlTestModel`.`fax`',
			'`MysqlTestModel`.`url`',
			'`MysqlTestModel`.`email`',
			'`MysqlTestModel`.`comments`',
			'`MysqlTestModel`.`last_login`',
			'`MysqlTestModel`.`created`',
			'`MysqlTestModel`.`updated`'
		);
		$this->assertEqual($result, $expected);

		$expected = 1.2;
		$result = $this->db->value(1.2, 'float');
		$this->assertEqual($expected, $result);

		$expected = "'1,2'";
		$result = $this->db->value('1,2', 'float');
		$this->assertEqual($expected, $result);

		$expected = "'4713e29446'";
		$result = $this->db->value('4713e29446');

		$this->assertEqual($expected, $result);

		$expected = 'NULL';
		$result = $this->db->value('', 'integer');
		$this->assertEqual($expected, $result);
		
		$expected = 'NULL';
		$result = $this->db->value('', 'boolean');
		$this->assertEqual($expected, $result);

		$expected = 10010001;
		$result = $this->db->value(10010001);
		$this->assertEqual($expected, $result);

		$expected = "'00010010001'";
		$result = $this->db->value('00010010001');
		$this->assertEqual($expected, $result);
	}
/**
 * testTinyintCasting method
 * 
 * @access public
 * @return void
 */
	function testTinyintCasting() {
		$this->db->cacheSources = $this->db->testing = false;
		$this->db->query('CREATE TABLE ' . $this->db->fullTableName('tinyint') . ' (id int(11) AUTO_INCREMENT, bool tinyint(1), small_int tinyint(2), primary key(id));');

		$this->model = new CakeTestModel(array(
			'name' => 'Tinyint', 'table' => $this->db->fullTableName('tinyint', false)
		));

		$result = $this->model->schema();
		$this->assertEqual($result['bool']['type'], 'boolean');
		$this->assertEqual($result['small_int']['type'], 'integer');

		$this->assertTrue($this->model->save(array('bool' => 5, 'small_int' => 5)));
		$result = $this->model->find('first');
		$this->assertIdentical($result['Tinyint']['bool'], '1');
		$this->assertIdentical($result['Tinyint']['small_int'], '5');
		$this->model->deleteAll(true);

		$this->assertTrue($this->model->save(array('bool' => 0, 'small_int' => 100)));
		$result = $this->model->find('first');
		$this->assertIdentical($result['Tinyint']['bool'], '0');
		$this->assertIdentical($result['Tinyint']['small_int'], '100');
		$this->model->deleteAll(true);

		$this->assertTrue($this->model->save(array('bool' => true, 'small_int' => 0)));
		$result = $this->model->find('first');
		$this->assertIdentical($result['Tinyint']['bool'], '1');
		$this->assertIdentical($result['Tinyint']['small_int'], '0');
		$this->model->deleteAll(true);

		$this->db->query('DROP TABLE ' . $this->db->fullTableName('tinyint'));
	}
}

?>