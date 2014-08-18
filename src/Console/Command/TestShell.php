<?php
/**
 * Test Shell
 *
 * This Shell allows the running of test suites via the cake command line
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\TestSuite\TestLoader;
use Cake\TestSuite\TestSuiteCommand;
use Cake\TestSuite\TestSuiteDispatcher;
use Cake\Utility\Inflector;

/**
 * Stub that tells people how to run tests with PHPUnit.
 *
 * @deprecated
 */
class TestShell extends Shell {

/**
 * Main entry point to this shell
 *
 * @return void
 */
	public function main() {
		$this->outputWarning();
		return 255;
	}

/**
 * Shows a list of available test cases and gives the option to run one of them
 *
 * @return void
 */
	public function available() {
		$this->outputWarning();
		return 255;
	}

/**
 * Warning that test shell is defunct
 *
 * @return void
 */
	public function outputWarning() {
		$this->err('<error>Test Shell has been removed.</error>');
		$this->err('');
		$this->err('TestShell has been removed and replaced with <info>phpunit</info>.');
		$this->err('');
		$this->err('To run your application tests run <info>phpunit --stderr</info>');
		$this->err('To run plugin tests, cd into the plugin directory and run <info>phpunit --stderr</info>');
	}

}
