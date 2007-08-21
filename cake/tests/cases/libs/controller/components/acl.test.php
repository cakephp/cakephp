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
uses('controller' . DS . 'components' . DS .'acl');

uses('controller'.DS.'components'.DS.'acl', 'model'.DS.'db_acl');

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('aclnodetestbase')) {
	class AclNodeTestBase extends AclNode {
		var $useDbConfig = 'test_suite';
	}
}

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('arotest')) {
	class AroTest extends AclNodeTestBase {
		var $name = 'AroTest';
		var $useTable = 'aros';
		var $hasAndBelongsToMany = array('AcoTest' => array('with' => 'PermissionTest'));
	}
}

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('acotest')) {
	class AcoTest extends AclNodeTestBase {
		var $name = 'AcoTest';
		var $useTable = 'acos';
		var $hasAndBelongsToMany = array('AroTest' => array('with' => 'PermissionTest'));
	}
}

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('permissiontest')) {
	class PermissionTest extends CakeTestModel {
		var $name = 'PermissionTest';
		var $useTable = 'aros_acos';
		var $cacheQueries = false;
		var $belongsTo = array('AroTest' => array('foreignKey' => 'aro_id'),
								'AcoTest' => array('foreignKey' => 'aco_id')
								);
		var $actsAs = null;
	}
}
/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('acoactiontest')) {
	class AcoActionTest extends CakeTestModel {
		var $name = 'AcoActionTest';
		var $useTable = 'aco_actions';
		var $belongsTo = array('AcoTest' => array('foreignKey' => 'aco_id'));
	}
}
/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
if(!class_exists('db_acl_test')) {
	class DB_ACL_TEST extends DB_ACL {

		function __construct() {
			$this->Aro =& new AroTest();
			$this->Aro->Permission =& new PermissionTest();
			$this->Aco =& new AcoTest();
			$this->Aro->Permission =& new PermissionTest();
		}
	}
}
/**
 * Short description for class.
 *
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller.components
 */
class AclComponentTest extends CakeTestCase {
	var $name = 'AclComponent';

	var $fixtures = array('core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action');

	function skip() {
		$this->skipif (true, 'AclComponentTest almost implemented');
	}

	function setUp() {
		$this->Acl =& new AclComponent();
	}

	function testInitDbAcl() {
		$this->Acl->name = 'DB_ACL_TEST';
		$controller = null;
		$this->Acl->startup($controller);

		$this->Acl->Aro->id = null;
		$this->Acl->Aro->create(array('alias'=>'Roles'));
		$result = $this->Acl->Aro->save();
		$this->assertTrue($result);

		$this->Acl->Aro->create(array('alias'=>'Admin'));
		$result = $this->Acl->Aro->save();
		$this->assertTrue($result);

		$this->Acl->Aro->create(array('model'=>'AuthUser', 'foreign_key'=>'1', 'alias'=> 'mariano'));
		$result = $this->Acl->Aro->save();
		$this->assertTrue($result);

		$this->Acl->Aro->setParent(1, 2);
		$this->Acl->Aro->setParent(2, 3);

		$this->Acl->Aco->create(array('alias'=>'Root'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);

		$this->Acl->Aco->create(array('alias'=>'AuthTest'));
		$result = $this->Acl->Aco->save();
		$this->assertTrue($result);

		$this->Acl->Aco->setParent(1, 2);

	}

	function testDbAclAllow() {
		$this->Acl->name = 'DB_ACL_TEST';
		$controller = null;
		$this->Acl->startup($controller);

		$result = $this->Acl->allow('Roles/Admin', 'Root');
		$this->assertTrue($result);

		$result = $this->Acl->allow('Roles/Admin', 'Root/AuthTest');
		$this->assertTrue($result);

	}

	function testDbAclCheck() {
		$this->Acl->name = 'DB_ACL_TEST';
		$controller = null;
		$this->Acl->startup($controller);

		$aro = null;
		$aco = null;
		$action = "*";

		$result = $this->Acl->check('Roles/Admin', 'Root', $action);
		$this->assertFalse($result);
	}



	function testDbAclDeny() {
		$this->Acl->name = 'DB_ACL_TEST';
		$controller = null;
		$this->Acl->startup($controller);

		$result = $this->Acl->deny('Roles/Admin', 'Root/AuthTest', $action);
		$this->assertTrue($result);

		$result = $this->Acl->check('Roles/Admin', 'Root/AuthTest', $action);
		$this->assertFalse($result);

	}

	function testDbAclInherit() {
		$this->Acl->name = 'DB_ACL_TEST';
		$controller = null;
		$this->Acl->startup($controller);

		$result = $this->Acl->inherit('Roles/Admin', 'Root/AuthTest', $action);
		$this->assertTrue($result);

	}
	function testDbAclGrant() {
		$this->Acl->name = 'DB_ACL_TEST';
		$controller = null;
		$this->Acl->startup($controller);

		$aro = null;
		$aco = null;
		$action = "*";

		$result = $this->Acl->grant($aro, $aco, $action);
		$this->assertTrue($result);

	}
	function testDbAclRevoke() {
		$this->Acl->name = 'DB_ACL_TEST';
		$controller = null;
		$this->Acl->startup($controller);

		$aro = null;
		$aco = null;
		$action = "*";

		$result = $this->Acl->revoke($aro, $aco, $action);
		$this->assertTrue($result);

	}

	function tearDown() {
		unset($this->Acl);
	}
}
?>