<?php
/**
 * CodeCoverageManagerTest file
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
require_once CAKE . 'tests' . DS . 'lib' . DS . 'code_coverage_manager.php';
require_once CAKE . 'tests' . DS . 'lib' . DS . 'reporter' . DS . 'cake_cli_reporter.php';

/**
 * CodeCoverageManagerTest class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class CodeCoverageManagerTest extends CakeTestCase {

/**
 * Skip if XDebug not installed
 *
 * @access public
 */
	function skip() {
		$this->skipIf(!extension_loaded('xdebug'), '%s XDebug not installed');
	}

/**
 * startTest Method
 * Store reference of $_GET to restore later.
 *
 * @return void
 */
	function startCase() {
		$this->_get = $_GET;
	}

/**
 * End Case - restore GET vars.
 *
 * @return void
 */
	function endCase() {
		$_GET = $this->_get;
	}

/**
 * testNoTestCaseSupplied method
 *
 * @access public
 * @return void
 */
	function testNoTestCaseSupplied() {
		if ($this->skipIf(PHP_SAPI == 'cli', 'Is cli, cannot run this test %s')) {
			return;
		}
		$reporter =& new CakeHtmlReporter(null, array('group' => false, 'app' => false, 'plugin' => false));

		CodeCoverageManager::init(substr(md5(microtime()), 0, 5), $reporter);
		CodeCoverageManager::report(false);
		$this->assertError();

		CodeCoverageManager::init('tests' . DS . 'lib' . DS . basename(__FILE__), $reporter);
		CodeCoverageManager::report(false);
		$this->assertError();
	}

/**
 * Test that test cases don't cause errors
 *
 * @return void
 */
	function testNoTestCaseSuppliedNoErrors() {
		if ($this->skipIf(PHP_SAPI == 'cli', 'Is cli, cannot run this test %s')) {
			return;
		}
		$reporter =& new CakeHtmlReporter(null, array('group' => false, 'app' => false, 'plugin' => false));
		$path = LIBS;
		if (strpos(LIBS, ROOT) === false) {
			$path = ROOT.DS.LIBS;
		}
		App::import('Core', 'Folder');
		$folder = new Folder();
		$folder->cd($path);
		$contents = $folder->read();

		$contents[1] = array_filter($contents[1], array(&$this, '_basenameFilter'));

		foreach ($contents[1] as $file) {
			CodeCoverageManager::init('libs' . DS . $file, $reporter);
			CodeCoverageManager::report(false);
			$this->assertNoErrors('libs' . DS . $file);
		}
	}

/**
 * Remove file names that don't share a basename with the current file.
 *
 * @return void
 */
	function _basenameFilter($var) {
		return ($var != basename(__FILE__));
	}

/**
 * testGetTestObjectFileNameFromTestCaseFile method
 *
 * @access public
 * @return void
 */
	function testGetTestObjectFileNameFromTestCaseFile() {
		$manager =& CodeCoverageManager::getInstance();
		$manager->reporter = new CakeHtmlReporter();

		$expected = $manager->__testObjectFileFromCaseFile('models/some_file.test.php', true);
		$this->assertIdentical(APP.'models'.DS.'some_file.php', $expected);

		$expected = $manager->__testObjectFileFromCaseFile('models/datasources/some_file.test.php', true);
		$this->assertIdentical(APP.'models'.DS.'datasources'.DS.'some_file.php', $expected);

		$expected = $manager->__testObjectFileFromCaseFile('controllers/some_file.test.php', true);
		$this->assertIdentical(APP.'controllers'.DS.'some_file.php', $expected);

		$expected = $manager->__testObjectFileFromCaseFile('views/some_file.test.php', true);
		$this->assertIdentical(APP.'views'.DS.'some_file.php', $expected);

		$expected = $manager->__testObjectFileFromCaseFile('behaviors/some_file.test.php', true);
		$this->assertIdentical(APP.'models'.DS.'behaviors'.DS.'some_file.php', $expected);

		$expected = $manager->__testObjectFileFromCaseFile('components/some_file.test.php', true);
		$this->assertIdentical(APP.'controllers'.DS.'components'.DS.'some_file.php', $expected);

		$expected = $manager->__testObjectFileFromCaseFile('helpers/some_file.test.php', true);
		$this->assertIdentical(APP.'views'.DS.'helpers'.DS.'some_file.php', $expected);

		$manager->pluginTest = 'bugs';
		$expected = $manager->__testObjectFileFromCaseFile('models/some_file.test.php', false);
		$this->assertIdentical(APP.'plugins'.DS.'bugs'.DS.'models'.DS.'some_file.php', $expected);

		$manager->pluginTest = false;
		$manager->reporter = new CakeCliReporter;
		$expected = $manager->__testObjectFileFromCaseFile('libs/set.test.php', false);
		$this->assertIdentical(ROOT.DS.'cake'.DS.'libs'.DS.'set.php', $expected);
	}

