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
	require_once LIBS.'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aco.php';
	require_once LIBS.'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'aro.php';
	require_once LIBS.'controller'.DS.'components'.DS.'dbacl'.DS.'models'.DS.'permission.php';
/**
 * Short description for class.
 *
 * @package		cake.tests
 * @subpackage	cake.tests.cases.libs.controller.components.dbacl.models
 */
class AclNodeTest extends UnitTestCase {

	function setUp() {
		$this->aro =& new Aro();
	}

	function testNodeNesting() {
		$this->aro->create(1, null, 'Food');
		$this->aro->create(2, null, 'Fruit');
		$this->aro->create(3, null, 'Red');
		$this->aro->create(4, null, 'Cherry');
		$this->aro->create(5, null, 'Yellow');
		$this->aro->create(6, null, 'Banana');
		$this->aro->create(7, null, 'Meat');
		$this->aro->create(8, null, 'Beef');
		$this->aro->create(9, null, 'Pork');

		$this->aro->setParent('Food', 'Meat');
		$this->aro->setParent('Food', 'Fruit');

		$this->aro->setParent('Fruit', 'Yellow');
		$this->aro->setParent('Yellow', 'Banana');
		$this->aro->setParent('Fruit', 'Red');
		$this->aro->setParent('Red', 'Cherry');

		$this->aro->setParent('Meat', 'Pork');
		$this->aro->setParent('Meat', 'Beef');
	}
}

?>