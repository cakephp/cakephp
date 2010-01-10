<?php
/**
 * Cake CLI test reporter.
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
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
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

	var $separator = '->';

	function CakeCLIReporter($separator = NULL) {
		$this->SimpleReporter();
		if (!is_null($separator)) {
			$this->setFailDetailSeparator($separator);
		}
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
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		$message .= "\n\tin " . implode("\n\tin ", array_reverse($breadcrumb));
		$message .= "\n\n";
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
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		$message .= "\n\tin " . implode("\n\tin ", array_reverse($breadcrumb));
		$message .= "\n\n";
		fwrite(STDERR, 'ERROR' . $this->separator . $message);
	}

/**
 * Paint exception faildetail to STDERR.
 *
 * @param string $message Message of the Error
 * @return void
 * @access public
 */
	function paintException($exception) {
		parent::paintException($exception);
		$message .= sprintf('Unexpected exception of type [%s] with message [%s] in [%s] line [%s]',
			get_class($exception),
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine()
		);
		$breadcrumb = $this->getTestList();
		array_shift($breadcrumb);
		$message .= "\n\tin " . implode("\n\tin ", array_reverse($breadcrumb));
		$message .= "\n\n";
		fwrite(STDERR, 'EXCEPTION' . $this->separator . $message);
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
			fwrite(STDOUT, $buffer);
		} else {
			fwrite(STDOUT, $buffer . $this->getPassCount() . " passes.\n");
		}
	}
}
?>