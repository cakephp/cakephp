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

use Cake\TestSuite\Fixture\FixtureManager;
use Cake\TestSuite\TestCase;
use Exception;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestListener;
use PHPUnit_Framework_TestSuite;

/**
 * Test listener used to inject a fixture manager in all tests that
 * are composed inside a Test Suite
 */
class FixtureInjector implements PHPUnit_Framework_TestListener {

/**
 * The instance of the fixture manager to use
 *
 * @var \Cake\TestSuite\Fixture\FixtureManager
 */
	protected $_fixtureManager;

/**
 * Holds a reference to the conainer test suite
 *
 * @var \PHPUnit_Framework_TestSuite
 */
	protected $_first;

/**
 * Constructor. Save internally the reference to the passed fixture manager
 *
 * @param \Cake\TestSuite\Fixture\FixtureManager $manager
 */
	public function __construct(FixtureManager $manager) {
		$this->_fixtureManager = $manager;
		$this->_fixtureManager->shutdown();
	}

/**
 * Iterates the tests inside a test suite and creates the required fixtures as
 * they were expressed inside each test case.
 *
 * @param \PHPUnit_Framework_TestSuite $suite
 * @return void
 */
	public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
		if (empty($this->_first)) {
			$this->_first = $suite;
		}
	}

/**
 * Destroys the fixtures created by the fixture manager at the end of the test
 * suite run
 *
 * @param \PHPUnit_Framework_TestSuite $suite
 * @return void
 */
	public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
		if ($this->_first === $suite) {
			$this->_fixtureManager->shutdown();
		}
	}

/**
 * Not Implemented
 *
 * @param PHPUnit_Framework_Test $test
 * @param Exception $e
 * @param float $time
 * @return void
 */
	public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

/**
 * Not Implemented
 *
 * @param PHPUnit_Framework_Test $test
 * @param PHPUnit_Framework_AssertionFailedError $e
 * @param float $time
 * @return void
 */
	public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
	}

/**
 * Not Implemented
 *
 * @param PHPUnit_Framework_Test $test
 * @param Exception $e
 * @param float $time
 * @return void
 */
	public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

/**
 * Not Implemented
 *
 * @param PHPUnit_Framework_Test $test
 * @param Exception $e
 * @param float $time
 * @return void
 */
	public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

/**
 * Not Implemented
 *
 * @param PHPUnit_Framework_Test $test
 * @return void
 */
	public function startTest(PHPUnit_Framework_Test $test) {
		$test->fixtureManager = $this->_fixtureManager;
		if ($test instanceof TestCase) {
			$this->_fixtureManager->fixturize($test);
			$this->_fixtureManager->load($test);
		}
	}

/**
 * Not Implemented
 *
 * @param PHPUnit_Framework_Test $test
 * @param float $time
 * @return void
 */
	public function endTest(PHPUnit_Framework_Test $test, $time) {
		if ($test instanceof TestCase) {
			$this->_fixtureManager->unload($test);
		}
	}

/**
 * Not Implemented
 *
 * @param PHPUnit_Framework_Test $test
 * @param Exception $e
 * @param float $time
 * @return void
 */
	public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

}
