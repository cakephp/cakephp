<?php
/**
 * ConsoleOptionParserTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2011, Cake Software Foundation, Inc.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc.
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ConsoleOptionParser', 'Console');

class ConsoleOptionParserTest extends CakeTestCase {

/**
 * test setting the console description
 *
 * @return void
 */
	public function testDescription() {
		$parser = new ConsoleOptionParser('test', false);
		$result = $parser->description('A test');

		$this->assertEquals($parser, $result, 'Setting description is not chainable');
		$this->assertEquals('A test', $parser->description(), 'getting value is wrong.');

		$result = $parser->description(array('A test', 'something'));
		$this->assertEquals("A test\nsomething", $parser->description(), 'getting value is wrong.');
	}

/**
 * test setting the console epliog
 *
 * @return void
 */
	public function testEpilog() {
		$parser = new ConsoleOptionParser('test', false);
		$result = $parser->epilog('A test');

		$this->assertEquals($parser, $result, 'Setting epilog is not chainable');
		$this->assertEquals('A test', $parser->epilog(), 'getting value is wrong.');

		$result = $parser->epilog(array('A test', 'something'));
		$this->assertEquals("A test\nsomething", $parser->epilog(), 'getting value is wrong.');
	}

/**
 * test adding an option returns self.
 *
 * @return void
 */
	public function testAddOptionReturnSelf() {
		$parser = new ConsoleOptionParser('test', false);
		$result = $parser->addOption('test');
		$this->assertEquals($parser, $result, 'Did not return $this from addOption');
	}

/**
 * test adding an option and using the long value for parsing.
 *
 * @return void
 */
	public function testAddOptionLong() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('test', array(
			'short' => 't'
		));
		$result = $parser->parse(array('--test', 'value'));
		$this->assertEquals(array('test' => 'value', 'help' => false), $result[0], 'Long parameter did not parse out');
	}

/**
 * test addOption with an object.
 *
 * @return void
 */
	public function testAddOptionObject() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption(new ConsoleInputOption('test', 't'));
		$result = $parser->parse(array('--test=value'));
		$this->assertEquals(array('test' => 'value', 'help' => false), $result[0], 'Long parameter did not parse out');
	}

/**
 * test adding an option and using the long value for parsing.
 *
 * @return void
 */
	public function testAddOptionLongEquals() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('test', array(
			'short' => 't'
		));
		$result = $parser->parse(array('--test=value'));
		$this->assertEquals(array('test' => 'value', 'help' => false), $result[0], 'Long parameter did not parse out');
	}

/**
 * test adding an option and using the default.
 *
 * @return void
 */
	public function testAddOptionDefault() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('test', array(
			'default' => 'default value',
		));
		$result = $parser->parse(array('--test'));
		$this->assertEquals(array('test' => 'default value', 'help' => false), $result[0], 'Default value did not parse out');

		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('test', array(
			'default' => 'default value',
		));
		$result = $parser->parse(array());
		$this->assertEquals(array('test' => 'default value', 'help' => false), $result[0], 'Default value did not parse out');
	}

/**
 * test adding an option and using the short value for parsing.
 *
 * @return void
 */
	public function testAddOptionShort() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('test', array(
			'short' => 't'
		));
		$result = $parser->parse(array('-t', 'value'));
		$this->assertEquals(array('test' => 'value', 'help' => false), $result[0], 'Short parameter did not parse out');
	}

/**
 * Test that adding an option using a two letter short value causes an exception.
 * As they will not parse correctly.
 *
 * @expectedException ConsoleException
 * @return void
 */
	public function testAddOptionShortOneLetter() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('test', array('short' => 'te'));
	}

/**
 * test adding and using boolean options.
 *
 * @return void
 */
	public function testAddOptionBoolean() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('test', array(
			'boolean' => true,
		));

		$result = $parser->parse(array('--test', 'value'));
		$expected = array(array('test' => true, 'help' => false), array('value'));
		$this->assertEquals($expected, $result);

		$result = $parser->parse(array('value'));
		$expected = array(array('test' => false, 'help' => false), array('value'));
		$this->assertEquals($expected, $result);
	}

/**
 * test adding an multiple shorts.
 *
 * @return void
 */
	public function testAddOptionMultipleShort() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('test', array('short' => 't', 'boolean' => true))
			->addOption('file', array('short' => 'f', 'boolean' => true))
			->addOption('output', array('short' => 'o', 'boolean' => true));

		$result = $parser->parse(array('-o', '-t', '-f'));
		$expected = array('file' => true, 'test' => true, 'output' => true, 'help' => false);
		$this->assertEquals($expected, $result[0], 'Short parameter did not parse out');

		$result = $parser->parse(array('-otf'));
		$this->assertEquals($expected, $result[0], 'Short parameter did not parse out');
	}

