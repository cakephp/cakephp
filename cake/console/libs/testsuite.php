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
		$params = $this->parseArgs();

		$this->_dispatcher = new CakeTestSuiteDispatcher();
		$this->_dispatcher->setParams($params);
	}

/**
 * Parse the CLI options into an array CakeTestDispatcher can use.
 *
 * @return array Array of params for CakeTestDispatcher
 */
	public function parseArgs() {
		if (empty($this->args)) {
			return;
		}
		$params = array(
			'app' => false,
			'plugin' => null,
			'output' => 'text',
			'codeCoverage' => false,
			'filter' => false,
			'case' => null
		);

		$category = $this->args[0];

		if ($category == 'app') {
			$params['app'] = true;
		} elseif ($category != 'core') {
			$params['plugin'] = $category;
		}

		if (isset($this->args[1])) {
			$type = $this->args[1];
		}

		if (isset($this->args[2])) {
			if ($this->args[2] == 'cov') {
				$params['codeCoverage'] = true;
			} else {
				$params['case'] = Inflector::underscore($this->args[2]) . '.test.php';
			}
		}
		if (isset($this->args[3]) && $this->args[3] == 'cov') {
			$params['codeCoverage'] = true;
		}
		return $params;
	}

/**
 * Main entry point to this shell
 *
 * @return void
 */
	public function main() {
		$this->out(__('CakePHP Test Shell'));
		$this->hr();

		if (count($this->args) == 0) {
			$this->error(__('Sorry, you did not pass any arguments!'));
		}

		$result = $this->_dispatcher->dispatch();
		$exit = 0;
		if ($result instanceof PHPUnit_Framework_TestResult) {
			$exit = ($result->errorCount() + $result->failureCount()) > 0;
		}
		$this->_stop($exitCode);
	}

/**
 * Help screen
 *
 * @return void
 */
	public function help() {
		$this->out('Usage: ');
		$this->out("\tcake testsuite category test_type file");
		$this->out("\t\t- category - \"app\", \"core\" or name of a plugin");
		$this->out("\t\t- test_type - \"case\", \"group\" or \"all\"");
		$this->out("\t\t- test_file - file name with folder prefix and without the (test|group).php suffix");
		$this->out();
		$this->out('Examples: ');
		$this->out("\t\tcake testsuite app all");
		$this->out("\t\tcake testsuite core all");
		$this->out();
		$this->out("\t\tcake testsuite app case behaviors/debuggable");
		$this->out("\t\tcake testsuite app case models/my_model");
		$this->out("\t\tcake testsuite app case controllers/my_controller");
		$this->out();
		$this->out("\t\tcake testsuite core case libs/file");
		$this->out("\t\tcake testsuite core case libs/router");
		$this->out("\t\tcake testsuite core case libs/set");
		$this->out();
		$this->out("\t\tcake testsuite bugs case models/bug");
		$this->out("\t\t  // for the plugin 'bugs' and its test case 'models/bug'");
		$this->out();
		$this->out('Code Coverage Analysis: ');
		$this->out("\n\nAppend 'cov' to any of the above in order to enable code coverage analysis");
	}
}
