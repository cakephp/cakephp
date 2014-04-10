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
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;

/**
 * ConsoleIo test.
 */
class ConsoleIoTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->out = $this->getMock('Cake\Console\ConsoleOutput', [], [], '', false);
		$this->err = $this->getMock('Cake\Console\ConsoleOutput', [], [], '', false);
		$this->in = $this->getMock('Cake\Console\ConsoleInput', [], [], '', false);
		$this->io = new ConsoleIo($this->out, $this->err, $this->in);
	}

/**
 * Provider for testing choice types.
 *
 * @return array
 */
	public function choiceProvider() {
		return [
			[['y', 'n']],
			['y,n'],
			['y/n'],
			['y'],
		];
	}

/**
 * test ask choices method
 *
 * @dataProvider choiceProvider
 * @return void
 */
	public function testAskChoices($choices) {
		$this->in->expects($this->at(0))
			->method('read')
			->will($this->returnValue('y'));

		$result = $this->io->askChoice('Just a test?', $choices);
		$this->assertEquals('y', $result);
	}

/**
 * test ask choices method
 *
 * @dataProvider choiceProvider
 * @return void
 */
	public function testAskChoicesInsensitive($choices) {
		$this->in->expects($this->at(0))
			->method('read')
			->will($this->returnValue('Y'));

		$result = $this->io->askChoice('Just a test?', $choices);
		$this->assertEquals('Y', $result);
	}

/**
 * Test ask method
 *
 * @return void
 */
	public function testAsk() {
		$this->out->expects($this->at(0))
			->method('write')
			->with("<question>Just a test?</question>\n> ");

		$this->in->expects($this->at(0))
			->method('read')
			->will($this->returnValue('y'));

		$result = $this->io->ask('Just a test?');
		$this->assertEquals('y', $result);
	}

/**
 * Test ask method
 *
 * @return void
 */
	public function testAskDefaultValue() {
		$this->out->expects($this->at(0))
			->method('write')
			->with("<question>Just a test?</question>\n[n] > ");

		$this->in->expects($this->at(0))
			->method('read')
			->will($this->returnValue(''));

		$result = $this->io->ask('Just a test?', 'n');
		$this->assertEquals('n', $result);
	}

}
