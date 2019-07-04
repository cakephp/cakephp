<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log\Engine;

use Cake\Console\ConsoleOutput;
use Cake\Log\Engine\ConsoleLog;
use Cake\TestSuite\TestCase;

/**
 * ConsoleLogTest class
 */
class ConsoleLogTest extends TestCase
{
    /**
     * Test writing to ConsoleOutput
     */
    public function testConsoleOutputlogs()
    {
        $output = $this->getMockBuilder('Cake\Console\ConsoleOutput')->getMock();

        $message = ' Error: oh noes</error>';
        $output->expects($this->at(0))
            ->method('write')
            ->with($this->stringContains($message));

        $log = new ConsoleLog([
            'stream' => $output,
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
            'stream' => $filename,
        ]);
        $log->log('error', 'oh noes');
        $fh = fopen($filename, 'r');
        $line = fgets($fh);
        $this->assertStringContainsString('Error: oh noes', $line);
    }

    /**
     * test value of stream 'outputAs'
     */
    public function testDefaultOutputAs()
    {
        $output = $this->getMockBuilder(ConsoleOutput::class)->getMock();

        $output->expects($this->at(0))
            ->method('setOutputAs')
            ->with(ConsoleOutput::RAW);

        $log = new ConsoleLog([
            'stream' => $output,
            'outputAs' => ConsoleOutput::RAW,
        ]);
        $this->assertEquals(ConsoleOutput::RAW, $log->getConfig('outputAs'));
    }
}
