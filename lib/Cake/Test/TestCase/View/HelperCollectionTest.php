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
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 2.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\View;

use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\HelperCollection;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;

/**
 * Extended HtmlHelper
 */
class HtmlAliasHelper extends HtmlHelper {
}

class HelperCollectionTest extends TestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->View = $this->getMock('Cake\View\View', array(), array(null));
		$this->Helpers = new HelperCollection($this->View);
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		Plugin::unload();
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
		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $result);
		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $this->Helpers->Html);

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Html'), $result, 'attached() results are wrong.');

		$this->assertTrue($this->Helpers->enabled('Html'));
	}

/**
 * test lazy loading of helpers
 *
 * @return void
 */
	public function testLazyLoad() {
		$result = $this->Helpers->Html;
		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $result);

		$result = $this->Helpers->Form;
		$this->assertInstanceOf('Cake\View\Helper\FormHelper', $result);

		App::build(array('Plugin' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS)));
		$this->View->plugin = 'TestPlugin';
		Plugin::load(array('TestPlugin'));
		$result = $this->Helpers->OtherHelper;
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $result);
	}

/**
 * test lazy loading of helpers
 *
 * @expectedException Cake\Error\MissingHelperException
 * @return void
 */
	public function testLazyLoadException() {
		$result = $this->Helpers->NotAHelper;
	}

/**
 * Tests loading as an alias
 *
 * @return void
 */
	public function testLoadWithAlias() {
		$result = $this->Helpers->load('Html', array('className' => __NAMESPACE__ . '\HtmlAliasHelper'));
		$this->assertInstanceOf(__NAMESPACE__ . '\HtmlAliasHelper', $result);
		$this->assertInstanceOf(__NAMESPACE__ . '\HtmlAliasHelper', $this->Helpers->Html);

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Html'), $result, 'attached() results are wrong.');

		$this->assertTrue($this->Helpers->enabled('Html'));

		$result = $this->Helpers->load('Html');
		$this->assertInstanceOf(__NAMESPACE__ . '\HtmlAliasHelper', $result);

		App::build(array('Plugin' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS)));
		Plugin::load(array('TestPlugin'));
		$result = $this->Helpers->load('SomeOther', array('className' => 'TestPlugin.OtherHelper'));
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $result);
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $this->Helpers->SomeOther);

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
		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $result);
		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $this->Helpers->Html);

		$this->assertFalse($this->Helpers->enabled('Html'), 'Html should be disabled');
	}

/**
 * test missinghelper exception
 *
 * @expectedException Cake\Error\MissingHelperException
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
			'Plugin' => array(CAKE . 'Test' . DS . 'TestApp' . DS . 'Plugin' . DS),
		));
		Plugin::load(array('TestPlugin'));

		$result = $this->Helpers->load('TestPlugin.OtherHelper');
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $result, 'Helper class is wrong.');
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $this->Helpers->OtherHelper, 'Class is wrong');

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
		$this->assertNotContains('Html', $this->Helpers->attached());
		$this->assertContains('Form', $this->Helpers->attached());

		$result = $this->Helpers->attached();
		$this->assertEquals(array('Form'), $result, 'loaded helpers is wrong');
	}

}
