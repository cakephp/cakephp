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
 * @since         CakePHP(tm) v3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Input;

use Cake\TestSuite\TestCase;
use Cake\View\Input\InputRegistry;
use Cake\View\StringTemplate;

/**
 * InputRegistry test case
 */
class InputRegistryTestCase extends TestCase {

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
			'text' => ['Cake\View\Input\Basic'],
		];
		$inputs = new InputRegistry($this->templates, $widgets);
		$result = $inputs->get('text');
		$this->assertInstanceOf('Cake\View\Input\Basic', $result);
	}

/**
 * Test adding new widgets.
 *
 * @return void
 */
	public function testAdd() {
		$inputs = new InputRegistry($this->templates);
		$result = $inputs->add([
			'text' => ['Cake\View\Input\Basic'],
		]);
		$this->assertNull($result);
	}

/**
 * Test getting registered widgets.
 *
 * @return void
 */
	public function testGet() {
		$inputs = new InputRegistry($this->templates);
		$inputs->add([
			'text' => ['Cake\View\Input\Basic'],
		]);
		$result = $inputs->get('text');
		$this->assertInstanceOf('Cake\View\Input\Basic', $result);
		$this->assertSame($result, $inputs->get('text'));
	}

/**
 * Test getting fallback widgets.
 *
 * @return void
 */
	public function testGetFallback() {
		$inputs = new InputRegistry($this->templates);
		$inputs->add([
			'_default' => ['Cake\View\Input\Basic'],
		]);
		$result = $inputs->get('text');
		$this->assertInstanceOf('Cake\View\Input\Basic', $result);

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
		$inputs = new InputRegistry($this->templates);
		$inputs->clear();
		$inputs->get('foo');
	}

/**
 * Test getting resolve dependency
 *
 * @return void
 */
	public function testGetResolveDependency() {
		$inputs = new InputRegistry($this->templates);
		$inputs->clear();
		$inputs->add([
			'label' => ['Cake\View\Input\Label'],
			'multicheckbox' => ['Cake\View\Input\MultiCheckbox', 'label']
		]);
		$result = $inputs->get('multicheckbox');
		$this->assertInstanceOf('Cake\View\Input\MultiCheckbox', $result);
	}

/**
 * Test getting resolve dependency missing class
 *
 * @expectedException RuntimeException
 * @expectedExceptionMessage Unable to locate widget class "TestApp\View\Derp"
 * @return void
 */
	public function testGetResolveDependencyMissingClass() {
		$inputs = new InputRegistry($this->templates);
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
		$inputs = new InputRegistry($this->templates);
		$inputs->clear();
		$inputs->add(['multicheckbox' => ['Cake\View\Input\MultiCheckbox', 'label']]);
		$inputs->get('multicheckbox');
	}

}
