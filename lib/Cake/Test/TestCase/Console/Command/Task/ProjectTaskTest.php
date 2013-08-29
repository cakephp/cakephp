<?php
/**
 * CakePHP : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         CakePHP v 1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\ProjectTask;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\Utility\File;
use Cake\Utility\Folder;

/**
 * ProjectTask Test class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ProjectTaskTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('Cake\Console\ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('Cake\Console\ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\ProjectTask',
			array('in', 'err', 'createFile', '_stop'),
			array($out, $out, $in)
		);
		$this->Task->path = TMP . 'tests/';
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();

		$Folder = new Folder($this->Task->path . 'BakeTestApp');
		$Folder->delete();
		unset($this->Task);
	}

/**
 * creates a test project that is used for testing project task.
 *
 * @return void
 */
	protected function _setupTestProject() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->bake($this->Task->path . 'BakeTestApp');
	}


/**
 * test bake with an absolute path.
 *
 * @return void
 */
	public function testExecuteWithAbsolutePath() {
		$path = $this->Task->args[0] = TMP . 'tests/BakeTestApp';
		$this->Task->params['skel'] = CAKE . 'Console/Templates/skel';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->execute();

		$this->assertTrue(is_dir($this->Task->args[0]), 'No project dir');
		$File = new File($path . DS . 'Config/paths.php');
		$contents = $File->read();
		$this->assertRegExp('/define\(\'CAKE_CORE_INCLUDE_PATH\', .*?DS/', $contents);
	}

/**
 * test getPrefix method, and that it returns Routing.prefix or writes to config file.
 *
 * @return void
 */
	public function testGetPrefix() {
		Configure::write('Routing.prefixes', array('admin'));
		$result = $this->Task->getPrefix();
		$this->assertEquals('admin_', $result);

		Configure::write('Routing.prefixes', null);
		$this->_setupTestProject();
		$this->Task->configPath = $this->Task->path . 'BakeTestApp/Config/';
		$this->Task->expects($this->once())->method('in')->will($this->returnValue('super_duper_admin'));

		$result = $this->Task->getPrefix();
		$this->assertEquals('super_duper_admin_', $result);

		$File = new File($this->Task->configPath . 'routes.php');
		$File->delete();
	}

/**
 * test cakeAdmin() writing routes.php
 *
 * @return void
 */
	public function testCakeAdmin() {
		$File = new File(APP . 'Config/routes.php');
		$contents = $File->read();
		$File = new File(TMP . 'tests/routes.php');
		$File->write($contents);

		Configure::write('Routing.prefixes', null);
		$this->Task->configPath = TMP . 'tests/';
		$result = $this->Task->cakeAdmin('my_prefix');
		$this->assertTrue($result);

		$this->assertEquals(Configure::read('Routing.prefixes'), array('my_prefix'));
		$File->delete();
	}

/**
 * test getting the prefix with more than one prefix setup
 *
 * @return void
 */
	public function testGetPrefixWithMultiplePrefixes() {
		Configure::write('Routing.prefixes', array('admin', 'ninja', 'shinobi'));
		$this->_setupTestProject();
		$this->Task->configPath = $this->Task->path . 'BakeTestApp/Config/';
		$this->Task->expects($this->once())->method('in')->will($this->returnValue(2));

		$result = $this->Task->getPrefix();
		$this->assertEquals('ninja_', $result);
	}
}
