<?php
/**
 * TestRunner for CakePHP Test suite.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once 'PHPUnit/TextUI/TestRunner.php';

/**
 * A custom test runner for Cake's use of PHPUnit.
 *
 * @package       Cake.TestSuite
 */
class CakeTestRunner extends PHPUnit_TextUI_TestRunner {
/**
 * Lets us pass in some options needed for cake's webrunner.
 *
 * @return void
 */
	public function __construct($loader, $params) {
		parent::__construct($loader);
		$this->_params = $params;
	}

/**
 * Actually run a suite of tests.  Cake initializes fixtures here using the chosen fixture manager
 *
 * @param PHPUnit_Framework_Test $suite
 * @param array $arguments
 * @return void
 */
	public function doRun(PHPUnit_Framework_Test $suite, array $arguments = array()) {
		if (isset($arguments['printer'])) {
			self::$versionStringPrinted = true;
		}

		$fixture = $this->_getFixtureManager($arguments);
		foreach ($suite->getIterator() as $test) {
			if ($test instanceof CakeTestCase) {
				$fixture->fixturize($test);
				$test->fixtureManager = $fixture;
			}
		}

		$return = parent::doRun($suite, $arguments);
		$fixture->shutdown();
		return $return;
	}

/**
 * Create the test result and splice on our code coverage reports.
 *
 * @return PHPUnit_Framework_TestResult
 */
	protected function createTestResult() {
		$result = new PHPUnit_Framework_TestResult;
		if (!empty($this->_params['codeCoverage'])) {
			if (method_exists($result, 'collectCodeCoverageInformation')) {
				$result->collectCodeCoverageInformation(true);
			}
			if (method_exists($result, 'setCodeCoverage')) {
				$result->setCodeCoverage(new PHP_CodeCoverage());
			}
		}
		return $result;
    }

/**
 * Get the fixture manager class specified or use the default one.
 *
 * @return instance of a fixture manager.
 */
	protected function _getFixtureManager($arguments) {
		if (isset($arguments['fixtureManager'])) {
			App::uses($arguments['fixtureManager'], 'TestSuite');
			if (class_exists($arguments['fixtureManager'])) {
				return new $arguments['fixtureManager'];
			}
			throw new RuntimeException(__d('cake_dev', 'Could not find fixture manager %s.', $arguments['fixtureManager']));
		}
		App::uses('AppFixtureManager', 'TestSuite');
		if (class_exists('AppFixtureManager')) {
			return new AppFixtureManager();
		}
		return new CakeFixtureManager();
	}
}
