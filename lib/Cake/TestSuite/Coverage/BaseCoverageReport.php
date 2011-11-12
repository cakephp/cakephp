<?php
/**
 * Abstract class for common CoverageReport methods.
 * Provides several template methods for custom output.
 *
 * PHP5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.TestSuite.Coverage
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class BaseCoverageReport {

/**
 * coverage data
 *
 * @var string
 */
	protected $_rawCoverage;

/**
 * is the test an app test
 *
 * @var string
 */
	public $appTest = false;

/**
 * is the test a plugin test
 *
 * @var string
 */
	public $pluginTest = false;

/**
 * Array of test case file names.  Used to do basename() matching with
 * files that have coverage to decide which results to show on page load.
 *
 * @var array
 */
	protected $_testNames = array();

/**
 * Constructor
 *
 * @param array $coverage Array of coverage data from PHPUnit_Test_Result
 * @param CakeBaseReporter $reporter A reporter to use for the coverage report.
 * @return void
 */
	public function __construct($coverage, CakeBaseReporter $reporter) {
		$this->_rawCoverage = $coverage;
		$this->setParams($reporter);
	}

/**
 * Pulls params out of the reporter.
 *
 * @param CakeBaseReporter $reporter Reporter to suck params out of.
 * @return void
 */
	protected function setParams(CakeBaseReporter $reporter) {
		if ($reporter->params['app']) {
			$this->appTest = true;
		}
		if ($reporter->params['plugin']) {
			$this->pluginTest = Inflector::camelize($reporter->params['plugin']);
		}
	}

/**
 * Set the coverage data array
 *
 * @param array $coverage Coverage data to use.
 * @return void
 */
	public function setCoverage($coverage) {
		$this->_rawCoverage = $coverage;
	}

/**
 * Gets the base path that the files we are interested in live in.
 *
 * @return void
 */
	public function getPathFilter() {
		$path = ROOT . DS;
		if ($this->appTest) {
			$path .= APP_DIR . DS;
		} elseif ($this->pluginTest) {
			$path = App::pluginPath($this->pluginTest);
		} else {
			$path = CAKE;
		}
		return $path;
	}

/**
 * Filters the coverage data by path.  Files not in the provided path will be removed.
 *
 * @param string $path Path to filter files by.
 * @return array Array of coverage data for files that match the given path.
 */
	public function filterCoverageDataByPath($path) {
		$files = array();
		foreach ($this->_rawCoverage as $fileName => $fileCoverage) {
			if (strpos($fileName, $path) !== 0) {
				continue;
			}
			$files[$fileName] = $fileCoverage;
		}
		return $files;
	}

/**
 * Calculates how many lines are covered and what the total number of executable lines is.
 *
 * Handles both PHPUnit3.5 and 3.6 formats.
 *
 * 3.5 uses -1 for uncovered, and -2 for dead.
 * 3.6 uses array() for uncovered and null for dead.
 *
 * @param array $fileLines
 * @param array $coverageData
 * @return array. Array of covered, total lines.
 */
	protected function _calculateCoveredLines($fileLines, $coverageData) {
		$covered = $total = 0;

		//shift line numbers forward one
		array_unshift($fileLines, ' ');
		unset($fileLines[0]);

		foreach ($fileLines as $lineno => $line) {
			if (!isset($coverageData[$lineno])) {
				continue;
			}
			if (is_array($coverageData[$lineno]) && !empty($coverageData[$lineno])) {
				$covered++;
				$total++;
			} else if ($coverageData[$lineno] === -1 || $coverageData[$lineno] === array()) {
				$total++;
			}
		}
		return array($covered, $total);
	}

/**
 * Generates report to display.
 *
 * @return string compiled html report.
 */
	abstract public function report();

/**
 * Generates an coverage 'diff' for $file based on $coverageData.
 *
 * @param string $filename Name of the file having coverage generated
 * @param array $fileLines File data as an array. See file() for how to get one of these.
 * @param array $coverageData Array of coverage data to use to generate HTML diffs with
 * @return string prepared report for a single file.
 */
	abstract public function generateDiff($filename, $fileLines, $coverageData);

}
