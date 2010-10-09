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
 * test getting help with defined options.
 *
 * @return void
 */
	function testGetHelpWithOptions() {
		$parser = new ConsoleOptionParser('mycommand', false);
		$parser->addOption('test', array('short' => 't', 'help' => 'A test option.'))
			->addOption('connection', array('help' => 'The connection to use.', 'default' => 'default'));

		$result = $parser->help();
		$expected = <<<TEXT
<info>Usage:</info>
cake mycommand [-h] [-t] [--connection default]

<info>Options:</info>

--help, -h    Display this help.
--test, -t    A test option.
--connection  The connection to use. <comment>(default: default)</comment>
TEXT;
		$this->assertEquals($expected, $result, 'Help does not match');
	}
}