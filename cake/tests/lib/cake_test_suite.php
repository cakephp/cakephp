<?php
/**
 * A class to contain test cases and run them with shered fixtures
 *
 * PHP 5
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
 * @subpackage    cake.cake.tests.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class CakeTestSuite extends PHPUnit_Framework_TestSuite {

/**
 * Instance of a fixture manager
 * @var CakeFixtureManager
 */
	protected $_fixtureManager = null;

/**
 * Sets the intances for the fixture manager that will be used by this class
 * @param CakeFixtureManager $manager the instance of the manager class
 * @return void
 * @access public
 */
	public function setFixtureManager(CakeFixtureManager $manager) {
		$this->_fixtureManager = $manager;
	}

/**
 * Method that is called before the tests of this test suite are run.
 * It will load fixtures accordingly for each test
 * @return void
 * @access protected
 */
	protected function setUp() {
		parent::setUp();
		restore_error_handler();
		restore_error_handler();
		if (!$this->_fixtureManager) {
			return;
		}
		$classes = array();
		foreach ($this->getIterator() as $test) {
			$this->_fixtureManager->fixturize($test);
		}
		$this->sharedFixture = $this->_fixtureManager;
	}

/**
 * Method that is called after all the tests of this test suite are run.
 * @return void
 * @access protected
 */
	protected function tearDown() {
		parent::tearDown();
		if ($this->_fixtureManager) {
			$this->_fixtureManager->shutDown();
		}
		$this->_fixtureManager = null;
		$this->sharedFixture = null;
	}
}