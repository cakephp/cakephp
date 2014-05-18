<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\OrmCacheShell;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;

/**
 * OrmCacheShell test.
 */
class OrmCacheShellTest extends TestCase {

/**
 * setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->io = $this->getMock('Cake\Console\ConsoleIo');
		$this->Shell = new OrmCacheShell($this->io);
	}

/**
 * Test build() with no args.
 *
 * @return void
 */
	public function testBuildNoArgs() {
		$this->markTestIncomplete();
	}

/**
 * Test build() with one arg.
 *
 * @return void
 */
	public function testBuildNamedModel() {
		$this->markTestIncomplete();
	}

/**
 * Test build() with a non-existing connection name.
 *
 * @return void
 */
	public function testBuildInvalidConnection() {
		$this->markTestIncomplete();
	}

/**
 * Test clear() with no args.
 *
 * @return void
 */
	public function testClearInvalidConnection() {
		$this->markTestIncomplete();
	}

/**
 * Test clear() with once arg.
 *
 * @return void
 */
	public function testClearNoArgs() {
		$this->markTestIncomplete();
	}

/**
 * Test clear() with a model name.
 *
 * @return void
 */
	public function testClearNamedModel() {
		$this->markTestIncomplete();
	}

}
