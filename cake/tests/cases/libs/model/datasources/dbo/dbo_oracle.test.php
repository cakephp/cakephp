<?php
/**
 * DboOracleTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Core', array('Model', 'DataSource', 'DboSource', 'DboOracle'));

/**
 * DboOracleTestDb class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class DboOracleTestDb extends DboOracle {

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
		$this->_statementId = null;
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
	
/**
 * getHistoricalQuery method
 * Get a query from the passed steps $ago.
 * E.g. getHistoricalQuery(1) is the same as getLastQuery().
 * getHistoricalQuery(3) is the query executed 3 times ago.
 * 
 * @param int $ago
 * @access public
 * @return String
 */
	function getHistoricalQuery($ago) {
		return $this->simulated[count($this->simulated) - $ago];
	}
}

/**
 * OracleTestModel class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources
 */
class OracleTestModel extends Model {

/**
 * name property
 *
 * @var string 'OracleTestModel'
 * @access public
 */
	var $name = 'OracleTestModel';

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
 * DboOracleTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.model.datasources.dbo
 */
class DboOracleTest extends CakeTestCase {

/**
 * fixtures property
 */
	var $fixtures = array('core.oracle_user');

/**
 * Actual DB connection used in testing
 *
 * @var DboSource
 * @access public
 */
	var $db = null;

/**
 * Simulated DB connection used in testing
 *
 * @var DboSource
 * @access public
 */
	var $simDb = null;
	
/**
 * Testing model
 * 
 * @var Model
 * @access public
 */
	var $model = null;
	
/**
 * Set up test suite database connection
 *
 * @access public
 */
	function startTest() {
		$this->_initDb();
	}
	
/**
 * setup method
 *
 * @access public
 * @return void
 */
	function setUp() {
		Configure::write('Cache.disable', true);
		$this->startTest();
		$this->db =& ConnectionManager::getDataSource('test_suite');
		$this->simDb = new DboOracleTestDb($this->db->config, false);
		$this->model = new OracleTestModel();
	}

/**
 * Tears down the Dbo class instance
 *
 * @access public
 */
	function tearDown() {
		Configure::write('Cache.disable', false);
		unset($this->db);
		unset($this->simDb);
		unset($this->model);
	}
	
/**
 * skip method
 *
 * @access public
 * @return void
 */
	function skip() {
		$this->_initDb();
		$this->skipUnless($this->db->config['driver'] == 'oracle', '%s Oracle connection not available');
	}

/**
 * testConnect method
 * 
 * @access public
 * @return void
 */
	function testConnect() {
		$result = $this->db->connect();
		$this->assertTrue($result);
		
		$this->simDb->config['schema'] = 'SAIBOT';
		$this->simDb->connect();
		$result = $this->simDb->getLastQuery();
		$expected = "ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'";
		$this->assertEqual($expected, $result);
		
		$result = $this->simDb->getHistoricalQuery(2);
		$expected = 'ALTER SESSION SET CURRENT_SCHEMA=SAIBOT';
		$this->assertEqual($expected, $result);
	}
	
/**
 * testDescribe method
 * 
 * @access public
 * @return void
 */
	function testDescribe() {
		$this->model->table = 'test_table';
		$this->simDb->describe($this->model);
		$expected = 'test_table_seq';
		$result = $this->simDb->_sequenceMap['test_table'];
		$this->assertEqual($expected, $result);
		
		$this->model->tablePrefix = 'ultimate_';
		$this->simDb->describe($this->model);
		$expected = 'ultimate_test_table_seq';
		$result = $this->simDb->_sequenceMap['ultimate_test_table'];
		$this->assertEqual($expected, $result);

		$this->model->tablePrefix = null;
		$this->model->sequence = 'test_sequence_dude';
		$this->simDb->describe($this->model);
		$expected = 'test_sequence_dude';
		$result = $this->simDb->_sequenceMap['test_table'];
		$this->assertEqual($expected, $result);
	}
	
/**
 * testLastInsertId method
 * 
 * @access public
 * @return void
 */
	function testLastInsertId() {
		$this->model->table = 'test_table';
		$this->simDb->describe($this->model);
		$this->simDb->lastInsertId('test_table');
		$expected = "SELECT test_table_seq.currval FROM dual";
		$result = $this->simDb->getLastQuery();
		$this->assertEqual($expected, $result);
	}
/**
 * testLastErrorStatement method
 *
 * @access public
 * @return void
 */
	function testLastErrorStatement() {
		if ($this->skip('testLastErrorStatement')) {
			return;
		}

		$this->expectError();
		$this->db->execute("SELECT ' FROM dual");
		$e = $this->db->lastError();
		$r = 'ORA-01756: quoted string not properly terminated';
		$this->assertEqual($e, $r);
	}

/**
 * testLastErrorConnect method
 *
 * @access public
 * @return void
 */
	function testLastErrorConnect() {
		if ($this->skip('testLastErrorConnect')) {
			return;
		}

		$config = $this->db->config;
		$old_pw = $this->db->config['password'];
		$this->db->config['password'] = 'keepmeout';
		$this->db->connect();
		$e = $this->db->lastError();
		$r = 'ORA-01017: invalid username/password; logon denied';
		$this->assertEqual($e, $r);
		$this->db->config['password'] = $old_pw;
		$this->db->connect();
	}

/**
 * testName method
 *
 * @access public
 * @return void
 */
	function testName() {
		$Db = $this->db;
		#$Db =& new DboOracle($config = null, $autoConnect = false);

		$r = $Db->name($Db->name($Db->name('foo.last_update_date')));
		$e = 'foo.last_update_date';
		$this->assertEqual($e, $r);

		$r = $Db->name($Db->name($Db->name('foo._update')));
		$e = 'foo."_update"';
		$this->assertEqual($e, $r);

		$r = $Db->name($Db->name($Db->name('foo.last_update_date')));
		$e = 'foo.last_update_date';
		$this->assertEqual($e, $r);

		$r = $Db->name($Db->name($Db->name('last_update_date')));
		$e = 'last_update_date';
		$this->assertEqual($e, $r);

		$r = $Db->name($Db->name($Db->name('_update')));
		$e = '"_update"';
		$this->assertEqual($e, $r);

	}
}
