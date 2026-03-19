<?php
declare(strict_types=1);

namespace Cake\Core;

use Cake\Container\Inflector\InflectorInterface as CakeInflectorInterface;
use League\Container\Inflector\InflectorInterface as LeagueInflectorInterface;

class ContainerInflectorAdapter implements CakeInflectorInterface, LeagueInflectorInterface
{
    /**
     * @param \Cake\Container\Inflector\InflectorInterface $inflector
     */
    public function __construct(protected CakeInflectorInterface $inflector)
    {
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->inflector->getType();
    }

    /**
     * @inheritDoc
     */
    public function inflect(object $object): void
    {
        $this->inflector->inflect($object);
    }

    /**
     * @inheritDoc
     */
    public function invokeMethod(string $name, array $args): static
    {
        $this->inflector->invokeMethod($name, $args);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function invokeMethods(array $methods): static
    {
        $this->inflector->invokeMethods($methods);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setProperties(array $properties): static
    {
        $this->inflector->setProperties($properties);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setProperty(string $property, mixed $value): static
    {
        $this->inflector->setProperty($property, $value);

        return $this;
    }
}