/**
 * test multiple options at once.
 *
 * @return void
 */
	public function testMultipleOptions() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('test')
			->addOption('connection')
			->addOption('table', array('short' => 't', 'default' => true));

		$result = $parser->parse(array('--test', 'value', '-t', '--connection', 'postgres'));
		$expected = array('test' => 'value', 'table' => true, 'connection' => 'postgres', 'help' => false);
		$this->assertEquals($expected, $result[0], 'multiple options did not parse');
	}

/**
 * Test adding multiple options.
 *
 * @return void
 */
	public function testAddOptions() {
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
	public function testOptionWithBooleanParam() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('no-commit', array('boolean' => true))
			->addOption('table', array('short' => 't'));

		$result = $parser->parse(array('--table', 'posts', '--no-commit', 'arg1', 'arg2'));
		$expected = array(array('table' => 'posts', 'no-commit' => true, 'help' => false), array('arg1', 'arg2'));
		$this->assertEquals($expected, $result, 'Boolean option did not parse correctly.');
	}

/**
 * test parsing options that do not exist.
 *
 * @expectedException ConsoleException
 */
	public function testOptionThatDoesNotExist() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('no-commit', array('boolean' => true));

		$result = $parser->parse(array('--fail', 'other'));
	}

/**
 * test parsing short options that do not exist.
 *
 * @expectedException ConsoleException
 */
	public function testShortOptionThatDoesNotExist() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('no-commit', array('boolean' => true));

		$result = $parser->parse(array('-f'));
	}

/**
 * test that options with choices enforce them.
 *
 * @expectedException ConsoleException
 * @return void
 */
	public function testOptionWithChoices() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('name', array('choices' => array('mark', 'jose')));

		$result = $parser->parse(array('--name', 'mark'));
		$expected = array('name' => 'mark', 'help' => false);
		$this->assertEquals($expected, $result[0], 'Got the correct value.');

		$result = $parser->parse(array('--name', 'jimmy'));
	}

/**
 * Ensure that option values can start with -
 *
 * @return void
 */
	public function testOptionWithValueStartingWithMinus() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('name')
			->addOption('age');

		$result = $parser->parse(array('--name', '-foo', '--age', 'old'));
		$expected = array('name' => '-foo', 'age' => 'old', 'help' => false);
		$this->assertEquals($expected, $result[0], 'Option values starting with "-" are broken.');
	}

/**
 * test positional argument parsing.
 *
 * @return void
 */
	public function testPositionalArgument() {
		$parser = new ConsoleOptionParser('test', false);
		$result = $parser->addArgument('name', array('help' => 'An argument'));
		$this->assertEquals($parser, $result, 'Should returnn this');
	}

/**
 * test addOption with an object.
 *
 * @return void
 */
	public function testAddArgumentObject() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addArgument(new ConsoleInputArgument('test'));
		$result = $parser->arguments();
		$this->assertEquals(1, count($result));
		$this->assertEquals('test', $result[0]->name());
	}

/**
 * test overwriting positional arguments.
 *
 * @return void
 */
	public function testPositionalArgOverwrite() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addArgument('name', array('help' => 'An argument'))
			->addArgument('other', array('index' => 0));

		$result = $parser->arguments();
		$this->assertEquals(1, count($result), 'Overwrite did not occur');
	}

/**
 * test parsing arguments.
 *
 * @expectedException ConsoleException
 * @return void
 */
	public function testParseArgumentTooMany() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addArgument('name', array('help' => 'An argument'))
			->addArgument('other');

		$expected = array('one', 'two');
		$result = $parser->parse($expected);
		$this->assertEquals($expected, $result[1], 'Arguments are not as expected');

		$result = $parser->parse(array('one', 'two', 'three'));
	}

/**
 * test parsing arguments with 0 value.
 *
 * @return void
 */
	public function testParseArgumentZero() {
		$parser = new ConsoleOptionParser('test', false);

		$expected = array('one', 'two', 0, 'after', 'zero');
		$result = $parser->parse($expected);
		$this->assertEquals($expected, $result[1], 'Arguments are not as expected');
	}

/**
 * test that when there are not enough arguments an exception is raised
 *
 * @expectedException ConsoleException
 * @return void
 */
	public function testPositionalArgNotEnough() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addArgument('name', array('required' => true))
			->addArgument('other', array('required' => true));

		$parser->parse(array('one'));
	}

