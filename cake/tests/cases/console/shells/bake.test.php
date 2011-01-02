<?php
/**
 * BakeShell Test Case
 *
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
 * @package       cake.tests.cases.console.libs.tasks
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::import('Shell', 'Shell', false);
App::import('Shell', 'Bake', false);
App::import('Shell', 'tasks/model');
App::import('Shell', 'tasks/controller');
App::import('Shell', 'tasks/db_config');

App::import('Core', 'Controller');
require_once CAKE . 'console' .  DS . 'shell_dispatcher.php';

if (!class_exists('UsersController')) {
	class UsersController extends Controller {
		public $name = 'Users';
	}
}

class BakeShellTest extends CakeTestCase {

/**
 * fixtures
 *
 * @var array
 * @access public
 */
	public $fixtures = array('core.user');

/**
 * setup test
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'BakeShell',
			array('in', 'out', 'hr', 'err', 'createFile', '_stop', '_checkUnitTest'),
			array($out, $out, $in)
		);
	}

/**
 * teardown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
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
		$this->Shell->Model = $this->getMock('ModelTask', array(), array(&$this->Dispatcher));
		$this->Shell->Controller = $this->getMock('ControllerTask', array(), array(&$this->Dispatcher));
		$this->Shell->View = $this->getMock('ModelTask', array(), array(&$this->Dispatcher));
		$this->Shell->DbConfig = $this->getMock('DbConfigTask', array(), array(&$this->Dispatcher));

		$this->Shell->DbConfig->expects($this->once())->method('getConfig')->will($this->returnValue('test'));
	
		$this->Shell->Model->expects($this->never())->method('getName');
		$this->Shell->Model->expects($this->once())->method('bake')->will($this->returnValue(true));
	
		$this->Shell->Controller->expects($this->once())->method('bake')->will($this->returnValue(true));
		$this->Shell->View->expects($this->once())->method('execute');

		$this->Shell->expects($this->once())->method('_stop');
		$this->Shell->expects($this->at(0))->method('out')->with('Bake All');
		$this->Shell->expects($this->at(5))->method('out')->with('<success>Bake All complete</success>');

		$this->Shell->connection = '';
		$this->Shell->params = array();
		$this->Shell->args = array('User');
		$this->Shell->all();
	}
}
