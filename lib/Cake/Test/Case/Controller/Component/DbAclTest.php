<?php
/**
 * DbAclTest file.
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
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('AclComponent', 'Controller/Component');
App::uses('AclNode', 'Model');
class_exists('AclComponent');

/**
 * AclNodeTwoTestBase class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AclNodeTwoTestBase extends AclNode {

/**
 * useDbConfig property
 *
 * @var string 'test'
 */
	public $useDbConfig = 'test';

/**
 * cacheSources property
 *
 * @var bool false
 */
	public $cacheSources = false;
}

/**
 * AroTwoTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AroTwoTest extends AclNodeTwoTestBase {

/**
 * name property
 *
 * @var string 'AroTwoTest'
 */
	public $name = 'AroTwoTest';

/**
 * useTable property
 *
 * @var string 'aro_twos'
 */
	public $useTable = 'aro_twos';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('AcoTwoTest' => array('with' => 'PermissionTwoTest'));
}

/**
 * AcoTwoTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class AcoTwoTest extends AclNodeTwoTestBase {

/**
 * name property
 *
 * @var string 'AcoTwoTest'
 */
	public $name = 'AcoTwoTest';

/**
 * useTable property
 *
 * @var string 'aco_twos'
 */
	public $useTable = 'aco_twos';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 */
	public $hasAndBelongsToMany = array('AroTwoTest' => array('with' => 'PermissionTwoTest'));
}

/**
 * PermissionTwoTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class PermissionTwoTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'PermissionTwoTest'
 */
	public $name = 'PermissionTwoTest';

/**
 * useTable property
 *
 * @var string 'aros_aco_twos'
 */
	public $useTable = 'aros_aco_twos';

/**
 * cacheQueries property
 *
 * @var bool false
 */
	public $cacheQueries = false;

/**
 * belongsTo property
 *
 * @var array
 */
	public $belongsTo = array('AroTwoTest' => array('foreignKey' => 'aro_id'), 'AcoTwoTest' => array('foreignKey' => 'aco_id'));

/**
 * actsAs property
 *
 * @var mixed null
 */
	public $actsAs = null;
}

/**
 * DbAclTwoTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class DbAclTwoTest extends DbAcl {

/**
 * construct method
 *
 * @return void
 */
	function __construct() {
		$this->Aro = new AroTwoTest();
		$this->Aro->Permission = new PermissionTwoTest();
		$this->Aco = new AcoTwoTest();
		$this->Aro->Permission = new PermissionTwoTest();
	}
}

/**
 * Test case for AclComponent using the DbAcl implementation.
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class DbAclTest extends CakeTestCase {
/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array('core.aro_two', 'core.aco_two', 'core.aros_aco_two');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Acl.classname', 'DbAclTwoTest');
		Configure::write('Acl.database', 'test');
		$Collection = new ComponentCollection();
		$this->Acl = new AclComponent($Collection);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Acl);
	}

/**
 * testAclCreate method
 *
 * @return void
 */
	public function testCreate() {
		$this->Acl->Aro->create(array('alias' => 'Chotchkey'));
		$this->assertTrue((bool)$this->Acl->Aro->save());

		$parent = $this->Acl->Aro->id;

		$this->Acl->Aro->create(array('parent_id' => $parent, 'alias' => 'Joanna'));
		$this->assertTrue((bool)$this->Acl->Aro->save());

		$this->Acl->Aro->create(array('parent_id' => $parent, 'alias' => 'Stapler'));
		$this->assertTrue((bool)$this->Acl->Aro->save());

		$root = $this->Acl->Aco->node('ROOT');
		$parent = $root[0]['AcoTwoTest']['id'];

		$this->Acl->Aco->create(array('parent_id' => $parent, 'alias' => 'Drinks'));
		$this->assertTrue((bool)$this->Acl->Aco->save());

		$this->Acl->Aco->create(array('parent_id' => $parent, 'alias' => 'PiecesOfFlair'));
		$this->assertTrue((bool)$this->Acl->Aco->save());
	}

