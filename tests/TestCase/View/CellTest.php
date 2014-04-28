<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use Cake\View\Cell;
use Cake\View\CellTrait;

/**
 * CellTest class.
 *
 * For testing both View\Cell & Utility\CellTrait
 */
class CellTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('App.namespace', 'TestApp');
		Configure::write('debug', 2);
		Plugin::load('TestPlugin');
		$request = $this->getMock('Cake\Network\Request');
		$response = $this->getMock('Cake\Network\Response');
		$this->View = new \Cake\View\View($request, $response);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		Plugin::unload('TestPlugin');
		unset($this->View);
	}

/**
 * Tests basic cell rendering.
 *
 * @return void
 */
	public function testCellRender() {
		$cell = $this->View->cell('Articles::teaserList');
		$render = "{$cell}";

		$this->assertEquals('teaser_list', $cell->template);
		$this->assertContains('<h2>Lorem ipsum</h2>', $render);
		$this->assertContains('<h2>Usectetur adipiscing eli</h2>', $render);
		$this->assertContains('<h2>Topis semper blandit eu non</h2>', $render);
		$this->assertContains('<h2>Suspendisse gravida neque</h2>', $render);
	}

/**
 * Tests that we are able pass multiple arguments to cell methods.
 *
 * @return void
 */
	public function testCellWithArguments() {
		$cell = $this->View->cell('Articles::doEcho', ['msg1' => 'dummy', 'msg2' => ' message']);
		$render = "{$cell}";
		$this->assertContains('dummy message', $render);
	}

/**
 * Tests that cell runs default action when none is provided.
 *
 * @return void
 */
	public function testDefaultCellAction() {
		$appCell = $this->View->cell('Articles');

		$this->assertEquals('display', $appCell->template);
		$this->assertContains('dummy', "{$appCell}");

		$pluginCell = $this->View->cell('TestPlugin.Dummy');
		$this->assertContains('dummy', "{$pluginCell}");
		$this->assertEquals('display', $pluginCell->template);
	}

/**
 * Tests manual render() invocation.
 *
 * @return void
 */
	public function testCellManualRender() {
		$cell = $this->View->cell('Articles::doEcho', ['msg1' => 'dummy', 'msg2' => ' message']);
		$this->assertContains('dummy message', $cell->render());

		$cell->teaserList();
		$this->assertContains('<h2>Lorem ipsum</h2>', $cell->render('teaser_list'));
	}

/**
 * Test rendering a cell with a theme.
 *
 * @return void
 */
	public function testCellRenderThemed() {
		$this->View->theme = 'TestTheme';
		$cell = $this->View->cell('Articles', ['msg' => 'hello world!']);

		$this->assertEquals($this->View->theme, $cell->theme);
		$this->assertContains('Themed cell content.', $cell->render());
		$this->assertEquals($cell->View->theme, $cell->theme);
	}

/**
 * Tests that using plugin's cells works.
 *
 * @return void
 */
	public function testPluginCell() {
		$cell = $this->View->cell('TestPlugin.Dummy::echoThis', ['msg' => 'hello world!']);
		$this->assertContains('hello world!', "{$cell}");
	}

/**
 * Tests that using an unexisting cell throws an exception.
 *
 * @expectedException \Cake\View\Error\MissingCellException
 * @return void
 */
	public function testUnexistingCell() {
		$cell = $this->View->cell('TestPlugin.Void::echoThis', ['arg1' => 'v1']);
		$cell = $this->View->cell('Void::echoThis', ['arg1' => 'v1', 'arg2' => 'v2']);
	}

}
