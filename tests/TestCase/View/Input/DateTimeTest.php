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
use Cake\View\Input\SelectBox;
use Cake\View\StringTemplate;

class DateTime extends \Cake\View\Input\DateTime {

	public function generateNumbers($start = 1, $end = 31, $options = []) {
		return parent::_generateNumbers($start, $end, $options);
	}

}

/**
 * DateTime input test case
 */
class DateTimeTest extends TestCase {

/**
 * @setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$templates = [
			'select' => '<select name="{{name}}"{{attrs}}>{{content}}</select>',
			'selectMultiple' => '<select name="{{name}}[]" multiple="multiple"{{attrs}}>{{content}}</select>',
			'option' => '<option value="{{value}}"{{attrs}}>{{text}}</option>',
			'optgroup' => '<optgroup label="{{label}}"{{attrs}}>{{content}}</optgroup>',
			'dateWidget' => '{{year}}-{{month}}-{{day}} {{hour}}:{{minute}}:{{second}}'
		];
		$this->templates = new StringTemplate($templates);
		$this->selectBox = new SelectBox($this->templates);
		$this->DateTime = new DateTime($this->templates, $this->selectBox);
	}

/**
 * Data provider for testing various acceptable selected values.
 *
 * @return array
 */
	public static function selectedValuesProvider() {
		$date = new \DateTime('2014-01-20 12:30:45');
		return [
			'DateTime' => [$date],
			'string' => [$date->format('Y-m-d H:i:s')],
			'int' => [$date->getTimestamp()],
			'array' => [[
				'year' => '2014', 'month' => '01', 'day' => '20',
				'hour' => '12', 'minute' => '30', 'second' => '45',
			]]
		];
	}

/**
 * test rendering selected values.
 *
 * @dataProvider selectedValuesProvider
 * @return void
 */
	public function testRenderSelected($selected) {
		$result = $this->DateTime->render(['val' => $selected]);
		$this->assertContains('<option value="2014" selected="selected">2014</option>', $result);
		$this->assertContains('<option value="01" selected="selected">01</option>', $result);
		$this->assertContains('<option value="20" selected="selected">20</option>', $result);
		$this->assertContains('<option value="12" selected="selected">12</option>', $result);
		$this->assertContains('<option value="30" selected="selected">30</option>', $result);
		$this->assertContains('<option value="45" selected="selected">45</option>', $result);
	}

/**
 * Test rendering widgets with empty values.
 *
 * @retun void
 */
	public function testRenderEmptyValues() {
		$result = $this->DateTime->render([
			'year' => ['empty' => 'YEAR'],
			'month' => ['empty' => 'MONTH'],
			'day' => ['empty' => 'DAY'],
			'hour' => ['empty' => 'HOUR'],
			'minute' => ['empty' => 'MINUTE'],
			'second' => ['empty' => 'SECOND'],
		]);
		$this->assertContains('<option value="" selected="selected">YEAR</option>', $result);
		$this->assertContains('<option value="" selected="selected">MONTH</option>', $result);
		$this->assertContains('<option value="" selected="selected">DAY</option>', $result);
		$this->assertContains('<option value="" selected="selected">HOUR</option>', $result);
		$this->assertContains('<option value="" selected="selected">MINUTE</option>', $result);
		$this->assertContains('<option value="" selected="selected">SECOND</option>', $result);
	}

/**
 * Test rendering the default year widget.
 *
 * @return void
 */
	public function testRenderYearWidgetDefaultRange() {
		$now = new \DateTime();
		$result = $this->DateTime->render([
			'month' => false,
			'day' => false,
			'hour' => false,
			'minute' => false,
			'second' => false,
			'val' => $now,
		]);
		$year = $now->format('Y');
		$format = '<option value="%s" selected="selected">%s</option>';
		$this->assertContains(sprintf($format, $year, $year), $result);

		$format = '<option value="%s">%s</option>';
		$maxYear = $now->format('Y') + 5;
		$minYear = $now->format('Y') - 5;
		$this->assertContains(sprintf($format, $maxYear, $maxYear), $result);
		$this->assertContains(sprintf($format, $minYear, $minYear), $result);

		$nope = $now->format('Y') + 6;
		$this->assertNotContains(sprintf($format, $nope, $nope), $result);

		$nope = $now->format('Y') - 6;
		$this->assertNotContains(sprintf($format, $nope, $nope), $result);
	}

/**
 * Test ordering of year options.
 *
 * @return void
 */
	public function testRenderYearWidgetOrdering() {
		$now = new \DateTime('2014-01-01 12:00:00');
		$result = $this->DateTime->render([
			'name' => 'date',
			'year' => [
				'start' => 2013,
				'end' => 2015,
				'order' => 'desc',
			],
			'month' => false,
			'day' => false,
			'hour' => false,
			'minute' => false,
			'second' => false,
			'val' => $now,
			'orderYear' => 'asc',
		]);
		$expected = [
			'select' => ['name' => 'date[year]'],
			['option' => ['value' => '2013']], '2013', '/option',
			['option' => ['value' => '2014', 'selected' => 'selected']], '2014', '/option',
			['option' => ['value' => '2015']], '2015', '/option',
			'/select',
		];
		$this->assertTags($result, $expected);

		$result = $this->DateTime->render([
			'name' => 'date',
			'year' => [
				'start' => 2013,
				'end' => 2015,
				'order' => 'asc'
			],
			'month' => false,
			'day' => false,
			'hour' => false,
			'minute' => false,
			'second' => false,
			'val' => $now,
		]);
		$expected = [
			'select' => ['name' => 'date[year]'],
			['option' => ['value' => '2015']], '2015', '/option',
			['option' => ['value' => '2014', 'selected' => 'selected']], '2014', '/option',
			['option' => ['value' => '2013']], '2013', '/option',
			'/select',
		];
		$this->assertTags($result, $expected);
	}

