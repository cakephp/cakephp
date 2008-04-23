<?php
/* SVN FILE: $Id: code_coverage_manager.php 6527 2008-04-09 04:07:56Z DarkAngelBGE $ */
/**
 * A class to manage all aspects for Code Coverage Analysis
 *
 * This class
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright		Copyright 2005-2008, Cake Software Foundation, Inc.
 * @link				https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package			cake
 * @subpackage		cake.cake.tests.lib
 * @since			CakePHP(tm) v 1.2.0.4433
 * @version			$Revision: 6527 $
 * @modifiedby		$LastChangedBy: gwoo $
 * @lastmodified	$Date: 2008-03-09 05:07:56 +0100 (Sun, 09 Mar 2008) $
 * @license			http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Folder');
/**
 * Short description for class.
 *
 * @package    cake
 * @subpackage cake.cake.tests.lib
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
 * Returns a singleton instance
 *
 * @return object
 * @access public
 */
	function &getInstance() {
		static $instance = array();
		if (!isset($instance[0]) || !$instance[0]) {
			$instance[0] =& new CodeCoverageManager();
		}
		return $instance[0];
	}
/**
 * Starts a new Coverage Analyzation for a given test case file
 * @TODO: Works with $_GET now within the function body, which will make it hard when we do code coverage reports for CLI
 *
 * @param string $testCaseFile 
 * @param string $reporter 
 * @return void
 */
	function start($testCaseFile, &$reporter) {
		$manager =& CodeCoverageManager::getInstance();
		$manager->reporter = $reporter;

		$thisFile = r('.php', '.test.php', basename(__FILE__));
		if (strpos($testCaseFile, $thisFile) !== false) {
			trigger_error('Xdebug supports no parallel coverage analysis - so this is not possible.', E_USER_ERROR);
		}

		if (isset($_GET['app'])) {
			$manager->appTest = true;
		}
		
		if (isset($_GET['plugin'])) {
			$manager->pluginTest = true;
		}

		$manager->testCaseFile = $testCaseFile;
		xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
	}
/**
 * Stops the current code coverage analyzation and dumps a nice report depending on the reporter that was passed to start()
 *
 * @return void
 */
	function report($output = true) {
		$manager =& CodeCoverageManager::getInstance();

		$testObjectFile = $manager->_testObjectFileFromCaseFile($manager->testCaseFile, $manager->appTest);
		$dump = xdebug_get_code_coverage();
		$coverageData = array();
		foreach ($dump as $file => $data) {
			if ($file == $testObjectFile) {
				$coverageData = $data;
				break;
			}
		}

		if (empty($coverageData) && $output) {
			echo 'The test object file is never loaded.';
		}

		$execCodeLines = $manager->_getExecutableLines(file_get_contents($testObjectFile));
		switch (get_class($manager->reporter)) {
			case 'CakeHtmlReporter':
				$manager->reportHtml($testObjectFile, $coverageData, $execCodeLines, $output);
				break;
			default:
				trigger_error('Currently only HTML reporting is supported for code coverage analysis.');
				break;
		}
	}
/**
 * Html reporting
 *
 * @param string $testObjectFile 
 * @param string $coverageData 
 * @param string $execCodeLines 
 * @param string $output 
 * @return void
 */
	function reportHtml($testObjectFile, $coverageData, $execCodeLines, $output) {
		if (file_exists($testObjectFile)) {
			$file = file($testObjectFile);

			$lineCount = 0;
			$coveredCount = 0;
			$report = '';
			foreach ($file as $num => $line) {
				// start line count at 1
				$num++;

				$foundByManualFinder = trim($execCodeLines[$num]) != '';
				$foundByXdebug = array_key_exists($num, $coverageData);

				// xdebug does not find all executable lines (zend engine fault)
				if ($foundByManualFinder && $foundByXdebug) {
					$class = 'uncovered';
					$lineCount++;

					if ($coverageData[$num] !== -1 && $coverageData[$num] !== -2) {
						$class = 'covered';
						$coveredCount++;
						$numExecuted = $coverageData[$num];
					}
				} else {
					$class = 'ignored';
				}
				$report .= '<span class="line-num">'.$num.'</span><span class="code-line '.$class.'">'.h($line).'</span>';
			}

			$codeCoverage = ($lineCount != 0)
						? round(100*$coveredCount/$lineCount, 2)
						: '0.00';

			if ($output) {
				echo '<h2>Code Coverage: '.$codeCoverage.'%</h2>';
				echo '<pre>'.$report.'</pre>';
			}
		}
	}
