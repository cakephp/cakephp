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
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake.tests
 * @subpackage		cake.tests.groups
 * @since			CakePHP(tm) v 1.2.0.4206
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/** AllCoreHelpersGroupTest
 *
 * This test group will run all tests in the cases/libs/controller/components directory.
 *
 * @package    cake.tests
 * @subpackage cake.tests.groups
 */
/**
 * AllCoreComponentsGroupTest class
 * 
 * @package              cake
 * @subpackage           cake.tests.groups
 */
class AllCoreComponentsGroupTest extends GroupTest {
/**
 * label property
 * 
 * @var string 'All core components'
 * @access public
 */
	var $label = 'All core components';
/**
 * AllCoreComponentsGroupTest method
 * 
 * @access public
 * @return void
 */
	function AllCoreComponentsGroupTest() {
		TestManager::addTestCasesFromDirectory($this, CORE_TEST_CASES . DS . 'libs' . DS . 'controller' . DS . 'components');
	}
}
?>
