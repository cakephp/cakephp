<?php

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
 * teardown
 *
 * @return void
 */
	function tearDown() {
		unset($this->Coverage);
	}
}