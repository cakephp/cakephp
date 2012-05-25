<?php
/**
 * TestSuiteShell test case
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ShellDispatcher', 'Console');
App::uses('TestShell', 'Console/Command');

class TestTestShell extends TestShell {

	public function mapFileToCase($file, $category, $throwOnMissingFile = true) {
		return $this->_mapFileToCase($file, $category, $throwOnMissingFile);
	}

	public function mapFileToCategory($file) {
		return $this->_mapFileToCategory($file);
	}

}

class TestShellTest extends CakeTestCase {

/**
 * setUp test case
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'TestTestShell',
			array('in', 'out', 'hr', 'help', 'error', 'err', '_stop', 'initialize', '_run', 'clear'),
			array($out, $out, $in)
		);
		$this->Shell->OptionParser = $this->getMock('ConsoleOptionParser', array(), array(null, false));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Dispatch, $this->Shell);
	}

/**
 * testMapCoreFileToCategory
 * 
 * @return void
 */
	public function testMapCoreFileToCategory() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCategory('lib/Cake/basics.php');
		$this->assertSame('core', $return);

		$return = $this->Shell->mapFileToCategory('lib/Cake/Core/App.php');
		$this->assertSame('core', $return);

		$return = $this->Shell->mapFileToCategory('lib/Cake/Some/Deeply/Nested/Structure.php');
		$this->assertSame('core', $return);
	}

/**
 * testMapCoreFileToCase
 *
 * basics.php is a slightly special case - it's the only file in the core with a test that isn't Capitalized
 * 
 * @return void
 */
	public function testMapCoreFileToCase() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCase('lib/Cake/basics.php', 'core');
		$this->assertSame('Basics', $return);

		$return = $this->Shell->mapFileToCase('lib/Cake/Core/App.php', 'core');
		$this->assertSame('Core/App', $return);

		$return = $this->Shell->mapFileToCase('lib/Cake/Some/Deeply/Nested/Structure.php', 'core', false);
		$this->assertSame('Some/Deeply/Nested/Structure', $return);
	}

/**
 * testMapAppFileToCategory
 * 
 * @return void
 */
	public function testMapAppFileToCategory() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCategory(APP . 'Controller/ExampleController.php');
		$this->assertSame('app', $return);

		$return = $this->Shell->mapFileToCategory(APP . 'My/File/Is/Here.php');
		$this->assertSame('app', $return);
	}

/**
 * testMapAppFileToCase
 *
 * @return void
 */
	public function testMapAppFileToCase() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCase(APP . 'Controller/ExampleController.php', 'app', false);
		$this->assertSame('Controller/ExampleController', $return);

		$return = $this->Shell->mapFileToCase(APP . 'My/File/Is/Here.php', 'app', false);
		$this->assertSame('My/File/Is/Here', $return);
	}

/**
 * testMapPluginFileToCategory
 * 
 * @return void
 */
	public function testMapPluginFileToCategory() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCategory(APP . 'Plugin/awesome/Controller/ExampleController.php');
		$this->assertSame('awesome', $return);

		$return = $this->Shell->mapFileToCategory(dirname(CAKE) . 'plugins/awesome/Controller/ExampleController.php');
		$this->assertSame('awesome', $return);
	}

/**
 * testMapPluginFileToCase
 *
 * @return void
 */
	public function testMapPluginFileToCase() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCase(APP . 'Plugin/awesome/Controller/ExampleController.php', 'awesome', false);
		$this->assertSame('Controller/ExampleController', $return);

		$return = $this->Shell->mapFileToCase(dirname(CAKE) . 'plugins/awesome/Controller/ExampleController.php', 'awesome', false);
		$this->assertSame('Controller/ExampleController', $return);
	}

/**
 * testMapCoreTestToCategory
 * 
 * @return void
 */
	public function testMapCoreTestToCategory() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCategory('lib/Cake/Test/Case/BasicsTest.php');
		$this->assertSame('core', $return);

		$return = $this->Shell->mapFileToCategory('lib/Cake/Test/Case/BasicsTest.php');
		$this->assertSame('core', $return);

		$return = $this->Shell->mapFileToCategory('lib/Cake/Test/Case/Some/Deeply/Nested/StructureTest.php');
		$this->assertSame('core', $return);
	}

/**
 * testMapCoreTestToCase
 *
 * basics.php is a slightly special case - it's the only file in the core with a test that isn't Capitalized
 * 
 * @return void
 */
	public function testMapCoreTestToCase() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCase('lib/Cake/Test/Case/BasicsTest.php', 'core');
		$this->assertSame('Basics', $return);

		$return = $this->Shell->mapFileToCase('lib/Cake/Test/Case/Core/AppTest.php', 'core');
		$this->assertSame('Core/App', $return);

		$return = $this->Shell->mapFileToCase('lib/Cake/Test/Case/Some/Deeply/Nested/StructureTest.php', 'core', false);
		$this->assertSame('Some/Deeply/Nested/Structure', $return);
	}

