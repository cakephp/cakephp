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

use Cake\Collection\Collection;
use Cake\TestSuite\TestCase;
use Cake\View\Input\Radio;
use Cake\View\StringTemplate;

/**
 * Radio test case
 */
class RadioTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$templates = [
			'radio' => '<input type="radio" name="{{name}}" value="{{value}}"{{attrs}}>',
			'label' => '<label{{attrs}}>{{text}}</label>',
			'radioContainer' => '{{input}}{{label}}',
		];
		$this->templates = new StringTemplate();
		$this->templates->add($templates);
	}

/**
 * Test rendering basic radio buttons.
 *
 * @return void
 */
	public function testRenderSimple() {
		$radio = new Radio($this->templates);
		$data = [
			'name' => 'Crayons[color]',
			'options' => ['r' => 'Red', 'b' => 'Black']
		];
		$result = $radio->render($data);
		$expected = [
			['input' => [
				'type' => 'radio',
				'name' => 'Crayons[color]',
				'value' => 'r',
				'id' => 'Crayons_color_r'
			]],
			['label' => ['for' => 'Crayons_color_r']],
			'Red',
			'/label',
			['input' => [
				'type' => 'radio',
				'name' => 'Crayons[color]',
				'value' => 'b',
				'id' => 'Crayons_color_b'
			]],
			['label' => ['for' => 'Crayons_color_b']],
			'Black',
			'/label',
		];
		$this->assertTags($result, $expected);

		$data = [
			'name' => 'Crayons[color]',
			'options' => new Collection(['r' => 'Red', 'b' => 'Black'])
		];
		$result = $radio->render($data);
		$this->assertTags($result, $expected);
	}

