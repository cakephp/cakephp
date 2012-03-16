<?php
/**
 * HelperCollectionTest file
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
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('HelperCollection', 'View');
App::uses('HtmlHelper', 'View/Helper');
App::uses('View', 'View');

/**
 * Extended HtmlHelper
 */
class HtmlAliasHelper extends HtmlHelper {
}

class HelperCollectionTest extends CakeTestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->View = $this->getMock('View', array(), array(null));
		$this->Helpers = new HelperCollection($this->View);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		CakePlugin::unload();
		unset($this->Helpers, $this->View);
		parent::tearDown();
	}

/**
 * test triggering callbacks on loaded helpers
 *
 * @return void
 */
	public function testLoad() {
		$result = $this->Helpers->load('Html');
		$this->assertInstanceOf('HtmlHelper', $result);
		$this->assertInstanceOf('HtmlHelper', $this->Helpers->Html);

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Html'), $result, 'attached() results are wrong.');

		$this->assertTrue($this->Helpers->enabled('Html'));
	}

/**
 * Tests loading as an alias
 *
 * @return void
 */
	public function testLoadWithAlias() {
		$result = $this->Helpers->load('Html', array('className' => 'HtmlAlias'));
		$this->assertInstanceOf('HtmlAliasHelper', $result);
		$this->assertInstanceOf('HtmlAliasHelper', $this->Helpers->Html);

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Html'), $result, 'attached() results are wrong.');

		$this->assertTrue($this->Helpers->enabled('Html'));

		$result = $this->Helpers->load('Html');
		$this->assertInstanceOf('HtmlAliasHelper', $result);

		App::build(array('Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS)));
		CakePlugin::load(array('TestPlugin'));
		$result = $this->Helpers->load('SomeOther', array('className' => 'TestPlugin.OtherHelper'));
		$this->assertInstanceOf('OtherHelperHelper', $result);
		$this->assertInstanceOf('OtherHelperHelper', $this->Helpers->SomeOther);

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Html', 'SomeOther'), $result, 'attached() results are wrong.');
		App::build();
	}

/**
 * test that the enabled setting disables the helper.
 *
 * @return void
 */
	public function testLoadWithEnabledFalse() {
		$result = $this->Helpers->load('Html', array('enabled' => false));
		$this->assertInstanceOf('HtmlHelper', $result);
		$this->assertInstanceOf('HtmlHelper', $this->Helpers->Html);

		$this->assertFalse($this->Helpers->enabled('Html'), 'Html should be disabled');
	}

/**
 * test missinghelper exception
 *
 * @expectedException MissingHelperException
 * @return void
 */
	public function testLoadMissingHelper() {
		$result = $this->Helpers->load('ThisHelperShouldAlwaysBeMissing');
	}

/**
 * test loading a plugin helper.
 *
 * @return void
 */
	public function testLoadPluginHelper() {
		App::build(array(
			'Plugin' => array(CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS),
		));
		CakePlugin::load(array('TestPlugin'));
		$result = $this->Helpers->load('TestPlugin.OtherHelper');
		$this->assertInstanceOf('OtherHelperHelper', $result, 'Helper class is wrong.');
		$this->assertInstanceOf('OtherHelperHelper', $this->Helpers->OtherHelper, 'Class is wrong');

		App::build();
	}

/**
 * test unload()
 *
 * @return void
 */
	public function testUnload() {
		$this->Helpers->load('Form');
		$this->Helpers->load('Html');

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Form', 'Html'), $result, 'loaded helpers is wrong');

		$this->Helpers->unload('Html');
		$this->assertFalse(isset($this->Helpers->Html));
		$this->assertTrue(isset($this->Helpers->Form));

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Form'), $result, 'loaded helpers is wrong');
	}

}
