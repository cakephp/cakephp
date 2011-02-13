<?php
/**
 * CakeBaseReporter contains common functionality to all cake test suite reporters.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.libs.reporter
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require_once 'PHPUnit/TextUi/ResultPrinter.php';

PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');

/**
 * CakeBaseReporter contains common reporting features used in the CakePHP Test suite
 *
 * @package cake
 * @package    cake.tests.lib
 */
class CakeBaseReporter extends PHPUnit_TextUI_ResultPrinter {

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
 * Retrieves a list of test cases from the active Manager class,
 * displaying it in the correct format for the reporter subclass
 *
 * @return mixed
 */
	public function testCaseList() {
		$testList = $this->_generateTestList($this->params);
		return $testList;
	}

/**
 * Get the list of files for the test listing.
 *
 * @return void
 */
	protected function _generateTestList($params) {
		$directory = self::_getTestsPath($params);
		$fileList = self::_getTestFileList($directory);

		$testCases = array();
		foreach ($fileList as $testCaseFile) {
			$testCases[$testCaseFile] = str_replace($directory . DS, '', $testCaseFile);
		}
		return $testCases;
	}

/**
 * Returns a list of test files from a given directory
 *
 * @param string $directory Directory to get test case files from.
 * @static
 */
	protected static function &_getTestFileList($directory = '.') {
		$return = self::_getRecursiveFileList($directory, array('self', '_isTestCaseFile'));
		return $return;
	}

/**
 * Gets a recursive list of files from a given directory and matches then against
 * a given fileTestFunction, like isTestCaseFile()
 *
 * @param string $directory The directory to scan for files.
 * @param mixed $fileTestFunction
 * @static
 */
	protected static function &_getRecursiveFileList($directory = '.', $fileTestFunction) {
		$fileList = array();
		if (!is_dir($directory)) {
			return $fileList;
		}

		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

		foreach ($files as $file) {
			if (!$file->isFile()) {
				continue;
			}
			$file = $file->getRealPath();

			if (call_user_func_array($fileTestFunction, array($file))) {
				$fileList[] = $file;
			}
		}
		return $fileList;
	}
/**
 * Extension suffix for test case files.
 *
 * @var string
 */
	protected static $_testExtension = '.test.php';
/**
 * Tests if a file has the correct test case extension
 *
 * @param string $file
 * @return boolean Whether $file is a test case.
 * @static
 */
	protected static function _isTestCaseFile($file) {
		return self::_hasExpectedExtension($file, self::$_testExtension);
	}

/**
 * Check if a file has a specific extension
 *
 * @param string $file
 * @param string $extension
 * @return void
 * @static
 */
	protected static function _hasExpectedExtension($file, $extension) {
		return $extension == strtolower(substr($file, (0 - strlen($extension))));
	}

/**
 * Returns the given path to the test files depending on a given type of tests (core, app, plugin)
 *
 * @param array $params Array of parameters for getting test paths.
 *   Can contain app, type, and plugin params.
 * @return string The path tests are located on
 * @static
 */
	protected static function _getTestsPath($params) {
		$result = null;
		if (!empty($params['app'])) {
			$result = APP_TEST_CASES;
		} else if (!empty($params['plugin'])) {
			$pluginPath = App::pluginPath($params['plugin']);
			$result = $pluginPath . 'tests' . DS . 'cases';
		} else {
			$result = CORE_TEST_CASES;
		}
		return $result;
	}

/**
 * Get the extension for either 'group' or 'test' types.
 *
 * @param string $type Type of test to get, either 'test' or 'group'
 * @return string Extension suffix for test.
 */
	public static function getExtension($type = 'test') {
		return self::$_testExtension;
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
		echo __('Running  %s', $suite->getName()) . "\n";
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
