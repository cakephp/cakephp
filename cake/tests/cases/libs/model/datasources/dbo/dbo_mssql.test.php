<?php
/* SVN FILE: $Id$ */
/**
 * DboMssql test
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
require_once LIBS.'model'.DS.'datasources'.DS.'dbo'.DS.'dbo_mssql.php';

/**
 * DboMssqlTestDb class
 * 
 * @package              cake
 * @subpackage           cake.tests.cases.libs.model.datasources.dbo
 */
class DboMssqlTestDb extends DboMssql {
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
class MssqlTestModel extends Model {
/**
 * name property
 * 
 * @var string 'MssqlTestModel'
 * @access public
 */
	var $name = 'MssqlTestModel';
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
		$this->_schema = array(
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
		return $this->_schema;
	}
}
/**
 * The test class for the DboMssql
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources.dbo
 */
class DboMssqlTest extends CakeTestCase {
/**
 * The Dbo instance to be tested
 *
 * @var object
 * @access public
 */
	var $db = null;
/**
 * Skip if cannot connect to mssql
 *
 * @access public
 */
	function skip() {
		$this->_initDb();
		$this->skipif ($this->db->config['driver'] != 'mssql', 'SQL Server connection not available');
	}
/**
 * Sets up a Dbo class instance for testing
 *
 * @access public
 */
	function setUp() {
		$db = ConnectionManager::getDataSource('test_suite');
		$this->db = new DboMssqlTestDb($db->config);
		$this->model = new MssqlTestModel();
	}
/**
 * testQuoting method
 * 
 * @access public
 * @return void
 */
	function testQuoting() {
		$result = $this->db->fields($this->model);
		$expected = array(
			'[MssqlTestModel].[id] AS [MssqlTestModel__0]',
			'[MssqlTestModel].[client_id] AS [MssqlTestModel__1]',
			'[MssqlTestModel].[name] AS [MssqlTestModel__2]',
			'[MssqlTestModel].[login] AS [MssqlTestModel__3]',
			'[MssqlTestModel].[passwd] AS [MssqlTestModel__4]',
			'[MssqlTestModel].[addr_1] AS [MssqlTestModel__5]',
			'[MssqlTestModel].[addr_2] AS [MssqlTestModel__6]',
			'[MssqlTestModel].[zip_code] AS [MssqlTestModel__7]',
			'[MssqlTestModel].[city] AS [MssqlTestModel__8]',
			'[MssqlTestModel].[country] AS [MssqlTestModel__9]',
			'[MssqlTestModel].[phone] AS [MssqlTestModel__10]',
			'[MssqlTestModel].[fax] AS [MssqlTestModel__11]',
			'[MssqlTestModel].[url] AS [MssqlTestModel__12]',
			'[MssqlTestModel].[email] AS [MssqlTestModel__13]',
			'[MssqlTestModel].[comments] AS [MssqlTestModel__14]',
			'CONVERT(VARCHAR(20), [MssqlTestModel].[last_login], 20) AS [MssqlTestModel__15]',
			'[MssqlTestModel].[created] AS [MssqlTestModel__16]',
			'CONVERT(VARCHAR(20), [MssqlTestModel].[updated], 20) AS [MssqlTestModel__17]'
		);
		$this->assertEqual($result, $expected);

		$expected = "1.2";
		$result = $this->db->value(1.2, 'float');
		$this->assertIdentical($expected, $result);

		$expected = "'1,2'";
		$result = $this->db->value('1,2', 'float');
		$this->assertIdentical($expected, $result);
	}
/**
 * testDistinctFields method
 * 
 * @access public
 * @return void
 */
	function testDistinctFields() {
		$result = $this->db->fields($this->model, null, array('DISTINCT Car.country_code'));
		$expected = array('DISTINCT [Car].[country_code] AS [Car__0]');
		$this->assertEqual($result, $expected);

		$result = $this->db->fields($this->model, null, 'DISTINCT Car.country_code');
		$expected = array('DISTINCT [Car].[country_code] AS [Car__1]');
		$this->assertEqual($result, $expected);
	}
/**
 * testDistinctWithLimit method
 * 
 * @access public
 * @return void
 */
	function testDistinctWithLimit() {
		$this->db->read($this->model, array(
			'fields' => array('DISTINCT MssqlTestModel.city', 'MssqlTestModel.country'),
			'limit' => 5
		));
		$result = $this->db->getLastQuery();
		$this->assertPattern('/^SELECT DISTINCT TOP 5/', $result);
	}
/**
 * tearDown method
 * 
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->model);
	}
}
?>