<?php
declare(strict_types=1);

/**
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Command\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Exception\StopException;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Table;
use Cake\TestSuite\Stub\ConsoleOutput;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Command\AbortCommand;
use TestApp\Command\AutoLoadModelCommand;
use TestApp\Command\DemoCommand;
use TestApp\Command\NonInteractiveCommand;

/**
 * Test case for Console\Command
 */
class CommandTest extends TestCase
{
    /**
     * test orm locator is setup
     *
     * @return void
     */
    public function testConstructorSetsLocator()
    {
        $command = new Command();
        $result = $command->getTableLocator();
        $this->assertInstanceOf(TableLocator::class, $result);
    }

    /**
     * test loadModel is configured properly
     *
     * @return void
     */
    public function testConstructorLoadModel()
    {
        $command = new Command();
        $command->loadModel('Comments');
        $this->assertInstanceOf(Table::class, $command->Comments);
    }

    /**
     * test loadModel is configured properly
     *
     * @return void
     */
    public function testConstructorAutoLoadModel()
    {
        $command = new AutoLoadModelCommand();
        $this->assertInstanceOf(Table::class, $command->Posts);
    }

    /**
     * Test name
     *
     * @return void
     */
    public function testSetName()
    {
        $command = new Command();
        $this->assertSame($command, $command->setName('routes show'));
        $this->assertSame('routes show', $command->getName());
        $this->assertSame('routes', $command->getRootName());
    }

