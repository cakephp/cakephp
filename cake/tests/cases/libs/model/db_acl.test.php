<?php
/**
 * DbAclTest file
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
 * @subpackage    cake.tests.cases.libs.controller.components.dbacl.models
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
	define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
}
App::import('Component', 'Acl');
App::import('Core', 'db_acl');

/**
 * DB ACL wrapper test class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class DbAclNodeTestBase extends AclNode {

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
 * Aro Test Wrapper
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class DbAroTest extends DbAclNodeTestBase {

/**
 * name property
 *
 * @var string 'DbAroTest'
 * @access public
 */
	var $name = 'DbAroTest';

/**
 * useTable property
 *
 * @var string 'aros'
 * @access public
 */
	var $useTable = 'aros';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('DbAcoTest' => array('with' => 'DbPermissionTest'));
}

/**
 * Aco Test Wrapper
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class DbAcoTest extends DbAclNodeTestBase {

/**
 * name property
 *
 * @var string 'DbAcoTest'
 * @access public
 */
	var $name = 'DbAcoTest';

/**
 * useTable property
 *
 * @var string 'acos'
 * @access public
 */
	var $useTable = 'acos';

/**
 * hasAndBelongsToMany property
 *
 * @var array
 * @access public
 */
	var $hasAndBelongsToMany = array('DbAroTest' => array('with' => 'DbPermissionTest'));
}

/**
 * Permission Test Wrapper
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class DbPermissionTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DbPermissionTest'
 * @access public
 */
	var $name = 'DbPermissionTest';

/**
 * useTable property
 *
 * @var string 'aros_acos'
 * @access public
 */
	var $useTable = 'aros_acos';

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
	var $belongsTo = array('DbAroTest' => array('foreignKey' => 'aro_id'), 'DbAcoTest' => array('foreignKey' => 'aco_id'));
}

/**
 * DboActionTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class DbAcoActionTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'DbAcoActionTest'
 * @access public
 */
	var $name = 'DbAcoActionTest';

/**
 * useTable property
 *
 * @var string 'aco_actions'
 * @access public
 */
	var $useTable = 'aco_actions';

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	var $belongsTo = array('DbAcoTest' => array('foreignKey' => 'aco_id'));
}

/**
 * DbAroUserTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class DbAroUserTest extends CakeTestModel {

/**
 * name property
 *
 * @var string 'AuthUser'
 * @access public
 */
	var $name = 'AuthUser';

/**
 * useTable property
 *
 * @var string 'auth_users'
 * @access public
 */
	var $useTable = 'auth_users';
	/**
 * bindNode method
 *
 * @param mixed $ref
 * @access public
 * @return void
 */
	function bindNode($ref = null) {
		if (Configure::read('DbAclbindMode') == 'string') {
			return 'ROOT/admins/Gandalf';
		} elseif (Configure::read('DbAclbindMode') == 'array') {
			return array('DbAroTest' => array('DbAroTest.model' => 'AuthUser', 'DbAroTest.foreign_key' => 2));
		}
	}
}

/**
 * DbAclTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components
 */
class DbAclTest extends DbAcl {

/**
 * construct method
 *
 * @access private
 * @return void
 */
	function __construct() {
		$this->Aro =& new DbAroTest();
		$this->Aro->Permission =& new DbPermissionTest();
		$this->Aco =& new DbAcoTest();
		$this->Aro->Permission =& new DbPermissionTest();
	}
}

/**
 * AclNodeTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs.controller.components.dbacl.models
 */
class AclNodeTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 * @access public
 */
	var $fixtures = array('core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action', 'core.auth_user');

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		Configure::write('Acl.classname', 'DbAclTest');
		Configure::write('Acl.database', 'test_suite');
	}

