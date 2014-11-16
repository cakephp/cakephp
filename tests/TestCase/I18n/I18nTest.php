<?php
/**
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
use Cake\Cache\Cache;
use Cake\Core\Plugin;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

/**
 * I18nTest class
 *
 */
class I18nTest extends TestCase {

/**
 * Used to restore the internal locale after tests
 *
 * @var string
 */
	public $locale;

/**
 * Set Up
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->locale = I18n::locale();
	}

/**
 * Tear down method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		I18n::clear();
		I18n::defaultFormatter('default');
		I18n::locale($this->locale);
		Plugin::unload();
		Cache::clear(false, '_cake_core_');
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
		$result = $translator->translate('There are {0} things', ['_count' => 2, 'plenty']);
		$this->assertEquals('There are plenty things', $result);

		$result = $translator->translate('There are {0} things', ['_count' => 1]);
		$this->assertEquals('There is only one', $result);
	}

/**
 * Tests that custom translation packages can be created on the fly and used later on
 *
 * @return void
 */
	public function testCreateCustomTranslationPackage() {
		I18n::translator('custom', 'fr_FR', function () {
			$package = new Package('default');
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
		Plugin::load([
			'TestPlugin',
			'Company/TestPluginThree'
		]);

		$translator = I18n::translator('test_plugin');
		$this->assertEquals(
			'Plural Rule 1 (from plugin)',
			$translator->translate('Plural Rule 1')
		);

		$translator = I18n::translator('company/test_plugin_three');
		$this->assertEquals(
			'String 1 (from plugin three)',
			$translator->translate('String 1')
		);
	}

/**
 * Tests that messages messages from a plugin can be automatically
 * overridden by messages in app
 *
 * @return void
 */
	public function testPluginOverride() {
		Plugin::load('TestTheme');
		$translator = I18n::translator('test_theme');
		$this->assertEquals(
			'translated',
			$translator->translate('A Message')
		);
	}

/**
 * Tests the locale method
 *
 * @return void
 */
	public function testDefaultLocale() {
		$this->assertEquals('en_US', I18n::locale());
		$this->assertEquals('en_US', ini_get('intl.default_locale'));
		I18n::locale('fr_FR');
		$this->assertEquals('fr_FR', I18n::locale());
		$this->assertEquals('fr_FR', ini_get('intl.default_locale'));
	}

/**
 * Tests that changing the default locale also changes the way translators
 * are fetched
 *
 * @return void
 */
	public function testGetTranslatorByDefaultLocale() {
		I18n::translator('custom', 'fr_FR', function () {
			$package = new Package('default');
			$package->setMessages([
				'Cow' => 'Le moo'
			]);
			return $package;
		});

		I18n::locale('fr_FR');
		$translator = I18n::translator('custom');
		$this->assertEquals('Le moo', $translator->translate('Cow'));
	}

/**
 * Tests the __() function
 *
 * @return void
 */
	public function testBasicTranslateFunction() {
		I18n::defaultFormatter('sprintf');
		$this->assertEquals('%d is 1 (po translated)', __('%d = 1'));
		$this->assertEquals('1 is 1 (po translated)', __('%d = 1', 1));
	}

/**
 * Tests the __n() function
 *
 * @return void
 */
	public function testBasicTranslatePluralFunction() {
		I18n::defaultFormatter('sprintf');
		$result = __n('singular msg', '%d = 0 or > 1', 1);
		$this->assertEquals('1 is 1 (po translated)', $result);

		$result = __n('singular msg', '%d = 0 or > 1', 2);
		$this->assertEquals('2 is 2-4 (po translated)', $result);
	}

/**
 * Tests the __d() function
 *
 * @return void
 */
	public function testBasicDomainFunction() {
		I18n::translator('custom', 'en_US', function () {
			$package = new Package('default');
			$package->setMessages([
				'Cow' => 'Le moo',
				'The {0} is tasty' => 'The {0} is delicious'
			]);
			return $package;
		});
		$result = __d('custom', 'The {0} is tasty', ['fruit']);
		$this->assertEquals('The fruit is delicious', $result);
	}

/**
 * Tests the __dn() function
 *
 * @return void
 */
	public function testBasicDomainPluralFunction() {
		I18n::translator('custom', 'en_US', function () {
			$package = new Package('default');
			$package->setMessages([
				'Cow' => 'Le Moo',
				'Cows' => [
					'Le Moo',
					'Les Moos'
				]
			]);
			return $package;
		});
		$this->assertEquals('Le Moo', __dn('custom', 'Cow', 'Cows', 1));
		$this->assertEquals('Les Moos', __dn('custom', 'Cow', 'Cows', 2));
	}

/**
 * Tests the __x() function
 *
 * @return void
 */
	public function testBasicContextFunction() {
		I18n::translator('default', 'en_US', function () {
			$package = new Package('default');
			$package->setMessages([
				'letter' => [
					'_context' => [
						'character' => 'The letter {0}',
						'communication' => 'She wrote a letter to {0}'
					]
				]
			]);
			return $package;
		});

		$this->assertEquals('The letter A', __x('character', 'letter', ['A']));
		$this->assertEquals(
			'She wrote a letter to Thomas',
			__x('communication', 'letter', ['Thomas'])
		);
	}

/**
 * Tests the __xn() function
 *
 * @return void
 */
	public function testPluralContextFunction() {
		I18n::translator('default', 'en_US', function () {
			$package = new Package('default');
			$package->setMessages([
				'letter' => [
					'_context' => [
						'character' => [
							'The letter {0}',
							'The letters {0} and {1}'
						],
						'communication' => [
							'She wrote a letter to {0}',
							'She wrote a letter to {0} and {1}'
						]
					]
				]
			]);
			return $package;
		});
		$this->assertEquals('The letters A and B', __xn('character', 'letter', 'letters', 2, ['A', 'B']));
		$this->assertEquals('The letter A', __xn('character', 'letter', 'letters', 1, ['A']));

		$this->assertEquals(
			'She wrote a letter to Thomas and Sara',
			__xn('communication', 'letter', 'letters', 2, ['Thomas', 'Sara'])
		);
		$this->assertEquals(
			'She wrote a letter to Thomas',
			__xn('communication', 'letter', 'letters', 1, ['Thomas'])
		);
	}

/**
 * Tests the __dx() function
 *
 * @return void
 */
	public function testDomainContextFunction() {
		I18n::translator('custom', 'en_US', function () {
			$package = new Package('default');
			$package->setMessages([
				'letter' => [
					'_context' => [
						'character' => 'The letter {0}',
						'communication' => 'She wrote a letter to {0}'
					]
				]
			]);
			return $package;
		});

		$this->assertEquals('The letter A', __dx('custom', 'character', 'letter', ['A']));
		$this->assertEquals(
			'She wrote a letter to Thomas',
			__dx('custom', 'communication', 'letter', ['Thomas'])
		);
	}

/**
 * Tests the __dxn() function
 *
 * @return void
 */
	public function testDomainPluralContextFunction() {
		I18n::translator('custom', 'en_US', function () {
			$package = new Package('default');
			$package->setMessages([
				'letter' => [
					'_context' => [
						'character' => [
							'The letter {0}',
							'The letters {0} and {1}'
						],
						'communication' => [
							'She wrote a letter to {0}',
							'She wrote a letter to {0} and {1}'
						]
					]
				]
			]);
			return $package;
		});
		$this->assertEquals(
			'The letters A and B',
			__dxn('custom', 'character', 'letter', 'letters', 2, ['A', 'B'])
		);
		$this->assertEquals(
			'The letter A',
			__dxn('custom', 'character', 'letter', 'letters', 1, ['A']));

		$this->assertEquals(
			'She wrote a letter to Thomas and Sara',
			__dxn('custom', 'communication', 'letter', 'letters', 2, ['Thomas', 'Sara'])
		);
		$this->assertEquals(
			'She wrote a letter to Thomas',
			__dxn('custom', 'communication', 'letter', 'letters', 1, ['Thomas'])
		);
	}

/**
 * Tests that translators are cached for performance
 *
 * @return void
 */
	public function testTranslatorCache() {
		$english = I18n::translator();
		$spanish = I18n::translator('default', 'es_ES');

		$cached = Cache::read('translations.default.en_US', '_cake_core_');
		$this->assertEquals($english, $cached);

		$cached = Cache::read('translations.default.es_ES', '_cake_core_');
		$this->assertEquals($spanish, $cached);

		$this->assertSame($english, I18n::translator());
		$this->assertSame($spanish, I18n::translator('default', 'es_ES'));
		$this->assertSame($english, I18n::translator());
	}

/**
 * Tests that it is possible to register a generic translators factory for a domain
 * instead of having to create them manually
 *
 * @return void
 */
	public function testloaderFactory() {
		I18n::config('custom', function ($name, $locale) {
			$this->assertEquals('custom', $name);
			$package = new Package('default');

			if ($locale == 'fr_FR') {
				$package->setMessages([
				'Cow' => 'Le Moo',
				'Cows' => [
					'Le Moo',
					'Les Moos'
					]
				]);
			}

			if ($locale === 'es_ES') {
				$package->setMessages([
				'Cow' => 'El Moo',
				'Cows' => [
					'El Moo',
					'Los Moos'
					]
				]);
			}

			return $package;
		});

		$translator = I18n::translator('custom', 'fr_FR');
		$this->assertEquals('Le Moo', $translator->translate('Cow'));
		$this->assertEquals('Les Moos', $translator->translate('Cows', ['_count' => 2]));

		$translator = I18n::translator('custom', 'es_ES');
		$this->assertEquals('El Moo', $translator->translate('Cow'));
		$this->assertEquals('Los Moos', $translator->translate('Cows', ['_count' => 2]));

		$translator = I18n::translator();
		$this->assertEquals('%d is 1 (po translated)', $translator->translate('%d = 1'));
	}

/**
 * Tests that missing translations will get fallbacked to the default translator
 *
 * @return void
 */
	public function testFallbackTranslator() {
		I18n::translator('default', 'fr_FR', function () {
			$package = new Package('default');
			$package->setMessages([
				'Dog' => 'Le bark'
			]);
			return $package;
		});

		I18n::translator('custom', 'fr_FR', function () {
			$package = new Package('default');
			$package->setMessages([
				'Cow' => 'Le moo'
			]);
			return $package;
		});

		$translator = I18n::translator('custom', 'fr_FR');
		$this->assertEquals('Le moo', $translator->translate('Cow'));
		$this->assertEquals('Le bark', $translator->translate('Dog'));
	}

/**
 * Tests that it is possible to register a generic translators factory for a domain
 * instead of having to create them manually
 *
 * @return void
 */
	public function testFallbackTranslatorWithFactory() {
		I18n::translator('default', 'fr_FR', function () {
			$package = new Package('default');
			$package->setMessages([
				'Dog' => 'Le bark'
			]);
			return $package;
		});
		I18n::config('custom', function ($name, $locale) {
			$this->assertEquals('custom', $name);
			$package = new Package('default');
			$package->setMessages([
				'Cow' => 'Le moo',
			]);
			return $package;
		});

		$translator = I18n::translator('custom', 'fr_FR');
		$this->assertEquals('Le moo', $translator->translate('Cow'));
		$this->assertEquals('Le bark', $translator->translate('Dog'));
	}

}
