<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log\Engine;

use Cake\Console\ConsoleOutput;
use Cake\Log\Engine\ConsoleLog;
use Cake\TestSuite\TestCase;

/**
 * ConsoleLogTest class
 *
 */
class ConsoleLogTest extends TestCase
{

    /**
     * Test writing to ConsoleOutput
     */
    public function testConsoleOutputlogs()
    {
        $output = $this->getMock('Cake\Console\ConsoleOutput');

        $output->expects($this->at(0))
            ->method('outputAs');

        $message = " Error: oh noes</error>";
        $output->expects($this->at(1))
            ->method('write')
            ->with($this->stringContains($message));

        $log = new ConsoleLog([
            'stream' => $output
        ]);
        $log->log('error', 'oh noes');
    }

    /**
     * Test writing to a file stream
     *
     * @return void
     */
    public function testlogToFileStream()
    {
        $filename = tempnam(sys_get_temp_dir(), 'cake_log_test');
        $log = new ConsoleLog([
            'stream' => $filename
        ]);
        $log->log('error', 'oh noes');
        $fh = fopen($filename, 'r');
        $line = fgets($fh);
        $this->assertContains('Error: oh noes', $line);
    }

    /**
     * test default value of stream 'outputAs'
     */
    public function testDefaultOutputAs()
    {
        if ((DS === '\\' && !(bool)env('ANSICON') && env('ConEmuANSI') !== 'ON') ||
            (function_exists('posix_isatty') && !posix_isatty(null))
        ) {
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
