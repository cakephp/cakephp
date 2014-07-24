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

use Aura\Intl\Package;
use Cake\Core\Plugin;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

/**
 * I18nTest class
 *
 */
class I18nTest extends TestCase {

/**
 * Tear down method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		I18n::clear();
		I18n::defaultFormatter('basic');
		Plugin::unload();
	}

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
 * using the sprintf formatter
 *
 * @return void
 */
	public function testPluralSelection() {
		I18n::defaultFormatter('sprintf');
		$translator = I18n::translator(); // en_US
		$result = $translator->translate('%d = 0 or > 1', ['_count' => 1]);
		$this->assertEquals('1 is 1 (po translated)', $result);

		$result = $translator->translate('%d = 0 or > 1', ['_count' => 2]);
		$this->assertEquals('2 is 2-4 (po translated)', $result);
	}

/**
 * Tests that plural rules are correctly used for the English language
 * using the basic formatter
 *
 * @return void
 */
	public function testPluralSelectionBasicFormatter() {
		$translator = I18n::translator('special');
		$result = $translator->translate('There are {_count} things', ['_count' => 2]);
		$this->assertEquals('There are 2 things', $result);

		$result = $translator->translate('There are {_count} things', ['_count' => 1]);
		$this->assertEquals('There is only one', $result);
	}

/**
 * Tests that custom translation packages can be created on the fly and used later on
 *
 * @return void
 */
	public function testCreateCustomTranslationPackage() {
		I18n::translator('custom', 'fr_FR', function() {
			$package = new Package();
			$package->setMessages([
				'Cow' => 'Le moo'
			]);
			return $package;
		});

		$translator = I18n::translator('custom', 'fr_FR');
		$this->assertEquals('Le moo', $translator->translate('Cow'));
	}

/**
 * Tests that messages can also be loaded from plugins by using the
 * domain = plugin_name convention
 *
 * @return void
 */
	public function testPluginMesagesLoad() {
		Plugin::load('TestPlugin');
		$translator = I18n::translator('test_plugin');
		$this->assertEquals(
			'Plural Rule 1 (from plugin)',
			$translator->translate('Plural Rule 1')
		);
	}

/**
 * Tests the defaultLocale method
 *
 * @return void
 */
	public function testDefaultLocale() {
		$this->assertEquals('en_US', I18n::defaultLocale());
		$this->assertEquals('en_US', ini_get('intl.default_locale'));
		I18n::defaultLocale('fr_FR');
		$this->assertEquals('fr_FR', ini_get('intl.default_locale'));
	}

}
