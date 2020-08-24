<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleInputArgument;
use Cake\Console\ConsoleInputOption;
use Cake\Console\ConsoleInputSubcommand;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\Exception\ConsoleException;
use Cake\Console\Exception\MissingOptionException;
use Cake\TestSuite\TestCase;
use LogicException;

/**
 * ConsoleOptionParserTest
 */
class ConsoleOptionParserTest extends TestCase
{
    /**
     * test setting the console description
     *
     * @return void
     */
    public function testDescription()
    {
        $parser = new ConsoleOptionParser('test', false);
        $result = $parser->setDescription('A test');

        $this->assertEquals($parser, $result, 'Setting description is not chainable');
        $this->assertSame('A test', $parser->getDescription(), 'getting value is wrong.');

        $result = $parser->setDescription(['A test', 'something']);
        $this->assertSame("A test\nsomething", $parser->getDescription(), 'getting value is wrong.');
    }

    /**
     * test setting and getting the console epilog
     *
     * @return void
     */
    public function testEpilog()
    {
        $parser = new ConsoleOptionParser('test', false);
        $result = $parser->setEpilog('A test');

        $this->assertEquals($parser, $result, 'Setting epilog is not chainable');
        $this->assertSame('A test', $parser->getEpilog(), 'getting value is wrong.');

        $result = $parser->setEpilog(['A test', 'something']);
        $this->assertSame("A test\nsomething", $parser->getEpilog(), 'getting value is wrong.');
    }

    /**
     * test adding an option returns self.
     *
     * @return void
     */
    public function testAddOptionReturnSelf()
    {
        $parser = new ConsoleOptionParser('test', false);
        $result = $parser->addOption('test');
        $this->assertEquals($parser, $result, 'Did not return $this from addOption');
    }

    /**
     * test removing an option
     *
     * @return void
     */
    public function testRemoveOption()
    {
        $parser = new ConsoleOptionParser('test', false);
        $result = $parser->addOption('test')
            ->removeOption('test')
            ->removeOption('help');
        $this->assertSame($parser, $result, 'Did not return $this from removeOption');
        $this->assertEquals([], $result->options());
    }

