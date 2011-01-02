<?php
/**
 * AclComponentTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.cases.libs.controller.components
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Component', 'Acl');
App::import('model' . DS . 'db_acl');

/**
 * AclNodeTwoTestBase class
 *
 * @package       cake.tests.cases.libs.controller.components
 */
class AclNodeTwoTestBase extends AclNode {

/**
 * useDbConfig property
 *
 * @var string 'test'
 * @access public
 */
	public $useDbConfig = 'test';

/**
 * cacheSources property
 *
 * @var bool false
 * @access public
 */
	public $cacheSources = false;
}

/**
 * AroTwoTest class
 *
 * @package       cake.tests.cases.libs.controller.components
 */
class AroTwoTest extends AclNodeTwoTestBase {

/**
 * name property
 *
 * @var string 'AroTwoTest'
 * @access public
 */
	public $name = 'AroTwoTest';

/**
 * useTable property
 *
 * @var string 'aro_twos'
 * @access public
 */
	public $useTable = 'aro_twos';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('AcoTwoTest' => array('with' => 'PermissionTwoTest'));
}

/**
 * AcoTwoTest class
 *
 * @package       cake.tests.cases.libs.controller.components
 */
class AcoTwoTest extends AclNodeTwoTestBase {

/**
 * name property
 *
 * @var string 'AcoTwoTest'
 * @access public
 */
	public $name = 'AcoTwoTest';

/**
 * useTable property
 *
 * @var string 'aco_twos'
 * @access public
 */
	public $useTable = 'aco_twos';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('AroTwoTest' => array('with' => 'PermissionTwoTest'));
}

/**
 * PermissionTwoTest class
 *
 * @package       cake.tests.cases.libs.controller.components
 */
class PermissionTwoTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'PermissionTwoTest'
 * @access public
 */
	public $name = 'PermissionTwoTest';

/**
 * useTable property
 *
 * @var string 'aros_aco_twos'
 * @access public
 */
	public $useTable = 'aros_aco_twos';

/**
 * cacheQueries property
 *
 * @var bool false
 * @access public
 */
	public $cacheQueries = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('AroTwoTest' => array('foreignKey' => 'aro_id'), 'AcoTwoTest' => array('foreignKey' => 'aco_id'));

/**
 * actsAs property
 *
 * @var mixed null
 * @access public
 */
	public $actsAs = null;
}

/**
 * DbAclTwoTest class
 *
 * @package       cake.tests.cases.libs.controller.components
 */
