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
			'dateWidget' => '<div{{attrs}}>{{year}}-{{month}}-{{day}} {{hour}}:{{minute}}:{{second}}</div>'
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

/**
 * testYearSelect
 *
 * @return void
 */
	public function testYearSelect() {
		$result = $this->DateTime->yearSelect();
		$this->markTestIncomplete();
	}

/**
 * testMonthSelect
 *
 * @return void
 */
	public function testMonthSelect() {
		$result = $this->DateTime->monthSelect();
		$expected = '<select name="data[month]"><option value="01">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option><option value="08">08</option><option value="09">09</option><option value="10">10</option><option value="11">11</option><option value="12">12</option></select>';
		$this->assertEquals($result, $expected);

		$result = $this->DateTime->monthSelect([
			'names' => true,
		]);
		$this->markTestIncomplete();
	}

/**
 * testDaySelect
 *
 * @return void
 */
	public function testDaySelect() {
		$result = $this->DateTime->daySelect();
		$expected = '<select name="data[day]"><option value="01" selected="selected">01</option><option value="02">02</option><option value="03">03</option><option value="04">04</option><option value="05">05</option><option value="06">06</option><option value="07">07</option></select>';
		$this->markTestIncomplete();
	}

/**
 * testHourSelect
 *
 * @return void
 */
	public function testHourSelect() {
		$result = $this->DateTime->hourSelect();
		$this->markTestIncomplete();
	}

/**
 * testHourSelect
 *
 * @return void
 */
	public function testMinuteSelect() {
		$result = $this->DateTime->minuteSelect();
		$this->markTestIncomplete();
	}

/**
 * testHourSelect
 *
 * @return void
 */
	public function testSecondSelect() {
		$result = $this->DateTime->secondSelect();
		$this->markTestIncomplete();
	}

}
