<?php
declare(strict_types=1);

namespace Cake\Container;

use Cake\Container\Argument\ArgumentResolverInterface;
use Cake\Container\Argument\ArgumentResolverTrait;
use Cake\Container\Exception\ContainerException;
use Cake\Container\Exception\NotFoundException;
use League\Container\Attribute\AttributeInterface;
use Psr\Container\ContainerInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class ReflectionContainer implements ArgumentResolverInterface, ContainerInterface
{
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    public const AUTO_WIRING = 0x01;
    public const ATTRIBUTE_RESOLUTION = 0x02;

    /**
     * @var bool
     */
    protected bool $cacheResolutions;

    /**
     * @var int
     */
    protected int $mode;

    /**
     * @var array
     */
    protected array $cache = [];

    /**
     * @param bool $cacheResolutions
     */
    public function __construct(
        bool $cacheResolutions = false,
        int $mode = self::AUTO_WIRING | self::ATTRIBUTE_RESOLUTION,
    ) {
        $this->cacheResolutions = $cacheResolutions;
        $this->mode = $mode;
    }

    /**
     * @param int $mode
     * @return void
     */
    public function setMode(int $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
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

    /**
     * @inheritDoc
     */
    public function reflectArguments(ReflectionFunctionAbstract $method, array $args = []): array
    {
        $params = $method->getParameters();
        $arguments = [];

        foreach ($params as $param) {
            $name = $param->getName();

            if (array_key_exists($name, $args)) {
                $arguments[] = new Argument\LiteralArgument($args[$name]);
                continue;
            }

            if ($this->mode & self::ATTRIBUTE_RESOLUTION) {
                foreach ($param->getAttributes() as $attribute) {
                    $argument = $this->resolveArgumentFromAttribute($attribute);
                    if ($argument !== null) {
                        $arguments[] = $argument;
                        continue 2;
                    }
                }
            }

            $type = $param->getType();

            if ($type instanceof ReflectionUnionType) {
                $this->throwParameterException(
                    $param,
                    'Union types are not supported',
                );
            }

            if (($this->mode & self::AUTO_WIRING) && $type instanceof ReflectionNamedType) {
                if ($type->getName() === 'mixed') {
                    $this->throwParameterException(
                        $param,
                        'Mixed types are not supported',
                    );
                }

                $typeHint = ltrim($type->getName(), '?');

                if ($param->isDefaultValueAvailable()) {
                    $arguments[] = new Argument\DefaultValueArgument($typeHint, $param->getDefaultValue());
                    continue;
                }

                $arguments[] = new Argument\ResolvableArgument($typeHint);
                continue;
            }

            if ($param->isDefaultValueAvailable()) {
                $arguments[] = new Argument\LiteralArgument($param->getDefaultValue());
                continue;
            }

            $this->throwParameterException(
                $param,
                'No default value available and no type hint to resolve',
            );
        }

        return $this->resolveArguments($arguments);
    }

    /**
     * @param \ReflectionAttribute<object> $attribute
     * @return \Cake\Container\Argument\LiteralArgument|null
     */
    protected function resolveArgumentFromAttribute(ReflectionAttribute $attribute): ?Argument\LiteralArgument
    {
        $attributeClass = $attribute->getName();
        if (!is_subclass_of($attributeClass, AttributeInterface::class)) {
            return null;
        }

        $instance = $attribute->newInstance();
        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->getContainer());
        }

        /** @var \League\Container\Attribute\AttributeInterface $instance */
        return new Argument\LiteralArgument($instance->resolve());
    }

    /**
     * @param \ReflectionParameter $parameter
     * @param string $message
     * @return void
     */
    protected function throwParameterException(ReflectionParameter $parameter, string $message): void
    {
        $function = $parameter->getDeclaringFunction();
        $class = $parameter->getDeclaringClass()?->getName();
        $suffix = $function instanceof ReflectionMethod && $function->isClosure() ? ' [closure]' : '';

        throw new NotFoundException(sprintf(
            'Unable to resolve parameter ($%s) in %s%s()%s',
            $parameter->getName(),
            $class ? $class . '::' : '',
            $function->getName(),
            $suffix ? ' - ' . trim($message . $suffix) : ' - ' . $message,
        ));
    }
}
