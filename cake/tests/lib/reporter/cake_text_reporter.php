<?php
/**
 * CakeTextReporter contains reporting features used for plain text based output
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.tests.libs.reporter
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
include_once dirname(__FILE__) . DS . 'cake_base_reporter.php';

/**
 * CakeTextReporter contains reporting features used for plain text based output
 *
 * @package cake
 * @subpackage cake.tests.lib
 */
class CakeTextReporter extends CakeBaseReporter {

/**
 * Sets the text/plain header if the test is not a CLI test.
 *
 * @return void
 */
	function paintDocumentStart() {
		if (!SimpleReporter::inCli()) {
			header('Content-type: text/plain');
		}
	}

/**
 * Paints the end of the test with a summary of
 * the passes and failures.
 *
 * @param string $test_name Name class of test.
 * @return void
 * @access public
 */
	function paintFooter($test_name) {
		if ($this->getFailCount() + $this->getExceptionCount() == 0) {
			echo "OK\n";
		} else {
			echo "FAILURES!!!\n";
		}
		echo "Test cases run: " . $this->getTestCaseProgress() .
				"/" . $this->getTestCaseCount() .
				", Passes: " . $this->getPassCount() .
				", Failures: " . $this->getFailCount() .
				", Exceptions: " . $this->getExceptionCount() . "\n";

		echo 'Time taken by tests (in seconds): ' . $this->_timeDuration . "\n";
		if (function_exists('memory_get_peak_usage')) {
			echo 'Peak memory use: (in bytes): ' . number_format(memory_get_peak_usage()) . "\n";
		}
		if (
			isset($this->params['codeCoverage']) && 
			$this->params['codeCoverage'] && 
			class_exists('CodeCoverageManager')
		) {
			CodeCoverageManager::report();
		}
	}

/**
 * Paints the title only.
 *
 * @param string $test_name Name class of test.
 * @return void
 * @access public
 */
	function paintHeader($test_name) {
		$this->paintDocumentStart();
		echo "$test_name\n";
		flush();
	}

/**
 * Paints the test failure as a stack trace.
 *
 * @param string $message Failure message displayed in
 *    the context of the other tests.
 * @return void
 * @access public
 */
	function paintFail($message) {
		parent::paintFail($message);
		echo $this->getFailCount() . ") $message\n";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		echo "\tin " . implode("\n\tin ", array_reverse($breadcrumb));
		echo "\n";
	}

/**
 * Paints a PHP error.
 *
 * @param string $message Message to be shown.
 * @return void
 * @access public
 */
	function paintError($message) {
		parent::paintError($message);
		echo "Exception " . $this->getExceptionCount() . "!\n$message\n";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		echo "\tin " . implode("\n\tin ", array_reverse($breadcrumb));
		echo "\n";
	}

/**
 * Paints a PHP exception.
 *
 * @param Exception $exception Exception to describe.
 * @return void
 * @access public
 */
	function paintException($exception) {
		parent::paintException($exception);
		$message = 'Unexpected exception of type [' . get_class($exception) .
				'] with message ['. $exception->getMessage() .
				'] in ['. $exception->getFile() .
				' line ' . $exception->getLine() . ']';
		echo "Exception " . $this->getExceptionCount() . "!\n$message\n";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		echo "\tin " . implode("\n\tin ", array_reverse($breadcrumb));
		echo "\n";
	}

/**
 * Prints the message for skipping tests.
 *
 * @param string $message Text of skip condition.
 * @return void
 * @access public
 */
	function paintSkip($message) {
		parent::paintSkip($message);
		echo "Skip: $message\n";
	}

/**
 * Paints formatted text such as dumped variables.
 *
 * @param string $message Text to show.
 * @return void
 * @access public
 */
	function paintFormattedMessage($message) {
		echo "$message\n";
		flush();
	}

/**
 * Generate a test case list in plain text.
 * Creates as series of url's for tests that can be run.
 * One case per line.
 *
 * @return void
 */
	function testCaseList() {
		$testCases = parent::testCaseList();
		$app = $this->params['app'];
		$plugin = $this->params['plugin'];

		$buffer = "Core Test Cases:\n";
		$urlExtra = '';
		if ($app) {
			$buffer = "App Test Cases:\n";
			$urlExtra = '&app=true';
		} elseif ($plugin) {
			$buffer = Inflector::humanize($plugin) . " Test Cases:\n";
			$urlExtra = '&plugin=' . $plugin;
		}

		if (1 > count($testCases)) {
			$buffer .= "EMPTY";
			echo $buffer;
		}

		foreach ($testCases as $testCaseFile => $testCase) {
			$buffer .= $_SERVER['SERVER_NAME'] . $this->baseUrl() ."?case=" . $testCase . "&output=text"."\n";
		}

		$buffer .= "\n";
		echo $buffer;
	}
}
