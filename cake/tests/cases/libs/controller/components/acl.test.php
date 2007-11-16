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
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2007, Cake Software Foundation, Inc.
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
					$inserts = $fixture->insert();

					if (isset($inserts) && !empty($inserts)) {
						foreach ($inserts as $query) {
							if (isset($query) && $query !== false) {
								$this->db->execute($query);
							}
						}
					}
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
		$result = $this->Acl->Aro->save();
		$this->assertTrue($result);

		$parent = $this->Acl->Aro->id;

		$this->Acl->Aro->create(array('parent_id' => $parent, 'alias'=>'Account'));
		$result = $this->Acl->Aro->save();
		$this->assertTrue($result);

		$this->Acl->Aro->create(array('parent_id' => $parent, 'alias'=>'Manager'));
		$result = $this->Acl->Aro->save();
		$this->assertTrue($result);

		$parent = $this->Acl->Aro->id;

		$this->Acl->Aro->create(array('parent_id' => $parent, 'alias'=>'Secretary'));
		$result = $this->Acl->Aro->save();
		$this->assertTrue($result);

		$this->Acl->Aco->create(array('alias' => 'Reports'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);

		$report = $this->Acl->Aco->id;

		$this->Acl->Aco->create(array('parent_id' => $report, 'alias'=>'Accounts'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);

		$account = $this->Acl->Aco->id;

		$this->Acl->Aco->create(array('parent_id' => $account, 'alias'=>'Contacts'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);

		$this->Acl->Aco->create(array('parent_id' => $report, 'alias'=>'Messages'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);

		$this->Acl->Aco->create(array('parent_id' => $account, 'alias'=>'MonthView'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);

		$this->Acl->Aco->create(array('parent_id' => $account, 'alias'=>'Links'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);

		$this->Acl->Aco->create(array('parent_id' => $account, 'alias'=>'Numbers'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);

		$this->Acl->Aco->create(array('parent_id' => $report, 'alias'=>'QuickStats'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);

		$this->Acl->Aco->create(array('parent_id' => $report, 'alias'=>'Bills'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);
	}

	function testDbAclAllow() {
		$result = $this->Acl->allow('Manager', 'Reports', array('read','delete','update'));
		$this->assertTrue($result);

		$result = $this->Acl->allow('Secretary', 'Links', array('create'));
		$this->assertTrue($result);
	}

	function testDbAclCheck() {

		$result = $this->Acl->check('Secretary','Links','read');
		$this->assertTrue($result);

		$result = $this->Acl->check('Secretary','Links','delete');
		$this->assertTrue($result);

		$result = $this->Acl->check('Secretary','Links','update');
		$this->assertTrue($result);

		$result = $this->Acl->check('Secretary','Links','create');
		$this->assertTrue($result);

		$result = $this->Acl->check('Secretary','Links','*');
		$this->assertTrue($result);

		$result = $this->Acl->check('Secretary','Links','create');
		$this->assertTrue($result);

		$result = $this->Acl->check('Manager','Links','read');
		$this->assertTrue($result);

		$result = $this->Acl->check('Manager','Links','delete');
		$this->assertTrue($result);

		$result = $this->Acl->check('Manager','Links','create');
		$this->assertFalse($result);

		$result = $this->Acl->check('Account','Links','read');
		$this->assertFalse($result);

		$result = $this->Acl->allow('Global','Reports', 'read');
		$this->assertTrue($result);

		$result = $this->Acl->check('Account','Links','create');
		$this->assertFalse($result);

		$result = $this->Acl->check('Account','Links','update');
		$this->assertFalse($result);

		$result = $this->Acl->check('Account','Links','delete');
		$this->assertFalse($result);

		$result = $this->Acl->allow('Global','Reports');
		$this->assertTrue($result);

		$result = $this->Acl->check('Account','Links','read');
		$this->assertTrue($result);
	}

	function testDbAclDeny() {
		$this->Acl->deny('Secretary','Links',array('delete'));

		$result = $this->Acl->check('Secretary','Links','delete');
		$this->assertFalse($result);
	}

	function tearDown() {
	}
}

?>