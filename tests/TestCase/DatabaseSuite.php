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

use Cake\Datasource\ConnectionManager;
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
		$suite = new self('Database related tests');
		$suite->addTestDirectoryRecursive(__DIR__ . DS . 'Database');
		$suite->addTestDirectoryRecursive(__DIR__ . DS . 'ORM');
		$suite->addTestDirectoryRecursive(__DIR__ . DS . 'Model');
		return $suite;
	}

/**
 * Returns an iterator for this test suite.
 *
 * @return ArrayIterator
 */
	public function getIterator() {
		$permutations = [
			'Identifier Quoting' => function() {
				ConnectionManager::get('test')->driver()->autoQuoting(true);
			},
			'No identifier quoting' => function() {
				ConnectionManager::get('test')->driver()->autoQuoting(false);
			}
		];

		$tests = [];
		foreach (parent::getIterator() as $test) {
			$tests[] = new TestPermutationDecorator($test, $permutations);
		}

		return new \ArrayIterator($tests);
	}

}
