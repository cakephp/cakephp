<?php
/**
 * AclComponentTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 * @since         CakePHP(tm) v 1.2.0.5435
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
App::import(array('controller' .DS . 'components' . DS . 'acl', 'model' . DS . 'db_acl'));

/**
 * AclNodeTwoTestBase class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class AclNodeTwoTestBase extends AclNode {

/**
 * useDbConfig property
 *
 * @var string 'test_suite'
 * @access public
 */
	var $useDbConfig = 'test_suite';

/**
 * cacheSources property
 *
 * @var bool false
 * @access public
 */
	var $cacheSources = false;
}

/**
 * AroTwoTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class AroTwoTest extends AclNodeTwoTestBase {

/**
 * name property
 *
 * @var string 'AroTwoTest'
 * @access public
 */
	var $name = 'AroTwoTest';

/**
 * useTable property
 *
 * @var string 'aro_twos'
 * @access public
 */
	var $useTable = 'aro_twos';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('AcoTwoTest' => array('with' => 'PermissionTwoTest'));
}

/**
 * AcoTwoTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class AcoTwoTest extends AclNodeTwoTestBase {

/**
 * name property
 *
 * @var string 'AcoTwoTest'
 * @access public
 */
	var $name = 'AcoTwoTest';

/**
 * useTable property
 *
 * @var string 'aco_twos'
 * @access public
 */
	var $useTable = 'aco_twos';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('AroTwoTest' => array('with' => 'PermissionTwoTest'));
}

/**
 * PermissionTwoTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class PermissionTwoTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'PermissionTwoTest'
 * @access public
 */
	var $name = 'PermissionTwoTest';

/**
 * useTable property
 *
 * @var string 'aros_aco_twos'
 * @access public
 */
	var $useTable = 'aros_aco_twos';

/**
 * cacheQueries property
 *
 * @var bool false
 * @access public
 */
	var $cacheQueries = false;

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('AroTwoTest' => array('foreignKey' => 'aro_id'), 'AcoTwoTest' => array('foreignKey' => 'aco_id'));

/**
 * actsAs property
 *
 * @var mixed null
 * @access public
 */
	var $actsAs = null;
}

/**
 * DbAclTwoTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class DbAclTwoTest extends DbAcl {

/**
 * construct method
 *
 * @access private
 * @return void
 */
	function __construct() {
		$this->Aro =& new AroTwoTest();
		$this->Aro->Permission =& new PermissionTwoTest();
		$this->Aco =& new AcoTwoTest();
		$this->Aro->Permission =& new PermissionTwoTest();
	}
}

/**
 * IniAclTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class IniAclTest extends IniAcl {
}

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class AclComponentTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array('core.aro_two', 'core.aco_two', 'core.aros_aco_two');

/**
 * startTest method
 *
 * @access public
 * @return void
 */
	function startTest() {
		$this->Acl =& new AclComponent();
	}

/**
 * before method
 *
 * @param mixed $method
 * @access public
 * @return void
 */
	function before($method) {
		Configure::write('Acl.classname', 'DbAclTwoTest');
		Configure::write('Acl.database', 'test_suite');
		parent::before($method);
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		unset($this->Acl);
	}

/**
 * testAclCreate method
 *
 * @access public
 * @return void
 */
	function testAclCreate() {
		$this->Acl->Aro->create(array('alias' => 'Chotchkey'));
		$this->assertTrue($this->Acl->Aro->save());

		$parent = $this->Acl->Aro->id;

		$this->Acl->Aro->create(array('parent_id' => $parent, 'alias' => 'Joanna'));
		$this->assertTrue($this->Acl->Aro->save());

		$this->Acl->Aro->create(array('parent_id' => $parent, 'alias' => 'Stapler'));
		$this->assertTrue($this->Acl->Aro->save());

		$root = $this->Acl->Aco->node('ROOT');
		$parent = $root[0]['AcoTwoTest']['id'];

		$this->Acl->Aco->create(array('parent_id' => $parent, 'alias' => 'Drinks'));
		$this->assertTrue($this->Acl->Aco->save());

		$this->Acl->Aco->create(array('parent_id' => $parent, 'alias' => 'PiecesOfFlair'));
		$this->assertTrue($this->Acl->Aco->save());
	}

