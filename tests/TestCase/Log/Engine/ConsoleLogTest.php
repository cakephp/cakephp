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
use Cake\Log\Formatter\JsonFormatter;
use Cake\TestSuite\TestCase;

/**
 * ConsoleLogTest class
 */
class ConsoleLogTest extends TestCase
{
    /**
     * Test writing to ConsoleOutput
     */
    public function testConsoleOutputlogs(): void
    {
        $output = $this->getMockBuilder('Cake\Console\ConsoleOutput')->getMock();

        $message = ' Error: oh noes</error>';
        $output->expects($this->once())
            ->method('write')
            ->with($this->stringContains($message));

        $log = new ConsoleLog([
            'stream' => $output,
        ]);
        $log->log('error', 'oh noes');
    }

    /**
     * Test writing to a file stream
     */
    public function testlogToFileStream(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'cake_log_test');
        $log = new ConsoleLog([
            'stream' => $filename,
        ]);
        $log->log('error', 'oh noes');
        $fh = fopen($filename, 'r');
        $line = fgets($fh);
        $this->assertStringContainsString('error: oh noes', $line);
        $this->assertMatchesRegularExpression('/2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ error: oh noes/', $line);
    }

    /**
     * test value of stream 'outputAs'
     */
    public function testDefaultOutputAs(): void
    {
        $output = $this->getMockBuilder(ConsoleOutput::class)->getMock();

        $output->expects($this->once())
            ->method('setOutputAs')
            ->with(ConsoleOutput::RAW);

        $log = new ConsoleLog([
            'stream' => $output,
            'outputAs' => ConsoleOutput::RAW,
        ]);
        $this->assertSame(ConsoleOutput::RAW, $log->getConfig('outputAs'));
    }

    /**
     * test dateFormat option
     */
    public function testDateFormat(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'cake_log_test');
        $log = new ConsoleLog([
            'stream' => $filename,
            'formatter.dateFormat' => 'c',
        ]);
        $log->log('error', 'oh noes');
        $fh = fopen($filename, 'r');
        $line = fgets($fh);
        $this->assertMatchesRegularExpression('/2[0-9]{3}-[0-9]+-[0-9]+T[0-9]+:[0-9]+:[0-9]+\+\d{2}:\d{2} error: oh noes/', $line);
    }

    /**
     * Test json formatter
     */
    public function testJsonFormatter(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'cake_log_test');
        $log = new ConsoleLog([
            'stream' => $filename,
            'formatter' => [
                'className' => JsonFormatter::class,
            ],
        ]);
        $log->log('error', 'test with newline');
        $fh = fopen($filename, 'r');
        $line = fgets($fh);
        $this->assertSame(strlen($line) - 1, strpos($line, "\n"));

        $entry = json_decode($line, true);
        $this->assertNotNull($entry['date']);
        $this->assertSame('error', $entry['level']);
        $this->assertSame('test with newline', $entry['message']);
    }

    /**
     * Test json formatter custom flags
     */
    public function testJsonFormatterFlags(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'cake_log_test');
        $log = new ConsoleLog([
            'stream' => $filename,
            'formatter' => [
                'className' => JsonFormatter::class,
                'flags' => JSON_HEX_QUOT,
            ],
        ]);
        $log->log('error', 'oh "{p1}"', ['p1' => 'noes']);
        $fh = fopen($filename, 'r');
        $line = fgets($fh);
        $this->assertStringContainsString('\u0022noes\u0022', $line);

        $entry = json_decode($line, true);
        $this->assertSame('oh "noes"', $entry['message']);
    }

    /**
     * Test deprecated dateFormat option
     */
    public function testDeprecatedDateFormat(): void
    {
        $this->deprecated(function (): void {
            $filename = tempnam(sys_get_temp_dir(), 'cake_log_test');
            $log = new ConsoleLog([
                'stream' => $filename,
                'dateFormat' => 'c',
            ]);
            $log->log('error', 'oh noes');
            $fh = fopen($filename, 'r');
            $line = fgets($fh);
            $this->assertMatchesRegularExpression('/2[0-9]{3}-[0-9]+-[0-9]+T[0-9]+:[0-9]+:[0-9]+\+\d{2}:\d{2} error: oh noes/', $line);
        });
    }
}
