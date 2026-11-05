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
 * @since         4.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use League\Container\Container as LeagueContainer;

/**
 * Dependency Injection container
 *
 * Based on the container out of League\Container
 */
class Container extends LeagueContainer implements ContainerInterface
{
    /**
     * @template T of object
     * @param class-string<T>|string $id Identifier of the entry to look for.
     * @return ($id is class-string<T> ? T : mixed)
     */
    public function get(string $id)
    {
        return parent::get($id);
    }
}
