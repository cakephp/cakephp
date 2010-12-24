<?php

require_once CONSOLE_LIBS . 'console_option_parser.php';
require_once CONSOLE_LIBS . 'help_formatter.php';

class HelpFormatterTest extends CakeTestCase {

/**
 * test that the console max width is respected when generating help.
 *
 * @return void
 */
	function testWidthFormatting() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->description(__('This is fifteen This is fifteen This is fifteen'))
			->addOption('four', array('help' => 'this is help text this is help text'))
			->addArgument('four', array('help' => 'this is help text this is help text'))
			->addSubcommand('four', array('help' => 'this is help text this is help text'));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->text(30);
		$expected = <<<TEXT
This is fifteen This is
fifteen This is fifteen

<info>Usage:</info>
cake test [subcommand] [-h] [--four] [<four>]

<info>Subcommands:</info>

four  this is help text this
      is help text

To see help on a subcommand use <info>`cake test [subcommand] --help`</info>

<info>Options:</info>

--help, -h  Display this help.
--four      this is help text
            this is help text

<info>Arguments:</info>

four  this is help text this
      is help text
      <comment>(optional)</comment>

TEXT;
		$this->assertEquals($expected, $result, 'Generated help is too wide');
	}

/**
 * test help() with options and arguments that have choices.
 *
 * @return void
 */
	function testHelpWithChoices() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addOption('test', array('help' => 'A test option.', 'choices' => array('one', 'two')))
			->addArgument('type', array(
				'help' => 'Resource type.',
				'choices' => array('aco', 'aro'),
				'required' => true
			))
			->addArgument('other_longer', array('help' => 'Another argument.'));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->text();
		$expected = <<<TEXT
<info>Usage:</info>
cake mycommand [-h] [--test one|two] <aco|aro> [<other_longer>]

<info>Options:</info>

--help, -h  Display this help.
--test      A test option. <comment>(choices: one|two)</comment>

<info>Arguments:</info>

type          Resource type. <comment>(choices: aco|aro)</comment>
other_longer  Another argument. <comment>(optional)</comment>

TEXT;
		$this->assertEquals($expected, $result, 'Help does not match');
	}

/**
 * test description and epilog in the help
 *
 * @return void
 */
	function testHelpDescriptionAndEpilog() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->description('Description text')
			->epilog('epilog text')
			->addOption('test', array('help' => 'A test option.'))
			->addArgument('model', array('help' => 'The model to make.', 'required' => true));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->text();
		$expected = <<<TEXT
Description text

<info>Usage:</info>
cake mycommand [-h] [--test] <model>

<info>Options:</info>

--help, -h  Display this help.
--test      A test option.

<info>Arguments:</info>

model  The model to make.

epilog text

TEXT;
		$this->assertEquals($expected, $result, 'Help is wrong.');
	}

/**
 * test that help() outputs subcommands.
 *
 * @return void
 */
	function testHelpSubcommand() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addSubcommand('method', array('help' => 'This is another command'))
			->addOption('test', array('help' => 'A test option.'));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->text();
		$expected = <<<TEXT
<info>Usage:</info>
cake mycommand [subcommand] [-h] [--test]

<info>Subcommands:</info>

method  This is another command

To see help on a subcommand use <info>`cake mycommand [subcommand] --help`</info>

<info>Options:</info>

--help, -h  Display this help.
--test      A test option.

TEXT;
		$this->assertEquals($expected, $result, 'Help is not correct.');
	}

/**
 * test getting help with defined options.
 *
 * @return void
 */
	function testHelpWithOptions() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addOption('test', array('help' => 'A test option.'))
			->addOption('connection', array(
				'short' => 'c', 'help' => 'The connection to use.', 'default' => 'default'
			));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->text();
		$expected = <<<TEXT
<info>Usage:</info>
cake mycommand [-h] [--test] [-c default]

<info>Options:</info>

--help, -h        Display this help.
--test            A test option.
--connection, -c  The connection to use. <comment>(default:
                  default)</comment>

TEXT;
		$this->assertEquals($expected, $result, 'Help does not match');
	}

/**
 * test getting help with defined options.
 *
 * @return void
 */
	function testHelpWithOptionsAndArguments() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addOption('test', array('help' => 'A test option.'))
			->addArgument('model', array('help' => 'The model to make.', 'required' => true))
			->addArgument('other_longer', array('help' => 'Another argument.'));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->text();
		$expected = <<<TEXT
<info>Usage:</info>
cake mycommand [-h] [--test] <model> [<other_longer>]

<info>Options:</info>

--help, -h  Display this help.
--test      A test option.

<info>Arguments:</info>

model         The model to make.
other_longer  Another argument. <comment>(optional)</comment>

TEXT;
		$this->assertEquals($expected, $result, 'Help does not match');
	}

