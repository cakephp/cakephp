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
     * Configuration for aliases to be used when creating instances.
     *
     * @var array
     */
    protected $_config = [];

    /**
     * Instances that belong to the registry.
     *
     * @var \Cake\Datasource\RepositoryInterface[]
     */
    protected $_instances = [];

    /**
     * Contains a list of options that were passed to get() method.
     *
     * @var array
     */
    protected $_options = [];

    /**
     * @inheritDoc
     */
    public function setConfig($alias, $options = null)
    {
        if (!is_string($alias)) {
            $this->_config = $alias;

            return $this;
        }

        if (isset($this->_instances[$alias])) {
            throw new RuntimeException(sprintf(
                'You cannot configure "%s", it has already been constructed.',
                $alias
            ));
        }

        $this->_config[$alias] = $options;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(?string $alias = null): array
    {
        if ($alias === null) {
            return $this->_config;
        }

        return $this->_config[$alias] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function exists(string $alias): bool
    {
        return isset($this->_instances[$alias]);
    }

    /**
     * @inheritDoc
     */
    public function set(string $alias, RepositoryInterface $repository)
    {
        return $this->_instances[$alias] = $repository;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $alias): void
    {
        unset(
            $this->_instances[$alias],
            $this->_options[$alias]
        );
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->_instances = [];
        $this->_config = [];
        $this->_options = [];
    }
}
