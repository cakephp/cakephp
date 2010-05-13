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

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'DEFAULT');

/**
 * CakeBaseReporter contains common reporting features used in the CakePHP Test suite
 *
 * @package cake
 * @subpackage cake.tests.lib
 */
class CakeBaseReporter implements PHPUnit_Framework_TestListener {

/**
 * Time the test runs started.
 *
 * @var integer
 * @access protected
 */
	protected $_timeStart = 0;

/**
 * Time the test runs ended
 *
 * @var integer
 * @access protected
 */
	protected $_timeEnd = 0;

/**
 * Duration of all test methods.
 *
 * @var integer
 * @access protected
 */
	protected $_timeDuration = 0;

/**
 * Array of request parameters.  Usually parsed GET params.
 *
 * @var array
 */
	public $params = array();

/**
 * Character set for the output of test reporting.
 *
 * @var string
 * @access protected
 */
	protected $_characterSet;

/**
* The number of assertions done for a test suite
*/
	protected $numAssertions = 0;
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
 */
	function __construct($charset = 'utf-8', $params = array()) {
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
	public function paintGroupStart($test_name, $size) {
		if (empty($this->_timeStart)) {
			$this->_timeStart = microtime(true);
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
	public function paintGroupEnd($test_name) {
		$this->_timeEnd = microtime(true);
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
	public function paintMethodStart($method) {
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
	public function paintMethodEnd($method) {
		parent::paintMethodEnd($method);
		if (!empty($this->params['codeCoverage'])) {
			CodeCoverageManager::stop();
		}
	}

/**
 * Retrieves a list of test cases from the active Manager class,
 * displaying it in the correct format for the reporter subclass
 *
 * @return mixed
 */
	public function testCaseList() {
		$testList = TestManager::getTestCaseList();
		return $testList;
	}

/**
 * Retrieves a list of group test cases from the active Manager class
 * displaying it in the correct format for the reporter subclass.
 *
 * @return void
 */
	public function groupTestList() {
		$testList = TestManager::getGroupTestList();
		return $testList;
	}

/**
 * Paints the start of the response from the test suite.
 * Used to paint things like head elements in an html page.
 *
 * @return void
 */
	public function paintDocumentStart() {

	}

/**
 * Paints the end of the response from the test suite.
 * Used to paint things like </body> in an html page.
 *
 * @return void
 */
	public function paintDocumentEnd() {
		
	}

/**
 * Paint a list of test sets, core, app, and plugin test sets
 * available.
 *
 * @return void
 */
	public function paintTestMenu() {
		
	}

/**
 * Get the baseUrl if one is available.
 *
 * @return string The base url for the request.
 */
	public function baseUrl() {
		if (!empty($_SERVER['PHP_SELF'])) {
			return $_SERVER['PHP_SELF'];
		}
		return '';
	}

	public function paintResult(PHPUnit_Framework_TestResult $result) {
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
		echo sprintf(__('Running  %s'), $suite->getName()) . "\n";
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