<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\Cache\Cache;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\I18n\Translator;
use Cake\I18n\TranslatorRegistry;
use Cake\TestSuite\TestCase;
use Locale;

/**
 * I18nTest class
 */
class I18nTest extends TestCase
{
    /**
     * Used to restore the internal locale after tests
     *
     * @var string
     */
    protected $locale;

    /**
     * Set Up
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->locale = Locale::getDefault() ?: I18n::DEFAULT_LOCALE;
    }

    /**
     * Tear down method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        I18n::clear();
        I18n::setDefaultFormatter('default');
        I18n::setLocale($this->locale);
        $this->clearPlugins();
        Cache::clear('_cake_core_');
    }

    /**
     * Tests that the default locale is set correctly
     *
     * @return void
     */
    public function testDefaultLocale()
    {
        $newLocale = 'de_DE';
        I18n::setLocale($newLocale);
        $this->assertSame($newLocale, I18n::getLocale());
        $this->assertSame($this->locale, I18n::getDefaultLocale());
    }

    /**
     * Tests that a default translator is created and messages are parsed
     * correctly
     *
     * @return void
     */
    public function testGetDefaultTranslator()
    {
        $translator = I18n::getTranslator();
        $this->assertInstanceOf(Translator::class, $translator);
        $this->assertSame('%d is 1 (po translated)', $translator->translate('%d = 1'));
        $this->assertSame($translator, I18n::getTranslator(), 'backwards compat works');
    }

    /**
     * Tests that the translator can automatically load messages from a .mo file
     *
     * @return void
     */
    public function testGetTranslatorLoadMoFile()
    {
        $translator = I18n::getTranslator('default', 'es_ES');
        $this->assertSame('Plural Rule 6 (translated)', $translator->translate('Plural Rule 1'));
    }

    /**
     * Tests that plural rules are correctly used for the English language
     * using the sprintf formatter
     *
     * @return void
     */
    public function testPluralSelectionSprintfFormatter()
    {
        I18n::setDefaultFormatter('sprintf');
        $translator = I18n::getTranslator(); // en_US
        $result = $translator->translate('%d = 0 or > 1', ['_count' => 1, 1]);
        $this->assertSame('1 is 1 (po translated)', $result);

        $result = $translator->translate('%d = 0 or > 1', ['_count' => 2, 2]);
        $this->assertSame('2 is 2-4 (po translated)', $result);
    }

    /**
     * Tests that plural rules are correctly used for the English language
     * using the basic formatter
     *
     * @return void
     */
    public function testPluralSelectionBasicFormatter()
    {
        $translator = I18n::getTranslator('special');
        $result = $translator->translate('There are {0} things', ['_count' => 2, 'plenty']);
        $this->assertSame('There are plenty things', $result);

        $result = $translator->translate('There are {0} things', ['_count' => 1]);
        $this->assertSame('There is only one', $result);
    }

    /**
     * Test plural rules are used for non-english languages
     *
     * @return void
     */
    public function testPluralSelectionRussian()
    {
        $translator = I18n::getTranslator('default', 'ru');
        $result = $translator->translate('{0} months', ['_count' => 1, 1]);
        $this->assertSame('1 months ends in 1, not 11', $result);

        $result = $translator->translate('{0} months', ['_count' => 2, 2]);
        $this->assertSame('2 months ends in 2-4, not 12-14', $result);

        $result = $translator->translate('{0} months', ['_count' => 7, 7]);
        $this->assertSame('7 months everything else', $result);
    }

    /**
     * Tests that custom translation packages can be created on the fly and used later on
     *
     * @return void
     */
    public function testCreateCustomTranslationPackage()
    {
        I18n::setTranslator('custom', function () {
            $package = new Package('default');
            $package->setMessages([
                'Cow' => 'Le moo',
            ]);

            return $package;
        }, 'fr_FR');

        $translator = I18n::getTranslator('custom', 'fr_FR');
        $this->assertSame('Le moo', $translator->translate('Cow'));
    }

