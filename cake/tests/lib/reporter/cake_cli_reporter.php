<?php
/**
 * Cake CLI test reporter.
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
 * @subpackage    cake.cake.tests.libs
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
	if (version_compare(PHP_VERSION, '4.4.4', '<=') ||
		PHP_SAPI == 'cgi') {
		define('STDOUT', fopen('php://stdout', 'w'));
		define('STDERR', fopen('php://stderr', 'w'));
		register_shutdown_function(create_function('', 'fclose(STDOUT); fclose(STDERR); return true;'));
	}

include_once dirname(__FILE__) . DS . 'cake_base_reporter.php';

/**
 * Minimal command line test displayer. Writes fail details to STDERR. Returns 0
 * to the shell if all tests pass, ST_FAILS_RETURN_CODE if any test fails.
 *
 * @package cake
 * @subpackage cake.tests.libs.reporter
 */
class CakeCliReporter extends CakeBaseReporter {
/**
 * separator string for fail, error, exception, and skip messages.
 *
 * @var string
 */
	var $separator = '->';

/**
 * array of 'request' parameters
 *
 * @var array
 */
	var $params = array();

/**
 * Constructor
 *
 * @param string $separator 
 * @param array $params 
 * @return void
 */
	function CakeCLIReporter($charset = 'utf-8', $params = array()) {
		$this->CakeBaseReporter($charset, $params);
	}

	function setFailDetailSeparator($separator) {
		$this->separator = $separator;
	}

/**
 * Paint fail faildetail to STDERR.
 *
 * @param string $message Message of the fail.
 * @return void
 * @access public
 */
	function paintFail($message) {
		parent::paintFail($message);
		$message .= $this->_getBreadcrumb();
		fwrite(STDERR, 'FAIL' . $this->separator . $message);
	}

/**
 * Paint PHP errors to STDERR.
 *
 * @param string $message Message of the Error
 * @return void
 * @access public
 */
	function paintError($message) {
		parent::paintError($message);
		$message .= $this->_getBreadcrumb();
		fwrite(STDERR, 'ERROR' . $this->separator . $message);
	}

/**
 * Paint exception faildetail to STDERR.
 *
 * @param object $exception Exception instance
 * @return void
 * @access public
 */
	function paintException($exception) {
		parent::paintException($exception);
		$message = sprintf('Unexpected exception of type [%s] with message [%s] in [%s] line [%s]',
			get_class($exception),
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine()
		);
		$message .= $this->_getBreadcrumb();
		fwrite(STDERR, 'EXCEPTION' . $this->separator . $message);
	}

/**
 * Get the breadcrumb trail for the current test method/case
 *
 * @return string The string for the breadcrumb
 */
	function _getBreadcrumb() {
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		$out = "\n\tin " . implode("\n\tin ", array_reverse($breadcrumb));
		$out .= "\n\n";
		return $out;
	}

/**
 * Paint a test skip message
 *
 * @param string $message The message of the skip
 * @return void
 */
	function paintSkip($message) {
		parent::paintSkip($message);
		fwrite(STDOUT, 'SKIP' . $this->separator . $message . "\n\n");
	}

/**
 * Paint a footer with test case name, timestamp, counts of fails and exceptions.
 */
	function paintFooter($test_name) {
		$buffer = $this->getTestCaseProgress() . '/' . $this->getTestCaseCount() . ' test cases complete: ';

		if (0 < ($this->getFailCount() + $this->getExceptionCount())) {
			$buffer .= $this->getPassCount() . " passes";
			if (0 < $this->getFailCount()) {
				$buffer .= ", " . $this->getFailCount() . " fails";
			}
			if (0 < $this->getExceptionCount()) {
				$buffer .= ", " . $this->getExceptionCount() . " exceptions";
			}
			$buffer .= ".\n";
			$buffer .= $this->_timeStats();
			fwrite(STDOUT, $buffer);
		} else {
			fwrite(STDOUT, $buffer . $this->getPassCount() . " passes.\n" . $this->_timeStats());
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
 * Get the time and memory stats for this test case/group
 *
 * @return string String content to display
 * @access protected
 */
	function _timeStats() {
		$out = 'Time taken by tests (in seconds): ' . $this->_timeDuration . "\n";
		if (function_exists('memory_get_peak_usage')) {
			$out .= 'Peak memory use: (in bytes): ' . number_format(memory_get_peak_usage()) . "\n";
		}
		return $out;
	}
}