/**
 * testOfHtmlDiffReport method
 *
 * @access public
 * @return void
 */
	function testOfHtmlDiffReport() {
		$manager =& CodeCoverageManager::getInstance();
		$code = <<<PHP
/**
 * Set class
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
		class Set extends Object {

/**
		 * Value of the Set object.
		 *
		 * @var array
		 * @access public
		 */
			var \$value = array();

/**
		 * Constructor. Defaults to an empty array.
		 *
		 * @access public
		 */
			function __construct() {
				if (func_num_args() == 1 && is_array(func_get_arg(0))) {
					\$this->value = func_get_arg(0);
				} else {
					\$this->value = func_get_args();
				}
			}

/**
		 * Returns the contents of the Set object
		 *
		 * @return array
		 * @access public
		 */
			function &get() {
				return \$this->value;
			}

/**
		 * This function can be thought of as a hybrid between PHP's array_merge and array_merge_recursive. The difference
		 * to the two is that if an array key contains another array then the function behaves recursive (unlike array_merge)
		 * but does not do if for keys containing strings (unlike array_merge_recursive). See the unit test for more information.
		 *
		 * Note: This function will work with an unlimited amount of arguments and typecasts non-array parameters into arrays.
		 *
		 * @param array \$arr1 Array to be merged
		 * @param array \$arr2 Array to merge with
		 * @return array Merged array
		 * @access public
		 */
			function merge(\$arr1, \$arr2 = null) {
				\$args = func_get_args();

				if (isset(\$this) && is_a(\$this, 'set')) {
					\$backtrace = debug_backtrace();
					\$previousCall = strtolower(\$backtrace[1]['class'].'::'.\$backtrace[1]['function']);
					if (\$previousCall != 'set::merge') {
						\$r =& \$this->value;
						array_unshift(\$args, null);
					}
				}
				if (!isset(\$r)) {
					\$r = (array)current(\$args);
				}

				while ((\$arg = next(\$args)) !== false) {
					if (is_a(\$arg, 'set')) {
						\$arg = \$arg->get();
					}

					foreach ((array)\$arg as \$key => \$val)	 {
						if (is_array(\$val) && isset(\$r[\$key]) && is_array(\$r[\$key])) {
							\$r[\$key] = Set::merge(\$r[\$key], \$val);
						} elseif (is_int(\$key)) {

						} else {
							\$r[\$key] = \$val;
						}
					}
				}
				return \$r;
			}
PHP;

		$testObjectFile = explode("\n", $code);
		$coverageData = array(
			0 => 1,
			1 => 1,
			2 => -2,
			3 => -2,
			4 => -2,
			5 => -2,
			6 => -2,
			7 => -2,
			8 => -1,
			9 => -2,
			10 => -2,
			11 => -2,
			12 => -2,
			13 => -2,
			14 => 1,
			15 => 1,
			16 => -1,
			17 => 1,
			18 => 1,
			19 => -1,
			20 => 1,
			21 => -2,
			22 => -2,
			23 => -2,
			24 => -2,
			25 => -2,
			26 => -2,
			27 => 1,
			28 => -1,
			29 => 1,
			30 => 1,
			31 => -2,
			32 => -2,
			33 => -2,
			34 => -2,
			35 => -2,
			36 => -2,
			37 => -2,
			38 => -2,
			39 => -2,
			40 => -2,
			41 => -2,
			42 => -2,
			43 => -1,
			44 => -2,
			45 => -2,
			46 => -2,
			47 => -2,
			48 => 1,
			49 => 1,
			50 => -1,
			51 => 1,
			52 => 1,
			53 => -2,
			54 => -2,
			55 => 1,
			56 => 1,
			57 => 1,
			58 => 1,
			59 => -1,
			60 => 1,
			61 => 1,
			62 => -2,
			63 => -2,
			64 => 1,
			65 => -2,
			66 => 1,
			67 => -1,
			68 => -2,
			69 => -1,
			70 => -1,
			71 => 1,
			72 => -2,
		);
		$expected = array(
			0 => 'ignored',
			1 => 'ignored',
			2 => 'ignored',
			3 => 'ignored',
			4 => 'ignored',
			5 => 'ignored show start realstart',
			6 => 'ignored show',
			7 => 'ignored show',
			8 => 'uncovered show',
			9 => 'ignored show',
			10 => 'ignored show',
			11 => 'ignored show end',
			12 => 'ignored',
			13 => 'ignored show start',
			14 => 'covered show',
			15 => 'covered show',
			16 => 'uncovered show',
			17 => 'covered show show',
			18 => 'covered show show',
			19 => 'uncovered show',
			20 => 'covered show',
			21 => 'ignored show',
			22 => 'ignored show end',
			23 => 'ignored',
			24 => 'ignored',
			25 => 'ignored show start',
			26 => 'ignored show',
			27 => 'covered show',
			28 => 'uncovered show',
			29 => 'covered show',
			30 => 'covered show',
			31 => 'ignored show end',
			32 => 'ignored',
			33 => 'ignored',
			34 => 'ignored',
			35 => 'ignored',
			36 => 'ignored',
			37 => 'ignored',
			38 => 'ignored',
			39 => 'ignored',
			40 => 'ignored show start',
			41 => 'ignored show',
			42 => 'ignored show',
			43 => 'uncovered show',
			41 => 'ignored show',
			42 => 'ignored show',
			43 => 'uncovered show',
			44 => 'ignored show',
			45 => 'ignored show',
			46 => 'ignored show',
			47 => 'ignored show',
			48 => 'covered show',
			49 => 'covered show',
			50 => 'uncovered show',
			51 => 'covered show',
			52 => 'covered show',
			53 => 'ignored show end',
			54 => 'ignored',
			55 => 'covered',
			56 => 'covered show start',
			57 => 'covered show',
			58 => 'covered show',
			59 => 'uncovered show',
			60 => 'covered show',
			61 => 'covered show',
			62 => 'ignored show end',
			63 => 'ignored',
			64 => 'covered show start',
			65 => 'ignored show',
			66 => 'covered show show',
			67 => 'uncovered show',
			68 => 'ignored show',
			69 => 'uncovered show',
			70 => 'uncovered show',
			71 => 'covered show',
			72 => 'ignored show',
			73 => 'ignored show end end',
		);
		$execCodeLines = range(0, 72);
		$result = explode("</div>", $report = $manager->reportCaseHtmlDiff($testObjectFile, $coverageData, $execCodeLines, 3));

		foreach ($result as $line) {
			preg_match('/<span class="line-num">(.*?)<\/span>/', $line, $matches);
			if (!isset($matches[1])) {
				continue;
			}

			$num = $matches[1];
			$class = $expected[$num];
			$pattern = '/<div class="code-line '.$class.'">/';
			$this->assertPattern($pattern, $line, $num.': '.$line." fails");
		}
	}

