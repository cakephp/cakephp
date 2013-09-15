<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc.
 * @link          http://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.2.0.7726
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\ApiShellShell;
use Cake\TestSuite\TestCase;

/**
 * ApiShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ApiShellTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('Cake\Console\ConsoleOutput', [], [], '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', [], [], '', false);

		$this->Shell = $this->getMock(
			'Cake\Console\Command\ApiShell',
			['in', 'out', 'createFile', 'hr', '_stop'],
			[$out, $out, $in]
		);
	}

/**
 * Test that method names are detected properly including those with no arguments.
 *
 * @return void
 */
	public function testMethodNameDetection() {
		$this->Shell->expects($this->any())
			->method('in')->will($this->returnValue('q'));
		$this->Shell->expects($this->at(0))
			->method('out')->with('Controller');

		$this->Shell->expects($this->at(2))
			->method('out')
			->with($this->logicalAnd(
				$this->contains('8. beforeFilter($event)'),
				$this->contains('21. render($view = NULL, $layout = NULL)')
			));

		$this->Shell->args = ['controller'];
		$this->Shell->paths['controller'] = CAKE . 'Controller/';
		$this->Shell->main();
	}
}
