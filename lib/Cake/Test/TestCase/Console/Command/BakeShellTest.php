<?php
/**
 * BakeShell Test Case
 *
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
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP(tm) v 1.3
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Console\Command;
use Cake\TestSuite\TestCase,
	Cake\Console\Command\BakeShellShell,
	Cake\Controller\Controller,
	Cake\Core\App;

class BakeShellTest extends TestCase {

/**
 * fixtures
 *
 * @var array
 */
	public $fixtures = array('core.user');

/**
 * setup test
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('Cake\Console\ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'Cake\Console\Command\BakeShell',
			array('in', 'out', 'hr', 'err', 'createFile', '_stop', '_checkUnitTest'),
			array($out, $out, $in)
		);
	}

/**
 * tearDown method
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
		$userExists = App::classname('User', 'Model');
		$this->skipIf($userExists, 'User class exists, cannot test `bake all [param]`.');

		$this->Shell->Model = $this->getMock('Cake\Console\Command\Task\ModelTask', array(), array(&$this->Dispatcher));
		$this->Shell->Controller = $this->getMock('Cake\Console\Command\Task\ControllerTask', array(), array(&$this->Dispatcher));
		$this->Shell->View = $this->getMock('Cake\Console\Command\Task\ModelTask', array(), array(&$this->Dispatcher));
		$this->Shell->DbConfig = $this->getMock('Cake\Console\Command\Task\DbConfigTask', array(), array(&$this->Dispatcher));

		$this->Shell->DbConfig->expects($this->once())
			->method('getConfig')
			->will($this->returnValue('test'));

		$this->Shell->Model->expects($this->never())
			->method('getName');

		$this->Shell->Model->expects($this->once())
			->method('bake')
			->will($this->returnValue(true));

		$this->Shell->Controller->expects($this->once())
			->method('bake')
			->will($this->returnValue(true));

		$this->Shell->View->expects($this->once())
			->method('execute');

		$this->Shell->expects($this->once())->method('_stop');
		$this->Shell->expects($this->at(0))
			->method('out')
			->with('Bake All');

		$this->Shell->expects($this->at(5))
			->method('out')
			->with('<success>Bake All complete</success>');

		$this->Shell->connection = '';
		$this->Shell->params = array();
		$this->Shell->args = array('User');
		$this->Shell->all();

		$this->assertEquals('User', $this->Shell->View->args[0]);
	}
}
