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
	}

	public function testRenderComplex() {
		$this->markTestIncomplete();
	}

	public function testRenderSelected() {
		$this->markTestIncomplete();
	}

	public function testRenderDisabled() {
		$this->markTestIncomplete();
	}

	public function testRenderLabelOptions() {
		$this->markTestIncomplete();
	}

	public function testRenderContainerTemplate() {
		$this->markTestIncomplete();
	}

}
