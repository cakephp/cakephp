<?php
/**
 * CakeTestSuiteDispatcherTest file
 *
 * Test Case for CakeTestSuiteDispatcher class
 *
 * PHP version 5
 *
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP v 2.3.2
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class CakeTestSuiteDispatcherTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		$this->vendors = App::path('vendors');
		$this->includePath = ini_get('include_path');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		App::build(array('Vendor' => $this->vendors), App::RESET);
		ini_set('include_path', $this->includePath);
	}

/**
 * Helper method to set vendor path
 *
 * @return void
 */
	protected function _clearPaths() {
		App::build(array('Vendor' => array('junk')), App::RESET);
		ini_set('include_path', 'junk');
	}

/**
 * testLoadTestFramework method
 *
 * @return void
 */
	public function testLoadTestFramework() {
		$dispatcher = new CakeTestSuiteDispatcher();

		$this->assertTrue($dispatcher->loadTestFramework());

		$this->_clearPaths();

		$this->setExpectedException('PHPUnit_Framework_Error_Warning');
		$dispatcher->loadTestFramework();
	}

}