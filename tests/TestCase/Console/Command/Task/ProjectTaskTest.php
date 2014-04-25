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
 * @since         1.3.0
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
 */
class ProjectTaskTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock('Cake\Console\Command\Task\ProjectTask',
			array('in', 'err', 'createFile', '_stop'),
			array($io)
		);
		$this->Task->path = TMP;
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
		$this->markTestIncomplete('Need to figure this out');
		$this->Task->params['skel'] = CAKE . 'Console/Templates/skel';
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));
		$this->Task->main(TMP . 'BakeTestApp');

		$this->assertTrue(is_dir(TMP . 'BakeTestApp'), 'No project dir');
		$File = new File($path . DS . 'Config/paths.php');
		$contents = $File->read();
		$this->assertRegExp('/define\(\'CAKE_CORE_INCLUDE_PATH\', .*?DS/', $contents);
	}

/**
 * Copy the TestApp route file so it can be modified.
 *
 * @return void
 */
	protected function _cloneRoutes() {
		$File = new File(TEST_APP . 'TestApp/Config/routes.php');
		$contents = $File->read();

		mkdir(TMP . 'BakeTestApp/Config/', 0777, true);
		$File = new File(TMP . 'BakeTestApp/Config/routes.php');
		$File->write($contents);
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

		$this->_cloneRoutes();

		Configure::write('Routing.prefixes', null);
		$this->Task->appPath = TMP . 'BakeTestApp/';
		Configure::write('Routing.prefixes', null);

		$this->Task->expects($this->once())->method('in')->will($this->returnValue('super_duper_admin'));

		$result = $this->Task->getPrefix();
		$this->assertEquals('super_duper_admin_', $result);
	}

/**
 * test cakeAdmin() writing routes.php
 *
 * @return void
 */
	public function testCakeAdmin() {
		$this->_cloneRoutes();

		Configure::write('Routing.prefixes', null);
		$this->Task->appPath = TMP . 'BakeTestApp/';
		$result = $this->Task->cakeAdmin('my_prefix');
		$this->assertTrue($result);

		$this->assertEquals(Configure::read('Routing.prefixes'), array('my_prefix'));
	}

/**
 * test getting the prefix with more than one prefix setup
 *
 * @return void
 */
	public function testGetPrefixWithMultiplePrefixes() {
		Configure::write('Routing.prefixes', array('admin', 'ninja', 'shinobi'));
		$this->Task->appPath = $this->Task->path . 'BakeTestApp/';
		$this->Task->expects($this->once())->method('in')->will($this->returnValue(2));

		$result = $this->Task->getPrefix();
		$this->assertEquals('ninja_', $result);
	}
}
