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
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\Formatter\IcuFormatter;
use Cake\TestSuite\TestCase;

/**
 * IcuFormatter tests
 *
 */
class IcuFormatterTest extends TestCase {

/**
 * Tests that variables are interpolated correctly
 *
 * @return void
 */
	public function testFormatSimple() {
		$formatter = new IcuFormatter();
		$this->assertEquals('Hello José', $formatter->format('en_US', 'Hello {0}', ['José']));
		$result = $formatter->format(
			'1 Orange',
			'{count, number} {fruit}',
			['count' => 1.0, 'fruit' => 'Orange']
		);
		$this->assertEquals('1 Orange', $result);
	}

/**
 * Tests that plural forms can be selected using the PO file format plural forms
 *
 * @return void
 */
	public function testFormatPlural() {
		$formatter = new IcuFormatter();
		$messages = [
			'{0} is 0',
			'{0} is 1',
			'{0} is 2',
			'{0} is 3',
			'{0} > 11'
		];
		$this->assertEquals('1 is 1', $formatter->format('ar', $messages, ['_count' => 1, 1]));
		$this->assertEquals('2 is 2', $formatter->format('ar', $messages, ['_count' => 2, 2]));
		$this->assertEquals('20 > 11', $formatter->format('ar', $messages, ['_count' => 20, 20]));
	}

/**
 * Tests that plurals can instead be selected using ICU's native selector
 *
 * @return void
 */
	public function testNativePluralSelection() {
		$formatter = new IcuFormatter();
		$locale = 'en_US';
		$string = '{fruits,plural,'
			. '=0{No fruits.}'
			. '=1{We have one fruit}'
			. 'other{We have {count} fruits}'
			. '}';

		$params = ['fruits' => 0];
		$expect = 'No fruits.';
		$actual = $formatter->format($locale, $string, $params);
		$this->assertSame($expect, $actual);

		$params = ['fruits' => 1];
		$expect = 'We have one fruit';
		$actual = $formatter->format($locale, $string, $params);
		$this->assertSame($expect, $actual);

		$params = ['fruits' => 10, 'count' => 10];
		$expect = 'We have 10 fruits';
		$actual = $formatter->format($locale, $string, $params);
		$this->assertSame($expect, $actual);
	}

/**
 * Tests that passing a message in the wrong format will throw an exception
 * 
 * @expectedException Aura\Intl\Exception\CannotInstantiateFormatter
 * @return void
 */ 
	public function testBadMessageFormat() {
		$formatter = new IcuFormatter();
		$formatter->format('en_US', '{crazy format', ['some', 'vars']);
	}

/**
 * Tests that strings stored inside context namespaces can also be formatted
 *
 * @return void
 */
	public function testFormatWithContext() {
		$messages = [
			'simple' => [
				'_context' => [
					'context a' => 'Text "a" {thing}',
					'context b' => 'Text "b" {thing}'
				]
			],
			'complex' => [
				'_context' => [
					'context b' => [
						0 => 'Only one',
						1 => 'there are {_count}'
					]
				]
			]
		];

		$formatter = new IcuFormatter();
		$this->assertEquals(
			'Text "a" is good',
			$formatter->format('en', $messages['simple'], ['_context' => 'context a', 'thing' => 'is good'])
		);
		$this->assertEquals(
			'Text "b" is good',
			$formatter->format('en', $messages['simple'], ['_context' => 'context b', 'thing' => 'is good'])
		);
		$this->assertEquals(
			'Text "a" is good',
			$formatter->format('en', $messages['simple'], ['thing' => 'is good'])
		);

		$this->assertEquals(
			'Only one',
			$formatter->format('en', $messages['complex'], ['_context' => 'context b', '_count' => 1])
		);

		$this->assertEquals(
			'there are 2',
			$formatter->format('en', $messages['complex'], ['_context' => 'context b', '_count' => 2])
		);
	}

}