    /**
     * Test invalid name
     *
     * @return void
     */
    public function testSetNameInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The name \'routes_show\' is missing a space. Names should look like `cake routes`');

        $command = new Command();
        $command->setName('routes_show');
    }

    /**
     * Test invalid name
     *
     * @return void
     */
    public function testSetNameInvalidLeadingSpace()
    {
        $this->expectException(InvalidArgumentException::class);

        $command = new Command();
        $command->setName(' routes_show');
    }

    /**
     * Test option parser fetching
     *
     * @return void
     */
    public function testGetOptionParser()
    {
        $command = new Command();
        $command->setName('cake routes show');
        $parser = $command->getOptionParser();
        $this->assertInstanceOf(ConsoleOptionParser::class, $parser);
        $this->assertSame('routes show', $parser->getCommand());
    }

    /**
     * Test that initialize is called.
     *
     * @return void
     */
    public function testRunCallsInitialize()
    {
        /** @var \Cake\Console\Command|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this->getMockBuilder(Command::class)
            ->onlyMethods(['initialize'])
            ->getMock();
        $command->setName('cake example');
        $command->expects($this->once())->method('initialize');
        $command->run([], $this->getMockIo(new ConsoleOutput()));
    }

    /**
     * Test run() outputs help
     *
     * @return void
     */
    public function testRunOutputHelp()
    {
        $command = new Command();
        $command->setName('cake demo');
        $output = new ConsoleOutput();

        $this->assertSame(
            Command::CODE_SUCCESS,
            $command->run(['-h'], $this->getMockIo($output))
        );
        $messages = implode("\n", $output->messages());
        $this->assertStringNotContainsString('Demo', $messages);
        $this->assertStringContainsString('cake demo [-h]', $messages);
    }

    /**
     * Test run() outputs help
     *
     * @return void
     */
    public function testRunOutputHelpLongOption()
    {
        $command = new Command();
        $command->setName('cake demo');
        $output = new ConsoleOutput();

        $this->assertSame(
            Command::CODE_SUCCESS,
            $command->run(['--help'], $this->getMockIo($output))
        );
        $messages = implode("\n", $output->messages());
        $this->assertStringNotContainsString('Demo', $messages);
        $this->assertStringContainsString('cake demo [-h]', $messages);
    }

    /**
     * Test run() sets output level
     *
     * @return void
     */
    public function testRunVerboseOption()
    {
        $command = new DemoCommand();
        $command->setName('cake demo');
        $output = new ConsoleOutput();

        $this->assertNull($command->run(['--verbose'], $this->getMockIo($output)));
        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Verbose!', $messages);
        $this->assertStringContainsString('Demo Command!', $messages);
        $this->assertStringContainsString('Quiet!', $messages);
        $this->assertStringNotContainsString('cake demo [-h]', $messages);
    }

    /**
     * Test run() sets output level
     *
     * @return void
     */
    public function testRunQuietOption()
    {
        $command = new DemoCommand();
        $command->setName('cake demo');
        $output = new ConsoleOutput();

        $this->assertNull($command->run(['--quiet'], $this->getMockIo($output)));
        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString('Quiet!', $messages);
        $this->assertStringNotContainsString('Verbose!', $messages);
        $this->assertStringNotContainsString('Demo Command!', $messages);
    }

    /**
     * Test run() sets option parser failure
     *
     * @return void
     */
    public function testRunOptionParserFailure()
    {
        /** @var \Cake\Console\Command|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this->getMockBuilder(Command::class)
            ->onlyMethods(['getOptionParser'])
            ->getMock();
        $parser = new ConsoleOptionParser('cake example');
        $parser->addArgument('name', ['required' => true]);

        $command->method('getOptionParser')->will($this->returnValue($parser));

        $output = new ConsoleOutput();
        $result = $command->run([], $this->getMockIo($output));
        $this->assertSame(Command::CODE_ERROR, $result);

        $messages = implode("\n", $output->messages());
        $this->assertStringContainsString(
            'Error: Missing required argument. The `name` argument is required',
            $messages
        );
    }

    /**
     * Test abort()
     *
     * @return void
     */
    public function testAbort()
    {
        $this->expectException(StopException::class);
        $this->expectExceptionCode(1);

        $command = new Command();
        $command->abort();
    }

    /**
     * Test abort()
     *
     * @return void
     */
    public function testAbortCustomCode()
    {
        $this->expectException(StopException::class);
        $this->expectExceptionCode(99);

        $command = new Command();
        $command->abort(99);
    }

    /**
     * test executeCommand with a string class
     *
     * @return void
     */
    public function testExecuteCommandString()
    {
        $output = new ConsoleOutput();
        $command = new Command();
        $result = $command->executeCommand(DemoCommand::class, [], $this->getMockIo($output));
        $this->assertNull($result);
        $this->assertEquals(['Quiet!', 'Demo Command!'], $output->messages());
    }

    /**
     * test executeCommand with an invalid string class
     *
     * @return void
     */
    public function testExecuteCommandStringInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Command class 'Nope' does not exist");

        $command = new Command();
        $command->executeCommand('Nope', [], $this->getMockIo(new ConsoleOutput()));
    }

    /**
     * test executeCommand with arguments
     *
     * @return void
     */
    public function testExecuteCommandArguments()
    {
        $output = new ConsoleOutput();
        $command = new Command();
        $command->executeCommand(DemoCommand::class, ['Jane'], $this->getMockIo($output));
        $this->assertEquals(['Quiet!', 'Demo Command!', 'Jane'], $output->messages());
    }

    /**
     * test executeCommand with arguments
     *
     * @return void
     */
    public function testExecuteCommandArgumentsOptions()
    {
        $output = new ConsoleOutput();
        $command = new Command();
        $command->executeCommand(DemoCommand::class, ['--quiet', 'Jane'], $this->getMockIo($output));
        $this->assertEquals(['Quiet!'], $output->messages());
    }

    /**
     * test executeCommand with an instance
     *
     * @return void
     */
    public function testExecuteCommandInstance()
    {
        $output = new ConsoleOutput();
        $command = new Command();
        $result = $command->executeCommand(new DemoCommand(), [], $this->getMockIo($output));
        $this->assertNull($result);
        $this->assertEquals(['Quiet!', 'Demo Command!'], $output->messages());
    }

    /**
     * test executeCommand with an abort
     *
     * @return void
     */
    public function testExecuteCommandAbort()
    {
        $output = new ConsoleOutput();
        $command = new Command();
        $result = $command->executeCommand(AbortCommand::class, [], $this->getMockIo($output));
        $this->assertSame(127, $result);
        $this->assertEquals(['<error>Command aborted</error>'], $output->messages());
    }

    /**
     * test executeCommand with an invalid instance
     *
     * @return void
     */
    public function testExecuteCommandInstanceInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Command 'stdClass' is not a subclass");

        $command = new Command();
        $command->executeCommand(new \stdClass(), [], $this->getMockIo(new ConsoleOutput()));
    }

    /**
     * Test that noninteractive commands use defaults where applicable.
     *
     * @return void
     */
    public function testExecuteCommandNonInteractive()
    {
        $output = new ConsoleOutput();
        $command = new Command();
        $command->executeCommand(NonInteractiveCommand::class, ['--quiet'], $this->getMockIo($output));
        $this->assertEquals(['Result: Default!'], $output->messages());
    }

    /**
     * @param \Cake\Console\ConsoleOutput $output
     * @return \Cake\Console\ConsoleIo|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockIo($output)
    {
        $io = $this->getMockBuilder(ConsoleIo::class)
            ->setConstructorArgs([$output, $output, null, null])
            ->addMethods(['in'])
            ->getMock();

        return $io;
    }
}