/**
 * testAclCreateWithParent method
 *
 * @access public
 * @return void
 */
	function testAclCreateWithParent() {
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
	function testDbAclAllow() {
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

		$this->expectError('DbAcl::allow() - Invalid node');
		$this->assertFalse($this->Acl->allow('Lumbergh', 'ROOT/tpsReports/DoesNotExist', 'create'));

		$this->expectError('DbAcl::allow() - Invalid node');
		$this->assertFalse($this->Acl->allow('Homer', 'tpsReports', 'create'));
	}

/**
 * testDbAclCheck method
 *
 * @access public
 * @return void
 */
	function testDbAclCheck() {
		$this->assertTrue($this->Acl->check('Samir', 'print', 'read'));
		$this->assertTrue($this->Acl->check('Lumbergh', 'current', 'read'));
		$this->assertFalse($this->Acl->check('Milton', 'smash', 'read'));
		$this->assertFalse($this->Acl->check('Milton', 'current', 'update'));

		$this->expectError("DbAcl::check() - Failed ARO/ACO node lookup in permissions check.  Node references:\nAro: WRONG\nAco: tpsReports");
		$this->assertFalse($this->Acl->check('WRONG', 'tpsReports', 'read'));

		$this->expectError("ACO permissions key foobar does not exist in DbAcl::check()");
		$this->assertFalse($this->Acl->check('Lumbergh', 'smash', 'foobar'));

		$this->expectError("DbAcl::check() - Failed ARO/ACO node lookup in permissions check.  Node references:\nAro: users\nAco: NonExistant");
		$this->assertFalse($this->Acl->check('users', 'NonExistant', 'read'));

		$this->assertFalse($this->Acl->check(null, 'printers', 'create'));
		$this->assertFalse($this->Acl->check('managers', null, 'read'));

		$this->assertTrue($this->Acl->check('Bobs', 'ROOT/tpsReports/view/current', 'read'));
		$this->assertFalse($this->Acl->check('Samir', 'ROOT/tpsReports/update', 'read'));

		$this->assertFalse($this->Acl->check('root/users/Milton', 'smash', 'delete'));
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
	function testDbAclCascadingDeny() {
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
	function testDbAclDeny() {
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

		$this->expectError('DbAcl::allow() - Invalid node');
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
	function testDbInherit() {
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
	function testDbGrant() {
		$this->assertFalse($this->Acl->check('Samir', 'tpsReports', 'create'));
		$this->Acl->grant('Samir', 'tpsReports', 'create');
		$this->assertTrue($this->Acl->check('Samir', 'tpsReports', 'create'));

		$this->assertFalse($this->Acl->check('Micheal', 'view', 'read'));
		$this->Acl->grant('Micheal', 'view', array('read', 'create', 'update'));
		$this->assertTrue($this->Acl->check('Micheal', 'view', 'read'));
		$this->assertTrue($this->Acl->check('Micheal', 'view', 'create'));
		$this->assertTrue($this->Acl->check('Micheal', 'view', 'update'));
		$this->assertFalse($this->Acl->check('Micheal', 'view', 'delete'));

		$this->expectError('DbAcl::allow() - Invalid node');
		$this->assertFalse($this->Acl->grant('Peter', 'ROOT/tpsReports/DoesNotExist', 'create'));
	}

/**
 * testDbRevoke method
 *
 * @access public
 * @return void
 */
	function testDbRevoke() {
		$this->assertTrue($this->Acl->check('Bobs', 'tpsReports', 'read'));
		$this->Acl->revoke('Bobs', 'tpsReports', 'read');
		$this->assertFalse($this->Acl->check('Bobs', 'tpsReports', 'read'));

		$this->assertTrue($this->Acl->check('users', 'printers', 'read'));
		$this->Acl->revoke('users', 'printers', 'read');
		$this->assertFalse($this->Acl->check('users', 'printers', 'read'));
		$this->assertFalse($this->Acl->check('Samir', 'printers', 'read'));
		$this->assertFalse($this->Acl->check('Peter', 'printers', 'read'));

		$this->expectError('DbAcl::allow() - Invalid node');
		$this->assertFalse($this->Acl->deny('Bobs', 'ROOT/printers/DoesNotExist', 'create'));
	}

/**
 * testStartup method
 *
 * @access public
 * @return void
 */
	function testStartup() {
		$controller = new Controller();
		$this->assertTrue($this->Acl->startup($controller));
	}

/**
 * testIniReadConfigFile
 *
 * @access public
 * @return void
 */
	function testIniReadConfigFile() {
		Configure::write('Acl.classname', 'IniAclTest');
		unset($this->Acl);
		$this->Acl = new AclComponent();
		$iniFile = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config'. DS . 'acl.ini.php';
		$result = $this->Acl->_Instance->readConfigFile($iniFile);
		$expected = array(
			'admin' => array(
				'groups' => 'administrators',
				'allow' => '',
				'deny' => 'ads',
			),
			'paul' => array(
				'groups' => 'users',
				'allow' =>'',
				'deny' => '',
			),
			'jenny' => array(
				'groups' => 'users',
				'allow' => 'ads',
				'deny' => 'images, files',
			),
			'nobody' => array(
				'groups' => 'anonymous',
				'allow' => '',
				'deny' => '',
			),
			'administrators' => array(
				'deny' => '',
				'allow' => 'posts, comments, images, files, stats, ads',
			),
			'users' => array(
				'allow' => 'posts, comments, images, files',
				'deny' => 'stats, ads',
			),
			'anonymous' => array(
				'allow' => '',
				'deny' => 'posts, comments, images, files, stats, ads',
			),
		);
		$this->assertEqual($result, $expected);
	}

/**
 * testIniCheck method
 *
 * @access public
 * @return void
 */
	function testIniCheck() {
		Configure::write('Acl.classname', 'IniAclTest');
		unset($this->Acl);
		$iniFile = TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'config'. DS . 'acl.ini.php';

		$this->Acl = new AclComponent();
		$this->Acl->_Instance->config= $this->Acl->_Instance->readConfigFile($iniFile);

		$this->assertFalse($this->Acl->check('admin', 'ads'));
		$this->assertTrue($this->Acl->check('admin', 'posts'));

		$this->assertTrue($this->Acl->check('jenny', 'posts'));
		$this->assertTrue($this->Acl->check('jenny', 'ads'));

		$this->assertTrue($this->Acl->check('paul', 'posts'));
		$this->assertFalse($this->Acl->check('paul', 'ads'));

		$this->assertFalse($this->Acl->check('nobody', 'comments'));
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
			debug (array('aros' => $this->Acl->Aro->generateTreeList(), 'acos' => $this->Acl->Aco->generateTreeList()));
		}
		debug (implode("\r\n", $permissions));
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
