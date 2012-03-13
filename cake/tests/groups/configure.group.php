<?php
/**
 * ConfigureGroupTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * ConfigureGroupTest class
 *
 * This test group will run all test for the configure and loader.
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class ConfigureGroupTest extends TestSuite {

/**
 * label property
 *
 * @var string 'Configure, Loader, ClassRegistry Tests'
 * @access public
 */
	var $label = 'Configure, App and ClassRegistry';

/**
 * ConfigureGroupTest method
 *
 * @access public
 * @return void
 */
	function ConfigureGroupTest() {
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'configure');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'class_registry');
	}
}
