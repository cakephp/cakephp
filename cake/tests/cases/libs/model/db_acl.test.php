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
		var $cacheSources = false;
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
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.controller.components.dbacl.models
 */
	class AclNodeTest extends CakeTestCase {
		var $fixtures = array('core.aro', 'core.aco', 'core.aros_aco', 'core.aco_action');

		function testNodeNesting() {
		}

		function testNode(){
			$aco = new AcoTest();
			$result = Set::extract($aco->node('Controller1'), '{n}.AcoTest.id');
			$expected = array(2, 1);
			$this->assertEqual($result, $expected);

			$result = Set::extract($aco->node('Controller1/action1'), '{n}.AcoTest.id');
			$expected = array(3, 2, 1);
			$this->assertEqual($result, $expected);

			$result = Set::extract($aco->node('Controller2/action1'), '{n}.AcoTest.id');
			$expected = array(7, 6, 1);
			$this->assertEqual($result, $expected);

			$result = Set::extract($aco->node('Controller1/action2'), '{n}.AcoTest.id');
			$expected = array(5, 2, 1);
			$this->assertEqual($result, $expected);

			$result = Set::extract($aco->node('Controller1/action1/record1'), '{n}.AcoTest.id');
			$expected = array(4, 3, 2, 1);
			$this->assertEqual($result, $expected);

			$result = Set::extract($aco->node('Controller2/action1/record1'), '{n}.AcoTest.id');
			$expected = array(8, 7, 6, 1);
			$this->assertEqual($result, $expected);

			//action3 is an action with no ACO entry
			//the default returned ACOs should be its parents
			$result = Set::extract($aco->node('Controller2/action3'), '{n}.AcoTest.id');
			$expected = array(6, 1);
			$this->assertEqual($result, $expected);

			//action3 and record5 have none ACO entry
			//the default returned ACOs should be their parents ACO
			$result = Set::extract($aco->node('Controller2/action3/record5'), '{n}.AcoTest.id');
			$expected = array(6, 1);
			$this->assertEqual($result, $expected);

		}
	}

?>