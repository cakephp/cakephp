<?php
declare(strict_types=1);

namespace Cake\Container\Definition;

use Cake\Container\Argument\ArgumentInterface;
use Cake\Container\Argument\ArgumentResolverInterface;
use Cake\Container\Argument\ArgumentResolverTrait;
use Cake\Container\Argument\LiteralArgumentInterface;
use Cake\Container\ContainerAwareTrait;
use Cake\Container\Exception\ContainerException;
use ReflectionClass;

class Definition implements ArgumentResolverInterface, DefinitionInterface
{
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected string $alias;

    /**
     * @var mixed
     */
    protected mixed $concrete;

    /**
     * @var bool
     */
    protected bool $shared = false;

    /**
     * @var array
     */
    protected array $tags = [];

    /**
     * @var array
     */
    protected array $arguments = [];

    /**
     * @var array
     */
    protected array $methods = [];

    /**
     * @var mixed
     */
    protected mixed $resolved = null;

    /**
     * @var array
     */
    protected array $recursiveCheck = [];

    /**
     * @param string     $id
     * @param mixed|null $concrete
     */
    public function __construct(string $id, mixed $concrete = null)
    {
        $id = static::normaliseAlias($id);

        $concrete ??= $id;
        $this->alias = $id;
        $this->concrete = $concrete;
    }

    /**
     * @inheritDoc
     */
    public function addTag(string $tag): DefinitionInterface
    {
        $this->tags[$tag] = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTags(): array
    {
        return array_keys($this->tags);
    }

    /**
     * @inheritDoc
     */
    public function hasTag(string $tag): bool
    {
        return isset($this->tags[$tag]);
    }

    /**
     * @inheritDoc
     */
    public function setAlias(string $id): DefinitionInterface
    {
        $id = static::normaliseAlias($id);

        $this->alias = $id;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @inheritDoc
     */
    public function setShared(bool $shared = true): DefinitionInterface
    {
        $this->shared = $shared;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * @inheritDoc
     */
    public function getConcrete(): mixed
    {
        return $this->concrete;
    }

    /**
     * @inheritDoc
     */
    public function setConcrete($concrete): DefinitionInterface
    {
        $this->concrete = $concrete;
        $this->resolved = null;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addArgument($arg, ?string $name = null): DefinitionInterface
    {
        if ($name) {
            $this->arguments[$name] = $arg;
        } else {
            $this->arguments[] = $arg;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addArguments(array $args): DefinitionInterface
    {
        foreach ($args as $argName => $arg) {
            if (is_string($argName)) {
                $this->addArgument($arg, $argName);
            } else {
                $this->addArgument($arg);
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addMethodCall(string $method, array $args = []): DefinitionInterface
    {
        $this->methods[] = [
            'method' => $method,
            'arguments' => $args,
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addMethodCalls(array $methods = []): DefinitionInterface
    {
        foreach ($methods as $method => $args) {
            $this->addMethodCall($method, $args);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function resolve(): mixed
    {
        if ($this->resolved !== null && $this->isShared()) {
            return $this->resolved;
        }

        return $this->resolveNew();
    }

    /**
     * @inheritDoc
     */
    public function resolveNew(): mixed
    {
        $concrete = $this->concrete;

        if (is_callable($concrete)) {
            $concrete = $this->resolveCallable($concrete);
        }

        if ($concrete instanceof LiteralArgumentInterface) {
            $this->resolved = $concrete->getValue();

            return $concrete->getValue();
        }

        if ($concrete instanceof ArgumentInterface) {
            $concrete = $concrete->getValue();
        }

        // Check if the container has a registered definition for this concrete class
        // before attempting to instantiate it directly. This ensures interface -> concrete
        // bindings respect existing definitions for the concrete class (fixes #275, #278).
        try {
            $container = $this->getContainer();
        } catch (ContainerException) {
            $container = null;
        }

        if (
            is_string($concrete)
            && $concrete !== $this->alias
            && $container !== null
            && $container->hasDefinition($concrete)
        ) {
            $this->recursiveCheck[] = $concrete;
            $concrete = $container->get($concrete);
            $this->resolved = $concrete;

            return $concrete;
        }

        if (is_string($concrete) && class_exists($concrete)) {
            $concrete = $this->resolveClass($concrete);
        }

        if (is_object($concrete)) {
            $concrete = $this->invokeMethods($concrete);
        }

        // stop recursive resolving
        if (is_string($concrete) && in_array($concrete, $this->recursiveCheck)) {
            $this->resolved = $concrete;

            return $concrete;
        }

        // if we still have a string, try to pull it from the container
        // this allows for `alias -> alias -> ... -> concrete
        if (is_string($concrete) && $container !== null && $container->has($concrete)) {
            $this->recursiveCheck[] = $concrete;
            $concrete = $container->get($concrete);
        }

        $this->resolved = $concrete;

        return $concrete;
    }

    /**
     * @param callable $concrete
     * @return mixed
     */
    protected function resolveCallable(callable $concrete): mixed
    {
        $resolved = $this->resolveArguments($this->arguments);

        return call_user_func_array($concrete, $resolved);
    }

    /**
     * @param class-string $concrete
     * @return object
     * @throws \ReflectionException
     */
    protected function resolveClass(string $concrete): object
    {
        $resolved = $this->resolveArguments($this->arguments);
        $reflection = new ReflectionClass($concrete);

        return $reflection->newInstanceArgs($resolved);
    }

    /**
     * @param object $instance
     * @return object
     */
    protected function invokeMethods(object $instance): object
    {
        foreach ($this->methods as $method) {
            $args = $this->resolveArguments($method['arguments']);
            /** @var callable $callable */
            $callable = [$instance, $method['method']];
            call_user_func_array($callable, $args);
        }

        return $instance;
    }

    /**
     * @param string $alias
     * @return string
     */
    public static function normaliseAlias(string $alias): string
    {
        if (str_starts_with($alias, '\\')) {
            return substr($alias, 1);
        }

        return $alias;
    }
}