/**
 * testAclCreateWithParent method
 *
 * @return void
 */
	public function testCreateWithParent() {
		$parent = $this->Acl->Aro->findByAlias('Peter', null, null, -1);
		$this->Acl->Aro->create();
		$this->Acl->Aro->save(array(
			'alias' => 'Subordinate',
			'model' => 'User',
			'foreign_key' => 7,
			'parent_id' => $parent['AroTwoTest']['id']
		));
		$result = $this->Acl->Aro->findByAlias('Subordinate', null, null, -1);
		$this->assertEqual($result['AroTwoTest']['lft'], 16);
		$this->assertEqual($result['AroTwoTest']['rght'], 17);
	}

/**
 * testDbAclAllow method
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testAllow() {
		$this->assertFalse($this->Acl->check('Micheal', 'tpsReports', 'read'));
		$this->assertTrue($this->Acl->allow('Micheal', 'tpsReports', array('read', 'delete', 'update')));
		$this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'update'));
		$this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'read'));
		$this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'delete'));

		$this->assertFalse($this->Acl->check('Micheal', 'tpsReports', 'create'));
		$this->assertTrue($this->Acl->allow('Micheal', 'ROOT/tpsReports', 'create'));
		$this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'create'));
		$this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'delete'));
		$this->assertTrue($this->Acl->allow('Micheal', 'printers', 'create'));
		// Michael no longer has his delete permission for tpsReports!
		$this->assertTrue($this->Acl->check('Micheal', 'tpsReports', 'delete'));
		$this->assertTrue($this->Acl->check('Micheal', 'printers', 'create'));

		$this->assertFalse($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/view'));
		$this->assertTrue($this->Acl->allow('root/users/Samir', 'ROOT/tpsReports/view', '*'));
		$this->assertTrue($this->Acl->check('Samir', 'view', 'read'));
		$this->assertTrue($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/view', 'update'));

		$this->assertFalse($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/update','*'));
		$this->assertTrue($this->Acl->allow('root/users/Samir', 'ROOT/tpsReports/update', '*'));
		$this->assertTrue($this->Acl->check('Samir', 'update', 'read'));
		$this->assertTrue($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/update', 'update'));
		// Samir should still have his tpsReports/view permissions, but does not
		$this->assertTrue($this->Acl->check('root/users/Samir', 'ROOT/tpsReports/view', 'update'));

		$this->assertFalse($this->Acl->allow('Lumbergh', 'ROOT/tpsReports/DoesNotExist', 'create'));
	}

/**
 * testAllowInvalidNode method
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testAllowInvalidNode() {
		$this->Acl->allow('Homer', 'tpsReports', 'create');
	}

/**
 * testDbAclCheck method
 *
 * @return void
 */
	public function testCheck() {
		$this->assertTrue($this->Acl->check('Samir', 'print', 'read'));
		$this->assertTrue($this->Acl->check('Lumbergh', 'current', 'read'));
		$this->assertFalse($this->Acl->check('Milton', 'smash', 'read'));
		$this->assertFalse($this->Acl->check('Milton', 'current', 'update'));

		$this->assertFalse($this->Acl->check(null, 'printers', 'create'));
		$this->assertFalse($this->Acl->check('managers', null, 'read'));

		$this->assertTrue($this->Acl->check('Bobs', 'ROOT/tpsReports/view/current', 'read'));
		$this->assertFalse($this->Acl->check('Samir', 'ROOT/tpsReports/update', 'read'));

		$this->assertFalse($this->Acl->check('root/users/Milton', 'smash', 'delete'));
	}

/**
 * testCheckInvalidNode method
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testCheckInvalidNode() {
		$this->assertFalse($this->Acl->check('WRONG', 'tpsReports', 'read'));
	}

/**
 * testCheckInvalidPermission method
 *
 * @expectedException PHPUnit_Framework_Error_Notice
 * @return void
 */
	public function testCheckInvalidPermission() {
		$this->Acl->check('Lumbergh', 'smash', 'foobar');
	}

