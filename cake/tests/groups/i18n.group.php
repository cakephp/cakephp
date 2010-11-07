<?php
/**
 * i18nGroupTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * i18nGroupTest class
 *
 * This test group will run all tests related to i18n/l10n and multibyte
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class i18nGroupTest extends TestSuite {

/**
 * label property
 *
 * @var string
 * @access public
 */
	var $label = 'i18n, l10n and multibyte tests';

/**
 * LibGroupTest method
 *
 * @access public
 * @return void
 */
	function i18nGroupTest() {
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'i18n');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'l10n');
		TestManager::addTestFile($this, CORE_TEST_CASES . DS . 'libs' . DS . 'multibyte');
	}
}
