<?php
/**
 * AllCacheTest file
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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * AllCacheTest class
 *
 * This test group will run cache engine tests.
 *
 * @package       Cake.Test.Case
 */
class AllCacheTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Cache related class tests');
		$suite->addTestDirectory(CORE_TEST_CASES . DS . 'Cache');
		$suite->addTestDirectory(CORE_TEST_CASES . DS . 'Cache' . DS . 'Engine');
		return $suite;
	}
}
