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
use Cake\Utility\CellTrait;
use Cake\View\Cell;

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

		$this->assertTrue(
			strpos($render, '<h2>Lorem ipsum</h2>') !== false &&
			strpos($render, '<h2>Usectetur adipiscing eli</h2>') !== false &&
			strpos($render, '<h2>Topis semper blandit eu non</h2>') !== false &&
			strpos($render, '<h2>Suspendisse gravida neque</h2>') !== false
		);
	}

/**
 * Tests that we are able pass multiple arguments to cell methods.
 *
 * @return void
 */
	public function testCellWithArguments() {
		$cell = $this->View->cell('Articles::doEcho', ['msg1' => 'dummy', 'msg2' => ' message']);
		$render = "{$cell}";
		$this->assertTrue(strpos($render, 'dummy message') !== false);
	}

/**
 * Tests that cell runs default action when none is provided.
 *
 * @return void
 */
	public function testDefaultCellAction() {
		$appCell = $this->View->cell('Articles');
		$this->assertTrue(strpos("{$appCell}", 'dummy') !== false);

		$pluginCell = $this->View->cell('TestPlugin.Dummy');
		$this->assertTrue(strpos("{$pluginCell}", 'dummy') !== false);
	}

/**
 * Tests manual render() invocation.
 *
 * @return void
 */
	public function testCellManualRender() {
		$cell = $this->View->cell('Articles::doEcho', ['msg1' => 'dummy', 'msg2' => ' message']);
		$this->assertTrue(strpos($cell->render(), 'dummy message') !== false);

		$cell->teaserList();
		$this->assertTrue(strpos($cell->render('teaser_list'), '<h2>Lorem ipsum</h2>') !== false);
	}

/**
 * Tests that using plugin's cells works.
 *
 * @return void
 */
	public function testPluginCell() {
		$cell = $this->View->cell('TestPlugin.Dummy::echoThis', ['msg' => 'hello world!']);
		$this->assertTrue(strpos("{$cell}", 'hello world!') !== false);
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
