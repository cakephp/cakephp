<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 * 
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP Test Suite <https://trac.cakephp.org/wiki/Cake/Manual/TestSuite/>
 * Copyright (c) 2005, CakePHP Test Suite Authors/Developers
 *
 * Author(s): Larry E. Masters aka PhpNut <nut@phpnut.com>
 *            Kamil Dzielinski aka Brego <brego.dk@gmail.com>
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource 
 * @author       CakePHP Test Suite Authors/Developers
 * @copyright    Copyright (c) 2005, CakePHP Test Suite Authors/Developers 
 * @link         https://trac.cakephp.org/wiki/TestSuite/Authors/ Authors/Developers
 * @package      test_suite
 * @since        CakePHP Test Suite v 1.0.0.0
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
  * Load Test Manager Class
  */


/** AllButExamplesTest
 * 
 * This test group will run all test in the cases
 * directory with the exception of examples in the
 * examples directory.
 *
 * @todo implement, nothing coded yet!!!
 * 
 * @package    test_suite
 * @subpackage test_suite.cases.app
 * @since      CakePHP Test Suite v 1.0.0.0
 */
class AllButExamplesTest extends GroupTest 
{
    function AllButExamplesTest()
    {
        $this->GroupTest('All but examples test cases');
        TestManager::addTestCasesFromDirectory($this,
                                               CORE_TEST_CASES . '/app');
        TestManager::addTestCasesFromDirectory($this,
                                               CORE_TEST_CASES . '/config');
        TestManager::addTestCasesFromDirectory($this,
                                               CORE_TEST_CASES . '/libs');
        TestManager::addTestCasesFromDirectory($this,
                                               CORE_TEST_CASES . '/logs');
        TestManager::addTestCasesFromDirectory($this,
                                               CORE_TEST_CASES . '/modules');
        TestManager::addTestCasesFromDirectory($this,
                                               CORE_TEST_CASES . '/public/');
        TestManager::addTestCasesFromDirectory($this,
                                               CORE_TEST_CASES . '/scripts');
        TestManager::addTestCasesFromDirectory($this,
                                               CORE_TEST_CASES . '/tmp');
    }
}

?>