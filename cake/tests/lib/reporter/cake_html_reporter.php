<?php
/**
 * CakeHtmlReporter
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.tests.libs.reporter
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
include_once dirname(__FILE__) . DS . 'cake_base_reporter.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'DEFAULT');

/**
 * CakeHtmlReporter Reports Results of TestSuites and Test Cases
 * in an HTML format / context.
 *
 * @package cake
 * @subpackage cake.tests.lib
 */
class CakeHtmlReporter extends CakeBaseReporter implements PHPUnit_Framework_TestListener {

/**
 * Paints the top of the web page setting the
 * title to the name of the starting test.
 *
 * @return void
 */
	public function paintHeader() {
		$this->sendNoCacheHeaders();
		$this->paintDocumentStart();
		$this->paintTestMenu();
		echo "<ul class='tests'>\n";
	}

/**
 * Paints the document start content contained in header.php
 *
 * @return void
 */
	public function paintDocumentStart() {
		ob_start();
		$baseDir = $this->params['baseDir'];
		include CAKE_TESTS_LIB . 'templates' . DS . 'header.php';
	}

/**
 * Paints the menu on the left side of the test suite interface.
 * Contains all of the various plugin, core, and app buttons.
 *
 * @return void
 */
	public function paintTestMenu() {
		$groups = $this->baseUrl() . '?show=groups';
		$cases = $this->baseUrl() . '?show=cases';
		$plugins = App::objects('plugin');
		sort($plugins);
		include CAKE_TESTS_LIB . 'templates' . DS . 'menu.php';
	}

/**
 * Retrieves and paints the list of tests cases in an HTML format.
 *
 * @return void
 */
	public function testCaseList() {
		$testCases = parent::testCaseList();
		$app = $this->params['app'];
		$plugin = $this->params['plugin'];

		$buffer = "<h3>Core Test Cases:</h3>\n<ul>";
		$urlExtra = null;
		if ($app) {
			$buffer = "<h3>App Test Cases:</h3>\n<ul>";
			$urlExtra = '&app=true';
		} elseif ($plugin) {
			$buffer = "<h3>" . Inflector::humanize($plugin) . " Test Cases:</h3>\n<ul>";
			$urlExtra = '&plugin=' . $plugin;
		}

		if (1 > count($testCases)) {
			$buffer .= "<strong>EMPTY</strong>";
		}

		foreach ($testCases as $testCaseFile => $testCase) {
			$title = explode(strpos($testCase, '\\') ? '\\' : '/', str_replace('.test.php', '', $testCase));
			$title[count($title) - 1] = Inflector::camelize($title[count($title) - 1]);
			$title = implode(' / ', $title);
				$buffer .= "<li><a href='" . $this->baseUrl() . "?case=" . urlencode($testCase) . $urlExtra ."'>" . $title . "</a></li>\n";
		}
		$buffer .= "</ul>\n";
		echo $buffer;
	}

/**
 * Retrieves and paints the list of group tests in an HTML format.
 *
 * @return void
 */
	public function groupTestList() {
		$groupTests = parent::groupTestList();
		$app = $this->params['app'];
		$plugin = $this->params['plugin'];

		$buffer = "<h3>Core Test Groups:</h3>\n<ul>";
		$urlExtra = null;
		if ($app) {
			$buffer = "<h3>App Test Groups:</h3>\n<ul>";
			$urlExtra = '&app=true';
		} else if ($plugin) {
			$buffer = "<h3>" . Inflector::humanize($plugin) . " Test Groups:</h3>\n<ul>";
			$urlExtra = '&plugin=' . $plugin;
		}

		$buffer .= "<li><a href='" . $this->baseURL() . "?group=all$urlExtra'>All tests</a></li>\n";

		foreach ($groupTests as $groupTest) {
			$buffer .= "<li><a href='" . $this->baseURL() . "?group={$groupTest}" . "{$urlExtra}'>" . $groupTest . "</a></li>\n";
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
 * @param string $test_name Name class of test.
 * @return void
 */
	public function paintFooter($result) {
		$colour = ($result->failureCount()  + $result->errorCount() > 0 ? "red" : "green");
		echo "</ul>\n";
		echo "<div style=\"";
		echo "padding: 8px; margin: 1em 0; background-color: $colour; color: white;";
		echo "\">";
		echo $result->count() . "/" . $result->count() - $result->skippedCount();
		echo " test methods complete:\n";
		echo "<strong>" . count($result->passed()) . "</strong> passes, ";
		echo "<strong>" . $result->failureCount() . "</strong> fails, ";
		echo "<strong>" . $this->numAssertions . "</strong> assertions and ";
		echo "<strong>" . $result->errorCount() . "</strong> exceptions.";
		echo "</div>\n";
		echo '<div style="padding:0 0 5px;">';
		echo '<p><strong>Time taken by tests (in seconds):</strong> ' . $result->time() . '</p>';
		if (function_exists('memory_get_peak_usage')) {
			echo '<p><strong>Peak memory use: (in bytes):</strong> ' . number_format(memory_get_peak_usage()) . '</p>';
		}
		echo $this->_paintLinks();
		echo '</div>';
		if (isset($this->params['codeCoverage']) && $this->params['codeCoverage']) {
			$coverage = $result->getCodeCoverageInformation();
			echo $this->paintCoverage($coverage);
		}
		$this->paintDocumentEnd();
	}

/**
 * Paints a code coverage report.
 *
 * @return void
 */
	public function paintCoverage($coverage) {
		$file = dirname(dirname(__FILE__)) . '/coverage/html_coverage_report.php';
		include $file;
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
		if (!empty($this->params['group'])) {
			$show['show'] = 'groups';
		} elseif (!empty($this->params['case'])) {
			$show['show'] = 'cases';
		}

		if (!empty($this->params['app'])) {
			$show['app'] = $query['app'] = 'true';
		}
		if (!empty($this->params['plugin'])) {
			$show['plugin'] = $query['plugin'] = $this->params['plugin'];
		}
		if (!empty($this->params['case'])) {
			$query['case'] = $this->params['case'];
 		} elseif (!empty($this->params['group'])) {
			$query['group'] = $this->params['group'];
		}
		$show = $this->_queryString($show);
		$query = $this->_queryString($query);

		echo "<p><a href='" . $this->baseUrl() . $show . "'>Run more tests</a> | <a href='" . $this->baseUrl() . $query . "&show_passes=1'>Show Passes</a> | \n";
		echo " <a href='" . $this->baseUrl() . $query . "&amp;code_coverage=true'>Analyze Code Coverage</a></p>\n";
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
		include CAKE_TESTS_LIB . 'templates/footer.php';
		ob_end_flush();
	}

/**
 * Paints the test failure with a breadcrumbs
 * trail of the nesting test suites below the
 * top level test.
 *
 * @param PHPUnit_Framework_AssertionFailedError $message Failure object displayed in
 *   the context of the other tests.
 * @return void
 */
	public function paintFail($message) {
		$context = $message->getTrace();
		$realContext = $context[3];
		$context = $context[2];

		echo "<li class='fail'>\n";
		echo "<span>Failed</span>";
		echo "<div class='msg'><pre>" . $this->_htmlEntities($message->toString()) . "</pre></div>\n";
		echo "<div class='msg'>" . sprintf(__('File: %s'), $context['file']) . "</div>\n";
		echo "<div class='msg'>" . sprintf(__('Method: %s'), $realContext['function']) . "</div>\n";
		echo "<div class='msg'>" . sprintf(__('Line: %s'), $context['line']) . "</div>\n";
		//$breadcrumb = $this->getTestList();
		//array_shift($breadcrumb);
		//echo "<div>" . implode(" -&gt; ", $breadcrumb) . "</div>\n";
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
		if (isset($this->params['show_passes']) && $this->params['show_passes']) {
			echo "<li class='pass'>\n";
			echo "<span>Passed</span> ";
			//$breadcrumb = $this->getTestList();
			//array_shift($breadcrumb);
			//echo implode(" -&gt; ", $breadcrumb);
			echo "<br />" . $this->_htmlEntities($test->getName()) . " ($time seconds)\n";
			echo "</li>\n";
		}
	}

/**
 * Paints a PHP error.
 *
 * @param string $message Message is ignored.
 * @return void
 */
	public function paintError($message) {
		echo "<li class='error'>\n";
		echo "<span>Error</span>";
		echo "<div class='msg'>" . $this->_htmlEntities($message) . "</div>\n";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		echo "<div>" . implode(" -&gt; ", $breadcrumb) . "</div>\n";
		echo "</li>\n";
	}

/**
 * Paints a PHP exception.
 *
 * @param Exception $exception Exception to display.
 * @return void
 */
	public function paintException($exception) {
		echo "<li class='fail'>\n";
		echo "<span>Exception</span>";
		$message = 'Unexpected exception of type [' . get_class($exception) .
			'] with message ['. $exception->getMessage() .
			'] in ['. $exception->getFile() .
			' line ' . $exception->getLine() . ']';
		echo "<div class='msg'>" . $this->_htmlEntities($message) . "</div>\n";
		//$breadcrumb = $this->getTestList();
		//array_shift($breadcrumb);
		//echo "<div>" . implode(" -&gt; ", $breadcrumb) . "</div>\n";
		echo "</li>\n";
	}

/**
 * Prints the message for skipping tests.
 *
 * @param string $message Text of skip condition.
 * @return void
 */
	public function paintSkip($message) {
		echo "<li class='skipped'>\n";
		echo "<span>Skipped</span> ";
		echo $this->_htmlEntities($message);
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

	public function paintResult(PHPUnit_Framework_TestResult $result) {

		/*if ($result->errorCount() > 0) {
			$this->printErrors($result);
		}

		if ($result->failureCount() > 0) {
			$this->printFailures($result);
		}

		if ($result->skippedCount() > 0) {
			 $this->printIncompletes($result);
		}

		if ($result->skippedCount() > 0) {
			$this->printSkipped($result);
		}*/

		$this->paintFooter($result);
	}

/**
* An error occurred.
*
* @param  PHPUnit_Framework_Test $test
* @param  Exception              $e
* @param  float                  $time
*/
	public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
		$this->paintException($e);
	}

/**
* A failure occurred.
*
* @param  PHPUnit_Framework_Test $test
* @param  PHPUnit_Framework_AssertionFailedError $e
* @param  float $time
*/
	public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
		$this->paintFail($e);
	}

/**
* Incomplete test.
*
* @param  PHPUnit_Framework_Test $test
* @param  Exception $e
* @param  float $time
*/
	public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {

	}

/**
* Skipped test.
*
* @param  PHPUnit_Framework_Test $test
* @param  Exception $e
* @param  float $time
*/
	public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
	}

/**
 * A test suite started.
 *
 * @param  PHPUnit_Framework_TestSuite $suite
 */
	public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
		echo '<h2>' . sprintf(__('Running  %s'), $suite->getName()) . '</h2>';
	}

/**
 * A test suite ended.
 *
 * @param  PHPUnit_Framework_TestSuite $suite
 */
	public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
	}

/**
 * A test started.
 *
 * @param  PHPUnit_Framework_Test $test
 */
	public function startTest(PHPUnit_Framework_Test $test) {
	}

/**
 * A test ended.
 *
 * @param  PHPUnit_Framework_Test $test
 * @param  float $time
 */
	public function endTest(PHPUnit_Framework_Test $test, $time) {
		$this->numAssertions += $test->getNumAssertions();
		$this->paintPass($test, $time);
	}
}
?>