    /**
     * test adding an option and using the long value for parsing.
     *
     * @return void
     */
    public function testAddOptionLong()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('test', [
            'short' => 't',
        ]);
        $result = $parser->parse(['--test', 'value']);
        $this->assertEquals(['test' => 'value', 'help' => false], $result[0], 'Long parameter did not parse out');
    }

    /**
     * test adding an option with a zero value
     *
     * @return void
     */
    public function testAddOptionZero()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('count', []);
        $result = $parser->parse(['--count', '0']);
        $this->assertEquals(['count' => '0', 'help' => false], $result[0], 'Zero parameter did not parse out');
    }

    /**
     * test addOption with an object.
     *
     * @return void
     */
    public function testAddOptionObject()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption(new ConsoleInputOption('test', 't'));
        $result = $parser->parse(['--test=value']);
        $this->assertEquals(['test' => 'value', 'help' => false], $result[0], 'Long parameter did not parse out');
    }

    /**
     * test adding an option and using the long value for parsing.
     *
     * @return void
     */
    public function testAddOptionLongEquals()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('test', [
            'short' => 't',
        ]);
        $result = $parser->parse(['--test=value']);
        $this->assertEquals(['test' => 'value', 'help' => false], $result[0], 'Long parameter did not parse out');
    }

    /**
     * test adding an option and using the default.
     *
     * @return void
     */
    public function testAddOptionDefault()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser
            ->addOption('test', [
                'default' => 'default value',
            ])
            ->addOption('no-default', [
            ]);
        $result = $parser->parse(['--test']);
        $this->assertSame(
            ['test' => 'default value', 'help' => false],
            $result[0],
            'Default value did not parse out'
        );

        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('test', [
            'default' => 'default value',
        ]);
        $result = $parser->parse([]);
        $this->assertEquals(['test' => 'default value', 'help' => false], $result[0], 'Default value did not parse out');
    }

    /**
     * test adding an option and using the short value for parsing.
     *
     * @return void
     */
    public function testAddOptionShort()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('test', [
            'short' => 't',
        ]);
        $result = $parser->parse(['-t', 'value']);
        $this->assertEquals(['test' => 'value', 'help' => false], $result[0], 'Short parameter did not parse out');
    }

    /**
     * test adding an option and using the short value for parsing.
     *
     * @return void
     */
    public function testAddOptionWithMultipleShort()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('source', [
            'multiple' => true,
            'short' => 's',
        ]);
        $result = $parser->parse(['-s', 'mysql', '-s', 'postgres']);
        $this->assertEquals(
            [
                'source' => ['mysql', 'postgres'],
                'help' => false,
            ],
            $result[0],
            'Short parameter did not parse out'
        );
    }

    /**
     * Test that adding an option using a two letter short value causes an exception.
     * As they will not parse correctly.
     *
     * @return void
     */
    public function testAddOptionShortOneLetter()
    {
        $this->expectException(ConsoleException::class);
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('test', ['short' => 'te']);
    }

    /**
     * test adding and using boolean options.
     *
     * @return void
     */
    public function testAddOptionBoolean()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('test', [
            'boolean' => true,
        ]);

        $result = $parser->parse(['--test', 'value']);
        $expected = [['test' => true, 'help' => false], ['value']];
        $this->assertEquals($expected, $result);

        $result = $parser->parse(['value']);
        $expected = [['test' => false, 'help' => false], ['value']];
        $this->assertEquals($expected, $result);
    }

    /**
     * test adding an multiple shorts.
     *
     * @return void
     */
    public function testAddOptionMultipleShort()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('test', ['short' => 't', 'boolean' => true])
            ->addOption('file', ['short' => 'f', 'boolean' => true])
            ->addOption('output', ['short' => 'o', 'boolean' => true]);

        $result = $parser->parse(['-o', '-t', '-f']);
        $expected = ['file' => true, 'test' => true, 'output' => true, 'help' => false];
        $this->assertEquals($expected, $result[0], 'Short parameter did not parse out');

        $result = $parser->parse(['-otf']);
        $this->assertEquals($expected, $result[0], 'Short parameter did not parse out');
    }

    /**
     * test multiple options at once.
     *
     * @return void
     */
    public function testAddOptionMultipleOptions()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('test')
            ->addOption('connection')
            ->addOption('table', ['short' => 't', 'default' => true]);

        $result = $parser->parse(['--test', 'value', '-t', '--connection', 'postgres']);
        $expected = ['test' => 'value', 'table' => true, 'connection' => 'postgres', 'help' => false];
        $this->assertEquals($expected, $result[0], 'multiple options did not parse');
    }

    /**
     * test adding an option that accepts multiple values.
     *
     * @return void
     */
    public function testAddOptionWithMultiple()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('source', ['short' => 's', 'multiple' => true]);

        $result = $parser->parse(['--source', 'mysql', '-s', 'postgres']);
        $expected = [
            'source' => [
                'mysql',
                'postgres',
            ],
            'help' => false,
        ];
        $this->assertEquals($expected, $result[0], 'options with multiple values did not parse');
    }

    /**
     * test adding multiple options, including one that accepts multiple values.
     *
     * @return void
     */
    public function testAddOptionMultipleOptionsWithMultiple()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser
            ->addOption('source', ['multiple' => true])
            ->addOption('name')
            ->addOption('export', ['boolean' => true]);

        $result = $parser->parse(['--export', '--source', 'mysql', '--name', 'annual-report', '--source', 'postgres']);
        $expected = [
            'export' => true,
            'source' => [
                'mysql',
                'postgres',
            ],
            'name' => 'annual-report',
            'help' => false,
        ];
        $this->assertEquals($expected, $result[0], 'options with multiple values did not parse');
    }

    /**
     * test adding a required option with a default.
     *
     * @return void
     */
    public function testAddOptionRequiredDefaultValue()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser
            ->addOption('test', [
                'default' => 'default value',
                'required' => true,
            ])
            ->addOption('no-default', [
                'required' => true,
            ]);
        $result = $parser->parse(['--test', '--no-default', 'value']);
        $this->assertSame(
            ['test' => 'default value', 'no-default' => 'value', 'help' => false],
            $result[0]
        );

        $result = $parser->parse(['--no-default', 'value']);
        $this->assertSame(
            ['no-default' => 'value', 'help' => false, 'test' => 'default value'],
            $result[0]
        );
    }

    /**
     * test adding a required option that is missing.
     *
     * @return void
     */
    public function testAddOptionRequiredMissing()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser
            ->addOption('test', [
                'default' => 'default value',
                'required' => true,
            ])
            ->addOption('no-default', [
                'required' => true,
            ]);

        $this->expectException(ConsoleException::class);
        $parser->parse(['--test']);
    }

    /**
     * Test adding multiple options.
     *
     * @return void
     */
    public function testAddOptions()
    {
        $parser = new ConsoleOptionParser('something', false);
        $result = $parser->addOptions([
            'name' => ['help' => 'The name'],
            'other' => ['help' => 'The other arg'],
        ]);
        $this->assertEquals($parser, $result, 'addOptions is not chainable.');

        $result = $parser->options();
        $this->assertCount(3, $result, 'Not enough options');
    }

    /**
     * test that boolean options work
     *
     * @return void
     */
    public function testOptionWithBooleanParam()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('no-commit', ['boolean' => true])
            ->addOption('table', ['short' => 't']);

        $result = $parser->parse(['--table', 'posts', '--no-commit', 'arg1', 'arg2']);
        $expected = [['table' => 'posts', 'no-commit' => true, 'help' => false], ['arg1', 'arg2']];
        $this->assertEquals($expected, $result, 'Boolean option did not parse correctly.');
    }

    /**
     * test parsing options that do not exist.
     *
     * @return void
     */
    public function testOptionThatDoesNotExist()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('no-commit', ['boolean' => true]);

        try {
            $parser->parse(['--he', 'other']);
        } catch (MissingOptionException $e) {
            $this->assertStringContainsString(
                "Unknown option `he`.\n" .
                "Did you mean: `help`?\n" .
                "\n" .
                "Other valid choices:\n" .
                "\n" .
                '- help',
                $e->getFullMessage()
            );
        }
    }

    /**
     * test parsing short options that do not exist.
     *
     * @return void
     */
    public function testShortOptionThatDoesNotExist()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('no-commit', ['boolean' => true, 'short' => 'n']);
        $parser->addOption('construct', ['boolean' => true]);
        $parser->addOption('clear', ['boolean' => true, 'short' => 'c']);

        try {
            $parser->parse(['-f']);
        } catch (MissingOptionException $e) {
            $this->assertStringContainsString('Unknown short option `f`.', $e->getFullMessage());
        }
    }

    /**
     * test that options with choices enforce them.
     *
     * @return void
     */
    public function testOptionWithChoices()
    {
        $this->expectException(ConsoleException::class);
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('name', ['choices' => ['mark', 'jose']]);

        $result = $parser->parse(['--name', 'mark']);
        $expected = ['name' => 'mark', 'help' => false];
        $this->assertEquals($expected, $result[0], 'Got the correct value.');

        $result = $parser->parse(['--name', 'jimmy']);
    }

    /**
     * Ensure that option values can start with -
     *
     * @return void
     */
    public function testOptionWithValueStartingWithMinus()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('name')
            ->addOption('age');

        $result = $parser->parse(['--name', '-foo', '--age', 'old']);
        $expected = ['name' => '-foo', 'age' => 'old', 'help' => false];
        $this->assertEquals($expected, $result[0], 'Option values starting with "-" are broken.');
    }

    /**
     * test positional argument parsing.
     *
     * @return void
     */
    public function testPositionalArgument()
    {
        $parser = new ConsoleOptionParser('test', false);
        $result = $parser->addArgument('name', ['help' => 'An argument']);
        $this->assertEquals($parser, $result, 'Should return this');
    }

    /**
     * test addOption with an object.
     *
     * @return void
     */
    public function testAddArgumentObject()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addArgument(new ConsoleInputArgument('test'));
        $result = $parser->arguments();
        $this->assertCount(1, $result);
        $this->assertSame('test', $result[0]->name());
    }

    /**
     * Test adding arguments out of order.
     *
     * @return void
     */
    public function testAddArgumentOutOfOrder()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addArgument('name', ['index' => 1, 'help' => 'first argument'])
            ->addArgument('bag', ['index' => 2, 'help' => 'second argument'])
            ->addArgument('other', ['index' => 0, 'help' => 'Zeroth argument']);

        $result = $parser->arguments();
        $this->assertCount(3, $result);
        $this->assertSame('other', $result[0]->name());
        $this->assertSame('name', $result[1]->name());
        $this->assertSame('bag', $result[2]->name());
        $this->assertSame([0, 1, 2], array_keys($result));
        $this->assertEquals(
            ['other', 'name', 'bag'],
            $parser->argumentNames()
        );
    }

    /**
     * test overwriting positional arguments.
     *
     * @return void
     */
    public function testPositionalArgOverwrite()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addArgument('name', ['help' => 'An argument'])
            ->addArgument('other', ['index' => 0]);

        $result = $parser->arguments();
        $this->assertCount(1, $result, 'Overwrite did not occur');
    }

    /**
     * test parsing arguments.
     *
     * @return void
     */
    public function testParseArgumentTooMany()
    {
        $this->expectException(ConsoleException::class);
        $parser = new ConsoleOptionParser('test', false);
        $parser->addArgument('name', ['help' => 'An argument'])
            ->addArgument('other');

        $expected = ['one', 'two'];
        $result = $parser->parse($expected);
        $this->assertEquals($expected, $result[1], 'Arguments are not as expected');

        $result = $parser->parse(['one', 'two', 'three']);
    }

    /**
     * test parsing arguments with 0 value.
     *
     * @return void
     */
    public function testParseArgumentZero()
    {
        $parser = new ConsoleOptionParser('test', false);

        $expected = ['one', 'two', 0, 'after', 'zero'];
        $result = $parser->parse($expected);
        $this->assertEquals($expected, $result[1], 'Arguments are not as expected');
    }

    /**
     * test that when there are not enough arguments an exception is raised
     *
     * @return void
     */
    public function testPositionalArgNotEnough()
    {
        $this->expectException(ConsoleException::class);
        $parser = new ConsoleOptionParser('test', false);
        $parser->addArgument('name', ['required' => true])
            ->addArgument('other', ['required' => true]);

        $parser->parse(['one']);
    }

    /**
     * test that when there are required arguments after optional ones an exception is raised
     *
     * @return void
     */
    public function testPositionalArgRequiredAfterOptional()
    {
        $this->expectException(LogicException::class);
        $parser = new ConsoleOptionParser('test');
        $parser->addArgument('name', ['required' => false])
            ->addArgument('other', ['required' => true]);
    }

    /**
     * test that arguments with choices enforce them.
     *
     * @return void
     */
    public function testPositionalArgWithChoices()
    {
        $this->expectException(ConsoleException::class);
        $parser = new ConsoleOptionParser('test', false);
        $parser->addArgument('name', ['choices' => ['mark', 'jose']])
            ->addArgument('alias', ['choices' => ['cowboy', 'samurai']])
            ->addArgument('weapon', ['choices' => ['gun', 'sword']]);

        $result = $parser->parse(['mark', 'samurai', 'sword']);
        $expected = ['mark', 'samurai', 'sword'];
        $this->assertEquals($expected, $result[1], 'Got the correct value.');

        $result = $parser->parse(['jose', 'coder']);
    }

    /**
     * Test adding multiple arguments.
     *
     * @return void
     */
    public function testAddArguments()
    {
        $parser = new ConsoleOptionParser('test', false);
        $result = $parser->addArguments([
            'name' => ['help' => 'The name'],
            'other' => ['help' => 'The other arg'],
        ]);
        $this->assertEquals($parser, $result, 'addArguments is not chainable.');

        $result = $parser->arguments();
        $this->assertCount(2, $result, 'Not enough arguments');
    }

    /**
     * test setting a subcommand up.
     *
     * @return void
     */
    public function testSubcommand()
    {
        $parser = new ConsoleOptionParser('test', false);
        $result = $parser->addSubcommand('initdb', [
            'help' => 'Initialize the database',
        ]);
        $this->assertEquals($parser, $result, 'Adding a subcommand is not chainable');
    }

    /**
     * Tests setting a subcommand up for a Shell method `initMyDb`.
     *
     * @return void
     */
    public function testSubcommandCamelBacked()
    {
        $parser = new ConsoleOptionParser('test', false);
        $result = $parser->addSubcommand('initMyDb', [
            'help' => 'Initialize the database',
        ]);

        $subcommands = array_keys($result->subcommands());
        $this->assertEquals(['init_my_db'], $subcommands, 'Adding a subcommand does not work with camel backed method names.');
    }

    /**
     * Test addSubcommand inherits options as no
     * parser is created.
     *
     * @return void
     */
    public function testAddSubcommandInheritOptions()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addSubcommand('build', [
            'help' => 'Build things',
        ])->addOption('connection', [
            'short' => 'c',
            'default' => 'default',
        ])->addArgument('name', ['required' => false]);

        $result = $parser->parse(['build']);
        $this->assertSame('default', $result[0]['connection']);

        $result = $parser->subcommands();
        $this->assertArrayHasKey('build', $result);
        $this->assertNull($result['build']->parser(), 'No parser should be created');
    }

    /**
     * test addSubcommand with an object.
     *
     * @return void
     */
    public function testAddSubcommandObject()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addSubcommand(new ConsoleInputSubcommand('test'));
        $result = $parser->subcommands();
        $this->assertCount(1, $result);
        $this->assertSame('test', $result['test']->name());
    }

    /**
     * test addSubcommand without sorting applied.
     */
    public function testAddSubcommandSort()
    {
        $parser = new ConsoleOptionParser('test', false);
        $this->assertTrue($parser->isSubcommandSortEnabled());
        $parser->enableSubcommandSort(false);
        $this->assertFalse($parser->isSubcommandSortEnabled());
        $parser->addSubcommand(new ConsoleInputSubcommand('betaTest'), []);
        $parser->addSubcommand(new ConsoleInputSubcommand('alphaTest'), []);
        $result = $parser->subcommands();
        $this->assertCount(2, $result);
        $firstResult = key($result);
        $this->assertSame('betaTest', $firstResult);
    }

    /**
     * test removeSubcommand with an object.
     *
     * @return void
     */
    public function testRemoveSubcommand()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addSubcommand(new ConsoleInputSubcommand('test'));
        $result = $parser->subcommands();
        $this->assertCount(1, $result);
        $parser->removeSubcommand('test');
        $result = $parser->subcommands();
        $this->assertCount(0, $result, 'Remove a subcommand does not work');
    }

    /**
     * test adding multiple subcommands
     *
     * @return void
     */
    public function testAddSubcommands()
    {
        $parser = new ConsoleOptionParser('test', false);
        $result = $parser->addSubcommands([
            'initdb' => ['help' => 'Initialize the database'],
            'create' => ['help' => 'Create something'],
        ]);
        $this->assertEquals($parser, $result, 'Adding a subcommands is not chainable');
        $result = $parser->subcommands();
        $this->assertCount(2, $result, 'Not enough subcommands');
    }

    /**
     * test that no exception is triggered when help is being generated
     *
     * @return void
     */
    public function testHelpNoExceptionWhenGettingHelp()
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addOption('test', ['help' => 'A test option.'])
            ->addArgument('model', ['help' => 'The model to make.', 'required' => true]);

        $result = $parser->parse(['--help']);
        $this->assertTrue($result[0]['help']);
    }

    /**
     * test that help() with a command param shows the help for a subcommand
     *
     * @return void
     */
    public function testHelpSubcommandHelp()
    {
        $subParser = new ConsoleOptionParser('method', false);
        $subParser->addOption('connection', ['help' => 'Db connection.']);
        $subParser->addOption('zero', ['short' => '0', 'help' => 'Zero.']);

        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addSubcommand('method', [
                'help' => 'This is another command',
                'parser' => $subParser,
            ])
            ->addOption('test', ['help' => 'A test option.']);

        $result = $parser->help('method');
        $expected = <<<TEXT
This is another command

<info>Usage:</info>
cake mycommand method [--connection] [-h] [-0]

<info>Options:</info>

--connection      Db connection.
--help, -h        Display this help.
--zero, -0        Zero.

TEXT;
        $this->assertTextEquals($expected, $result, 'Help is not correct.');
    }

    /**
     * Test addSubcommand inherits options as no
     * parser is created.
     *
     * @return void
     */
    public function testHelpSubcommandInheritOptions()
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addSubcommand('build', [
            'help' => 'Build things.',
        ])->addSubcommand('destroy', [
            'help' => 'Destroy things.',
        ])->addOption('connection', [
            'help' => 'Db connection.',
            'short' => 'c',
        ])->addArgument('name', ['required' => false]);

        $result = $parser->help('build');
        $expected = <<<TEXT
