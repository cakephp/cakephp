<?php
/**
 * Test Suite Shell
 *
 * This is a bc wrapper for the newer Test shell
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('TestShell', 'Console/Command');
App::uses('AppShell', 'Console/Command');
App::uses('CakeTestSuiteDispatcher', 'TestSuite');
App::uses('CakeTestSuiteCommand', 'TestSuite');
App::uses('CakeTestLoader', 'TestSuite');

/**
 * Provides a CakePHP wrapper around PHPUnit.
 * Adds in CakePHP's fixtures and gives access to plugin, app and core test cases
 *
 * @package       Cake.Console.Command
 */
class TestsuiteShell extends TestShell {

/**
 * get the option parser for the test suite.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description(array(
			__d('cake_console', 'The CakePHP Testsuite allows you to run test cases from the command line'),
			__d('cake_console', '<warning>This shell is for backwards-compatibility only</warning>'),
			__d('cake_console', 'use the test shell instead')
		));

		return $parser;
	}

/**
 * Parse the CLI options into an array CakeTestDispatcher can use.
 *
 * @return array Array of params for CakeTestDispatcher
 */
	protected function _parseArgs() {
		if (empty($this->args)) {
			return;
		}
		$params = array(
			'core' => false,
			'app' => false,
			'plugin' => null,
			'output' => 'text',
		);

		$category = $this->args[0];

		if ($category == 'core') {
			$params['core'] = true;
		} elseif ($category == 'app') {
			$params['app'] = true;
		} elseif ($category != 'core') {
			$params['plugin'] = $category;
		}

		if (isset($this->args[1])) {
			$params['case'] = $this->args[1];
		}
		return $params;
	}

/**
 * Main entry point to this shell
 *
 * @return void
 */
	public function main() {
		$this->out(__d('cake_console', 'CakePHP Test Shell'));
		$this->hr();

		$args = $this->_parseArgs();

		if (empty($args['case'])) {
			return $this->available();
		}

		$this->_run($args, $this->_runnerOptions());
	}

}
