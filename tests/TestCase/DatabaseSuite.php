<?php
/**
 * PHP Version 5.4
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
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase;

use Cake\Database\ConnectionManager;
use Cake\TestSuite\TestPermutationDecorator;
use Cake\TestSuite\TestSuite;

/**
 * All tests related to database
 *
 */
class DatabaseSuite extends TestSuite {

/**
 * Returns a suite containing all tests requiring a database connection,
 * tests are decorated so that they are run once with automatic
 *
 * @return void
 */
	public static function suite() {
		$suite = new TestSuite('Database related tests');
		$suite->addTestDirectoryRecursive(__DIR__ . DS . 'Database');
		$suite->addTestDirectoryRecursive(__DIR__ . DS . 'ORM');

		$permutations = [
			'Identifier Quoting' => function() {
				ConnectionManager::get('test')->driver()->autoQuoting(true);
			},
			'No identifier quoting' => function() {
				ConnectionManager::get('test')->driver()->autoQuoting(false);
			}
		];

		$suite = new TestPermutationDecorator($suite, $permutations);
		return $suite;
	}

}
