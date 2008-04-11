<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.cases.libs.controller.components
 * @since			CakePHP(tm) v 1.2.0.5435
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
uses('controller' . DS . 'components' . DS .'acl');

uses('controller'.DS.'components'.DS.'acl', 'model'.DS.'db_acl');

class AclNodeTestBase extends AclNode {
	var $useDbConfig = 'test_suite';
	var $cacheSources = false;
}
class AroTest extends AclNodeTestBase {
	var $name = 'AroTest';
	var $useTable = 'aros';
	var $hasAndBelongsToMany = array('AcoTest' => array('with' => 'PermissionTest'));
}
class AcoTest extends AclNodeTestBase {
	var $name = 'AcoTest';
	var $useTable = 'acos';
	var $hasAndBelongsToMany = array('AroTest' => array('with' => 'PermissionTest'));
}
class PermissionTest extends CakeTestModel {
	var $name = 'PermissionTest';
	var $useTable = 'aros_acos';
	var $cacheQueries = false;
	var $belongsTo = array('AroTest' => array('foreignKey' => 'aro_id'), 'AcoTest' => array('foreignKey' => 'aco_id'));
	var $actsAs = null;
}
class AcoActionTest extends CakeTestModel {
	var $name = 'AcoActionTest';
	var $useTable = 'aco_actions';
	var $belongsTo = array('AcoTest' => array('foreignKey' => 'aco_id'));
}
class DB_ACL_TEST extends DB_ACL {

	function __construct() {
		$this->Aro =& new AroTest();
		$this->Aro->Permission =& new PermissionTest();
		$this->Aco =& new AcoTest();
		$this->Aro->Permission =& new PermissionTest();
	}
}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller.components
 */
class AclComponentTest extends CakeTestCase {

	var $fixtures = array('core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action');

	function start() {
	}

	function startTest() {
		$this->Acl =& new AclComponent();
	}

	function before() {
		if (!isset($this->_initialized)) {
			Configure::write('Acl.classname', 'DB_ACL_TEST');
			Configure::write('Acl.database', 'test_suite');
			if (isset($this->fixtures) && (!is_array($this->fixtures) || empty($this->fixtures))) {
				unset($this->fixtures);
			}

			// Set up DB connection
			if (isset($this->fixtures)) {
				$this->_initDb();
				$this->_loadFixtures();
			}
			parent::start();

			// Create records
			if (isset($this->_fixtures) && isset($this->db)) {
				foreach ($this->_fixtures as $fixture) {
					$fixture->insert($this->db);
				}
			}

			$this->startTest();
			$this->_initialized = true;
		}
	}

	function after() {
	}

	function testAclCreate() {
		$this->Acl->Aro->create(array('alias' => 'Global'));
		$this->assertTrue($this->Acl->Aro->save());

		$parent = $this->Acl->Aro->id;

		$this->Acl->Aro->create(array('parent_id' => $parent, 'alias' => 'Account'));
		$this->assertTrue($this->Acl->Aro->save());

		$this->Acl->Aro->create(array('parent_id' => $parent, 'alias' => 'Manager'));
		$this->assertTrue($this->Acl->Aro->save());

		$parent = $this->Acl->Aro->id;

		$this->Acl->Aro->create(array('parent_id' => $parent, 'alias' => 'Secretary'));
		$this->assertTrue($this->Acl->Aro->save());

		$this->Acl->Aco->create(array('alias' => 'Reports'));
		$this->assertTrue($this->Acl->Aco->save());

		$report = $this->Acl->Aco->id;

		$this->Acl->Aco->create(array('parent_id' => $report, 'alias' => 'Accounts'));
		$this->assertTrue($this->Acl->Aco->save());

		$account = $this->Acl->Aco->id;

		$this->Acl->Aco->create(array('parent_id' => $account, 'alias' => 'Contacts'));
		$this->assertTrue($this->Acl->Aco->save());

		$this->Acl->Aco->create(array('parent_id' => $report, 'alias' => 'Messages'));
		$this->assertTrue($this->Acl->Aco->save());

		$this->Acl->Aco->create(array('parent_id' => $account, 'alias' => 'MonthView'));
		$this->assertTrue($this->Acl->Aco->save());

		$this->Acl->Aco->create(array('parent_id' => $account, 'alias' => 'Links'));
		$this->assertTrue($this->Acl->Aco->save());

		$this->Acl->Aco->create(array('parent_id' => $account, 'alias' => 'Numbers'));
		$this->assertTrue($this->Acl->Aco->save());

		$this->Acl->Aco->create(array('parent_id' => $report, 'alias' => 'QuickStats'));
		$this->assertTrue($this->Acl->Aco->save());

		$this->Acl->Aco->create(array('parent_id' => $report, 'alias' => 'Bills'));
		$this->assertTrue($this->Acl->Aco->save());
	}

