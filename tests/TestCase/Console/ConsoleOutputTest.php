<?php
declare(strict_types=1);

/**
 * ConsoleOutputTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleOutput;
use Cake\TestSuite\TestCase;

/**
 * ConsoleOutputTest
 */
class ConsoleOutputTest extends TestCase
{
    /**
     * @var \Cake\Console\ConsoleOutput|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $output;

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->output = $this->getMockBuilder(ConsoleOutput::class)
            ->onlyMethods(['_write'])
            ->getMock();
        $this->output->setOutputAs(ConsoleOutput::COLOR);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        unset($this->output);
    }

    public function testNoColorEnvironmentVariable()
    {
        $_SERVER['NO_COLOR'] = '1';
        $output = new ConsoleOutput();
        $this->assertSame(ConsoleOutput::PLAIN, $output->getOutputAs());

        unset($_SERVER['NO_COLOR']);
    }

    /**
     * test writing with no new line
     *
     * @return void
     */
    public function testWriteNoNewLine()
    {
        $this->output->expects($this->once())->method('_write')
            ->with('Some output');

        $this->output->write('Some output', 0);
    }

    /**
     * test writing with no new line
     *
     * @return void
     */
    public function testWriteNewLine()
    {
        $this->output->expects($this->once())->method('_write')
            ->with('Some output' . PHP_EOL);

        $this->output->write('Some output');
    }

    /**
     * test write() with multiple new lines
     *
     * @return void
     */
    public function testWriteMultipleNewLines()
    {
        $this->output->expects($this->once())->method('_write')
            ->with('Some output' . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL);

        $this->output->write('Some output', 4);
    }

    /**
     * test writing an array of messages.
     *
     * @return void
     */
    public function testWriteArray()
    {
        $this->output->expects($this->once())->method('_write')
            ->with('Line' . PHP_EOL . 'Line' . PHP_EOL . 'Line' . PHP_EOL);

        $this->output->write(['Line', 'Line', 'Line']);
    }

    /**
     * test getting a style.
     *
     * @return void
     */
    public function testStylesGet()
    {
        $result = $this->output->getStyle('error');
        $expected = ['text' => 'red'];
        $this->assertEquals($expected, $result);

        $this->assertSame([], $this->output->getStyle('made_up_goop'));

        $result = $this->output->styles();
        $this->assertNotEmpty($result, 'Error is missing');
        $this->assertNotEmpty($result, 'Warning is missing');
    }

    /**
     * test adding a style.
     *
     * @return void
     */
    public function testStylesAdding()
    {
        $this->output->setStyle('test', ['text' => 'red', 'background' => 'black']);
        $result = $this->output->getStyle('test');
        $expected = ['text' => 'red', 'background' => 'black'];
        $this->assertEquals($expected, $result);

        $this->output->setStyle('test', []);
        $this->assertSame([], $this->output->getStyle('test'), 'Removed styles should be empty.');
    }

    /**
     * test formatting text with styles.
     *
     * @return void
     */
    public function testFormattingSimple()
    {
        $this->output->expects($this->once())->method('_write')
            ->with("\033[31mError:\033[0m Something bad");

        $this->output->write('<error>Error:</error> Something bad', 0);
    }

    /**
     * test that formatting doesn't eat tags it doesn't know about.
     *
     * @return void
     */
    public function testFormattingNotEatingTags()
    {
        $this->output->expects($this->once())->method('_write')
            ->with('<red> Something bad');

        $this->output->write('<red> Something bad', 0);
    }

    /**
     * test formatting with custom styles.
     *
     * @return void
     */
    public function testFormattingCustom()
    {
        $this->output->setStyle('annoying', [
            'text' => 'magenta',
            'background' => 'cyan',
            'blink' => true,
            'underline' => true,
        ]);

        $this->output->expects($this->once())->method('_write')
            ->with("\033[35;46;5;4mAnnoy:\033[0m Something bad");

        $this->output->write('<annoying>Annoy:</annoying> Something bad', 0);
    }

    /**
     * test formatting text with missing styles.
     *
     * @return void
     */
    public function testFormattingMissingStyleName()
    {
        $this->output->expects($this->once())->method('_write')
            ->with('<not_there>Error:</not_there> Something bad');

        $this->output->write('<not_there>Error:</not_there> Something bad', 0);
    }

    /**
     * test formatting text with multiple styles.
     *
     * @return void
     */
    public function testFormattingMultipleStylesName()
    {
        $this->output->expects($this->once())->method('_write')
            ->with("\033[31mBad\033[0m \033[33mWarning\033[0m Regular");

        $this->output->write('<error>Bad</error> <warning>Warning</warning> Regular', 0);
    }

    /**
     * test that multiple tags of the same name work in one string.
     *
     * @return void
     */
    public function testFormattingMultipleSameTags()
    {
        $this->output->expects($this->once())->method('_write')
            ->with("\033[31mBad\033[0m \033[31mWarning\033[0m Regular");

        $this->output->write('<error>Bad</error> <error>Warning</error> Regular', 0);
    }

    /**
     * test raw output not getting tags replaced.
     *
     * @return void
     */
    public function testSetOutputAsRaw()
    {
        $this->output->setOutputAs(ConsoleOutput::RAW);
        $this->output->expects($this->once())->method('_write')
            ->with('<error>Bad</error> Regular');

        $this->output->write('<error>Bad</error> Regular', 0);
    }

    /**
     * test set/get plain output.
     *
     * @return void
     */
    public function testSetOutputAsPlain()
    {
        $this->output->setOutputAs(ConsoleOutput::PLAIN);
        $this->assertSame(ConsoleOutput::PLAIN, $this->output->getOutputAs());
        $this->output->expects($this->once())->method('_write')
            ->with('Bad Regular');

        $this->output->write('<error>Bad</error> Regular', 0);
    }

    /**
     * test plain output only strips tags used for formatting.
     *
     * @return void
     */
    public function testSetOutputAsPlainSelectiveTagRemoval()
    {
        $this->output->setOutputAs(ConsoleOutput::PLAIN);
        $this->output->expects($this->once())
            ->method('_write')
            ->with('Bad Regular <b>Left</b> <i>behind</i> <name>');

        $this->output->write('<error>Bad</error> Regular <b>Left</b> <i>behind</i> <name>', 0);
    }
}
