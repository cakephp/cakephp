<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log\Engine;

use Cake\Console\ConsoleOutput;
use Cake\Log\Engine\ConsoleLog;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;

/**
 * ConsoleLogTest class
 *
 */
class ConsoleLogTest extends TestCase {

/**
 * Test writing to ConsoleOutput
 */
	public function testConsoleOutputWrites() {
		$output = $this->getMock('Cake\Console\ConsoleOutput');

		$output->expects($this->at(0))
			->method('outputAs');

		$message = '<error>' . date('Y-m-d H:i:s') . " Error: oh noes\n</error>";
		$output->expects($this->at(1))
			->method('write')
			->with($message);

		$log = new ConsoleLog([
			'stream' => $output
		]);
		$log->write('error', 'oh noes');
	}

	public function testWriteToFileStream() {
		$filename = tempnam(sys_get_temp_dir(), 'cake_log_test');
		$log = new ConsoleLog([
			'stream' => $filename
		]);
		$log->write('error', 'oh noes');
		$fh = fopen($filename, 'r');
		$line = fgets($fh);
		$this->assertContains('Error: oh noes', $line);
	}

/**
 * test default value of stream 'outputAs'
 */
	public function testDefaultOutputAs() {
		if (DS === '\\' && !(bool)env('ANSICON')) {
			$expected = ConsoleOutput::PLAIN;
		} else {
			$expected = ConsoleOutput::COLOR;
		}
		$output = $this->getMock('Cake\Console\ConsoleOutput');

		$output->expects($this->at(0))
			->method('outputAs')
			->with($expected);

		$log = new ConsoleLog([
			'stream' => $output,
		]);
		$config = $log->config();
		$this->assertEquals($expected, $config['outputAs']);
	}

}
