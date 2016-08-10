<?php
/**
 * AllDbRelatedTest file
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
 * @since         CakePHP(tm) v 2.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * AllDbRelatedTest class
 *
 * This test group will run db related tests.
 *
 * @package       Cake.Test.Case
 */
class PostgresTest extends PHPUnit_Framework_TestSuite {

	/**
	 * Suite define the tests for this suite
	 *
	 * @return void
	 */
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('All Db Related Tests');

		$path = CORE_TEST_CASES . DS;

		$suite->addTestFile($path . 'Model' . DS . 'ModelTest.php');
		return $suite;
	}
}
