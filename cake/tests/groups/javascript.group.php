<?php
/**
 * AllCoreJavascriptHelpersGroupTest file
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.groups
 * @since         CakePHP(tm) v 1.3
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
/**
 * AllCoreJavascriptHelpersGroupTest class
 *
 * This test group will run all test in the cases/libs/view/helpers directory related
 * to Js helper and its engines
 *
 * @package       cake
 * @subpackage    cake.tests.groups
 */
class JavascriptGroupTest extends CakeTestSuite {
/**
 * label property
 *
 * @var string 'All core helpers'
 * @access public
 */
	public $label = 'Js Helper and all Engine Helpers';
/**
 * AllCoreHelpersGroupTest method
 *
 * @access public
 * @return void
 */
	function __construct($theClass = '', $name = '') {
		parent::__construct($theClass, $name);
		$helperTestPath = CORE_TEST_CASES . DS . 'libs' . DS . 'view' . DS . 'helpers' . DS;
		$this->addTestFile($helperTestPath . 'js.test.php');
		$this->addTestFile($helperTestPath . 'jquery_engine.test.php');
		$this->addTestFile($helperTestPath . 'mootools_engine.test.php');
		$this->addTestFile($helperTestPath . 'prototype_engine.test.php');
	}
}
