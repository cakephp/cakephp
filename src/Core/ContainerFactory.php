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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Container\Container;
use Cake\Container\ContainerInterface;
use InvalidArgumentException;

/**
 * Factory for creating the application's dependency injection container.
 *
 * By default, a plain `Cake\Container\Container` is created. Applications
 * that need a custom container implementation (for example, one with
 * extra bootstrapping or a different underlying implementation) can set
 * `Configure::write('App.container', MyContainer::class)` to a class name
 * implementing `Cake\Container\ContainerInterface`.
 */
class ContainerFactory
{
    /**
     * Create a new container instance based on configuration.
     *
     * @return \Cake\Container\ContainerInterface
     */
    public static function create(): ContainerInterface
    {
        $class = Configure::read('App.container') ?? Container::class;

        if (!is_string($class) || !is_subclass_of($class, ContainerInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                '`App.container` must be a class name implementing `%s`.',
                ContainerInterface::class,
            ));
        }

        /** @var \Cake\Container\ContainerInterface */
        return new $class();
    }
}
