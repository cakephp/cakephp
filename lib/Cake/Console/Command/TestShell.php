<?php
/**
 * Test Shell
 *
 * This Shell allows the running of test suites via the cake command line
 *
 * PHP 5
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
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Shell', 'Console');
App::uses('CakeTestSuiteDispatcher', 'TestSuite');
App::uses('CakeTestSuiteCommand', 'TestSuite');
App::uses('CakeTestLoader', 'TestSuite');

/**
 * Provides a CakePHP wrapper around PHPUnit.
 * Adds in CakePHP's fixtures and gives access to plugin, app and core test cases
 *
 * @package       Cake.Console.Command
 */
class TestShell extends Shell {

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
			__d('cake_console', 'The CakePHP Testsuite allows you to run test cases from the command line'),
		))->addArgument('category', array(
			'help' => __d('cake_console', 'The category for the test, or test file, to test.'),
			'required' => false,
		))->addArgument('file', array(
			'help' => __d('cake_console', 'The path to the file, or test file, to test.'),
			'required' => false,
		))->addOption('log-junit', array(
			'help' => __d('cake_console', '<file> Log test execution in JUnit XML format to file.'),
			'default' => false
		))->addOption('log-json', array(
			'help' => __d('cake_console', '<file> Log test execution in JSON format to file.'),
			'default' => false
		))->addOption('log-tap', array(
			'help' => __d('cake_console', '<file> Log test execution in TAP format to file.'),
			'default' => false
		))->addOption('log-dbus', array(
			'help' => __d('cake_console', 'Log test execution to DBUS.'),
			'default' => false
		))->addOption('coverage-html', array(
			'help' => __d('cake_console', '<dir> Generate code coverage report in HTML format.'),
			'default' => false
		))->addOption('coverage-clover', array(
			'help' => __d('cake_console', '<file> Write code coverage data in Clover XML format.'),
			'default' => false
		))->addOption('testdox-html', array(
			'help' => __d('cake_console', '<file> Write agile documentation in HTML format to file.'),
			'default' => false
		))->addOption('testdox-text', array(
			'help' => __d('cake_console', '<file> Write agile documentation in Text format to file.'),
			'default' => false
		))->addOption('filter', array(
			'help' => __d('cake_console', '<pattern> Filter which tests to run.'),
			'default' => false
		))->addOption('group', array(
			'help' => __d('cake_console', '<name> Only runs tests from the specified group(s).'),
			'default' => false
		))->addOption('exclude-group', array(
			'help' => __d('cake_console', '<name> Exclude tests from the specified group(s).'),
			'default' => false
		))->addOption('list-groups', array(
			'help' => __d('cake_console', 'List available test groups.'),
			'boolean' => true
		))->addOption('loader', array(
			'help' => __d('cake_console', 'TestSuiteLoader implementation to use.'),
			'default' => false
		))->addOption('repeat', array(
			'help' => __d('cake_console', '<times> Runs the test(s) repeatedly.'),
			'default' => false
		))->addOption('tap', array(
			'help' => __d('cake_console', 'Report test execution progress in TAP format.'),
			'boolean' => true
		))->addOption('testdox', array(
			'help' => __d('cake_console', 'Report test execution progress in TestDox format.'),
			'default' => false,
			'boolean' => true
		))->addOption('no-colors', array(
			'help' => __d('cake_console', 'Do not use colors in output.'),
			'boolean' => true
		))->addOption('stderr', array(
			'help' => __d('cake_console', 'Write to STDERR instead of STDOUT.'),
			'boolean' => true
		))->addOption('stop-on-error', array(
			'help' => __d('cake_console', 'Stop execution upon first error or failure.'),
			'boolean' => true
		))->addOption('stop-on-failure', array(
			'help' => __d('cake_console', 'Stop execution upon first failure.'),
			'boolean' => true
		))->addOption('stop-on-skipped', array(
			'help' => __d('cake_console', 'Stop execution upon first skipped test.'),
			'boolean' => true
		))->addOption('stop-on-incomplete', array(
			'help' => __d('cake_console', 'Stop execution upon first incomplete test.'),
			'boolean' => true
		))->addOption('strict', array(
			'help' => __d('cake_console', 'Mark a test as incomplete if no assertions are made.'),
			'boolean' => true
		))->addOption('wait', array(
			'help' => __d('cake_console', 'Waits for a keystroke after each test.'),
			'boolean' => true
		))->addOption('process-isolation', array(
			'help' => __d('cake_console', 'Run each test in a separate PHP process.'),
			'boolean' => true
		))->addOption('no-globals-backup', array(
			'help' => __d('cake_console', 'Do not backup and restore $GLOBALS for each test.'),
			'boolean' => true
		))->addOption('static-backup', array(
			'help' => __d('cake_console', 'Backup and restore static attributes for each test.'),
			'boolean' => true
		))->addOption('syntax-check', array(
			'help' => __d('cake_console', 'Try to check source files for syntax errors.'),
			'boolean' => true
		))->addOption('bootstrap', array(
			'help' => __d('cake_console', '<file> A "bootstrap" PHP file that is run before the tests.'),
			'default' => false
		))->addOption('configuration', array(
			'help' => __d('cake_console', '<file> Read configuration from XML file.'),
			'default' => false
		))->addOption('no-configuration', array(
			'help' => __d('cake_console', 'Ignore default configuration file (phpunit.xml).'),
			'boolean' => true
		))->addOption('include-path', array(
			'help' => __d('cake_console', '<path(s)> Prepend PHP include_path with given path(s).'),
			'default' => false
		))->addOption('directive', array(
			'help' => __d('cake_console', 'key[=value] Sets a php.ini value.'),
			'default' => false
		))->addOption('fixture', array(
			'help' => __d('cake_console', 'Choose a custom fixture manager.'),
		))->addOption('debug', array(
			'help' => __d('cake_console', 'More verbose output.'),
		));

		return $parser;
	}