/**
 * testArrayStrrpos method
 *
 * @access public
 * @return void
 */
	function testArrayStrrpos() {
		$manager =& CodeCoverageManager::getInstance();

		$a = array(
			'apples',
			'bananas',
			'oranges'
		);
		$this->assertEqual(1, $manager->__array_strpos($a, 'ba', true));
		$this->assertEqual(2, $manager->__array_strpos($a, 'range', true));
		$this->assertEqual(0, $manager->__array_strpos($a, 'pp', true));
		$this->assertFalse($manager->__array_strpos('', 'ba', true));
		$this->assertFalse($manager->__array_strpos(false, 'ba', true));
		$this->assertFalse($manager->__array_strpos(array(), 'ba', true));

		$a = array(
			'rang',
			'orange',
			'oranges'
		);
		$this->assertEqual(0, $manager->__array_strpos($a, 'rang'));
		$this->assertEqual(2, $manager->__array_strpos($a, 'rang', true));
		$this->assertEqual(1, $manager->__array_strpos($a, 'orange', false));
		$this->assertEqual(1, $manager->__array_strpos($a, 'orange'));
		$this->assertEqual(2, $manager->__array_strpos($a, 'orange', true));
	}

/**
 * testGetExecutableLines method
 *
 * @access public
 * @return void
 */
	function testGetExecutableLines() {
		$manager =& CodeCoverageManager::getInstance();
		$code = <<<HTML
			\$manager =& CodeCoverageManager::getInstance();
HTML;
		$result = $manager->__getExecutableLines($code);
		foreach ($result as $line) {
			$this->assertNotIdentical($line, '');
		}

		$code = <<<HTML
		{
		}
		<?php?>
		?>
		<?
}
{{}}
(())
		@codeCoverageIgnoreStart
		some
		more
		code
		here
		@codeCoverageIgnoreEnd
HTML;
		$result = $manager->__getExecutableLines($code);
		foreach ($result as $line) {
			$this->assertIdentical(trim($line), '');
		}
	}

/**
 * testCalculateCodeCoverage method
 *
 * @access public
 * @return void
 */
	function testCalculateCodeCoverage() {
		$manager =& CodeCoverageManager::getInstance();
		$data = array(
			'25' => array(100, 25),
			'50' => array(100, 50),
			'0' => array(0, 0),
			'0' => array(100, 0),
			'100' => array(100, 100),
		);
		foreach ($data as $coverage => $lines) {
			$this->assertEqual($coverage, $manager->__calcCoverage($lines[0], $lines[1]));
		}

		$manager->__calcCoverage(100, 1000);
		$this->assertError();
	}
}
?>