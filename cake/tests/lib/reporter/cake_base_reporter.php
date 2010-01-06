<?php
/**
 * CakeBaseReporter contains common functionality to all cake test suite reporters.
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
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

/**
 * CakeBaseReporter contains common reporting features used in the CakePHP Test suite
 *
 * @package cake
 * @subpackage cake.tests.lib
 */
class CakeBaseReporter extends SimpleReporter {

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
 * Signals / Paints the beginning of a TestSuite executing.
 * Starts the timer for the TestSuite execution time.
 *
 * @param string $test_name Name of the test that is being run.
 * @param integer $size 
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
 * @param string $test_name Name of the test that is being run.
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
 * @access protected
 */
	function _getTime() {
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$sec + (float)$usec);
	}

/**
 * Retrieves a list of test cases from the active Manager class,
 * displaying it in the correct format for the reporter subclass
 *
 * @return mixed
 */
	function testCaseList() {
		
	}

/**
 * Retrieves a list of group test cases from the active Manager class
 * displaying it in the correct format for the reporter subclass.
 *
 * @return void
 */
	function groupTestList() {
		
	}

}
?>