<?php
/**
 * ConsoleOptionParserTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc.
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

require_once CAKE . 'console' .  DS . 'console_option_parser.php';

class ConsoleOptionParserTest extends CakeTestCase {

/**
 * test setting the console description
 *
 * @return void
 */
	function testDescription() {
		$parser = new ConsoleOptionParser();
		$result = $parser->description('A test');

		$this->assertEquals($parser, $result, 'Setting description is not chainable');
		$this->assertEquals('A test', $parser->description(), 'getting value is wrong.');
	}

/**
 * test setting the console epliog
 *
 * @return void
 */
	function testEpilog() {
		$parser = new ConsoleOptionParser();
		$result = $parser->epilog('A test');

		$this->assertEquals($parser, $result, 'Setting epilog is not chainable');
		$this->assertEquals('A test', $parser->epilog(), 'getting value is wrong.');
	}

/**
 * test adding an option returns self.
 *
 * @return void
 */
	function testAddOptionReturnSelf() {
		$parser = new ConsoleOptionParser();
		$result = $parser->addOption('test');
		$this->assertEquals($parser, $result, 'Did not return $this from addOption');
	}

/**
 * test adding an option and using the long value for parsing.
 *
 * @return void
 */
	function testAddOptionLong() {
		$parser = new ConsoleOptionParser();
		$parser->addOption('test', array(
			'short' => 't'
		));
		$result = $parser->parse(array('--test', 'value'));
		$this->assertEquals(array('test' => 'value'), $result[0], 'Long parameter did not parse out');
	}

/**
 * test adding an option and using the long value for parsing.
 *
 * @return void
 */
	function testAddOptionLongEquals() {
		$parser = new ConsoleOptionParser();
		$parser->addOption('test', array(
			'short' => 't'
		));
		$result = $parser->parse(array('--test=value'));
		$this->assertEquals(array('test' => 'value'), $result[0], 'Long parameter did not parse out');
	}

/**
 * test adding an option and using the default.
 *
 * @return void
 */
	function testAddOptionDefault() {
		$parser = new ConsoleOptionParser();
		$parser->addOption('test', array(
			'default' => 'default value',
		));
		$result = $parser->parse(array('--test'));
		$this->assertEquals(array('test' => 'default value'), $result[0], 'Default value did not parse out');
	}

/**
 * test adding an option and using the short value for parsing.
 *
 * @return void
 */
	function testAddOptionShort() {
		$parser = new ConsoleOptionParser();
		$parser->addOption('test', array(
			'short' => 't'
		));
		$result = $parser->parse(array('-t', 'value'));
		$this->assertEquals(array('test' => 'value'), $result[0], 'Short parameter did not parse out');
	}

/**
 * test adding an multiple shorts.
 *
 * @return void
 */
	function testAddOptionMultipleShort() {
		$parser = new ConsoleOptionParser();
		$parser->addOption('test', array('short' => 't'))
			->addOption('file', array('short' => 'f'))
			->addOption('output', array('short' => 'o'));

		$result = $parser->parse(array('-o', '-t', '-f'));
		$expected = array('file' => true, 'test' => true, 'output' => true);
		$this->assertEquals($expected, $result[0], 'Short parameter did not parse out');

		$result = $parser->parse(array('-otf'));
		$this->assertEquals($expected, $result[0], 'Short parameter did not parse out');
	}

/**
 * test multiple options at once.
 *
 * @return void
 */
	function testMultipleOptions() {
		$parser = new ConsoleOptionParser();
		$parser->addOption('test')
			->addOption('connection')
			->addOption('table', array('short' => 't'));

		$result = $parser->parse(array('--test', 'value', '-t', '--connection', 'postgres'));
		$expected = array('test' => 'value', 'table' => true, 'connection' => 'postgres');
		$this->assertEquals($expected, $result[0], 'multiple options did not parse');
	}

/**
 * Test adding multiple options.
 *
 * @return void
 */
	function testAddOptions() {
		$parser = new ConsoleOptionParser('something', false);
		$result = $parser->addOptions(array(
			'name' => array('help' => 'The name'),
			'other' => array('help' => 'The other arg')
		));
		$this->assertEquals($parser, $result, 'addOptions is not chainable.');

		$result = $parser->options();
		$this->assertEquals(3, count($result), 'Not enough options');
	}

/**
 * test that boolean options work
 *
 * @return void
 */
	function testOptionWithBooleanParam() {
		$parser = new ConsoleOptionParser();
		$parser->addOption('no-commit', array('boolean' => true))
			->addOption('table', array('short' => 't'));

		$result = $parser->parse(array('--table', 'posts', '--no-commit', 'arg1', 'arg2'));
		$expected = array(array('table' => 'posts', 'no-commit' => true), array('arg1', 'arg2'));
		$this->assertEquals($expected, $result, 'Boolean option did not parse correctly.');
	}

/**
 * test parsing options that do not exist.
 *
 * @expectedException InvalidArgumentException
 */
	function testOptionThatDoesNotExist() {
		$parser = new ConsoleOptionParser();
		$parser->addOption('no-commit', array('boolean' => true));

		$result = $parser->parse(array('--fail', 'other'));
	}