/**
 * Test rendering inputs with the complex option form.
 *
 * @return void
 */
	public function testRenderComplex() {
		$radio = new Radio($this->templates);
		$data = [
			'name' => 'Crayons[color]',
			'options' => [
				['value' => 'r', 'text' => 'Red', 'id' => 'my_id'],
				['value' => 'b', 'text' => 'Black', 'id' => 'my_id_2', 'data-test' => 'test'],
			]
		];
		$result = $radio->render($data);
		$expected = [
			['input' => [
				'type' => 'radio',
				'name' => 'Crayons[color]',
				'value' => 'r',
				'id' => 'my_id'
			]],
			['label' => ['for' => 'my_id']],
			'Red',
			'/label',
			['input' => [
				'type' => 'radio',
				'name' => 'Crayons[color]',
				'value' => 'b',
				'id' => 'my_id_2',
				'data-test' => 'test'
			]],
			['label' => ['for' => 'my_id_2']],
			'Black',
			'/label',
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test rendering the empty option.
 *
 * @return void
 */
	public function testRenderEmptyOption() {
		$radio = new Radio($this->templates);
		$data = [
			'name' => 'Crayons[color]',
			'options' => ['r' => 'Red'],
			'empty' => true,
		];
		$result = $radio->render($data);
		$expected = [
			['input' => [
				'type' => 'radio',
				'name' => 'Crayons[color]',
				'value' => '',
				'id' => 'Crayons_color'
			]],
			['label' => ['for' => 'Crayons_color']],
			'empty',
			'/label',
			['input' => [
				'type' => 'radio',
				'name' => 'Crayons[color]',
				'value' => 'r',
				'id' => 'Crayons_color_r'
			]],
			['label' => ['for' => 'Crayons_color_r']],
			'Red',
			'/label',
		];
		$this->assertTags($result, $expected);

		$data['empty'] = 'Choose one';
		$result = $radio->render($data);
		$expected = [
			['input' => [
				'type' => 'radio',
				'name' => 'Crayons[color]',
				'value' => '',
				'id' => 'Crayons_color'
			]],
			['label' => ['for' => 'Crayons_color']],
			'Choose one',
			'/label',
			['input' => [
				'type' => 'radio',
				'name' => 'Crayons[color]',
				'value' => 'r',
				'id' => 'Crayons_color_r'
			]],
			['label' => ['for' => 'Crayons_color_r']],
			'Red',
			'/label',
		];
		$this->assertTags($result, $expected);
	}

/**
 * test render() and selected inputs.
 *
 * @return void
 */
	public function testRenderSelected() {
		$radio = new Radio($this->templates);
		$data = [
			'name' => 'Versions[ver]',
			'value' => '1',
			'options' => [
				1 => 'one',
				'1x' => 'one x',
				'2' => 'two',
			]
		];
		$result = $radio->render($data);
		$expected = [
			['input' => [
				'id' => 'Versions_ver_1',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '1',
				'checked' => 'checked'
			]],
			['label' => ['for' => 'Versions_ver_1']],
			'one',
			'/label',
			['input' => [
				'id' => 'Versions_ver_1x',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '1x'
			]],
			['label' => ['for' => 'Versions_ver_1x']],
			'one x',
			'/label',
			['input' => [
				'id' => 'Versions_ver_2',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '2'
			]],
			['label' => ['for' => 'Versions_ver_2']],
			'two',
			'/label',
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test rendering with disable inputs
 *
 * @return void
 */
	public function testRenderDisabled() {
		$radio = new Radio($this->templates);
		$data = [
			'name' => 'Versions[ver]',
			'options' => [
				1 => 'one',
				'1x' => 'one x',
				'2' => 'two',
			],
			'disabled' => true,
		];
		$result = $radio->render($data);
		$expected = [
			['input' => [
				'id' => 'Versions_ver_1',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '1',
				'disabled' => 'disabled'
			]],
			['label' => ['for' => 'Versions_ver_1']],
			'one',
			'/label',
			['input' => [
				'id' => 'Versions_ver_1x',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '1x',
				'disabled' => 'disabled'
			]],
			['label' => ['for' => 'Versions_ver_1x']],
			'one x',
			'/label',
		];
		$this->assertTags($result, $expected);

		$data['disabled'] = ['1'];
		$result = $radio->render($data);
		$expected = [
			['input' => [
				'id' => 'Versions_ver_1',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '1',
				'disabled' => 'disabled'
			]],
			['label' => ['for' => 'Versions_ver_1']],
			'one',
			'/label',
			['input' => [
				'id' => 'Versions_ver_1x',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '1x',
			]],
			['label' => ['for' => 'Versions_ver_1x']],
			'one x',
			'/label',
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test rendering with label options.
 *
 * @return void
 */
	public function testRenderLabelOptions() {
		$radio = new Radio($this->templates);
		$data = [
			'name' => 'Versions[ver]',
			'options' => [
				1 => 'one',
				'1x' => 'one x',
				'2' => 'two',
			],
			'label' => false,
		];
		$result = $radio->render($data);
		$expected = [
			['input' => [
				'id' => 'Versions_ver_1',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '1',
			]],
			['input' => [
				'id' => 'Versions_ver_1x',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '1x',
			]],
		];
		$this->assertTags($result, $expected);

		$data = [
			'name' => 'Versions[ver]',
			'options' => [
				1 => 'one',
				'1x' => 'one x',
				'2' => 'two',
			],
			'label' => [
				'class' => 'my-class',
			]
		];
		$result = $radio->render($data);
		$expected = [
			['input' => [
				'id' => 'Versions_ver_1',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '1',
			]],
			['label' => ['class' => 'my-class', 'for' => 'Versions_ver_1']],
			'one',
			'/label',
			['input' => [
				'id' => 'Versions_ver_1x',
				'name' => 'Versions[ver]',
				'type' => 'radio',
				'value' => '1x',
			]],
			['label' => ['class' => 'my-class', 'for' => 'Versions_ver_1x']],
			'one x',
			'/label',
		];
		$this->assertTags($result, $expected);
	}

/**
 * Ensure that the input + label are composed with
 * a template.
 *
 * @return void
 */
	public function testRenderContainerTemplate() {
		$this->templates->add([
			'radioContainer' => '<div class="radio">{{input}}{{label}}</div>'
		]);
		$radio = new Radio($this->templates);
		$data = [
			'name' => 'Versions[ver]',
			'options' => [
				1 => 'one',
				'1x' => 'one x',
				'2' => 'two',
			],
		];
		$result = $radio->render($data);
		$this->assertContains(
			'<div class="radio"><input type="radio"',
			$result
		);
		$this->assertContains(
			'</label></div>',
			$result
		);
	}

}
