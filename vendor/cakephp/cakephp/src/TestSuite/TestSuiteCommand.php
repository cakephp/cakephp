<?php
/**
 * TestRunner for CakePHP Test suite.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite;

use Cake\Core\App;
use Cake\Error;

require_once 'PHPUnit/TextUI/Command.php';

/**
 * Class to customize loading of test suites from CLI
 *
 */
class TestSuiteCommand extends \PHPUnit_TextUI_Command {

/**
 * Construct method
 *
 * @param mixed $loader
 * @param array $params list of options to be used for this run
 * @throws Cake\Error\MissingTestLoaderException When a loader class could not be found.
 */
	public function __construct($loader, $params = array()) {
		if ($loader && !class_exists($loader)) {
			throw new Error\MissingTestLoaderException(array('class' => $loader));
		}
		$this->arguments['loader'] = $loader;
		$this->arguments['test'] = $params['case'];
		$this->arguments['testFile'] = $params;
		$this->_params = $params;

		$this->longOptions['fixture='] = 'handleFixture';
		$this->longOptions['output='] = 'handleReporter';
	}

/**
 * Ugly hack to get around PHPUnit having a hard coded class name for the Runner. :(
 *
 * @param array   $argv
 * @param boolean $exit
 */
	public function run(array $argv, $exit = true) {
		$this->handleArguments($argv);

		$runner = $this->getRunner($this->arguments['loader']);

		if (is_object($this->arguments['test']) &&
			$this->arguments['test'] instanceof \PHPUnit_Framework_Test) {
			$suite = $this->arguments['test'];
		} else {
			$suite = $runner->getTest(
				$this->arguments['test'],
				$this->arguments['testFile']
			);
		}

		if ($this->arguments['listGroups']) {
			\PHPUnit_TextUI_TestRunner::printVersionString();

			print "Available test group(s):\n";

			$groups = $suite->getGroups();
			sort($groups);

			foreach ($groups as $group) {
				print " - $group\n";
			}

			exit(\PHPUnit_TextUI_TestRunner::SUCCESS_EXIT);
		}

		unset($this->arguments['test']);
		unset($this->arguments['testFile']);

		try {
			$result = $runner->doRun($suite, $this->arguments);
		} catch (\PHPUnit_Framework_Exception $e) {
			print $e->getMessage() . "\n";
		}

		if ($exit) {
			if (isset($result) && $result->wasSuccessful()) {
				exit(\PHPUnit_TextUI_TestRunner::SUCCESS_EXIT);
			} elseif (!isset($result) || $result->errorCount() > 0) {
				exit(\PHPUnit_TextUI_TestRunner::EXCEPTION_EXIT);
			} else {
				exit(\PHPUnit_TextUI_TestRunner::FAILURE_EXIT);
			}
			exit(PHPUnit_TextUI_TestRunner::FAILURE_EXIT);
		}
	}

/**
 * Create a runner for the command.
 *
 * @param mixed $loader The loader to be used for the test run.
 * @return Cake\TestSuite\TestRunner
 */
	public function getRunner($loader) {
		return new TestRunner($loader, $this->_params);
	}

/**
 * Handler for customizing the FixtureManager class/
 *
 * @param string $class Name of the class that will be the fixture manager
 * @return void
 */
	public function handleFixture($class) {
		$this->arguments['fixtureManager'] = $class;
	}

/**
 * Handles output flag used to change printing on webrunner.
 *
 * @param string $reporter
 * @return void
 */
	public function handleReporter($reporter) {
		$reporter = ucwords($reporter);
		$class = App::classname($reporter, 'TestSuite/Reporter', 'Reporter');
		$object = new $class(null, $this->_params);

		return $this->arguments['printer'] = $object;
	}

}