    /**
     * Tests that messages can also be loaded from plugins by using the
     * domain = plugin_name convention
     *
     * @return void
     */
    public function testPluginMesagesLoad()
    {
        $this->loadPlugins([
            'TestPlugin',
            'Company/TestPluginThree',
        ]);

        $translator = I18n::getTranslator('test_plugin');
        $this->assertSame(
            'Plural Rule 1 (from plugin)',
            $translator->translate('Plural Rule 1')
        );

        $translator = I18n::getTranslator('company/test_plugin_three');
        $this->assertSame(
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
    public function testPluginOverride()
    {
        $this->loadPlugins(['TestTheme']);
        $translator = I18n::getTranslator('test_theme');
        $this->assertSame(
            'translated',
            $translator->translate('A Message')
        );
    }

    /**
     * Tests the locale method
     *
     * @return void
     */
    public function testGetDefaultLocale()
    {
        $this->assertSame('en_US', I18n::getLocale());
        $this->assertSame('en_US', ini_get('intl.default_locale'));
        I18n::setLocale('fr_FR');
        $this->assertSame('fr_FR', I18n::getLocale());
        $this->assertSame('fr_FR', ini_get('intl.default_locale'));
    }

    /**
     * Tests that changing the default locale also changes the way translators
     * are fetched
     *
     * @return void
     */
    public function testGetTranslatorByDefaultLocale()
    {
        I18n::setTranslator('custom', function () {
            $package = new Package('default');
            $package->setMessages([
                'Cow' => 'Le moo',
            ]);

            return $package;
        }, 'fr_FR');

        I18n::setLocale('fr_FR');
        $translator = I18n::getTranslator('custom');
        $this->assertSame('Le moo', $translator->translate('Cow'));
    }

    /**
     * Tests the __() function
     *
     * @return void
     */
    public function testBasicTranslateFunction()
    {
        I18n::setDefaultFormatter('sprintf');
        $this->assertSame('%d is 1 (po translated)', __('%d = 1'));
        $this->assertSame('1 is 1 (po translated)', __('%d = 1', 1));
        $this->assertSame('1 is 1 (po translated)', __('%d = 1', [1]));
        $this->assertSame('The red dog, and blue cat', __('The %s dog, and %s cat', ['red', 'blue']));
        $this->assertSame('The red dog, and blue cat', __('The %s dog, and %s cat', 'red', 'blue'));
    }

    /**
     * Tests the __() functions with explicit null params
     *
     * @return void
     */
    public function testBasicTranslateFunctionsWithNullParam()
    {
        $this->assertSame('text {0}', __('text {0}'));
        $this->assertSame('text ', __('text {0}', null));

        $this->assertSame('text {0}', __n('text {0}', 'texts {0}', 1));
        $this->assertSame('text ', __n('text {0}', 'texts {0}', 1, null));

        $this->assertSame('text {0}', __d('default', 'text {0}'));
        $this->assertSame('text ', __d('default', 'text {0}', null));

        $this->assertSame('text {0}', __dn('default', 'text {0}', 'texts {0}', 1));
        $this->assertSame('text ', __dn('default', 'text {0}', 'texts {0}', 1, null));

        $this->assertSame('text {0}', __x('default', 'text {0}'));
        $this->assertSame('text ', __x('default', 'text {0}', null));

        $this->assertSame('text {0}', __xn('default', 'text {0}', 'texts {0}', 1));
        $this->assertSame('text ', __xn('default', 'text {0}', 'texts {0}', 1, null));

        $this->assertSame('text {0}', __dx('default', 'words', 'text {0}'));
        $this->assertSame('text ', __dx('default', 'words', 'text {0}', null));

        $this->assertSame('text {0}', __dxn('default', 'words', 'text {0}', 'texts {0}', 1));
        $this->assertSame('text ', __dxn('default', 'words', 'text {0}', 'texts {0}', 1, null));
    }

    /**
     * Tests the __() function on a plural key works
     *
     * @return void
     */
    public function testBasicTranslateFunctionPluralData()
    {
        I18n::setDefaultFormatter('sprintf');
        $this->assertSame('%d is 1 (po translated)', __('%d = 0 or > 1'));
    }

    /**
     * Tests the __n() function
     *
     * @return void
     */
    public function testBasicTranslatePluralFunction()
    {
        I18n::setDefaultFormatter('sprintf');
        $result = __n('singular msg', '%d = 0 or > 1', 1, 1);
        $this->assertSame('1 is 1 (po translated)', $result);

        $result = __n('singular msg', '%d = 0 or > 1', 2, 2);
        $this->assertSame('2 is 2-4 (po translated)', $result);

        $result = __n('%s, %s, and %s are good', '%s, %s, and %s are best', 1, ['red', 'blue', 'green']);
        $this->assertSame('red, blue, and green are good', $result);

        $result = __n('%s, %s, and %s are good', '%s, %s, and %s are best', 1, 'red', 'blue', 'green');
        $this->assertSame('red, blue, and green are good', $result);
    }

    /**
     * Tests the __n() function on singular keys
     *
     * @return void
     */
    public function testBasicTranslatePluralFunctionSingularMessage()
    {
        I18n::setDefaultFormatter('sprintf');
        $result = __n('No translation needed', 'not used', 1);
        $this->assertSame('No translation needed', $result);
    }

    /**
     * Tests the __d() function
     *
     * @return void
     */
    public function testBasicDomainFunction()
    {
        I18n::setTranslator('custom', function () {
            $package = new Package('default');
            $package->setMessages([
                'Cow' => 'Le moo',
                'The {0} is tasty' => 'The {0} is delicious',
                'Average price {0}' => 'Price Average {0}',
                'Unknown' => '',
            ]);

            return $package;
        }, 'en_US');
        $this->assertSame('Le moo', __d('custom', 'Cow'));
        $this->assertSame('Unknown', __d('custom', 'Unknown'));

        $result = __d('custom', 'The {0} is tasty', ['fruit']);
        $this->assertSame('The fruit is delicious', $result);

        $result = __d('custom', 'The {0} is tasty', 'fruit');
        $this->assertSame('The fruit is delicious', $result);

        $result = __d('custom', 'Average price {0}', ['9.99']);
        $this->assertSame('Price Average 9.99', $result);
    }

    /**
     * Tests the __dn() function
     *
     * @return void
     */
    public function testBasicDomainPluralFunction()
    {
        I18n::setTranslator('custom', function () {
            $package = new Package('default');
            $package->setMessages([
                'Cow' => 'Le Moo',
                'Cows' => [
                    'Le Moo',
                    'Les Moos',
                ],
                '{0} years' => [
                    '',
                    '',
                ],
            ]);

            return $package;
        }, 'en_US');
        $this->assertSame('Le Moo', __dn('custom', 'Cow', 'Cows', 1));
        $this->assertSame('Les Moos', __dn('custom', 'Cow', 'Cows', 2));
        $this->assertSame('{0} years', __dn('custom', '{0} year', '{0} years', 1));
        $this->assertSame('{0} years', __dn('custom', '{0} year', '{0} years', 2));
    }

    /**
     * Tests the __x() function
     *
     * @return void
     */
    public function testBasicContextFunction()
    {
        I18n::setTranslator('default', function () {
            $package = new Package('default');
            $package->setMessages([
                'letter' => [
                    '_context' => [
                        'character' => 'The letter {0}',
                        'communication' => 'She wrote a letter to {0}',
                    ],
                ],
                'letters' => [
                    '_context' => [
                        'character' => [
                            'The letter {0}',
                            'The letters {0} and {1}',
                        ],
                        'communication' => [
                            'She wrote a letter to {0}',
                            'She wrote a letter to {0} and {1}',
                        ],
                    ],
                ],
            ]);

            return $package;
        }, 'en_US');

        $this->assertSame('The letters A and B', __x('character', 'letters', ['A', 'B']));
        $this->assertSame('The letter A', __x('character', 'letter', ['A']));

        $this->assertSame('The letters A and B', __x('character', 'letters', 'A', 'B'));
        $this->assertSame('The letter A', __x('character', 'letter', 'A'));

        $this->assertSame(
            'She wrote a letter to Thomas and Sara',
            __x('communication', 'letters', ['Thomas', 'Sara'])
        );
        $this->assertSame(
            'She wrote a letter to Thomas',
            __x('communication', 'letter', ['Thomas'])
        );

        $this->assertSame(
            'She wrote a letter to Thomas and Sara',
            __x('communication', 'letters', 'Thomas', 'Sara')
        );
        $this->assertSame(
            'She wrote a letter to Thomas',
            __x('communication', 'letter', 'Thomas')
        );
    }

    /**
     * Tests the __x() function with no msgstr
     *
     * @return void
     */
    public function testBasicContextFunctionNoString()
    {
        I18n::setTranslator('default', function () {
            $package = new Package('default');
            $package->setMessages([
                'letter' => [
                    '_context' => [
                        'character' => '',
                    ],
                ],
            ]);

            return $package;
        }, 'en_US');

        $this->assertSame('letter', __x('character', 'letter'));
        $this->assertSame('letter', __x('unknown', 'letter'));
    }

    /**
     * Tests the __x() function with an invalid context
     *
     * @return void
     */
    public function testBasicContextFunctionInvalidContext()
    {
        I18n::setTranslator('default', function () {
            $package = new Package('default');
            $package->setMessages([
                'letter' => [
                    '_context' => [
                        'noun' => 'a paper letter',
                    ],
                ],
            ]);

            return $package;
        }, 'en_US');

        $this->assertSame('letter', __x('garbage', 'letter'));
        $this->assertSame('a paper letter', __('letter'));
    }

    /**
     * Tests the __xn() function
     *
     * @return void
     */
    public function testPluralContextFunction()
    {
        I18n::setTranslator('default', function () {
            $package = new Package('default');
            $package->setMessages([
                'letter' => [
                    '_context' => [
                        'character' => 'The letter {0}',
                        'communication' => 'She wrote a letter to {0}',
                    ],
                ],
                'letters' => [
                    '_context' => [
                        'character' => [
                            'The letter {0}',
                            'The letters {0} and {1}',
                        ],
                        'communication' => [
                            'She wrote a letter to {0}',
                            'She wrote a letter to {0} and {1}',
                        ],
                    ],
                ],
            ]);

            return $package;
        }, 'en_US');
        $this->assertSame('The letters A and B', __xn('character', 'letter', 'letters', 2, ['A', 'B']));
        $this->assertSame('The letter A', __xn('character', 'letter', 'letters', 1, ['A']));

        $this->assertSame(
            'She wrote a letter to Thomas and Sara',
            __xn('communication', 'letter', 'letters', 2, ['Thomas', 'Sara'])
        );
        $this->assertSame(
            'She wrote a letter to Thomas',
            __xn('communication', 'letter', 'letters', 1, ['Thomas'])
        );

        $this->assertSame(
            'She wrote a letter to Thomas and Sara',
            __xn('communication', 'letter', 'letters', 2, 'Thomas', 'Sara')
        );
        $this->assertSame(
            'She wrote a letter to Thomas',
            __xn('communication', 'letter', 'letters', 1, 'Thomas')
        );
    }

    /**
     * Tests the __dx() function
     *
     * @return void
     */
    public function testDomainContextFunction()
    {
        I18n::setTranslator('custom', function () {
            $package = new Package('default');
            $package->setMessages([
                'letter' => [
                    '_context' => [
                        'character' => 'The letter {0}',
                        'communication' => 'She wrote a letter to {0}',
                    ],
                ],
                'letters' => [
                    '_context' => [
                        'character' => [
                            'The letter {0}',
                            'The letters {0} and {1}',
                        ],
                        'communication' => [
                            'She wrote a letter to {0}',
                            'She wrote a letter to {0} and {1}',
                        ],
                    ],
                ],
            ]);

            return $package;
        }, 'en_US');

        $this->assertSame('The letters A and B', __dx('custom', 'character', 'letters', ['A', 'B']));
        $this->assertSame('The letter A', __dx('custom', 'character', 'letter', ['A']));

        $this->assertSame(
            'She wrote a letter to Thomas and Sara',
            __dx('custom', 'communication', 'letters', ['Thomas', 'Sara'])
        );
        $this->assertSame(
            'She wrote a letter to Thomas',
            __dx('custom', 'communication', 'letter', ['Thomas'])
        );

        $this->assertSame(
            'She wrote a letter to Thomas and Sara',
            __dx('custom', 'communication', 'letters', 'Thomas', 'Sara')
        );
        $this->assertSame(
            'She wrote a letter to Thomas',
            __dx('custom', 'communication', 'letter', 'Thomas')
        );
    }

    /**
     * Tests the __dxn() function
     *
     * @return void
     */
    public function testDomainPluralContextFunction()
    {
        I18n::setTranslator('custom', function () {
            $package = new Package('default');
            $package->setMessages([
                'letter' => [
                    '_context' => [
                        'character' => 'The letter {0}',
                        'communication' => 'She wrote a letter to {0}',
                    ],
                ],
                'letters' => [
                    '_context' => [
                        'character' => [
                            'The letter {0}',
                            'The letters {0} and {1}',
                        ],
                        'communication' => [
                            'She wrote a letter to {0}',
                            'She wrote a letter to {0} and {1}',
                        ],
                    ],
                ],
            ]);

            return $package;
        }, 'en_US');
        $this->assertSame(
            'The letters A and B',
            __dxn('custom', 'character', 'letter', 'letters', 2, ['A', 'B'])
        );
        $this->assertSame(
            'The letter A',
            __dxn('custom', 'character', 'letter', 'letters', 1, ['A'])
        );

        $this->assertSame(
            'She wrote a letter to Thomas and Sara',
            __dxn('custom', 'communication', 'letter', 'letters', 2, ['Thomas', 'Sara'])
        );
        $this->assertSame(
            'She wrote a letter to Thomas',
            __dxn('custom', 'communication', 'letter', 'letters', 1, ['Thomas'])
        );

        $this->assertSame(
            'She wrote a letter to Thomas and Sara',
            __dxn('custom', 'communication', 'letter', 'letters', 2, 'Thomas', 'Sara')
        );
        $this->assertSame(
            'She wrote a letter to Thomas',
            __dxn('custom', 'communication', 'letter', 'letters', 1, 'Thomas')
        );
    }

    /**
     * Tests that translators are cached for performance
     *
     * @return void
     */
    public function testTranslatorCache()
    {
        $english = I18n::getTranslator();
        $spanish = I18n::getTranslator('default', 'es_ES');

        $cached = Cache::read('translations.default.en_US', '_cake_core_');
        $this->assertEquals($english, $cached);

        $cached = Cache::read('translations.default.es_ES', '_cake_core_');
        $this->assertEquals($spanish, $cached);

        $this->assertSame($english, I18n::getTranslator());
        $this->assertSame($spanish, I18n::getTranslator('default', 'es_ES'));
        $this->assertSame($english, I18n::getTranslator());
    }

    /**
     * Tests that it is possible to register a generic translators factory for a domain
     * instead of having to create them manually
     *
     * @return void
     */
    public function testLoaderFactory()
    {
        I18n::config('custom', function (string $name, string $locale) {
            $this->assertSame('custom', $name);
            $package = new Package('default');

            if ($locale === 'fr_FR') {
                $package->setMessages([
                'Cow' => 'Le Moo',
                'Cows' => [
                    'Le Moo',
                    'Les Moos',
                    ],
                ]);
            }

            if ($locale === 'es_ES') {
                $package->setMessages([
                'Cow' => 'El Moo',
                'Cows' => [
                    'El Moo',
                    'Los Moos',
                    ],
                ]);
            }

            return $package;
        });

        $translator = I18n::getTranslator('custom', 'fr_FR');
        $this->assertSame('Le Moo', $translator->translate('Cow'));
        $this->assertSame('Les Moos', $translator->translate('Cows', ['_count' => 2]));

        $translator = I18n::getTranslator('custom', 'es_ES');
        $this->assertSame('El Moo', $translator->translate('Cow'));
        $this->assertSame('Los Moos', $translator->translate('Cows', ['_count' => 2]));

        $translator = I18n::getTranslator();
        $this->assertSame('%d is 1 (po translated)', $translator->translate('%d = 1'));
    }

    /**
     * Tests that it is possible to register a fallback translators factory
     *
     * @return void
     */
    public function testFallbackLoaderFactory()
    {
        I18n::config(TranslatorRegistry::FALLBACK_LOADER, function (string $name, string $locale) {
            $package = new Package('default');

            if ($name === 'custom') {
                $package->setMessages([
                    'Cow' => 'Le Moo custom',
                ]);
            } else {
                $package->setMessages([
                    'Cow' => 'Le Moo default',
                ]);
            }

            return $package;
        });

        $translator = I18n::getTranslator('custom');
        $this->assertSame('Le Moo custom', $translator->translate('Cow'));

        $translator = I18n::getTranslator();
        $this->assertSame('Le Moo default', $translator->translate('Cow'));
    }

    /**
     * Tests that missing translations will get fallbacked to the default translator
     *
     * @return void
     */
    public function testFallbackTranslator()
    {
        I18n::setTranslator('default', function () {
            $package = new Package('default');
            $package->setMessages([
                'Dog' => 'Le bark',
            ]);

            return $package;
        }, 'fr_FR');

        I18n::setTranslator('custom', function () {
            $package = new Package('default');
            $package->setMessages([
                'Cow' => 'Le moo',
            ]);

            return $package;
        }, 'fr_FR');

        $translator = I18n::getTranslator('custom', 'fr_FR');
        $this->assertSame('Le moo', $translator->translate('Cow'));
        $this->assertSame('Le bark', $translator->translate('Dog'));
    }

    /**
     * Test that the translation fallback can be disabled
     *
     * @return void
     */
    public function testFallbackTranslatorDisabled()
    {
        I18n::useFallback(false);

        I18n::setTranslator('default', function () {
            $package = new Package('default');
            $package->setMessages(['Dog' => 'Le bark']);

            return $package;
        }, 'fr_FR');

        I18n::setTranslator('custom', function () {
            $package = new Package('default');
            $package->setMessages(['Cow' => 'Le moo']);

            return $package;
        }, 'fr_FR');

        $translator = I18n::getTranslator('custom', 'fr_FR');
        $this->assertSame('Le moo', $translator->translate('Cow'));
        $this->assertSame('Dog', $translator->translate('Dog'));
    }

    /**
     * Tests that it is possible to register a generic translators factory for a domain
     * instead of having to create them manually
     *
     * @return void
     */
    public function testFallbackTranslatorWithFactory()
    {
        I18n::setTranslator('default', function () {
            $package = new Package('default');
            $package->setMessages([
                'Dog' => 'Le bark',
            ]);

            return $package;
        }, 'fr_FR');
        I18n::config('custom', function ($name, $locale) {
            $this->assertSame('custom', $name);
            $package = new Package('default');
            $package->setMessages([
                'Cow' => 'Le moo',
            ]);

            return $package;
        });

        $translator = I18n::getTranslator('custom', 'fr_FR');
        $this->assertSame('Le moo', $translator->translate('Cow'));
        $this->assertSame('Le bark', $translator->translate('Dog'));
    }

    /**
     * Tests the __() function on empty translations
     *
     * @return void
     */
    public function testEmptyTranslationString()
    {
        I18n::setDefaultFormatter('sprintf');
        $result = __('No translation needed');
        $this->assertSame('No translation needed', $result);
    }

    /**
     * Tests that a plurals from a domain get translated correctly
     *
     * @return void
     */
    public function testPluralTranslationsFromDomain()
    {
        I18n::setLocale('de');
        $this->assertSame('Standorte', __dn('wa', 'Location', 'Locations', 0));
        $this->assertSame('Standort', __dn('wa', 'Location', 'Locations', 1));
        $this->assertSame('Standorte', __dn('wa', 'Location', 'Locations', 2));
    }
}
