<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.8
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses("TableShellHelper", "Console/Helper");
App::uses("ConsoleOutputStub", "TestSuite/Stub");

/**
 * ProgressHelper test.
 * @property ConsoleOutputStub $consoleOutput
 * @property TableShellHelper $helper
 */
class TableShellHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->consoleOutput = new ConsoleOutputStub();
		$this->helper = new TableShellHelper($this->consoleOutput);
	}

/**
 * Test output
 *
 * @return void
 */
	public function testDefaultOutput() {
		$data = array(
			array('Header 1', 'Header', 'Long Header'),
			array('short', 'Longish thing', 'short'),
			array('Longer thing', 'short', 'Longest Value'),
		);
		$this->helper->output($data);
		$expected = array(
			'+--------------+---------------+---------------+',
			'| <info>Header 1</info>     | <info>Header</info>        | <info>Long Header</info>   |',
			'+--------------+---------------+---------------+',
			'| short        | Longish thing | short         |',
			'| Longer thing | short         | Longest Value |',
			'+--------------+---------------+---------------+',
		);
		$this->assertEquals($expected, $this->consoleOutput->messages());
	}

/**
 * Test output with multibyte characters
 *
 * @return void
 */
	public function testOutputUtf8() {
		$data = array(
			array('Header 1', 'Head', 'Long Header'),
			array('short', 'ÄÄÄÜÜÜ', 'short'),
			array('Longer thing', 'longerish', 'Longest Value'),
		);
		$this->helper->output($data);
		$expected = array(
			'+--------------+-----------+---------------+',
			'| <info>Header 1</info>     | <info>Head</info>      | <info>Long Header</info>   |',
			'+--------------+-----------+---------------+',
			'| short        | ÄÄÄÜÜÜ    | short         |',
			'| Longer thing | longerish | Longest Value |',
			'+--------------+-----------+---------------+',
		);
		$this->assertEquals($expected, $this->consoleOutput->messages());
	}

/**
 * Test output without headers
 *
 * @return void
 */
	public function testOutputWithoutHeaderStyle() {
		$data = array(
			array('Header 1', 'Header', 'Long Header'),
			array('short', 'Longish thing', 'short'),
			array('Longer thing', 'short', 'Longest Value'),
		);
		$this->helper->config(array('headerStyle' => false));
		$this->helper->output($data);
		$expected = array(
			'+--------------+---------------+---------------+',
			'| Header 1     | Header        | Long Header   |',
			'+--------------+---------------+---------------+',
			'| short        | Longish thing | short         |',
			'| Longer thing | short         | Longest Value |',
			'+--------------+---------------+---------------+',
		);
		$this->assertEquals($expected, $this->consoleOutput->messages());
	}

/**
 * Test output with different header style
 *
 * @return void
 */
	public function testOutputWithDifferentHeaderStyle() {
		$data = array(
			array('Header 1', 'Header', 'Long Header'),
			array('short', 'Longish thing', 'short'),
			array('Longer thing', 'short', 'Longest Value'),
		);
		$this->helper->config(array('headerStyle' => 'error'));
		$this->helper->output($data);
		$expected = array(
			'+--------------+---------------+---------------+',
			'| <error>Header 1</error>     | <error>Header</error>        | <error>Long Header</error>   |',
			'+--------------+---------------+---------------+',
			'| short        | Longish thing | short         |',
			'| Longer thing | short         | Longest Value |',
			'+--------------+---------------+---------------+',
		);
		$this->assertEquals($expected, $this->consoleOutput->messages());
	}

/**
 * Test output without table headers
 *
 * @return void
 */
	public function testOutputWithoutHeaders() {
		$data = array(
			array('short', 'Longish thing', 'short'),
			array('Longer thing', 'short', 'Longest Value'),
		);
		$this->helper->config(array('headers' => false));
		$this->helper->output($data);
		$expected = array(
			'+--------------+---------------+---------------+',
			'| short        | Longish thing | short         |',
			'| Longer thing | short         | Longest Value |',
			'+--------------+---------------+---------------+',
		);
		$this->assertEquals($expected, $this->consoleOutput->messages());
	}

/**
 * Test output with row separator
 *
 * @return void
 */
	public function testOutputWithRowSeparator() {
		$data = array(
			array('Header 1', 'Header', 'Long Header'),
			array('short', 'Longish thing', 'short'),
			array('Longer thing', 'short', 'Longest Value')
		);
		$this->helper->config(array('rowSeparator' => true));
		$this->helper->output($data);
		$expected = array(
			'+--------------+---------------+---------------+',
			'| <info>Header 1</info>     | <info>Header</info>        | <info>Long Header</info>   |',
			'+--------------+---------------+---------------+',
			'| short        | Longish thing | short         |',
			'+--------------+---------------+---------------+',
			'| Longer thing | short         | Longest Value |',
			'+--------------+---------------+---------------+',
		);
		$this->assertEquals($expected, $this->consoleOutput->messages());
	}

/**
 * Test output with row separator and no headers
 *
 * @return void
 */
	public function testOutputWithRowSeparatorAndHeaders() {
		$data = array(
			array('Header 1', 'Header', 'Long Header'),
			array('short', 'Longish thing', 'short'),
			array('Longer thing', 'short', 'Longest Value'),
		);
		$this->helper->config(array('rowSeparator' => true));
		$this->helper->output($data);
		$expected = array(
			'+--------------+---------------+---------------+',
			'| <info>Header 1</info>     | <info>Header</info>        | <info>Long Header</info>   |',
			'+--------------+---------------+---------------+',
			'| short        | Longish thing | short         |',
			'+--------------+---------------+---------------+',
			'| Longer thing | short         | Longest Value |',
			'+--------------+---------------+---------------+',
		);
		$this->assertEquals($expected, $this->consoleOutput->messages());
	}
}