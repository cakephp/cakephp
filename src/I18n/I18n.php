<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\Cache\Cache;
use Cake\Cache\Exception\InvalidArgumentException;
use Cake\I18n\Exception\I18nException;
use Cake\I18n\Formatter\IcuFormatter;
use Cake\I18n\Formatter\SprintfFormatter;
use Locale;
use function Cake\Core\deprecationWarning;

/**
 * I18n handles translation of Text and time format strings.
 */
class I18n
{
    /**
     * Default locale
     *
     * @var string
     */
    public const DEFAULT_LOCALE = 'en_US';

    /**
     * The translators collection
     *
     * @var \Cake\I18n\TranslatorRegistry|null
     */
    protected static ?TranslatorRegistry $_collection = null;

    /**
     * The environment default locale
     *
     * @var string|null
     */
    protected static ?string $_defaultLocale = null;

    /**
     * Returns the translators collection instance. It can be used
     * for getting specific translators based of their name and locale
     * or to configure some aspect of future translations that are not yet constructed.
     *
     * @return \Cake\I18n\TranslatorRegistry The translator collection.
     */
    public static function translators(): TranslatorRegistry
    {
        if (static::$_collection !== null) {
            return static::$_collection;
        }

        static::$_collection = new TranslatorRegistry(
            new PackageLocator(),
            new FormatterLocator([
                'default' => IcuFormatter::class,
                'sprintf' => SprintfFormatter::class,
            ]),
            static::getLocale(),
        );

        if (class_exists(Cache::class)) {
            try {
                $pool = Cache::pool('_cake_translations_');
            } catch (InvalidArgumentException) {
                $pool = Cache::pool('_cake_core_');
                deprecationWarning(
                    '5.1.0',
                    'Cache config `_cake_core_` is deprecated. Use `_cake_translations_` instead',
                );
            }
            static::$_collection->setCacher($pool);
        }

        return static::$_collection;
    }

    /**
     * Sets a translator.
     *
     * Configures future translators, this is achieved by passing a callable
     * as the last argument of this function.
     *
     * ### Example:
     *
     * ```
     *  I18n::setTranslator('default', function () {
     *      $package = new \Cake\I18n\Package();
     *      $package->setMessages([
     *          'Cake' => 'Gâteau'
     *      ]);
     *      return $package;
     *  }, 'fr_FR');
     *
     *  $translator = I18n::getTranslator('default', 'fr_FR');
     *  echo $translator->translate('Cake');
     * ```
     *
     * You can also use the `Cake\I18n\MessagesFileLoader` class to load a specific
     * file from a folder. For example for loading a `my_translations.po` file from
     * the `resources/locales/custom` folder, you would do:
     *
     * ```
     * I18n::setTranslator(
     *  'default',
     *  new MessagesFileLoader('my_translations', 'custom', 'po'),
     *  'fr_FR'
     * );
     * ```
     *
     * @param string $name The domain of the translation messages.
     * @param callable $loader A callback function or callable class responsible for
     *   constructing a translations package instance.
     * @param string|null $locale The locale for the translator.
     * @return void
     */
    public static function setTranslator(string $name, callable $loader, ?string $locale = null): void
    {
        $locale = $locale ?: static::getLocale();

        $translators = static::translators();
        $loader = $translators->setLoaderFallback($name, $loader);
        $packages = $translators->getPackages();
        $packages->set($name, $locale, $loader);
    }

    /**
     * Returns an instance of a translator that was configured for the name and locale.
     *
     * If no locale is passed then it takes the value returned by the `getLocale()` method.
     *
     * @param string $name The domain of the translation messages.
     * @param string|null $locale The locale for the translator.
     * @return \Cake\I18n\Translator The configured translator.
     * @throws \Cake\I18n\Exception\I18nException
     */
    public static function getTranslator(string $name = 'default', ?string $locale = null): Translator
    {
        $translators = static::translators();

        $currentLocale = null;
        if ($locale) {
            $currentLocale = $translators->getLocale();
            $translators->setLocale($locale);
        }

        $translator = $translators->get($name);
        if ($translator === null) {
            throw new I18nException(sprintf(
                'Translator for domain `%s` could not be found.',
                $name,
            ));
        }

        if ($currentLocale !== null) {
            $translators->setLocale($currentLocale);
        }

        return $translator;
    }

