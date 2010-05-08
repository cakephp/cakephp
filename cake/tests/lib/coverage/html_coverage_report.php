<?php
/**
 * Generates code coverage reports in HTML from data obtained from PHPUnit
 *
 * PHP5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class HtmlCoverageReport {
/**
 * coverage data
 *
 * @var string
 */
	protected $_rawCoverage;

	public $appTest = false;
	public $pluginTest = false;
	public $groupTest = false;

/**
 * Constructor
 *
 * @param array $coverage Array of coverage data from PHPUnit_Test_Result
 * @return void
 */
	public function __construct($coverage, CakeBaseReporter $reporter) {
		$this->_rawCoverage = $coverage;
		$this->setParams($reporter);
	}

/**
 * Pulls params out of the reporter.
 *
 * @return void
 */
	protected function setParams(CakeBaseReporter $reporter) {
		if ($reporter->params['app']) {
			$this->appTest = true;
		}
		if ($reporter->params['group']) {
			$this->groupTest = true;
		}
		if ($reporter->params['plugin']) {
			$this->pluginTest = Inflector::underscore($reporter->params['plugin']);
		}
	}

/**
 * Set the coverage data array
 *
 * @return void
 */
	public function setCoverage($coverage) {
		$this->_rawCoverage = $coverage;
	}

/**
 * Generates report html to display.
 *
 * @return string compiled html report.
 */
	public function report() {
		$pathFilter = $this->getPathFilter();
		$coverageData = $this->filterCoverageDataByPath($pathFilter);
	}

/**
 * Gets the base path that the files we are interested in live in.
 * If appTest ist
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
			$path = TEST_CAKE_CORE_INCLUDE_PATH;
		}
		return $path;
	}

/**
 * Filters the coverage data by path.  Files not in the provided path will be removed.
 * This method will merge all the various test run reports as well into a single report per file.
 *
 * @param string $path Path to filter files by.
 * @return array Array of coverage data for files that match the given path.
 */
	public function filterCoverageDataByPath($path) {
		$files = array();
		foreach ($this->_rawCoverage as $testRun) {
			foreach ($testRun['files'] as $filename => $fileCoverage) {
				if (strpos($filename, $path) !== 0) {
					continue;
				}
				if (!isset($files[$filename])) {
					$files[$filename] = array();
				}
				foreach ($fileCoverage as $line => $value) {
					if (!isset($files[$filename][$line])) {
						$files[$filename][$line] = $value;
					} elseif ($files[$filename][$line] < $value) {
						$files[$filename][$line] = $value;
					}
				}
			}
		}
		return $files;
	}
}