/**
 * test help() with options and arguments that have choices.
 *
 * @return void
 */
	function testXmlHelpWithChoices() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addOption('test', array('help' => 'A test option.', 'choices' => array('one', 'two')))
			->addArgument('type', array(
				'help' => 'Resource type.',
				'choices' => array('aco', 'aro'),
				'required' => true
			))
			->addArgument('other_longer', array('help' => 'Another argument.'));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->xml();
		$expected = <<<TEXT
<?xml version="1.0"?>
<shell>
<name>mycommand</name>
<description>Description text</description>
<subcommands />
<options>
	<option name="--help" short="-h" help="Display this help." boolean="1">
		<default></default>
		<choices></choices>
	</option>
	<option name="--test" short="" help="A test option." boolean="0">
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
</arguments>
<epilog>epilog text</epilog>
</shell>
TEXT;
		$this->assertEquals(new DomDocument($expected), new DomDocument($result), 'Help does not match');
	}

/**
 * test description and epilog in the help
 *
 * @return void
 */
	function testXmlHelpDescriptionAndEpilog() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->description('Description text')
			->epilog('epilog text')
			->addOption('test', array('help' => 'A test option.'))
			->addArgument('model', array('help' => 'The model to make.', 'required' => true));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->xml();
		$expected = <<<TEXT
<?xml version="1.0"?>
<shell>
<name>mycommand</name>
<description>Description text</description>
<subcommands />
<options>
	<option name="--help" short="-h" help="Display this help." boolean="1">
		<default></default>
		<choices></choices>
	</option>
	<option name="--test" short="" help="A test option." boolean="0">
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
TEXT;
		$this->assertEquals(new DomDocument($expected), new DomDocument($result), 'Help does not match');
	}

/**
 * test that help() outputs subcommands.
 *
 * @return void
 */
	function testXmlHelpSubcommand() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addSubcommand('method', array('help' => 'This is another command'))
			->addOption('test', array('help' => 'A test option.'));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->xml();
		$expected = <<<TEXT
<?xml version="1.0"?>
<shell>
<name>mycommand</name>
<description/>
<subcommands>
	<command name="method" help="This is another command" />
</subcommands>
<options>
	<option name="--help" short="-h" help="Display this help." boolean="1">
		<default></default>
		<choices></choices>
	</option>
	<option name="--test" short="" help="A test option." boolean="0">
		<default></default>
		<choices></choices>
	</option>
</options>
<arguments/>
<epilog/>
</shell>
TEXT;
		$this->assertEquals(new DomDocument($expected), new DomDocument($result), 'Help does not match');
	}

/**
 * test getting help with defined options.
 *
 * @return void
 */
	function testXmlHelpWithOptions() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addOption('test', array('help' => 'A test option.'))
			->addOption('connection', array(
				'short' => 'c', 'help' => 'The connection to use.', 'default' => 'default'
			));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->xml();
		$expected = <<<TEXT
<?xml version="1.0"?>
<shell>
<name>mycommand</name>
<description/>
<subcommands/>
<options>
	<option name="--help" short="-h" help="Display this help." boolean="1">
		<default></default>
		<choices></choices>
	</option>
	<option name="--test" short="" help="A test option." boolean="0">
		<default></default>
		<choices></choices>
	</option>
	<option name="--connection" short="-c" help="The connection to use." boolean="0">
		<default>default</default>
		<choices></choices>
	</option>
</options>
<arguments/>
<epilog/>
</shell>
TEXT;
		$this->assertEquals(new DomDocument($expected), new DomDocument($result), 'Help does not match');
	}

/**
 * test getting help with defined options.
 *
 * @return void
 */
	function testXmlHelpWithOptionsAndArguments() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addOption('test', array('help' => 'A test option.'))
			->addArgument('model', array('help' => 'The model to make.', 'required' => true))
			->addArgument('other_longer', array('help' => 'Another argument.'));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->xml();
		$expected = <<<TEXT
<?xml version="1.0"?>
<shell>
	<name>mycommand</name>
	<description/>
	<subcommands/>
	<options>
		<option name="--help" short="-h" help="Display this help." boolean="1">
			<default></default>
			<choices></choices>
		</option>
		<option name="--test" short="" help="A test option." boolean="0">
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
TEXT;
		$this->assertEquals(new DomDocument($expected), new DomDocument($result), 'Help does not match');
	}

/**
 * Test xml help as object
 *
 * @return void
 */
	function testXmlHelpAsObject() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addOption('test', array('help' => 'A test option.'))
			->addArgument('model', array('help' => 'The model to make.', 'required' => true))
			->addArgument('other_longer', array('help' => 'Another argument.'));

		$formatter = new HelpFormatter($parser);
		$result = $formatter->xml(false);
		$this->assertInstanceOf('SimpleXmlElement', $result);
	}
}
