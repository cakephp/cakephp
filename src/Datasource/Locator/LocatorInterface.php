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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Datasource\Locator;

use Cake\Datasource\RepositoryInterface;

/**
 * Registries for repository objects should implement this interface.
 *
 * @template TRepo of \Cake\Datasource\RepositoryInterface
 */
interface LocatorInterface
{
    /**
     * Get a repository instance from the registry.
     *
     * @param string $alias The alias name you want to get.
     * @param array<string, mixed> $options The options you want to build the table with.
     * @return TRepo
     * @throws \RuntimeException When trying to get alias for which instance
     *   has already been created with different options.
     */
    public function get(string $alias, array $options = []): RepositoryInterface;

    /**
     * Set a repository instance.
     *
     * @param string $alias The alias to set.
     * @param TRepo $repository The repository to set.
     * @return TRepo
     */
    public function set(string $alias, RepositoryInterface $repository): RepositoryInterface;

    /**
     * Check to see if an instance exists in the registry.
     *
     * @param string $alias The alias to check for.
     * @return bool
     */
    public function exists(string $alias): bool;

    /**
     * Removes an repository instance from the registry.
     *
     * @param string $alias The alias to remove.
     * @return void
     */
    public function remove(string $alias): void;

    /**
     * Clears the registry of configuration and instances.
     *
     * @return void
     */
    public function clear(): void;
}
