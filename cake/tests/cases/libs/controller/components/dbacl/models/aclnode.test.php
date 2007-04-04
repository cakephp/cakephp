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
 * @subpackage		cake.tests.cases.libs.controller.components.dbacl.models
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	if (!defined('CAKEPHP_UNIT_TEST_EXECUTION')) {
		define('CAKEPHP_UNIT_TEST_EXECUTION', 1);
	}
	require_once LIBS.'model'.DS.'model.php';
	require_once LIBS.'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aclnode.php';
	require_once LIBS.'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'permission.php';
	
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.controller.components.dbacl.models
	 */
	class AclNodeTestBase extends AclNode {
		var $useDbConfig = 'test_suite';
	}
	
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.controller.components.dbacl.models
	 */
	class AroTest extends AclNodeTestBase {
		var $name = 'AroTest';
		var $useTable = 'aros';
		var $hasAndBelongsToMany = array('AcoTest' => array('with' => 'PermissionTest'));
	}
	
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.controller.components.dbacl.models
	 */
	class AcoTest extends AclNodeTestBase {
		var $name = 'AcoTest';
		var $useTable = 'acos';
		var $hasAndBelongsToMany = array('AroTest' => array('with' => 'PermissionTest'));
	}
	
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.controller.components.dbacl.models
	 */
	class PermissionTest extends CakeTestModel {
		var $name = 'PermissionTest';
		var $useTable = 'aros_acos';
		var $cacheQueries = false;
		var $belongsTo = 'AroTest,AcoTest';
		var $actsAs = null;
	}
	
	/**
	 * Short description for class.
	 *
	 * @package		cake.tests
	 * @subpackage	cake.tests.cases.libs.controller.components.dbacl.models
	 */
	class AcoActionTest extends CakeTestModel {
		var $name = 'AcoActionTest';
		var $useTable = 'aco_actions';
		var $belongsTo = 'AcoTest';
	}
	
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.controller.components.dbacl.models
 */
class AclNodeTest extends CakeTestCase {
	var $fixtures = array( 'core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action' );
	
	function testNodeNesting() {
		$this->Aro =& new AroTest();
		
		$this->Aro->create(1, null, 'Food');
		$this->Aro->create(2, null, 'Fruit');
		$this->Aro->create(3, null, 'Red');
		$this->Aro->create(4, null, 'Cherry');
		$this->Aro->create(5, null, 'Yellow');
		$this->Aro->create(6, null, 'Banana');
		$this->Aro->create(7, null, 'Meat');
		$this->Aro->create(8, null, 'Beef');
		$this->Aro->create(9, null, 'Pork');

		$this->Aro->setParent('Food', 'Meat');
		$this->Aro->setParent('Food', 'Fruit');

		$this->Aro->setParent('Fruit', 'Yellow');
		$this->Aro->setParent('Yellow', 'Banana');
		$this->Aro->setParent('Fruit', 'Red');
		$this->Aro->setParent('Red', 'Cherry');

		$this->Aro->setParent('Meat', 'Pork');
		$this->Aro->setParent('Meat', 'Beef');
	}
}

?>