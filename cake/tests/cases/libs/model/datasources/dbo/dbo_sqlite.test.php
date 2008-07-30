<?php
/* SVN FILE: $Id: dbo_sqlite.test.php 7257 2008-06-24 04:55:59Z nate $ */
/**
 * DboSqlite test
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
 * @version			$Revision: 7257 $
 * @modifiedby		$LastChangedBy: nate $
 * @lastmodified	$Date: 2008-06-24 00:55:59 -0400 (Tue, 24 Jun 2008) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
require_once LIBS.'model'.DS.'model.php';
require_once LIBS.'model'.DS.'datasources'.DS.'datasource.php';
require_once LIBS.'model'.DS.'datasources'.DS.'dbo_source.php';
require_once LIBS.'model'.DS.'datasources'.DS.'dbo'.DS.'dbo_sqlite.php';
require_once dirname(dirname(dirname(__FILE__))) . DS . 'models.php';


/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources
 */
class DboSqliteTestDb extends DboSqlite {
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
 * The test class for the DboPostgres
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.model.datasources.dbo
 */
class DboSqliteTest extends CakeTestCase {
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
 * Skip if cannot connect to SQLite
 *
 * @access public
 */
	function skip() {
		$this->_initDb();
		$this->skipif($this->db->config['driver'] != 'sqlite', 'SQLite connection not available');
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
		$this->db2 = new DboSqliteTestDb($this->db->config, false);
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
 * Tests that SELECT queries from DboSqlite::listSources() are not cached
 *
 * @access public
 */
	function testTableListCacheDisabling() {
		$this->assertFalse(in_array('foo_test', $this->db->listSources()));

		$this->db->query('CREATE TABLE foo_test (test VARCHAR(255));');
		$this->assertTrue(in_array('foo_test', $this->db->listSources()));

		$this->db->query('DROP TABLE foo_test;');
		$this->assertFalse(in_array('foo_test', $this->db->listSources()));
	}
}

?>