<?php
/**
 * A class to manage all aspects for Code Coverage Analysis
 *
 * This class
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Folder');

/**
 * Short description for class.
 *
 * @package       cake
 * @subpackage    cake.cake.tests.lib
 */
class CodeCoverageManager {

/**
 * Is this an app test case?
 *
 * @var string
 */
	var $appTest = false;

/**
 * Is this an app test case?
 *
 * @var string
 */
	var $pluginTest = false;

/**
 * Is this a grouptest?
 *
 * @var string
 * @access public
 */
	var $groupTest = false;

/**
 * The test case file to analyze
 *
 * @var string
 */
	var $testCaseFile = '';

/**
 * The currently used CakeTestReporter
 *
 * @var string
 */
	var $reporter = '';

/**
 * undocumented variable
 *
 * @var string
 */
	var $numDiffContextLines = 7;

/**
 * Returns a singleton instance
 *
 * @return object
 * @access public
 */
	function &getInstance() {
		static $instance = array();
		if (!$instance) {
			$instance[0] =& new CodeCoverageManager();
		}
		return $instance[0];
	}

/**
 * Initializes a new Coverage Analyzation for a given test case file
 *
 * @param string $testCaseFile The test case file being covered.
 * @param object $reporter Instance of the reporter running.
 * @return void
 * @static
 */
	function init($testCaseFile, &$reporter) {
		$manager =& CodeCoverageManager::getInstance();
		$manager->reporter =& $reporter;
		$testCaseFile = str_replace(DS . DS, DS, $testCaseFile);
		$thisFile = str_replace('.php', '.test.php', basename(__FILE__));

		if (strpos($testCaseFile, $thisFile) !== false) {
			trigger_error(__('Xdebug supports no parallel coverage analysis - so this is not possible.', true), E_USER_ERROR);
		}
		$manager->setParams($reporter);
		$manager->testCaseFile = $testCaseFile;
	}

/**
 * Start/resume Code coverage collection.
 *
 * @return void
 * @static
 */
	function start() {
		xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
	}

/**
 * Stops/pauses code coverage collection. Does not clean the
 * code coverage memory. Use clean() to clear code coverage memory
 *
 * @return void
 * @static
 */
	function stop() {
		xdebug_stop_code_coverage(false);
	}

/**
 * Clears the existing code coverage information.  Also stops any
 * running collection.
 *
 * @return void
 * @static
 */
	function clear() {
		xdebug_stop_code_coverage();
	}

/**
 * Set the parameters from a reporter to the CodeCoverageManager
 *
 * @return void
 */
	function setParams(&$reporter) {
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
 * Stops the current code coverage analyzation and dumps a nice report
 * depending on the reporter that was passed to start()
 *
 * @return void
 * @static
 */
	function report($output = true) {
		$manager =& CodeCoverageManager::getInstance();

		CodeCoverageManager::stop();
		CodeCoverageManager::clear();

		list($coverageData, $testObjectFile) = $manager->_getCoverageData();

		if (empty($coverageData) && $output) {
			echo "The test object file is never loaded.\n";
		}

		if (!$manager->groupTest) {
			$execCodeLines = $manager->__getExecutableLines(file_get_contents($testObjectFile));
			$result = '';

			switch (get_class($manager->reporter)) {
				case 'CakeHtmlReporter':
					$result = $manager->reportCaseHtmlDiff(@file($testObjectFile), $coverageData, $execCodeLines, $manager->numDiffContextLines);
					break;
				case 'CakeCliReporter':
				default:
					$result = $manager->reportCaseCli(@file($testObjectFile), $coverageData, $execCodeLines, $manager->numDiffContextLines);
					break;
			}
		} else {
			$execCodeLines = $manager->__getExecutableLines($testObjectFile);
			$result = '';

			switch (get_class($manager->reporter)) {
				case 'CakeHtmlReporter':
					$result = $manager->reportGroupHtml($testObjectFile, $coverageData, $execCodeLines, $manager->numDiffContextLines);
					break;
				case 'CakeCliReporter':
				default:
					$result = $manager->reportGroupCli($testObjectFile, $coverageData, $execCodeLines, $manager->numDiffContextLines);
					break;
			}
		}

		if ($output) {
			echo $result;
		}
	}

/**
 * Gets the coverage data for the test case or group test that is being run.
 *
 * @return void
 */
	function _getCoverageData() {
		$coverageData = array();
		$dump = xdebug_get_code_coverage();

		if ($this->groupTest) {
			$testObjectFile = $this->__testObjectFilesFromGroupFile($this->testCaseFile, $this->appTest);
			foreach ($testObjectFile as $file) {
				if (!file_exists($file)) {
					trigger_error(sprintf(__('This test object file is invalid: %s', true), $file));
					return ;
				}
			}
			foreach ($testObjectFile as $file) {
				if (isset($dump[$file])) {
					$coverageData[$file] = $dump[$file];
				}
			}
		} else {
			$testObjectFile = $this->__testObjectFileFromCaseFile($this->testCaseFile, $this->appTest);

			if (!file_exists($testObjectFile)) {
				trigger_error(sprintf(__('This test object file is invalid: %s', true), $testObjectFile));
				return ;
			}

			if (isset($dump[$testObjectFile])) {
				$coverageData = $dump[$testObjectFile];
			}
		}
		return array($coverageData, $testObjectFile);
	}

/**
 * Diff reporting
 *
 * @param string $testObjectFile
 * @param string $coverageData
 * @param string $execCodeLines
 * @param string $output
 * @return void
 * @static
 */
	function reportCaseHtmlDiff($testObjectFile, $coverageData, $execCodeLines, $numContextLines) {
		$manager = CodeCoverageManager::getInstance();
		$total = count($testObjectFile);
		$lines = array();

		for ($i = 1; $i < $total + 1; $i++) {
			$foundByManualFinder = isset($execCodeLines[$i]) && trim($execCodeLines[$i]) != '';
			$foundByXdebug = isset($coverageData[$i]);

			if (!$foundByManualFinder || !$foundByXdebug || $coverageData[$i] === -2) {
				if (isset($lines[$i])) {
					$lines[$i] = 'ignored ' . $lines[$i];
				} else {
					$lines[$i] = 'ignored';
				}
				continue;
			}

			if ($coverageData[$i] !== -1) {
				if (isset($lines[$i])) {
					$lines[$i] = 'covered ' . $lines[$i];
				} else {
					$lines[$i] = 'covered';
				}
				continue;
			}
			$lines[$i] = 'uncovered show';
			$foundEndBlockInContextSearch = false;

			for ($j = 1; $j <= $numContextLines; $j++) {
				$key = $i - $j;

				if ($key > 0 && isset($lines[$key])) {
					if (strpos($lines[$key], 'end') !== false) {
						$foundEndBlockInContextSearch = true;
						if ($j < $numContextLines) {
							$lines[$key] = str_replace('end', '', $lines[$key-1]);
						}
					}

					if (strpos($lines[$key], 'uncovered') === false) {
						if (strpos($lines[$key], 'covered') !== false) {
							$lines[$key] .= ' show';
						} else {
							$lines[$key] = 'ignored show';
						}
					}

					if ($j == $numContextLines) {
						$lineBeforeIsEndBlock = strpos($lines[$key-1], 'end') !== false;
						$lineBeforeIsShown = strpos($lines[$key-1], 'show') !== false;
						$lineBeforeIsUncovered = strpos($lines[$key-1], 'uncovered') !== false;

						if (!$foundEndBlockInContextSearch && !$lineBeforeIsUncovered && ($lineBeforeIsEndBlock)) {
							$lines[$key-1] = str_replace('end', '', $lines[$key-1]);
						}

						if (!$lineBeforeIsShown && !$lineBeforeIsUncovered) {
							$lines[$key] .= ' start';
						}
					}
				}
				$key = $i + $j;

				if ($key < $total) {
					$lines[$key] = 'show';

					if ($j == $numContextLines) {
						$lines[$key] .= ' end';
					}
				}
			}
		}

		// find the last "uncovered" or "show"n line and "end" its block
		$lastShownLine = $manager->__array_strpos($lines, 'show', true);
		if (isset($lines[$lastShownLine])) {
			$lines[$lastShownLine] .= ' end';
		}

		// give the first start line another class so we can control the top padding of the entire results
		$firstShownLine = $manager->__array_strpos($lines, 'show');
		if (isset($lines[$firstShownLine])) {
			$lines[$firstShownLine] .= ' realstart';
		}

		// get the output
		$lineCount = $coveredCount = 0;
		$report = '';
		foreach ($testObjectFile as $num => $line) {
			// start line count at 1
			$num++;
			$class = $lines[$num];

			if (strpos($class, 'ignored') === false) {
				$lineCount++;

				if (strpos($class, 'covered') !== false && strpos($class, 'uncovered') === false) {
					$coveredCount++;
				}
			}

			if (strpos($class, 'show') !== false) {
				$report .= $manager->__paintCodeline($class, $num, $line);
			}
		}
		return $manager->__paintHeader($lineCount, $coveredCount, $report);
	}

/**
 * CLI reporting
 *
 * @param string $testObjectFile
 * @param string $coverageData
 * @param string $execCodeLines
 * @param string $output
 * @return void
 * @static
 */
	function reportCaseCli($testObjectFile, $coverageData, $execCodeLines) {
		$manager = CodeCoverageManager::getInstance();
		$lineCount = $coveredCount = 0;
		$report = '';

		foreach ($testObjectFile as $num => $line) {
			$num++;
			$foundByManualFinder = isset($execCodeLines[$num]) && trim($execCodeLines[$num]) != '';
			$foundByXdebug = isset($coverageData[$num]) && $coverageData[$num] !== -2;

			if ($foundByManualFinder && $foundByXdebug) {
				$lineCount++;

				if ($coverageData[$num] > 0) {
					$coveredCount++;
				}
			}
		}
		return $manager->__paintHeaderCli($lineCount, $coveredCount, $report);
	}

/**
 * Diff reporting
 *
 * @param string $testObjectFile
 * @param string $coverageData
 * @param string $execCodeLines
 * @param string $output
 * @return void
 * @static
 */
	function reportGroupHtml($testObjectFiles, $coverageData, $execCodeLines, $numContextLines) {
		$manager = CodeCoverageManager::getInstance();
		$report = '';

		foreach ($testObjectFiles as $testObjectFile) {
			$lineCount = $coveredCount = 0;
			$objFilename = $testObjectFile;
			$testObjectFile = file($testObjectFile);

			foreach ($testObjectFile as $num => $line) {
				$num++;
				$foundByManualFinder = isset($execCodeLines[$objFilename][$num]) && trim($execCodeLines[$objFilename][$num]) != '';
				$foundByXdebug = isset($coverageData[$objFilename][$num]) && $coverageData[$objFilename][$num] !== -2;

				if ($foundByManualFinder && $foundByXdebug) {
					$class = 'uncovered';
					$lineCount++;

					if ($coverageData[$objFilename][$num] > 0) {
						$class = 'covered';
						$coveredCount++;
					}
				} else {
					$class = 'ignored';
				}
			}
			$report .= $manager->__paintGroupResultLine($objFilename, $lineCount, $coveredCount);
		}
		return $manager->__paintGroupResultHeader($report);
	}

/**
 * CLI reporting
 *
 * @param string $testObjectFile
 * @param string $coverageData
 * @param string $execCodeLines
 * @param string $output
 * @return void
 * @static
 */
	function reportGroupCli($testObjectFiles, $coverageData, $execCodeLines) {
		$manager = CodeCoverageManager::getInstance();
		$report = '';

		foreach ($testObjectFiles as $testObjectFile) {
			$lineCount = $coveredCount = 0;
			$objFilename = $testObjectFile;
			$testObjectFile = file($testObjectFile);

			foreach ($testObjectFile as $num => $line) {
				$num++;
				$foundByManualFinder = isset($execCodeLines[$objFilename][$num]) && trim($execCodeLines[$objFilename][$num]) != '';
				$foundByXdebug = isset($coverageData[$objFilename][$num]) && $coverageData[$objFilename][$num] !== -2;

				if ($foundByManualFinder && $foundByXdebug) {
					$lineCount++;

					if ($coverageData[$objFilename][$num] > 0) {
						$coveredCount++;
					}
				}
			}
			$report .= $manager->__paintGroupResultLineCli($objFilename, $lineCount, $coveredCount);
		}
		return $report;
	}

/**
 * Returns the name of the test object file based on a given test case file name
 *
 * @param string $file
 * @param string $isApp
 * @return string name of the test object file
 * @access private
 */
	function __testObjectFileFromCaseFile($file, $isApp = true) {
		$manager = CodeCoverageManager::getInstance();
		$path = $manager->__getTestFilesPath($isApp);
		$folderPrefixMap = array(
			'behaviors' => 'models',
			'components' => 'controllers',
			'helpers' => 'views'
		);

		foreach ($folderPrefixMap as $dir => $prefix) {
			if (strpos($file, $dir) === 0) {
				$path .= $prefix . DS;
				break;
			}
		}
		$testManager =& new TestManager();
		$testFile = str_replace(array('/', $testManager->_testExtension), array(DS, '.php'), $file);

		$folder =& new Folder();
		$folder->cd(ROOT . DS . CAKE_TESTS_LIB);
		$contents = $folder->read();

		if (in_array(basename($testFile), $contents[1])) {
			$testFile = basename($testFile);
			$path = ROOT . DS . CAKE_TESTS_LIB;
		}
		$path .= $testFile;
		$realpath = realpath($path);

		if ($realpath) {
			return $realpath;
		}
		return $path;
	}

/**
 * Returns an array of names of the test object files based on a given test group file name
 *
 * @param array $files
 * @param string $isApp
 * @return array names of the test object files
 * @access private
 */
	function __testObjectFilesFromGroupFile($groupFile, $isApp = true) {
		$manager = CodeCoverageManager::getInstance();
		$testManager =& new TestManager();

		$path = TESTS;
		if (!$isApp) {
			$path = CAKE_CORE_INCLUDE_PATH . DS . 'cake' . DS . 'tests';
		}
		if (!!$manager->pluginTest) {
			$path = App::pluginPath($manager->pluginTest) . DS . 'tests';
		}

		$result = array();
		if ($groupFile == 'all') {
			$files = array_keys($testManager->getTestCaseList());
			foreach ($files as $file) {
				$file = str_replace(DS . 'tests' . DS . 'cases' . DS, DS, $file);
				$file = str_replace('.test.php', '.php', $file);
				$file = str_replace(DS . DS, DS, $file);
				$result[] = $file;
			}
		} else {
			$path .= DS . 'groups' . DS . $groupFile . $testManager->_groupExtension;
			if (!file_exists($path)) {
				trigger_error(__('This group file does not exist!', true));
				return array();
			}

			$result = array();
			$groupContent = file_get_contents($path);
			$ds = '\s*\.\s*DS\s*\.\s*';
			$pluginTest = 'APP\.\'plugins\'' . $ds . '\'' . $manager->pluginTest . '\'' . $ds . '\'tests\'' . $ds . '\'cases\'';
			$pluginTest .= '|App::pluginPath\(\'' . $manager->pluginTest . '\'\)' . $ds . '\'tests\'' . $ds . '\'cases\'';
			$pattern = '/\s*TestManager::addTestFile\(\s*\$this,\s*(' . $pluginTest . '|APP_TEST_CASES|CORE_TEST_CASES)' . $ds . '(.*?)\)/i';
			preg_match_all($pattern, $groupContent, $matches);

			foreach ($matches[2] as $file) {
				$patterns = array(
					'/\s*\.\s*DS\s*\.\s*/',
					'/\s*APP_TEST_CASES\s*/',
					'/\s*CORE_TEST_CASES\s*/',
				);

				$replacements = array(DS, '', '');
				$file = preg_replace($patterns, $replacements, $file);
				$file = str_replace("'", '', $file);
				$result[] = $manager->__testObjectFileFromCaseFile($file, $isApp) . '.php';
			}
		}
		return $result;
	}

/**
 * Parses a given code string into an array of lines and replaces some non-executable code lines with the needed
 * amount of new lines in order for the code line numbers to stay in sync
 *
 * @param string $content
 * @return array array of lines
 * @access private
 */
	function __getExecutableLines($content) {
		if (is_array($content)) {
			$manager =& CodeCoverageManager::getInstance();
			$result = array();
			foreach ($content as $file) {
				$result[$file] = $manager->__getExecutableLines(file_get_contents($file));
			}
			return $result;
		}
		$content = h($content);
		// arrays are 0-indexed, but we want 1-indexed stuff now as we are talking code lines mind you (**)
		$content = "\n" . $content;
		// // strip unwanted lines
		$content = preg_replace_callback("/(@codeCoverageIgnoreStart.*?@codeCoverageIgnoreEnd)/is", array('CodeCoverageManager', '__replaceWithNewlines'), $content);
		// strip php | ?\> tag only lines
		$content = preg_replace('/[ |\t]*[&lt;\?php|\?&gt;]+[ |\t]*/', '', $content);

		// strip lines that contain only braces and parenthesis
		$content = preg_replace('/[ |\t]*[{|}|\(|\)]+[ |\t]*/', '', $content);
		$result = explode("\n", $content);
		// unset the zero line again to get the original line numbers, but starting at 1, see (**)
		unset($result[0]);
		return $result;
	}

/**
 * Replaces a given arg with the number of newlines in it
 *
 * @return string the number of newlines in a given arg
 * @access private
 */
	function __replaceWithNewlines() {
		$args = func_get_args();
		$numLineBreaks = count(explode("\n", $args[0][0]));
		return str_pad('', $numLineBreaks - 1, "\n");
	}

/**
 * Paints the headline for code coverage analysis
 *
 * @param string $codeCoverage
 * @param string $report
 * @return void
 * @access private
 */
	function __paintHeader($lineCount, $coveredCount, $report) {
		$manager =& CodeCoverageManager::getInstance();
		$codeCoverage = $manager->__calcCoverage($lineCount, $coveredCount);
		return $report = '<h2>Code Coverage: ' . $codeCoverage . '%</h2>
						<div class="code-coverage-results"><pre>' . $report . '</pre></div>';
	}

/**
 * Displays a notification concerning group test results
 *
 * @return void
 * @access public
 */
	function __paintGroupResultHeader($report) {
		return '<div class="code-coverage-results"><p class="note">Please keep in mind that the coverage can vary a little bit depending on how much the different tests in the group interfere. If for example, TEST A calls a line from TEST OBJECT B, the coverage for TEST OBJECT B will be a little greater than if you were running the corresponding test case for TEST OBJECT B alone.</p><pre>' . $report . '</pre></div>';
	}

/**
 * Paints the headline for code coverage analysis
 *
 * @param string $codeCoverage
 * @param string $report
 * @return void
 * @access private
 */
	function __paintGroupResultLine($file, $lineCount, $coveredCount) {
		$manager =& CodeCoverageManager::getInstance();
		$codeCoverage = $manager->__calcCoverage($lineCount, $coveredCount);
		$class = 'result-bad';

		if ($codeCoverage > 50) {
			$class = 'result-ok';
		}
		if ($codeCoverage > 80) {
			$class = 'result-good';
		}
		return '<p>Code Coverage for ' . $file . ': <span class="' . $class . '">' . $codeCoverage . '%</span></p>';
	}

/**
 * Paints the headline for code coverage analysis
 *
 * @param string $codeCoverage
 * @param string $report
 * @return void
 * @access private
 */
	function __paintGroupResultLineCli($file, $lineCount, $coveredCount) {
		$manager =& CodeCoverageManager::getInstance();
		$codeCoverage = $manager->__calcCoverage($lineCount, $coveredCount);
		$class = 'bad';
		if ($codeCoverage > 50) {
			$class = 'ok';
		}
		if ($codeCoverage > 80) {
			$class = 'good';
		}
		return "\n" . 'Code Coverage for ' . $file . ': ' . $codeCoverage . '% (' . $class . ')' . "\n";
	}

/**
 * Paints the headline for code coverage analysis in the CLI
 *
 * @param string $codeCoverage
 * @param string $report
 * @return void
 * @access private
 */
	function __paintHeaderCli($lineCount, $coveredCount, $report) {
		$manager =& CodeCoverageManager::getInstance();
		$codeCoverage = $manager->__calcCoverage($lineCount, $coveredCount);
		$class = 'bad';
		if ($codeCoverage > 50) {
			$class = 'ok';
		}
		if ($codeCoverage > 80) {
			$class = 'good';
		}
		return $report = "Code Coverage: $codeCoverage% ($class)\n";
	}

/**
 * Paints a code line for html output
 *
 * @package       default
 * @access private
 */
	function __paintCodeline($class, $num, $line) {
		$line = h($line);

		if (trim($line) == '') {
			$line = '&nbsp;'; // Win IE fix
		}
		return '<div class="code-line ' . trim($class) . '"><span class="line-num">' . $num . '</span><span class="content">' . $line . '</span></div>';
	}

/**
 * Calculates the coverage percentage based on a line count and a covered line count
 *
 * @param string $lineCount
 * @param string $coveredCount
 * @return void
 * @access private
 */
	function __calcCoverage($lineCount, $coveredCount) {
		if ($coveredCount > $lineCount) {
			trigger_error(__('Sorry, you cannot have more covered lines than total lines!', true));
		}
		return ($lineCount != 0)
				? round(100 * $coveredCount / $lineCount, 2)
				: '0.00';
	}

/**
 * Gets us the base path to look for the test files
 *
 * @param string $isApp
 * @return void
 * @access public
 */
	function __getTestFilesPath($isApp = true) {
		$manager = CodeCoverageManager::getInstance();
		$path = ROOT . DS;

		if ($isApp) {
			$path .= APP_DIR . DS;
		} elseif (!!$manager->pluginTest) {
			$pluginPath = APP . 'plugins' . DS . $manager->pluginTest . DS;

			$pluginPaths = App::path('plugins');
			foreach ($pluginPaths as $tmpPath) {
				$tmpPath = $tmpPath . $manager->pluginTest . DS;
				if (file_exists($tmpPath)) {
					$pluginPath = $tmpPath;
					break;
				}
			}

			$path = $pluginPath;
		} else {
			$path = TEST_CAKE_CORE_INCLUDE_PATH;
		}

		return $path;
	}

/**
 * Finds the last element of an array that contains $needle in a strpos computation
 *
 * @param array $arr
 * @param string $needle
 * @return void
 * @access private
 */
	function __array_strpos($arr, $needle, $reverse = false) {
		if (!is_array($arr) || empty($arr)) {
			return false;
		}

		if ($reverse) {
			$arr = array_reverse($arr, true);
		}

		foreach ($arr as $key => $val) {
			if (strpos($val, $needle) !== false) {
				return $key;
			}
		}
		return false;
	}
}
