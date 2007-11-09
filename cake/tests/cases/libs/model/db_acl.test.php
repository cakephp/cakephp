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
class DbAclNodeTestBase extends AclNode {
	var $useDbConfig = 'test_suite';
	var $cacheSources = false;
}

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
class DbAroTest extends DbAclNodeTestBase {
	var $name = 'DbAroTest';
	var $useTable = 'aros';
	var $hasAndBelongsToMany = array('DbAcoTest' => array('with' => 'DbPermissionTest'));
}

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
class DbAcoTest extends DbAclNodeTestBase {
	var $name = 'DbAcoTest';
	var $useTable = 'acos';
	var $hasAndBelongsToMany = array('DbAroTest' => array('with' => 'DbPermissionTest'));
}

/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
class DbPermissionTest extends CakeTestModel {
	var $name = 'DbPermissionTest';
	var $useTable = 'aros_acos';
	var $cacheQueries = false;
	var $belongsTo = array('DbAroTest' => array('foreignKey' => 'aro_id'), 'DbAcoTest' => array('foreignKey' => 'aco_id'));
}
/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
class DbAcoActionTest extends CakeTestModel {
	var $name = 'DbAcoActionTest';
	var $useTable = 'aco_actions';
	var $belongsTo = array('DbAcoTest' => array('foreignKey' => 'aco_id'));
}
/**
* Short description for class.
*
* @package		cake.tests
* @subpackage	cake.tests.cases.libs.controller.components
*/
class DBACL_TEST extends DB_ACL {

	function __construct() {
		$this->Aro =& new DbAroTest();
		$this->Aro->Permission =& new DbPermissionTest();
		$this->Aco =& new DbAcoTest();
		$this->Aro->Permission =& new DbPermissionTest();
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
		
		function setUp() {
			Configure::write('Acl.classname', 'DB_ACL_TEST');
			Configure::write('Acl.database', 'test_suite');
		}
		
		function testNode(){
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
			$expected = array(6, 1);
			$this->assertEqual($result, $expected);

			$result = Set::extract($Aco->node('Controller2/action3/record5'), '{n}.DbAcoTest.id');
			$expected = array(6, 1);
			$this->assertEqual($result, $expected);
		}
	}

?>