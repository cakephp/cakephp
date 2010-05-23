<?php
/**
 * BakeShell Test Case
 *
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.tests.cases.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);

if (!defined('DISABLE_AUTO_DISPATCH')) {
	define('DISABLE_AUTO_DISPATCH', true);
}

if (!class_exists('ShellDispatcher')) {
	ob_start();
	$argv = false;
	require CAKE . 'console' .  DS . 'cake.php';
	ob_end_clean();
}

require_once CAKE . 'console' .  DS . 'libs' . DS . 'bake.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'model.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'controller.php';
require_once CAKE . 'console' .  DS . 'libs' . DS . 'tasks' . DS . 'db_config.php';

if (!class_exists('UsersController')) {
	class UsersController extends Controller {
		public $name = 'Users';
	}
}

class BakeShellTestCase extends CakeTestCase {

/**
 * fixtures
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.user');

/**
 * start test
 *
 * @return void
 */
	public function startTest() {
		$this->Dispatcher = $this->getMock(
			'ShellDispatcher', 
			array('getInput', 'stdout', 'stderr', '_stop', '_initEnvironment')
		);
		$this->Shell = $this->getMock(
			'BakeShell',
			array('in', 'out', 'hr', 'err', 'createFile', '_stop', '_checkUnitTest'),
			array(&$this->Dispatcher)
		);
		$this->Shell->Dispatch->shellPaths = App::path('shells');
	}

/**
 * endTest method
 *
 * @return void
 */
	public function endTest() {
		unset($this->Dispatch, $this->Shell);
	}

/**
 * test bake all
 *
 * @return void
 */
	public function testAllWithModelName() {
		App::import('Model', 'User');
		$userExists = class_exists('User');
		if ($this->skipIf($userExists, 'User class exists, cannot test `bake all [param]`. %s')) {
			return;
		}
		$this->Shell->Model = $this->getMock('ModelTask', array(), array(&$this->Dispatch));
		$this->Shell->Controller = $this->getMock('ControllerTask', array(), array(&$this->Dispatch));
		$this->Shell->View = $this->getMock('ModelTask', array(), array(&$this->Dispatch));
		$this->Shell->DbConfig = $this->getMock('DbConfigTask', array(), array(&$this->Dispatch));

		$this->Shell->DbConfig->expects($this->once())->method('getConfig')->will($this->returnValue('test_suite'));
	
		$this->Shell->Model->expects($this->never())->method('getName');
		$this->Shell->Model->expects($this->once())->method('bake')->will($this->returnValue(true));
	
		$this->Shell->Controller->expects($this->once())->method('bake')->will($this->returnValue(true));
		$this->Shell->View->expects($this->once())->method('execute');

		$this->Shell->expects($this->at(1))->method('out')->with('Bake All');
		$this->Shell->expects($this->at(3))->method('out')->with('User Model was baked.');
		$this->Shell->expects($this->at(5))->method('out')->with('User Controller was baked.');
		$this->Shell->expects($this->at(7))->method('out')->with('User Views were baked.');
		$this->Shell->expects($this->at(8))->method('out')->with('Bake All complete');

		$this->Shell->params = array();
		$this->Shell->args = array('User');
		$this->Shell->all();
	}
}
