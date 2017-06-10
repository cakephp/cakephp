<?php
/**
 * AllNetworkTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * AllNetworkTest class
 *
 * This test group will run socket class tests
 *
 * @package       Cake.Test.Case
 */
class AllNetworkTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Network related class tests');

		$suite->addTestDirectory(CORE_TEST_CASES . DS . 'Network');
		$suite->addTestDirectory(CORE_TEST_CASES . DS . 'Network' . DS . 'Email');
		$suite->addTestDirectory(CORE_TEST_CASES . DS . 'Network' . DS . 'Http');
		return $suite;
	}
}
