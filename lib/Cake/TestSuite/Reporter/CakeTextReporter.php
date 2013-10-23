<?php
/**
 * CakeTextReporter contains reporting features used for plain text based output
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeBaseReporter', 'TestSuite/Reporter');
App::uses('TextCoverageReport', 'TestSuite/Coverage');

/**
 * CakeTextReporter contains reporting features used for plain text based output
 *
 * @package       Cake.TestSuite.Reporter
 */
class CakeTextReporter extends CakeBaseReporter {

/**
 * Sets the text/plain header if the test is not a CLI test.
 *
 * @return void
 */
	public function paintDocumentStart() {
		if (!headers_sent()) {
			header('Content-type: text/plain');
		}
	}

/**
 * Paints a pass
 *
 * @return void
 */
	public function paintPass() {
		echo '.';
	}

/**
 * Paints a failing test.
 *
 * @param PHPUnit_Framework_AssertionFailedError $message Failure object displayed in
 *   the context of the other tests.
 * @return void
 */
	public function paintFail($message) {
		$context = $message->getTrace();
		$realContext = $context[3];
		$context = $context[2];

		printf(
			"FAIL on line %s\n%s in\n%s %s()\n\n",
			$context['line'], $message->toString(), $context['file'], $realContext['function']
		);
	}

/**
 * Paints the end of the test with a summary of
 * the passes and failures.
 *
 * @param PHPUnit_Framework_TestResult $result Result object
 * @return void
 */
	public function paintFooter($result) {
		if ($result->failureCount() + $result->errorCount()) {
			echo "FAILURES!!!\n";
		} else {
			echo "\nOK\n";
		}

		echo "Test cases run: " . $result->count() .
			"/" . ($result->count() - $result->skippedCount()) .
			', Passes: ' . $this->numAssertions .
			', Failures: ' . $result->failureCount() .
			', Exceptions: ' . $result->errorCount() . "\n";

		echo 'Time: ' . $result->time() . " seconds\n";
		echo 'Peak memory: ' . number_format(memory_get_peak_usage()) . " bytes\n";

		if (isset($this->params['codeCoverage']) && $this->params['codeCoverage']) {
			$coverage = $result->getCodeCoverage()->getSummary();
			echo $this->paintCoverage($coverage);
		}
	}

/**
 * Paints the title only.
 *
 * @return void
 */
	public function paintHeader() {
		$this->paintDocumentStart();
		flush();
	}

/**
 * Paints a PHP exception.
 *
 * @param Exception $exception Exception to describe.
 * @return void
 */
	public function paintException($exception) {
		$message = 'Unexpected exception of type [' . get_class($exception) .
			'] with message [' . $exception->getMessage() .
			'] in [' . $exception->getFile() .
			' line ' . $exception->getLine() . ']';
		echo $message . "\n\n";
	}

/**
 * Prints the message for skipping tests.
 *
 * @param string $message Text of skip condition.
 * @return void
 */
	public function paintSkip($message) {
		printf("Skip: %s\n", $message->getMessage());
	}

/**
 * Paints formatted text such as dumped variables.
 *
 * @param string $message Text to show.
 * @return void
 */
	public function paintFormattedMessage($message) {
		echo "$message\n";
		flush();
	}

/**
 * Generate a test case list in plain text.
 * Creates as series of URLs for tests that can be run.
 * One case per line.
 *
 * @return void
 */
	public function testCaseList() {
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

		if (count($testCases) < 1) {
			$buffer .= 'EMPTY';
			echo $buffer;
		}

		foreach ($testCases as $testCase) {
			$buffer .= $_SERVER['SERVER_NAME'] . $this->baseUrl() . "?case=" . $testCase . "&output=text\n";
		}

		$buffer .= "\n";
		echo $buffer;
	}

/**
 * Generates a Text summary of the coverage data.
 *
 * @param array $coverage Array of coverage data.
 * @return void
 */
	public function paintCoverage($coverage) {
		$reporter = new TextCoverageReport($coverage, $this);
		echo $reporter->report();
	}

}