class DbAclTwoTest extends DbAcl {

/**
 * construct method
 *
 * @access private
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
 * Short description for class.
 *
 * @package       cake.tests.cases.libs.controller.components
 */
class AclComponentTest extends CakeTestCase {
/**
 * setUp method
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		if (!class_exists('MockAclImplementation', false)) {
			$this->getMock('AclInterface', array(), array(), 'MockAclImplementation');
		}
		Configure::write('Acl.classname', 'MockAclImplementation');
		$Collection = new ComponentCollection();
		$this->Acl = new AclComponent($Collection);
	}

/**
 * tearDown method
 *
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		unset($this->Acl);
	}

/**
 * test that construtor throws an exception when Acl.classname is a 
 * non-existant class
 *
 * @expectedException CakeException
 * @return void
 */
	function testConstrutorException() {
		Configure::write('Acl.classname', 'AclClassNameThatDoesNotExist');
		$Collection = new ComponentCollection();
		$acl = new AclComponent($Collection);
	}

/**
 * test that adapter() allows control of the interal implementation AclComponent uses.
 *
 * @return void
 */
	function testAdapter() {
		$implementation = new MockAclImplementation();
		$implementation->expects($this->once())->method('initialize')->with($this->Acl);
		$this->assertNull($this->Acl->adapter($implementation));

		$this->assertEqual($this->Acl->adapter(), $implementation, 'Returned object is different %s');
	}

/**
 * test that adapter() whines when the class is not an AclBase
 *
 * @expectedException CakeException
 * @return void
 */
	function testAdapterException() {
		$thing = new StdClass();
		$this->Acl->adapter($thing);
	}

}

/**
 * Test case for the IniAcl implementation
 *
 * @package cake.tests.cases.libs.controller.components
 */
class IniAclTest extends CakeTestCase {

/**
 * testIniCheck method
 *
 * @access public
 * @return void
 */
	function testCheck() {
		$iniFile = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config'. DS . 'acl.ini.php';

		$Ini = new IniAcl();
		$Ini->config = $Ini->readConfigFile($iniFile);

		$this->assertFalse($Ini->check('admin', 'ads'));
		$this->assertTrue($Ini->check('admin', 'posts'));

		$this->assertTrue($Ini->check('jenny', 'posts'));
		$this->assertTrue($Ini->check('jenny', 'ads'));

		$this->assertTrue($Ini->check('paul', 'posts'));
		$this->assertFalse($Ini->check('paul', 'ads'));

		$this->assertFalse($Ini->check('nobody', 'comments'));
	}

/**
 * check should accept a user array.
 *
 * @return void
 */
	function testCheckArray() {
		$iniFile = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config'. DS . 'acl.ini.php';

		$Ini = new IniAcl();
		$Ini->config = $Ini->readConfigFile($iniFile);
		$Ini->userPath = 'User.username';

		$user = array(
			'User' => array('username' => 'admin')
		);
		$this->assertTrue($Ini->check($user, 'posts'));
	}
}


/**
 * Test case for AclComponent using the DbAcl implementation.
 *
 * @package cake.tests.cases.libs.controller.components
 */
class DbAclTest extends CakeTestCase {
/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.aro_two', 'core.aco_two', 'core.aros_aco_two');

/**
 * setUp method
 *
 * @return void
 */
	function setUp() {
		parent::setUp();
		Configure::write('Acl.classname', 'DbAclTwoTest');
		Configure::write('Acl.database', 'test');
		$Collection = new ComponentCollection();
		$this->Acl = new AclComponent($Collection);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		parent::tearDown();
		unset($this->Acl);
	}

/**
 * testAclCreate method
 *
 * @access public
 * @return void
 */
	function testCreate() {
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
 * @access public
 * @return void
 */
	function testCreateWithParent() {
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
 * @access public
 * @return void
 */
	function testAllow() {
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

		$this->expectError();
		$this->assertFalse($this->Acl->allow('Lumbergh', 'ROOT/tpsReports/DoesNotExist', 'create'));
	}

/**
 * testAllowInvalidNode method
 *
 * @access public
 * @return void
 */
	public function testAllowInvalidNode() {
		$this->expectError();
		$this->Acl->allow('Homer', 'tpsReports', 'create');
	}

/**
 * testDbAclCheck method
 *
 * @access public
 * @return void
 */
	function testCheck() {
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
 * @access public
 * @return void
 */
	public function testCheckInvalidNode() {
		$this->expectError();
		$this->assertFalse($this->Acl->check('WRONG', 'tpsReports', 'read'));
	}

/**
 * testCheckInvalidPermission method
 *
 * @access public
 * @return void
 */
	public function testCheckInvalidPermission() {
		$this->expectError();
		$this->assertFalse($this->Acl->check('Lumbergh', 'smash', 'foobar'));
	}

/**
 * testCheckMissingPermission method
 *
 * @access public
 * @return void
 */
	public function testCheckMissingPermission() {
		$this->expectError();
		$this->assertFalse($this->Acl->check('users', 'NonExistant', 'read'));
	}

/**
 * testDbAclCascadingDeny function
 *
 * Setup the acl permissions such that Bobs inherits from admin.
 * deny Admin delete access to a specific resource, check the permisssions are inherited.
 *
 * @access public
 * @return void
 */
	function testAclCascadingDeny() {
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
 * @access public
 * @return void
 */
	function testDeny() {
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

		$this->expectError();
		$this->assertFalse($this->Acl->deny('Lumbergh', 'ROOT/tpsReports/DoesNotExist', 'create'));
	}

/**
 * testAclNodeLookup method
 *
 * @access public
 * @return void
 */
	function testAclNodeLookup() {
		$result = $this->Acl->Aro->node('root/users/Samir');
		$expected = array(
			array('AroTwoTest' => array('id' => '7', 'parent_id' => '4', 'model' => 'User', 'foreign_key' => 3, 'alias' => 'Samir')),
			array('AroTwoTest' => array('id' => '4', 'parent_id' => '1', 'model' => 'Group', 'foreign_key' => 3, 'alias' => 'users')),
			array('AroTwoTest' => array('id' => '1', 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'root'))
		);
		$this->assertEqual($result, $expected);

		$result = $this->Acl->Aco->node('ROOT/tpsReports/view/current');
		$expected = array(
			array('AcoTwoTest' => array('id' => '4', 'parent_id' => '3', 'model' => null, 'foreign_key' => null, 'alias' => 'current')),
			array('AcoTwoTest' => array('id' => '3', 'parent_id' => '2', 'model' => null, 'foreign_key' => null, 'alias' => 'view')),
			array('AcoTwoTest' => array('id' => '2', 'parent_id' => '1', 'model' => null, 'foreign_key' => null, 'alias' => 'tpsReports')),
			array('AcoTwoTest' => array('id' => '1', 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'ROOT')),
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testDbInherit method
 *
 * @access public
 * @return void
 */
	function testInherit() {
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
 * @access public
 * @return void
 */
	function testGrant() {
		$this->assertFalse($this->Acl->check('Samir', 'tpsReports', 'create'));
		$this->Acl->allow('Samir', 'tpsReports', 'create');
		$this->assertTrue($this->Acl->check('Samir', 'tpsReports', 'create'));

		$this->assertFalse($this->Acl->check('Micheal', 'view', 'read'));
		$this->Acl->allow('Micheal', 'view', array('read', 'create', 'update'));
		$this->assertTrue($this->Acl->check('Micheal', 'view', 'read'));
		$this->assertTrue($this->Acl->check('Micheal', 'view', 'create'));
		$this->assertTrue($this->Acl->check('Micheal', 'view', 'update'));
		$this->assertFalse($this->Acl->check('Micheal', 'view', 'delete'));

		$this->expectError();
		$this->assertFalse($this->Acl->allow('Peter', 'ROOT/tpsReports/DoesNotExist', 'create'));
	}

/**
 * testDbRevoke method
 *
 * @access public
 * @return void
 */
	function testRevoke() {
		$this->assertTrue($this->Acl->check('Bobs', 'tpsReports', 'read'));
		$this->Acl->deny('Bobs', 'tpsReports', 'read');
		$this->assertFalse($this->Acl->check('Bobs', 'tpsReports', 'read'));

		$this->assertTrue($this->Acl->check('users', 'printers', 'read'));
		$this->Acl->deny('users', 'printers', 'read');
		$this->assertFalse($this->Acl->check('users', 'printers', 'read'));
		$this->assertFalse($this->Acl->check('Samir', 'printers', 'read'));
		$this->assertFalse($this->Acl->check('Peter', 'printers', 'read'));

		$this->expectError();
		$this->assertFalse($this->Acl->deny('Bobs', 'ROOT/printers/DoesNotExist', 'create'));
	}
/**
 * debug function - to help editing/creating test cases for the ACL component
 *
 * To check the overal ACL status at any time call $this->__debug();
 * Generates a list of the current aro and aco structures and a grid dump of the permissions that are defined
 * Only designed to work with the db based ACL
 *
 * @param bool $treesToo
 * @access private
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
 * @access private
 * @return void
 */
	function __pad($string = '', $len = 14) {
		return str_pad($string, $len);
	}
}
