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
use RuntimeException;

/**
 * Provides an abstract registry/factory for repository objects.
 */
abstract class AbstractLocator implements LocatorInterface
{
    /**
     * Instances that belong to the registry.
     *
     * @var \Cake\Datasource\RepositoryInterface[]
     */
    protected $instances = [];

    /**
     * Contains a list of options that were passed to get() method.
     *
     * @var array
     */
    protected $options = [];

    /**
     * @inheritDoc
     */
    public function get(string $alias, array $options = [])
    {
        if (isset($this->instances[$alias])) {
            if (!empty($options) && $this->options[$alias] !== $options) {
                throw new RuntimeException(sprintf(
                    'You cannot configure "%s", it already exists in the registry.',
                    $alias
                ));
            }

            return $this->instances[$alias];
        }

        $this->options[$alias] = $options;

        return $this->instances[$alias] = $this->createInstance($alias, $options);
    }

    /**
     * Create an instance of a given classname.
     *
     * @param string $alias Repository alias.
     * @param array $options The options you want to build the instance with.
     * @return \Cake\Datasource\RepositoryInterface
     */
    abstract protected function createInstance(string $alias, array $options);

    /**
     * @inheritDoc
     */
    public function set(string $alias, RepositoryInterface $repository)
    {
        return $this->instances[$alias] = $repository;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $alias): bool
    {
        return isset($this->instances[$alias]);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $alias): void
    {
        unset(
            $this->instances[$alias],
            $this->options[$alias]
        );
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->instances = [];
        $this->options = [];
    }
}
