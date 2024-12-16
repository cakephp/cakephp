<?php
declare(strict_types=1);

namespace Cake\Container;

use Cake\Container\Definition\DefinitionInterface;
use Cake\Container\Inflector\InflectorInterface;
use Cake\Container\ServiceProvider\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

interface DefinitionContainerInterface extends ContainerInterface
{
    /**
     * @param string $id
     * @param mixed $concrete
     * @return \Cake\Container\Definition\DefinitionInterface
     */
    public function add(string $id, mixed $concrete = null): DefinitionInterface;

    /**
     * @param \Cake\Container\ServiceProvider\ServiceProviderInterface $provider
     * @return self
     */
    public function addServiceProvider(ServiceProviderInterface $provider): self;

    /**
     * @param string $id
     * @param mixed $concrete
     * @return \Cake\Container\Definition\DefinitionInterface
     */
    public function addShared(string $id, mixed $concrete = null): DefinitionInterface;

    /**
     * @param string $id
     * @return \Cake\Container\Definition\DefinitionInterface
     */
    public function extend(string $id): DefinitionInterface;

    /**
     * @param mixed $id
     * @return mixed
     */
    public function getNew(mixed $id): mixed;

    /**
     * @param string $type
     * @param callable|null $callback
     * @return \Cake\Container\Inflector\InflectorInterface
     */
    public function inflector(string $type, ?callable $callback = null): InflectorInterface;
}
