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

use Aura\Intl\PackageLocator;
use Aura\Intl\FormatterLocator;
use Aura\Intl\TranslatorFactory;
use Aura\Intl\TranslatorLocator;
use Aura\Intl\Package;
/**
 * I18n handles translation of Text and time format strings.
 *
 */
class I18n {

	protected static $_collection;

	protected static $_defaultLocale = 'en_US';

	public static function translators() {
		if (static::$_collection !== null) {
			return static::$_collection;
		}

		$translators = new TranslatorLocator(
			new PackageLocator,
			new FormatterLocator([
				'basic' => function() { return new \Aura\Intl\BasicFormatter; },
				'intl'  => function() { return new \Aura\Intl\IntlFormatter; },
			]),
			new TranslatorFactory,
			static::$_defaultLocale
		);

		static::attachDefaults($translators);
		return static::$_collection = $translators;
	}

	public static function translator($package = 'default', $locale = null, callable $loader = null) {
		if ($loader !== null) {
			$packages = $translators->getPackages();
			$locale = $locale ?: static::$_defaultLocale;
			$packages->set($package, $locale, $loader);
			return;
		}

		return static::translators()->get($package);
	}

	public static function attachDefaults(TranslatorLocator $translators) {
		$packages = $translators->getPackages();
		$packages->set('default', static::$_defaultLocale, function() {
			$package = new Package;
			$package->setMessages([
				'FOO' => 'The text for "foo."',
				'BAR' => 'The text for "bar."'
			]);
			return $package;
		});
	}

/**
 * Used by the translation functions in basics.php
 * Returns a translated string based on current language and translation files stored in locale folder
 *
 * @param string $singular String to translate
 * @param string $plural Plural string (if any)
 * @param string $domain Domain The domain of the translation. Domains are often used by plugin translations.
 *    If null, the default domain will be used.
 * @param int $count Count Count is used with $plural to choose the correct plural form.
 * @param string $language Language to translate string to.
 *    If null it checks for language in session followed by Config.language configuration variable.
 * @return string translated string.
 * @throws \Cake\Error\Exception When '' is provided as a domain.
 */
	public static function translate($singular, $plural = null, $domain = null, $count = null, $language = null) {
		
	}

/**
 * Clears the domains internal data array. Useful for testing i18n.
 *
 * @return void
 */
	public static function clear() {
		$self = I18n::getInstance();
		$self->_domains = array();
	}

/**
 * Get the loaded domains cache.
 *
 * @return array
 */
	public static function domains() {
		$self = I18n::getInstance();
		return $self->_domains;
	}

}
