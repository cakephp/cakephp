<?php
/**
 * AllConsoleLibsTest file
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * AllConsoleLibsTest class
 *
 * This test group will run all console lib classes.
 *
 * @package       Cake.Test.Case.Console
 */
class AllConsoleLibsTest extends PHPUnit_Framework_TestSuite {

/**
 * suite method, defines tests for this suite.
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All console lib classes');

		foreach (new DirectoryIterator(dirname(__FILE__)) as $file) {
			if (!$file->isFile() || strpos($file, 'All') === 0) {
				continue;
			}
			$fileName = $file->getRealPath();
			if (substr($fileName, -4) === '.php') {
				$suite->addTestFile($file->getRealPath());
			}
		}
		return $suite;
	}
}