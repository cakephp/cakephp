<?php
declare(strict_types=1);

namespace Cake\Container\Inflector;

use Cake\Container\Argument\ArgumentResolverInterface;
use Cake\Container\Argument\ArgumentResolverTrait;
use Cake\Container\ContainerAwareTrait;

class Inflector implements ArgumentResolverInterface, InflectorInterface
{
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected string $type;

    /**
     * @var callable|null
     */
    protected $callback;

    /**
     * @var array
     */
    protected array $methods = [];

    /**
     * @var array
     */
    protected array $properties = [];

    /**
     * @param string $type
     * @param callable|null $callback
     */
    public function __construct(string $type, ?callable $callback = null)
    {
        $this->type = $type;
        $this->callback = $callback;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function invokeMethod(string $name, array $args): InflectorInterface
    {
        $this->methods[$name] = $args;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function invokeMethods(array $methods): InflectorInterface
    {
        foreach ($methods as $name => $args) {
            $this->invokeMethod($name, $args);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setProperty(string $property, $value): InflectorInterface
    {
        $this->properties[$property] = $this->resolveArguments([$value])[0];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setProperties(array $properties): InflectorInterface
    {
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function inflect(object $object): void
    {
        $properties = $this->resolveArguments(array_values($this->properties));
        $properties = array_combine(array_keys($this->properties), $properties);

        // array_combine() can technically return false
        foreach ($properties ?: [] as $property => $value) {
            $object->{$property} = $value;
        }

        foreach ($this->methods as $method => $args) {
            $args = $this->resolveArguments($args);
            /** @var callable $callable */
            $callable = [$object, $method];
            call_user_func_array($callable, $args);
        }

        if ($this->callback !== null) {
            call_user_func($this->callback, $object);
        }
    }
}