	public function testRenderYearWidgetValueOutOfBounds() {
		$this->markTestIncomplete();
	}

	public function testRenderMonthWidget() {
		$this->markTestIncomplete();
	}

	public function testRenderMonthWidgetWithNames() {
		$this->markTestIncomplete();
	}

	public function testRenderDayWidget() {
		$this->markTestIncomplete();
	}

	public function testRenderHourWidget() {
		$this->markTestIncomplete();
	}

	public function testRenderHourWidget24() {
		$this->markTestIncomplete();
	}

	public function testRenderMinuteWidget() {
		$this->markTestIncomplete();
	}

	public function testRenderMinuteWidgetInterval() {
		$this->markTestIncomplete();
	}

	public function testRenderMinuteWidgetIntervalRounding() {
		$this->markTestIncomplete();
	}

	public function testRenderSecondsWidget() {
		$this->markTestIncomplete();
	}

	public function testRenderMeridianWidget() {
		$this->markTestIncomplete();
	}

/**
 * testGenerateNumbers
 *
 * @return void
 */
	public function testGenerateNumbers() {
		$result = $this->DateTime->generateNumbers(1, 3);
		$expected = array(
			'01' => '01',
			'02' => '02',
			'03' => '03'
		);
		$this->assertEquals($result, $expected);

		$result = $this->DateTime->generateNumbers(1, 3, [
			'leadingZeroKey' => false
		]);
		$expected = array(
			1 => '01',
			2 => '02',
			3 => '03'
		);
		$this->assertEquals($result, $expected);

		$result = $this->DateTime->generateNumbers(1, 5, [
			'leadingZeroValue' => false
		]);
		$expected = array(
			'01' => '1',
			'02' => '2',
			'03' => '3',
			'04' => '4',
			'05' => '5'
		);
		$this->assertEquals($result, $expected);

		$result = $this->DateTime->generateNumbers(1, 3, [
			'leadingZeroValue' => false,
			'leadingZeroKey' => false
		]);
		$expected = array(
			1 => '1',
			2 => '2',
			3 => '3'
		);
		$this->assertEquals($result, $expected);
	}

}