/**
 * Initialization method installs PHPUnit and loads all plugins
 *
 * @return void
 * @throws Exception
 */
	public function initialize() {
		$this->_dispatcher = new CakeTestSuiteDispatcher();
		$success = $this->_dispatcher->loadTestFramework();
		if (!$success) {
			throw new Exception(__d('cake_dev', 'Please install PHPUnit framework <info>(http://www.phpunit.de)</info>'));
		}
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

		if (strpos($this->args[0], '.php')) {
			$category = $this->_mapFileToCategory($this->args[0]);
			$params['case'] = $this->_mapFileToCase($this->args[0], $category);
		} else {
			$category = $this->args[0];
			if (isset($this->args[1])) {
				$params['case'] = $this->args[1];
			}
		}

		if ($category === 'core') {
			$params['core'] = true;
		} elseif ($category === 'app') {
			$params['app'] = true;
		} else {
			$params['plugin'] = $category;
		}

		return $params;
	}

/**
 * Converts the options passed to the shell as options for the PHPUnit cli runner
 *
 * @return array Array of params for CakeTestDispatcher
 */
	protected function _runnerOptions() {
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
		$this->out(__d('cake_console', 'CakePHP Test Shell'));
		$this->hr();

		$args = $this->_parseArgs();

		if (empty($args['case'])) {
			return $this->available();
		}

		$this->_run($args, $this->_runnerOptions());
	}

/**
 * Runs the test case from $runnerArgs
 *
 * @param array $runnerArgs list of arguments as obtained from _parseArgs()
 * @param array $options list of options as constructed by _runnerOptions()
 * @return void
 */
	protected function _run($runnerArgs, $options = array()) {
		restore_error_handler();
		restore_error_handler();

		$testCli = new CakeTestSuiteCommand('CakeTestLoader', $runnerArgs);
		$testCli->run($options);
	}

