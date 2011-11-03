<?php
/**
 * ConsoleOutputTest file
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
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ConsoleOutput', 'Console');

class ConsoleOutputTest extends CakeTestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->output = $this->getMock('ConsoleOutput', array('_write'));
		$this->output->outputAs(ConsoleOutput::COLOR);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		unset($this->output);
	}

/**
 * test writing with no new line
 *
 * @return void
 */
	public function testWriteNoNewLine() {
		$this->output->expects($this->once())->method('_write')
			->with('Some output');

		$this->output->write('Some output', false);
	}

/**
 * test writing with no new line
 *
 * @return void
 */
	public function testWriteNewLine() {
		$this->output->expects($this->once())->method('_write')
			->with('Some output' . PHP_EOL);

		$this->output->write('Some output');
	}

/**
 * test write() with multiple new lines
 *
 * @return void
 */
	public function testWriteMultipleNewLines() {
		$this->output->expects($this->once())->method('_write')
			->with('Some output' . PHP_EOL . PHP_EOL . PHP_EOL . PHP_EOL);

		$this->output->write('Some output', 4);
	}

/**
 * test writing an array of messages.
 *
 * @return void
 */
	public function testWriteArray() {
		$this->output->expects($this->once())->method('_write')
			->with('Line' . PHP_EOL . 'Line' . PHP_EOL . 'Line' . PHP_EOL);

		$this->output->write(array('Line', 'Line', 'Line'));
	}

/**
 * test getting a style.
 *
 * @return void
 */
	public function testStylesGet() {
		$result = $this->output->styles('error');
		$expected = array('text' => 'red', 'underline' => true);
		$this->assertEqual($expected, $result);

		$this->assertNull($this->output->styles('made_up_goop'));

		$result = $this->output->styles();
		$this->assertNotEmpty($result, 'error', 'Error is missing');
		$this->assertNotEmpty($result, 'warning', 'Warning is missing');
	}

/**
 * test adding a style.
 *
 * @return void
 */
	public function testStylesAdding() {
		$this->output->styles('test', array('text' => 'red', 'background' => 'black'));
		$result = $this->output->styles('test');
		$expected = array('text' => 'red', 'background' => 'black');
		$this->assertEquals($expected, $result);

		$this->assertTrue($this->output->styles('test', false), 'Removing a style should return true.');
		$this->assertNull($this->output->styles('test'), 'Removed styles should be null.');
	}

/**
 * test formatting text with styles.
 *
 * @return void
 */
	public function testFormattingSimple() {
		$this->output->expects($this->once())->method('_write')
			->with("\033[31;4mError:\033[0m Something bad");

		$this->output->write('<error>Error:</error> Something bad', false);
	}

/**
 * test that formatting doesn't eat tags it doesn't know about.
 *
 * @return void
 */
	public function testFormattingNotEatingTags() {
		$this->output->expects($this->once())->method('_write')
			->with("<red> Something bad");

		$this->output->write('<red> Something bad', false);
	}

/**
 * test formatting with custom styles.
 *
 * @return void
 */
	public function testFormattingCustom() {
		$this->output->styles('annoying', array(
			'text' => 'magenta',
			'background' => 'cyan',
			'blink' => true,
			'underline' => true
		));

		$this->output->expects($this->once())->method('_write')
			->with("\033[35;46;5;4mAnnoy:\033[0m Something bad");

		$this->output->write('<annoying>Annoy:</annoying> Something bad', false);
	}

/**
 * test formatting text with missing styles.
 *
 * @return void
 */
	public function testFormattingMissingStyleName() {
		$this->output->expects($this->once())->method('_write')
			->with("<not_there>Error:</not_there> Something bad");

		$this->output->write('<not_there>Error:</not_there> Something bad', false);
	}

/**
 * test formatting text with multiple styles.
 *
 * @return void
 */
	public function testFormattingMultipleStylesName() {
		$this->output->expects($this->once())->method('_write')
			->with("\033[31;4mBad\033[0m \033[33mWarning\033[0m Regular");

		$this->output->write('<error>Bad</error> <warning>Warning</warning> Regular', false);
	}

/**
 * test that multiple tags of the same name work in one string.
 *
 * @return void
 */
	public function testFormattingMultipleSameTags() {
		$this->output->expects($this->once())->method('_write')
			->with("\033[31;4mBad\033[0m \033[31;4mWarning\033[0m Regular");

		$this->output->write('<error>Bad</error> <error>Warning</error> Regular', false);
	}

/**
 * test raw output not getting tags replaced.
 *
 * @return void
 */
	public function testOutputAsRaw() {
		$this->output->outputAs(ConsoleOutput::RAW);
		$this->output->expects($this->once())->method('_write')
			->with('<error>Bad</error> Regular');

		$this->output->write('<error>Bad</error> Regular', false);
	}

/**
 * test plain output.
 *
 * @return void
 */
	public function testOutputAsPlain() {
		$this->output->outputAs(ConsoleOutput::PLAIN);
		$this->output->expects($this->once())->method('_write')
			->with('Bad Regular');

		$this->output->write('<error>Bad</error> Regular', false);
	}

/**
 * test plain output only strips tags used for formatting.
 *
 * @return void
 */
	public function testOutputAsPlainSelectiveTagRemoval() {
		$this->output->outputAs(ConsoleOutput::PLAIN);
		$this->output->expects($this->once())->method('_write')
			->with('Bad Regular <b>Left</b> <i>behind</i> <name>');

		$this->output->write('<error>Bad</error> Regular <b>Left</b> <i>behind</i> <name>', false);
	}
}
