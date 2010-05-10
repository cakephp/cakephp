<?php
/**
 * ComponentsGroupTest file
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
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * ComponentsGroupTest class
 *
 * This test group will run all tests in the cases/libs/controller/components directory.
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class ComponentsGroupTest extends TestSuite {

/**
 * label property
 *
 * @var string 'All core components'
 * @access public
 */
	var $label = 'All Components';

/**
 * CoreComponentsGroupTest method
 *
 * @access public
 * @return void
 */
	function ComponentsGroupTest() {
		TestManager::addTestCasesFromDirectory($this, CORE_TEST_CASES . DS . 'libs' . DS . 'controller' . DS . 'components');
	}
}
