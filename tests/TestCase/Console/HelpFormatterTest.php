<?php
declare(strict_types=1);

/**
 * HelpFormatterTest file
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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\HelpFormatter;
use Cake\TestSuite\TestCase;

/**
 * HelpFormatterTest
 */
class HelpFormatterTest extends TestCase
{
    /**
     * test that the console max width is respected when generating help.
     */
    public function testWidthFormatting(): void
    {
        $parser = new ConsoleOptionParser('test', false);
        $parser->setDescription('This is fifteen This is fifteen This is fifteen')
            ->addOption('four', ['help' => 'this is help text this is help text'])
            ->addArgument('four', ['help' => 'this is help text this is help text']);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->text(30);
        $expected = <<<txt
This is fifteen This is
fifteen This is fifteen

<info>Usage:</info>
cake test [--four] [-h] [<four>]

<info>Options:</info>

--four      this is help text
            this is help text
--help, -h  Display this help.

<info>Arguments:</info>

four  this is help text this
      is help text
      <comment>(optional)</comment>

txt;
        $this->assertTextEquals($expected, $result, 'Generated help is too wide');
    }

    /**
     * test help() with options and arguments that have choices.
     */
    public function testHelpWithChoices(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addOption('test', ['help' => 'A test option.', 'choices' => ['one', 'two']])
            ->addArgument('type', [
                'help' => 'Resource type.',
                'choices' => ['aco', 'aro'],
                'required' => true,
            ])
            ->addArgument('other_longer', ['help' => 'Another argument.']);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->text();
        $expected = <<<txt
<info>Usage:</info>
cake mycommand [-h] [--test one|two] <aco|aro> [<other_longer>]

<info>Options:</info>

--help, -h  Display this help.
--test      A test option. <comment>(choices: one|two)</comment>

<info>Arguments:</info>

type          Resource type. <comment>(choices: aco|aro)</comment>
other_longer  Another argument. <comment>(optional)</comment>

txt;
        $this->assertTextEquals($expected, $result, 'Help does not match');
    }

    /**
     * test description and epilog in the help
     */
    public function testHelpDescriptionAndEpilog(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->setDescription('Description text')
            ->setEpilog('epilog text')
            ->addOption('test', ['help' => 'A test option.'])
            ->addArgument('model', ['help' => 'The model to make.', 'required' => true]);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->text();
        $expected = <<<txt
Description text

<info>Usage:</info>
cake mycommand [-h] [--test] <model>

<info>Options:</info>

--help, -h  Display this help.
--test      A test option.

<info>Arguments:</info>

model  The model to make.

epilog text

txt;
        $this->assertTextEquals($expected, $result, 'Help is wrong.');
    }

    /**
     * test getting help with defined options.
     */
    public function testHelpWithOptions(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addOption('test', ['help' => 'A test option.'])
               ->addOption('number', [
                   'help' => 'The number',
                   'default' => '2',
               ])
                ->addOption('connection', [
                    'short' => 'c',
                    'help' => 'The connection to use.',
                    'default' => 'default',
                ]);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->text();
        $expected = <<<txt
<info>Usage:</info>
cake mycommand [-c default] [-h] [--number 2] [--test]

<info>Options:</info>

--connection, -c  The connection to use. <comment>(default:
                  default)</comment>
--help, -h        Display this help.
--number          The number <comment>(default: 2)</comment>
--test            A test option.

txt;
        $this->assertTextEquals($expected, $result, 'Help does not match');
    }

    /**
     * test getting help with defined options.
     */
    public function testHelpWithOptionsAndArguments(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addOption('test', ['help' => 'A test option.'])
            ->addArgument('model', ['help' => 'The model to make.', 'required' => true])
            ->addArgument('other_longer', ['help' => 'Another argument.']);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->text();
        $expected = <<<xml
<info>Usage:</info>
cake mycommand [-h] [--test] <model> [<other_longer>]

<info>Options:</info>

--help, -h  Display this help.
--test      A test option.

<info>Arguments:</info>

model         The model to make.
other_longer  Another argument. <comment>(optional)</comment>

xml;
        $this->assertTextEquals($expected, $result, 'Help does not match');
    }

    /**
     * Test that a long set of options doesn't make useless output.
     */
    public function testHelpWithLotsOfOptions(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser
            ->addOption('test', ['help' => 'A test option.'])
            ->addOption('test2', ['help' => 'A test option.'])
            ->addOption('test3', ['help' => 'A test option.'])
            ->addOption('test4', ['help' => 'A test option.'])
            ->addOption('test5', ['help' => 'A test option.'])
            ->addOption('test6', ['help' => 'A test option.'])
            ->addOption('test7', ['help' => 'A test option.'])
            ->addArgument('model', ['help' => 'The model to make.', 'required' => true])
            ->addArgument('other_longer', ['help' => 'Another argument.']);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->text();
        $expected = 'cake mycommand [options] <model> [<other_longer>]';
        $this->assertStringContainsString($expected, $result);
    }

    /**
     * Test that a long set of arguments doesn't make useless output.
     */
    public function testHelpWithLotsOfArguments(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser
            ->addArgument('test', ['help' => 'A test option.', 'required' => true])
            ->addArgument('test2', ['help' => 'A test option.', 'required' => true])
            ->addArgument('test3', ['help' => 'A test option.'])
            ->addArgument('test4', ['help' => 'A test option.'])
            ->addArgument('test5', ['help' => 'A test option.'])
            ->addArgument('test6', ['help' => 'A test option.'])
            ->addArgument('test7', ['help' => 'A test option.'])
            ->addArgument('model', ['help' => 'The model to make.'])
            ->addArgument('other_longer', ['help' => 'Another argument.']);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->text();
        $expected = 'cake mycommand [-h] [arguments]';
        $this->assertStringContainsString($expected, $result);
    }

