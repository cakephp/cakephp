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
namespace Cake\TestSuite\Fixture;

loadPHPUnitAliases();

use Cake\TestSuite\TestCase;
use PHPUnit\Framework\BaseTestListener;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;

/**
 * Test listener used to inject a fixture manager in all tests that
 * are composed inside a Test Suite
 */
class FixtureInjector extends BaseTestListener
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
            $manager->setDebug(in_array('--debug', $_SERVER['argv'], true));
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
}
