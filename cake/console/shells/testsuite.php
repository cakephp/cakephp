<?php
/**
 * Test Suite Shell
 *
 * This Shell allows the running of test suites via the cake command line
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.console.shells
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TestSuiteShell extends Shell {

/**
 * Dispatcher object for the run.
 *
 * @var CakeTestDispatcher
 */
	protected $_dispatcher = null;

/**
 * get the option parser for the test suite.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = new ConsoleOptionParser($this->name);
		$parser->description(array(
			'The CakePHP Testsuite allows you to run test cases from the command line',
			'If run with no command line arguments, a list of available core test cases will be shown'
		))->addArgument('category', array(
			'help' => __('app, core or name of a plugin.'),
			'required' => true
		))->addArgument('file', array(
			'help' => __('file name with folder prefix and without the test.php suffix.'),
			'required' => false,
		))->addOption('log-junit', array(
			'help' => __('<file> Log test execution in JUnit XML format to file.'),
			'default' => false
		))->addOption('log-json', array(
			'help' => __('<file> Log test execution in TAP format to file.'),
			'default' => false
		))->addOption('log-tap', array(
			'help' => __('<file> Log test execution in TAP format to file.'),
			'default' => false
		))->addOption('log-dbus', array(
			'help' => __('Log test execution to DBUS.'),
			'default' => false
		))->addOption('coverage-html', array(
			'help' => __('<dir> Generate code coverage report in HTML format.'),
			'default' => false
		))->addOption('coverage-clover', array(
			'help' => __('<file> Write code coverage data in Clover XML format.'),
			'default' => false
		))->addOption('testdox-html', array(
			'help' => __('<file> Write agile documentation in HTML format to file.'),
			'default' => false
		))->addOption('testdox-text', array(
			'help' => __('<file> Write agile documentation in Text format to file.'),
			'default' => false
		))->addOption('filter', array(
			'help' => __('<pattern> Filter which tests to run.'),
			'default' => false
		))->addOption('group', array(
			'help' => __('<name> Only runs tests from the specified group(s).'),
			'default' => false
		))->addOption('exclude-group', array(
			'help' => __('<name> Exclude tests from the specified group(s).'),
			'default' => false
		))->addOption('list-groups', array(
			'help' => __('List available test groups.'),
			'boolean' => true
		))->addOption('loader', array(
			'help' => __('TestSuiteLoader implementation to use.'),
			'default' => false
		))->addOption('repeat', array(
			'help' => __('<times> Runs the test(s) repeatedly.'),
			'default' => false
		))->addOption('tap', array(
			'help' => __('Report test execution progress in TAP format.'),
			'boolean' => true
		))->addOption('testdox', array(
			'help' => __('Report test execution progress in TestDox format.'),
			'default' => false,
			'boolean' => true
		))->addOption('no-colors', array(
			'help' => __('Do not use colors in output.'),
			'boolean' => true
		))->addOption('stderr', array(
			'help' => __('Write to STDERR instead of STDOUT.'),
			'boolean' => true
		))->addOption('stop-on-error', array(
			'help' => __('Stop execution upon first error or failure.'),
			'boolean' => true
		))->addOption('stop-on-failure', array(
			'help' => __('Stop execution upon first failure.'),
			'boolean' => true
		))->addOption('stop-on-skipped ', array(
			'help' => __('Stop execution upon first skipped test.'),
			'boolean' => true
		))->addOption('stop-on-incomplete', array(
			'help' => __('Stop execution upon first incomplete test.'),
			'boolean' => true
		))->addOption('strict', array(
			'help' => __('Mark a test as incomplete if no assertions are made.'),
			'boolean' => true
		))->addOption('wait', array(
			'help' => __('Waits for a keystroke after each test.'),
			'boolean' => true
		))->addOption('process-isolation', array(
			'help' => __('Run each test in a separate PHP process.'),
			'boolean' => true
		))->addOption('no-globals-backup', array(
			'help' => __('Do not backup and restore $GLOBALS for each test.'),
			'boolean' => true
		))->addOption('static-backup ', array(
			'help' => __('Backup and restore static attributes for each test.'),
			'boolean' => true
		))->addOption('syntax-check', array(
			'help' => __('Try to check source files for syntax errors.'),
			'boolean' => true
		))->addOption('bootstrap', array(
			'help' => __('<file> A "bootstrap" PHP file that is run before the tests.'),
			'default' => false
		))->addOption('configuraion', array(
			'help' => __('<file> Read configuration from XML file.'),
			'default' => false
		))->addOption('no-configuration', array(
			'help' => __('Ignore default configuration file (phpunit.xml).'),
			'boolean' => true
		))->addOption('include-path', array(
			'help' => __('<path(s)> Prepend PHP include_path with given path(s).'),
			'default' => false
		))->addOption('directive', array(
			'help' => __('key[=value] Sets a php.ini value.'),
			'default' => false
		));

		return $parser;
	}

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
		require_once CAKE . 'tests' . DS . 'lib' . DS . 'test_manager.php';
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
		return $params;
	}

/**
 * Converts the options passed to the shell as options for the PHPUnit cli runner
 *
 * @return array Array of params for CakeTestDispatcher
 */
	protected function runnerOptions() {
		$options = array();
		$params = $this->params;
		unset($params['help']);

		if (!empty($params['no-colors'])) {
			unset($params['no-colors'], $params['colors']);
		} else {
			$params['colors'] = true;
		}

		foreach ($params as $param => $value) {
			if ($value === false) {
				continue;
			}
			$options[] = '--' . $param;
			if (is_string($value)) {
				$options[] = $value;
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

		$args = $this->parseArgs();

		if (empty($args['case'])) {
			return $this->available();
		}

		$this->run($args, $this->runnerOptions());
	}

/**
 * Runs the test case from $runnerArgs
 *
 * @param array $runnerArgs list of arguments as obtained from parseArgs()
 * @param array $options list of options as constructed by runnerOptions()
 * @return void
 */
	protected function run($runnerArgs, $options = array()) {
		require_once CAKE . 'tests' . DS . 'lib' . DS . 'test_runner.php';

		restore_error_handler();
		restore_error_handler();

		$testCli = new TestRunner($runnerArgs);
		$testCli->run($options);
	}

/**
 * Shows a list of available test cases and gives the option to run one of them
 *
 * @return void
 */
	public function available() {
		$params = $this->parseArgs();
		$testCases = TestManager::getTestCaseList($params);
		$app = $params['app'];
		$plugin = $params['plugin'];

		$title = "Core Test Cases:";
		$category = 'core';
		if ($app) {
			$title = "App Test Cases:";
			$category = 'app';
		} elseif ($plugin) {
			$title = Inflector::humanize($plugin) . " Test Cases:";
			$category = $plugin;
		}

		if (empty($testCases)) {
			$this->out(__("No test cases available \n\n"));
			return $this->out($this->OptionParser->help());
		}

		$this->out($title);
		$i = 1;
		$cases = array();
		foreach ($testCases as $testCaseFile => $testCase) {
			$case = explode(DS, str_replace('.test.php', '', $testCase));
			$case[count($case) - 1] = Inflector::camelize($case[count($case) - 1]);
			$case = implode('/', $case);
			$this->out("[$i] $case");
			$cases[$i] = $case;
			$i++;
		}

		while ($choice = $this->in(__('What test case would you like to run?'), null, 'q')) {
			if (is_numeric($choice)  && isset($cases[$choice])) {
				$this->args[0] = $category;
				$this->args[1] = $cases[$choice];
				$this->run($this->parseArgs(), $this->runnerOptions());
				break;
			}

			if (is_string($choice) && in_array($choice, $cases)) {
				$this->args[0] = $category;
				$this->args[1] = $choice;
				$this->run($this->parseArgs(), $this->runnerOptions());
				break;
			}

			if ($choice == 'q') {
				break;
			}
		}
	}
}