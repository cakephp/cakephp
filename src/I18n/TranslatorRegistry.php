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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\Cache\CacheEngineInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Constructs and stores instances of translators that can be
 * retrieved by name and locale.
 */
class TranslatorRegistry
{
    /**
     * Fallback loader name.
     *
     * @var string
     */
    public const FALLBACK_LOADER = '_fallback';

    /**
     * A registry to retain translator objects.
     *
     * @var array<string, array<string, \Cake\I18n\Translator>>
     */
    protected array $registry = [];

    /**
     * The current locale code.
     *
     * @var string
     */
    protected string $locale;

    /**
     * A package locator.
     *
     * @var \Cake\I18n\PackageLocator
     */
    protected PackageLocator $packages;

    /**
     * A formatter locator.
     *
     * @var \Cake\I18n\FormatterLocator
     */
    protected FormatterLocator $formatters;

    /**
     * A list of loader functions indexed by domain name. Loaders are
     * callables that are invoked as a default for building translation
     * packages where none can be found for the combination of translator
     * name and locale.
     *
     * @var array<callable>
     */
    protected array $_loaders = [];

    /**
     * The name of the default formatter to use for newly created
     * translators from the fallback loader
     *
     * @var string
     */
    protected string $_defaultFormatter = 'default';

    /**
     * Use fallback-domain for translation loaders.
     *
     * @var bool
     */
    protected bool $_useFallback = true;

    /**
     * A CacheEngine object that is used to remember translator across
     * requests.
     *
     * @var (\Psr\SimpleCache\CacheInterface&\Cake\Cache\CacheEngineInterface)|null
     */
    protected $_cacher;

    /**
     * Constructor.
     *
     * @param \Cake\I18n\PackageLocator $packages The package locator.
     * @param \Cake\I18n\FormatterLocator $formatters The formatter locator.
     * @param string $locale The default locale code to use.
     */
    public function __construct(
        PackageLocator $packages,
        FormatterLocator $formatters,
        string $locale
    ) {
        $this->packages = $packages;
        $this->formatters = $formatters;
        $this->setLocale($locale);

        $this->registerLoader(static::FALLBACK_LOADER, function ($name, $locale) {
            $loader = new ChainMessagesLoader([
                new MessagesFileLoader($name, $locale, 'mo'),
                new MessagesFileLoader($name, $locale, 'po'),
            ]);

            $formatter = $name === 'cake' ? 'default' : $this->_defaultFormatter;
            $package = $loader();
            $package->setFormatter($formatter);

            return $package;
        });
    }

    /**
     * Sets the default locale code.
     *
     * @param string $locale The new locale code.
     * @return void
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Returns the default locale code.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Returns the translator packages
     *
     * @return \Cake\I18n\PackageLocator
     */
    public function getPackages(): PackageLocator
    {
        return $this->packages;
    }

    /**
     * An object of type FormatterLocator
     *
     * @return \Cake\I18n\FormatterLocator
     */
    public function getFormatters(): FormatterLocator
    {
        return $this->formatters;
    }

    /**
     * Sets the CacheEngine instance used to remember translators across
     * requests.
     *
     * @param \Psr\SimpleCache\CacheInterface&\Cake\Cache\CacheEngineInterface $cacher The cacher instance.
     * @return void
     */
    public function setCacher(CacheInterface&CacheEngineInterface $cacher): void
    {
        $this->_cacher = $cacher;
    }

    /**
     * Gets a translator from the registry by package for a locale.
     *
     * @param string $name The translator package to retrieve.
     * @param string|null $locale The locale to use; if empty, uses the default
     * locale.
     * @return \Cake\I18n\Translator|null A translator object.
     * @throws \Cake\I18n\Exception\I18nException If no translator with that name could be found
     * for the given locale.
     */
    public function get(string $name, ?string $locale = null): ?Translator
    {
        $locale ??= $this->getLocale();

        if (isset($this->registry[$name][$locale])) {
            return $this->registry[$name][$locale];
        }

        if ($this->_cacher === null) {
            return $this->registry[$name][$locale] = $this->_getTranslator($name, $locale);
        }

        // Cache keys cannot contain / if they go to file engine.
        $keyName = str_replace('/', '.', $name);
        $key = "translations.{$keyName}.{$locale}";
        /** @var \Cake\I18n\Translator|null $translator */
        $translator = $this->_cacher->get($key);

        // PHP <8.1 does not correctly garbage collect strings created
        // by unserialized arrays.
        gc_collect_cycles();

        if (!$translator) {
            $translator = $this->_getTranslator($name, $locale);
            $this->_cacher->set($key, $translator);
        }

        return $this->registry[$name][$locale] = $translator;
    }

