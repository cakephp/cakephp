<?php
/**
 * TestSuiteShell test case
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake.tests.libs
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::import('Shell', 'Shell', false);
App::import('Shell', 'Testsuite');

require_once CAKE . 'console' .  DS . 'shell_dispatcher.php';


class TestSuiteShellTest extends CakeTestCase {


/**
 * setUp test case
 *
 * @return void
 */
	public function setUp() {
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'TestSuiteShell',
			array('in', 'out', 'hr', 'help', 'error', 'err', '_stop', 'initialize', 'run', 'clear'),
			array($out, $out, $in)
		);
		$this->Shell->OptionParser = $this->getMock('ConsoleOptionParser', array(), array(null, false));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Dispatch, $this->Shell);
	}

/**
 * test available list of test cases for an empty category
 *
 * @return void
 */
	public function testAvailableWithEmptyList() {
		$this->Shell->startup();
		$this->Shell->args = array('unexistant-category');
		$this->Shell->expects($this->at(0))->method('out')->with(__("No test cases available \n\n"));
		$this->Shell->OptionParser->expects($this->once())->method('help');
		$this->Shell->available();
	}

/**
 * test available list of test cases for core category
 *
 * @return void
 */
	public function testAvailableCoreCategory() {
		$this->Shell->startup();
		$this->Shell->args = array('core');
		$this->Shell->expects($this->at(0))->method('out')->with('Core Test Cases:');
		$this->Shell->expects($this->at(1))->method('out')
			->with(new PHPUnit_Framework_Constraint_PCREMatch('/\[1\].*/'));
		$this->Shell->expects($this->at(2))->method('out')
			->with(new PHPUnit_Framework_Constraint_PCREMatch('/\[2\].*/'));
		
		$this->Shell->expects($this->once())->method('in')
			->with(__('What test case would you like to run?'), null, 'q')
			->will($this->returnValue('1'));

		$this->Shell->expects($this->once())->method('run');
		$this->Shell->available();
		$this->assertEquals($this->Shell->args, array('core', 'Basics'));
	}

/**
 * Tests that correct option for test runner are passed
 *
 * @return void
 */
	public function testRunnerOptions() {
		$this->Shell->startup();
		$this->Shell->args = array('core', 'Basics');
		$this->Shell->params = array('filter' => 'myFilter', 'colors' => true, 'verbose' => true);

		$this->Shell->expects($this->once())->method('run')
			->with(
				array('app' => false, 'plugin' => null, 'output' => 'text', 'case' => 'basics'),
				array('--filter', 'myFilter', '--colors', '--verbose')
			);
		$this->Shell->main();
	}
}