/**
 * Shows a list of available test cases and gives the option to run one of them
 *
 * @return void
 */
	public function available() {
		$params = $this->_parseArgs();
		$testCases = CakeTestLoader::generateTestList($params);
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
			$this->out(__d('cake_console', "No test cases available \n\n"));
			return $this->out($this->OptionParser->help());
		}

		$this->out($title);
		$i = 1;
		$cases = array();
		foreach ($testCases as $testCase) {
			$case = str_replace('Test.php', '', $testCase);
			$this->out("[$i] $case");
			$cases[$i] = $case;
			$i++;
		}

		while ($choice = $this->in(__d('cake_console', 'What test case would you like to run?'), null, 'q')) {
			if (is_numeric($choice) && isset($cases[$choice])) {
				$this->args[0] = $category;
				$this->args[1] = $cases[$choice];
				$this->_run($this->_parseArgs(), $this->_runnerOptions());
				break;
			}

			if (is_string($choice) && in_array($choice, $cases)) {
				$this->args[0] = $category;
				$this->args[1] = $choice;
				$this->_run($this->_parseArgs(), $this->_runnerOptions());
				break;
			}

			if ($choice === 'q') {
				break;
			}
		}
	}

/**
 * Find the test case for the passed file. The file could itself be a test.
 *
 * @param string $file
 * @param string $category
 * @param boolean $throwOnMissingFile
 * @access protected
 * @return array array(type, case)
 * @throws Exception
 */
	protected function _mapFileToCase($file, $category, $throwOnMissingFile = true) {
		if (!$category || (substr($file, -4) !== '.php')) {
			return false;
		}

		$_file = realpath($file);
		if ($_file) {
			$file = $_file;
		}

		$testFile = $testCase = null;

		if (preg_match('@Test[\\\/]@', $file)) {

			if (substr($file, -8) === 'Test.php') {

				$testCase = substr($file, 0, -8);
				$testCase = str_replace(DS, '/', $testCase);

				if ($testCase = preg_replace('@.*Test\/Case\/@', '', $testCase)) {

					if ($category === 'core') {
						$testCase = str_replace('lib/Cake', '', $testCase);
					}

					return $testCase;
				}

				throw new Exception(__d('cake_dev', 'Test case %s cannot be run via this shell', $testFile));
			}
		}

		$file = substr($file, 0, -4);
		if ($category === 'core') {

			$testCase = str_replace(DS, '/', $file);
			$testCase = preg_replace('@.*lib/Cake/@', '', $file);
			$testCase[0] = strtoupper($testCase[0]);
			$testFile = CAKE . 'Test/Case/' . $testCase . 'Test.php';

			if (!file_exists($testFile) && $throwOnMissingFile) {
				throw new Exception(__d('cake_dev', 'Test case %s not found', $testFile));
			}

			return $testCase;
		}

		if ($category === 'app') {
			$testFile = str_replace(APP, APP . 'Test/Case/', $file) . 'Test.php';
		} else {
			$testFile = preg_replace(
				"@((?:plugins|Plugin)[\\/]{$category}[\\/])(.*)$@",
				'\1Test/Case/\2Test.php',
				$file
			);
		}

		if (!file_exists($testFile) && $throwOnMissingFile) {
			throw new Exception(__d('cake_dev', 'Test case %s not found', $testFile));
		}

		$testCase = substr($testFile, 0, -8);
		$testCase = str_replace(DS, '/', $testCase);
		$testCase = preg_replace('@.*Test/Case/@', '', $testCase);

		return $testCase;
	}

/**
 * For the given file, what category of test is it? returns app, core or the name of the plugin
 *
 * @param string $file
 * @access protected
 * @return string
 */
	protected function _mapFileToCategory($file) {
		$_file = realpath($file);
		if ($_file) {
			$file = $_file;
		}

		$file = str_replace(DS, '/', $file);
		if (strpos($file, 'lib/Cake/') !== false) {
			return 'core';
		} elseif (preg_match('@(?:plugins|Plugin)/([^/]*)@', $file, $match)) {
			return $match[1];
		}
		return 'app';
	}

}