/**
 * testCheckMissingPermission method
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testCheckMissingPermission() {
		$this->Acl->check('users', 'NonExistant', 'read');
	}

/**
 * testDbAclCascadingDeny function
 *
 * Setup the acl permissions such that Bobs inherits from admin.
 * deny Admin delete access to a specific resource, check the permisssions are inherited.
 *
 * @return void
 */
	public function testAclCascadingDeny() {
		$this->Acl->inherit('Bobs', 'ROOT', '*');
		$this->assertTrue($this->Acl->check('admin', 'tpsReports', 'delete'));
		$this->assertTrue($this->Acl->check('Bobs', 'tpsReports', 'delete'));
		$this->Acl->deny('admin', 'tpsReports', 'delete');
		$this->assertFalse($this->Acl->check('admin', 'tpsReports', 'delete'));
		$this->assertFalse($this->Acl->check('Bobs', 'tpsReports', 'delete'));
	}

/**
 * testDbAclDeny method
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testDeny() {
		$this->assertTrue($this->Acl->check('Micheal', 'smash', 'delete'));
		$this->Acl->deny('Micheal', 'smash', 'delete');
		$this->assertFalse($this->Acl->check('Micheal', 'smash', 'delete'));
		$this->assertTrue($this->Acl->check('Micheal', 'smash', 'read'));
		$this->assertTrue($this->Acl->check('Micheal', 'smash', 'create'));
		$this->assertTrue($this->Acl->check('Micheal', 'smash', 'update'));
		$this->assertFalse($this->Acl->check('Micheal', 'smash', '*'));

		$this->assertTrue($this->Acl->check('Samir', 'refill', '*'));
		$this->Acl->deny('Samir', 'refill', '*');
		$this->assertFalse($this->Acl->check('Samir', 'refill', 'create'));
		$this->assertFalse($this->Acl->check('Samir', 'refill', 'update'));
		$this->assertFalse($this->Acl->check('Samir', 'refill', 'read'));
		$this->assertFalse($this->Acl->check('Samir', 'refill', 'delete'));

		$result = $this->Acl->Aro->Permission->find('all', array('conditions' => array('AroTwoTest.alias' => 'Samir')));
		$expected = '-1';
		$this->assertEqual($result[0]['PermissionTwoTest']['_delete'], $expected);

		$this->assertFalse($this->Acl->deny('Lumbergh', 'ROOT/tpsReports/DoesNotExist', 'create'));
	}

/**
 * testAclNodeLookup method
 *
 * @return void
 */
	public function testAclNodeLookup() {
		$result = $this->Acl->Aro->node('root/users/Samir');
		$expected = array(
			array('AroTwoTest' => array('id' => '7', 'parent_id' => '4', 'model' => 'User', 'foreign_key' => 3, 'alias' => 'Samir')),
			array('AroTwoTest' => array('id' => '4', 'parent_id' => '1', 'model' => 'Group', 'foreign_key' => 3, 'alias' => 'users')),
			array('AroTwoTest' => array('id' => '1', 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'root'))
		);
		$this->assertEqual($expected, $result);

		$result = $this->Acl->Aco->node('ROOT/tpsReports/view/current');
		$expected = array(
			array('AcoTwoTest' => array('id' => '4', 'parent_id' => '3', 'model' => null, 'foreign_key' => null, 'alias' => 'current')),
			array('AcoTwoTest' => array('id' => '3', 'parent_id' => '2', 'model' => null, 'foreign_key' => null, 'alias' => 'view')),
			array('AcoTwoTest' => array('id' => '2', 'parent_id' => '1', 'model' => null, 'foreign_key' => null, 'alias' => 'tpsReports')),
			array('AcoTwoTest' => array('id' => '1', 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'ROOT')),
		);
		$this->assertEqual($expected, $result);
	}

/**
 * testDbInherit method
 *
 * @return void
 */
	public function testInherit() {
		//parent doesn't have access inherit should still deny
		$this->assertFalse($this->Acl->check('Milton', 'smash', 'delete'));
		$this->Acl->inherit('Milton', 'smash', 'delete');
		$this->assertFalse($this->Acl->check('Milton', 'smash', 'delete'));

		//inherit parent
		$this->assertFalse($this->Acl->check('Milton', 'smash', 'read'));
		$this->Acl->inherit('Milton', 'smash', 'read');
		$this->assertTrue($this->Acl->check('Milton', 'smash', 'read'));
	}

