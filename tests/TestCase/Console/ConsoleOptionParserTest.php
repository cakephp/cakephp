<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleInputArgument;
use Cake\Console\ConsoleInputOption;
use Cake\Console\ConsoleInputSubcommand;
use Cake\Console\ConsoleOptionParser;
use Cake\TestSuite\TestCase;

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
        $result = $parser->description('A test');

        $this->assertEquals($parser, $result, 'Setting description is not chainable');
        $this->assertEquals('A test', $parser->description(), 'getting value is wrong.');

        $result = $parser->description(['A test', 'something']);
        $this->assertEquals("A test\nsomething", $parser->description(), 'getting value is wrong.');
    }

    /**
     * test setting the console epilog
     *
     * @return void
     */
    public function testEpilog()
    {
        $parser = new ConsoleOptionParser('test', false);
        $result = $parser->epilog('A test');

        $this->assertEquals($parser, $result, 'Setting epilog is not chainable');
        $this->assertEquals('A test', $parser->epilog(), 'getting value is wrong.');

        $result = $parser->epilog(['A test', 'something']);
        $this->assertEquals("A test\nsomething", $parser->epilog(), 'getting value is wrong.');
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
            'short' => 't'
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
            'short' => 't'
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
        $parser->addOption('test', [
            'default' => 'default value',
        ]);
        $result = $parser->parse(['--test']);
        $this->assertEquals(['test' => 'default value', 'help' => false], $result[0], 'Default value did not parse out');

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
            'short' => 't'
        ]);
        $result = $parser->parse(['-t', 'value']);
        $this->assertEquals(['test' => 'value', 'help' => false], $result[0], 'Short parameter did not parse out');
    }

    /**
     * Test that adding an option using a two letter short value causes an exception.
     * As they will not parse correctly.
     *
     * @expectedException \Cake\Console\Exception\ConsoleException
     * @return void
     */
    public function testAddOptionShortOneLetter()
    {
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
    public function testMultipleOptions()
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
     * Test adding multiple options.
     *
     * @return void
     */
    public function testAddOptions()
    {
        $parser = new ConsoleOptionParser('something', false);
        $result = $parser->addOptions([
            'name' => ['help' => 'The name'],
            'other' => ['help' => 'The other arg']
        ]);
        $this->assertEquals($parser, $result, 'addOptions is not chainable.');

        $result = $parser->options();
        $this->assertEquals(3, count($result), 'Not enough options');
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
     * @expectedException \Cake\Console\Exception\ConsoleException
     * @return void
     */
    public function testOptionThatDoesNotExist()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('no-commit', ['boolean' => true]);

        $parser->parse(['--fail', 'other']);
    }

    /**
     * test parsing short options that do not exist.
     *
     * @expectedException \Cake\Console\Exception\ConsoleException
     * @return void
     */
    public function testShortOptionThatDoesNotExist()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addOption('no-commit', ['boolean' => true]);

        $parser->parse(['-f']);
    }

    /**
     * test that options with choices enforce them.
     *
     * @expectedException \Cake\Console\Exception\ConsoleException
     * @return void
     */
    public function testOptionWithChoices()
    {
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
        $this->assertEquals('test', $result[0]->name());
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
        $this->assertEquals('other', $result[0]->name());
        $this->assertEquals('name', $result[1]->name());
        $this->assertEquals('bag', $result[2]->name());
        $this->assertSame([0, 1, 2], array_keys($result));
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
        $this->assertEquals(1, count($result), 'Overwrite did not occur');
    }

    /**
     * test parsing arguments.
     *
     * @expectedException \Cake\Console\Exception\ConsoleException
     * @return void
     */
    public function testParseArgumentTooMany()
    {
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
     * @expectedException \Cake\Console\Exception\ConsoleException
     * @return void
     */
    public function testPositionalArgNotEnough()
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->addArgument('name', ['required' => true])
            ->addArgument('other', ['required' => true]);

        $parser->parse(['one']);
    }

    /**
     * test that when there are required arguments after optional ones an exception is raised
     *
     * @expectedException \LogicException
     * @return void
     */
    public function testPositionalArgRequiredAfterOptional()
    {
        $parser = new ConsoleOptionParser('test');
        $parser->addArgument('name', ['required' => false])
            ->addArgument('other', ['required' => true]);
    }

    /**
     * test that arguments with choices enforce them.
     *
     * @expectedException \Cake\Console\Exception\ConsoleException
     * @return void
     */
    public function testPositionalArgWithChoices()
    {
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
            'other' => ['help' => 'The other arg']
        ]);
        $this->assertEquals($parser, $result, 'addArguments is not chainable.');

        $result = $parser->arguments();
        $this->assertEquals(2, count($result), 'Not enough arguments');
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
            'help' => 'Initialize the database'
        ]);
        $this->assertEquals($parser, $result, 'Adding a subcommand is not chainable');
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
        $this->assertEquals(1, count($result));
        $this->assertEquals('test', $result['test']->name());
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
        $this->assertEquals(1, count($result));
        $parser->removeSubcommand('test');
        $result = $parser->subcommands();
        $this->assertEquals(0, count($result), 'Remove a subcommand does not work');
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
            'create' => ['help' => 'Create something']
        ]);
        $this->assertEquals($parser, $result, 'Adding a subcommands is not chainable');
        $result = $parser->subcommands();
        $this->assertEquals(2, count($result), 'Not enough subcommands');
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
                'parser' => $subParser
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
                ]
            ],
        ];

        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addSubcommand('method', [
            'help' => 'This is a subcommand',
            'parser' => $subParser
        ])
            ->addOption('test', ['help' => 'A test option.']);

        $result = $parser->help('method');
        $expected = <<<TEXT
