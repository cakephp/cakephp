<?php
/**
 * I18nTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

/**
 * I18nTest class
 *
 */
class I18nTest extends TestCase {

/**
 * Tests that a default translator is created and messages are parsed
 * correclty
 *
 * @return void
 */
	public function testDefaultTranslator() {
		$translator = I18n::translator();
		$this->assertInstanceOf('Aura\Intl\Translator', $translator);
		$this->assertEquals('%d is 1 (po translated)', $translator->translate('%d = 1'));
	}

/**
 * Tests that the translator can automatically load messages from a .mo file
 *
 * @return void
 */
	public function testTranslatorLoadMoFile() {
		$translator = I18n::translator('default', 'es_ES');
		$this->assertEquals('Plural Rule 6 (translated)', $translator->translate('Plural Rule 1'));
	}

/**
 * Tests that plural rules are correctly used for the English language
 *
 * @return void
 */
	public function testPluralSelection() {
		$translator = I18n::translator(); // en_US
		$result = $translator->translate('%d = 0 or > 1', ['_count' => 1]);
		$this->assertEquals('1 is 1 (po translated)', $result);

		$result = $translator->translate('%d = 0 or > 1', ['_count' => 2]);
		$this->assertEquals('2 is 2-4 (po translated)', $result);
	}

}
