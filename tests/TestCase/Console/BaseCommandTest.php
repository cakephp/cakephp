<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use Mockery;

/**
 * Test that BaseCommand hydrates $args and $io properties.
 */
class BaseCommandTest extends TestCase
{
    /**
     * Test that $this->args is available inside execute()
     */
    public function testRunHydratesArgs(): void
    {
        $command = new class extends Command {
            public ?Arguments $capturedArgs = null;

            public function execute(Arguments $args, ConsoleIo $io): int
            {
                $this->capturedArgs = $this->args;

                return static::CODE_SUCCESS;
            }
        };
        $command->setName('cake test');
        $output = new StubConsoleOutput();
        $io = Mockery::mock(ConsoleIo::class, [$output, $output, null, null])->makePartial();

        $command->run([], $io);

        $this->assertInstanceOf(Arguments::class, $command->capturedArgs);
    }

    /**
     * Test that $this->io is available inside execute()
     */
    public function testRunHydratesIo(): void
    {
        $command = new class extends Command {
            public ?ConsoleIo $capturedIo = null;

            public function execute(Arguments $args, ConsoleIo $io): int
            {
                $this->capturedIo = $this->io;

                return static::CODE_SUCCESS;
            }
        };
        $command->setName('cake test');
        $output = new StubConsoleOutput();
        $io = Mockery::mock(ConsoleIo::class, [$output, $output, null, null])->makePartial();

        $command->run([], $io);

        $this->assertSame($io, $command->capturedIo);
    }

    /**
     * Test that $this->args matches the Arguments passed to execute()
     */
    public function testRunHydratedArgsMatchExecuteArgs(): void
    {
        $command = new class extends Command {
            public bool $argsMatch = false;

            public function execute(Arguments $args, ConsoleIo $io): int
            {
                $this->argsMatch = ($this->args === $args);

                return static::CODE_SUCCESS;
            }
        };
        $command->setName('cake test');
        $output = new StubConsoleOutput();
        $io = Mockery::mock(ConsoleIo::class, [$output, $output, null, null])->makePartial();

        $command->run([], $io);

        $this->assertTrue($command->argsMatch);
    }

    /**
     * Test that $this->args and $this->io are hydrated before execute(),
     * so traits/parent classes can rely on them without manual assignment.
     */
    public function testRunHydratesPropertiesBeforeExecute(): void
    {
        $command = new class extends Command {
            public bool $propsAvailable = false;

            public function execute(Arguments $args, ConsoleIo $io): int
            {
                $this->propsAvailable = isset($this->args) && isset($this->io);

                return static::CODE_SUCCESS;
            }
        };
        $command->setName('cake test');
        $output = new StubConsoleOutput();
        $io = Mockery::mock(ConsoleIo::class, [$output, $output, null, null])->makePartial();

        $command->run([], $io);

        $this->assertTrue($command->propsAvailable);
    }

    /**
     * Test that $this->io is accessible in initialize()
     */
    public function testInitializeCanAccessIo(): void
    {
        $command = new class extends Command {
            public bool $ioAccessible = false;

            public function initialize(): void
            {
                $this->ioAccessible = isset($this->io);
            }

            public function execute(Arguments $args, ConsoleIo $io): int
            {
                return static::CODE_SUCCESS;
            }
        };
        $command->setName('cake test');
        $output = new StubConsoleOutput();
        $io = Mockery::mock(ConsoleIo::class, [$output, $output, null, null])->makePartial();

        $command->run([], $io);

        $this->assertTrue($command->ioAccessible);
    }

    /**
     * Test that $this->args is accessible in initialize()
     */
    public function testInitializeCanAccessArgs(): void
    {
        $command = new class extends Command {
            public bool $argsAccessible = false;

            public function initialize(): void
            {
                $this->argsAccessible = isset($this->args);
            }

            public function execute(Arguments $args, ConsoleIo $io): int
            {
                return static::CODE_SUCCESS;
            }
        };
        $command->setName('cake test');
        $output = new StubConsoleOutput();
        $io = Mockery::mock(ConsoleIo::class, [$output, $output, null, null])->makePartial();

        $command->run([], $io);

        $this->assertTrue($command->argsAccessible);
    }

    /**
     * Test that hydrated args contain the parsed arguments from the command line.
     */
    public function testRunHydratedArgsContainParsedValues(): void
    {
        $command = new class extends Command {
            public ?string $capturedName = null;

            protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
            {
                $parser->addArgument('name', ['required' => true]);

                return $parser;
            }

            public function execute(Arguments $args, ConsoleIo $io): int
            {
                $this->capturedName = $this->args->getArgument('name');

                return static::CODE_SUCCESS;
            }
        };
        $command->setName('cake test');
        $output = new StubConsoleOutput();
        $io = Mockery::mock(ConsoleIo::class, [$output, $output, null, null])->makePartial();

        $command->run(['Alice'], $io);

        $this->assertSame('Alice', $command->capturedName);
    }
}
