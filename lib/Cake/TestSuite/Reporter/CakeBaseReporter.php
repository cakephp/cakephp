<?php
/**
 * CakeBaseReporter contains common functionality to all cake test suite reporters.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once 'PHPUnit/TextUI/ResultPrinter.php';

/**
 * CakeBaseReporter contains common reporting features used in the CakePHP Test suite
 *
 * @package       Cake.TestSuite.Reporter
 */
class CakeBaseReporter extends PHPUnit_TextUI_ResultPrinter {

	protected $_headerSent = false;

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
 * - core - Core test being run.
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
 * Retrieves a list of test cases from the active Manager class,
 * displaying it in the correct format for the reporter subclass
 *
 * @return mixed
 */
	public function testCaseList() {
		$testList = CakeTestLoader::generateTestList($this->params);
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

	public function printResult(PHPUnit_Framework_TestResult $result) {
		$this->paintFooter($result);
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
		$this->paintException($e, $test);
	}

/**
* A failure occurred.
*
* @param  PHPUnit_Framework_Test $test
* @param  PHPUnit_Framework_AssertionFailedError $e
* @param  float $time
*/
	public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
		$this->paintFail($e, $test);
	}

/**
* Incomplete test.
*
* @param  PHPUnit_Framework_Test $test
* @param  Exception $e
* @param  float $time
*/
	public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
		$this->paintSkip($e, $test);
	}

/**
* Skipped test.
*
* @param  PHPUnit_Framework_Test $test
* @param  Exception $e
* @param  float $time
*/
	public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
		$this->paintSkip($e, $test);
	}

/**
 * A test suite started.
 *
 * @param  PHPUnit_Framework_TestSuite $suite
 */
	public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
		if (!$this->_headerSent) {
			echo $this->paintHeader();
		}
		echo __d('cake_dev', 'Running  %s', $suite->getName()) . "\n";
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
