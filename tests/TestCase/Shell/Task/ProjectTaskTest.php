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
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\Configure;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\Shell\Task\ProjectTask;
use Cake\TestSuite\TestCase;

/**
 * ProjectTask class enabling access to protected methods.
 */
class TestProjectTask extends ProjectTask {

/**
 * Wrapper for `_isOpenBasedirProtected()`
 *
 * @param string $path The path to test.
 * @return bool
 */
	public function isOpenBasedirProtected($path) {
		return $this->_isOpenBasedirProtected($path);
	}

}

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

		$this->Task = $this->getMock('Cake\Test\TestCase\Shell\Task\TestProjectTask',
			array('in', 'err', 'createFile', '_stop', '_getOpenBasedirConfig'),
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
 * Test _isOpenBasedirProtected()
 *
 * @return void
 */
	public function testIsOpenBasedirProtected() {
		$path = DS . 'foo' . PATH_SEPARATOR . DS . 'bar' . PATH_SEPARATOR . DS . 'baz' . DS . PATH_SEPARATOR . '.';
		$this->Task->expects($this->at(0))->method('_getOpenBasedirConfig')->will($this->returnValue($path));

		$this->assertTrue($this->Task->isOpenBasedirProtected(DS));
		$this->assertTrue($this->Task->isOpenBasedirProtected(DS . 'some' . DS . 'path'));
		$this->assertfalse($this->Task->isOpenBasedirProtected(DS . 'foo'));
		$this->assertfalse($this->Task->isOpenBasedirProtected(DS . 'foo' . DS . 'bar'));
		$this->assertfalse($this->Task->isOpenBasedirProtected(DS . 'baz' . DS));
		$this->assertfalse($this->Task->isOpenBasedirProtected(DS . 'baz'));
		$this->assertfalse($this->Task->isOpenBasedirProtected(getcwd()));
	}

/**
 * Test _isOpenBasedirProtected() with no basedirs configured.
 *
 * @return void
 */
	public function testIsOpenBasedirProtectedWithEmptyConfig() {
		$this->Task->expects($this->at(0))->method('_getOpenBasedirConfig')->will($this->returnValue(null));

		$this->assertfalse($this->Task->isOpenBasedirProtected(DS));
		$this->assertfalse($this->Task->isOpenBasedirProtected(DS . 'some' . DS . 'path'));
		$this->assertfalse($this->Task->isOpenBasedirProtected(getcwd()));
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

}