/**
 * testMapAppTestToCategory
 * 
 * @return void
 */
	public function testMapAppTestToCategory() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCategory(APP . 'Test/Case/Controller/ExampleControllerTest.php');
		$this->assertSame('app', $return);

		$return = $this->Shell->mapFileToCategory(APP . 'Test/Case/My/File/Is/HereTest.php');
		$this->assertSame('app', $return);
	}

/**
 * testMapAppTestToCase
 *
 * @return void
 */
	public function testMapAppTestToCase() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCase(APP . 'Test/Case/Controller/ExampleControllerTest.php', 'app', false);
		$this->assertSame('Controller/ExampleController', $return);

		$return = $this->Shell->mapFileToCase(APP . 'Test/Case/My/File/Is/HereTest.php', 'app', false);
		$this->assertSame('My/File/Is/Here', $return);
	}

/**
 * testMapPluginTestToCategory
 * 
 * @return void
 */
	public function testMapPluginTestToCategory() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCategory(APP . 'Plugin/awesome/Test/Case/Controller/ExampleControllerTest.php');
		$this->assertSame('awesome', $return);

		$return = $this->Shell->mapFileToCategory(dirname(CAKE) . 'plugins/awesome/Test/Case/Controller/ExampleControllerTest.php');
		$this->assertSame('awesome', $return);
	}

/**
 * testMapPluginTestToCase
 *
 * @return void
 */
	public function testMapPluginTestToCase() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCase(APP . 'Plugin/awesome/Test/Case/Controller/ExampleControllerTest.php', 'awesome', false);
		$this->assertSame('Controller/ExampleController', $return);

		$return = $this->Shell->mapFileToCase(dirname(CAKE) . 'plugins/awesome/Test/Case/Controller/ExampleControllerTest.php', 'awesome', false);
		$this->assertSame('Controller/ExampleController', $return);
	}

/**
 * testMapNotTestToNothing
 *
 * @return void
 */
	public function testMapNotTestToNothing() {
		$this->Shell->startup();

		$return = $this->Shell->mapFileToCategory(APP . 'Test/Case/NotATestFile.php');
		$this->assertSame('app', $return);

		$return = $this->Shell->mapFileToCase(APP . 'Test/Case/NotATestFile.php', false, false);
		$this->assertFalse($return);

		$return = $this->Shell->mapFileToCategory(APP . 'Test/Fixture/SomeTest.php');
		$this->assertSame('app', $return);

		$return = $this->Shell->mapFileToCase(APP . 'Test/Fixture/SomeTest.php', false, false);
		$this->assertFalse($return);
	}

/**
 * test available list of test cases for an empty category
 *
 * @return void
 */
	public function testAvailableWithEmptyList() {
		$this->Shell->startup();
		$this->Shell->args = array('unexistant-category');
		$this->Shell->expects($this->at(0))->method('out')->with(__d('cake_console', "No test cases available \n\n"));
		$this->Shell->OptionParser->expects($this->once())->method('help');
		$this->Shell->available();
	}

/**
 * test available list of test cases for core category
 *
 * @return void
 */
	public function testAvailableCoreCategory() {
		$this->Shell->startup();
		$this->Shell->args = array('core');
		$this->Shell->expects($this->at(0))->method('out')->with('Core Test Cases:');
		$this->Shell->expects($this->at(1))->method('out')
			->with($this->stringContains('[1]'));
		$this->Shell->expects($this->at(2))->method('out')
			->with($this->stringContains('[2]'));

		$this->Shell->expects($this->once())->method('in')
			->with(__d('cake_console', 'What test case would you like to run?'), null, 'q')
			->will($this->returnValue('1'));

		$this->Shell->expects($this->once())->method('_run');
		$this->Shell->available();
		$this->assertEquals(array('core', 'AllBehaviors'), $this->Shell->args);
	}

/**
 * Tests that correct option for test runner are passed
 *
 * @return void
 */
	public function testRunnerOptions() {
		$this->Shell->startup();
		$this->Shell->args = array('core', 'Basics');
		$this->Shell->params = array('filter' => 'myFilter', 'colors' => true, 'verbose' => true);

		$this->Shell->expects($this->once())->method('_run')
			->with(
				array('app' => false, 'plugin' => null, 'core' => true, 'output' => 'text', 'case' => 'Basics'),
				array('--filter', 'myFilter', '--colors', '--verbose')
			);
		$this->Shell->main();
	}
}
