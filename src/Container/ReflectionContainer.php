<?php
declare(strict_types=1);

namespace Cake\Container;

use Cake\Container\Argument\ArgumentResolverInterface;
use Cake\Container\Argument\ArgumentResolverTrait;
use Cake\Container\Exception\ContainerException;
use Cake\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class ReflectionContainer implements ArgumentResolverInterface, ContainerInterface
{
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    /**
     * @var bool
     */
    protected bool $cacheResolutions;

    /**
     * @var array
     */
    protected array $cache = [];

    /**
     * @param bool $cacheResolutions
     */
    public function __construct(bool $cacheResolutions = false)
    {
        $this->cacheResolutions = $cacheResolutions;
    }

    /**
     * @inheritDoc
     */
    public function get(string $id, array $args = [])
    {
        // Only use cache when no custom args are provided
        if ($this->cacheResolutions && $args === [] && array_key_exists($id, $this->cache)) {
            return $this->cache[$id];
        }

        if (!$this->has($id)) {
            throw new NotFoundException(
                sprintf('Alias (%s) is not an existing class and therefore cannot be resolved', $id),
            );
        }

        /** @var class-string $id */
        $reflector = new ReflectionClass($id);
        $construct = $reflector->getConstructor();

        if ($construct && !$construct->isPublic()) {
            throw new NotFoundException(
                sprintf('Alias (%s) has a non-public constructor and therefore cannot be instantiated', $id),
            );
        }

        $resolution = $construct === null
            ? new $id()
            : $reflector->newInstanceArgs($this->reflectArguments($construct, $args));

        // Only cache when no custom args are provided
        if ($this->cacheResolutions && $args === []) {
            $this->cache[$id] = $resolution;
        }

        return $resolution;
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        return class_exists($id);
    }

    /**
     * Get a new instance, bypassing the cache.
     *
     * @param string $id
     * @param array<string, mixed> $args
     * @return mixed
     */
    public function getNew(string $id, array $args = []): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException(
                sprintf('Alias (%s) is not an existing class and therefore cannot be resolved', $id),
            );
        }

        /** @var class-string $id */
        $reflector = new ReflectionClass($id);
        $construct = $reflector->getConstructor();

        if ($construct && !$construct->isPublic()) {
            throw new NotFoundException(
                sprintf('Alias (%s) has a non-public constructor and therefore cannot be instantiated', $id),
            );
        }

        return $construct === null
            ? new $id()
            : $reflector->newInstanceArgs($this->reflectArguments($construct, $args));
    }

    /**
     * @param callable|string $callable
     * @param array $args
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function call(callable|string $callable, array $args = []): mixed
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable)) {
            if (is_string($callable[0])) {
                // if we have a definition container, try that first, otherwise, reflect
                try {
                    $callable[0] = $this->getContainer()->get($callable[0]);
                } catch (ContainerException) {
                    $callable[0] = $this->get($callable[0]);
                }
            }

            $reflection = new ReflectionMethod($callable[0], $callable[1]);

            if ($reflection->isStatic()) {
                $callable[0] = null;
            }

            return $reflection->invokeArgs($callable[0], $this->reflectArguments($reflection, $args));
        }

        if (is_object($callable)) {
            $reflection = new ReflectionMethod($callable, '__invoke');

            return $reflection->invokeArgs($callable, $this->reflectArguments($reflection, $args));
        }

        if (is_callable($callable)) {
            $reflection = new ReflectionFunction($callable(...));

            return $reflection->invokeArgs($this->reflectArguments($reflection, $args));
        }

        throw new NotFoundException(sprintf(
            'Callable (%s) is not a valid callable',
            $callable,
        ));
    }
}
