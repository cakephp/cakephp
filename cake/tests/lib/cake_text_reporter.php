<?php
class CakeTextReporter extends TextReporter {

	var $_timeStart = 0;
	var $_timeEnd = 0;
	var $_timeDuration = 0;

/**
 * Signals / Paints the beginning of a TestSuite executing.
 * Starts the timer for the TestSuite execution time.
 *
 * @param 
 * @return void
 */
	function paintGroupStart($test_name, $size) {
		if (empty($this->_timeStart)) {
			$this->_timeStart = $this->_getTime();
		}
		parent::paintGroupStart($test_name, $size);
	}

/**
 * Signals/Paints the end of a TestSuite. All test cases have run
 * and timers are stopped.
 *
 * @return void
 */
	function paintGroupEnd($test_name) {
		$this->_timeEnd = $this->_getTime();
		$this->_timeDuration = $this->_timeEnd - $this->_timeStart;
		parent::paintGroupEnd($test_name);
	}

/**
 * Get the current time in microseconds. Similar to getMicrotime in basics.php
 * but in a separate function to reduce dependancies.
 *
 * @return float Time in microseconds
 */
	function _getTime() {
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$sec + (float)$usec);
	}

/**
 * Paints the end of the test with a summary of
 * the passes and failures.
 *
 * @param string $test_name Name class of test.
 * @access public
 */
	function paintFooter($test_name) {
		parent::paintFooter($test_name);
		echo 'Time taken by tests (in seconds): ' . $this->_timeDuration . "\n";
		if (function_exists('memory_get_peak_usage')) {
			echo 'Peak memory use: (in bytes): ' . number_format(memory_get_peak_usage()) . "\n";
		}
	}
}
?>