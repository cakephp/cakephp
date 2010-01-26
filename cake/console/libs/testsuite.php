<?php
/* SVN FILE: $Id$ */
/**
 * Test Suite Shell
 *
 * This Shell allows the running of test suites via the cake command line
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <https://trac.cakephp.org/wiki/Developement/TestSuite>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          https://trac.cakephp.org/wiki/Developement/TestSuite CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.cake.console.libs
 * @since         CakePHP(tm) v 1.2.0.4433
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
class TestSuiteShell extends Shell {
/**
 * The test category, "app", "core" or the name of a plugin
 *
 * @var string
 * @access public
 */
	var $category = '';
/**
 * "group", "case" or "all"
 *
 * @var string
 * @access public
 */
	var $type = '';
/**
 * Path to the test case/group file
 *
 * @var string
 * @access public
 */
	var $file = '';
/**
 * Storage for plugins that have tests
 *
 * @var string
 * @access public
 */
	var $plugins = array();
/**
 * Convenience variable to avoid duplicated code
 *
 * @var string
 * @access public
 */
	var $isPluginTest = false;
/**
 * Stores if the user wishes to get a code coverage analysis report
 *
 * @var string
 * @access public
 */
	var $doCoverage = false;
/**
 * The headline for the test output
 *
 * @var string
 * @access public
 */
	var $headline = 'CakePHP Test Shell';
/**
 * Initialization method installs Simpletest and loads all plugins
 *
 * @return void
 * @access public
 */
	function initialize() {
		$corePath = Configure::corePaths('cake');
		if (isset($corePath[0])) {
			define('TEST_CAKE_CORE_INCLUDE_PATH', rtrim($corePath[0], DS) . DS);
		} else {
			define('TEST_CAKE_CORE_INCLUDE_PATH', CAKE_CORE_INCLUDE_PATH);
		}

		$this->__installSimpleTest();

		require_once CAKE . 'tests' . DS . 'lib' . DS . 'test_manager.php';
		require_once CAKE . 'tests' . DS . 'lib' . DS . 'cli_reporter.php';

		$plugins = Configure::listObjects('plugin');
		foreach ($plugins as $p) {
			$this->plugins[] = Inflector::underscore($p);
		}
	}
/**
 * Main entry point to this shell
 *
 * @return void
 * @access public
 */
	function main() {
		$this->out($this->headline);
		$this->hr();

		if (count($this->args) > 0) {
			$this->category = $this->args[0];

			if (!in_array($this->category, array('app', 'core'))) {
				$this->isPluginTest = true;
			}

			if (isset($this->args[1])) {
				$this->type = $this->args[1];
			}

			if (isset($this->args[2])) {
				if ($this->args[2] == 'cov') {
					$this->doCoverage = true;
				} else {
					$this->file = Inflector::underscore($this->args[2]);
				}
			}

			if (isset($this->args[3]) && $this->args[3] == 'cov') {
				$this->doCoverage = true;
			}
		} else {
			$this->err('Sorry, you did not pass any arguments!');
		}

		if ($this->__canRun()) {
			$this->out('Running '.$this->category.' '.$this->type.' '.$this->file);

			$exitCode = 0;
			if (!$this->__run()) {
				$exitCode = 1;
			}
			exit($exitCode);
		} else {
			$this->err('Sorry, the tests could not be found.');
			exit(1);
		}
	}
/**
 * Help screen
 *
 * @return void
 * @access public
 */
	function help() {
		$this->out('Usage: ');
		$this->out("\tcake testsuite category test_type file");
		$this->out("\t\t- category - \"app\", \"core\" or name of a plugin");
		$this->out("\t\t- test_type - \"case\", \"group\" or \"all\"");
		$this->out("\t\t- test_file - file name with folder prefix and without the (test|group).php suffix");
		$this->out('');
		$this->out('Examples: ');
		$this->out("\t\tcake testsuite app all");
		$this->out("\t\tcake testsuite core all");
		$this->out('');
		$this->out("\t\tcake testsuite app case behaviors/debuggable");
		$this->out("\t\tcake testsuite app case models/my_model");
		$this->out("\t\tcake testsuite app case controllers/my_controller");
		$this->out('');
		$this->out("\t\tcake testsuite core case file");
		$this->out("\t\tcake testsuite core case router");
		$this->out("\t\tcake testsuite core case set");
		$this->out('');
		$this->out("\t\tcake testsuite app group mygroup");
		$this->out("\t\tcake testsuite core group acl");
		$this->out("\t\tcake testsuite core group socket");
		$this->out('');
		$this->out("\t\tcake testsuite bugs case models/bug");
		$this->out("\t\t  // for the plugin 'bugs' and its test case 'models/bug'");
		$this->out("\t\tcake testsuite bugs group bug");
		$this->out("\t\t  // for the plugin bugs and its test group 'bug'");
		$this->out('');
		$this->out('Code Coverage Analysis: ');
		$this->out("\n\nAppend 'cov' to any of the above in order to enable code coverage analysis");
	}
