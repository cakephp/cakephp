<?php
/**
 * AllDatabaseTest file
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
namespace Cake\Test\TestCase;

use Cake\TestSuite\TestSuite;

/**
 * AllDatabaseTest class
 *
 * This test group will run database tests not in model or behavior group.
 *
 * @package       Cake.Test.Case
 */
class AllDatabaseTest extends \PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$path = CORE_TEST_CASES . DS . 'Database';

		$suite = new TestSuite('Connection, Datasources and Query builder');
		$suite->addTestDirectoryRecursive($path);
		return $suite;
	}
}
