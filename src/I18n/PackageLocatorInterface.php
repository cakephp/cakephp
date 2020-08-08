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
 * @copyright     Copyright (c) 2017-2020, Aura for PHP
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\I18n;

/**
 * Package locator interface.
 */
interface PackageLocatorInterface
{
    /**
     * Sets a Package object.
     *
     * @param string $name The package name.
     *
     * @param string $locale The locale for the package.
     *
     * @param callable $spec A callable that returns a Package object.
     * @return void
     */
    public function set($name, $locale, callable $spec);

    /**
     * Gets a Package object.
     *
     * @param string $name The package name.
     *
     * @param string $locale The locale for the package.
     * @return \Cake\I18n\Package
     */
    public function get($name, $locale);
}
