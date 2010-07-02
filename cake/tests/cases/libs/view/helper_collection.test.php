<?php
/**
 * HelperCollectionTest file
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 2.0
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */

App::import('Core', 'HelperCollection');
App::import('View', 'View');

class HelperCollectionTest extends CakeTestCase {
/**
 * setup
 *
 * @return void
 */
	function setup() {
		$View = $this->getMock('View', array(), array(null));
		$this->Helpers = new HelperCollection($View);
	}

/**
 * teardown
 *
 * @return void
 */
	function teardown() {
		unset($this->Helpers);
	}

/**
 * test triggering callbacks on loaded helpers
 *
 * @return void
 */
	function testLoad() {
		$result = $this->Helpers->load('Html');
		$this->assertType('HtmlHelper', $result);
		$this->assertType('HtmlHelper', $this->Helpers->Html);

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Html'), $result, 'attached() results are wrong.');
		
		$this->assertTrue($this->Helpers->enabled('Html'));
	}

/**
 * test missinghelper exception
 *
 * @expectedException MissingHelperFileException
 * @return void
 */
	function testLoadMissingHelperFile() {
		$result = $this->Helpers->load('ThisHelperShouldAlwaysBeMissing');
	}

/**
 * test loading a plugin helper.
 *
 * @return void
 */
	function testLoadPluginHelper() {
		App::build(array(
			'plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS),
		));
		$result = $this->Helpers->load('TestPlugin.OtherHelper');
		$this->assertType('OtherHelperHelper', $result, 'Helper class is wrong.');
		$this->assertType('OtherHelperHelper', $this->Helpers->OtherHelper, 'Class is wrong');

		App::build();
	}
}