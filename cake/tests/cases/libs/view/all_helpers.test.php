<?php
/**
 * HelpersGroupTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * HelpersGroupTest class
 *
 * This test group will run all test in the cases/libs/view/helpers directory.
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class AllHelpersTest extends PHPUnit_Framework_TestSuite {

/**
 * suite declares tests to run
 *
 * @access public
 * @return void
 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Helper tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'libs' . DS . 'view' . DS . 'helper.test.php');

		$helperIterator = new DirectoryIterator(CORE_TEST_CASES . DS . 'libs' . DS . 'view' . DS . 'helpers' . DS);

		// The following test cases cause segfaults for me.
		$segfaulty = array('form.test.php', 'cache.test.php', 'session.test.php');

		foreach ($helperIterator as $i => $file) {
			if (!$file->isDot() && !in_array($file->getFilename(), $segfaulty)) {
				$suite->addTestfile($file->getPathname());
			}
		}
		return $suite;
	}
}
