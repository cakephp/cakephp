<?php
namespace App\Test\TestCase\Shell;

use App\Shell\ArticlesShell;
use Cake\TestSuite\TestCase;

/**
 * App\Shell\ArticlesShell Test Case
 */
class ArticlesShellTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->io = $this->getMock('Cake\Console\ConsoleIo');
		$this->Articles = new ArticlesShell($this->io);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Articles);

		parent::tearDown();
	}

/**
 * Test initial setup
 *
 * @return void
 */
	public function testInitialization() {
		$this->markTestIncomplete('Not implemented yet.');
	}

}
