<?php
/**
 * Test Suite Shell
 *
 * This Shell allows the running of test suites via the cake command line
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.console.libs
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
class TestSuiteShell extends Shell {

/**
 * Dispatcher object for the run.
 *
 * @var CakeTestDispatcher
 */
	protected $_dispatcher = null;

/**
 * Initialization method installs Simpletest and loads all plugins
 *
 * @return void
 */
	public function initialize() {
		require_once CAKE . 'tests' . DS . 'lib' . DS . 'cake_test_suite_dispatcher.php';

		$corePath = App::core('cake');
		if (isset($corePath[0])) {
			define('TEST_CAKE_CORE_INCLUDE_PATH', rtrim($corePath[0], DS) . DS);
		} else {
			define('TEST_CAKE_CORE_INCLUDE_PATH', CAKE_CORE_INCLUDE_PATH);
		}
		$this->_dispatcher = new CakeTestSuiteDispatcher();
		$this->_dispatcher->loadTestFramework();
	}

/**
 * Parse the CLI options into an array CakeTestDispatcher can use.
 *
 * @return array Array of params for CakeTestDispatcher
 */
	protected function parseArgs() {
		if (empty($this->args)) {
			return;
		}
		$params = array(
			'app' => false,
			'plugin' => null,
			'output' => 'text',
		);

		$category = $this->args[0];

		if ($category == 'app') {
			$params['app'] = true;
		} elseif ($category != 'core') {
			$params['plugin'] = $category;
		}

		if (isset($this->args[1])) {
			$params['case'] = Inflector::underscore($this->args[1]);
		}
		if (isset($this->args[2]) && $this->args[2] == 'cov') {
			$params['codeCoverage'] = true;
		}
		if (isset($this->params['coverage'])) {
			$params['codeCoverage'] = true;
		}
		return $params;
	}

/**
 * Converts the options passed to the shell as options for the PHPUnit cli runner
 *
 * @return array Array of params for CakeTestDispatcher
 */
	protected function runnerOptions() {
		$options = array();
		foreach ($this->params as $param => $value) {
			if ($param[0] === '-') {
				$options[] = '-' . $param;
				if (is_string($value)) {
					$options[] = $value;
				}
			}
		}
		return $options;
	}

/**
 * Main entry point to this shell
 *
 * @return void
 */
	public function main() {
		$this->out(__('CakePHP Test Shell'));
		$this->hr();

		require_once CAKE . 'tests' . DS . 'lib' . DS . 'test_runner.php';
		$testCli = new TestRunner($this->parseArgs());
		$testCli->run($this->runnerOptions());
	}

/**
 * Help screen
 *
 * @return void
 */
	public function help() {
		$help = <<<TEXT
Usage:
-----
cake testsuite <category> <file> [params]
	- category - "app", "core" or name of a plugin
	- file - file name with folder prefix and without the test.php suffix.
	
Params:
-------
  -filter
	The -filter option allows you supply a pattern that is used to match
	test method names. This can be a regular expression.

  -coverage
	Enable code coverage for this run.

Examples:
---------
	cake testsuite app behaviors/debuggable;
	cake testsuite app models/my_model;
	cake testsuite app controllers/my_controller
	
	cake testsuite core libs/file
	cake testsuite core libs/router
	cake testsuite core libs/set

	cake testsuite bugs models/bug
	// for the plugin 'bugs' and its test case 'models/bug'

TEXT;
		$this->out($help);
	}
}
