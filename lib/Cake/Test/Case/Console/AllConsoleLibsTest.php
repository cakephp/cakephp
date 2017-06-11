<?php
/**
 * AllConsoleLibsTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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
