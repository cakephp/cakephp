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
use Cake\Container\ServiceProvider\ServiceProviderInterface as CakeServiceProviderInterface;
use InvalidArgumentException;
use League\Container\Definition\DefinitionInterface;
use League\Container\Inflector\InflectorInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Bridge class that wraps CakePHP's built-in container to satisfy ContainerInterface.
 *
 * This minimal bridge enables using the CakePHP container while maintaining
 * backwards compatibility with code expecting ContainerInterface.
 */
class CakeContainerBridge implements ContainerInterface
{
    /**
     * @param \Cake\Container\Container $container The wrapped CakePHP container
     */
    public function __construct(
        protected CakeContainer $container,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function add(string $id, mixed $concrete = null, bool $overwrite = false): DefinitionInterface
    {
        $definition = $this->container->add($id, $concrete);

        return new CakeDefinitionBridge($definition, $this);
    }

    /**
     * @inheritDoc
     */
    public function addServiceProvider(ServiceProviderInterface $provider): static
    {
        if (!$provider instanceof CakeServiceProviderInterface) {
            throw new InvalidArgumentException(sprintf(
                'Service provider must implement `%s` when using the CakePHP container',
                CakeServiceProviderInterface::class,
            ));
        }
        $this->container->addServiceProvider($provider);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addShared(string $id, mixed $concrete = null, bool $overwrite = false): DefinitionInterface
    {
        $definition = $this->container->addShared($id, $concrete);

        return new CakeDefinitionBridge($definition, $this);
    }

    /**
     * @inheritDoc
     */
    public function extend(string $id): DefinitionInterface
    {
        $definition = $this->container->extend($id);

        return new CakeDefinitionBridge($definition, $this);
    }

    /**
     * @inheritDoc
     */
    public function getNew(string $id): mixed
    {
        return $this->container->getNew($id);
    }

    /**
     * @inheritDoc
     */
    public function inflector(string $type, ?callable $callback = null): InflectorInterface
    {
        $inflector = $this->container->inflector($type, $callback);

        return new CakeInflectorBridge($inflector);
    }

    /**
     * @inheritDoc
     */
    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * @inheritDoc
     */
    public function delegate(PsrContainerInterface $container): PsrContainerInterface
    {
        $this->container->delegate($container);

        return $this;
    }
}
