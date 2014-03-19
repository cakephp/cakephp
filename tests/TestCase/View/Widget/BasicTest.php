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
use Cake\View\Widget\Basic;

/**
 * Basic input test.
 */
class BasicTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$templates = [
			'input' => '<input type="{{type}}" name="{{name}}"{{attrs}}>',
		];
		$this->templates = new StringTemplate($templates);
	}

/**
 * Test render in a simple case.
 *
 * @return void
 */
	public function testRenderSimple() {
		$text = new Basic($this->templates);
		$result = $text->render(['name' => 'my_input']);
		$expected = [
			'input' => ['type' => 'text', 'name' => 'my_input']
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test render with custom type
 *
 * @return void
 */
	public function testRenderType() {
		$text = new Basic($this->templates);
		$data = [
			'name' => 'my_input',
			'type' => 'email',
		];
		$result = $text->render($data);
		$expected = [
			'input' => ['type' => 'email', 'name' => 'my_input']
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test render with a value
 *
 * @return void
 */
	public function testRenderWithValue() {
		$text = new Basic($this->templates);
		$data = [
			'name' => 'my_input',
			'type' => 'email',
			'val' => 'Some <value>'
		];
		$result = $text->render($data);
		$expected = [
			'input' => [
				'type' => 'email',
				'name' => 'my_input',
				'value' => 'Some &lt;value&gt;'
			]
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test render with additional attributes.
 *
 * @return void
 */
	public function testRenderAttributes() {
		$text = new Basic($this->templates);
		$data = [
			'name' => 'my_input',
			'type' => 'email',
			'class' => 'form-control',
			'required' => true
		];
		$result = $text->render($data);
		$expected = [
			'input' => [
				'type' => 'email',
				'name' => 'my_input',
				'class' => 'form-control',
				'required' => 'required',
			]
		];
		$this->assertTags($result, $expected);
	}

}
