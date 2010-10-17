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
 * @package       cake
 * @subpackage    cake.cake.console.libs
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
			'The CakPHP Testsuite allows you to run test cases from the command line',
			'If run with no command line arguments, a list of available core test cases will be shown'
		))->addArgument('category', array(
			'help' => __('app, core or name of a plugin.'),
			'required' => true
		))->addArgument('file', array(
			'help' => __('file name with folder prefix and without the test.php suffix.'),
			'required' => true,
		))->addOption('filter', array(
			'help' => __('Filter which tests to run.'),
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
		$params = array_filter($params);
		foreach ($params as $param => $value) {
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
			$this->available();
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
			$this->help();
			$this->_stop();
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

/**
 * Help screen
 *
 * @return void
 */
	public function help() {
		$this->out('CakePHP Testsuite:');
		$this->hr();

		$this->out('The CakPHP Testsuite allows you to run test cases from the command line');
		$this->out('If run with no command line arguments, a list of available core test cases will be shown');
		$this->hr();

		$this->out("Usage: cake testuite <category> <file> [params]");
		$this->out("\t- category: app, core or name of a plugin");
		$this->out("\t- file: file name with folder prefix and without the test.php suffix");
		$this->hr();

		$this->out("Usage: cake testuite available <category> [params]");
		$this->out("\t Shows a list of available testcases for the specified category");
		$this->out("\t Params list will be used for running the selected test case");
		$this->hr();

		$this->out('Examples:');
		$this->out('cake testsuite app models/my_model');
		$this->out("cake testsuite app controllers/my_controller \n");
		$this->out('cake testsuite core libs/file');
		$this->out("cake testsuite core libs/set \n");
		$this->out('cake testsuite bugs models/bug -- for the plugin bugs and its test case models/bug');
		$this->hr();

		$this->out('Params:');
		$this->out("--log-junit <file>       Log test execution in JUnit XML format to file.");
		$this->out("--log-json <file>        Log test execution in JSON format.");
		$this->out("--log-tap <file>         Log test execution in TAP format to file.");
	 	$this->out("--log-dbus               Log test execution to DBUS.");

		$this->out("--coverage-html <dir>    Generate code coverage report in HTML format.");
		$this->out("--coverage-clover <file> Write code coverage data in Clover XML format.");

		$this->out("--testdox-html <file>    Write agile documentation in HTML format to file.");
		$this->out("--testdox-text <file>    Write agile documentation in Text format to file.");

		$this->out("--filter <pattern>       Filter which tests to run.");
		$this->out("--group ...              Only runs tests from the specified group(s).");
		$this->out("--exclude-group ...      Exclude tests from the specified group(s).");
		$this->out("--filter <pattern>       Filter which tests to run.");
		$this->out("--list-groups            List available test groups.");

		$this->out("--loader <loader>        TestSuiteLoader implementation to use.");
		$this->out("--repeat <times>         Runs the test(s) repeatedly.");

		$this->out("--tap                    Report test execution progress in TAP format.");
		$this->out("--testdox                Report test execution progress in TestDox format.");

		$this->out("--colors                 Use colors in output.");
		$this->out("--stderr                 Write to STDERR instead of STDOUT.");
		$this->out("--stop-on-failure        Stop execution upon first error or failure.");
		$this->out("--stop-on-skipped        Stop execution upon first skipped test.");
		$this->out("--stop-on-incomplete     Stop execution upon first incomplete test.");
		$this->out("--strict                 Mark a test as incomplete if no assertions are made.");
		$this->out("--verbose                Output more verbose information.");
		$this->out("--wait                   Waits for a keystroke after each test.");

		$this->out("--skeleton-class         Generate Unit class for UnitTest in UnitTest.php.");
		$this->out("--skeleton-test          Generate UnitTest class for Unit in Unit.php.");

		$this->out("--process-isolation      Run each test in a separate PHP process.");
		$this->out("--no-globals-backup      Do not backup and restore \$GLOBALS for each test.");
		$this->out("--static-backup          Backup and restore static attributes for each test.");
		$this->out("--syntax-check           Try to check source files for syntax errors.");

		$this->out("--bootstrap <file>       A \"bootstrap\" PHP file that is run before the tests.");
		$this->out("--configuration <file>   Read configuration from XML file.");
		$this->out("--no-configuration       Ignore default configuration file (phpunit.xml).");
		$this->out("--include-path <path(s)> Prepend PHP's include_path with given path(s).");

		$this->out("-d key[=value]           Sets a php.ini value. \n");
	}

}
