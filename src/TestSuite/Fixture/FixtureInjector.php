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
namespace Cake\TestSuite\Fixture;

if (class_exists('PHPUnit_Runner_Version')) {
    if (version_compare(\PHPUnit_Runner_Version::id(), '5.7', '<')) {
        trigger_error(sprintf('Your PHPUnit Version must be at least 5.7.0 to use CakePHP Testsuite, found %s', \PHPUnit_Runner_Version::id()), E_USER_ERROR);
    }
    class_alias('PHPUnit_Framework_Test', 'PHPUnit\Framework\Test');
    class_alias('PHPUnit_Framework_Warning', 'PHPUnit\Framework\Warning');

    if (!class_exists('PHPUnit\Framework\TestSuite')) {
        class_alias('PHPUnit_Framework_TestSuite', 'PHPUnit\Framework\TestSuite');
    }
    if (class_exists('PHPUnit_Runner_Version') && !class_exists('PHPUnit\Framework\AssertionFailedError')) {
        class_alias('PHPUnit_Framework_AssertionFailedError', 'PHPUnit\Framework\AssertionFailedError');
    }
}

use Cake\TestSuite\TestCase;
use Exception;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;

/**
 * Test listener used to inject a fixture manager in all tests that
 * are composed inside a Test Suite
 */
class FixtureInjector implements TestListener
{

    /**
     * The instance of the fixture manager to use
     *
     * @var \Cake\TestSuite\Fixture\FixtureManager
     */
    protected $_fixtureManager;

    /**
     * Holds a reference to the container test suite
     *
     * @var \PHPUnit\Framework\TestSuite
     */
    protected $_first;

    /**
     * Constructor. Save internally the reference to the passed fixture manager
     *
     * @param \Cake\TestSuite\Fixture\FixtureManager $manager The fixture manager
     */
    public function __construct(FixtureManager $manager)
    {
        if (isset($_SERVER['argv'])) {
            $manager->setDebug(in_array('--debug', $_SERVER['argv']));
        }
        $this->_fixtureManager = $manager;
        $this->_fixtureManager->shutDown();
    }

    /**
     * Iterates the tests inside a test suite and creates the required fixtures as
     * they were expressed inside each test case.
     *
     * @param \PHPUnit\Framework\TestSuite $suite The test suite
     * @return void
     */
    public function startTestSuite(TestSuite $suite)
    {
        if (empty($this->_first)) {
            $this->_first = $suite;
        }
    }

    /**
     * Destroys the fixtures created by the fixture manager at the end of the test
     * suite run
     *
     * @param \PHPUnit\Framework\TestSuite $suite The test suite
     * @return void
     */
    public function endTestSuite(TestSuite $suite)
    {
        if ($this->_first === $suite) {
            $this->_fixtureManager->shutDown();
        }
    }

    /**
     * Not Implemented
     *
     * @param \PHPUnit\Framework\Test $test The test to add errors from.
     * @param \Exception $e The exception
     * @param float $time current time
     * @return void
     */
    public function addError(Test $test, Exception $e, $time)
    {
    }

    /**
     * Not Implemented
     *
     * @param \PHPUnit\Framework\Test $test The test to add warnings from.
     * @param \PHPUnit\Framework\Warning $e The warning
     * @param float $time current time
     * @return void
     */
    public function addWarning(Test $test, Warning $e, $time)
    {
    }

    /**
     * Not Implemented
     *
     * @param \PHPUnit\Framework\Test $test The test case
     * @param \PHPUnit\Framework\AssertionFailedError $e The failed assertion
     * @param float $time current time
     * @return void
     */
    public function addFailure(Test $test, AssertionFailedError $e, $time)
    {
    }

    /**
     * Not Implemented
     *
     * @param \PHPUnit\Framework\Test $test The test case
     * @param \Exception $e The incomplete test error.
     * @param float $time current time
     * @return void
     */
    public function addIncompleteTest(Test $test, Exception $e, $time)
    {
    }

    /**
     * Not Implemented
     *
     * @param \PHPUnit\Framework\Test $test The test case
     * @param \Exception $e Skipped test exception
     * @param float $time current time
     * @return void
     */
    public function addSkippedTest(Test $test, Exception $e, $time)
    {
    }

    /**
     * Adds fixtures to a test case when it starts.
     *
     * @param \PHPUnit\Framework\Test $test The test case
     * @return void
     */
    public function startTest(Test $test)
    {
        $test->fixtureManager = $this->_fixtureManager;
        if ($test instanceof TestCase) {
            $this->_fixtureManager->fixturize($test);
            $this->_fixtureManager->load($test);
        }
    }

    /**
     * Unloads fixtures from the test case.
     *
     * @param \PHPUnit\Framework\Test $test The test case
     * @param float $time current time
     * @return void
     */
    public function endTest(Test $test, $time)
    {
        if ($test instanceof TestCase) {
            $this->_fixtureManager->unload($test);
        }
    }

    /**
     * Not Implemented
     *
     * @param \PHPUnit\Framework\Test $test The test case
     * @param \Exception $e The exception to track
     * @param float $time current time
     * @return void
     */
    public function addRiskyTest(Test $test, Exception $e, $time)
    {
    }
}
