<?php
/**
 * DboOracleTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Datasource.Database
 * @since         CakePHP(tm) v 1.2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

require_once CAKE . 'Model' . DS . 'Datasource' . DS . 'DboSource.php';
require_once CAKE . 'Model' . DS . 'Datasource' . DS . 'Database' . DS . 'Oracle.php';

/**
 * DboOracleTest class
 *
 * @package       Cake.Test.Case.Model.Datasource.Database
 */
class DboOracleTest extends CakeTestCase {

/**
 * fixtures property
 */
	public $fixtures = array('core.oracle_user');

/**
 * setup method
 *
 * @access public
 * @return void
 */
	public function setUp() {
		$this->Dbo = ConnectionManager::getDataSource('test');
		if (!($this->Dbo instanceof Oracle)) {
			$this->markTestSkipped('The Oracle extension is not available.');
		}
	}

/**
 * testLastErrorStatement method
 *
 * @access public
 * @return void
 */
	public function testLastErrorStatement() {
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
	public function testLastErrorConnect() {
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
	public function testName() {
		$Db = $this->db;
		#$Db = new DboOracle($config = null, $autoConnect = false);

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
