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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core;

use Cake\Container\Container as BuiltInContainer;
use Cake\Container\Definition\DefinitionInterface as CakeDefinitionInterface;
use Cake\Container\Exception\ContainerException;
use Cake\Container\Inflector\InflectorInterface as CakeInflectorInterface;
use Cake\Container\ServiceProvider\ServiceProviderInterface as CakeServiceProviderInterface;
use League\Container\Definition\DefinitionInterface as LeagueDefinitionInterface;
use League\Container\Inflector\InflectorInterface as LeagueInflectorInterface;
use League\Container\ServiceProvider\ServiceProviderInterface as LeagueServiceProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Opt-in CakePHP container implementation exposed through the core container API.
 */
class CakeContainer extends BuiltInContainer implements ContainerInterface
{
    /**
     * @inheritDoc
     */
    public function add(string $id, mixed $concrete = null, bool $overwrite = false): CakeDefinitionInterface&LeagueDefinitionInterface
    {
        return $this->wrapDefinition(parent::add($id, $concrete, $overwrite));
    }

    /**
     * @inheritDoc
     */
    public function addShared(string $id, mixed $concrete = null, bool $overwrite = false): CakeDefinitionInterface&LeagueDefinitionInterface
    {
        return $this->wrapDefinition(parent::addShared($id, $concrete, $overwrite));
    }

    /**
     * @inheritDoc
     */
    public function addServiceProvider(mixed $provider): static
    {
        if ($provider instanceof LeagueServiceProviderInterface) {
            $provider = new ContainerServiceProviderAdapter($provider, $this);
        } elseif (!$provider instanceof CakeServiceProviderInterface) {
            throw new ContainerException(sprintf(
                'Service provider must implement `%s` or `%s`, got `%s` instead.',
                CakeServiceProviderInterface::class,
                LeagueServiceProviderInterface::class,
                get_debug_type($provider),
            ));
        }

        parent::addServiceProvider($provider);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function extend(string $id): CakeDefinitionInterface&LeagueDefinitionInterface
    {
        return $this->wrapDefinition(parent::extend($id));
    }

    /**
     * @inheritDoc
     */
    public function delegate(PsrContainerInterface $container): static
    {
        parent::delegate($container);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function inflector(string $type, ?callable $callback = null): CakeInflectorInterface&LeagueInflectorInterface
    {
        return $this->wrapInflector(parent::inflector($type, $callback));
    }

    /**
     * @param \Cake\Container\Definition\DefinitionInterface $definition
     * @return \League\Container\Definition\DefinitionInterface&\Cake\Container\Definition\DefinitionInterface
     */
    protected function wrapDefinition(CakeDefinitionInterface $definition): CakeDefinitionInterface&LeagueDefinitionInterface
    {
        return new ContainerDefinitionAdapter($definition);
    }

    /**
     * @param \Cake\Container\Inflector\InflectorInterface $inflector
     * @return \Cake\Container\Inflector\InflectorInterface&\League\Container\Inflector\InflectorInterface
     */
    protected function wrapInflector(CakeInflectorInterface $inflector): CakeInflectorInterface&LeagueInflectorInterface
    {
        return new ContainerInflectorAdapter($inflector);
    }
}
