<?php
/**
 * A class to contain test cases and run them with shered fixtures
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'DEFAULT');

class CakeTestSuite extends PHPUnit_Framework_TestSuite {

/**
 * Adds all the files in a directory to the test suite. Does not recurse through directories.
 *
 * @param string $directory The directory to add tests from.
 * @return void
 */
	public function addTestDirectory($directory = '.') {
		$files = new DirectoryIterator($directory);

		foreach ($files as $file) {
			if (!$file->isFile()) {
				continue;
			}
			$file = $file->getRealPath();
			$this->addTestFile($file);
		}
	}

/**
 * Recursively adds all the files in a directory to the test suite.
 *
 * @param string $directory The directory subtree to add tests from.
 * @return void
 */
	public function addTestDirectoryRecursive($directory = '.') {
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

		foreach ($files as $file) {
			if (!$file->isFile()) {
				continue;
			}
			$file = $file->getRealPath();
			$this->addTestFile($file);
		}
	}

}