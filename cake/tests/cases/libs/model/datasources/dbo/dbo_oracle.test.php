<?php
/**
 * DboOracleTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Core', array('DataSource', 'DboSource', 'DboOracle'));
Mock::generatePartial('DboOracle', 'QueryMockDboOracle', array('query', 'execute'));

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
	}

/**
 * Tears down the Dbo class instance
 *
 * @access public
 */
	function tearDown() {
		Configure::write('Cache.disable', false);
		unset($this->db);
		ClassRegistry::flush();
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

		$mockDbo =& new QueryMockDboOracle($this);
		$mockDbo->config = $this->db->config;

		// Test NLS_SORT setting
		$mockDbo->config['nls_sort'] = 'BINARY';
		$mockDbo->setReturnValue('execute', true);
		$mockDbo->expectAt(0, 'execute', array("ALTER SESSION SET NLS_SORT=BINARY"));
		
		// The NLS_DATE_FORMAT will always be set, so test that in the same connect() run
		$mockDbo->expectAt(1, 'execute', array("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'"));

		$mockDbo->connect();

		unset($mockDbo->config['nls_sort']);

		// Test NLS_COMP setting
		$mockDbo->config['nls_comp'] = 'BINARY';
		$mockDbo->expectAt(2, 'execute', array("ALTER SESSION SET NLS_COMP=BINARY"));

		$mockDbo->connect();
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
