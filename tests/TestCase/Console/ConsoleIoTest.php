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
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\TestSuite\TestCase;

/**
 * ConsoleIo test.
 */
class ConsoleIoTest extends TestCase
{

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');

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
    public function choiceProvider()
    {
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
    public function testAskChoices($choices)
    {
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
    public function testAskChoicesInsensitive($choices)
    {
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
    public function testAsk()
    {
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
    public function testAskDefaultValue()
    {
        $this->out->expects($this->at(0))
            ->method('write')
            ->with("<question>Just a test?</question>\n[n] > ");

        $this->in->expects($this->at(0))
            ->method('read')
            ->will($this->returnValue(''));

        $result = $this->io->ask('Just a test?', 'n');
        $this->assertEquals('n', $result);
    }

    /**
     * testOut method
     *
     * @return void
     */
    public function testOut()
    {
        $this->out->expects($this->at(0))
            ->method('write')
            ->with("Just a test", 1);

        $this->out->expects($this->at(1))
            ->method('write')
            ->with(['Just', 'a', 'test'], 1);

        $this->out->expects($this->at(2))
            ->method('write')
            ->with(['Just', 'a', 'test'], 2);

        $this->out->expects($this->at(3))
            ->method('write')
            ->with('', 1);

        $this->io->out('Just a test');
        $this->io->out(['Just', 'a', 'test']);
        $this->io->out(['Just', 'a', 'test'], 2);
        $this->io->out();
    }

    /**
     * test that verbose and quiet output levels work
     *
     * @return void
     */
    public function testVerboseOut()
    {
        $this->out->expects($this->at(0))
            ->method('write')
            ->with('Verbose', 1);
        $this->out->expects($this->at(1))
            ->method('write')
            ->with('Normal', 1);
        $this->out->expects($this->at(2))
            ->method('write')
            ->with('Quiet', 1);

        $this->io->level(ConsoleIo::VERBOSE);

        $this->io->out('Verbose', 1, ConsoleIo::VERBOSE);
        $this->io->out('Normal', 1, ConsoleIo::NORMAL);
        $this->io->out('Quiet', 1, ConsoleIo::QUIET);
    }

    /**
     * test that verbose and quiet output levels work
     *
     * @return void
     */
    public function testVerboseOutput()
    {
        $this->out->expects($this->at(0))
            ->method('write')
            ->with('Verbose', 1);
        $this->out->expects($this->at(1))
            ->method('write')
            ->with('Normal', 1);
        $this->out->expects($this->at(2))
            ->method('write')
            ->with('Quiet', 1);

        $this->io->level(ConsoleIo::VERBOSE);

        $this->io->verbose('Verbose');
        $this->io->out('Normal');
        $this->io->quiet('Quiet');
    }

    /**
     * test that verbose and quiet output levels work
     *
     * @return void
     */
    public function testQuietOutput()
    {
        $this->out->expects($this->exactly(2))
            ->method('write')
            ->with('Quiet', 1);

        $this->io->level(ConsoleIo::QUIET);

        $this->io->out('Verbose', 1, ConsoleIo::VERBOSE);
        $this->io->out('Normal', 1, ConsoleIo::NORMAL);
        $this->io->out('Quiet', 1, ConsoleIo::QUIET);
        $this->io->verbose('Verbose');
        $this->io->quiet('Quiet');
    }

    /**
     * testErr method
     *
     * @return void
     */
    public function testErr()
    {
        $this->err->expects($this->at(0))
            ->method('write')
            ->with("Just a test", 1);

        $this->err->expects($this->at(1))
            ->method('write')
            ->with(['Just', 'a', 'test'], 1);

        $this->err->expects($this->at(2))
            ->method('write')
            ->with(['Just', 'a', 'test'], 2);

        $this->err->expects($this->at(3))
            ->method('write')
            ->with('', 1);

        $this->io->err('Just a test');
        $this->io->err(['Just', 'a', 'test']);
        $this->io->err(['Just', 'a', 'test'], 2);
        $this->io->err();
    }

    /**
     * testNl
     *
     * @return void
     */
    public function testNl()
    {
        $newLine = "\n";
        if (DS === '\\') {
            $newLine = "\r\n";
        }
        $this->assertEquals($this->io->nl(), $newLine);
        $this->assertEquals($this->io->nl(true), $newLine);
        $this->assertEquals("", $this->io->nl(false));
        $this->assertEquals($this->io->nl(2), $newLine . $newLine);
        $this->assertEquals($this->io->nl(1), $newLine);
    }

    /**
     * testHr
     *
     * @return void
     */
    public function testHr()
    {
        $bar = str_repeat('-', 79);

        $this->out->expects($this->at(0))->method('write')->with('', 0);
        $this->out->expects($this->at(1))->method('write')->with($bar, 1);
        $this->out->expects($this->at(2))->method('write')->with('', 0);

        $this->out->expects($this->at(3))->method('write')->with("", true);
        $this->out->expects($this->at(4))->method('write')->with($bar, 1);
        $this->out->expects($this->at(5))->method('write')->with("", true);

        $this->out->expects($this->at(6))->method('write')->with("", 2);
        $this->out->expects($this->at(7))->method('write')->with($bar, 1);
        $this->out->expects($this->at(8))->method('write')->with("", 2);

        $this->io->hr();
        $this->io->hr(true);
        $this->io->hr(2);
    }

    /**
     * Test overwriting.
     *
     * @return void
     */
    public function testOverwrite()
    {
        $number = strlen('Some text I want to overwrite');

        $this->out->expects($this->at(0))
            ->method('write')
            ->with('Some <info>text</info> I want to overwrite', 0)
            ->will($this->returnValue($number));

        $this->out->expects($this->at(1))
            ->method('write')
            ->with(str_repeat("\x08", $number), 0);

        $this->out->expects($this->at(2))
            ->method('write')
            ->with('Less text', 0)
            ->will($this->returnValue(9));

        $this->out->expects($this->at(3))
            ->method('write')
            ->with(str_repeat(' ', $number - 9), 0);

        $this->io->out('Some <info>text</info> I want to overwrite', 0);
        $this->io->overwrite('Less text');
    }

    /**
     * Tests that setLoggers works properly
     *
     * @return void
     */
    public function testSetLoggers()
    {
        Log::drop('stdout');
        Log::drop('stderr');
        $this->io->setLoggers(true);
        $this->assertNotEmpty(Log::engine('stdout'));
        $this->assertNotEmpty(Log::engine('stderr'));

        $this->io->setLoggers(false);
        $this->assertFalse(Log::engine('stdout'));
        $this->assertFalse(Log::engine('stderr'));
    }

    /**
     * Tests that setLoggers works properly with quiet
     *
     * @return void
     */
    public function testSetLoggersQuiet()
    {
        Log::drop('stdout');
        Log::drop('stderr');
        $this->io->setLoggers(ConsoleIo::QUIET);
        $this->assertEmpty(Log::engine('stdout'));
        $this->assertNotEmpty(Log::engine('stderr'));
    }

    /**
     * Tests that setLoggers works properly with verbose
     *
     * @return void
     */
    public function testSetLoggersVerbose()
    {
        Log::drop('stdout');
        Log::drop('stderr');
        $this->io->setLoggers(ConsoleIo::VERBOSE);

        $this->assertNotEmpty(Log::engine('stderr'));
        $engine = Log::engine('stdout');
        $this->assertEquals(['notice', 'info', 'debug'], $engine->config('levels'));
    }

    /**
     * Ensure that styles() just proxies to stdout.
     *
     * @return void
     */
    public function testStyles()
    {
        $this->out->expects($this->once())
            ->method('styles')
            ->with('name', 'props');
        $this->io->styles('name', 'props');
    }

    /**
     * Test the helper method.
     *
     * @return void
     */
    public function testHelper()
    {
        $this->out->expects($this->once())
            ->method('write')
            ->with('It works!well ish');
        $helper = $this->io->helper('simple');
        $this->assertInstanceOf('Cake\Console\Helper', $helper);
        $helper->output(['well', 'ish']);
    }
}