/**
 * testNode method
 *
 * @access public
 * @return void
 */
	function testNode() {
		$Aco = new DbAcoTest();
		$result = Set::extract($Aco->node('Controller1'), '{n}.DbAcoTest.id');
		$expected = array(2, 1);
		$this->assertEqual($result, $expected);

		$result = Set::extract($Aco->node('Controller1/action1'), '{n}.DbAcoTest.id');
		$expected = array(3, 2, 1);
		$this->assertEqual($result, $expected);

		$result = Set::extract($Aco->node('Controller2/action1'), '{n}.DbAcoTest.id');
		$expected = array(7, 6, 1);
		$this->assertEqual($result, $expected);

		$result = Set::extract($Aco->node('Controller1/action2'), '{n}.DbAcoTest.id');
		$expected = array(5, 2, 1);
		$this->assertEqual($result, $expected);

		$result = Set::extract($Aco->node('Controller1/action1/record1'), '{n}.DbAcoTest.id');
		$expected = array(4, 3, 2, 1);
		$this->assertEqual($result, $expected);

		$result = Set::extract($Aco->node('Controller2/action1/record1'), '{n}.DbAcoTest.id');
		$expected = array(8, 7, 6, 1);
		$this->assertEqual($result, $expected);

		$result = Set::extract($Aco->node('Controller2/action3'), '{n}.DbAcoTest.id');
		$this->assertFalse($result);

		$result = Set::extract($Aco->node('Controller2/action3/record5'), '{n}.DbAcoTest.id');
		$this->assertFalse($result);

		$result = $Aco->node('');
		$this->assertEqual($result, null);
	}
	/**
 * testNodeArrayFind method
 *
 * @access public
 * @return void
 */
	function testNodeArrayFind() {
		$Aro = new DbAroTest();
		Configure::write('DbAclbindMode', 'string');
		$result = Set::extract($Aro->node(array('DbAroUserTest' => array('id' => '1', 'foreign_key' => '1'))), '{n}.DbAroTest.id');
		$expected = array(3, 2, 1);
		$this->assertEqual($result, $expected);

		Configure::write('DbAclbindMode', 'array');
		$result = Set::extract($Aro->node(array('DbAroUserTest' => array('id' => 4, 'foreign_key' => 2))), '{n}.DbAroTest.id');
		$expected = array(4);
		$this->assertEqual($result, $expected);
	}
	/**
 * testNodeObjectFind method
 *
 * @access public
 * @return void
 */
	function testNodeObjectFind() {
		$Aro = new DbAroTest();
		$Model = new DbAroUserTest();
		$Model->id = 1;
		$result = Set::extract($Aro->node($Model), '{n}.DbAroTest.id');
		$expected = array(3, 2, 1);
		$this->assertEqual($result, $expected);

		$Model->id = 2;
		$result = Set::extract($Aro->node($Model), '{n}.DbAroTest.id');
		$expected = array(4, 2, 1);
		$this->assertEqual($result, $expected);

	}

/**
 * testNodeAliasParenting method
 *
 * @access public
 * @return void
 */
	function testNodeAliasParenting() {
		$Aco = new DbAcoTest();
		$db =& ConnectionManager::getDataSource('test_suite');
		$db->truncate($Aco);
		$db->_queriesLog = array();

		$Aco->create(array('model' => null, 'foreign_key' => null, 'parent_id' => null, 'alias' => 'Application'));
		$Aco->save();

		$Aco->create(array('model' => null, 'foreign_key' => null, 'parent_id' => $Aco->id, 'alias' => 'Pages'));
		$Aco->save();

		$result = $Aco->find('all');
		$expected = array(
			array('DbAcoTest' => array('id' => '1', 'parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'Application', 'lft' => '1', 'rght' => '4'), 'DbAroTest' => array()),
			array('DbAcoTest' => array('id' => '2', 'parent_id' => '1', 'model' => null, 'foreign_key' => null, 'alias' => 'Pages', 'lft' => '2', 'rght' => '3', ), 'DbAroTest' => array())
		);
		$this->assertEqual($result, $expected);
	}
}
?>