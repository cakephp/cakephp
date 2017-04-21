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

use Aura\Intl\FormatterLocator;
use Aura\Intl\PackageLocator;
use Cake\Cache\Cache;
use Cake\I18n\Formatter\IcuFormatter;
use Cake\I18n\Formatter\SprintfFormatter;
use Locale;

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
    const DEFAULT_LOCALE = 'en_US';

    /**
     * The translators collection
     *
     * @var \Cake\I18n\TranslatorRegistry|null
     */
    protected static $_collection;

    /**
     * The environment default locale
     *
     * @var string
     */
    protected static $_defaultLocale;

    /**
     * Returns the translators collection instance. It can be used
     * for getting specific translators based of their name and locale
     * or to configure some aspect of future translations that are not yet constructed.
     *
     * @return \Cake\I18n\TranslatorRegistry The translators collection.
     */
    public static function translators()
    {
        if (static::$_collection !== null) {
            return static::$_collection;
        }

        static::$_collection = new TranslatorRegistry(
            new PackageLocator,
            new FormatterLocator([
                'sprintf' => function () {
                    return new SprintfFormatter();
                },
                'default' => function () {
                    return new IcuFormatter();
                },
            ]),
            new TranslatorFactory,
            static::locale()
        );

        if (class_exists('Cake\Cache\Cache')) {
            static::$_collection->setCacher(Cache::engine('_cake_core_'));
        }

        return static::$_collection;
    }

    /**
     * Returns an instance of a translator that was configured for the name and passed
     * locale. If no locale is passed then it takes the value returned by the `locale()` method.
     *
     * This method can be used to configure future translators, this is achieved by passing a callable
     * as the last argument of this function.
     *
     * ### Example:
     *
     * ```
     *  I18n::translator('default', 'fr_FR', function () {
     *      $package = new \Aura\Intl\Package();
     *      $package->setMessages([
     *          'Cake' => 'Gâteau'
     *      ]);
     *      return $package;
     *  });
     *
     *  $translator = I18n::translator('default', 'fr_FR');
     *  echo $translator->translate('Cake');
     * ```
     *
     * You can also use the `Cake\I18n\MessagesFileLoader` class to load a specific
     * file from a folder. For example for loading a `my_translations.po` file from
     * the `src/Locale/custom` folder, you would do:
     *
     * ```
     * I18n::translator(
     *  'default',
     *  'fr_FR',
     *  new MessagesFileLoader('my_translations', 'custom', 'po');
     * );
     * ```
     *
     * @param string $name The domain of the translation messages.
     * @param string|null $locale The locale for the translator.
     * @param callable|null $loader A callback function or callable class responsible for
     * constructing a translations package instance.
     * @return \Aura\Intl\TranslatorInterface|null The configured translator.
     */
    public static function translator($name = 'default', $locale = null, callable $loader = null)
    {
        if ($loader !== null) {
            $locale = $locale ?: static::locale();

            $loader = static::translators()->setLoaderFallback($name, $loader);

            $packages = static::translators()->getPackages();
            $packages->set($name, $locale, $loader);

            return null;
        }

        $translators = static::translators();

        if ($locale) {
            $currentLocale = $translators->getLocale();
            static::translators()->setLocale($locale);
        }

        $translator = $translators->get($name);

        if (isset($currentLocale)) {
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
     * from any source, but must return an `Aura\Intl\Package` object.
     *
     * ### Example:
     *
     * ```
     *  use Cake\I18n\MessagesFileLoader;
     *  I18n::config('my_domain', function ($name, $locale) {
     *      // Load src/Locale/$locale/filename.po
     *      $fileLoader = new MessagesFileLoader('filename', $locale, 'po');
     *      return $fileLoader();
     *  });
     * ```
     *
     * You can also assemble the package object yourself:
     *
     * ```
     *  use Aura\Intl\Package;
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
    public static function config($name, callable $loader)
    {
        static::translators()->registerLoader($name, $loader);
    }

    /**
     * Sets the default locale to use for future translator instances.
     * This also affects the `intl.default_locale` PHP setting.
     *
     * When called with no arguments it will return the currently configure
     * locale as stored in the `intl.default_locale` PHP setting.
     *
     * @param string|null $locale The name of the locale to set as default.
     * @return string|null The name of the default locale.
     */
    public static function locale($locale = null)
    {
        static::defaultLocale();

        if (!empty($locale)) {
            Locale::setDefault($locale);
            if (isset(static::$_collection)) {
                static::translators()->setLocale($locale);
            }

            return null;
        }

        $current = Locale::getDefault();
        if ($current === '') {
            $current = static::DEFAULT_LOCALE;
            Locale::setDefault($current);
        }

        return $current;
    }

    /**
     * This returns the default locale before any modifications, i.e.
     * the value as stored in the `intl.default_locale` PHP setting before
     * any manipulation by this class.
     *
     * @return string
     */
    public static function defaultLocale()
    {
        if (static::$_defaultLocale === null) {
            static::$_defaultLocale = Locale::getDefault() ?: static::DEFAULT_LOCALE;
        }

        return static::$_defaultLocale;
    }

    /**
     * Sets the name of the default messages formatter to use for future
     * translator instances. By default the `default` and `sprintf` formatters
     * are available.
     *
     * If called with no arguments, it will return the currently configured value.
     *
     * @param string|null $name The name of the formatter to use.
     * @return string The name of the formatter.
     */
    public static function defaultFormatter($name = null)
    {
        return static::translators()->defaultFormatter($name);
    }

    /**
     * Set if the domain fallback is used.
     *
     * @param bool $enable flag to enable or disable fallback
     * @return void
     */
    public static function useFallback($enable = true)
    {
        static::translators()->useFallback($enable);
    }

    /**
     * Destroys all translator instances and creates a new empty translations
     * collection.
     *
     * @return void
     */
    public static function clear()
    {
        static::$_collection = null;
    }
}