/**
 * test that options with choices enforce them.
 *
 * @expectedException InvalidArgumentException
 * @return void
 */
	function testOptionWithChoices() {
		$parser = new ConsoleOptionParser();
		$parser->addOption('name', array('choices' => array('mark', 'jose')));
		
		$result = $parser->parse(array('--name', 'mark'));
		$expected = array('name' => 'mark');
		$this->assertEquals($expected, $result[0], 'Got the correct value.');

		$result = $parser->parse(array('--name', 'jimmy'));
	}

/**
 * test positional argument parsing.
 *
 * @return void
 */
	function testPositionalArgument() {
		$parser = new ConsoleOptionParser();
		$result = $parser->addArgument('name', array('help' => 'An argument'));
		$this->assertEquals($parser, $result, 'Should returnn this');
	}

/**
 * test overwriting positional arguments.
 *
 * @return void
 */
	function testPositionalArgOverwrite() {
		$parser = new ConsoleOptionParser();
		$parser->addArgument('name', array('help' => 'An argument'))
			->addArgument('other', array('index' => 0));

		$result = $parser->arguments();
		$this->assertEquals(1, count($result), 'Overwrite did not occur');
	}

/**
 * test parsing arguments.
 *
 * @expectedException InvalidArgumentException
 * @return void
 */
	function testParseArgumentTooMany() {
		$parser = new ConsoleOptionParser();
		$parser->addArgument('name', array('help' => 'An argument'))
			->addArgument('other');

		$expected = array('one', 'two');
		$result = $parser->parse($expected);
		$this->assertEquals($expected, $result[1], 'Arguments are not as expected');

		$result = $parser->parse(array('one', 'two', 'three'));
	}

/**
 * test that when there are not enough arguments an exception is raised
 *
 * @expectedException RuntimeException
 * @return void
 */
	function testPositionalArgNotEnough() {
		$parser = new ConsoleOptionParser();
		$parser->addArgument('name', array('required' => true))
			->addArgument('other', array('required' => true));

		$parser->parse(array('one'));
	}

/**
 * test that arguments with choices enforce them.
 *
 * @expectedException InvalidArgumentException
 * @return void
 */
	function testPositionalArgWithChoices() {
		$parser = new ConsoleOptionParser();
		$parser->addArgument('name', array('choices' => array('mark', 'jose')));

		$result = $parser->parse(array('mark'));
		$expected = array('mark');
		$this->assertEquals($expected, $result[1], 'Got the correct value.');

		$result = $parser->parse(array('jimmy'));
	}

/**
 * Test adding multiple arguments.
 *
 * @return void
 */
	function testAddArguments() {
		$parser = new ConsoleOptionParser();
		$result = $parser->addArguments(array(
			'name' => array('help' => 'The name'),
			'other' => array('help' => 'The other arg')
		));
		$this->assertEquals($parser, $result, 'addArguments is not chainable.');

		$result = $parser->arguments();
		$this->assertEquals(2, count($result), 'Not enough arguments');
	}

/**
 * test setting a subcommand up.
 *
 * @return void
 */
	function testSubcommand() {
		$parser = new ConsoleOptionParser();
		$result = $parser->addSubcommand('initdb', array(
			'help' => 'Initialize the database'
		));
		$this->assertEquals($parser, $result, 'Adding a subcommand is not chainable');
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

		$result = $parser->help();
		$expected = <<<TEXT
<info>Usage:</info>
cake mycommand [-h] [--test] [-c default]

<info>Options:</info>

--help, -h        Display this help.
--test            A test option.
--connection, -c  The connection to use. <comment>(default: default)</comment>

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

		$result = $parser->help();
		$expected = <<<TEXT
<info>Usage:</info>
cake mycommand [-h] [--test] model [other_longer]

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

		$result = $parser->help();
		$expected = <<<TEXT
Description text

<info>Usage:</info>
cake mycommand [-h] [--test] model

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
		
		$result = $parser->help();
		$expected = <<<TEXT
<info>Usage:</info>
cake mycommand [subcommand] [-h] [--test]

<info>Subcommands:</info>

method  This is another command

<info>Options:</info>

--help, -h  Display this help.
--test      A test option.

TEXT;
		$this->assertEquals($expected, $result, 'Help is not correct.');
	}

/**
 * test that help() with a command param shows the help for a subcommand
 *
 * @return void
 */
	function testHelpSubcommandHelp() {
		$subParser = new ConsoleOptionParser('method', false);
		$subParser->addOption('connection', array('help' => 'Db connection.'));

		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addSubcommand('method', array(
				'help' => 'This is another command',
				'parser' => $subParser
			))
			->addOption('test', array('help' => 'A test option.'));

		$result = $parser->help('method');
		$expected = <<<TEXT
<info>Usage:</info>
cake mycommand method [-h] [--connection]

<info>Options:</info>

--help, -h        Display this help.
--connection      Db connection.

TEXT;
		$this->assertEquals($expected, $result, 'Help is not correct.');
	}
}