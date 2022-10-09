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
 * @copyright     Copyright (c) 2017 Aura for PHP
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

use Cake\I18n\Exception\I18nException;

/**
 * A ServiceLocator implementation for loading and retaining package objects.
 *
 * @internal
 */
class PackageLocator
{
    /**
     * A registry of packages.
     *
     * Unlike many other registries, this one is two layers deep. The first
     * key is a package name, the second key is a locale code, and the value
     * is a callable that returns a Package object for that name and locale.
     *
     * @var array<string, array<string, \Cake\I18n\Package|callable>>
     */
    protected array $registry = [];

    /**
     * Tracks whether a registry entry has been converted from a
     * callable to a Package object.
     *
     * @var array<string, array<string, bool>>
     */
    protected array $converted = [];

    /**
     * Constructor.
     *
     * @param array<string, array<string, \Cake\I18n\Package|callable>> $registry A registry of packages.
     * @see PackageLocator::$registry
     */
    public function __construct(array $registry = [])
    {
        foreach ($registry as $name => $locales) {
            foreach ($locales as $locale => $spec) {
                $this->set($name, $locale, $spec);
            }
        }
    }

    /**
     * Sets a Package loader.
     *
     * @param string $name The package name.
     * @param string $locale The locale for the package.
     * @param \Cake\I18n\Package|callable $spec A callable that returns a package or Package instance.
     * @return void
     */
    public function set(string $name, string $locale, Package|callable $spec): void
    {
        $this->registry[$name][$locale] = $spec;
        $this->converted[$name][$locale] = $spec instanceof Package;
    }

    /**
     * Gets a Package object.
     *
     * @param string $name The package name.
     * @param string $locale The locale for the package.
     * @return \Cake\I18n\Package
     */
    public function get(string $name, string $locale): Package
    {
        if (!isset($this->registry[$name][$locale])) {
            throw new I18nException(sprintf('Package `%s` with locale `%s` is not registered.', $name, $locale));
        }

        if (!$this->converted[$name][$locale]) {
            $func = $this->registry[$name][$locale];
            assert(is_callable($func));
            $this->registry[$name][$locale] = $func();
            $this->converted[$name][$locale] = true;
        }

        /** @var \Cake\I18n\Package */
        return $this->registry[$name][$locale];
    }

    /**
     * Check if a Package object for given name and locale exists in registry.
     *
     * @param string $name The package name.
     * @param string $locale The locale for the package.
     * @return bool
     */
    public function has(string $name, string $locale): bool
    {
        return isset($this->registry[$name][$locale]);
    }
}
