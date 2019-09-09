<?php
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

use Aura\Intl\Exception;
use Aura\Intl\FormatterLocator;
use Aura\Intl\PackageLocator;
use Aura\Intl\TranslatorLocator;
use Cake\Cache\CacheEngine;

/**
 * Constructs and stores instances of translators that can be
 * retrieved by name and locale.
 */
class TranslatorRegistry extends TranslatorLocator
{
    /**
     * A list of loader functions indexed by domain name. Loaders are
     * callables that are invoked as a default for building translation
     * packages where none can be found for the combination of translator
     * name and locale.
     *
     * @var callable[]
     */
    protected $_loaders = [];

    /**
     * Fallback loader name
     *
     * @var string
     */
    protected $_fallbackLoader = '_fallback';

    /**
     * The name of the default formatter to use for newly created
     * translators from the fallback loader
     *
     * @var string
     */
    protected $_defaultFormatter = 'default';

    /**
     * Use fallback-domain for translation loaders.
     *
     * @var bool
     */
    protected $_useFallback = true;

    /**
     * A CacheEngine object that is used to remember translator across
     * requests.
     *
     * @var \Cake\Cache\CacheEngine
     */
    protected $_cacher;

    /**
     * Constructor.
     *
     * @param \Aura\Intl\PackageLocator $packages The package locator.
     * @param \Aura\Intl\FormatterLocator $formatters The formatter locator.
     * @param \Cake\I18n\TranslatorFactory $factory A translator factory to
     *   create translator objects for the locale and package.
     * @param string $locale The default locale code to use.
     */
    public function __construct(
        PackageLocator $packages,
        FormatterLocator $formatters,
        TranslatorFactory $factory,
        $locale
    ) {
        parent::__construct($packages, $formatters, $factory, $locale);

        $this->registerLoader($this->_fallbackLoader, function ($name, $locale) {
            $chain = new ChainMessagesLoader([
                new MessagesFileLoader($name, $locale, 'mo'),
                new MessagesFileLoader($name, $locale, 'po')
            ]);

            // \Aura\Intl\Package by default uses formatter configured with key "basic".
            // and we want to make sure the cake domain always uses the default formatter
            $formatter = $name === 'cake' ? 'default' : $this->_defaultFormatter;
            $chain = function () use ($formatter, $chain) {
                $package = $chain();
                $package->setFormatter($formatter);

                return $package;
            };

            return $chain;
        });
    }

    /**
     * Sets the CacheEngine instance used to remember translators across
     * requests.
     *
     * @param \Cake\Cache\CacheEngine $cacher The cacher instance.
     * @return void
     */
    public function setCacher(CacheEngine $cacher)
    {
        $this->_cacher = $cacher;
    }

    /**
     * Gets a translator from the registry by package for a locale.
     *
     * @param string $name The translator package to retrieve.
     * @param string|null $locale The locale to use; if empty, uses the default
     * locale.
     * @return \Aura\Intl\TranslatorInterface|null A translator object.
     * @throws \Aura\Intl\Exception If no translator with that name could be found
     * for the given locale.
     */
    public function get($name, $locale = null)
    {
        if (!$name) {
            return null;
        }

        if ($locale === null) {
            $locale = $this->getLocale();
        }

        if (isset($this->registry[$name][$locale])) {
            return $this->registry[$name][$locale];
        }

        if (!$this->_cacher) {
            return $this->registry[$name][$locale] = $this->_getTranslator($name, $locale);
        }

        $key = "translations.$name.$locale";
        $translator = $this->_cacher->read($key);
        if (!$translator || !$translator->getPackage()) {
            $translator = $this->_getTranslator($name, $locale);
            $this->_cacher->write($key, $translator);
        }

        return $this->registry[$name][$locale] = $translator;
    }

    /**
     * Gets a translator from the registry by package for a locale.
     *
     * @param string $name The translator package to retrieve.
     * @param string|null $locale The locale to use; if empty, uses the default
     * locale.
     * @return \Aura\Intl\TranslatorInterface A translator object.
     */
    protected function _getTranslator($name, $locale)
    {
        try {
            return parent::get($name, $locale);
        } catch (Exception $e) {
        }

        if (!isset($this->_loaders[$name])) {
            $this->registerLoader($name, $this->_partialLoader());
        }

        return $this->_getFromLoader($name, $locale);
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
    public function registerLoader($name, callable $loader)
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
    public function defaultFormatter($name = null)
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
    public function useFallback($enable = true)
    {
        $this->_useFallback = $enable;
    }

    /**
     * Returns a new translator instance for the given name and locale
     * based of conventions.
     *
     * @param string $name The translation package name.
     * @param string $locale The locale to create the translator for.
     * @return \Aura\Intl\Translator
     */
    protected function _fallbackLoader($name, $locale)
    {
        return $this->_loaders[$this->_fallbackLoader]($name, $locale);
    }

    /**
     * Returns a function that can be used as a loader for the registerLoaderMethod
     *
     * @return callable
     */
    protected function _partialLoader()
    {
        return function ($name, $locale) {
            return $this->_fallbackLoader($name, $locale);
        };
    }

    /**
     * Registers a new package by passing the register loaded function for the
     * package name.
     *
     * @param string $name The name of the translator package
     * @param string $locale The locale that should be built the package for
     * @return \Aura\Intl\TranslatorInterface A translator object.
     */
    protected function _getFromLoader($name, $locale)
    {
        $loader = $this->_loaders[$name]($name, $locale);
        $package = $loader;

        if (!is_callable($loader)) {
            $loader = function () use ($package) {
                return $package;
            };
        }

        $loader = $this->setLoaderFallback($name, $loader);

        $this->packages->set($name, $locale, $loader);

        return parent::get($name, $locale);
    }

    /**
     * Set domain fallback for loader.
     *
     * @param string $name The name of the loader domain
     * @param callable $loader invokable loader
     * @return callable loader
     */
    public function setLoaderFallback($name, callable $loader)
    {
        $fallbackDomain = 'default';
        if (!$this->_useFallback || $name === $fallbackDomain) {
            return $loader;
        }
        $loader = function () use ($loader, $fallbackDomain) {
            /* @var \Aura\Intl\Package $package */
            $package = $loader();
            if (!$package->getFallback()) {
                $package->setFallback($fallbackDomain);
            }

            return $package;
        };

        return $loader;
    }
}
