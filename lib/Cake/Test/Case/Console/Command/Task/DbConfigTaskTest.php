<?php
/**
 * DBConfigTask Test Case
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Console.Command.Task
 * @since         CakePHP(tm) v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('DbConfigTask', 'Console/Command/Task');

/**
 * DbConfigTest class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class DbConfigTaskTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('DbConfigTask',
			array('in', 'out', 'err', 'hr', 'createFile', '_stop', '_checkUnitTest', '_verify'),
			array($out, $out, $in)
		);

		$this->Task->path = CONFIG;
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
	}

/**
 * Test the getConfig method.
 *
 * @return void
 */
	public function testGetConfig() {
		$this->Task->expects($this->any())
			->method('in')
			->will($this->returnValue('test'));

		$result = $this->Task->getConfig();
		$this->assertEquals('test', $result);
	}

/**
 * test that initialize sets the path up.
 *
 * @return void
 */
	public function testInitialize() {
		$this->Task->initialize();
		$this->assertFalse(empty($this->Task->path));
		$this->assertEquals(CONFIG, $this->Task->path);
	}

/**
 * test execute and by extension _interactive
 *
 * @return void
 */
	public function testExecuteIntoInteractive() {
		$this->Task->initialize();

		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);
		$this->Task = $this->getMock(
			'DbConfigTask',
			array('in', '_stop', 'createFile', 'bake'), array($out, $out, $in)
		);

		$this->Task->expects($this->once())->method('_stop');
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('default')); //name
		$this->Task->expects($this->at(1))->method('in')->will($this->returnValue('mysql')); //db type
		$this->Task->expects($this->at(2))->method('in')->will($this->returnValue('n')); //persistent
		$this->Task->expects($this->at(3))->method('in')->will($this->returnValue('localhost')); //server
		$this->Task->expects($this->at(4))->method('in')->will($this->returnValue('n')); //port
		$this->Task->expects($this->at(5))->method('in')->will($this->returnValue('root')); //user
		$this->Task->expects($this->at(6))->method('in')->will($this->returnValue('password')); //password
		$this->Task->expects($this->at(10))->method('in')->will($this->returnValue('cake_test')); //db
		$this->Task->expects($this->at(11))->method('in')->will($this->returnValue('n')); //prefix
		$this->Task->expects($this->at(12))->method('in')->will($this->returnValue('n')); //encoding
		$this->Task->expects($this->at(13))->method('in')->will($this->returnValue('y')); //looks good
		$this->Task->expects($this->at(14))->method('in')->will($this->returnValue('n')); //another
		$this->Task->expects($this->at(15))->method('bake')
			->with(array(
				array(
					'name' => 'default',
					'datasource' => 'mysql',
					'persistent' => 'false',
					'host' => 'localhost',
					'login' => 'root',
					'password' => 'password',
					'database' => 'cake_test',
					'prefix' => null,
					'encoding' => null,
					'port' => '',
					'schema' => null
				)
			));

		$this->Task->execute();
	}
}