    /**
     * Test setting a help alias
     */
    public function testWithHelpAlias(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $formatter = new HelpFormatter($parser);
        $formatter->setAlias('foo');
        $result = $formatter->text();
        $expected = 'foo mycommand [-h]';
        $this->assertStringContainsString($expected, $result);
    }

    /**
     * test help() with options and arguments that have choices.
     */
    public function testXmlHelpWithChoices(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addOption('test', ['help' => 'A test option.', 'choices' => ['one', 'two']])
            ->addArgument('type', [
                'help' => 'Resource type.',
                'choices' => ['aco', 'aro'],
                'required' => true,
            ])
            ->addArgument('other_longer', ['help' => 'Another argument.']);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->xml();
        $expected = <<<xml
<?xml version="1.0"?>
<shell>
<command>mycommand</command>
<description />
<options>
	<option name="--help" short="-h" help="Display this help." boolean="1" required="0">
		<default>false</default>
		<choices></choices>
	</option>
	<option name="--test" short="" help="A test option." boolean="0" required="0">
		<default></default>
		<choices>
			<choice>one</choice>
			<choice>two</choice>
		</choices>
	</option>
</options>
<arguments>
	<argument name="type" help="Resource type." required="1">
		<choices>
			<choice>aco</choice>
			<choice>aro</choice>
		</choices>
	</argument>
	<argument name="other_longer" help="Another argument." required="0">
		<choices></choices>
	</argument>
</arguments>
<epilog />
</shell>
xml;
        $this->assertXmlStringEqualsXmlString($expected, $result, 'Help does not match');
    }

    /**
     * test description and epilog in the help
     */
    public function testXmlHelpDescriptionAndEpilog(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->setDescription('Description text')
            ->setEpilog('epilog text')
            ->addOption('test', ['help' => 'A test option.'])
            ->addArgument('model', ['help' => 'The model to make.', 'required' => true]);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->xml();
        $expected = <<<xml
<?xml version="1.0"?>
<shell>
<command>mycommand</command>
<description>Description text</description>
<options>
	<option name="--help" short="-h" help="Display this help." boolean="1" required="0">
		<default>false</default>
		<choices></choices>
	</option>
	<option name="--test" short="" help="A test option." boolean="0" required="0">
		<default></default>
		<choices></choices>
	</option>
</options>
<arguments>
	<argument name="model" help="The model to make." required="1">
		<choices></choices>
	</argument>
</arguments>
<epilog>epilog text</epilog>
</shell>
xml;
        $this->assertXmlStringEqualsXmlString($expected, $result, 'Help does not match');
    }

    /**
     * test getting help with defined options.
     */
    public function testXmlHelpWithOptions(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addOption('test', ['help' => 'A test option.'])
            ->addOption('connection', [
                'short' => 'c', 'help' => 'The connection to use.', 'default' => 'default',
            ]);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->xml();
        $expected = <<<xml
<?xml version="1.0"?>
<shell>
<command>mycommand</command>
<description/>
<options>
	<option name="--connection" short="-c" help="The connection to use." boolean="0" required="0">
		<default>default</default>
		<choices></choices>
	</option>
	<option name="--help" short="-h" help="Display this help." boolean="1" required="0">
		<default>false</default>
		<choices></choices>
	</option>
	<option name="--test" short="" help="A test option." boolean="0" required="0">
		<default></default>
		<choices></choices>
	</option>
</options>
<arguments/>
<epilog/>
</shell>
xml;
        $this->assertXmlStringEqualsXmlString($expected, $result, 'Help does not match');
    }

    /**
     * test getting help with defined options.
     */
    public function testXmlHelpWithOptionsAndArguments(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addOption('test', ['help' => 'A test option.'])
            ->addArgument('model', ['help' => 'The model to make.', 'required' => true])
            ->addArgument('other_longer', ['help' => 'Another argument.']);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->xml();
        $expected = <<<xml
<?xml version="1.0"?>
<shell>
	<command>mycommand</command>
	<description/>
	<options>
		<option name="--help" short="-h" help="Display this help." boolean="1" required="0">
			<default>false</default>
			<choices></choices>
		</option>
		<option name="--test" short="" help="A test option." boolean="0" required="0">
			<default></default>
			<choices></choices>
		</option>
	</options>
	<arguments>
		<argument name="model" help="The model to make." required="1">
			<choices></choices>
		</argument>
		<argument name="other_longer" help="Another argument." required="0">
			<choices></choices>
		</argument>
	</arguments>
	<epilog/>
</shell>
xml;
        $this->assertXmlStringEqualsXmlString($expected, $result, 'Help does not match');
    }

    /**
     * Test XML help as object
     */
    public function testXmlHelpAsObject(): void
    {
        $parser = new ConsoleOptionParser('mycommand', false);
        $parser->addOption('test', ['help' => 'A test option.'])
            ->addArgument('model', ['help' => 'The model to make.', 'required' => true])
            ->addArgument('other_longer', ['help' => 'Another argument.']);

        $formatter = new HelpFormatter($parser);
        $result = $formatter->xml(false);
        $this->assertInstanceOf('SimpleXmlElement', $result);
    }
}
