<?php
/**
 * TestRunner for CakePHP Test suite.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.TestSuite
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

require_once 'PHPUnit/TextUI/Command.php';

App::uses('CakeTestRunner', 'TestSuite');
App::uses('CakeTestLoader', 'TestSuite');
App::uses('CakeTestSuite', 'TestSuite');
App::uses('CakeTestCase', 'TestSuite');
App::uses('ControllerTestCase', 'TestSuite');
App::uses('CakeTestModel', 'TestSuite/Fixture');

/**
 * Class to customize loading of test suites from CLI
 *
 * @package       Cake.TestSuite
 */
class CakeTestSuiteCommand extends PHPUnit_TextUI_Command {

/**
 * Construct method
 *
 * @param array $params list of options to be used for this run
 * @throws MissingTestLoaderException When a loader class could not be found.
 */
	public function __construct($loader, $params = array()) {
		if ($loader && !class_exists($loader)) {
			throw new MissingTestLoaderException(array('class' => $loader));
		}
		$this->arguments['loader'] = $loader;
		$this->arguments['test'] = $params['case'];
		$this->arguments['testFile'] = $params;
		$this->_params = $params;

		$this->longOptions['fixture='] = 'handleFixture';
		$this->longOptions['output='] = 'handleReporter';
	}

/**
 * Ugly hack to get around PHPUnit having a hard coded classname for the Runner. :(
 *
 * @param array   $argv
 * @param boolean $exit
 */
	public function run(array $argv, $exit = true) {
		$this->handleArguments($argv);

		$runner = $this->getRunner($this->arguments['loader']);

		if (is_object($this->arguments['test']) &&
			$this->arguments['test'] instanceof PHPUnit_Framework_Test) {
			$suite = $this->arguments['test'];
		} else {
			$suite = $runner->getTest(
				$this->arguments['test'],
				$this->arguments['testFile']
			);
		}

		if (count($suite) == 0) {
			$skeleton = new PHPUnit_Util_Skeleton_Test(
				$suite->getName(),
				$this->arguments['testFile']
			);

			$result = $skeleton->generate(true);

			if (!$result['incomplete']) {
				eval(str_replace(array('<?php', '?>'), '', $result['code']));
				$suite = new PHPUnit_Framework_TestSuite(
					$this->arguments['test'] . 'Test'
				);
			}
		}

		if ($this->arguments['listGroups']) {
			PHPUnit_TextUI_TestRunner::printVersionString();

			print "Available test group(s):\n";

			$groups = $suite->getGroups();
			sort($groups);

			foreach ($groups as $group) {
				print " - $group\n";
			}

			exit(PHPUnit_TextUI_TestRunner::SUCCESS_EXIT);
		}

		unset($this->arguments['test']);
		unset($this->arguments['testFile']);

		try {
			$result = $runner->doRun($suite, $this->arguments);
		} catch (PHPUnit_Framework_Exception $e) {
			print $e->getMessage() . "\n";
		}

		if ($exit) {
			if (isset($result) && $result->wasSuccessful()) {
				exit(PHPUnit_TextUI_TestRunner::SUCCESS_EXIT);
			} elseif (!isset($result) || $result->errorCount() > 0) {
				exit(PHPUnit_TextUI_TestRunner::EXCEPTION_EXIT);
			} else {
				exit(PHPUnit_TextUI_TestRunner::FAILURE_EXIT);
			}
		}
	}

/**
 * Create a runner for the command.
 *
 * @param $loader The loader to be used for the test run.
 * @return CakeTestRunner
 */
	public function getRunner($loader) {
 		return new CakeTestRunner($loader, $this->_params);
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
 * @return void
 */
	public function handleReporter($reporter) {
		$object = null;

		$type = strtolower($reporter);
		$reporter = ucwords($reporter);
		$coreClass = 'Cake' . $reporter . 'Reporter';
		App::uses($coreClass, 'TestSuite/Reporter');

		$appClass = $reporter . 'Reporter';
		App::uses($appClass, 'TestSuite/Reporter');

		if (!class_exists($appClass)) {
			$object = new $coreClass(null, $this->_params);
		} else {
			$object = new $appClass(null, $this->_params);
		}
		return $this->arguments['printer'] = $object;
	}

}
