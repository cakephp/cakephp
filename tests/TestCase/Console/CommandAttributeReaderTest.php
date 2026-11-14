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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Command\Command;
use Cake\Console\Attribute\Argument;
use Cake\Console\Attribute\AsCommand;
use Cake\Console\Attribute\Option;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleIoInterface;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Exception\CommandAttributeException;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\TestSuite\TestCase;
use Mockery;
use TestApp\Console\Attribute\IndexedArguments;
use TestApp\Console\Attribute\Unannotated;

/**
 * Verify declarative parser behavior and command lifecycle integration.
 */
class CommandAttributeReaderTest extends TestCase
{
    /**
     * Attribute values reach Arguments before initialization and execution.
     */
    public function testParserAndExecution(): void
    {
        $command = new #[AsCommand('attribute run', description: 'Run attributes.', help: ['Details.'], epilog: 'Examples.')]
        #[Argument('target', required: true, choices: ['one', 'two'])]
        #[Argument('labels', default: 'a,b', separator: ',')]
        #[Option('format', short: 'f', default: 'csv', choices: ['csv', 'json'])]
        #[Option('force', boolean: true)]
        #[Option('tag', multiple: true, separator: ',')]
        class extends Command {
            public array $values = [];

            /**
             * Read parsed values at the initialization stage.
             *
             * @return void
             */
            public function initialize(): void
            {
                $this->values = [
                    $this->args->getArgument('target'),
                    $this->args->getArrayArgument('labels'),
                    $this->args->getOption('format'),
                    $this->args->getOption('force'),
                    $this->args->getArrayOption('tag'),
                ];
            }
        };
        $command->setName('cake alias');
        $parser = $command->getOptionParser();
        $this->assertSame('attribute run', $command::defaultName());
        $this->assertSame('Run attributes.', $command::getDescription());
        $this->assertSame('alias', $parser->getCommand());
        $this->assertSame("Run attributes.\nDetails.", $parser->getDescription());
        $this->assertSame('Examples.', $parser->getEpilog());
        $this->assertArrayHasKey('help', $parser->options());

        $output = new StubConsoleOutput();
        $command->run(['one', 'a,b', '-f', 'json', '--force', '--tag', 'x,y', '--tag', 'z'], new ConsoleIo($output, $output));
        $this->assertSame(['one', ['a', 'b'], 'json', true, ['x', 'y', 'z']], $command->values);
    }

    /**
     * Parser declarations are inherited, with child replacements retaining positions.
     */
    public function testInheritance(): void
    {
        $command = new #[Argument('source', help: 'Child source.', required: true)]
        #[Argument('destination')]
        #[Option('mode', short: 'n', default: 'new')]
        class extends Unannotated {
        };
        $command->setName('cake inherited');
        $parser = $command->getOptionParser();

        $this->assertSame('', $command::getDescription());
        $this->assertNotSame('abstract export', $command::defaultName());
        $this->assertSame(['source', 'destination'], $parser->argumentNames());
        $this->assertStringContainsString('Child source.', $parser->help());
        [$options] = $parser->parse(['input', '-n', 'custom']);
        $this->assertSame('custom', $options['mode']);
        $this->assertSame('new', $parser->options()['mode']->defaultValue());
    }

    /**
     * Explicit indexes determine positional order independently of declaration order.
     */
    public function testArgumentIndexes(): void
    {
        $command = new #[Argument('second', index: 1)]
        #[Argument('first', required: true, index: 0)]
        class extends Command {
        };
        $this->assertSame(['first', 'second'], $command->getOptionParser()->argumentNames());
    }