    /**
     * Gets a translator from the registry by package for a locale.
     *
     * @param string $name The translator package to retrieve.
     * @param string $locale The locale to use; if empty, uses the default
     * locale.
     * @return \Cake\I18n\Translator A translator object.
     */
    protected function _getTranslator(string $name, string $locale): Translator
    {
        if ($this->packages->has($name, $locale)) {
            return $this->createInstance($name, $locale);
        }

        if (isset($this->_loaders[$name])) {
            $package = $this->_loaders[$name]($name, $locale);
        } else {
            $package = $this->_loaders[static::FALLBACK_LOADER]($name, $locale);
        }

        $package = $this->setFallbackPackage($name, $package);
        $this->packages->set($name, $locale, $package);

        return $this->createInstance($name, $locale);
    }

    /**
     * Create translator instance.
     *
     * @param string $name The translator package to retrieve.
     * @param string $locale The locale to use; if empty, uses the default locale.
     * @return \Cake\I18n\Translator A translator object.
     */
    protected function createInstance(string $name, string $locale): Translator
    {
        $package = $this->packages->get($name, $locale);
        $fallback = $package->getFallback();
        if ($fallback !== null) {
            $fallback = $this->get($fallback, $locale);
        }
        $formatter = $this->formatters->get($package->getFormatter());

        return new Translator($locale, $package, $formatter, $fallback);
    }

    /**
     * Registers a loader function for a package name that will be used as a fallback
     * in case no package with that name can be found.
     *
     * Loader callbacks will get as first argument the package name and the locale as
     * the second argument.
     *
     * @param string $name The name of the translator package to register a loader for
     * @param callable $loader A callable object that should return a Package
     * @return void
     */
    public function registerLoader(string $name, callable $loader): void
    {
        $this->_loaders[$name] = $loader;
    }

    /**
     * Sets the name of the default messages formatter to use for future
     * translator instances.
     *
     * If called with no arguments, it will return the currently configured value.
     *
     * @param string|null $name The name of the formatter to use.
     * @return string The name of the formatter.
     */
    public function defaultFormatter(?string $name = null): string
    {
        if ($name === null) {
            return $this->_defaultFormatter;
        }

        return $this->_defaultFormatter = $name;
    }

    /**
     * Set if the default domain fallback is used.
     *
     * @param bool $enable flag to enable or disable fallback
     * @return void
     */
    public function useFallback(bool $enable = true): void
    {
        $this->_useFallback = $enable;
    }

    /**
     * Set fallback domain for package.
     *
     * @param string $name The name of the package.
     * @param \Cake\I18n\Package $package Package instance
     * @return \Cake\I18n\Package
     */
    public function setFallbackPackage(string $name, Package $package): Package
    {
        if ($package->getFallback()) {
            return $package;
        }

        $fallbackDomain = null;
        if ($this->_useFallback && $name !== 'default') {
            $fallbackDomain = 'default';
        }

        $package->setFallback($fallbackDomain);

        return $package;
    }

    /**
     * Set domain fallback for loader.
     *
     * @param string $name The name of the loader domain
     * @param callable $loader invokable loader
     * @return callable loader
     */
    public function setLoaderFallback(string $name, callable $loader): callable
    {
        $fallbackDomain = 'default';
        if (!$this->_useFallback || $name === $fallbackDomain) {
            return $loader;
        }

        return function () use ($loader, $fallbackDomain) {
            /** @var \Cake\I18n\Package $package */
            $package = $loader();
            if (!$package->getFallback()) {
                $package->setFallback($fallbackDomain);
            }

            return $package;
        };
    }
}
