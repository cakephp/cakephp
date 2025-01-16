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
 * @since         3.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Cake\Datasource\Locator\LocatorInterface;
use InvalidArgumentException;

/**
 * Class FactoryLocator
 */
class FactoryLocator
{
    /**
     * A list of model factory functions.
     *
     * @var array<string, \Cake\Datasource\Locator\LocatorInterface>
     */
    protected static array $_modelFactories = [];

    /**
     * Register a locator to return repositories of a given type.
     *
     * @param string $type The name of the repository type the factory function is for.
     * @param \Cake\Datasource\Locator\LocatorInterface $factory The factory function used to create instances.
     * @return void
     */
    public static function add(string $type, LocatorInterface $factory): void
    {
        static::$_modelFactories[$type] = $factory;
    }

    /**
     * Drop a model factory.
     *
     * @param string $type The name of the repository type to drop the factory for.
     * @return void
     */
    public static function drop(string $type): void
    {
        unset(static::$_modelFactories[$type]);
    }

    /**
     * Get the factory for the specified repository type.
     *
     * @param string $type The repository type to get the factory for.
     * @throws \InvalidArgumentException If the specified repository type has no factory.
     * @return \Cake\Datasource\Locator\LocatorInterface The factory for the repository type.
     */
    public static function get(string $type): LocatorInterface
    {
        if (isset(static::$_modelFactories[$type])) {
            return static::$_modelFactories[$type];
        }

        throw new InvalidArgumentException(sprintf(
            'Unknown repository type `%s`. Make sure you register a type before trying to use it.',
            $type,
        ));
    }
}
