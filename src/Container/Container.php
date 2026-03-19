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
     * @var array<\Psr\Container\ContainerInterface>
     */
    protected array $delegates = [];

    /**
     * @param \Cake\Container\Definition\DefinitionAggregateInterface $definitions
     * @param \Cake\Container\ServiceProvider\ServiceProviderAggregateInterface $providers
     * @param \Cake\Container\Inflector\InflectorAggregateInterface $inflectors
     */
    public function __construct(
        protected DefinitionAggregateInterface $definitions = new DefinitionAggregate(),
        protected ServiceProviderAggregateInterface $providers = new ServiceProviderAggregate(),
        protected InflectorAggregateInterface $inflectors = new InflectorAggregate(),
    ) {
        $this->definitions->setContainer($this);
        $this->providers->setContainer($this);
        $this->inflectors->setContainer($this);

        $this->enableAutoWiring();
    }

    /**
     * @inheritDoc
     */
    public function add(string $id, $concrete = null, bool $overwrite = false): DefinitionInterface
    {
        $concrete ??= $id;

        if ($overwrite && $this->definitions->has($id)) {
            return $this->definitions->getDefinition($id)->setConcrete($concrete);
        }

        if ($this->defaultToShared) {
            return $this->addShared($id, $concrete, $overwrite);
        }

        return $this->definitions->add($id, $concrete);
    }

    /**
     * @inheritDoc
     */
    public function addShared(string $id, $concrete = null, bool $overwrite = false): DefinitionInterface
    {
        $concrete ??= $id;

        if ($overwrite && $this->definitions->has($id)) {
            return $this->definitions->getDefinition($id)
                ->setConcrete($concrete)
                ->setShared(true);
        }

        return $this->definitions->addShared($id, $concrete);
    }

    /**
     * Add multiple definitions at once.
     *
     * Examples:
     *
     * ```
     * $container->addDefinitions([
     *     Foo::class,
     *     Bar::class
     * ]);
     * ```
     *
     * ```
     * $container->addDefinitions([
     *     Foo::class => [Bar::class],
     *     Bar::class
     * ]);
     * ```
     *
     * ```
     * $container->addDefinitions([
     *    'foo' => Foo::class,
     *    'bar' => Bar::class
     * ]);
     * ```
     *
     * @param array<int|string, array<class-string>|class-string> $definitions
     * @return \Cake\Container\DefinitionContainerInterface
     */
    public function addDefinitions(array $definitions): DefinitionContainerInterface
    {
        foreach ($definitions as $id => $definition) {
            if (is_int($id) && is_string($definition)) {
                $this->add($definition);
            } elseif (is_string($id) && is_string($definition)) {
                $this->add($id, $definition);
            } elseif (is_string($id) && is_array($definition)) {
                $this->add($id)
                    ->addArguments($definition);
            }
        }

        return $this;
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
            $id,
        ));
    }

    /**
     * @inheritDoc
     */
    public function addServiceProvider(mixed $provider): DefinitionContainerInterface
    {
        if (!$provider instanceof ServiceProviderInterface) {
            throw new ContainerException(sprintf(
                'Service provider must implement `%s`, got `%s` instead.',
                ServiceProviderInterface::class,
                get_debug_type($provider),
            ));
        }

        $this->providers->add($provider);

        return $this;
    }

    /**
     * @template RequestedType
     * @param class-string<RequestedType>|string $id
     * @return RequestedType|mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function get(string $id)
    {
        return $this->resolve($id);
    }

    /**
     * @template RequestedType
     * @param class-string<RequestedType>|string $id
     * @return RequestedType|mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getNew(mixed $id): mixed
    {
        return $this->resolve($id, true);
    }

    /**
     * Resolve an entry with specific constructor arguments.
     *
     * Unlike `get()`, this method allows passing specific constructor arguments
     * that will be used during autowiring. Arguments can be passed by name.
     *
     * Example:
     * ```
     * $container->make(MyService::class, ['configValue' => 'foo']);
     * ```
     *
     * @template RequestedType
     * @param class-string<RequestedType>|string $id
     * @param array<string, mixed> $args Named arguments to pass to the constructor
     * @return RequestedType|mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function make(string $id, array $args = []): mixed
    {
        return $this->resolve($id, true, $args);
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
    public function hasDefinition(string $id): bool
    {
        return $this->definitions->has($id);
    }

    /**
     * @inheritDoc
     */
    public function inflector(string $type, ?callable $callback = null): InflectorInterface
    {
        return $this->inflectors->add($type, $callback);
    }

    /**
     * Add a container delegate.
     *
     * @param \Psr\Container\ContainerInterface $container
     * @return $this
     */
    public function delegate(ContainerInterface $container)
    {
        $this->delegates[] = $container;

        if ($container instanceof ReflectionContainer) {
            $container->setContainer($this);
        }

        return $this;
    }

    /**
     * Enable autowiring by delegating to the reflection container.
     *
     * @param bool $cache
     * @return void
     */
    public function enableAutoWiring(bool $cache = true): void
    {
        $reflectionContainer = new ReflectionContainer($cache);
        $reflectionContainer->setContainer($this);
        $this->delegate($reflectionContainer);
    }

    /**
     * Disable autowiring by clearing delegates.
     *
     * @return void
     */
    public function disableAutoWiring(): void
    {
        $this->delegates = [];
    }

    /**
     * @param string $id
     * @param bool $new
     * @param array<string, mixed> $args
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function resolve(string $id, bool $new = false, array $args = []): mixed
    {
        if ($this->definitions->has($id)) {
            $definition = $this->definitions->getDefinition($id);
            if ($args !== []) {
                $definition = clone $definition;
                $definition->addArguments($args);
            }

            $instance = $new ? $definition->resolveNew() : $definition->resolve();
            $this->inflectors->inflect($instance);

            return $instance;
        }

        if ($this->definitions->hasTag($id)) {
            $instances = $new ? $this->definitions->resolveTaggedNew($id) : $this->definitions->resolveTagged($id);
            array_walk($instances, function (object &$resolved): void {
                $resolved = $this->inflectors->inflect($resolved);
            });

            return $instances;
        }

        if ($this->providers->provides($id)) {
            $this->providers->register($id);

            try {
                return $this->resolve($id, $new, $args);
            } catch (NotFoundException) {
                throw new ContainerException(sprintf('Service provider lied about providing (%s) service', $id));
            }
        }

        foreach ($this->delegates as $delegate) {
            if ($delegate->has($id)) {
                if ($delegate instanceof ReflectionContainer) {
                    $instance = $new || $args !== []
                        ? $delegate->getNew($id, $args)
                        : $delegate->get($id, $args);
                } else {
                    $instance = $delegate->get($id);
                }
                $this->inflectors->inflect($instance);

                return $instance;
            }
        }

        throw new NotFoundException(sprintf(
            'Alias (%s) is not being managed by the container or delegates',
            $id,
        ));
    }
}