/**
 * test that arguments with choices enforce them.
 *
 * @expectedException ConsoleException
 * @return void
 */
	public function testPositionalArgWithChoices() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addArgument('name', array('choices' => array('mark', 'jose')))
			->addArgument('alias', array('choices' => array('cowboy', 'samurai')))
			->addArgument('weapon', array('choices' => array('gun', 'sword')));

		$result = $parser->parse(array('mark', 'samurai', 'sword'));
		$expected = array('mark', 'samurai', 'sword');
		$this->assertEquals($expected, $result[1], 'Got the correct value.');

		$result = $parser->parse(array('jose', 'coder'));
	}

/**
 * Test adding multiple arguments.
 *
 * @return void
 */
	public function testAddArguments() {
		$parser = new ConsoleOptionParser('test', false);
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
	public function testSubcommand() {
		$parser = new ConsoleOptionParser('test', false);
		$result = $parser->addSubcommand('initdb', array(
			'help' => 'Initialize the database'
		));
		$this->assertEquals($parser, $result, 'Adding a subcommand is not chainable');
	}

/**
 * test addSubcommand with an object.
 *
 * @return void
 */
	public function testAddSubcommandObject() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addSubcommand(new ConsoleInputSubcommand('test'));
		$result = $parser->subcommands();
		$this->assertEquals(1, count($result));
		$this->assertEquals('test', $result['test']->name());
	}

/**
 * test adding multiple subcommands
 *
 * @return void
 */
	public function testAddSubcommands() {
		$parser = new ConsoleOptionParser('test', false);
		$result = $parser->addSubcommands(array(
			'initdb' => array('help' => 'Initialize the database'),
			'create' => array('help' => 'Create something')
		));
		$this->assertEquals($parser, $result, 'Adding a subcommands is not chainable');
		$result = $parser->subcommands();
		$this->assertEquals(2, count($result), 'Not enough subcommands');
	}

/**
 * test that no exception is triggered when help is being generated
 *
 * @return void
 */
	public function testHelpNoExceptionWhenGettingHelp() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addOption('test', array('help' => 'A test option.'))
			->addArgument('model', array('help' => 'The model to make.', 'required' => true));

		$result = $parser->parse(array('--help'));
		$this->assertTrue($result[0]['help']);
	}

/**
 * test that help() with a command param shows the help for a subcommand
 *
 * @return void
 */
	public function testHelpSubcommandHelp() {
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

/**
 * test building a parser from an array.
 *
 * @return void
 */
	public function testBuildFromArray() {
		$spec = array(
			'command' => 'test',
			'arguments' => array(
				'name' => array('help' => 'The name'),
				'other' => array('help' => 'The other arg')
			),
			'options' => array(
				'name' => array('help' => 'The name'),
				'other' => array('help' => 'The other arg')
			),
			'subcommands' => array(
				'initdb' => array('help' => 'make database')
			),
			'description' => 'description text',
			'epilog' => 'epilog text'
		);
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
	public function testCreateFactory() {
		$parser = ConsoleOptionParser::create('factory', false);
		$this->assertInstanceOf('ConsoleOptionParser', $parser);
		$this->assertEquals('factory', $parser->command());
	}

/**
 * test that command() inflects the command name.
 *
 * @return void
 */
	public function testCommandInflection() {
		$parser = new ConsoleOptionParser('CommandLine');
		$this->assertEquals('command_line', $parser->command());
	}

/**
 * test that parse() takes a subcommand argument, and that the subcommand parser
 * is used.
 *
 * @return void
 */
	public function testParsingWithSubParser() {
		$parser = new ConsoleOptionParser('test', false);
		$parser->addOption('primary')
			->addArgument('one', array('required' => true, 'choices' => array('a', 'b')))
			->addArgument('two', array('required' => true))
			->addSubcommand('sub', array(
				'parser' => array(
					'options' => array(
						'secondary' => array('boolean' => true),
						'fourth' => array('help' => 'fourth option')
					),
					'arguments' => array(
						'sub_arg' => array('choices' => array('c', 'd'))
					)
				)
			));

		$result = $parser->parse(array('--secondary', '--fourth', '4', 'c'), 'sub');
		$expected = array(array(
			'secondary' => true,
			'fourth' => '4',
			'help' => false,
			'verbose' => false,
			'quiet' => false), array('c'));
		$this->assertEquals($expected, $result, 'Sub parser did not parse request.');
	}

}
