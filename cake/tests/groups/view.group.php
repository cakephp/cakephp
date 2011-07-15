<?php
/**
 * ViewsGroupTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * ViewsGroupTest class
 *
 * This test group will run view class tests (view, theme)
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class ViewsGroupTest extends TestSuite {

/**
 * label property
 *
 * @var string 'All core views'
 * @access public
 */
	var $label = 'View and ThemeView';

/**
 * ViewsGroupTest method
 *
 * @access public
 * @return void
 */
	function ViewsGroupTest() {
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'view' . DS . 'view');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'view' . DS . 'theme');
	}
}
