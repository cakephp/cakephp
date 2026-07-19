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
    public function add(string $id, $concrete = null): DefinitionInterface
    {
        $concrete ??= $id;

        if ($this->defaultToShared) {
            return $this->addShared($id, $concrete);
        }

        return $this->definitions->add($id, $concrete);
    }

    /**
     * @inheritDoc
     */
    public function addShared(string $id, $concrete = null): DefinitionInterface
    {
        $concrete ??= $id;

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
            } elseif (is_string($id) && is_array($definition)) { // @phpstan-ignore-line
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
    public function addServiceProvider(ServiceProviderInterface $provider): DefinitionContainerInterface
    {
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
     * @param array<string, mixed> $args
     * @return mixed|object|array|null|void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function resolve(mixed $id, bool $new = false, array $args = []): mixed
    {
        if ($this->definitions->has($id)) {
            $resolved = $new ? $this->definitions->resolveNew($id) : $this->definitions->resolve($id);

            return $this->inflectors->inflect($resolved);
        }

        if ($this->definitions->hasTag($id)) {
            $arrayOf = $new
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

            return $this->resolve($id, $new, $args);
        }

        foreach ($this->delegates as $delegate) {
            if ($delegate->has($id)) {
                // Use getNew() for ReflectionContainer when $new is true or args are provided
                if ($delegate instanceof ReflectionContainer) {
                    $resolved = $new || $args !== []
                        ? $delegate->getNew($id, $args)
                        : $delegate->get($id, $args);
                } else {
                    $resolved = $delegate->get($id);
                }

                return $this->inflectors->inflect($resolved);
            }
        }

        throw new NotFoundException(sprintf('Alias (%s) is not being managed by the container or delegates', $id));
    }
}