    /**
     * Runtime hooks receive the populated parser and can replace options.
     */
    public function testRuntimeHookRunsLast(): void
    {
        $command = new #[AsCommand('runtime', description: 'Static.')]
        #[Option('mode', short: 'm', default: 'static')]
        class extends Command {
            /**
             * Replace an attributed option using the existing parser API.
             *
             * @param \Cake\Console\ConsoleOptionParser $parser Populated parser.
             * @return \Cake\Console\ConsoleOptionParser
             */
            protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
            {
                return $parser->setDescription('Runtime.')
                    ->removeOption('mode')
                    ->addOption('mode', ['short' => 'r', 'default' => 'dynamic']);
            }
        };
        $parser = $command->getOptionParser();
        $this->assertSame('Runtime.', $parser->getDescription());
        $this->assertSame('dynamic', $parser->options()['mode']->defaultValue());
        [$options] = $parser->parse(['-r', 'value']);
        $this->assertSame('value', $options['mode']);
    }

    /**
     * Built-in options can be declared without duplicating their short mappings.
     */
    public function testBuiltInOptionReplacement(): void
    {
        $command = new #[Option('verbose', short: 'v', help: 'Custom verbosity.', boolean: true)]
        class extends Command {
        };
        $parser = $command->getOptionParser();
        [$options] = $parser->parse(['-v']);
        $this->assertTrue($options['verbose']);
        $this->assertStringContainsString('Custom verbosity.', $parser->help());
    }

    /**
     * Duplicate declarations on one class are configuration errors.
     */
    public function testDuplicateArgument(): void
    {
        $command = new #[Argument('name')]
        #[Argument('name')]
        class extends Command {
        };
        $this->expectException(CommandAttributeException::class);
        $this->expectExceptionMessage('more than once');
        $command->getOptionParser();
    }

    /**
     * Duplicate short aliases retain parser validation and class context.
     */
    public function testConflictingShortOptions(): void
    {
        $command = new #[Option('first', short: 'x')]
        #[Option('second', short: 'x')]
        class extends Command {
        };
        $this->expectException(CommandAttributeException::class);
        $this->expectExceptionMessage('Short option `x`');
        $command->getOptionParser();
    }

    /**
     * Required arguments cannot follow optional arguments in the effective order.
     */
    public function testInvalidArgumentOrder(): void
    {
        $command = new #[Argument('optional')]
        #[Argument('required', required: true)]
        class extends Command {
        };
        $this->expectException(CommandAttributeException::class);
        $this->expectExceptionMessage('required argument cannot follow an optional');
        $command->getOptionParser();
    }

    /**
     * Existing parser validation handles interactive option configuration.
     */
    public function testPromptAndDefaultConflict(): void
    {
        $command = new #[Option('secret', default: 'value', prompt: 'Secret?')]
        class extends Command {
        };
        $this->expectException(CommandAttributeException::class);
        $this->expectExceptionMessage('both `prompt` and `default`');
        $command->getOptionParser();
    }

    /**
     * Parser errors are reported before command initialization.
     */
    public function testInvalidInputDoesNotExecute(): void
    {
        $command = new #[Argument('target', required: true, choices: ['allowed'])]
        class extends Command {
            public bool $initialized = false;

            /**
             * Record whether input validation reached initialization.
             *
             * @return void
             */
            public function initialize(): void
            {
                $this->initialized = true;
            }
        };
        $output = new StubConsoleOutput();
        $this->assertSame(1, $command->run(['invalid'], new ConsoleIo($output, $output)));
        $this->assertFalse($command->initialized);
    }

    /**
     * Interactive prompts satisfy required options through the existing IO API.
     */
    public function testInteractiveRequiredOption(): void
    {
        $command = new #[Option('format', choices: ['csv', 'json'], required: true, prompt: 'Format?')]
        class extends Command {
        };
        $io = Mockery::mock(ConsoleIoInterface::class);
        $io->shouldReceive('askChoice')->once()->with('Format?', ['csv', 'json'])->andReturn('json');
        [$options] = $command->getOptionParser()->parse([], $io);

        $this->assertSame('json', $options['format']);
    }

    /**
     * Child replacements inherit the parent's explicit positional index.
     */
    public function testInheritedArgumentIndexes(): void
    {
        $command = new #[Argument('second', help: 'Child definition.')]
        class extends IndexedArguments {
        };
        $parser = $command->getOptionParser();
        $this->assertSame(['first', 'second'], $parser->argumentNames());
        $this->assertStringContainsString('Child definition.', $parser->help());
    }
}
