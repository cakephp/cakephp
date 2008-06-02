<?php
/* SVN FILE: $Id: dbo_mysql.test.php 6296 2008-01-01 22:18:17Z phpnut $ */
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
 * @version			$Revision: 6296 $
 * @modifiedby		$LastChangedBy: phpnut $
 * @lastmodified	$Date: 2008-01-01 17:18:17 -0500 (Tue, 01 Jan 2008) $
 * @license			http://www.opensource.org/licenses/mit-license.php The MIT License
 */

if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
require_once LIBS.'model'.DS.'datasources'.DS.'dbo_source.php';
/**
 * DboOracleTest class
 * 
 * @package              cake
 * @subpackage           cake.tests.cases.libs.model.datasources.dbo
 */
class DboOracleTest extends CakeTestCase {
/**
 * skip method
 * 
 * @access public
 * @return void
 */
	function skip() {
		$this->_initDb();
		$this->skipif($this->db->config['driver'] != 'oracle', 'Oracle connection not available');
	}
/**
 * testLastErrorStatement method
 * 
 * @access public
 * @return void
 */
	function testLastErrorStatement() {
		$this->expectError();
		$this->db->execute("SELECT ' FROM dual");
		$e = $this->db->lastError();
		$r = 'ORA-01756: quoted string not properly terminated';
		$this->assertEqual($e, $r);
	}

	function testLastErrorConnect() {
		$config = $this->db->config;
		$this->db->config['password'] = 'keepmeout';
		$this->db->connect();
		$e = $this->db->lastError();
		$r = 'ORA-01017: invalid username/password; logon denied';
		$this->assertEqual($e, $r);
	}


}


?>