    /**
     * Registers a callable object that can be used for creating new translator
     * instances for the same translations domain. Loaders will be invoked whenever
     * a translator object is requested for a domain that has not been configured or
     * loaded already.
     *
     * Registering loaders is useful when you need to lazily use translations in multiple
     * different locales for the same domain, and don't want to use the built-in
     * translation service based of `gettext` files.
     *
     * Loader objects will receive two arguments: The domain name that needs to be
     * built, and the locale that is requested. These objects can assemble the messages
     * from any source, but must return an `Cake\I18n\Package` object.
     *
     * ### Example:
     *
     * ```
     *  use Cake\I18n\MessagesFileLoader;
     *  I18n::config('my_domain', function ($name, $locale) {
     *      // Load resources/locales/$locale/filename.po
     *      $fileLoader = new MessagesFileLoader('filename', $locale, 'po');
     *      return $fileLoader();
     *  });
     * ```
     *
     * You can also assemble the package object yourself:
     *
     * ```
     *  use Cake\I18n\Package;
     *  I18n::config('my_domain', function ($name, $locale) {
     *      $package = new Package('default');
     *      $messages = (...); // Fetch messages for locale from external service.
     *      $package->setMessages($message);
     *      $package->setFallback('default');
     *      return $package;
     *  });
     * ```
     *
     * @param string $name The name of the translator to create a loader for
     * @param callable $loader A callable object that should return a Package
     * instance to be used for assembling a new translator.
     * @return void
     */
    public static function config(string $name, callable $loader): void
    {
        static::translators()->registerLoader($name, $loader);
    }

    /**
     * Sets the default locale to use for future translator instances.
     * This also affects the `intl.default_locale` PHP setting.
     *
     * @param string $locale The name of the locale to set as default.
     * @return void
     */
    public static function setLocale(string $locale): void
    {
        static::getDefaultLocale();
        Locale::setDefault($locale);
        if (isset(static::$_collection)) {
            static::translators()->setLocale($locale);
        }
    }

    /**
     * Will return the currently configure locale as stored in the
     * `intl.default_locale` PHP setting.
     *
     * @return string The name of the default locale.
     */
    public static function getLocale(): string
    {
        static::getDefaultLocale();
        $current = Locale::getDefault();
        if ($current === '') {
            $current = static::DEFAULT_LOCALE;
            Locale::setDefault($current);
        }

        return $current;
    }

    /**
     * Returns the default locale.
     *
     * This returns the default locale before any modifications, i.e.
     * the value as stored in the `intl.default_locale` PHP setting before
     * any manipulation by this class.
     *
     * @return string
     */
    public static function getDefaultLocale(): string
    {
        return static::$_defaultLocale ??= Locale::getDefault() ?: static::DEFAULT_LOCALE;
    }

    /**
     * Returns the currently configured default formatter.
     *
     * @return string The name of the formatter.
     */
    public static function getDefaultFormatter(): string
    {
        return static::translators()->defaultFormatter();
    }

    /**
     * Sets the name of the default messages formatter to use for future
     * translator instances. By default, the `default` and `sprintf` formatters
     * are available.
     *
     * @param string $name The name of the formatter to use.
     * @return void
     */
    public static function setDefaultFormatter(string $name): void
    {
        static::translators()->defaultFormatter($name);
    }

    /**
     * Set if the domain fallback is used.
     *
     * @param bool $enable flag to enable or disable fallback
     * @return void
     */
    public static function useFallback(bool $enable = true): void
    {
        static::translators()->useFallback($enable);
    }

    /**
     * Destroys all translator instances and creates a new empty translations
     * collection.
     *
     * @return void
     */
    public static function clear(): void
    {
        static::$_collection = null;
    }
}
