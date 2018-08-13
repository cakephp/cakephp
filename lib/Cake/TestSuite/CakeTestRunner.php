<?php
/**
 * TestRunner for CakePHP Test suite.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

if (!class_exists('PHPUnit_TextUI_TestRunner')) {
	require_once 'PHPUnit/TextUI/TestRunner.php';
}
if (class_exists('SebastianBergmann\CodeCoverage\CodeCoverage')) {
	class_alias('SebastianBergmann\CodeCoverage\CodeCoverage', 'PHP_CodeCoverage');
	class_alias('SebastianBergmann\CodeCoverage\Report\Text', 'PHP_CodeCoverage_Report_Text');
	class_alias('SebastianBergmann\CodeCoverage\Report\PHP', 'PHP_CodeCoverage_Report_PHP');
	class_alias('SebastianBergmann\CodeCoverage\Report\Clover', 'PHP_CodeCoverage_Report_Clover');
	class_alias('SebastianBergmann\CodeCoverage\Report\Html\Facade', 'PHP_CodeCoverage_Report_HTML');
	class_alias('SebastianBergmann\CodeCoverage\Exception', 'PHP_CodeCoverage_Exception');
}

App::uses('CakeFixtureManager', 'TestSuite/Fixture');

/**
 * A custom test runner for CakePHP's use of PHPUnit.
 *
 * @package       Cake.TestSuite
 */
class CakeTestRunner extends PHPUnit_TextUI_TestRunner {

/**
 * Lets us pass in some options needed for CakePHP's webrunner.
 *
 * @param mixed $loader The test suite loader
 * @param array $params list of options to be used for this run
 */
	public function __construct($loader, $params) {
		parent::__construct($loader);
		$this->_params = $params;
	}

/**
 * Actually run a suite of tests. Cake initializes fixtures here using the chosen fixture manager
 *
 * @param PHPUnit_Framework_Test $suite The test suite to run
 * @param array $arguments The CLI arguments
 * @param bool $exit Exits by default or returns the results
 * @return void
 */
	public function doRun(PHPUnit_Framework_Test $suite, array $arguments = array(), $exit = true) {
		if (isset($arguments['printer'])) {
			static::$versionStringPrinted = true;
		}

		$fixture = $this->_getFixtureManager($arguments);
		$iterator = $suite->getIterator();
		if ($iterator instanceof RecursiveIterator) {
			$iterator = new RecursiveIteratorIterator($iterator);
		}
		foreach ($iterator as $test) {
			if ($test instanceof CakeTestCase) {
				$fixture->fixturize($test);
				$test->fixtureManager = $fixture;
			}
		}

		$return = parent::doRun($suite, $arguments);
		$fixture->shutdown();
		return $return;
	}

// @codingStandardsIgnoreStart PHPUnit overrides don't match CakePHP
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
// @codingStandardsIgnoreEnd

/**
 * Get the fixture manager class specified or use the default one.
 *
 * @param array $arguments The CLI arguments.
 * @return mixed instance of a fixture manager.
 * @throws RuntimeException When fixture manager class cannot be loaded.
 */
	protected function _getFixtureManager($arguments) {
		if (!empty($arguments['fixtureManager'])) {
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
