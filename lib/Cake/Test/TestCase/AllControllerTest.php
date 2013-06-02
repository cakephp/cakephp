<?php
/**
 * AllControllersTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase;

use Cake\TestSuite\TestSuite;

/**
 * AllControllersTest class
 *
 * This test group will run Controller related tests.
 *
 * @package       Cake.Test.Case
 */
class AllControllerTest extends \PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new TestSuite('All Controller related class tests');

		$suite->addTestFile(CORE_TEST_CASES . DS . 'Controller/ControllerTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Controller/ScaffoldTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Controller/PagesControllerTest.php');
		$suite->addTestFile(CORE_TEST_CASES . DS . 'Controller/ComponentTest.php');
		return $suite;
	}
}
