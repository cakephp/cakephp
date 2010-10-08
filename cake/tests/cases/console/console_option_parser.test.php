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
 * test adding an option and using the shortcut value for parsing.
 *
 * @return void
 */
	function testAddOptionShortcut() {
		$parser = new ConsoleOptionParser();
		$parser->addOption('test', array(
			'shortcut' => 't'
		));
		$result = $parser->parse(array('-t', 'value'));
		$this->assertEqual(array('test' => 'value'), $result[0], 'Short parameter did not parse out');
	}
}