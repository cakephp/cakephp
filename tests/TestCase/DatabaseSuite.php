<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestPermutationDecorator;
use Cake\TestSuite\TestSuite;
use \PHPUnit_Framework_TestResult;

/**
 * All tests related to database
 */
class DatabaseSuite extends TestSuite
{

    /**
     * Returns a suite containing all tests requiring a database connection,
     * tests are decorated so that they are run once with automatic
     *
     * @return void
     */
    public static function suite()
    {
        $suite = new self('Database related tests');
        $suite->addTestFile(__DIR__ . DS . 'Database' . DS . 'ConnectionTest.php');
        $suite->addTestDirectoryRecursive(__DIR__ . DS . 'Database');
        $suite->addTestDirectoryRecursive(__DIR__ . DS . 'ORM');

        return $suite;
    }

    public function count()
    {
        return parent::count() * 2;
    }

    /**
     * Runs the tests and collects their result in a TestResult.
     *
     * @param \PHPUnit_Framework_TestResult $result
     * @return \PHPUnit_Framework_TestResult
     */
    public function run(PHPUnit_Framework_TestResult $result = null)
    {
        $permutations = [
            'Identifier Quoting' => function () {
                ConnectionManager::get('test')->driver()->autoQuoting(true);
            },
            'No identifier quoting' => function () {
                ConnectionManager::get('test')->driver()->autoQuoting(false);
            }
        ];

        foreach ($permutations as $permutation) {
            $permutation();
            $result = parent::run($result);
        }

        return $result;
    }
}
