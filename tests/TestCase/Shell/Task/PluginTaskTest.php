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

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\Shell\Task\TemplateTask;
use Cake\TestSuite\TestCase;

/**
 * PluginTaskPlugin class
 */
class PluginTaskTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock('Cake\Shell\Task\PluginTask',
			array('in', 'err', 'createFile', '_stop', 'clear', 'callProcess'),
			array($this->io)
		);
		$this->Task->Template = new TemplateTask($this->io);
		$this->Task->Template->interactive = false;

		$this->Task->path = TMP . 'tests' . DS;
		$this->Task->bootstrap = TMP . 'tests' . DS . 'bootstrap.php';

		if (!is_dir($this->Task->path)) {
			mkdir($this->Task->path);
		}
		touch($this->Task->bootstrap);

		$this->_path = App::path('Plugin');
	}

/**
 * tearDown()
 *
 * @return void
 */
	public function tearDown() {
		if (file_exists($this->Task->bootstrap)) {
			unlink($this->Task->bootstrap);
		}
		parent::tearDown();
	}

/**
 * test bake()
 *
 * @return void
 */
	public function testBakeFoldersAndFiles() {
		$this->Task->expects($this->at(0))->method('in')->will($this->returnValue('y'));

		$path = $this->Task->path . 'BakeTestPlugin';

		$file = $path . DS . 'src' . DS . 'Controller' . DS . 'AppController.php';
		$this->Task->expects($this->at(1))->method('createFile')
			->with($file, $this->stringContains('namespace BakeTestPlugin\Controller;'));

		$this->Task->bake('BakeTestPlugin');

		$path = $this->Task->path . 'BakeTestPlugin';
		$this->assertTrue(is_dir($path), 'No plugin dir');

		$directories = array(
			'config',
			'src/Model/Behavior',
			'src/Model/Table',
			'src/Model/Entity',
			'src/Shell/Task',
			'src/Controller/Component',
			'src/View/Helper',
			'tests/TestCase/Controller/Component',
			'tests/TestCase/View/Helper',
			'tests/TestCase/Model/Behavior',
			'tests/Fixture',
			'src/Template',
			'webroot'
		);
		foreach ($directories as $dir) {
			$this->assertTrue(is_dir($path . DS . $dir), 'Missing directory for ' . $dir);
		}

		$Folder = new Folder($this->Task->path . 'BakeTestPlugin');
		$Folder->delete();
	}

/**
 * test execute with no args, flowing into interactive,
 *
 * @return void
 */
	public function testExecuteWithNoArgs() {
		$path = $this->Task->path . 'TestPlugin';

		$this->Task->expects($this->at(0))
			->method('err')
			->with($this->stringContains('You must'));

		$this->Task->expects($this->never())
			->method('createFile');

		$this->Task->main();

		$Folder = new Folder($path);
		$Folder->delete();
	}

/**
 * Test Execute
 *
 * @return void
 */
	public function testExecuteWithOneArg() {
		$this->Task->expects($this->at(0))->method('in')
			->will($this->returnValue('y'));

		$path = $this->Task->path . 'BakeTestPlugin';
		$file = $path . DS . 'src' . DS . 'Controller' . DS . 'AppController.php';
		$this->Task->expects($this->at(1))->method('createFile')
			->with($file, $this->stringContains('class AppController extends BaseController {'));

		$file = $path . DS . 'config' . DS . 'routes.php';
		$this->Task->expects($this->at(2))->method('createFile')
			->with($file, $this->stringContains("Router::plugin('BakeTestPlugin', function(\$routes)"));

		$file = $path . DS . 'phpunit.xml';
		$this->Task->expects($this->at(3))->method('createFile')
			->with($file, $this->anything());

		$file = $path . DS . 'tests' . DS . 'bootstrap.php';
		$this->Task->expects($this->at(4))->method('createFile')
			->with($file, $this->anything());

		$this->Task->main('BakeTestPlugin');

		$Folder = new Folder($this->Task->path . 'BakeTestPlugin');
		$Folder->delete();
	}

/**
 * Test that baking a plugin for a project that contains a composer.json, the later
 * will be updated
 *
 * @return void
 */
	public function testExecuteUpdateComposer() {
		$this->Task->expects($this->at(0))->method('in')
			->will($this->returnValue('y'));

		$this->Task->Project = $this->getMock('ComposerProject', ['findComposer']);
		$this->Task->Project->expects($this->at(0))
			->method('findComposer')
			->will($this->returnValue('composer.phar'));

		$path = dirname($this->Task->path);
		$file = $path . DS . 'composer.json';
		file_put_contents($file, '{}');

		$config = [
			'autoload' => [
				'psr-4' => [
					'BakeTestPlugin\\' => './plugins/BakeTestPlugin/src',
				],
			],
			'autoload-dev' => [
				'psr-4' => [
					'BakeTestPlugin\\Test\\' => './plugins/BakeTestPlugin/tests',
				],
			],
		];
		$config = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		$this->Task->expects($this->at(2))
			->method('createFile')
			->with($file, $config);

		$this->Task->expects($this->at(3))
			->method('callProcess')
			->with('cd ' . escapeshellarg($path) . '; php ' . escapeshellarg('composer.phar') . ' dump-autoload');

		$this->Task->main('BakeTestPlugin');

		$Folder = new Folder($this->Task->path . 'BakeTestPlugin');
		$Folder->delete();

		$File = new File($file);
		$File->delete();
	}

/**
 * Test that findPath ignores paths that don't exist.
 *
 * @return void
 */
	public function testFindPathNonExistant() {
		$paths = App::path('Plugin');
		$last = count($paths);

		array_unshift($paths, '/fake/path');
		$paths[] = '/fake/path2';

		$this->Task = $this->getMock('Cake\Shell\Task\PluginTask',
			array('in', 'out', 'err', 'createFile', '_stop'),
			array($this->io)
		);
		$this->Task->path = TMP . 'tests' . DS;

		// Make sure the added path is filtered out.
		$this->Task->expects($this->exactly($last))
			->method('out');

		$this->Task->expects($this->once())
			->method('in')
			->will($this->returnValue($last));

		$this->Task->findPath($paths);
	}

}
