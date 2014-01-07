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
use Cake\View\Input\Context;
use Cake\View\Input\SelectBox;
use Cake\View\StringTemplate;

/**
 * SelectBox test case
 */
class SelectBoxTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$templates = [
			'select' => '<select name="{{name}}" {{attrs}}>{{content}}</select>',
			'selectMultiple' => '<select name="{{name}}" multiple="multiple" {{attrs}}>{{content}}</select>',
			'option' => '<option value="{{name}}">{{value}}</option>',
			'optionSelected' => '<option value="{{name}}" selected="selected">{{value}}</option>',
			'optgroup' => '<optgroup label="{{label}}">{{content}}</optgroup>',
		];
		$this->templates = new StringTemplate();
		$this->templates->add($templates);
	}

/**
 * test render no options
 *
 * @return void
 */
	public function testRenderNoOptions() {
		$context = new Context();
		$select = new SelectBox($this->templates, $context);
		$data = [
			'id' => 'BirdName',
			'name' => 'Birds[name]',
			'options' => []
		];
		$result = $select->render($data);
		$expected = [
			'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
			'/select'
		];
		$this->assertTags($result, $expected);
	}

/**
 * test simple rendering
 *
 * @return void
 */
	public function testRenderSimple() {
		$context = new Context();
		$select = new SelectBox($this->templates, $context);
		$data = [
			'id' => 'BirdName',
			'name' => 'Birds[name]',
			'options' => ['a' => 'Albatross', 'b' => 'Budgie']
		];
		$result = $select->render($data);
		$expected = [
			'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
			['option' => ['value' => 'a']], 'Albatross', '/option',
			['option' => ['value' => 'b']], 'Budgie', '/option',
			'/select'
		];
		$this->assertTags($result, $expected);
	}

/**
 * test rendering with a selected value
 *
 * @return void
 */
	public function testRenderSelected() {
		$context = new Context();
		$select = new SelectBox($this->templates, $context);
		$data = [
			'id' => 'BirdName',
			'name' => 'Birds[name]',
			'value' => '1',
			'options' => [
				1 => 'one',
				'1x' => 'one x',
				'2' => 'two',
				'2x' => 'two x',
			]
		];
		$result = $select->render($data);
		$expected = [
			'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
			['option' => ['value' => '1', 'selected' => 'selected']], 'one', '/option',
			['option' => ['value' => '1x']], 'one x', '/option',
			['option' => ['value' => '2']], 'two', '/option',
			['option' => ['value' => '2x']], 'two x', '/option',
			'/select'
		];
		$this->assertTags($result, $expected);

		$data['value'] = 2;
		$result = $select->render($data);
		$expected = [
			'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
			['option' => ['value' => '1']], 'one', '/option',
			['option' => ['value' => '1x']], 'one x', '/option',
			['option' => ['value' => '2', 'selected' => 'selected']], 'two', '/option',
			['option' => ['value' => '2x']], 'two x', '/option',
			'/select'
		];
		$this->assertTags($result, $expected);
	}

/**
 * test rendering a multi select
 *
 * @return void
 */
	public function testRenderMultipleSelect() {
		$this->markTestIncomplete('Not done');
	}

/**
 * test rendering multi select & selected values
 *
 * @return void
 */
	public function testRenderMultipleSelected() {
		$this->markTestIncomplete('Not done');
	}

/**
 * test rendering with option groups
 *
 * @return void
 */
	public function testRenderOptionGroups() {
		$this->markTestIncomplete('Not done');
	}

/**
 * test rendering option groups and selected values
 *
 * @return void
 */
	public function testRenderOptionGroupsSelected() {
		$this->markTestIncomplete('Not done');
	}

/**
 * test rendering a disabled element
 *
 * @return void
 */
	public function testRenderDisabled() {
		$this->markTestIncomplete('Not done');
	}

/**
 * test rendering with an empty value
 *
 * @return void
 */
	public function testRenderEmptyOption() {
		$select = new SelectBox($this->templates, $context);
		$data = [
			'id' => 'BirdName',
			'name' => 'Birds[name]',
			'empty' => true,
			'options' => ['a' => 'Albatross', 'b' => 'Budgie']
		];
		$result = $select->render($data);
		$expected = [
			'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
			['option' => ['value' => '']], '/option',
			['option' => ['value' => 'a']], 'Albatross', '/option',
			['option' => ['value' => 'b']], 'Budgie', '/option',
			'/select'
		];
		$this->assertTags($result, $expected);

		$data['empty'] = 'empty';
		$result = $select->render($data);
		$expected = [
			'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
			['option' => ['value' => '']], 'empty', '/option',
			['option' => ['value' => 'a']], 'Albatross', '/option',
			['option' => ['value' => 'b']], 'Budgie', '/option',
			'/select'
		];
		$this->assertTags($result, $expected);

		$data['empty'] = 'empty';
		$data['value'] = '';
		$result = $select->render($data);
		$expected = [
			'select' => ['name' => 'Birds[name]', 'id' => 'BirdName'],
			['option' => ['value' => '', 'selected' => 'selected']], 'empty', '/option',
			['option' => ['value' => 'a']], 'Albatross', '/option',
			['option' => ['value' => 'b']], 'Budgie', '/option',
			'/select'
		];
		$this->assertTags($result, $expected);
	}

}
