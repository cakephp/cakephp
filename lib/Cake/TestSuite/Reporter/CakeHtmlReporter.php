<?php
/**
 * CakeHtmlReporter
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
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('CakeBaseReporter', 'TestSuite/Reporter');

/**
 * CakeHtmlReporter Reports Results of TestSuites and Test Cases
 * in an HTML format / context.
 *
 * @package       Cake.TestSuite.Reporter
 */
class CakeHtmlReporter extends CakeBaseReporter {

/**
 * Paints the top of the web page setting the
 * title to the name of the starting test.
 *
 * @return void
 */
	public function paintHeader() {
		$this->_headerSent = true;
		$this->sendContentType();
		$this->sendNoCacheHeaders();
		$this->paintDocumentStart();
		$this->paintTestMenu();
		echo "<ul class='tests'>\n";
	}

/**
 * Set the content-type header so it is in the correct encoding.
 *
 * @return void
 */
	public function sendContentType() {
		if (!headers_sent()) {
			header('Content-Type: text/html; charset=' . Configure::read('App.encoding'));
		}
	}

/**
 * Paints the document start content contained in header.php
 *
 * @return void
 */
	public function paintDocumentStart() {
		ob_start();
		$baseDir = $this->params['baseDir'];
		include CAKE . 'TestSuite' . DS . 'templates' . DS . 'header.php';
	}

/**
 * Paints the menu on the left side of the test suite interface.
 * Contains all of the various plugin, core, and app buttons.
 *
 * @return void
 */
	public function paintTestMenu() {
		$cases = $this->baseUrl() . '?show=cases';
		$plugins = App::objects('plugin', null, false);
		sort($plugins);
		include CAKE . 'TestSuite' . DS . 'templates' . DS . 'menu.php';
	}

/**
 * Retrieves and paints the list of tests cases in an HTML format.
 *
 * @return void
 */
	public function testCaseList() {
		$testCases = parent::testCaseList();
		$core = $this->params['core'];
		$plugin = $this->params['plugin'];

		$buffer = "<h3>App Test Cases:</h3>\n<ul>";
		$urlExtra = null;
		if ($core) {
			$buffer = "<h3>Core Test Cases:</h3>\n<ul>";
			$urlExtra = '&core=true';
		} elseif ($plugin) {
			$buffer = "<h3>" . Inflector::humanize($plugin) . " Test Cases:</h3>\n<ul>";
			$urlExtra = '&plugin=' . $plugin;
		}

		if (count($testCases) < 1) {
			$buffer .= "<strong>EMPTY</strong>";
		}

		foreach ($testCases as $testCase) {
			$title = explode(DS, str_replace('.test.php', '', $testCase));
			$title[count($title) - 1] = Inflector::camelize($title[count($title) - 1]);
			$title = implode(' / ', $title);
				$buffer .= "<li><a href='" . $this->baseUrl() . "?case=" . urlencode($testCase) . $urlExtra . "'>" . $title . "</a></li>\n";
		}
		$buffer .= "</ul>\n";
		echo $buffer;
	}

/**
 * Send the headers necessary to ensure the page is
 * reloaded on every request. Otherwise you could be
 * scratching your head over out of date test data.
 *
 * @return void
 */
	public function sendNoCacheHeaders() {
		if (!headers_sent()) {
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");
		}
	}

/**
 * Paints the end of the test with a summary of
 * the passes and failures.
 *
 * @param PHPUnit_Framework_TestResult $result Result object
 * @return void
 */
	public function paintFooter($result) {
		ob_end_flush();
		$colour = ($result->failureCount() + $result->errorCount() > 0 ? "red" : "green");
		echo "</ul>\n";
		echo "<div style=\"";
		echo "padding: 8px; margin: 1em 0; background-color: $colour; color: white;";
		echo "\">";
		echo ($result->count() - $result->skippedCount()) . "/" . $result->count();
		echo " test methods complete:\n";
		echo "<strong>" . count($result->passed()) . "</strong> passes, ";
		echo "<strong>" . $result->failureCount() . "</strong> fails, ";
		echo "<strong>" . $this->numAssertions . "</strong> assertions and ";
		echo "<strong>" . $result->errorCount() . "</strong> exceptions.";
		echo "</div>\n";
		echo '<div style="padding:0 0 5px;">';
		echo '<p><strong>Time:</strong> ' . $result->time() . ' seconds</p>';
		echo '<p><strong>Peak memory:</strong> ' . number_format(memory_get_peak_usage()) . ' bytes</p>';
		echo $this->_paintLinks();
		echo '</div>';
		if (isset($this->params['codeCoverage']) && $this->params['codeCoverage']) {
			$coverage = $result->getCodeCoverage();
			if (method_exists($coverage, 'getSummary')) {
				$report = $coverage->getSummary();
				echo $this->paintCoverage($report);
			}
			if (method_exists($coverage, 'getData')) {
				$report = $coverage->getData();
				echo $this->paintCoverage($report);
			}
		}
		$this->paintDocumentEnd();
	}

/**
 * Paints a code coverage report.
 *
 * @param array $coverage
 * @return void
 */
	public function paintCoverage(array $coverage) {
		App::uses('HtmlCoverageReport', 'TestSuite/Coverage');

		$reporter = new HtmlCoverageReport($coverage, $this);
		echo $reporter->report();
	}

/**
 * Renders the links that for accessing things in the test suite.
 *
 * @return void
 */
	protected function _paintLinks() {
		$show = $query = array();
		if (!empty($this->params['case'])) {
			$show['show'] = 'cases';
		}

		if (!empty($this->params['core'])) {
			$show['core'] = $query['core'] = 'true';
		}
		if (!empty($this->params['plugin'])) {
			$show['plugin'] = $query['plugin'] = $this->params['plugin'];
		}
		if (!empty($this->params['case'])) {
			$query['case'] = $this->params['case'];
		}
		$show = $this->_queryString($show);
		$query = $this->_queryString($query);

		echo "<p><a href='" . $this->baseUrl() . $show . "'>Run more tests</a> | <a href='" . $this->baseUrl() . $query . "&amp;show_passes=1'>Show Passes</a> | \n";
		echo "<a href='" . $this->baseUrl() . $query . "&amp;debug=1'>Enable Debug Output</a> | \n";
		echo "<a href='" . $this->baseUrl() . $query . "&amp;code_coverage=true'>Analyze Code Coverage</a></p>\n";
	}

/**
 * Convert an array of parameters into a query string url
 *
 * @param array $url Url hash to be converted
 * @return string Converted url query string
 */
	protected function _queryString($url) {
		$out = '?';
		$params = array();
		foreach ($url as $key => $value) {
			$params[] = "$key=$value";
		}
		$out .= implode('&amp;', $params);
		return $out;
	}

/**
 * Paints the end of the document html.
 *
 * @return void
 */
	public function paintDocumentEnd() {
		$baseDir = $this->params['baseDir'];
		include CAKE . 'TestSuite' . DS . 'templates' . DS . 'footer.php';
		if (ob_get_length()) {
			ob_end_flush();
		}
	}

/**
 * Paints the test failure with a breadcrumbs
 * trail of the nesting test suites below the
 * top level test.
 *
 * @param PHPUnit_Framework_AssertionFailedError $message Failure object displayed in
 *   the context of the other tests.
 * @param mixed $test
 * @return void
 */
	public function paintFail($message, $test) {
		$trace = $this->_getStackTrace($message);
		$testName = get_class($test) . '(' . $test->getName() . ')';

		$actualMsg = $expectedMsg = null;
		if (method_exists($message, 'getComparisonFailure')) {
			$failure = $message->getComparisonFailure();
			if (is_object($failure)) {
				$actualMsg = $failure->getActualAsString();
				$expectedMsg = $failure->getExpectedAsString();
			}
		}

		echo "<li class='fail'>\n";
		echo "<span>Failed</span>";
		echo "<div class='msg'><pre>" . $this->_htmlEntities($message->toString());

		if ((is_string($actualMsg) && is_string($expectedMsg)) || (is_array($actualMsg) && is_array($expectedMsg))) {
			echo "<br />" . PHPUnit_Util_Diff::diff($expectedMsg, $actualMsg);
		}

		echo "</pre></div>\n";
		echo "<div class='msg'>" . __d('cake_dev', 'Test case: %s', $testName) . "</div>\n";
		echo "<div class='msg'>" . __d('cake_dev', 'Stack trace:') . '<br />' . $trace . "</div>\n";
		echo "</li>\n";
	}

/**
 * Paints the test pass with a breadcrumbs
 * trail of the nesting test suites below the
 * top level test.
 *
 * @param PHPUnit_Framework_Test test method that just passed
 * @param float $time time spent to run the test method
 * @return void
 */
	public function paintPass(PHPUnit_Framework_Test $test, $time = null) {
		if (isset($this->params['showPasses']) && $this->params['showPasses']) {
			echo "<li class='pass'>\n";
			echo "<span>Passed</span> ";

			echo "<br />" . $this->_htmlEntities($test->getName()) . " ($time seconds)\n";
			echo "</li>\n";
		}
	}

/**
 * Paints a PHP exception.
 *
 * @param Exception $exception Exception to display.
 * @param mixed $test
 * @return void
 */
	public function paintException($message, $test) {
		$trace = $this->_getStackTrace($message);
		$testName = get_class($test) . '(' . $test->getName() . ')';

		echo "<li class='fail'>\n";
		echo "<span>" . get_class($message) . "</span>";

		echo "<div class='msg'>" . $this->_htmlEntities($message->getMessage()) . "</div>\n";
		echo "<div class='msg'>" . __d('cake_dev', 'Test case: %s', $testName) . "</div>\n";
		echo "<div class='msg'>" . __d('cake_dev', 'Stack trace:') . '<br />' . $trace . "</div>\n";
		echo "</li>\n";
	}

/**
 * Prints the message for skipping tests.
 *
 * @param string $message Text of skip condition.
 * @param PHPUnit_Framework_TestCase $test the test method skipped
 * @return void
 */
	public function paintSkip($message, $test) {
		echo "<li class='skipped'>\n";
		echo "<span>Skipped</span> ";
		echo $test->getName() . ': ' . $this->_htmlEntities($message->getMessage());
		echo "</li>\n";
	}

/**
 * Paints formatted text such as dumped variables.
 *
 * @param string $message Text to show.
 * @return void
 */
	public function paintFormattedMessage($message) {
		echo '<pre>' . $this->_htmlEntities($message) . '</pre>';
	}

/**
 * Character set adjusted entity conversion.
 *
 * @param string $message Plain text or Unicode message.
 * @return string Browser readable message.
 */
	protected function _htmlEntities($message) {
		return htmlentities($message, ENT_COMPAT, $this->_characterSet);
	}

/**
 * Gets a formatted stack trace.
 *
 * @param Exception $e Exception to get a stack trace for.
 * @return string Generated stack trace.
 */
	protected function _getStackTrace(Exception $e) {
		$trace = $e->getTrace();
		$out = array();
		foreach ($trace as $frame) {
			if (isset($frame['file']) && isset($frame['line'])) {
				$out[] = $frame['file'] . ' : ' . $frame['line'];
			} elseif (isset($frame['class']) && isset($frame['function'])) {
				$out[] = $frame['class'] . '::' . $frame['function'];
			} else {
				$out[] = '[internal]';
			}
		}
		return implode('<br />', $out);
	}

/**
 * A test suite started.
 *
 * @param PHPUnit_Framework_TestSuite $suite
 * @return void
 */
	public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
		if (!$this->_headerSent) {
			echo $this->paintHeader();
		}
		echo '<h2>' . __d('cake_dev', 'Running  %s', $suite->getName()) . '</h2>';
	}

}
