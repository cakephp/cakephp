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
 * @package    cake.tests
 * @subpackage cake.tests.cases.libs.controller.components
 */
class AclComponentTest extends CakeTestCase {

	var $fixtures = array('core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action');

	function skip() {
		$this->skipif (false, 'AclComponentTest almost implemented');
	}

	function startTest() {
		Configure::write('Acl.classname', 'DB_ACL');
		Configure::write('Acl.database', 'test_suite');
		$this->Acl =& new AclComponent();
		$this->__testInitDbAcl();
	}

	function __testInitDbAcl() {

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

		$result = $this->Acl->allow('Roles/Admin', 'Root');
		$this->assertTrue($result);

		$result = $this->Acl->allow('Roles/Admin', 'Root/AuthTest');
		$this->assertTrue($result);
	}

	function testDbAclCheck() {

		$aro = null;
		$aco = null;
		$action = "*";

		$result = $this->Acl->check('Roles/Admin', 'Root', $action);
		$this->assertFalse($result);
	}



	function testDbAclDeny() {

		$action = "*";

		$result = $this->Acl->deny('Roles/Admin', 'Root/AuthTest', $action);
		$this->assertTrue($result);

		$result = $this->Acl->check('Roles/Admin', 'Root/AuthTest', $action);
		$this->assertFalse($result);

	}

	function testDbAclInherit() {

		$action = "*";

		$result = $this->Acl->inherit('Roles/Admin', 'Root/AuthTest', $action);
		$this->assertTrue($result);

	}
	function testDbAclGrant() {

		$aro = 'Roles/Admin';
		$aco = 'Root/AuthTest';
		$action = "*";

		$result = $this->Acl->grant($aro, $aco, $action);
		$this->assertTrue($result);

	}
	function testDbAclRevoke() {

		$aro = 'Roles/Admin';
		$aco = 'Root/AuthTest';
		$action = "*";

		$result = $this->Acl->revoke($aro, $aco, $action);
		$this->assertTrue($result);

	}

	function endTest() {
		unset($this->Acl);
	}
}
?>