/**
 * testDbGrant method
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testGrant() {
		$this->assertFalse($this->Acl->check('Samir', 'tpsReports', 'create'));
		$this->Acl->allow('Samir', 'tpsReports', 'create');
		$this->assertTrue($this->Acl->check('Samir', 'tpsReports', 'create'));

		$this->assertFalse($this->Acl->check('Micheal', 'view', 'read'));
		$this->Acl->allow('Micheal', 'view', array('read', 'create', 'update'));
		$this->assertTrue($this->Acl->check('Micheal', 'view', 'read'));
		$this->assertTrue($this->Acl->check('Micheal', 'view', 'create'));
		$this->assertTrue($this->Acl->check('Micheal', 'view', 'update'));
		$this->assertFalse($this->Acl->check('Micheal', 'view', 'delete'));

		$this->assertFalse($this->Acl->allow('Peter', 'ROOT/tpsReports/DoesNotExist', 'create'));
	}

/**
 * testDbRevoke method
 *
 * @expectedException PHPUnit_Framework_Error_Warning
 * @return void
 */
	public function testRevoke() {
		$this->assertTrue($this->Acl->check('Bobs', 'tpsReports', 'read'));
		$this->Acl->deny('Bobs', 'tpsReports', 'read');
		$this->assertFalse($this->Acl->check('Bobs', 'tpsReports', 'read'));

		$this->assertTrue($this->Acl->check('users', 'printers', 'read'));
		$this->Acl->deny('users', 'printers', 'read');
		$this->assertFalse($this->Acl->check('users', 'printers', 'read'));
		$this->assertFalse($this->Acl->check('Samir', 'printers', 'read'));
		$this->assertFalse($this->Acl->check('Peter', 'printers', 'read'));

		$this->Acl->deny('Bobs', 'ROOT/printers/DoesNotExist', 'create');
	}
/**
 * debug function - to help editing/creating test cases for the ACL component
 *
 * To check the overal ACL status at any time call $this->__debug();
 * Generates a list of the current aro and aco structures and a grid dump of the permissions that are defined
 * Only designed to work with the db based ACL
 *
 * @param bool $treesToo
 * @return void
 */
	function __debug ($printTreesToo = false) {
		$this->Acl->Aro->displayField = 'alias';
		$this->Acl->Aco->displayField = 'alias';
		$aros = $this->Acl->Aro->find('list', array('order' => 'lft'));
		$acos = $this->Acl->Aco->find('list', array('order' => 'lft'));
		$rights = array('*', 'create', 'read', 'update', 'delete');
		$permissions['Aros v Acos >'] = $acos;
		foreach ($aros as $aro) {
			$row = array();
			foreach ($acos as $aco) {
				$perms = '';
				foreach ($rights as $right) {
					if ($this->Acl->check($aro, $aco, $right)) {
						if ($right == '*') {
							$perms .= '****';
							break;
						}
						$perms .= $right[0];
					} elseif ($right != '*') {
						$perms .= ' ';
					}
				}
				$row[] = $perms;
			}
			$permissions[$aro] = $row;
		}
		foreach ($permissions as $key => $values) {
			array_unshift($values, $key);
			$values = array_map(array(&$this, '__pad'), $values);
			$permissions[$key] = implode (' ', $values);
		}
		$permisssions = array_map(array(&$this, '__pad'), $permissions);
		array_unshift($permissions, 'Current Permissions :');
		if ($printTreesToo) {
			debug(array('aros' => $this->Acl->Aro->generateTreeList(), 'acos' => $this->Acl->Aco->generateTreeList()));
		}
		debug(implode("\r\n", $permissions));
	}

/**
 * pad function
 * Used by debug to format strings used in the data dump
 *
 * @param string $string
 * @param int $len
 * @return void
 */
	function __pad($string = '', $len = 14) {
		return str_pad($string, $len);
	}
}
