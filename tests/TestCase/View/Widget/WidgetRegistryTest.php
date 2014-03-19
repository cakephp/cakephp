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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Widget;

use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;
use Cake\View\Widget\WidgetRegistry;

/**
 * WidgetRegistry test case
 */
class WidgetRegistryTestCase extends TestCase {

/**
 * setup method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->templates = new StringTemplate();
	}

/**
 * Test adding new widgets.
 *
 * @return void
 */
	public function testAddInConstructor() {
		$widgets = [
			'text' => ['Cake\View\Widget\Basic'],
		];
		$inputs = new WidgetRegistry($this->templates, $widgets);
		$result = $inputs->get('text');
		$this->assertInstanceOf('Cake\View\Widget\Basic', $result);
	}

/**
 * Test adding new widgets.
 *
 * @return void
 */
	public function testAdd() {
		$inputs = new WidgetRegistry($this->templates);
		$result = $inputs->add([
			'text' => ['Cake\View\Widget\Basic'],
		]);
		$this->assertNull($result);
		$result = $inputs->get('text');
		$this->assertInstanceOf('Cake\View\Widget\WidgetInterface', $result);

		$inputs = new WidgetRegistry($this->templates);
		$result = $inputs->add([
			'hidden' => 'Cake\View\Widget\Basic',
		]);
		$this->assertNull($result);
		$result = $inputs->get('hidden');
		$this->assertInstanceOf('Cake\View\Widget\WidgetInterface', $result);
	}

/**
 * Test adding an instance of an invalid type.
 *
 * @expectedException \RuntimeException
 * @expectedExceptionMessage Input objects must implement Cake\View\Widget\WidgetInterface
 * @return void
 */
	public function testAddInvalidType() {
		$inputs = new WidgetRegistry($this->templates);
		$inputs->add([
			'text' => new \StdClass()
		]);
		$inputs->get('text');
	}

/**
 * Test getting registered widgets.
 *
 * @return void
 */
	public function testGet() {
		$inputs = new WidgetRegistry($this->templates);
		$inputs->add([
			'text' => ['Cake\View\Widget\Basic'],
		]);
		$result = $inputs->get('text');
		$this->assertInstanceOf('Cake\View\Widget\Basic', $result);
		$this->assertSame($result, $inputs->get('text'));
	}

/**
 * Test getting fallback widgets.
 *
 * @return void
 */
	public function testGetFallback() {
		$inputs = new WidgetRegistry($this->templates);
		$inputs->add([
			'_default' => ['Cake\View\Widget\Basic'],
		]);
		$result = $inputs->get('text');
		$this->assertInstanceOf('Cake\View\Widget\Basic', $result);

		$result2 = $inputs->get('hidden');
		$this->assertSame($result, $result2);
	}

/**
 * Test getting errors
 *
 * @expectedException RuntimeException
 * @expectedExceptionMessage Unknown widget "foo"
 * @return void
 */
	public function testGetNoFallbackError() {
		$inputs = new WidgetRegistry($this->templates);
		$inputs->clear();
		$inputs->get('foo');
	}

/**
 * Test getting resolve dependency
 *
 * @return void
 */
	public function testGetResolveDependency() {
		$inputs = new WidgetRegistry($this->templates);
		$inputs->clear();
		$inputs->add([
			'label' => ['Cake\View\Widget\Label'],
			'multicheckbox' => ['Cake\View\Widget\MultiCheckbox', 'label']
		]);
		$result = $inputs->get('multicheckbox');
		$this->assertInstanceOf('Cake\View\Widget\MultiCheckbox', $result);
	}

/**
 * Test getting resolve dependency missing class
 *
 * @expectedException RuntimeException
 * @expectedExceptionMessage Unable to locate widget class "TestApp\View\Derp"
 * @return void
 */
	public function testGetResolveDependencyMissingClass() {
		$inputs = new WidgetRegistry($this->templates);
		$inputs->add(['test' => ['TestApp\View\Derp']]);
		$inputs->get('test');
	}

/**
 * Test getting resolve dependency missing dependency
 *
 * @expectedException RuntimeException
 * @expectedExceptionMessage Unknown widget "label"
 * @return void
 */
	public function testGetResolveDependencyMissingDependency() {
		$inputs = new WidgetRegistry($this->templates);
		$inputs->clear();
		$inputs->add(['multicheckbox' => ['Cake\View\Widget\MultiCheckbox', 'label']]);
		$inputs->get('multicheckbox');
	}

}
