<?php
/**
 * TestRunner for CakePHP Test suite.
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
 * @package       cake.tests.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
require 'PHPUnit/TextUI/TestRunner.php';

PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');

/**
 * Class to customize loading of test suites from CLI
 *
 * @package       cake.tests.lib
 */
class CakeTestRunner extends PHPUnit_TextUI_TestRunner {

/**
 * Actually run a suite of tests.  Cake initializes fixtures here using the chosen fixture manager
 *
 * @param PHPUnit_Framework_Test $suite 
 * @param array $arguments 
 * @return void
 */
	public function doRun(PHPUnit_Framework_Test $suite, array $arguments = array()) {
		$fixture = $this->_getFixtureManager($arguments);
		foreach ($suite->getIterator() as $test) {
			if ($test instanceof CakeTestCase) {
				$fixture->fixturize($test);
				$test->fixtureManager = $fixture;
			}
		}
		$return = parent::doRun($suite, $arguments);
		$fixture->shutdown();
		return $return;
	}

/**
 * Get the fixture manager class specified or use the default one.
 *
 * @return instance of a fixture manager.
 */
	protected function _getFixtureManager($arguments) {
		if (!isset($arguments['fixtureManager'])) {
			return new CakeFixtureManager();
		}
		App::import('Lib', 'test_suite/' . Inflector::underscore($arguments['fixtureManagerΩ']));
		if (class_exists($arguments['fixtureManager'])) {
			return new $arguments['fixtureManager'];
		}
		throw new Exception('No fixture manager found.');
	}
}