Build things.

<info>Usage:</info>
cake mycommand build [-c] [-h] [-q] [-v] [<name>]

<info>Options:</info>

--connection, -c  Db connection.
--help, -h        Display this help.
--quiet, -q       Enable quiet output.
--verbose, -v     Enable verbose output.

<info>Arguments:</info>

name   <comment>(optional)</comment>

TEXT;
        $this->assertTextEquals($expected, $result, 'Help is not correct.');
    }

    /**
     * test that help() with a command param shows the help for a subcommand
     *
     * @return void
     */
    public function testHelpSubcommandHelpArray()
    {
        $subParser = [
            'options' => [
                'foo' => [
                    'short' => 'f',
                    'help' => 'Foo.',
                    'boolean' => true,
                ],
            ],
        ];

        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addSubcommand('method', [
                'help' => 'This is a subcommand',
                'parser' => $subParser,
            ])
            ->setRootName('tool')
            ->addOption('test', ['help' => 'A test option.']);

        $result = $parser->help('method');
        $expected = <<<TEXT
This is a subcommand

<info>Usage:</info>
tool mycommand method [-f] [-h] [-q] [-v]

<info>Options:</info>

--foo, -f      Foo.
--help, -h     Display this help.
--quiet, -q    Enable quiet output.
--verbose, -v  Enable verbose output.

TEXT;
        $this->assertTextEquals($expected, $result, 'Help is not correct.');
    }

    /**
     * test that help() with a command param shows the help for a subcommand
     *
     * @return void
     */
    public function testHelpSubcommandInheritParser()
    {
        $subParser = new ConsoleOptionParser('method', false);
        $subParser->addOption('connection', ['help' => 'Db connection.']);
        $subParser->addOption('zero', ['short' => '0', 'help' => 'Zero.']);

        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addSubcommand('method', [
                'help' => 'This is another command',
                'parser' => $subParser,
            ])
            ->addOption('test', ['help' => 'A test option.']);

        $result = $parser->help('method');
        $expected = <<<TEXT
This is another command

<info>Usage:</info>
cake mycommand method [--connection] [-h] [-0]

<info>Options:</info>

--connection      Db connection.
--help, -h        Display this help.
--zero, -0        Zero.

TEXT;
        $this->assertTextEquals($expected, $result, 'Help is not correct.');
    }

    /**
     * test that help() with a custom rootName
     *
     * @return void
     */
    public function testHelpWithRootName()
    {
        $parser = new ConsoleOptionParser('sample', false);
        $parser->setDescription('A command!')
            ->setRootName('tool')
            ->addOption('test', ['help' => 'A test option.'])
            ->addOption('reqd', ['help' => 'A required option.', 'required' => true]);

        $result = $parser->help();
        $expected = <<<TEXT
A command!

<info>Usage:</info>
tool sample [-h] --reqd [--test]

<info>Options:</info>

--help, -h  Display this help.
--reqd      A required option. <comment>(required)</comment>
--test      A test option.

TEXT;
        $this->assertTextEquals($expected, $result, 'Help is not correct.');
    }

    /**
     * test that getCommandError() with an unknown subcommand param shows a helpful message
     *
     * @return void
     */
    public function testHelpUnknownSubcommand()
    {
        $subParser = [
            'options' => [
                'foo' => [
                    'short' => 'f',
                    'help' => 'Foo.',
                    'boolean' => true,
                ],
            ],
        ];

        $parser = new ConsoleOptionParser('mycommand', false);
        $parser
            ->addSubcommand('method', [
                'help' => 'This is a subcommand',
                'parser' => $subParser,
            ])
            ->addOption('test', ['help' => 'A test option.'])
            ->addSubcommand('unstash');

        try {
            $result = $parser->help('unknown');
        } catch (MissingOptionException $e) {
            $result = $e->getFullMessage();
            $this->assertStringContainsString(
                "Unable to find the `mycommand unknown` subcommand. See `bin/cake mycommand --help`.\n" .
                "\n" .
                "Other valid choices:\n" .
                "\n" .
                '- method',
                $result
            );
        }
    }

    /**
     * test building a parser from an array.
     *
     * @return void
     */
    public function testBuildFromArray()
    {
        $spec = [
            'command' => 'test',
            'arguments' => [
                'name' => ['help' => 'The name'],
                'other' => ['help' => 'The other arg'],
            ],
            'options' => [
                'name' => ['help' => 'The name'],
                'other' => ['help' => 'The other arg'],
            ],
            'subcommands' => [
                'initdb' => ['help' => 'make database'],
            ],
            'description' => 'description text',
            'epilog' => 'epilog text',
        ];
        $parser = ConsoleOptionParser::buildFromArray($spec);

        $this->assertSame($spec['description'], $parser->getDescription());
        $this->assertSame($spec['epilog'], $parser->getEpilog());

        $options = $parser->options();
        $this->assertArrayHasKey('name', $options);
        $this->assertArrayHasKey('other', $options);

        $args = $parser->arguments();
        $this->assertCount(2, $args);

        $commands = $parser->subcommands();
        $this->assertCount(1, $commands);
    }

    /**
     * test that create() returns instances
     *
     * @return void
     */
    public function testCreateFactory()
    {
        $parser = ConsoleOptionParser::create('factory', false);
        $this->assertInstanceOf('Cake\Console\ConsoleOptionParser', $parser);
        $this->assertSame('factory', $parser->getCommand());
    }

    /**
     * test that getCommand() inflects the command name.
     *
     * @return void
     */
    public function testCommandInflection()
    {
        $parser = new ConsoleOptionParser('CommandLine');
        $this->assertSame('command_line', $parser->getCommand());
    }

    /**
     * test that parse() takes a subcommand argument, and that the subcommand parser
     * is used.
     *
     * @return void
     */
    public function testParsingWithSubParser()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('primary')
            ->addArgument('one', ['required' => true, 'choices' => ['a', 'b']])
            ->addArgument('two', ['required' => true])
            ->addSubcommand('sub', [
                'parser' => [
                    'options' => [
                        'secondary' => ['boolean' => true],
                        'fourth' => ['help' => 'fourth option'],
                    ],
                    'arguments' => [
                        'sub_arg' => ['choices' => ['c', 'd']],
                    ],
                ],
            ]);

        $result = $parser->parse(['sub', '--secondary', '--fourth', '4', 'c']);
        $expected = [[
            'secondary' => true,
            'fourth' => '4',
            'help' => false,
            'verbose' => false,
            'quiet' => false], ['c']];
        $this->assertEquals($expected, $result, 'Sub parser did not parse request.');
    }

    /**
     * Tests toArray()
     *
     * @return void
     */
    public function testToArray()
    {
        $spec = [
            'command' => 'test',
            'arguments' => [
                'name' => ['help' => 'The name'],
                'other' => ['help' => 'The other arg'],
            ],
            'options' => [
                'name' => ['help' => 'The name'],
                'other' => ['help' => 'The other arg'],
            ],
            'subcommands' => [
                'initdb' => ['help' => 'make database'],
            ],
            'description' => 'description text',
            'epilog' => 'epilog text',
        ];
        $parser = ConsoleOptionParser::buildFromArray($spec);
        $result = $parser->toArray();

        $this->assertSame($spec['description'], $result['description']);
        $this->assertSame($spec['epilog'], $result['epilog']);

        $options = $result['options'];
        $this->assertArrayHasKey('name', $options);
        $this->assertArrayHasKey('other', $options);

        $this->assertCount(2, $result['arguments']);
        $this->assertCount(1, $result['subcommands']);
    }

    /**
     * Tests merge()
     *
     * @return void
     */
    public function testMerge()
    {
        $parser = new ConsoleOptionParser('test');
        $parser->addOption('test', ['short' => 't', 'boolean' => true])
            ->addArgument('one', ['required' => true, 'choices' => ['a', 'b']])
            ->addArgument('two', ['required' => true]);

        $parserTwo = new ConsoleOptionParser('test');
        $parserTwo->addOption('file', ['short' => 'f', 'boolean' => true])
            ->addOption('output', ['short' => 'o', 'boolean' => true])
            ->addArgument('one', ['required' => true, 'choices' => ['a', 'b']]);

        $parser->merge($parserTwo);
        $result = $parser->toArray();

        $options = $result['options'];
        $this->assertArrayHasKey('quiet', $options);
        $this->assertArrayHasKey('test', $options);
        $this->assertArrayHasKey('file', $options);
        $this->assertArrayHasKey('output', $options);

        $this->assertCount(2, $result['arguments']);
        $this->assertCount(6, $result['options']);
    }
}
