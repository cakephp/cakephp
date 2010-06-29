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
					'/something/dispatcher.php' => array(
						10 => 1,
						12 => 1
					),
				), 
				'executable' => array(
					'/something/dispatcher.php' => array(
						9 => -1
					)
				),
				'dead' => array(
					'/something/dispatcher.php' => array(
						22 => -2,
						23 => -2
					)
				)
			),
			array(
				'files' => array(
					'/something/dispatcher.php' => array(
						10 => 1,
						50 => 1,
					),
				),
				'executable' => array(
					'/something/dispatcher.php' => array(
						12 => -1,
						51 => -1
					)
				),
				'dead' => array(
					'/something/dispatcher.php' => array(
						13 => -2,
						42 => -2
					)
				)
			),
		);
		$this->Coverage->setCoverage($data);
		$result = $this->Coverage->filterCoverageDataByPath('/something/');

		$path = '/something/dispatcher.php';
		$this->assertTrue(isset($result[$path]));
		$this->assertEquals(array(10, 12, 50), array_keys($result[$path]['covered']));
		$this->assertEquals(array(9, 12, 51), array_keys($result[$path]['executable']));
		$this->assertEquals(array(22, 23, 13, 42), array_keys($result[$path]['dead']));
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
			'covered' => array(
				1 => 1,
				3 => 1,
				4 => 1,
				6 => 1,
				7 => 1,
				8 => 1,
				10 => 1
			),
			'executable' => array(
				5 => -1,
				9 => -1
			),
			'dead' => array(
				2 => -2
			)
		);
		$result = $this->Coverage->generateDiff('myfile.php', $file, $coverage);
		$this->assertRegExp('/myfile\.php Code coverage\: \d+\.?\d*\%/', $result);
		$this->assertRegExp('/<div class="code-coverage-results" id\="coverage\-myfile\.php"/', $result);
		$this->assertRegExp('/<pre>/', $result);
		foreach ($file as $i => $line) {
			$this->assertTrue(strpos($line, $result) !== 0, 'Content is missing ' . $i);
			$class = 'covered';
			if (in_array($i + 1, array(5, 9, 2))) {
				$class = 'uncovered';
			}
			if ($i + 1 == 2) {
				$class .= ' dead';
			}
			$this->assertTrue(strpos($class, $result) !== 0, 'Class name is wrong ' . $i);
		}
	}

/**
 * test that covering methods show up as title attributes for lines.
 *
 * @return void
 */
	function testCoveredLinesTitleAttributes() {
		$file = array(
			'line 1',
			'line 2',
			'line 3',
			'line 4',
			'line 5',
		);
		$mock = $this->getMock('PHPUnit_Framework_TestCase');
		$mock->expects($this->any())->method('getName')->will($this->returnValue('testAwesomeness'));

		$rawdata = array(
			array(
				'test' => $mock,
				'files' => array(
					'myfile.php' => array(
						1 => 1,
						3 => 1,
						4 => 1,
					)
				),
				'executable' => array(
					'myfile.php' => array(
						5 => -1
					)
				)
			)
		);

		$coverage = array(
			'covered' => array(
				1 => 1,
				3 => 1,
				4 => 1,
			),
			'executable' => array(
				5 => -1,
			),
			'dead' => array(
				2 => -2
			)
		);
		$this->Coverage->setCoverage($rawdata);
		$result = $this->Coverage->generateDiff('myfile.php', $file, $coverage);

		$this->assertTrue(
			strpos($result, "title=\"Covered by:\ntestAwesomeness\n\"><span class=\"line-num\">1") !== false,
			'Missing method coverage for line 1'
		);
		$this->assertTrue(
			strpos($result, "title=\"Covered by:\ntestAwesomeness\n\"><span class=\"line-num\">3") !== false,
			'Missing method coverage for line 3'
		);
		$this->assertTrue(
			strpos($result, "title=\"Covered by:\ntestAwesomeness\n\"><span class=\"line-num\">4") !== false,
			'Missing method coverage for line 4'
		);
		$this->assertTrue(
			strpos($result, "title=\"\"><span class=\"line-num\">5") !== false,
			'Coverage report is wrong for line 5'
		);
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