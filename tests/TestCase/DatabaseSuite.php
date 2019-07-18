<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase;

use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestSuite;
use PHPUnit\Framework\TestResult;

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

    public function count($preferCache = false)
    {
        return parent::count($preferCache) * 2;
    }

    /**
     * Runs the tests and collects their result in a TestResult.
     *
     * @param \PHPUnit\Framework\TestResult $result
     * @return \PHPUnit\Framework\TestResult
     */
    public function run(TestResult $result = null)
    {
        $permutations = [
            'Identifier Quoting' => function () {
                ConnectionManager::get('test')->getDriver()->enableAutoQuoting(true);
            },
            'No identifier quoting' => function () {
                ConnectionManager::get('test')->getDriver()->enableAutoQuoting(false);
            }
        ];

        foreach ($permutations as $permutation) {
            $permutation();
            $result = parent::run($result);
        }

        return $result;
    }
}