/**
 * Returns the name of the test object file based on a given test case file name
 *
 * @param string $file 
 * @param string $isApp 
 * @return void
 */
	function _testObjectFileFromCaseFile($file, $isApp = true) {
		$path = ROOT.DS;
		if ($isApp) {
			$path .= APP_DIR.DS;
		} else {
			$path .= CAKE;
		}
		
		$folderPrefixMap = array(
			'behaviors' => 'models',
			'components' => 'controllers',
			'helpers' => 'views'
		);
		foreach ($folderPrefixMap as $dir => $prefix) {
			if (strpos($file, $dir) === 0) {
				$path .= $prefix.DS;
				break;
			}
		}

		$testManager =& new TestManager();
		$testFile = r($testManager->_testExtension, '.php', $file);
		
		// if this is a file from the test lib, we cannot find the test object file in /cake/libs
		// but need to search for it in /cake/test/lib
		$folder = new Folder();
		$folder->cd(ROOT.DS.CAKE_TESTS_LIB);
		$contents = $folder->ls();

		if (in_array(basename($testFile), $contents[1])) {
			$testFile = basename($testFile);
			$path = ROOT.DS.CAKE_TESTS_LIB;
		}
		$path .= $testFile;

		return $path;
	}
/**
 * Parses a given code string into an array of lines and replaces every non-executable code line with the needed
 * amount of new lines in order for the code line numbers to stay in sync
 *
 * @param string $content 
 * @return array array of lines
 */
	function _getExecutableLines($content) {
		$content = h($content);

		// arrays are 0-indexed, but we want 1-indexed stuff now as we are talking code lines mind you (**)
		$content = "\n".$content;

		// strip unwanted lines
		$content = preg_replace_callback("/(@codeCoverageIgnoreStart.*?@codeCoverageIgnoreEnd)/is", array('CodeCoverageManager', '_replaceWithNewlines'), $content);

		// strip multiline comments
		$content = preg_replace_callback('/\/\\*[\\s\\S]*?\\*\//', array('CodeCoverageManager', '_replaceWithNewlines'), $content);

		// strip singleline comments
		$content = preg_replace('/\/\/.*/', '', $content);

		// strip function declarations as xdebug does not count them as covered
		$content = preg_replace('/[ |\t]*function[^\n]*\([^\n]*[ |\t]*\{/', '', $content);
		$content = preg_replace('/[ |\t]*function[^\n]*\([^\n]*[ |\t]*(\n)+[ |\t]*\{/', '$1', $content);

		// strip php | ?\> tag only lines
		$content = preg_replace('/[ |\t]*[ |&lt;\?php|\?&gt;|\t]*/', '', $content);

		// strip var declarations as xdebug does not count them as covered
		$content = preg_replace('/[ |\t]*var[ |\t]+\$[\w]+[ |\t]*=[ |\t]*.*?;/', '', $content);

		// strip lines than contain only braces
		$content = preg_replace('/[ |\t]*[{|}|\(|\)]+[ |\t]*/', '', $content);

		$result = explode("\n", $content); 

		// unset the zero line again to get the original line numbers, but starting at 1, see (**)
		unset($result[0]);

		return $result;
	}
/**
 * Replaces a given arg with the number of newlines in it
 *
 * @return void
 */
	function _replaceWithNewlines() {
		$args = func_get_args();
		$numLineBreaks = count(explode("\n", $args[0][0]));
		return str_pad('', $numLineBreaks-1, "\n");
	}
}
?>