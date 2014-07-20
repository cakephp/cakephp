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

		return static::$_collection = $translators;
	}

	public static function translator($package = 'default', $locale = null, callable $loader = null) {
		if ($loader !== null) {
			$packages = static::translators()->getPackages();
			$locale = $locale ?: static::$_defaultLocale;
			$packages->set($package, $locale, $loader);
			return;
		}

		if ($locale) {
			static::translators()->setLocale($locale);
		}

		try {
			return static::translators()->get($package);
		} catch (LoadException $e) {
			return static::_fallbackTranslator($package, $locale);
		}
	}

	protected static function _fallbackTranslator($package, $locale) {
		$chain = new ChainMessagesLoader([
			new MessagesFileLoader($package, $locale, 'mo'),
			new MessagesFileLoader($package, $locale, 'po')
		]);
		static::translator($package, $locale, $chain);
		return static::translators()->get($package);
	}

}
