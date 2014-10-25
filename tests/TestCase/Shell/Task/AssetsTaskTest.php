<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Shell\Task;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use Cake\Shell\Task\AssetsTask;
use Cake\TestSuite\TestCase;

/**
 * SymlinkAssetsTask class
 *
 */
class SymlinkAssetsTaskTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->io = $this->getMock('Cake\Console\ConsoleIo', [], [], '', false);

		$this->Task = $this->getMock(
			'Cake\Shell\Task\AssetsTask',
			array('in', 'out', 'err', '_stop'),
			array($this->io)
		);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
		Plugin::unload();
	}

/**
 * testExecute method
 *
 * @return void
 */
	public function testExecute() {
		Plugin::load('TestPlugin');
		Plugin::load('Company/TestPluginThree');

		$this->Task->main();

		$path = WWW_ROOT . 'test_plugin';
		$link = new \SplFileInfo($path);
		$this->assertTrue($link->isLink());
		$this->assertTrue(file_exists($path . DS . 'root.js'));
		unlink($path);

		$path = WWW_ROOT . 'company' . DS . 'test_plugin_three';
		$link = new \SplFileInfo($path);
		$this->assertTrue($link->isLink());
		$this->assertTrue(file_exists($path . DS . 'css' . DS . 'company.css'));
		$folder = new Folder(WWW_ROOT . 'company');
		$folder->delete();
	}

/**
 * test that plugins without webroot are not processed
 *
 * @return void
 */
	public function testForPluginWithoutWebroot() {
		Plugin::load('TestPluginTwo');

		$this->Task->main();
		$this->assertFalse(file_exists(WWW_ROOT . 'test_plugin_two'));
	}

}
