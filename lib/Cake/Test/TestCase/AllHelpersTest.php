<?php
/**
 * HelpersGroupTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase;

use Cake\TestSuite\TestSuite;

/**
 * HelpersGroupTest class
 *
 * This test group will run all Helper related tests.
 *
 * @package       Cake.Test.Case
 */
class AllHelpersTest extends \PHPUnit_Framework_TestSuite {

/**
 * suite declares tests to run
 *
 * @return void
 */
	public static function suite() {
		$suite = new TestSuite('All Helper tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'View/HelperTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'View/HelperCollectionTest.php');
		$suite->addTestDirectory(CORE_TEST_CASES . DS . 'View/Helper/');
		return $suite;
	}
}
