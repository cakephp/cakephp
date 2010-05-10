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
 * Number of lines to provide around an uncovered code block
 *
 * @var integer
 */
	public $numDiffContextLines = 7;

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
		if (empty($coverageData)) {
			return '<h3>No files to generate coverage for</h3>';
		}
		$output = $this->coverageScript();
		foreach ($coverageData as $file => $coverageData) {
			$fileData = file($file);
			$output .= $this->generateDiff($file, $fileData, $coverageData);
		}
		return $output;
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
				$dead = isset($testRun['dead'][$filename]) ? $testRun['dead'][$filename] : array();
				$executable = isset($testRun['executable'][$filename]) ? $testRun['executable'][$filename] : array();
		
				if (!isset($files[$filename])) {
					$files[$filename] = array(
						'covered' => array(),
						'dead' => array(),
						'executable' => array()
					);
				}
				$files[$filename]['covered'] += $fileCoverage;
				$files[$filename]['executable'] += $executable;
				$files[$filename]['dead'] += $dead;
			}
		}
		ksort($files);
		return $files;
	}

/**
 * Generates an HTML diff for $file based on $coverageData.
 *
 * @param array $fileData File data as an array. See file() for how to get one of these.
 * @param array $coverageData Array of coverage data to use to generate HTML diffs with
 * @return string HTML diff.
 */
	function generateDiff($filename, $fileLines, $coverageData) {
		$output = ''; 
		$diff = array();
		$covered = 0;
		$total = 0;

		//shift line numbers forward one;
		array_unshift($fileLines, ' ');
		unset($fileLines[0]);

		foreach ($fileLines as $lineno => $line) {
			$class = 'ignored';
			if (isset($coverageData['covered'][$lineno])) {
				$class = 'covered';
				$covered++;
				$total++;
			} elseif (isset($coverageData['executable'][$lineno])) {
				$class = 'uncovered';
				$total++;
			} elseif (isset($coverageData['dead'][$lineno])) {
				$class .= ' dead';
			}
			$diff[] = $this->_paintLine($line, $lineno, $class);
		}

		$percentCovered = round(100 * $covered / $total, 2);

		$output .= $this->coverageHeader($filename, $percentCovered);
		$output .= implode("", $diff);
		$output .= $this->coverageFooter();
		return $output;
	}

/**
 * Renders the html for a single line in the html diff.
 *
 * @return void
 */
	protected function _paintLine($line, $linenumber, $class) {
		return sprintf(
			'<div class="code-line %s"><span class="line-num">%s</span><span class="content">%s</span></div>',
			$class,
			$linenumber,
			htmlspecialchars($line)
		);
	}

/**
 * generate some javascript for the coverage report.
 *
 * @return void
 */
	public function coverageScript() {
		return <<<HTML
		<script type="text/javascript">
		function coverage_show_hide(selector) {
			var element = document.getElementById(selector);
			element.style.display = (element.style.display == 'none') ? '' : 'none';
		}
		</script>
HTML;
	}

/**
 * Generate an HTML snippet for coverage headers
 *
 * @return void
 */
	public function coverageHeader($filename, $percent) {
		$filename = basename($filename);
		return <<<HTML
	<h4>
		<a href="#coverage-$filename" onclick="coverage_show_hide('coverage-$filename');">
			$filename Code coverage: $percent%
		</a>
	</h4>
	<div class="code-coverage-results" id="coverage-$filename" style="display:none;">
	<pre>
HTML;
	}

/**
 * Generate an HTML snippet for coverage footers
 *
 * @return void
 */
	public function coverageFooter() {
		return "</pre></div>";
	}
}