/**
 * Checks if the arguments supplied point to a valid test file and thus the shell can be run.
 *
 * @return bool true if it's a valid test file, false otherwise
 * @access private
 */
	function __canRun() {
		$isNeitherAppNorCore = !in_array($this->category, array('app', 'core'));
		$isPlugin = in_array(Inflector::underscore($this->category), $this->plugins);

		if ($isNeitherAppNorCore && !$isPlugin) {
			$this->err($this->category.' is an invalid test category (either "app", "core" or name of a plugin)');
			return false;
		}

		$folder = $this->__findFolderByCategory($this->category);
		if (!file_exists($folder)) {
			$this->err($folder . ' not found');
			return false;
		}

		if (!in_array($this->type, array('all', 'group', 'case'))) {
			$this->err($this->type.' is invalid. Should be case, group or all');
			return false;
		}

		switch ($this->type) {
			case 'all':
				return true;
				break;
			case 'group':
				if (file_exists($folder.DS.'groups'.DS.$this->file.'.group.php')) {
					return true;
				}
				break;
			case 'case':
				if ($this->category == 'app' && file_exists($folder.DS.'cases'.DS.$this->file.'.test.php')) {
					return true;
				}

				if ($this->category == 'core' && file_exists($folder.DS.'cases'.DS.'libs'.DS.$this->file.'.test.php')) {
					return true;
				}

				if ($isPlugin && file_exists($folder.DS.'cases'.DS.$this->file.'.test.php')) {
					return true;
				}
				break;
		}

		$this->err($this->category.' '.$this->type.' '.$this->file.' is an invalid test identifier');
		return false;
	}
/**
 * Executes the tests depending on our settings
 *
 * @return void
 * @access private
 */
	function __run() {
		$reporter = new CLIReporter();
		$this->__setGetVars();

		if ($this->type == 'all') {
			return TestManager::runAllTests($reporter);
		}

		if ($this->doCoverage) {
			if (!extension_loaded('xdebug')) {
				$this->out('You must install Xdebug to use the CakePHP(tm) Code Coverage Analyzation. Download it from http://www.xdebug.org/docs/install');
				exit(0);
			}
		}

		if ($this->type == 'group') {
			$ucFirstGroup = ucfirst($this->file);

			$path = CORE_TEST_GROUPS;
			if ($this->category == 'app') {
				$path = APP_TEST_GROUPS;
			} elseif ($this->isPluginTest) {
				$path = APP.'plugins'.DS.$this->category.DS.'tests'.DS.'groups';
			}

			if ($this->doCoverage) {
				require_once CAKE . 'tests' . DS . 'lib' . DS . 'code_coverage_manager.php';
				CodeCoverageManager::start($ucFirstGroup, $reporter);
			}
			$result = TestManager::runGroupTest($ucFirstGroup, $reporter);
			if ($this->doCoverage) {
				CodeCoverageManager::report();
			}
			return $result;
		}

		$case = 'libs'.DS.$this->file.'.test.php';
		if ($this->category == 'app') {
			$case = $this->file.'.test.php';
		} elseif ($this->isPluginTest) {
			$case = $this->file.'.test.php';
		}

		if ($this->doCoverage) {
			require_once CAKE . 'tests' . DS . 'lib' . DS . 'code_coverage_manager.php';
			CodeCoverageManager::start($case, $reporter);
		}

		$result = TestManager::runTestCase($case, $reporter);
		if ($this->doCoverage) {
			CodeCoverageManager::report();
		}

		return $result;
	}
/**
 * Finds the correct folder to look for tests for based on the input category
 *
 * @return string the folder path
 * @access private
 */
	function __findFolderByCategory($category) {
		$folder = '';
		$paths = array(
			'core' => CAKE,
			'app'  => APP
		);

		if (array_key_exists($category, $paths)) {
			$folder = $paths[$category] . 'tests';
		} else {
			$scoredCategory = Inflector::underscore($category);
			$folder = APP . 'plugins' . DS . $scoredCategory . DS;
			$pluginPaths = Configure::read('pluginPaths');
			foreach ($pluginPaths as $path) {
				if (file_exists($path . $scoredCategory . DS . 'tests')) {
					$folder = $path . $scoredCategory . DS . 'tests';
					break;
				}
			}
		}
		return $folder;
	}
/**
 * Sets some get vars needed for TestManager
 *
 * @return void
 * @access private
 */
	function __setGetVars() {
		if (in_array($this->category, $this->plugins)) {
			$_GET['plugin'] = $this->category;
		} elseif (in_array(Inflector::Humanize($this->category), $this->plugins)) {
			$_GET['plugin'] = Inflector::Humanize($this->category);
		} elseif ($this->category == 'app') {
			$_GET['app'] = true;
		}
		if ($this->type == 'group') {
			$_GET['group'] = true;
		}
	}
/**
 * tries to install simpletest and exits gracefully if it is not there
 *
 * @return void
 * @access private
 */
	function __installSimpleTest() {
		if (!App::import('Vendor', 'simpletest' . DS . 'reporter')) {
			$this->err('Sorry, Simpletest could not be found. Download it from http://simpletest.org and install it to your vendors directory.');
			exit;
		}
	}
}
?>