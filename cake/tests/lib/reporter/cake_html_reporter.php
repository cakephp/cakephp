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
 * @link          http://cakephp.org
 * @package       cake
 * @subpackage    cake.cake.tests.libs
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
include_once dirname(__FILE__) . DS . 'cake_base_reporter.php';

/**
 * CakeHtmlReporter Reports Results of TestSuites and Test Cases
 * in an HTML format / context.
 *
 * @package cake
 * @subpackage cake.tests.lib
 */
class CakeHtmlReporter extends SimpleReporter {

/**
 * Character set for the output of test reporting.
 *
 * @var string
 * @access protected
 */
	var $_character_set;
/**
 * Toggle to show passes in output.
 *
 * @var boolean
 * @access protected
 */
	var $_show_passes = false;

/**
 * Time the test runs started.
 *
 * @var integer
 * @access protected
 */
	var $_timeStart = 0;

/**
 * Time the test runs ended
 *
 * @var integer
 * @access protected
 */
	var $_timeEnd = 0;

/**
 * Duration of all test methods.
 *
 * @var integer
 * @access protected
 */
	var $_timeDuration = 0;

/**
 * Array of request parameters.  Usually parsed GET params.
 *
 * @var array
 */
	var $params = array();

/**
 * Does nothing yet. The first output will
 * be sent on the first test start. For use
 * by a web browser.
 *
 * ### Params
 *
 * - show_passes - Should passes be shown
 * - plugin - Plugin test being run?
 * - app - App test being run.
 * - case - The case being run
 * 
 * @param string $character_set The character set to output with. Defaults to ISO-8859-1
 * @param array $params Array of request parameters the reporter should use. See above.
 * @access public
 */
	function CakeHtmlReporter($character_set = 'ISO-8859-1', $params = array()) {
		$this->SimpleReporter();
		$this->_character_set = !empty($character_set) ? $character_set : 'ISO-8859-1';
		$this->params = $params;
	}

/**
 * Paints the top of the web page setting the
 * title to the name of the starting test.
 *
 * @param string $test_name Name class of test.
 * @return void
 * @access public
 */
	function paintHeader($testName) {
		$this->sendNoCacheHeaders();
		$this->paintDocumentHeader();
		$this->paintTestMenu();
		echo "<h2>$testName</h2>\n";
		echo "<ul class='tests'>\n";
	}

/**
 * Paints the document header contained in header.php
 *
 * @return void
 */
	function paintDocumentHeader() {
		$baseDir = $this->params['baseDir'];
		include CAKE_TESTS_LIB . 'templates' . DS . 'header.php';
	}

/**
 * Paints the menu on the left side of the test suite interface.
 * Contains all of the various plugin, core, and app buttons.
 *
 * @return void
 */
	function paintTestMenu() {
		CakeTestMenu::testSuiteHeader();
	}

/**
 * Send the headers necessary to ensure the page is
 * reloaded on every request. Otherwise you could be
 * scratching your head over out of date test data.
 *
 * @return void
 * @access public
 */
	function sendNoCacheHeaders() {
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
 * @access public
 */
	function paintFooter($test_name) {
		$colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
		echo "</ul>\n";
		echo "<div style=\"";
		echo "padding: 8px; margin: 1em 0; background-color: $colour; color: white;";
		echo "\">";
		echo $this->getTestCaseProgress() . "/" . $this->getTestCaseCount();
		echo " test cases complete:\n";
		echo "<strong>" . $this->getPassCount() . "</strong> passes, ";
		echo "<strong>" . $this->getFailCount() . "</strong> fails and ";
		echo "<strong>" . $this->getExceptionCount() . "</strong> exceptions.";
		echo "</div>\n";
		echo '<div style="padding:0 0 5px;">';
		echo '<p><strong>Time taken by tests (in seconds):</strong> ' . $this->_timeDuration . '</p>';
		if (function_exists('memory_get_peak_usage')) {
			echo '<p><strong>Peak memory use: (in bytes):</strong> ' . number_format(memory_get_peak_usage()) . '</p>';
		}
		echo $this->_paintLinks();
		echo '</div>';
		$baseDir = $this->params['baseDir'];
		include CAKE_TESTS_LIB . 'templates' . DS . 'footer.php';
	}

/**
 * Renders the links that for accessing things in the test suite.
 *
 * @return void
 */
	function _paintLinks() {
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

		echo "<p><a href='" . RUN_TEST_LINK . $show . "'>Run more tests</a> | <a href='" . RUN_TEST_LINK . $query . "&show_passes=1'>Show Passes</a> | \n";
		echo " <a href='" . RUN_TEST_LINK . $query . "&amp;code_coverage=true'>Analyze Code Coverage</a></p>\n";
	}
/**
 * Convert an array of parameters into a query string url
 *
 * @param array $url Url hash to be converted
 * @return string Converted url query string
 */
	function _queryString($url) {
		$out = '?';
		$params = array();
		foreach ($url as $key => $value) {
			$params[] = "$key=$value";
		}
		$out .= implode('&amp;', $params);
		return $out;
	}

/**
 * Paints the test failure with a breadcrumbs
 * trail of the nesting test suites below the
 * top level test.
 *
 * @param string $message Failure message displayed in
 *   the context of the other tests.
 * @return void
 * @access public
 */
	function paintFail($message) {
		parent::paintFail($message);
		echo "<li class='fail'>\n";
		echo "<span>Failed</span>";
		echo "<div class='msg'>" . $this->_htmlEntities($message) . "</div>\n";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		echo "<div>" . implode(" -&gt; ", $breadcrumb) . "</div>\n";
		echo "</li>\n";
	}

/**
 * Paints the test pass with a breadcrumbs
 * trail of the nesting test suites below the
 * top level test.
 *
 * @param string $message Pass message displayed in the context of the other tests.
 * @return void
 * @access public
 */
	function paintPass($message) {
		parent::paintPass($message);

		if (isset($this->params['show_passes']) && $this->params['show_passes']) {
			echo "<li class='pass'>\n";
			echo "<span>Passed</span> ";
			$breadcrumb = $this->getTestList();
			array_shift($breadcrumb);
			echo implode(" -&gt; ", $breadcrumb);
			echo "<br />" . $this->_htmlEntities($message) . "\n";
			echo "</li>\n";
		}
	}

/**
 * Paints a PHP error.
 *
 * @param string $message Message is ignored.
 * @return void
 * @access public
 */
	function paintError($message) {
		parent::paintError($message);
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
 * @access public
 */
	function paintException($exception) {
		parent::paintException($exception);
		echo "<li class='fail'>\n";
		echo "<span>Exception</span>";
		$message = 'Unexpected exception of type [' . get_class($exception) .
			'] with message ['. $exception->getMessage() .
			'] in ['. $exception->getFile() .
			' line ' . $exception->getLine() . ']';
		echo "<div class='msg'>" . $this->_htmlEntities($message) . "</div>\n";
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		echo "<div>" . implode(" -&gt; ", $breadcrumb) . "</div>\n";
		echo "</li>\n";
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
 * @access public
 */
	function paintFormattedMessage($message) {
		echo '<pre>' . $this->_htmlEntities($message) . '</pre>';
	}

/**
 * Character set adjusted entity conversion.
 *
 * @param string $message Plain text or Unicode message.
 * @return string Browser readable message.
 * @access protected
 */
	function _htmlEntities($message) {
		return htmlentities($message, ENT_COMPAT, $this->_character_set);
	}
}
?>