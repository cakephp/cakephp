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
 * Sets the proper test suite to use and loads the test file in it.
 * this method gets called as a callback from the parent class
 *
 * @return void
 */
	protected function handleCustomTestSuite() {
		
	}
}