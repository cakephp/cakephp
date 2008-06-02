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
 * @since			CakePHP(tm) v 1.2.0.5517
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/** RoutingSystemGroupTest
 *
 * This test group will run all the Router/Dispatcher (and related) tests
 *
 * @package    cake.tests
 * @subpackage cake.tests.groups
 */
/**
 * RoutingSystemGroupTest class
 * 
 * @package              cake
 * @subpackage           cake.tests.groups
 */
class RoutingSystemGroupTest extends GroupTest {
/**
 * label property
 * 
 * @var string 'Routing system'
 * @access public
 */
	var $label = 'Routing system';
/**
 * RoutingSystemGroupTest method
 * 
 * @access public
 * @return void
 */
	function RoutingSystemGroupTest() {
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'dispatcher');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'router');
	}
}
?>
