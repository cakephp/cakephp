<?php
declare(strict_types=1);

namespace Cake\Container;

use Cake\Container\Definition\DefinitionAggregate;
use Cake\Container\Definition\DefinitionAggregateInterface;
use Cake\Container\Definition\DefinitionInterface;
use Cake\Container\Exception\ContainerException;
use Cake\Container\Exception\NotFoundException;
use Cake\Container\Inflector\InflectorAggregate;
use Cake\Container\Inflector\InflectorAggregateInterface;
use Cake\Container\Inflector\InflectorInterface;
use Cake\Container\ServiceProvider\ServiceProviderAggregate;
use Cake\Container\ServiceProvider\ServiceProviderAggregateInterface;
use Cake\Container\ServiceProvider\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

class Container implements DefinitionContainerInterface
{
    /**
     * @var bool
     */
    protected bool $defaultToShared = false;

    /**
     * @var \Cake\Container\Definition\DefinitionAggregateInterface
     */
    protected DefinitionAggregateInterface $definitions;

    /**
     * @var \Cake\Container\ServiceProvider\ServiceProviderAggregateInterface
     */
    protected ServiceProviderAggregateInterface $providers;

    /**
     * @var \Cake\Container\Inflector\InflectorAggregateInterface
     */
    protected InflectorAggregateInterface $inflectors;

    /**
     * @var array<\Psr\Container\ContainerInterface>
     */
    protected array $delegates = [];

    /**
     * @param \Cake\Container\Definition\DefinitionAggregateInterface|null $definitions
     * @param \Cake\Container\ServiceProvider\ServiceProviderAggregateInterface|null $providers
     * @param \Cake\Container\Inflector\InflectorAggregateInterface|null $inflectors
     */
    public function __construct(
        ?DefinitionAggregateInterface $definitions = null,
        ?ServiceProviderAggregateInterface $providers = null,
        ?InflectorAggregateInterface $inflectors = null
    ) {
        $this->definitions = $definitions ?? new DefinitionAggregate();
        $this->providers = $providers ?? new ServiceProviderAggregate();
        $this->inflectors = $inflectors ?? new InflectorAggregate();

        $this->definitions->setContainer($this);
        $this->providers->setContainer($this);
        $this->inflectors->setContainer($this);

        $this->enableAutoWiring();
    }

    /**
     * @inheritDoc
     */
    public function add(string $id, $concrete = null): DefinitionInterface
    {
        $concrete = $concrete ?? $id;

        if ($this->defaultToShared === true) {
            return $this->addShared($id, $concrete);
        }

        return $this->definitions->add($id, $concrete);
    }

    /**
     * @inheritDoc
     */
    public function addShared(string $id, $concrete = null): DefinitionInterface
    {
        $concrete = $concrete ?? $id;

        return $this->definitions->addShared($id, $concrete);
    }

    /**
     * @param bool $shared
     * @return \Psr\Container\ContainerInterface
     */
    public function defaultToShared(bool $shared = true): ContainerInterface
    {
        $this->defaultToShared = $shared;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function extend(string $id): DefinitionInterface
    {
        if ($this->providers->provides($id)) {
            $this->providers->register($id);
        }

        if ($this->definitions->has($id)) {
            return $this->definitions->getDefinition($id);
        }

        throw new NotFoundException(sprintf(
            'Unable to extend alias (%s) as it is not being managed as a definition',
            $id
        ));
    }

    /**
     * @inheritDoc
     */
    public function addServiceProvider(ServiceProviderInterface $provider): DefinitionContainerInterface
    {
        $this->providers->add($provider);

        return $this;
    }

    /**
     * @template RequestedType
     * @param class-string<RequestedType>|string $id
     * @return RequestedType|mixed
     */
    public function get(string $id)
    {
        return $this->resolve($id);
    }

    /**
     * @template RequestedType
     * @param class-string<RequestedType>|string $id
     * @return RequestedType|mixed
     */
    public function getNew(mixed $id): mixed
    {
        return $this->resolve($id, true);
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        if ($this->definitions->has($id)) {
            return true;
        }

        if ($this->definitions->hasTag($id)) {
            return true;
        }

        if ($this->providers->provides($id)) {
            return true;
        }

        foreach ($this->delegates as $delegate) {
            if ($delegate->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function inflector(string $type, ?callable $callback = null): InflectorInterface
    {
        return $this->inflectors->add($type, $callback);
    }

    /**
     * @param \Psr\Container\ContainerInterface $container
     * @return $this
     */
    public function delegate(ContainerInterface $container)
    {
        $this->delegates[] = $container;

        if ($container instanceof ContainerAwareInterface) {
            $container->setContainer($this);
        }

        return $this;
    }

    /**
     * @param bool $cache
     * @return void
     */
    public function enableAutoWiring(bool $cache = true): void
    {
        $this->delegate(new ReflectionContainer($cache));
    }

    /**
     * @return void
     */
    public function disableAutoWiring(): void
    {
        $this->delegates = [];
    }

    /**
     * @param mixed $id
     * @param bool $new
     * @return mixed|object|array|null|void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function resolve(mixed $id, bool $new = false): mixed
    {
        if ($this->definitions->has($id)) {
            $resolved = $new === true ? $this->definitions->resolveNew($id) : $this->definitions->resolve($id);

            return $this->inflectors->inflect($resolved);
        }

        if ($this->definitions->hasTag($id)) {
            $arrayOf = $new === true
                ? $this->definitions->resolveTaggedNew($id)
                : $this->definitions->resolveTagged($id);

            array_walk($arrayOf, function (object &$resolved): void {
                $resolved = $this->inflectors->inflect($resolved);
            });

            return $arrayOf;
        }

        if ($this->providers->provides($id)) {
            $this->providers->register($id);

            if (!$this->definitions->has($id) && !$this->definitions->hasTag($id)) {
                throw new ContainerException(sprintf('Service provider lied about providing (%s) service', $id));
            }

            return $this->resolve($id, $new);
        }

        foreach ($this->delegates as $delegate) {
            if ($delegate->has($id)) {
                $resolved = $delegate->get($id);

                return $this->inflectors->inflect($resolved);
            }
        }

        throw new NotFoundException(sprintf('Alias (%s) is not being managed by the container or delegates', $id));
    }
}
