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
use Cake\View\Widget\Checkbox;

/**
 * Checkbox test case
 */
class CheckboxTest extends TestCase {

/**
 * setup method.
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$templates = [
			'checkbox' => '<input type="checkbox" name="{{name}}" value="{{value}}"{{attrs}}>',
		];
		$this->templates = new StringTemplate($templates);
	}

/**
 * Test rendering simple checkboxes.
 *
 * @return void
 */
	public function testRenderSimple() {
		$checkbox = new Checkbox($this->templates);
		$data = [
			'name' => 'Comment[spam]',
		];
		$result = $checkbox->render($data);
		$expected = [
			'input' => [
				'type' => 'checkbox',
				'name' => 'Comment[spam]',
				'value' => 1,
			]
		];
		$this->assertTags($result, $expected);

		$data = [
			'name' => 'Comment[spam]',
			'value' => 99,
		];
		$result = $checkbox->render($data);
		$expected = [
			'input' => [
				'type' => 'checkbox',
				'name' => 'Comment[spam]',
				'value' => 99,
			]
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test rendering disabled checkboxes.
 *
 * @return void
 */
	public function testRenderDisabled() {
		$checkbox = new Checkbox($this->templates);
		$data = [
			'name' => 'Comment[spam]',
			'disabled' => true,
		];
		$result = $checkbox->render($data);
		$expected = [
			'input' => [
				'type' => 'checkbox',
				'name' => 'Comment[spam]',
				'value' => 1,
				'disabled' => 'disabled',
			]
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test rendering checked checkboxes.
 *
 * @return void
 */
	public function testRenderChecked() {
		$checkbox = new Checkbox($this->templates);
		$data = [
			'name' => 'Comment[spam]',
			'value' => 1,
			'checked' => 1,
		];
		$result = $checkbox->render($data);
		$expected = [
			'input' => [
				'type' => 'checkbox',
				'name' => 'Comment[spam]',
				'value' => 1,
				'checked' => 'checked',
			]
		];
		$this->assertTags($result, $expected);

		$data = [
			'name' => 'Comment[spam]',
			'value' => 1,
			'val' => 1,
		];
		$result = $checkbox->render($data);
		$this->assertTags($result, $expected);

		$data['val'] = '1';
		$result = $checkbox->render($data);
		$this->assertTags($result, $expected);

		$data = [
			'name' => 'Comment[spam]',
			'value' => 1,
			'val' => '1x',
		];
		$result = $checkbox->render($data);
		$expected = [
			'input' => [
				'type' => 'checkbox',
				'name' => 'Comment[spam]',
				'value' => 1,
			]
		];
		$this->assertTags($result, $expected);
	}

/**
 * Data provider for checkbox values
 *
 * @return array
 */
	public static function checkedProvider() {
		return [
			['checked'],
			['1'],
			[1],
			[true],
		];
	}

/**
 * Test rendering checked checkboxes with value.
 *
 * @dataProvider checkedProvider
 * @return void
 */
	public function testRenderCheckedValue($checked) {
		$checkbox = new Checkbox($this->templates);
		$data = [
			'name' => 'Comment[spam]',
			'value' => 1,
			'checked' => $checked,
		];
		$result = $checkbox->render($data);
		$expected = [
			'input' => [
				'type' => 'checkbox',
				'name' => 'Comment[spam]',
				'value' => 1,
				'checked' => 'checked',
			]
		];
		$this->assertTags($result, $expected);
	}

}
