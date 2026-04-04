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

use Cake\Container\Container as CakeContainer;

/**
 * Factory for creating the appropriate DI container based on configuration.
 *
 * The container selection is controlled by `Configure::read('App.container')`:
 * - 'cake': Uses the built-in CakePHP container (wrapped in CakeContainerBridge)
 * - Any other value or not set: Uses the League container (default, backwards compatible)
 *
 * When using the 'cake' container, applications may need to adjust code that
 * uses League-specific features. The basic container API (`add`, `addShared`,
 * `get`, `has`, `addServiceProvider`) works identically in both containers.
 */
class ContainerFactory
{
    /**
     * Create a new container instance based on configuration.
     *
     * @return \Cake\Core\ContainerInterface
     */
    public static function create(): ContainerInterface
    {
        return match (Configure::read('App.container')) {
            'cake' => new CakeContainerBridge(new CakeContainer()),
            default => new Container(),
        };
    }
}
