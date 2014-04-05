<?php
/**
 * View Group Test for DebugKit
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         DebugKit 1.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

require_once dirname(__FILE__) . DS . 'DebugkitGroupTestCase.php';

/**
 * DebugKitViewTestSuite class
 *
 * @since         DebugKit 1.0
 */
class AllDebugKitWithoutViewTest extends DebugkitGroupTestCase {

/**
 * Assemble Test Suite
 *
 * @return PHPUnit_Framework_TestSuite the instance of PHPUnit_Framework_TestSuite
 */
	public static function suite() {
		$suite = new self;
		$files = $suite->getTestFiles(null, 'View');
		$suite->addTestFiles($files);

		return $suite;
	}
}
