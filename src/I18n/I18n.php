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
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Aura\Intl\Exception as LoadException;
use Aura\Intl\FormatterLocator;
use Aura\Intl\Package;
use Aura\Intl\PackageLocator;
use Aura\Intl\TranslatorFactory;
use Aura\Intl\TranslatorLocator;
use Cake\I18n\Formatter\IcuFormatter;
use Cake\I18n\Formatter\SprintfFormatter;

/**
 * I18n handles translation of Text and time format strings.
 */
class I18n {

/**
 * The translators collection
 *
 * @var \Aura\Int\TranslatorLocator
 */
	protected static $_collection;

/**
 * The name of the default formatter to use for newly created
 * translators
 *
 * @var string
 */
	protected static $_defaultFormatter = 'basic';

/**
 * Returns the translators collection instance. It can be used
 * for getting specific translators based of their name and locale
 * or to configure some aspect of future translations that are not yet constructed.
 *
 * @return \Aura\Intl\TranslatorLocator The translators collection.
 */
	public static function translators() {
		if (static::$_collection !== null) {
			return static::$_collection;
		}

		return static::$_collection = new TranslatorLocator(
			new PackageLocator,
			new FormatterLocator([
				'sprintf' => function() {
					return new SprintfFormatter;
				},
				'basic' => function() {
					return new IcuFormatter;
				},
			]),
			new TranslatorFactory,
			static::defaultLocale()
		);
	}

/**
 * Returns an instance of a translator that was configured for the name and passed
 * locale. If no locale is passed the it takes the value returned by the `defaultLocale()` method.
 *
 * This method can be used to configure future translators, this is achieved by passing a callable
 * as the last argument of this function.
 *
 * ### Example:
 *
 * {{{
 *  I18n::translator('default', 'fr_FR', function() {
 *		$package = new \Aura\Intl\Package();
 *		$package->setMessages([
 *			'Cake' => 'Gâteau'
 *		]);
 *		return $package;
 *  });
 *
 *	$translator = I18n::translator('default', 'fr_FR');
 *	echo $translator->translate('Cake');
 * }}}
 *
 * You can also use the `Cake\I18n\MessagesFileLoader` class to load a specific
 * file from a folder. For example for loading a `my_translations.po` file from
 * the `src/Locale/custom` folder, you would do:
 *
 * {{{
 * I18n::translator(
 *	'default',
 *	'fr_FR',
 *	new MessagesFileLoader('my_translations', 'custom', 'po');
 * );
 * }}}
 *
 * @param string $name The domain of the translation messages.
 * @param string $locale The locale for the translator.
 * @param callable $loader A callback function or callable class responsible for
 * constructing a translations package instance.
 * @return \Aura\Intl\Translator The configured translator.
 */
	public static function translator($name = 'default', $locale = null, callable $loader = null) {
		if ($loader !== null) {
			$packages = static::translators()->getPackages();
			$locale = $locale ?: static::defaultLocale();
			$packages->set($name, $locale, $loader);
			return;
		}

		$translators = static::translators();

		if ($locale) {
			$currentLocale = $translators->getLocale();
			static::translators()->setLocale($locale);
		}

		try {
			$translator = $translators->get($name);
		} catch (LoadException $e) {
			$translator = static::_fallbackTranslator($name, $locale);
		}

		if (isset($currentLocale)) {
			$translators->setLocale($currentLocale);
		}

		return $translator;
	}

/**
 * Sets the default locale to use for future translator instances.
 * This also affects the `intl.default_locale` php setting.
 *
 * When called with no arguments it will return the currently configure
 * defaultLocale as stored in the `intl.default_locale` php setting.
 *
 * @param string $locale The name of the locale to set as default.
 * @return string|null The name of the default locale.
 */
	public static function defaultLocale($locale = null) {
		if (!empty($locale)) {
			ini_set('intl.default_locale', $locale);
			static::translators()->setLocale($locale);
			return;
		}

		$current = ini_get('intl.default_locale');

		if ($current === '') {
			$current = 'en_US';
			ini_set('intl.default_locale', $current);
		}

		return $current;
	}

/**
 * Sets the name of the default messages formatter to use for future
 * translator instances. By default the `basic` and `sprintf` formatters
 * are available.
 *
 * If called with no arguments, it will return the currently configured value.
 *
 * @param string $name The name of the formatter to use.
 * @return string The name of the formatter.
 */
	public static function defaultFormatter($name = null) {
		if ($name === null) {
			return static::$_defaultFormatter;
		}

		static::$_defaultFormatter = $name;
	}

/**
 * Destroys all translator instances and creates a new empty translations
 * collection.
 *
 * @return void
 */
	public static function clear() {
		static::$_collection = null;
	}

/**
 * Returns a new translator instance for the given name and locale
 * based of conventions.
 *
 * @param string $name The translation package name.
 * @param string $locale The locale to create the translator for.
 * @return \Aura\Intl\Translator
 */
	protected static function _fallbackTranslator($name, $locale) {
		$chain = new ChainMessagesLoader([
			new MessagesFileLoader($name, $locale, 'mo'),
			new MessagesFileLoader($name, $locale, 'po')
		]);

		if (static::$_defaultFormatter !== 'basic') {
			$formatter = static::$_defaultFormatter;
			$chain = function() use ($formatter, $chain) {
				$package = $chain();
				$package->setFormatter($formatter);
				return $package;
			};
		}

		static::translator($name, $locale, $chain);
		return static::translators()->get($name);
	}

}
