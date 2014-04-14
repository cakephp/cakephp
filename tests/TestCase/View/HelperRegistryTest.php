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
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View;

use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\TestSuite\TestCase;
use Cake\View\Helper;
use Cake\View\HelperRegistry;
use Cake\View\View;

/**
 * Extended HtmlHelper
 */
class HtmlAliasHelper extends Helper {

	public function afterRender($viewFile) {
	}

}

/**
 * Class HelperRegistryTest
 *
 */
class HelperRegistryTest extends TestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->View = new View();
		$this->Events = $this->View->getEventManager();
		$this->Helpers = new HelperRegistry($this->View);
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
 * test loading helpers.
 *
 * @return void
 */
	public function testLoad() {
		$result = $this->Helpers->load('Html');
		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $result);
		$this->assertInstanceOf('Cake\View\Helper\HtmlHelper', $this->Helpers->Html);

		$result = $this->Helpers->loaded();
		$this->assertEquals(array('Html'), $result, 'loaded() results are wrong.');
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

		$this->View->plugin = 'TestPlugin';
		Plugin::load(array('TestPlugin'));
		$result = $this->Helpers->OtherHelper;
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $result);
	}

/**
 * test lazy loading of helpers
 *
 * @expectedException \Cake\View\Error\MissingHelperException
 * @return void
 */
	public function testLazyLoadException() {
		$this->Helpers->NotAHelper;
	}

/**
 * Test that loading helpers subscribes to events.
 *
 * @return void
 */
	public function testLoadSubscribeEvents() {
		$this->Helpers->load('Html', array('className' => __NAMESPACE__ . '\HtmlAliasHelper'));
		$result = $this->Events->listeners('View.afterRender');
		$this->assertCount(1, $result);
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

		$result = $this->Helpers->loaded();
		$this->assertEquals(array('Html'), $result, 'loaded() results are wrong.');

		$result = $this->Helpers->load('Html');
		$this->assertInstanceOf(__NAMESPACE__ . '\HtmlAliasHelper', $result);
	}

/**
 * Test loading helpers with aliases and plugins.
 *
 * @return void
 */
	public function testLoadWithAliasAndPlugin() {
		Plugin::load('TestPlugin');
		$result = $this->Helpers->load('SomeOther', array('className' => 'TestPlugin.OtherHelper'));
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $result);
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $this->Helpers->SomeOther);

		$result = $this->Helpers->loaded();
		$this->assertEquals(['SomeOther'], $result, 'loaded() results are wrong.');
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

		$this->assertEmpty($this->Events->listeners('View.beforeRender'));
	}

/**
 * test missinghelper exception
 *
 * @expectedException \Cake\View\Error\MissingHelperException
 * @return void
 */
	public function testLoadMissingHelper() {
		$this->Helpers->load('ThisHelperShouldAlwaysBeMissing');
	}

/**
 * test loading a plugin helper.
 *
 * @return void
 */
	public function testLoadPluginHelper() {
		Plugin::load(array('TestPlugin'));

		$result = $this->Helpers->load('TestPlugin.OtherHelper');
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $result, 'Helper class is wrong.');
		$this->assertInstanceOf('TestPlugin\View\Helper\OtherHelperHelper', $this->Helpers->OtherHelper, 'Class is wrong');
	}

/**
 * Test reset.
 *
 * @return void
 */
	public function testReset() {
		$instance = $this->Helpers->load('Paginator');
		$this->assertSame(
			$instance,
			$this->Helpers->Paginator,
			'Instance in registry should be the same as previously loaded'
		);
		$this->assertCount(1, $this->Events->listeners('View.beforeRender'));

		$this->assertNull($this->Helpers->reset(), 'No return expected');
		$this->assertCount(0, $this->Events->listeners('View.beforeRender'));

		$this->assertNotSame($instance, $this->Helpers->load('Paginator'));
	}

/**
 * Test unloading.
 *
 * @return void
 */
	public function testUnload() {
		$instance = $this->Helpers->load('Paginator');
		$this->assertSame(
			$instance,
			$this->Helpers->Paginator,
			'Instance in registry should be the same as previously loaded'
		);
		$this->assertCount(1, $this->Events->listeners('View.beforeRender'));

		$this->assertNull($this->Helpers->unload('Paginator'), 'No return expected');
		$this->assertCount(0, $this->Events->listeners('View.beforeRender'));
	}
}