This is a subcommand

<info>Usage:</info>
cake mycommand method [-f] [-h] [-q] [-v]

<info>Options:</info>

--foo, -f      Foo.
--help, -h     Display this help.
--quiet, -q    Enable quiet output.
--verbose, -v  Enable verbose output.

TEXT;
        $this->assertTextEquals($expected, $result, 'Help is not correct.');
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
                'other' => ['help' => 'The other arg']
            ],
            'options' => [
                'name' => ['help' => 'The name'],
                'other' => ['help' => 'The other arg']
            ],
            'subcommands' => [
                'initdb' => ['help' => 'make database']
            ],
            'description' => 'description text',
            'epilog' => 'epilog text'
        ];
        $parser = ConsoleOptionParser::buildFromArray($spec);

        $this->assertEquals($spec['description'], $parser->description());
        $this->assertEquals($spec['epilog'], $parser->epilog());

        $options = $parser->options();
        $this->assertTrue(isset($options['name']));
        $this->assertTrue(isset($options['other']));

        $args = $parser->arguments();
        $this->assertEquals(2, count($args));

        $commands = $parser->subcommands();
        $this->assertEquals(1, count($commands));
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
        $this->assertEquals('factory', $parser->command());
    }

    /**
     * test that command() inflects the command name.
     *
     * @return void
     */
    public function testCommandInflection()
    {
        $parser = new ConsoleOptionParser('CommandLine');
        $this->assertEquals('command_line', $parser->command());
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
                        'fourth' => ['help' => 'fourth option']
                    ],
                    'arguments' => [
                        'sub_arg' => ['choices' => ['c', 'd']]
                    ]
                ]
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
                'other' => ['help' => 'The other arg']
            ],
            'options' => [
                'name' => ['help' => 'The name'],
                'other' => ['help' => 'The other arg']
            ],
            'subcommands' => [
                'initdb' => ['help' => 'make database']
            ],
            'description' => 'description text',
            'epilog' => 'epilog text'
        ];
        $parser = ConsoleOptionParser::buildFromArray($spec);
        $result = $parser->toArray();

        $this->assertEquals($spec['description'], $result['description']);
        $this->assertEquals($spec['epilog'], $result['epilog']);

        $options = $result['options'];
        $this->assertTrue(isset($options['name']));
        $this->assertTrue(isset($options['other']));

        $this->assertEquals(2, count($result['arguments']));
        $this->assertEquals(1, count($result['subcommands']));
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
        $this->assertTrue(isset($options['quiet']));
        $this->assertTrue(isset($options['test']));
        $this->assertTrue(isset($options['file']));
        $this->assertTrue(isset($options['output']));

        $this->assertEquals(2, count($result['arguments']));
        $this->assertEquals(6, count($result['options']));
    }
}
