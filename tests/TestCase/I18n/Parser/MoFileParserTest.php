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
namespace Cake\Test\TestCase\I18n\Parser;

use Cake\I18n\Parser\MoFileParser;
use Cake\TestSuite\TestCase;

/**
 * Tests the MoFileLoader
 *
 */
class MoFileParserTest extends TestCase {

/**
 * Tests parsing a file with plurals and message context
 *
 * @return void
 */
	public function testParse() {
		$parser = new MoFileParser;
		$file = APP . 'Locale' . DS . 'rule_1_mo' . DS . 'core.mo';
		$messages = $parser->parse($file);
		$this->assertCount(3, $messages);
		$expected = [
			'%d = 1 (from core)' => '%d = 1 (from core translated)',
			'%d = 0 or > 1 (from core)' => [
				'%d = 1 (from core translated)',
				'%d = 0 or > 1 (from core translated)'
			],
			'Plural Rule 1 (from core)' => 'Plural Rule 1 (from core translated)'
		];
		$this->assertEquals($expected, $messages);
	}

/**
 * Tests parsing a file with larger plural forms
 *
 * @return void
 */
	public function testParse2() {
		$parser = new MoFileParser;
		$file = APP . 'Locale' . DS . 'rule_9_mo' . DS . 'core.mo';
		$messages = $parser->parse($file);
		$this->assertCount(3, $messages);
		$expected = [
			'%d = 1 (from core)' => '%d is 1 (from core translated)',
			'%d = 0 or > 1 (from core)' => [
				'%d is 1 (from core translated)',
				'%d ends in 2-4, not 12-14 (from core translated)',
				'%d everything else (from core translated)'
			],
			'Plural Rule 1 (from core)' => 'Plural Rule 9 (from core translated)'
		];
		$this->assertEquals($expected, $messages);
	}

}
