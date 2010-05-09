<?php
/**
 * Test case for HtmlCoverageReport
 *
 * PHP5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

require_once CAKE . 'tests' . DS . 'lib' . DS . 'coverage' . DS . 'html_coverage_report.php';

class HtmlCoverageReportTest extends CakeTestCase {
/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		$reporter = new CakeBaseReporter();
		$reporter->params = array('app' => false, 'plugin' => false, 'group' => false);
		$coverage = array();
		$this->Coverage = new HtmlCoverageReport($coverage, $reporter);
	}

/**
 * test getting the path filters.
 *
 * @return void
 */
	function testGetPathFilter() {
		$this->Coverage->appTest = false;
		$result = $this->Coverage->getPathFilter();
		$this->assertEquals(TEST_CAKE_CORE_INCLUDE_PATH, $result);

		$this->Coverage->appTest = true;
		$result = $this->Coverage->getPathFilter();
		$this->assertEquals(ROOT . DS . APP_DIR . DS, $result);

		$this->Coverage->appTest = false;
		$this->Coverage->pluginTest = 'test_plugin';
		$result = $this->Coverage->getPathFilter();
		$this->assertEquals(ROOT . DS . APP_DIR . DS . 'plugins' . DS .'test_plugin' . DS, $result);
	}

/**
 * test filtering coverage data.
 *
 * @return void
 */
	function testFilterCoverageDataByPathRemovingElements() {
		$data = array(
			array(
				'files' => array(
					TEST_CAKE_CORE_INCLUDE_PATH . 'dispatcher.php' => array(
						10 => -1,
						12 => 1
					),
					APP . 'app_model.php' => array(
						50 => 1,
						52 => -1
					)
				)
			)
		);
		$this->Coverage->setCoverage($data);
		$result = $this->Coverage->filterCoverageDataByPath(TEST_CAKE_CORE_INCLUDE_PATH);
		$this->assertTrue(isset($result[TEST_CAKE_CORE_INCLUDE_PATH . 'dispatcher.php']));
		$this->assertFalse(isset($result[APP . 'app_model.php']));
	}

/**
 * test that filterCoverageDataByPath correctly merges data sets in each test run.
 *
 * @return void
 */
	function testFilterCoverageDataCorrectlyMergingValues() {
		$data = array(
			array(
				'files' => array(
					TEST_CAKE_CORE_INCLUDE_PATH . 'dispatcher.php' => array(
						10 => -1,
						12 => 1
					),
				)
			),
			array(
				'files' => array(
					TEST_CAKE_CORE_INCLUDE_PATH . 'dispatcher.php' => array(
						10 => 1,
						12 => -1,
						50 => 1,
						51 => -1
					),
				)
			),
		);
		$this->Coverage->setCoverage($data);
		$result = $this->Coverage->filterCoverageDataByPath(TEST_CAKE_CORE_INCLUDE_PATH);

		$path = TEST_CAKE_CORE_INCLUDE_PATH . 'dispatcher.php';
		$this->assertTrue(isset($result[$path]));
		$this->assertEquals(1, $result[$path][10]);
		$this->assertEquals(1, $result[$path][12]);
		$this->assertEquals(1, $result[$path][50]);
		$this->assertEquals(-1, $result[$path][51]);
	}

/**
 * test the features of getExecutableLines
 *
 * @return void
 */
	function testGetExecutableLines() {
		$contents = <<<PHP
<?php
/**
 * A comment line.
 */
function thing() {
	echo 'thinger';
}

function other_thing() {
	if (
		\$something == true
	) {
		doSomethingElse();
	}
}
?>
PHP;
		$result = $this->Coverage->getExecutableLines(explode("\n", $contents));
		$expected = array(
			0 => false,
			1 => true,
			2 => true,
			3 => true,
			4 => true,
			5 => true,
			6 => false,
			7 => true,
			8 => true,
			9 => true,
			10 => true,
			11 => false,
			12 => true,
			13 => false,
			14 => false,
			15 => false
		);
		$this->assertEquals($expected, $result);
	}

/**
 * test generating HTML reports from file arrays.
 *
 * @return void
 */
	function testGenerateDiff() {
		$file = array(
			'line 1',
			'line 2',
			'line 3',
			'line 4',
			'line 5',
			'line 6',
			'line 7',
			'line 8',
			'line 9',
			'line 10',
		);
		$coverage = array(
			1 => 1,
			2 => -2,
			3 => 1,
			4 => 1,
			5 => -1,
			6 => -1,
			7 => -1,
			8 => 1,
			9 => -1,
			10 => 1,
		);
		$result = $this->Coverage->generateDiff('myfile.php', $file, $coverage);
		$this->assertRegExp('/<h2>myfile\.php Code coverage\: \d+\.\d+\%<\/h2>/', $result);
		$this->assertRegExp('/<div class="code-coverage-results">/', $result);
		$this->assertRegExp('/<pre>/', $result);
		foreach ($file as $i => $line) {
			$this->assertTrue(strpos($line, $result) !== 0, 'Content is missing ' . $i);
			$class = 'uncovered';
			if ($coverage[$i + 1] > 0) {
				$class = 'covered';
			}
			$this->assertTrue(strpos($class, $result) !== 0, 'Class name is wrong ' . $i);
		}
	}

/**
 * teardown
 *
 * @return void
 */
	function tearDown() {
		unset($this->Coverage);
	}
}