	function testDbAclAllow() {
		$this->assertTrue($this->Acl->allow('Manager', 'Reports', array('read', 'delete', 'update')));

		$this->assertFalse($this->Acl->check('Manager', 'Reports', 'create'));
		$this->assertFalse($this->Acl->check('Secretary', 'Links', 'create'));

		$this->assertTrue($this->Acl->allow('Secretary', 'Links', array('create')));

		$this->assertFalse($this->Acl->check('Manager', 'Reports', 'create'));
		$this->assertTrue($this->Acl->check('Secretary', 'Links', 'create'));

		$this->expectError('DB_ACL::allow() - Invalid node');
		$this->assertFalse($this->Acl->allow('Manager', 'Links/DoesNotExist', 'create'));
	}

	function testDbAclCheck() {
		$this->assertTrue($this->Acl->check('Secretary', 'Links', 'read'));
		$this->assertTrue($this->Acl->check('Secretary', 'Links', 'delete'));
		$this->assertTrue($this->Acl->check('Secretary', 'Links', 'update'));
		$this->assertTrue($this->Acl->check('Secretary', 'Links', 'create'));
		$this->assertTrue($this->Acl->check('Secretary', 'Links', '*'));
		$this->assertTrue($this->Acl->check('Secretary', 'Links', 'create'));
		$this->assertTrue($this->Acl->check('Manager', 'Links', 'read'));
		$this->assertTrue($this->Acl->check('Manager', 'Links', 'delete'));
		$this->assertFalse($this->Acl->check('Manager', 'Links', 'create'));
		$this->assertFalse($this->Acl->check('Account', 'Links', 'read'));

		$this->assertTrue($this->Acl->allow('Global', 'Reports', 'read'));

		$this->assertFalse($this->Acl->check('Account', 'Reports', 'create'));
		$this->assertTrue($this->Acl->check('Account', 'Reports', 'read'));
		$this->assertFalse($this->Acl->check('Account', 'Reports', 'update'));
		$this->assertFalse($this->Acl->check('Account', 'Reports', 'delete'));

		$this->assertFalse($this->Acl->check('Account', 'Links', 'create'));
		$this->assertFalse($this->Acl->check('Account', 'Links', 'update'));
		$this->assertFalse($this->Acl->check('Account', 'Links', 'delete'));

		$this->assertTrue($this->Acl->allow('Global', 'Reports'));

		$this->assertTrue($this->Acl->check('Account', 'Links', 'read'));
	}

	function testDbAclDeny() {
		$this->assertTrue($this->Acl->check('Secretary', 'Links', 'delete'));

		$this->Acl->allow('Secretary', 'Links', 'read');
		$result = $this->Acl->Aro->Permission->find('all', array('conditions' => array('AroTest.alias' => 'Secretary')));
		$expected = array('id' => '2', 'aro_id' => '4', 'aco_id' => '15', '_create' => '1', '_read' => '1', '_update' => '0', '_delete' => '0');
		$this->assertEqual($result[0]['PermissionTest'], $expected);

		$this->Acl->deny('Secretary', 'Links', 'delete');
		$result = $this->Acl->Aro->Permission->find('all', array('conditions' => array('AroTest.alias' => 'Secretary')));
		$expected['_delete'] = '-1';
		$this->assertEqual($result[0]['PermissionTest'], $expected);

		$this->assertFalse($this->Acl->check('Secretary', 'Links', 'delete'));
		$this->assertTrue($this->Acl->check('Secretary', 'Links', 'read'));
		$this->assertTrue($this->Acl->check('Secretary', 'Links', 'create'));
		$this->assertTrue($this->Acl->check('Secretary', 'Links', 'update'));

		$this->Acl->deny('Secretary', 'Links', '*');

		$this->assertFalse($this->Acl->check('Secretary', 'Links', 'delete'));
		$this->assertFalse($this->Acl->check('Secretary', 'Links', 'read'));
		$this->assertFalse($this->Acl->check('Secretary', 'Links', 'create'));
		$this->assertFalse($this->Acl->check('Secretary', 'Links', 'update'));
		$this->assertFalse($this->Acl->check('Secretary', 'Links'));

		$this->Acl->Aro->create(array('alias' => 'Tele'));
		$this->assertTrue($this->Acl->Aro->save());

		$this->Acl->Aco->create(array('alias' => 'Tobies'));
		$this->assertTrue($this->Acl->Aco->save());

		$this->Acl->allow('Tele', 'Tobies', array('read', 'update', 'delete'));
		$this->Acl->deny('Tele', 'Tobies', array('delete'));
		$result = $this->Acl->Aro->Permission->find('all', array('conditions' => array('AroTest.alias' => 'Tele')));
		$expected = array('id' => '4', 'aro_id' => '5', 'aco_id' => '19', '_create' => '0', '_read' => '1', '_update' => '1', '_delete' => '-1');
		$this->assertEqual($result[0]['PermissionTest'], $expected);
	}

	function testAclNodeLookup() {
		$result = $this->Acl->Aro->node('Global/Manager/Secretary');
		$expected = array(
			array('AroTest' => array('id' => '4', 'parent_id' => '3', 'model' => null, 'foreign_key' => null, 'alias' => 'Secretary')),
			array('AroTest' => array('id' => '3', 'parent_id' => '1', 'model' => null, 'foreign_key' => null, 'alias' => 'Manager')),
			array('AroTest' => array('id' => '1', 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'Global'))
		);
		$this->assertEqual($result, $expected);
		//die('Working');
	}

	function tearDown() {
	}
}

?>