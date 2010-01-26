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
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.tests.libs.reporter
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
 * Array of request parameters.  Usually parsed GET params.
 *
 * @var array
 */
	var $params = array();

/**
 * Character set for the output of test reporting.
 *
 * @var string
 * @access protected
 */
	var $_characterSet;

/**
 * Does nothing yet. The first output will
 * be sent on the first test start.
 *
 * ### Params
 *
 * - show_passes - Should passes be shown
 * - plugin - Plugin test being run?
 * - app - App test being run.
 * - case - The case being run
 * - codeCoverage - Whether the case/group being run is being code covered.
 * 
 * @param string $charset The character set to output with. Defaults to UTF-8
 * @param array $params Array of request parameters the reporter should use. See above.
 * @access public
 */
	function CakeBaseReporter($charset = 'utf-8', $params = array()) {
		$this->SimpleReporter();
		if (!$charset) {
			$charset = 'utf-8';
		}
		$this->_characterSet = $charset;
		$this->params = $params;
	}

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
 * Paints the beginning of a test method being run.  This is used
 * to start/resume the code coverage tool.
 *
 * @param string $method The method name being run.
 * @return void
 */
	function paintMethodStart($method) {
		parent::paintMethodStart($method);
		if (!empty($this->params['codeCoverage'])) {
			CodeCoverageManager::start();
		}
	}

/**
 * Paints the end of a test method being run.  This is used
 * to pause the collection of code coverage if its being used.
 *
 * @param string $method The name of the method being run.
 * @return void
 */
	function paintMethodEnd($method) {
		parent::paintMethodEnd($method);
		if (!empty($this->params['codeCoverage'])) {
			CodeCoverageManager::stop();
		}
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
		$testList = TestManager::getTestCaseList();
		return $testList;
	}

/**
 * Retrieves a list of group test cases from the active Manager class
 * displaying it in the correct format for the reporter subclass.
 *
 * @return void
 */
	function groupTestList() {
		$testList = TestManager::getGroupTestList();
		return $testList;
	}

/**
 * Paints the start of the response from the test suite.
 * Used to paint things like head elements in an html page.
 *
 * @return void
 */
	function paintDocumentStart() {

	}

/**
 * Paints the end of the response from the test suite.
 * Used to paint things like </body> in an html page.
 *
 * @return void
 */
	function paintDocumentEnd() {
		
	}

/**
 * Paint a list of test sets, core, app, and plugin test sets
 * available.
 *
 * @return void
 */
	function paintTestMenu() {
		
	}

/**
 * Get the baseUrl if one is available.
 *
 * @return string The base url for the request.
 */
	function baseUrl() {
		if (!empty($_SERVER['PHP_SELF'])) {
			return $_SERVER['PHP_SELF'];
		}
		return '';
	}

}
?>