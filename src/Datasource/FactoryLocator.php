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
 * @since         3.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource;

use Cake\ORM\TableRegistry;
use InvalidArgumentException;

class FactoryLocator
{
    /**
     * A list of model factory functions.
     *
     * @var callable[]
     */
    protected static $_modelFactories = [];

    /**
     * Register a callable to generate repositories of a given type.
     *
     * @param string $type The name of the repository type the factory function is for.
     * @param callable $factory The factory function used to create instances.
     * @return void
     */
    public static function add($type, callable $factory)
    {
        static::$_modelFactories[$type] = $factory;
    }

    /**
     * Drop a model factory.
     *
     * @param string $type The name of the repository type to drop the factory for.
     * @return void
     */
    public static function drop($type)
    {
        unset(static::$_modelFactories[$type]);
    }

    /**
     * Get the factory for the specified repository type.
     *
     * @param string $type The repository type to get the factory for.
     * @throws InvalidArgumentException If the specified repository type has no factory.
     * @return callable The factory for the repository type.
     */
    public static function get($type)
    {
        if (!isset(static::$_modelFactories['Table'])) {
            static::$_modelFactories['Table'] = [TableRegistry::locator(), 'get'];
        }

        if (!isset(static::$_modelFactories[$type])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown repository type "%s". Make sure you register a type before trying to use it.',
                $type
            ));
        }

        return static::$_modelFactories[$type];
    }
}
