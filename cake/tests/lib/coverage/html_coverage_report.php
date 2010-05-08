<?php

/**
 * Generates an HTML coverage report from data provided by PHPUnit.
 *
 * @package default
 * @author Mark Story
 */
class HtmlCoverageReport {
/**
 * coverage data
 *
 * @var string
 */
	protected $_coverage;

/**
 * Constructor
 *
 * @param array $coverage Array of coverage data from PHPUnit_Test_Result
 * @return void
 */
	public function __construct($coverage) {
		$this->_coverage = $coverage;
	}

/**
 * Generates report html to display.
 *
 * @return string compiled html report.
 */
	public function report() {
		
	}
}