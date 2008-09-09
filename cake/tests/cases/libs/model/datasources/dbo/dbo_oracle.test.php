<?php
/* SVN FILE: $Id$ */
/**
 * DboOracle test
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
require_once LIBS.'model'.DS.'datasources'.DS.'dbo_source.php';
require_once LIBS.'model'.DS.'datasources'.DS.'dbo'.DS.'dbo_oracle.php';

/**
 * DboOracleTest class
 *
 * @package				 cake
 * @subpackage			 cake.tests.cases.libs.model.datasources.dbo
 */
class DboOracleTest extends CakeTestCase {
/**
 * setup method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->_initDb();
	}
/**
 * skip method
 *
 * @access public
 * @return void
 */
	function skip($case = null) {
		$this->_initDb();
		if ($this->db->config['driver'] != 'oracle' && $case) {
			pr("Oracle connection not available not available for " . $case);
			return true;
		}
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
		$this->db->config['password'] = 'keepmeout';
		$this->db->connect();
		$e = $this->db->lastError();
		$r = 'ORA-01017: invalid username/password; logon denied';
		$this->assertEqual($e, $r);
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

?>